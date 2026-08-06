<?php

namespace App\Http\Controllers\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class Success extends Controller
{
    public function __invoke(Request $request, QuoteRequest $quoteRequest): Response|RedirectResponse
    {
        abort_unless($quoteRequest->user_id === $request->user()->id, 403);

        if ((int) $request->session()->get('quote_request_success_id') !== $quoteRequest->id) {
            return redirect()->route('quote-requests.show', $quoteRequest);
        }

        $quoteRequest->load('category:id,name');

        return Inertia::render('QuoteRequests/Success', [
            'quoteRequest' => [
                'id' => $quoteRequest->id,
                'title' => $quoteRequest->title,
                'category' => $quoteRequest->category->name,
                'event_type' => $quoteRequest->event_type,
                'event_date' => optional($quoteRequest->event_date)->format('d.m.Y'),
                'city' => $quoteRequest->city,
                'county' => $quoteRequest->county,
                'guest_count' => $quoteRequest->guest_count,
                'budget_range' => $quoteRequest->budget_range,
                'preferences' => $quoteRequest->preferences,
                'message' => $quoteRequest->message,
                'name' => $quoteRequest->name,
                'contact_method' => $quoteRequest->contact_method,
                'status' => $quoteRequest->status,
                'rejection_reason' => $quoteRequest->rejection_reason,
                'created_at' => $quoteRequest->created_at->format('d.m.Y, H:i'),
            ],
        ]);
    }
}
