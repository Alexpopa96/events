<?php

namespace App\Http\Controllers\Provider\Offers;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\QuoteRequest;
use App\Notifications\OfferReceived;
use App\Support\Offers\OfferData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A provider sends (or, called again, edits) their one offer for a lead.
 */
class Store extends Controller
{
    public function __invoke(Request $request, QuoteRequest $lead): RedirectResponse
    {
        $profile = $request->user()->providerProfile;

        abort_unless($lead->status === 'open', 404);

        $existing = Offer::where('quote_request_id', $lead->id)->where('provider_profile_id', $profile->id)->first();
        abort_if($existing && ! in_array($existing->status, Offer::OPEN_STATUSES, true), 403, 'Această ofertă nu mai poate fi modificată.');

        $data = OfferData::validate($request, $lead, $profile);

        $offer = Offer::updateOrCreate(
            ['quote_request_id' => $lead->id, 'provider_profile_id' => $profile->id],
            [...$data, 'status' => Offer::SENT, 'viewed_at' => null, 'responded_at' => null]
        );

        if ($lead->user_id) {
            $lead->user->notify(new OfferReceived($offer->load(['quoteRequest', 'providerProfile']), updated: (bool) $existing));
        }

        return back()->with('success', ['message' => $existing ? 'Oferta a fost actualizată.' : 'Oferta a fost trimisă.']);
    }
}
