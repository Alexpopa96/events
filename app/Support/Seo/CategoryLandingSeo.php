<?php

namespace App\Support\Seo;

use App\Models\Listing;
use App\Support\EventTypes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Copy, internal links and meta for category pages and their place / event type
 * landing variants (see Landing).
 */
class CategoryLandingSeo
{
    /**
     * Romanian numerals from 20 up take "de" before the noun ("20 de furnizori").
     */
    public static function count(int $n, string $one, string $many): string
    {
        if ($n === 1) {
            return "1 {$one}";
        }

        $rest = $n % 100;

        return ($n >= 20 && ($rest === 0 || $rest >= 20)) ? "{$n} de {$many}" : "{$n} {$many}";
    }

    /**
     * A listing with no event types tagged serves every event type
     * (same rule as ListingQueryScope::servesEventTypes).
     */
    public static function serves(Listing $listing, ?string $eventType): bool
    {
        return ! $eventType || empty($listing->event_types) || in_array($eventType, $listing->event_types, true);
    }

    public static function tagged(Listing $listing, string $eventType): bool
    {
        return in_array($eventType, $listing->event_types ?? [], true);
    }

    /**
     * An event type page is only worth indexing when some listing was explicitly tagged
     * for that event — otherwise it is a near-duplicate of the plain category page.
     */
    public static function indexable(Landing $landing, array $stats): bool
    {
        return $stats['listingsCount'] > 0 && (! $landing->eventType || $stats['taggedCount'] > 0);
    }

    public function intro(Landing $landing, array $stats): ?string
    {
        if (! $landing->hasPlace() && ! $landing->eventType) {
            return null;
        }

        $scope = "din categoria {$landing->category->name}"
            .($landing->eventType ? " pentru {$landing->eventTerm()}" : '')
            .($landing->hasPlace() ? " în {$landing->placeLabel()}" : '');
        $n = $stats['listingsCount'];

        if ($n === 0) {
            return "Încă nu avem furnizori {$scope}. "
                .'Publică gratuit o cerere de ofertă și te vor contacta furnizorii potriviți pentru evenimentul tău.';
        }

        $sentences = ['Compară '.self::count($n, 'furnizor', 'furnizori')." {$scope}."];

        if ($stats['minPrice']) {
            $sentences[] = 'Prețurile pornesc de la '.number_format($stats['minPrice'], 0, ',', '.').' RON.';
        }

        if ($stats['avgRating'] && $stats['reviewsCount']) {
            $sentences[] = "Rating mediu {$stats['avgRating']}/5 din ".self::count($stats['reviewsCount'], 'recenzie verificată', 'recenzii verificate').'.';
        }

        $sentences[] = 'Verifică disponibilitatea pentru data evenimentului și cere oferte gratuite, direct de la furnizori.';

        return implode(' ', $sentences);
    }

    /**
     * Groups of internal links to related landing pages, so crawlers (and people)
     * can reach every indexable combination.
     *
     * @return array<int, array{title: string, items: array<int, array{name: string, url: string, count: int}>}>
     */
    public function places(Landing $landing): array
    {
        $event = $landing->eventType;
        $subject = $landing->subject();
        $categoryRows = $this->rows(fn ($q) => $q->where('category_id', $landing->category->id));
        $groups = [];

        if (! $landing->county) {
            $groups[] = [
                'title' => "{$subject} pe județe",
                'items' => $this->links($categoryRows, $event, fn (Listing $row) => $row->county?->id, fn (Listing $row) => $landing->withPlace($row->county), fn (Listing $row) => $row->county->displayName()),
            ];
        } else {
            $county = $landing->county;
            $locality = $landing->locality;

            $groups[] = [
                'title' => $locality ? "Alte localități din {$county->displayName()}" : "{$subject} în localitățile din {$county->displayName()}",
                'items' => array_slice($this->links(
                    $categoryRows->filter(fn (Listing $row) => $row->county_id === $county->id && $row->locality_id !== $locality?->id && $row->locality?->county_id === $county->id),
                    $event,
                    fn (Listing $row) => $row->locality?->id,
                    fn (Listing $row) => $landing->withPlace($county, $row->locality),
                    fn (Listing $row) => $row->locality->name,
                ), 0, 30),
            ];

            $placeRows = $this->rows(fn ($q) => $q
                ->where('category_id', '!=', $landing->category->id)
                ->where('county_id', $county->id)
                ->when($locality, fn ($q) => $q->where('locality_id', $locality->id))
                ->whereHas('category', fn ($q) => $q->where('is_active', true)));

            $groups[] = [
                'title' => 'Alte categorii'.($event ? " pentru {$landing->eventTerm()}" : '')." în {$landing->placeLabel()}",
                'items' => $this->links($placeRows, $event, fn (Listing $row) => $row->category_id, fn (Listing $row) => $landing->withCategory($row->category), fn (Listing $row) => $row->category->name),
            ];

            if (! $locality) {
                $groups[] = [
                    'title' => "{$subject} în alte județe",
                    'items' => $this->links(
                        $categoryRows->filter(fn (Listing $row) => $row->county_id !== $county->id),
                        $event,
                        fn (Listing $row) => $row->county?->id,
                        fn (Listing $row) => $landing->withPlace($row->county),
                        fn (Listing $row) => $row->county->displayName(),
                    ),
                ];
            }
        }

        $groups[] = $this->eventLinks($landing, $categoryRows);

        return array_values(array_filter($groups, fn (array $group) => count($group['items']) > 0));
    }

