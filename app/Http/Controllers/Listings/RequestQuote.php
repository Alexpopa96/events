<?php

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingEvent;
use App\Models\QuoteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RequestQuote extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        $data = $request->validate([
            'event_date' => ['nullable', 'date', 'after:today'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $quoteRequest = QuoteRequest::create([
            'category_id' => $listing->category_id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'event_date' => $data['event_date'] ?? null,
            'message' => $data['message'],
            'status' => 'open',
        ]);

        ListingEvent::create([
            'provider_profile_id' => $listing->provider_profile_id,
            'listing_id' => $listing->id,
            'quote_request_id' => $quoteRequest->id,
            'user_id' => $user->id,
            'type' => 'contact_form_submit',
            'ip_hash' => hash('sha256', $request->ip()),
        ]);

        return back()->with('success', ['message' => 'Cererea ta a fost trimisă furnizorului.']);
    }
}
