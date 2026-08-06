<?php

namespace App\Http\Controllers\Administration\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $status = $request->string('status', 'all')->toString();
        $search = $request->string('search')->toString();

        $quoteRequests = QuoteRequest::query()
            ->with(['category:id,name', 'user:id,name,email'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (QuoteRequest $quoteRequest) => [
                'id' => $quoteRequest->id,
                'title' => $quoteRequest->title,
                'category' => $quoteRequest->category?->name,
                'status' => $quoteRequest->status,
                'name' => $quoteRequest->name,
                'email' => $quoteRequest->email,
                'phone' => $quoteRequest->phone,
                'city' => $quoteRequest->city,
                'county' => $quoteRequest->county,
                'created_at' => $quoteRequest->created_at->format('d.m.Y H:i'),
            ]);

        $counts = QuoteRequest::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return Inertia::render('Administration/QuoteRequests/Index', [
            'quoteRequests' => $quoteRequests,
            'filters' => ['status' => $status, 'search' => $search],
            'counts' => [
                'all' => $counts->sum(),
                'pending_review' => $counts->get('pending_review', 0),
                'open' => $counts->get('open', 0),
                'rejected' => $counts->get('rejected', 0),
                'closed' => $counts->get('closed', 0),
            ],
        ]);
    }
}
