<?php

namespace App\Http\Controllers\Administration\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Inertia\Inertia;
use Inertia\Response;

class Show extends Controller
{
    public function __invoke(QuoteRequest $quoteRequest): Response
    {
        $quoteRequest->load(['category:id,name', 'county:id,name', 'locality:id,name', 'user:id,name,email,created_at']);

        return Inertia::render('Administration/QuoteRequests/Show', [
            'quoteRequest' => [
                'id' => $quoteRequest->id,
                'title' => $quoteRequest->title,
                'message' => $quoteRequest->message,
                'notes' => $quoteRequest->notes,
                'category' => $quoteRequest->category?->only(['id', 'name']),
                'event_type' => $quoteRequest->event_type,
                'event_date' => optional($quoteRequest->event_date)->format('d.m.Y'),
                'county' => $quoteRequest->county?->only(['name']) ?? ($quoteRequest->county ? ['name' => $quoteRequest->county] : null),
                'locality' => $quoteRequest->locality?->only(['name']) ?? ($quoteRequest->city ? ['name' => $quoteRequest->city] : null),
                'guest_count' => $quoteRequest->guest_count,
                'budget_range' => $quoteRequest->budget_range,
                'preferences' => $quoteRequest->preferences,
                'name' => $quoteRequest->name,
                'email' => $quoteRequest->email,
                'phone' => $quoteRequest->phone,
                'status' => $quoteRequest->status,
                'created_at' => $quoteRequest->created_at->format('d.m.Y H:i'),
                'approved_at' => $quoteRequest->approved_at?->format('d.m.Y H:i'),
                'rejected_at' => $quoteRequest->rejected_at?->format('d.m.Y H:i'),
                'rejection_reason' => $quoteRequest->rejection_reason,
                'user' => $quoteRequest->user?->only(['id', 'name', 'email']),
            ],
        ]);
    }
}
