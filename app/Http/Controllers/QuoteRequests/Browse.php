<?php

namespace App\Http\Controllers\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\County;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public board of open quote requests. Only event details are exposed —
 * the client's name and contact data stay behind the provider leads page.
 */
class Browse extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'county_id' => ['nullable', 'integer', 'exists:counties,id'],
        ]);

        $quoteRequests = QuoteRequest::query()
            ->where('status', 'open')
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('category', fn ($q) => $q->where('slug', $slug)))
            ->when($filters['county_id'] ?? null, fn ($query, $countyId) => $query->where('county_id', $countyId))
            ->with('category:id,name,slug')
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (QuoteRequest $quoteRequest) => [
                'id' => $quoteRequest->id,
                'title' => $quoteRequest->title ?: $quoteRequest->category?->name,
                'category' => $quoteRequest->category?->name,
                'message' => $quoteRequest->message,
                'event_type' => $quoteRequest->event_type,
                'event_date' => optional($quoteRequest->event_date)->format('d.m.Y'),
                'location' => collect([$quoteRequest->city, $quoteRequest->county])->filter()->unique()->implode(', '),
                'guest_count' => $quoteRequest->guest_count,
                'budget_range' => $quoteRequest->budget_range,
                'created_at_human' => $quoteRequest->created_at->diffForHumans(),
            ]);

        return Inertia::render('QuoteRequests/Browse', [
            'quoteRequests' => $quoteRequests,
            'filters' => [
                'category' => $filters['category'] ?? '',
                'county_id' => $filters['county_id'] ?? '',
            ],
            'categories' => Category::where('is_active', true)->whereNull('parent_id')->orderBy('position')->get(['name', 'slug']),
            'counties' => County::orderBy('name')->get(['id', 'name']),
            'openCount' => QuoteRequest::where('status', 'open')->count(),
        ]);
    }
}
