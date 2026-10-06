<?php

namespace App\Http\Controllers\Administration\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $status = $request->string('status', 'pending')->toString();
        $search = $request->string('search')->toString();

        $reviews = Review::query()
            ->with(['user:id,name,email', 'listing:id,title,slug', 'providerProfile:id,company_name'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('comment', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('listing', fn ($listing) => $listing->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('providerProfile', fn ($profile) => $profile->where('company_name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Review $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'status' => $review->status,
                'author' => $review->user->name,
                'author_email' => $review->user->email,
                'listing_title' => $review->listing?->title,
                'listing_slug' => $review->listing?->slug,
                'provider' => $review->providerProfile?->company_name,
                'created_at' => $review->created_at->format('d.m.Y H:i'),
            ]);

        $counts = Review::query()->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        return Inertia::render('Administration/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => ['status' => $status, 'search' => $search],
            'counts' => [
                'all' => $counts->sum(),
                'pending' => $counts->get('pending', 0),
                'approved' => $counts->get('approved', 0),
                'rejected' => $counts->get('rejected', 0),
            ],
        ]);
    }
}
