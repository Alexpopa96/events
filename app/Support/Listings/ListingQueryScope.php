<?php

namespace App\Support\Listings;

use App\Support\Search\FuzzySearch;
use Illuminate\Database\Eloquent\Builder;

class ListingQueryScope
{
    /**
     * Apply the given filters to a Listing query, skipping any facet key listed in $except.
     *
     * Recognized facet keys: county, price, rating, featured, category, event_type.
     * The free-text search (q) and the availability date are not facets and are always applied.
     */
    public static function apply(Builder $query, ListingFilters $filters, array $except = []): Builder
    {
        return $query
            ->when($filters->q, fn (Builder $q, string $term) => FuzzySearch::apply(
                $q, $term, ['title', 'description', 'category.name', 'providerProfile.company_name', 'locality.name']
            ))
            ->when($filters->availableOn, fn (Builder $q, string $date) => $q->whereDoesntHave(
                'providerProfile.availabilityBlocks', fn (Builder $q) => $q->whereDate('date', $date)
            ))
            ->when(! in_array('category', $except) ? $filters->categorySlugs : [], fn (Builder $q, array $slugs) => $q->whereHas(
                'category', fn (Builder $q) => $q->whereIn('slug', $slugs)
            ))
            ->when(! in_array('event_type', $except) ? $filters->eventTypes : [], fn (Builder $q, array $types) => self::servesEventTypes($q, $types))
            ->when(! in_array('county', $except) ? $filters->countyIds : [], fn (Builder $q, array $ids) => $q->whereIn('county_id', $ids))
            ->when(! in_array('price', $except) && $filters->priceMin !== null, fn (Builder $q) => $q->where('price_from', '>=', $filters->priceMin))
            ->when(! in_array('price', $except) && $filters->priceMax !== null, fn (Builder $q) => $q->where('price_from', '<=', $filters->priceMax))
            ->when(! in_array('rating', $except) && $filters->rating !== null, fn (Builder $q) => self::ratingAtLeast($q, $filters->rating))
            ->when(! in_array('featured', $except) && $filters->featured, fn (Builder $q) => $q->where('is_featured', true));
    }

    /**
     * A listing without tagged event types serves every event type; otherwise it must
     * serve at least one of the given types.
     */
    public static function servesEventTypes(Builder $query, array $types): Builder
    {
        return $query->where(function (Builder $q) use ($types) {
            $q->whereNull('event_types')->orWhereJsonLength('event_types', 0);

            foreach ($types as $type) {
                $q->orWhereJsonContains('event_types', $type);
            }
        });
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
