<?php

namespace App\Console\Commands;

use App\Models\ProviderSubscription;
use App\Notifications\SubscriptionExpiringSoon;
use Illuminate\Console\Command;

/**
 * Daily heads-up to providers whose subscription renews within the next 3 days,
 * sent once per subscription (reminder_sent_at guards against repeats).
 */
class NotifyExpiringSubscriptions extends Command
{
    protected $signature = 'subscriptions:notify-expiring';

    protected $description = 'Email providers whose subscription is about to renew and has not been reminded yet';

    public function handle(): int
    {
        $subscriptions = ProviderSubscription::query()
            ->whereIn('status', ['active', 'past_due'])
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays(3)])
            ->whereNull('reminder_sent_at')
            ->with(['plan', 'providerProfile.user'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->providerProfile?->user;

            if (! $user) {
                continue;
            }

            $user->notify(new SubscriptionExpiringSoon($subscription));
            $subscription->update(['reminder_sent_at' => now()]);
        }

        $this->info("Sent {$subscriptions->count()} renewal reminder(s).");

        return self::SUCCESS;
    }
}
