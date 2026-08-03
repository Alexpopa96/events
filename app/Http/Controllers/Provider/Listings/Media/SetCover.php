<?php

namespace App\Http\Controllers\Provider\Listings\Media;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SetCover extends Controller
{
    public function __invoke(Request $request, Listing $listing, ListingMedia $media): RedirectResponse
    {
        abort_unless($listing->provider_profile_id === $request->user()->providerProfile?->id, 403);
        abort_unless($media->listing_id === $listing->id, 404);

        $listing->media()->where('id', '!=', $media->id)->update(['is_cover' => false]);
        $media->update(['is_cover' => true]);

        return back()->with('success', ['message' => 'Fotografia principală a fost actualizată.']);
    }
}
