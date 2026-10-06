<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Models\SavedSearch;
use App\Notifications\SavedSearchMatches;
use App\Support\Listings\ListingFilters;
use App\Support\Listings\ListingQueryScope;
use Illuminate\Console\Command;

/**
 * Daily digest: for each saved search, finds listings published since the last
 * check and emails a summary. Silent (no email, no timestamp bump) when nothing matches,
 * so a quiet search doesn't nag the client with empty updates.
 */
class NotifySavedSearches extends Command
{
    protected $signature = 'saved-searches:notify';

    protected $description = 'Email clients about new listings matching their saved searches';

    public function handle(): int
    {
        $searches = SavedSearch::with('user')->get();
        $notified = 0;

        foreach ($searches as $search) {
            $since = $search->last_notified_at ?? $search->created_at;

            $filters = ListingFilters::fromValidated($search->filters);

            $matches = ListingQueryScope::apply(
                Listing::query()->where('status', 'published')->where('published_at', '>', $since),
                $filters
            )->with('providerProfile:id,company_name')->get();

            if ($matches->isEmpty()) {
                continue;
            }

            $search->user->notify(new SavedSearchMatches($search, $matches));
            $search->update(['last_notified_at' => now()]);
            $notified++;
        }

        $this->info("Notified {$notified} saved search(es) out of {$searches->count()}.");

        return self::SUCCESS;
    }
}
