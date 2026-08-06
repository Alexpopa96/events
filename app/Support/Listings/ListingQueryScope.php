<?php

namespace App\Support\Listings;

use Illuminate\Database\Eloquent\Builder;

class ListingQueryScope
{
    /**
     * Apply the given filters to a Listing query, skipping any facet key listed in $except.
     *
     * Recognized facet keys: county, price, rating, featured, category.
     * The free-text search (q) is not a facet and is always applied.
     */
    public static function apply(Builder $query, ListingFilters $filters, array $except = []): Builder
    {
        return $query
            ->when($filters->q, fn (Builder $q, string $term) => $q->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%");
            }))
            ->when(! in_array('category', $except) ? $filters->categorySlug : null, fn (Builder $q, string $slug) => $q->whereHas(
                'category', fn (Builder $q) => $q->where('slug', $slug)
            ))
            ->when(! in_array('county', $except) ? $filters->countyIds : [], fn (Builder $q, array $ids) => $q->whereIn('county_id', $ids))
            ->when(! in_array('price', $except) && $filters->priceMin !== null, fn (Builder $q) => $q->where('price_from', '>=', $filters->priceMin))
            ->when(! in_array('price', $except) && $filters->priceMax !== null, fn (Builder $q) => $q->where('price_from', '<=', $filters->priceMax))
            ->when(! in_array('rating', $except) && $filters->rating !== null, fn (Builder $q) => self::ratingAtLeast($q, $filters->rating))
            ->when(! in_array('featured', $except) && $filters->featured, fn (Builder $q) => $q->where('is_featured', true));
    }

    /**
     * Filters listings by their average approved-review rating using a correlated WHERE
     * subquery rather than a HAVING clause on a withAvg() alias, so it stays correct even
     * when the caller later adds its own groupBy() (e.g. facet count queries).
     */
    public static function ratingAtLeast(Builder $query, float $threshold): Builder
    {
        return $query->whereRaw(
            '(select avg(reviews.rating) from reviews where reviews.listing_id = listings.id and reviews.status = ?) >= ?',
            ['approved', $threshold]
        );
    }
}
