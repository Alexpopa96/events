<?php

namespace App\Support\Listings;

use App\Models\County;
use Illuminate\Database\Eloquent\Builder;

class ListingFacets
{
    /**
     * Compute facet data for a minimal, unscoped-by-select Listing query builder.
     * $baseQuery must not already carry eager loads / withAvg / select columns.
     */
    public function compute(Builder $baseQuery, ListingFilters $filters, array $facets): array
    {
        $result = [];

        if (in_array('counties', $facets)) {
            $result['counties'] = $this->counties($baseQuery, $filters);
        }

        if (in_array('rating', $facets)) {
            $result['rating'] = $this->ratingBuckets($baseQuery, $filters);
        }

        if (in_array('featured', $facets)) {
            $result['featured'] = $this->featuredCount($baseQuery, $filters);
        }

        if (in_array('price', $facets)) {
            $result['price'] = $this->priceBounds($baseQuery, $filters);
        }

        if (in_array('categories', $facets)) {
            $result['categories'] = $this->categoryCounts($baseQuery, $filters);
        }

        return $result;
    }

    private function counties(Builder $baseQuery, ListingFilters $filters): array
    {
        $query = ListingQueryScope::apply(clone $baseQuery, $filters, except: ['county']);

        $counts = (clone $query)
            ->whereNotNull('county_id')
            ->selectRaw('county_id, count(*) as total')
            ->groupBy('county_id')
            ->pluck('total', 'county_id');

        $topIds = $counts->sortDesc()->keys()->take(8);
        $visibleIds = $topIds->merge($filters->countyIds)->unique();

        return County::whereIn('id', $visibleIds)
            ->get(['id', 'name'])
            ->map(fn (County $county) => [
                'id' => $county->id,
                'name' => $county->name,
                'count' => (int) ($counts[$county->id] ?? 0),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    private function ratingBuckets(Builder $baseQuery, ListingFilters $filters): array
    {
        $query = ListingQueryScope::apply(clone $baseQuery, $filters, except: ['rating']);

        return [
            '4' => ListingQueryScope::ratingAtLeast(clone $query, 4)->count(),
            '4.5' => ListingQueryScope::ratingAtLeast(clone $query, 4.5)->count(),
        ];
    }

    private function featuredCount(Builder $baseQuery, ListingFilters $filters): int
    {
        $query = ListingQueryScope::apply(clone $baseQuery, $filters, except: ['featured']);

        return (clone $query)->where('is_featured', true)->count();
    }

    private function priceBounds(Builder $baseQuery, ListingFilters $filters): array
    {
        $query = ListingQueryScope::apply(clone $baseQuery, $filters, except: ['price']);

        $bounds = (clone $query)->selectRaw('min(price_from) as min, max(price_from) as max')->first();

        return [
            'min' => $bounds?->min !== null ? (float) $bounds->min : null,
            'max' => $bounds?->max !== null ? (float) $bounds->max : null,
        ];
    }

    private function categoryCounts(Builder $baseQuery, ListingFilters $filters): array
    {
        $query = ListingQueryScope::apply(clone $baseQuery, $filters, except: ['category']);

        return (clone $query)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->all();
    }
}
