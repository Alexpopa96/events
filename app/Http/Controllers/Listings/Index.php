<?php

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use App\Support\EventTypes;
use App\Support\Listings\ListingFacets;
use App\Support\Listings\ListingFilters;
use App\Support\Listings\ListingQueryScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request, ListingFacets $facets): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:100'],
            'event_type' => ['nullable', 'string', Rule::in(EventTypes::values())],
            'event_types' => ['nullable', 'array'],
            'event_types.*' => ['string', Rule::in(EventTypes::values())],
            'county_ids' => ['nullable', 'array'],
            'county_ids.*' => ['integer', 'exists:counties,id'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'in:4,4.5'],
            'featured' => ['nullable', 'boolean'],
            'available_on' => ['nullable', 'date', 'after_or_equal:today'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,rating'],
        ]);

        $sort = $validated['sort'] ?? 'newest';
        $filters = ListingFilters::fromValidated($validated);

        $listings = ListingQueryScope::apply(
            Listing::query()
                ->where('status', 'published')
                ->with([
                    'category:id,name,slug',
                    'providerProfile:id,company_name,slug,logo_path',
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
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('is_featured')->orderByDesc('published_at'))
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Listing $listing) => $this->present($listing));

        $user = $request->user();
        $favoriteListingIds = $user
            ? $user->favorites()->pluck('listing_id')
            : collect();

        $facetData = $facets->compute(
            Listing::query()->where('status', 'published'),
            $filters,
            ['counties', 'rating', 'featured', 'price', 'categories', 'event_types']
        );

        return Inertia::render('Listings/Index', [
            'listings' => $listings,
            'favoriteListingIds' => $favoriteListingIds,
            'filters' => [
                'q' => $validated['q'] ?? '',
                'categories' => $filters->categorySlugs,
                'event_types' => $filters->eventTypes,
                'county_ids' => $validated['county_ids'] ?? [],
                'price_min' => $validated['price_min'] ?? null,
                'price_max' => $validated['price_max'] ?? null,
                'rating' => $validated['rating'] ?? null,
                'featured' => $validated['featured'] ?? false,
                'available_on' => $validated['available_on'] ?? null,
                'sort' => $sort,
            ],
            'categories' => Category::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['id', 'name', 'slug']),
            'eventTypes' => EventTypes::options(),
            'counties' => $facetData['counties'],
            'facets' => [
                'rating' => $facetData['rating'],
                'featured' => $facetData['featured'],
                'price' => $facetData['price'],
                'categories' => $facetData['categories'],
                'event_types' => $facetData['event_types'],
            ],
        ]);
    }

    private function present(Listing $listing): array
    {
        return [
            'id' => $listing->id,
            'slug' => $listing->slug,
            'title' => $listing->title,
            'category' => $listing->category->name,
            'category_slug' => $listing->category->slug,
            'price_from' => $listing->price_from,
            'price_to' => $listing->price_to,
            'price_type' => $listing->price_type,
            'is_featured' => $listing->is_featured,
            'county' => $listing->county?->name,
            'locality' => $listing->locality?->name,
            'cover_url' => $listing->media->first() ? "/storage/{$listing->media->first()->path}" : null,
            'rating' => $listing->approved_reviews_avg_rating ? round((float) $listing->approved_reviews_avg_rating, 1) : null,
            'reviews_count' => $listing->approved_reviews_count,
            'provider' => [
                'company_name' => $listing->providerProfile->company_name,
                'slug' => $listing->providerProfile->slug,
                'logo_url' => $listing->providerProfile->logoUrl(),
            ],
        ];
    }
}
