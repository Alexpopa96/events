<?php

namespace App\Http\Controllers\Provider\Listings\Media;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Store extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        $profile = $request->user()->providerProfile;

        abort_unless($listing->provider_profile_id === $profile?->id, 403);

        $plan = $profile->activePlan();
        $maxPhotos = $plan?->max_photos_per_listing;
        $maxVideos = $plan?->max_videos_per_listing;

        $existingPhotos = $listing->media()->where('type', 'photo')->count();
        $existingVideos = $listing->media()->where('type', 'video')->count();

        $photoRules = ['nullable', 'array'];
        if ($maxPhotos !== null) {
            $photoRules[] = 'max:'.max(0, $maxPhotos - $existingPhotos);
        }

        $videoRules = ['nullable', 'array'];
        if ($maxVideos !== null) {
            $videoRules[] = 'max:'.max(0, $maxVideos - $existingVideos);
        }

        $data = $request->validate([
            'photos' => $photoRules,
            'photos.*' => ['image', 'max:5120'],
            'videos' => $videoRules,
            'videos.*' => ['mimes:mp4,mov,webm,ogg', 'max:51200'],
        ]);

        $position = (int) $listing->media()->max('position') + 1;
        $hasCover = $listing->media()->where('is_cover', true)->exists();

        foreach ($data['photos'] ?? [] as $photo) {
            $listing->media()->create([
                'type' => 'photo',
                'path' => $photo->store('listings/media', 'public'),
                'is_cover' => ! $hasCover,
                'position' => $position++,
            ]);
            $hasCover = true;
        }

        foreach ($data['videos'] ?? [] as $video) {
            $listing->media()->create([
                'type' => 'video',
                'path' => $video->store('listings/media', 'public'),
                'is_cover' => false,
                'position' => $position++,
            ]);
        }

        return back()->with('success', ['message' => 'Fișierele au fost adăugate.']);
    }
}
