<?php

namespace App\Http\Controllers\QuoteRequests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $quoteRequests = $request->user()
            ->quoteRequests()
            ->with('category:id,name,slug')
            ->latest()
            ->get();

        return Inertia::render('QuoteRequests/Index', [
            'quoteRequests' => $quoteRequests->map(fn ($quoteRequest) => [
                'id' => $quoteRequest->id,
                'title' => $quoteRequest->title ?: $quoteRequest->category->name,
                'category' => $quoteRequest->category->name,
                'category_slug' => $quoteRequest->category->slug,
                'event_date' => optional($quoteRequest->event_date)->format('d.m.Y'),
                'city' => $quoteRequest->city,
                'county' => $quoteRequest->county,
                'guest_count' => $quoteRequest->guest_count,
                'budget_range' => $quoteRequest->budget_range,
                'message' => $quoteRequest->message,
                'notes' => $quoteRequest->notes,
                'status' => $quoteRequest->status,
                // No offers/messaging system exists yet — providers can't respond
                // to a request in-app, so this is always 0 until that's built.
                'offers_count' => 0,
                'created_at' => $quoteRequest->created_at->format('d.m.Y, H:i'),
                'created_at_human' => $quoteRequest->created_at->diffForHumans(),
            ])->values(),
            'stats' => [
                'total' => $quoteRequests->count(),
                'pending' => $quoteRequests->where('status', 'pending_review')->count(),
                'active' => $quoteRequests->where('status', 'open')->count(),
                'withOffers' => 0,
                'closed' => $quoteRequests->whereIn('status', ['closed', 'rejected'])->count(),
            ],
        ]);
    }
}
