<?php

namespace App\Http\Controllers\Provider\Listings\Media;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Destroy extends Controller
{
    public function __invoke(Request $request, Listing $listing, ListingMedia $media): RedirectResponse
    {
        abort_unless($listing->provider_profile_id === $request->user()->providerProfile?->id, 403);
        abort_unless($media->listing_id === $listing->id, 404);

        $wasCover = $media->is_cover;

        Storage::disk('public')->delete(array_filter([$media->path, $media->thumbnail_path]));
        $media->delete();

        if ($wasCover) {
            $listing->media()->orderBy('position')->first()?->update(['is_cover' => true]);
        }

        return back()->with('success', ['message' => 'Fotografia a fost ștearsă.']);
    }
}
