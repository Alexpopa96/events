<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->providerProfile;

        if (! $profile) {
            return Inertia::render('Provider/Dashboard', [
                'profile' => null,
            ]);
        }

        $profile->loadMissing('currentSubscription.plan');

        $lastInvoice = $profile->invoices()->latest('issued_at')->first();

        return Inertia::render('Provider/Dashboard', [
            'profile' => [
                'company_name' => $profile->company_name,
                'status' => $profile->status,
                'completion_score' => $profile->calculateProfileCompletionScore(),
                'missing_fields' => $profile->missingProfileFields(),
            ],
            'subscription' => $profile->currentSubscription ? [
                'plan_name' => $profile->currentSubscription->plan->name,
                'status' => $profile->currentSubscription->status,
                'ends_at' => optional($profile->currentSubscription->ends_at)->format('d.m.Y'),
            ] : null,
            'lastInvoice' => $lastInvoice ? [
                'number' => $lastInvoice->number,
                'amount' => number_format((float) $lastInvoice->amount, 0, ',', '.'),
                'currency' => $lastInvoice->currency,
                'status' => $lastInvoice->status,
                'issued_at' => optional($lastInvoice->issued_at)->format('d.m.Y'),
            ] : null,
            'stats' => [
                'views' => $profile->statsCount('view'),
                'phone_clicks' => $profile->statsCount('phone_click'),
                'whatsapp_clicks' => $profile->statsCount('whatsapp_click'),
                'quote_interest' => $profile->statsCount('quote_request_view'),
            ],
            'trend' => $this->trend($profile),
            'listings' => $this->listingPerformance($profile),
            'reviews' => $this->reviewsSummary($profile),
            'leads' => $this->recentLeads($profile),
        ]);
    }

    /**
     * Dense 30-day series (zero-filled gaps) so the dashboard trend chart
     * doesn't have to reason about missing days.
     */
    private function trend(ProviderProfile $profile): array
    {
        $since = now()->subDays(29)->startOfDay();

        $events = $profile->events()
            ->whereIn('type', ['view', 'phone_click', 'whatsapp_click'])
            ->where('created_at', '>=', $since)
            ->get(['type', 'created_at'])
            ->groupBy(fn ($event) => $event->created_at->toDateString());

        return collect(range(29, 0))
            ->map(function (int $daysAgo) use ($events) {
                $day = now()->subDays($daysAgo);
                $dayEvents = $events->get($day->toDateString(), collect());

                return [
                    'date' => $day->translatedFormat('d M'),
                    'views' => $dayEvents->where('type', 'view')->count(),
                    'phone_clicks' => $dayEvents->where('type', 'phone_click')->count(),
                    'whatsapp_clicks' => $dayEvents->where('type', 'whatsapp_click')->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Views/clicks come from the events log rather than the listing's
     * denormalized `views_count` column, so this table always agrees with
     * the stat cards and trend chart above it (all three read the same
     * source of truth).
     */
    private function listingPerformance(ProviderProfile $profile): array
    {
        return $profile->listings()
            ->with('category')
            ->withCount([
                'events as views_count' => fn ($query) => $query->where('type', 'view'),
                'events as phone_clicks_count' => fn ($query) => $query->where('type', 'phone_click'),
                'events as whatsapp_clicks_count' => fn ($query) => $query->where('type', 'whatsapp_click'),
            ])
            ->orderByDesc('views_count')
            ->get()
            ->map(function ($listing) {
                $clicks = $listing->phone_clicks_count + $listing->whatsapp_clicks_count;

                return [
                    'id' => $listing->id,
                    'title' => $listing->title,
                    'category' => $listing->category->name,
                    'status' => $listing->status,
                    'views_count' => $listing->views_count,
                    'phone_clicks' => $listing->phone_clicks_count,
                    'whatsapp_clicks' => $listing->whatsapp_clicks_count,
                    'conversion_rate' => $listing->views_count > 0
                        ? round(($clicks / $listing->views_count) * 100, 1)
                        : 0.0,
                    'published_at' => optional($listing->published_at)->format('d.m.Y'),
                ];
            })
            ->all();
    }

    private function reviewsSummary(ProviderProfile $profile): array
    {
        $approved = fn () => $profile->reviews()->where('status', 'approved');

        $count = $approved()->count();

        return [
            'average_rating' => $count > 0 ? round((float) $approved()->avg('rating'), 1) : null,
            'count' => $count,
            'recent' => $approved()
                ->with(['listing:id,title', 'user:id,name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($review) => [
                    'id' => $review->id,
                    'author' => $review->user->name,
                    'listing_title' => $review->listing->title,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->diffForHumans(),
                ]),
        ];
    }

    private function recentLeads(ProviderProfile $profile): Collection
    {
        $categoryIds = $profile->listings()->distinct()->pluck('category_id');

        $contactedIds = $profile->events()
            ->where('type', 'quote_request_view')
            ->pluck('quote_request_id');

        return QuoteRequest::whereIn('category_id', $categoryIds)
            ->where('status', 'open')
            ->with('category')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (QuoteRequest $lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'category' => $lead->category->name,
                'city' => $lead->city,
                'message' => $lead->message,
                'created_at' => $lead->created_at->diffForHumans(),
                'contacted' => $contactedIds->contains($lead->id),
            ]);
    }
}
