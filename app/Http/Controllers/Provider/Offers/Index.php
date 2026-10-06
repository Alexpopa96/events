<?php

namespace App\Http\Controllers\Provider\Offers;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The provider's full offer history, independent of the Leads inbox (which only
 * shows requests still open — an accepted request's offer would otherwise vanish).
 */
class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->providerProfile;
        $filter = $request->string('filter', 'all')->toString();

        $offers = Offer::query()
            ->where('provider_profile_id', $profile->id)
            ->with(['quoteRequest:id,title,name,email,phone,category_id', 'quoteRequest.category:id,name', 'listing:id,title,slug'])
            ->latest()
            ->get();

        $withStatus = $offers->map(fn (Offer $offer) => [
            'id' => $offer->id,
            'status' => $offer->effectiveStatus(),
            'price' => $offer->price,
            'includes' => $offer->includes ?? [],
            'message' => $offer->message,
            'valid_until' => $offer->valid_until->format('d.m.Y'),
            'listing' => $offer->listing ? ['title' => $offer->listing->title, 'slug' => $offer->listing->slug] : null,
            'decline_reason' => $offer->decline_reason,
            'created_at' => $offer->created_at->diffForHumans(),
            'lead' => [
                'id' => $offer->quote_request_id,
                'title' => $offer->quoteRequest->title,
                'category' => $offer->quoteRequest->category?->name,
            ],
            // Contact details only make sense to show once the client has actually accepted.
            'client' => $offer->status === Offer::ACCEPTED
                ? ['name' => $offer->quoteRequest->name, 'email' => $offer->quoteRequest->email, 'phone' => $offer->quoteRequest->phone]
                : null,
        ]);

        $filtered = match ($filter) {
            'awaiting' => $withStatus->whereIn('status', Offer::OPEN_STATUSES),
            'accepted' => $withStatus->where('status', Offer::ACCEPTED),
            'declined' => $withStatus->whereIn('status', [Offer::DECLINED, Offer::EXPIRED, Offer::WITHDRAWN]),
            default => $withStatus,
        };

        return Inertia::render('Provider/Offers/Index', [
            'offers' => $filtered->values(),
            'filter' => $filter,
            'counts' => [
                'all' => $withStatus->count(),
                'awaiting' => $withStatus->whereIn('status', Offer::OPEN_STATUSES)->count(),
                'accepted' => $withStatus->where('status', Offer::ACCEPTED)->count(),
                'declined' => $withStatus->whereIn('status', [Offer::DECLINED, Offer::EXPIRED, Offer::WITHDRAWN])->count(),
            ],
        ]);
    }
}
