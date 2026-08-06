<?php

namespace App\Http\Controllers\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Locality;
use App\Models\QuoteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Store extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'event_type' => ['nullable', 'string', 'max:50'],
            'event_date' => ['nullable', 'date', 'after:today'],
            'county_id' => ['nullable', 'integer', 'exists:counties,id'],
            'locality_id' => ['nullable', 'integer', 'exists:localities,id'],
            'guest_count' => ['nullable', 'string', 'max:50'],
            'budget_range' => ['nullable', 'string', 'max:50'],
            'preferences' => ['nullable', 'array'],
            'preferences.*' => ['string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'contact_method' => ['nullable', 'string', 'max:30'],
            'platform_only' => ['nullable', 'boolean'],
        ]);

        $quoteRequest = QuoteRequest::create([
            ...$data,
            'user_id' => $request->user()->id,
            'county' => ($data['county_id'] ?? null) ? County::find($data['county_id'])?->name : null,
            'city' => ($data['locality_id'] ?? null) ? Locality::find($data['locality_id'])?->name : null,
            'status' => 'pending_review',
        ]);

        return redirect()->route('quote-requests.success', $quoteRequest)
            ->with('success', ['message' => 'Cererea ta a fost trimisă spre aprobare.'])
            ->with('quote_request_success_id', $quoteRequest->id);
    }
}
