<?php

namespace Database\Seeders;

use App\Models\Listing;
use Illuminate\Database\Seeder;

/**
 * Tags demo listings with the event types their category typically serves, so the
 * "Tip eveniment" filter has something to narrow. Categories missing from the map
 * (e.g. fotograf, videograf) stay untagged and therefore serve every event type.
 * Listings that already have event types are left alone.
 */
class ListingEventTypesSeeder extends Seeder
{
    private const BY_CATEGORY = [
        'wedding-planner' => ['nunta', 'botez'],
        'machiaj' => ['nunta'],
        'coafura' => ['nunta'],
        'invitatii' => ['nunta', 'botez'],
        'lumini' => ['nunta', 'concert'],
        'cazare' => ['nunta'],
        'limuzine' => ['nunta', 'corporate'],
        'torturi' => ['nunta', 'botez', 'aniversare', 'petrecere-privata'],
        'candy-bar' => ['nunta', 'botez', 'aniversare', 'petrecere-privata'],
        'dj' => ['nunta', 'aniversare', 'corporate', 'petrecere-privata'],
        'formatie' => ['nunta', 'botez', 'aniversare', 'concert'],
        'mc' => ['nunta', 'botez', 'aniversare', 'corporate'],
        'sonorizare' => ['corporate', 'concert', 'petrecere-privata'],
        'restaurant' => ['nunta', 'botez', 'aniversare', 'corporate', 'petrecere-privata'],
        'salon-evenimente' => ['nunta', 'botez', 'aniversare', 'corporate', 'petrecere-privata'],
        'decor' => ['nunta', 'botez', 'aniversare', 'corporate'],
        'florist' => ['nunta', 'botez', 'aniversare', 'corporate'],
        'cabina-foto' => ['nunta', 'aniversare', 'corporate', 'petrecere-privata'],
        'cabina-360' => ['nunta', 'aniversare', 'corporate', 'petrecere-privata'],
    ];

    public function run(): void
    {
        Listing::with('category.parent')->get()->each(function (Listing $listing) {
            if (! empty($listing->event_types)) {
                return;
            }

            $category = $listing->category;
            $types = self::BY_CATEGORY[$category?->slug] ?? self::BY_CATEGORY[$category?->parent?->slug] ?? null;

            if ($types) {
                $listing->update(['event_types' => $types]);
            }
        });
    }
}
