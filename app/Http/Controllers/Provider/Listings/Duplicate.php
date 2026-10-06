<?php

namespace App\Http\Controllers\Provider\Listings;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One-click copy of an existing listing (fields + photos/videos) as a new draft,
 * so a provider can quickly spin off a variant ("Pachet nuntă" -> "Pachet botez")
 * instead of retyping everything.
 */
class Duplicate extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        $profile = $request->user()->providerProfile;

        abort_unless($listing->provider_profile_id === $profile?->id, 403);

        $plan = $profile->activePlan();
        $activeListings = $profile->listings()->where('status', '!=', 'archived')->count();

        if ($plan?->max_listings !== null && $activeListings >= $plan->max_listings) {
            return back()->with('error', [
                'message' => "Ai atins limita de {$plan->max_listings} ".Str::plural('anunț', $plan->max_listings).' activ'.($plan->max_listings > 1 ? 'e' : '')." din planul \"{$plan->name}\". Arhivează un anunț existent sau treci la un plan superior.",
            ]);
        }

        $copy = $profile->listings()->create([
            'category_id' => $listing->category_id,
            'title' => $listing->title.' (copie)',
            'slug' => Str::slug($listing->title).'-'.Str::lower(Str::random(5)),
            'description' => $listing->description,
            'price_type' => $listing->price_type,
            'price_from' => $listing->price_from,
            'price_to' => $listing->price_to,
            'benefits' => $listing->benefits,
            'event_types' => $listing->event_types,
            'county_id' => $listing->county_id,
            'locality_id' => $listing->locality_id,
            'status' => 'draft',
        ]);

        foreach ($listing->media as $media) {
            $newPath = null;

            if (Storage::disk('public')->exists($media->path)) {
                $newPath = 'listings/media/'.Str::random(32).'.'.pathinfo($media->path, PATHINFO_EXTENSION);
                Storage::disk('public')->copy($media->path, $newPath);
            }

            $copy->media()->create([
                'type' => $media->type,
                'path' => $newPath ?? $media->path,
                'thumbnail_path' => $media->thumbnail_path,
                'is_cover' => $media->is_cover,
                'position' => $media->position,
            ]);
        }

        return redirect()
            ->route('provider.listings.edit', $copy)
            ->with('success', ['message' => 'Anunțul a fost duplicat ca ciornă. Modifică ce trebuie și publică-l.']);
    }
}
