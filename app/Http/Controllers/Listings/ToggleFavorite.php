<?php

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ToggleFavorite extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        $favorites = $request->user()->favorites();

        $existing = $favorites->where('listing_id', $listing->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $favorites->create(['listing_id' => $listing->id]);
        }

        return back();
    }
}
