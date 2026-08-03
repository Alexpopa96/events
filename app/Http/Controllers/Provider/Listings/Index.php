<?php

namespace App\Http\Controllers\Provider\Listings;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->providerProfile;

        $listings = $profile->listings()
            ->with([
                'category',
                'county:id,name',
                'locality:id,name',
                'media' => fn ($query) => $query->orderByDesc('is_cover')->orderBy('position'),
            ])
            ->latest()
            ->get()
            ->map(fn ($listing) => [
                'id' => $listing->id,
                'title' => $listing->title,
                'category' => $listing->category->name,
                'category_slug' => $listing->category->slug,
                'status' => $listing->status,
                'views_count' => $listing->views_count,
                'price_from' => $listing->price_from,
                'price_to' => $listing->price_to,
                'price_type' => $listing->price_type,
                'county' => $listing->county?->only(['id', 'name']),
                'locality' => $listing->locality?->only(['id', 'name']),
                'cover_url' => $listing->media->first() ? "/storage/{$listing->media->first()->path}" : null,
                'photos_count' => $listing->media->where('type', 'photo')->count(),
                'published_at' => optional($listing->published_at)->format('d.m.Y'),
            ]);

        $plan = $profile->activePlan();
        $activeListings = $listings->where('status', '!=', 'archived')->count();

        return Inertia::render('Provider/Listings/Index', [
            'listings' => $listings,
            'quota' => [
                'used' => $activeListings,
                'max' => $plan?->max_listings,
                'plan_name' => $plan?->name,
            ],
            'trend' => $this->trend($profile),
            'performance' => $this->performance($profile),
        ]);
    }

    /**
     * Dense 30-day series (zero-filled gaps), aggregated across every
     * listing this provider owns — same shape/source as the dashboard's
     * trend chart so TrendChart can be reused as-is.
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
     * Per-listing views/clicks/conversion, read from the events log (not
     * the denormalized `views_count` column) so it agrees with the trend
     * chart above it.
     */
    private function performance(ProviderProfile $profile): array
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
                ];
            })
            ->all();
    }
}
