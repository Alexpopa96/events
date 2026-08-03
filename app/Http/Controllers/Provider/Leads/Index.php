<?php

namespace App\Http\Controllers\Provider\Leads;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->providerProfile;
        $categoryIds = $profile->listings()->distinct()->pluck('category_id');

        $contactedIds = $profile->events()
            ->where('type', 'quote_request_view')
            ->pluck('quote_request_id');

        $leads = QuoteRequest::whereIn('category_id', $categoryIds)
            ->where('status', 'open')
            ->with('category')
            ->latest()
            ->get()
            ->map(fn (QuoteRequest $lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'category' => $lead->category->name,
                'event_date' => optional($lead->event_date)->format('d.m.Y'),
                'city' => $lead->city,
                'county' => $lead->county,
                'budget_range' => $lead->budget_range,
                'message' => $lead->message,
                'created_at' => $lead->created_at->diffForHumans(),
                'contacted' => $contactedIds->contains($lead->id),
            ]);

        return Inertia::render('Provider/Leads/Index', [
            'leads' => $leads,
            'hasCategories' => $categoryIds->isNotEmpty(),
        ]);
    }
}
