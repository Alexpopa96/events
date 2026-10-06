<?php

namespace App\Http\Controllers\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\County;
use App\Models\Offer;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Show extends Controller
{
    public function __invoke(Request $request, QuoteRequest $quoteRequest): Response
    {
        abort_unless($quoteRequest->user_id === $request->user()->id, 403);

        $quoteRequest->load('category:id,name,slug');

        // Opening the request marks any offers still waiting on the client as seen.
        $quoteRequest->offers()->where('status', Offer::SENT)->update(['status' => Offer::VIEWED, 'viewed_at' => now()]);

        $offers = $quoteRequest->offers()
            ->with(['providerProfile:id,company_name,slug,logo_path', 'listing:id,title,slug'])
            ->get()
            ->map(fn (Offer $offer) => [
                'id' => $offer->id,
                'status' => $offer->effectiveStatus(),
                'price' => $offer->price,
                'includes' => $offer->includes ?? [],
                'message' => $offer->message,
                'valid_until' => $offer->valid_until->format('d.m.Y'),
                'valid_until_iso' => $offer->valid_until->toDateString(),
                'decline_reason' => $offer->decline_reason,
                'created_at' => $offer->created_at->diffForHumans(),
                'provider' => [
                    'company_name' => $offer->providerProfile->company_name,
                    'slug' => $offer->providerProfile->slug,
                    'logo_url' => $offer->providerProfile->logoUrl(),
                ],
                'listing' => $offer->listing ? ['title' => $offer->listing->title, 'slug' => $offer->listing->slug] : null,
            ])
            ->sortBy(fn ($offer) => match ($offer['status']) {
                'sent', 'viewed' => 0,
                'accepted' => 1,
                default => 2,
            })
            ->values();

        return Inertia::render('QuoteRequests/Show', [
            'quoteRequest' => [
                'id' => $quoteRequest->id,
                'category_id' => $quoteRequest->category_id,
                'category' => $quoteRequest->category->name,
                'title' => $quoteRequest->title ?: $quoteRequest->category->name,
                'message' => $quoteRequest->message,
                'event_type' => $quoteRequest->event_type,
                'event_date' => optional($quoteRequest->event_date)->format('d.m.Y'),
                'event_date_iso' => optional($quoteRequest->event_date)->toDateString(),
                'county_id' => $quoteRequest->county_id,
                'locality_id' => $quoteRequest->locality_id,
                'city' => $quoteRequest->city,
                'county' => $quoteRequest->county,
                'guest_count' => $quoteRequest->guest_count,
                'budget_range' => $quoteRequest->budget_range,
                'preferences' => $quoteRequest->preferences ?? [],
                'notes' => $quoteRequest->notes,
                'name' => $quoteRequest->name,
                'email' => $quoteRequest->email,
                'phone' => $quoteRequest->phone,
                'contact_method' => $quoteRequest->contact_method,
                'platform_only' => $quoteRequest->platform_only,
                'status' => $quoteRequest->status,
                'rejection_reason' => $quoteRequest->rejection_reason,
                'offers_count' => $offers->count(),
                'created_at' => $quoteRequest->created_at->format('d.m.Y, H:i'),
                'updated_at' => $quoteRequest->updated_at->format('d.m.Y, H:i'),
            ],
            'categories' => Category::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['id', 'name', 'slug']),
            'counties' => County::orderBy('name')->get(['id', 'name']),
            'offers' => $offers,
            'package' => $quoteRequest->group_token
                ? $quoteRequest->siblings()->with('category:id,name')->get()->map(fn (QuoteRequest $sibling) => [
                    'id' => $sibling->id,
                    'category' => $sibling->category->name,
                    'status' => $sibling->status,
                ])->values()
                : [],
        ]);
    }
}
