<?php

namespace App\Support\Listings;

class ListingFilters
{
    public function __construct(
        public readonly ?string $q = null,
        public readonly array $countyIds = [],
        public readonly ?float $priceMin = null,
        public readonly ?float $priceMax = null,
        public readonly ?float $rating = null,
        public readonly bool $featured = false,
        public readonly ?int $categoryId = null,
        public readonly array $categorySlugs = [],
        public readonly array $eventTypes = [],
        public readonly ?string $availableOn = null,
    ) {}

    public static function fromValidated(array $filters): self
    {
        return new self(
            q: $filters['q'] ?? null,
            countyIds: $filters['county_ids'] ?? [],
            priceMin: isset($filters['price_min']) ? (float) $filters['price_min'] : null,
            priceMax: isset($filters['price_max']) ? (float) $filters['price_max'] : null,
            rating: isset($filters['rating']) ? (float) $filters['rating'] : null,
            featured: (bool) ($filters['featured'] ?? false),
            categoryId: isset($filters['category_id']) ? (int) $filters['category_id'] : null,
            categorySlugs: self::many($filters, 'categories', 'category'),
            eventTypes: self::many($filters, 'event_types', 'event_type'),
            availableOn: $filters['available_on'] ?? null,
        );
    }

    /**
     * Read a multi-value filter, falling back to its legacy single-value key
     * (e.g. ?category=restaurant) so old links keep working.
     *
     * @return list<string>
     */
    private static function many(array $filters, string $key, string $legacyKey): array
    {
        $values = $filters[$key] ?? [];

        if (isset($filters[$legacyKey])) {
            $values[] = $filters[$legacyKey];
        }

        return array_values(array_unique(array_filter($values)));
    }
}
