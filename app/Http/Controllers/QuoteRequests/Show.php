<?php

namespace App\Http\Controllers\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\County;
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
                // No offers/messaging system exists yet — providers can't respond
                // to a request in-app, so these are always 0 until that's built.
                'offers_count' => 0,
                'messages_count' => 0,
                'created_at' => $quoteRequest->created_at->format('d.m.Y, H:i'),
                'updated_at' => $quoteRequest->updated_at->format('d.m.Y, H:i'),
            ],
            'categories' => Category::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['id', 'name', 'slug']),
            'counties' => County::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
