<?php

namespace App\Support\Listings;

use App\Models\Category;
use App\Models\Listing;
use App\Support\EventTypes;
use App\Support\Seo\CategoryLandingSeo;
use App\Support\Seo\Landing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders a category page for any Landing: the plain category, or one narrowed to a
 * place and/or an event type. The narrowed variants are the SEO landing pages for
 * "<category> [<event>] <place>" searches.
 */
class CategoryPage
{
    public function __construct(
        private readonly ListingFacets $facets,
        private readonly CategoryLandingSeo $seo,
    ) {}

    public function render(Request $request, Landing $landing): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'county_ids' => ['nullable', 'array'],
            'county_ids.*' => ['integer', 'exists:counties,id'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'in:4,4.5'],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:recommended,price_asc,price_desc,rating,newest'],
        ]);

        $category = $landing->category;
        $county = $landing->county;
        $locality = $landing->locality;
        $eventType = $landing->eventType;

        // On a place page the place itself is the location filter.
        if ($county) {
            unset($validated['county_ids']);
        }

        $sort = $validated['sort'] ?? 'recommended';
        $filters = ListingFilters::fromValidated($validated + ['category_id' => $category->id]);

        $base = fn (): Builder => Listing::query()
            ->where('status', 'published')
            ->where('category_id', $category->id)
            ->when($county, fn (Builder $q) => $q->where('county_id', $county->id))
            ->when($locality, fn (Builder $q) => $q->where('locality_id', $locality->id))
            ->when($eventType, fn (Builder $q) => ListingQueryScope::servesEventTypes($q, [$eventType]));

        $listings = ListingQueryScope::apply(
            $base()
                ->with([
                    'providerProfile:id,company_name,slug,logo_path,phone,whatsapp,email',
                    'county:id,name',
                    'locality:id,name',
                    'media' => fn ($query) => $query->where('is_cover', true)->orWhere('position', 0),
                ])
                ->withAvg('approvedReviews', 'rating')
                ->withCount('approvedReviews'),
            $filters
        )
            ->when($sort === 'price_asc', fn ($query) => $query->orderByRaw('price_from IS NULL, price_from asc'))
            ->when($sort === 'price_desc', fn ($query) => $query->orderByRaw('price_from IS NULL, price_from desc'))
            ->when($sort === 'rating', fn ($query) => $query->orderByDesc('approved_reviews_avg_rating'))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('published_at'))
            ->when($sort === 'recommended', fn ($query) => $query->orderByDesc('is_featured')->orderByDesc('approved_reviews_avg_rating'))
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Listing $listing) => $this->present($listing));

        $user = $request->user();
        $favoriteListingIds = $user
            ? $user->favorites()->pluck('listing_id')
            : collect();

        $recommendedQuery = fn () => $base()
            ->with(['providerProfile:id,company_name,slug', 'county:id,name', 'locality:id,name'])
            ->withAvg('approvedReviews', 'rating')
            ->orderByDesc('approved_reviews_avg_rating')
            ->take(3);

        $recommended = $recommendedQuery()->where('is_featured', true)->get();

        if ($recommended->isEmpty()) {
            $recommended = $recommendedQuery()->get();
        }

        $recommended = $recommended->map(fn (Listing $listing) => $this->present($listing));

        $facetData = $this->facets->compute(
            $base(),
            $filters,
            ['counties', 'rating', 'featured', 'price']
        );

        $reviewStats = $base()
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->get(['id', 'price_from', 'price_type', 'event_types']);

        $reviewsCount = $reviewStats->sum('approved_reviews_count');
        $avgRating = $reviewStats
            ->filter(fn ($listing) => $listing->approved_reviews_avg_rating !== null)
            ->avg('approved_reviews_avg_rating');
        $minPrice = $reviewStats
            ->filter(fn ($listing) => $listing->price_type !== 'on_request' && $listing->price_from > 0)
            ->min('price_from');

        $stats = [
            'listingsCount' => $reviewStats->count(),
            'avgRating' => $avgRating ? round((float) $avgRating, 1) : null,
            'reviewsCount' => $reviewsCount,
            'minPrice' => $minPrice ? (int) $minPrice : null,
            'taggedCount' => $eventType ? $reviewStats->filter(fn (Listing $listing) => CategoryLandingSeo::tagged($listing, $eventType))->count() : 0,
        ];

        $hasFilters = collect($validated)->except('sort')->filter(fn ($value) => filled($value))->isNotEmpty()
            || $sort !== 'recommended';

        return Inertia::render('Categories/Show', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'place' => $county ? [
                'county' => ['id' => $county->id, 'name' => $county->displayName(), 'region' => $county->regionLabel(), 'slug' => $county->slug],
                'locality' => $locality ? ['name' => $locality->name, 'slug' => $locality->slug] : null,
            ] : null,
            'eventType' => $eventType ? ['value' => $eventType, 'label' => EventTypes::LABELS[$eventType]] : null,
            'pageUrl' => $landing->url(),
            'heading' => $landing->heading(),
            'breadcrumbs' => $landing->breadcrumbs(),
            'intro' => $this->seo->intro($landing, $stats),
            'places' => $this->seo->places($landing),
            'stats' => $stats,
            'listings' => $listings,
            'recommended' => $recommended,
            'favoriteListingIds' => $favoriteListingIds,
            'counties' => $county ? [] : $facetData['counties'],
            'facets' => [
                'rating' => $facetData['rating'],
                'featured' => $facetData['featured'],
                'price' => $facetData['price'],
            ],
            'filters' => [
                'q' => $validated['q'] ?? '',
                'county_ids' => $validated['county_ids'] ?? [],
                'price_min' => $validated['price_min'] ?? null,
                'price_max' => $validated['price_max'] ?? null,
                'rating' => $validated['rating'] ?? null,
                'featured' => $validated['featured'] ?? false,
                'sort' => $sort,
            ],
            'categories' => Category::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['id', 'name', 'slug']),
            'seo' => $this->seo->meta($landing, $stats, $listings, $hasFilters)->toArray(),
        ]);
    }

    private function present(Listing $listing): array
    {
        return [
            'id' => $listing->id,
            'slug' => $listing->slug,
            'title' => $listing->title,
            'price_from' => $listing->price_from,
            'price_to' => $listing->price_to,
            'price_type' => $listing->price_type,
            'is_featured' => $listing->is_featured,
            'county' => $listing->county?->name,
            'locality' => $listing->locality?->name,
            'cover_url' => $listing->media->first() ? "/storage/{$listing->media->first()->path}" : null,
            'rating' => $listing->approved_reviews_avg_rating ? round((float) $listing->approved_reviews_avg_rating, 1) : null,
            'reviews_count' => $listing->approved_reviews_count ?? 0,
            'provider' => [
                'company_name' => $listing->providerProfile->company_name,
                'slug' => $listing->providerProfile->slug,
                'logo_url' => $listing->providerProfile->logoUrl(),
                'phone' => $listing->providerProfile->phone,
                'whatsapp' => $listing->providerProfile->whatsapp,
                'email' => $listing->providerProfile->email,
            ],
        ];
    }
}
