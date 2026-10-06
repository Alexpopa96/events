<?php

namespace App\Http\Controllers\Provider\Offers;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Destroy extends Controller
{
    public function __invoke(Request $request, Offer $offer): RedirectResponse
    {
        abort_unless($offer->provider_profile_id === $request->user()->providerProfile->id, 404);
        abort_unless(in_array($offer->status, Offer::OPEN_STATUSES, true), 403);

        $offer->update(['status' => Offer::WITHDRAWN, 'responded_at' => now()]);

        return back()->with('success', ['message' => 'Oferta a fost retrasă.']);
    }
}
