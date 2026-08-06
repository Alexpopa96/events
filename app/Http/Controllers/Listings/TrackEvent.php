<?php

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackEvent extends Controller
{
    public function __invoke(Request $request, Listing $listing): Response
    {
        $data = $request->validate([
            'type' => ['required', 'in:phone_click,whatsapp_click,email_click'],
        ]);

        ListingEvent::create([
            'provider_profile_id' => $listing->provider_profile_id,
            'listing_id' => $listing->id,
            'user_id' => $request->user()?->id,
            'type' => $data['type'],
            'ip_hash' => hash('sha256', $request->ip()),
        ]);

        return response()->noContent();
    }
}
