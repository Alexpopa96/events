<?php

namespace App\Http\Controllers\Providers;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'county_ids' => ['nullable', 'array'],
            'county_ids.*' => ['integer', 'exists:counties,id'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'in:4,4.5'],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:recommended,price_asc,price_desc,rating,newest'],
        ]);

        $providers = $this->filteredQuery($filters)
            ->with([
                'county:id,name',
                'locality:id,name',
                'listings' => fn ($query) => $query->where('status', 'published')->with('category:id,name,slug'),
            ])
            ->withAvg(['reviews as reviews_avg_rating' => fn ($query) => $query->where('status', 'approved')], 'rating')
            ->withCount(['reviews as reviews_count' => fn ($query) => $query->where('status', 'approved')])
            ->withMin(['listings as min_price' => fn ($query) => $query->where('status', 'published')->where('price_type', '!=', 'on_request')->whereNotNull('price_from')], 'price_from')
            ->addSelect(['has_featured_listing' => Listing::selectRaw('count(*) > 0')
                ->whereColumn('listings.provider_profile_id', 'provider_profiles.id')
                ->where('status', 'published')
                ->where('is_featured', true),
            ])
            ->when(($filters['sort'] ?? null) === 'price_asc', fn ($query) => $query->orderByRaw('min_price IS NULL, min_price asc'))
            ->when(($filters['sort'] ?? null) === 'price_desc', fn ($query) => $query->orderByRaw('min_price IS NULL, min_price desc'))
            ->when(($filters['sort'] ?? null) === 'rating', fn ($query) => $query->orderByDesc('reviews_avg_rating'))
            ->when(($filters['sort'] ?? null) === 'newest', fn ($query) => $query->orderByDesc('approved_at'))
            ->when(! isset($filters['sort']) || $filters['sort'] === 'recommended', fn ($query) => $query->orderByDesc('has_featured_listing')->orderByDesc('reviews_avg_rating'))
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ProviderProfile $provider) => $this->present($provider));

        $recommendedQuery = fn () => ProviderProfile::query()
            ->where('status', 'active')
            ->with(['county:id,name', 'locality:id,name', 'listings' => fn ($query) => $query->where('status', 'published')->with('category:id,name,slug')])
            ->withAvg(['reviews as reviews_avg_rating' => fn ($query) => $query->where('status', 'approved')], 'rating')
            ->withCount(['reviews as reviews_count' => fn ($query) => $query->where('status', 'approved')])
            ->withMin(['listings as min_price' => fn ($query) => $query->where('status', 'published')->where('price_type', '!=', 'on_request')->whereNotNull('price_from')], 'price_from')
            ->addSelect(['has_featured_listing' => Listing::selectRaw('count(*) > 0')
                ->whereColumn('listings.provider_profile_id', 'provider_profiles.id')
                ->where('status', 'published')
                ->where('is_featured', true),
            ])
            ->orderByDesc('reviews_avg_rating')
            ->take(3);

        $recommended = $recommendedQuery()
            ->whereHas('listings', fn ($query) => $query->where('status', 'published')->where('is_featured', true))
            ->get();

        if ($recommended->isEmpty()) {
            $recommended = $recommendedQuery()->get();
        }

        $recommended = $recommended->map(fn (ProviderProfile $provider) => $this->present($provider));

        // Facet counts: apply every active filter except the facet's own dimension,
        // so picking a category doesn't zero out the counts of the other categories.
        $providerIdsExcludingCategory = $this->filteredQuery($filters, excluding: ['category'])->pluck('id');

        $providerIdsExcludingRating = $this->filteredQuery($filters, excluding: ['rating'])->pluck('id');
        $ratingFacet = [
            '4' => $this->ratingAtLeast(ProviderProfile::whereIn('id', $providerIdsExcludingRating), 4)->count(),
            '4.5' => $this->ratingAtLeast(ProviderProfile::whereIn('id', $providerIdsExcludingRating), 4.5)->count(),
        ];

        $providerIdsExcludingFeatured = $this->filteredQuery($filters, excluding: ['featured'])->pluck('id');
        $featuredFacet = ProviderProfile::whereIn('id', $providerIdsExcludingFeatured)
            ->whereHas('listings', fn ($query) => $query->where('status', 'published')->where('is_featured', true))
            ->count();

        $providerIdsExcludingPrice = $this->filteredQuery($filters, excluding: ['price'])->pluck('id');
        $priceBoundsRow = Listing::query()
            ->where('status', 'published')
            ->whereIn('provider_profile_id', $providerIdsExcludingPrice)
            ->selectRaw('min(price_from) as min, max(price_from) as max')
            ->first();
        $priceFacet = [
            'min' => $priceBoundsRow?->min !== null ? (float) $priceBoundsRow->min : null,
            'max' => $priceBoundsRow?->max !== null ? (float) $priceBoundsRow->max : null,
        ];

        $categories = Listing::query()
            ->selectRaw('category_id, count(distinct provider_profile_id) as total')
            ->where('status', 'published')
            ->whereIn('provider_profile_id', $providerIdsExcludingCategory)
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category:id,name,slug')
            ->get()
            ->filter(fn ($row) => $row->category !== null)
            ->map(fn ($row) => [
                'id' => $row->category->id,
                'name' => $row->category->name,
                'slug' => $row->category->slug,
                'count' => $row->total,
            ])
            ->values();

        $counties = $this->filteredQuery($filters, excluding: ['county'])
            ->selectRaw('county_id, count(*) as total')
            ->whereNotNull('county_id')
            ->groupBy('county_id')
            ->orderByDesc('total')
            ->with('county:id,name')
            ->take(8)
            ->get()
            ->filter(fn ($row) => $row->county !== null)
            ->map(fn ($row) => [
                'id' => $row->county->id,
                'name' => $row->county->name,
                'count' => $row->total,
            ])
            ->values();

        $totalProvidersCount = ProviderProfile::query()->where('status', 'active')->count();
        $totalCategoriesCount = Listing::query()->where('status', 'published')->distinct()->count('category_id');

        $reviewStats = ProviderProfile::query()
            ->where('status', 'active')
            ->withCount(['reviews as reviews_count' => fn ($query) => $query->where('status', 'approved')])
            ->withAvg(['reviews as reviews_avg_rating' => fn ($query) => $query->where('status', 'approved')], 'rating')
            ->get();

        $reviewsCount = $reviewStats->sum('reviews_count');
        $avgRating = $reviewStats
            ->filter(fn ($provider) => $provider->reviews_avg_rating !== null)
            ->avg('reviews_avg_rating');

        return Inertia::render('Providers/Index', [
            'stats' => [
                'providersCount' => $totalProvidersCount,
                'categoriesCount' => $totalCategoriesCount,
                'avgRating' => $avgRating ? round((float) $avgRating, 1) : null,
                'reviewsCount' => $reviewsCount,
            ],
            'providers' => $providers,
            'recommended' => $recommended,
            'categories' => $categories,
            'counties' => $counties,
            'facets' => [
                'rating' => $ratingFacet,
                'featured' => $featuredFacet,
                'price' => $priceFacet,
            ],
            'filters' => [
                'q' => $filters['q'] ?? '',
                'category_ids' => $filters['category_ids'] ?? [],
                'county_ids' => $filters['county_ids'] ?? [],
                'price_min' => $filters['price_min'] ?? null,
                'price_max' => $filters['price_max'] ?? null,
                'rating' => $filters['rating'] ?? null,
                'featured' => $filters['featured'] ?? false,
                'sort' => $filters['sort'] ?? 'recommended',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $excluding  Filter dimensions to skip (e.g. 'category', 'county'), used to compute facet counts.
     */
    private function filteredQuery(array $filters, array $excluding = []): Builder
    {
        return ProviderProfile::query()
            ->where('status', 'active')
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(function (Builder $query) use ($q) {
                $query->where('company_name', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%");
            }))
            ->when(! in_array('category', $excluding) && ! empty($filters['category_ids']), fn ($query) => $query->whereHas(
                'listings',
                fn ($query) => $query->where('status', 'published')->whereIn('category_id', $filters['category_ids'])
            ))
            ->when(! in_array('county', $excluding) && ! empty($filters['county_ids']), fn ($query) => $query->whereIn('county_id', $filters['county_ids']))
            ->when(isset($filters['price_min']), fn ($query) => $query->whereHas(
                'listings',
                fn ($query) => $query->where('status', 'published')->where('price_from', '>=', $filters['price_min'])
            ))
            ->when(isset($filters['price_max']), fn ($query) => $query->whereHas(
                'listings',
                fn ($query) => $query->where('status', 'published')->where('price_from', '<=', $filters['price_max'])
            ))
            ->when(isset($filters['rating']), fn ($query) => $this->ratingAtLeast($query, (float) $filters['rating']))
            ->when($filters['featured'] ?? null, fn ($query) => $query->whereHas(
                'listings',
                fn ($query) => $query->where('status', 'published')->where('is_featured', true)
            ));
    }

    private function ratingAtLeast(Builder $query, float $threshold): Builder
    {
        return $query->whereRaw(
            '(select avg(rating) from reviews where reviews.provider_profile_id = provider_profiles.id and reviews.status = ?) >= ?',
            ['approved', $threshold]
        );
    }

    private function present(ProviderProfile $provider): array
    {
        $categories = $provider->listings
            ->pluck('category')
            ->filter()
            ->unique('id')
            ->values()
            ->take(3)
            ->map(fn ($category) => ['name' => $category->name, 'slug' => $category->slug]);

        return [
            'id' => $provider->id,
            'slug' => $provider->slug,
            'company_name' => $provider->company_name,
            'description' => $provider->description,
            'logo_url' => $provider->logoUrl(),
            'cover_url' => $provider->coverUrl(),
            'county' => $provider->county?->name,
            'locality' => $provider->locality?->name,
            'categories' => $categories,
            'category' => $categories->first()['name'] ?? null,
            'category_slug' => $categories->first()['slug'] ?? null,
            'listings_count' => $provider->listings->count(),
            'price_from' => $provider->min_price,
            'price_type' => $provider->min_price !== null ? 'starting_from' : 'on_request',
            'is_featured' => (bool) $provider->has_featured_listing,
            'rating' => $provider->reviews_avg_rating ? round((float) $provider->reviews_avg_rating, 1) : null,
            'reviews_count' => $provider->reviews_count ?? 0,
            'phone' => $provider->phone,
            'whatsapp' => $provider->whatsapp,
            'email' => $provider->email,
        ];
    }
}
