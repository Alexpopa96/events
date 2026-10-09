<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ProviderProfile;
use App\Support\EventTypes;
use App\Support\Seo\Landing;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class Sitemap extends Controller
{
    public const CACHE_KEY = 'seo.sitemap';

    public function __invoke(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => $this->render($this->urls()));

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function render(array $urls): string
    {
        $entries = array_map(fn (array $url) => '<url><loc>'.e($url['loc']).'</loc>'
            .($url['lastmod'] ? "<lastmod>{$url['lastmod']}</lastmod>" : '')
            ."<priority>{$url['priority']}</priority></url>", $urls);

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .implode("\n", $entries)."\n"
            .'</urlset>'."\n";
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, priority: string}>
     */
    private function urls(): array
    {
        $urls = collect([
            [route('home'), null, '1.0'],
            [route('categories.index'), null, '0.8'],
            [route('listings.index'), null, '0.7'],
            [route('providers.index'), null, '0.7'],
            [route('quote-requests.browse'), null, '0.5'],
            [route('subscriptions.index'), null, '0.4'],
        ]);

        $categories = Category::where('is_active', true)->get(['id', 'name', 'slug', 'updated_at'])->keyBy('id');

        foreach ($categories as $category) {
            $urls->push([(new Landing($category))->url(), $category->updated_at, '0.9']);
        }

        $published = Listing::query()
            ->where('status', 'published')
            ->whereIn('category_id', $categories->keys())
            ->with(['county:id,name,slug', 'locality:id,name,slug,county_id'])
            ->get(['id', 'slug', 'category_id', 'county_id', 'locality_id', 'event_types', 'updated_at']);

        // Every indexable landing each listing appears on: its category in its county and
        // locality, plus the event type variants it was explicitly tagged for (untagged
        // listings show up on event pages too, but don't make those pages indexable).
        $landings = collect();

        foreach ($published as $listing) {
            $category = $categories[$listing->category_id];
            $locality = $listing->locality?->county_id === $listing->county_id ? $listing->locality : null;
            $eventTypes = array_intersect($listing->event_types ?? [], EventTypes::landingValues());

            foreach ([null, ...$eventTypes] as $eventType) {
                $candidates = $eventType ? [new Landing($category, eventType: $eventType)] : [];

                if ($listing->county) {
                    $candidates[] = new Landing($category, $listing->county, eventType: $eventType);
                }

                if ($listing->county && $locality) {
                    $candidates[] = new Landing($category, $listing->county, $locality, $eventType);
                }

                foreach ($candidates as $landing) {
                    $url = $landing->url();
                    $landings[$url] = max($landings[$url] ?? $listing->updated_at, $listing->updated_at);
                }
            }
        }

        $landings->each(fn ($lastmod, string $url) => $urls->push([$url, $lastmod, '0.8']));

        Listing::where('status', 'published')
            ->get(['slug', 'updated_at'])
            ->each(fn (Listing $listing) => $urls->push([route('listings.show', $listing->slug), $listing->updated_at, '0.7']));

        ProviderProfile::where('status', 'active')
            ->get(['slug', 'updated_at'])
            ->each(fn (ProviderProfile $provider) => $urls->push([route('providers.show', $provider->slug), $provider->updated_at, '0.6']));

        return $urls
            ->map(fn (array $url) => [
                'loc' => $url[0],
                'lastmod' => $url[1]?->toAtomString(),
                'priority' => $url[2],
            ])
            ->unique('loc')
            ->values()
            ->all();
    }
}
