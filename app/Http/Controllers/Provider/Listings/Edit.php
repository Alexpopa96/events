<?php

namespace App\Http\Controllers\Provider\Listings;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Edit extends Controller
{
    public function __invoke(Request $request, Listing $listing): Response
    {
        $profile = $request->user()->providerProfile;

        abort_unless($listing->provider_profile_id === $profile?->id, 403);

        $listing->load([
            'county:id,name',
            'locality:id,name',
            'media' => fn ($query) => $query->orderByDesc('is_cover')->orderBy('position'),
        ]);

        $plan = $profile->activePlan();

        $viewsCount = $listing->events()->where('type', 'view')->count();
        $phoneClicks = $listing->events()->where('type', 'phone_click')->count();
        $whatsappClicks = $listing->events()->where('type', 'whatsapp_click')->count();
        $clicks = $phoneClicks + $whatsappClicks;

        return Inertia::render('Provider/Listings/Edit', [
            'listing' => [
                ...$listing->only([
                    'id', 'title', 'category_id', 'description', 'price_type',
                    'price_from', 'price_to', 'county_id', 'locality_id',
                    'status', 'rejection_reason', 'views_count',
                ]),
                'county' => $listing->county?->only(['id', 'name']),
                'locality' => $listing->locality?->only(['id', 'name']),
                'created_at' => $listing->created_at->format('d.m.Y'),
                'published_at' => optional($listing->published_at)->format('d.m.Y'),
                'media' => $listing->media->map(fn ($media) => [
                    'id' => $media->id,
                    'type' => $media->type,
                    'url' => "/storage/{$media->path}",
                    'is_cover' => $media->is_cover,
                    'position' => $media->position,
                ]),
            ],
            'categories' => Category::where('is_active', true)
                ->orderBy('position')
                ->get(['id', 'name', 'slug', 'parent_id']),
            'counties' => County::orderBy('name')->get(['id', 'name']),
            'mediaLimits' => [
                'max_photos' => $plan?->max_photos_per_listing,
                'max_videos' => $plan?->max_videos_per_listing,
                'photos_count' => $listing->media->where('type', 'photo')->count(),
                'videos_count' => $listing->media->where('type', 'video')->count(),
            ],
            'stats' => [
                'views' => $viewsCount,
                'phone_clicks' => $phoneClicks,
                'whatsapp_clicks' => $whatsappClicks,
                'conversion_rate' => $viewsCount > 0 ? round(($clicks / $viewsCount) * 100, 1) : 0.0,
            ],
            'trend' => $this->trend($listing),
        ]);
    }

    /**
     * Dense 30-day series (zero-filled gaps), same shape as the provider
     * dashboard trend so TrendChart can be reused as-is.
     */
    private function trend(Listing $listing): array
    {
        $since = now()->subDays(29)->startOfDay();

        $events = $listing->events()
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
}
