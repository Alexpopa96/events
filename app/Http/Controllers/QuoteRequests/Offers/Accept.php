<?php

namespace App\Http\Controllers\QuoteRequests\Offers;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Offer;
use App\Notifications\OfferAccepted;
use App\Notifications\OfferDeclined;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Accept extends Controller
{
    public function __invoke(Request $request, Offer $offer): RedirectResponse
    {
        $quoteRequest = $offer->quoteRequest;

        abort_unless($quoteRequest->user_id === $request->user()->id, 403);
        abort_unless($offer->isAwaitingAnswer(), 403, 'Această ofertă nu mai este disponibilă.');

        $others = $quoteRequest->offers()
            ->where('id', '!=', $offer->id)
            ->whereIn('status', Offer::OPEN_STATUSES)
            ->with('providerProfile.user')
            ->get();

        DB::transaction(function () use ($offer, $quoteRequest, $others) {
            $offer->update(['status' => Offer::ACCEPTED, 'responded_at' => now()]);
            $others->each->update(['status' => Offer::DECLINED, 'responded_at' => now()]);
            $quoteRequest->update(['status' => 'closed']);

            // The provider's calendar reflects the confirmed booking automatically.
            // firstOrCreate leaves an existing block (e.g. one they set by hand) alone.
            if ($quoteRequest->event_date) {
                AvailabilityBlock::firstOrCreate(
                    ['provider_profile_id' => $offer->provider_profile_id, 'date' => $quoteRequest->event_date->toDateString()],
                    ['source' => AvailabilityBlock::OFFER, 'offer_id' => $offer->id, 'note' => $quoteRequest->title]
                );
            }
        });

        $offer->providerProfile->user->notify(new OfferAccepted($offer->load('quoteRequest')));

        $others->each(fn (Offer $other) => $other->providerProfile->user->notify(new OfferDeclined($other->load('quoteRequest'), chosenAnother: true)));

        return back()->with('success', ['message' => 'Ai acceptat oferta. Furnizorul a fost notificat și te va contacta.']);
    }
}
