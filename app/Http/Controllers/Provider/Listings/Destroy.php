<?php

namespace App\Http\Controllers\Provider\Listings;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Destroy extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->provider_profile_id === $request->user()->providerProfile?->id, 403);

        $listing->delete();

        return redirect()
            ->route('provider.listings.index')
            ->with('success', ['message' => 'Anunțul a fost șters.']);
    }
}