    public function meta(Landing $landing, array $stats, LengthAwarePaginator $listings, bool $hasFilters): Seo
    {
        $n = $stats['listingsCount'];
        $furnizori = self::count($n, 'furnizor', 'furnizori');

        $title = match (true) {
            $landing->hasPlace() => "{$landing->subject()} {$landing->placeName()}".($n > 0 ? " — {$furnizori}, prețuri și recenzii" : ''),
            $landing->eventType !== null => "{$landing->subject()} — ".($n > 0 ? "{$furnizori}, " : '').'prețuri și recenzii',
            default => "{$landing->category->name} pentru nunți și evenimente — prețuri și recenzii",
        };

        $description = $this->intro($landing, $stats)
            ?? ($landing->category->description
                ?: "Compară {$furnizori} din categoria {$landing->category->name}: portofolii, prețuri și recenzii reale. Cere oferte gratuite.");

        $canonical = $landing->url();

        if ($listings->currentPage() > 1) {
            $canonical .= '?page='.$listings->currentPage();
            $title .= " — pagina {$listings->currentPage()}";
        }

        $items = collect($listings->items());

        return Seo::make($title, $description, $canonical)
            ->noindex($hasFilters || ! self::indexable($landing, $stats))
            ->image($items->firstWhere('cover_url')['cover_url'] ?? null)
            ->breadcrumbs($landing->breadcrumbs())
            ->jsonLd($items->isEmpty() ? null : [
                '@type' => 'ItemList',
                'name' => trim("{$landing->subject()} {$landing->placeName()}"),
                'numberOfItems' => $listings->total(),
                'itemListElement' => $items->values()->map(fn (array $item, int $i) => [
                    '@type' => 'ListItem',
                    'position' => ($listings->currentPage() - 1) * $listings->perPage() + $i + 1,
                    'url' => route('listings.show', $item['slug']),
                    'name' => $item['title'],
                ])->all(),
            ]);
    }

    /**
     * Links to this category (in this place) for each event type that has tagged listings,
     * plus a way back to "all events" when already on an event page.
     */
    private function eventLinks(Landing $landing, Collection $categoryRows): array
    {
        $rows = $categoryRows
            ->when($landing->county, fn ($rows) => $rows->where('county_id', $landing->county->id))
            ->when($landing->locality, fn ($rows) => $rows->where('locality_id', $landing->locality->id));

        $items = collect(EventTypes::landingValues())
            ->reject(fn (string $type) => $type === $landing->eventType)
            ->filter(fn (string $type) => $rows->contains(fn (Listing $row) => self::tagged($row, $type)))
            ->map(fn (string $type) => [
                'name' => EventTypes::LABELS[$type],
                'url' => $landing->withEventType($type)->url(),
                'count' => $rows->filter(fn (Listing $row) => self::serves($row, $type))->count(),
            ]);

        if ($landing->eventType && $rows->isNotEmpty()) {
            $items->prepend(['name' => 'Toate evenimentele', 'url' => $landing->withEventType(null)->url(), 'count' => $rows->count()]);
        }

        $where = $landing->hasPlace() ? " în {$landing->placeName()}" : '';

        return [
            'title' => $landing->eventType
                ? "{$landing->category->name}{$where} pentru alte evenimente"
                : "{$landing->category->name}{$where} pe tip de eveniment",
            'items' => $items->values()->all(),
        ];
    }

    /**
     * Group listing rows into links; on an event page only places/categories with a listing
     * tagged for that event are linked (the others are noindex).
     */
    private function links(Collection $rows, ?string $event, callable $key, callable $landing, callable $name): array
    {
        return $rows
            ->filter(fn (Listing $row) => $key($row) !== null && self::serves($row, $event))
            ->groupBy($key)
            ->filter(fn (Collection $group) => ! $event || $group->contains(fn (Listing $row) => self::tagged($row, $event)))
            ->map(fn (Collection $group) => [
                'name' => $name($group->first()),
                'url' => $landing($group->first())->url(),
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    private function rows(callable $scope): Collection
    {
        return Listing::query()
            ->where('status', 'published')
            ->tap($scope)
            ->with(['category:id,name,slug', 'county:id,name,slug', 'locality:id,name,slug,county_id'])
            ->get(['id', 'category_id', 'county_id', 'locality_id', 'event_types']);
    }
}
