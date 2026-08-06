<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use App\Support\Listings\ListingFacets;
use App\Support\Listings\ListingFilters;
use App\Support\Listings\ListingQueryScope;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class Show extends Controller
{
    public function __invoke(Request $request, Category $category, ListingFacets $facets): Response
    {
        abort_unless($category->is_active, HttpResponse::HTTP_NOT_FOUND);

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

        $sort = $validated['sort'] ?? 'recommended';
        $filters = ListingFilters::fromValidated($validated + ['category_id' => $category->id]);

        $listings = ListingQueryScope::apply(
            Listing::query()
                ->where('status', 'published')
                ->where('category_id', $category->id)
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

        $recommendedQuery = fn () => Listing::query()
            ->where('status', 'published')
            ->where('category_id', $category->id)
            ->with(['providerProfile:id,company_name,slug', 'county:id,name', 'locality:id,name'])
            ->withAvg('approvedReviews', 'rating')
            ->orderByDesc('approved_reviews_avg_rating')
            ->take(3);

        $recommended = $recommendedQuery()->where('is_featured', true)->get();

        if ($recommended->isEmpty()) {
            $recommended = $recommendedQuery()->get();
        }

        $recommended = $recommended->map(fn (Listing $listing) => $this->present($listing));

        $facetData = $facets->compute(
            Listing::query()->where('status', 'published')->where('category_id', $category->id),
            $filters,
            ['counties', 'rating', 'featured', 'price']
        );

        $totalListingsCount = Listing::query()
            ->where('status', 'published')
            ->where('category_id', $category->id)
            ->count();

        $reviewStats = Listing::query()
            ->where('status', 'published')
            ->where('category_id', $category->id)
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->get();

        $reviewsCount = $reviewStats->sum('approved_reviews_count');
        $avgRating = $reviewStats
            ->filter(fn ($listing) => $listing->approved_reviews_avg_rating !== null)
            ->avg('approved_reviews_avg_rating');

        return Inertia::render('Categories/Show', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'stats' => [
                'listingsCount' => $totalListingsCount,
                'avgRating' => $avgRating ? round((float) $avgRating, 1) : null,
                'reviewsCount' => $reviewsCount,
            ],
            'listings' => $listings,
            'recommended' => $recommended,
            'favoriteListingIds' => $favoriteListingIds,
            'counties' => $facetData['counties'],
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
