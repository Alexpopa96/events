<?php

namespace App\Http\Controllers\QuoteRequests\Offers;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Notifications\OfferDeclined;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Decline extends Controller
{
    public function __invoke(Request $request, Offer $offer): RedirectResponse
    {
        abort_unless($offer->quoteRequest->user_id === $request->user()->id, 403);
        abort_unless($offer->isAwaitingAnswer(), 403, 'Această ofertă nu mai este disponibilă.');

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $offer->update([
            'status' => Offer::DECLINED,
            'responded_at' => now(),
            'decline_reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
        ]);

        $offer->providerProfile->user->notify(new OfferDeclined($offer->load('quoteRequest')));

        return back()->with('success', ['message' => 'Ai refuzat oferta.']);
    }
}
