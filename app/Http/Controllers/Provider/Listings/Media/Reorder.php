<?php

namespace App\Http\Controllers\Provider\Listings\Media;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class Reorder extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->provider_profile_id === $request->user()->providerProfile?->id, 403);

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', Rule::exists('listing_media', 'id')->where('listing_id', $listing->id)],
        ]);

        foreach ($data['order'] as $position => $mediaId) {
            $listing->media()->where('id', $mediaId)->update(['position' => $position]);
        }

        return back();
    }
}
