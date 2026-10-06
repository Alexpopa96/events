<?php

namespace App\Http\Controllers\Provider\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Approved reviews on the provider's listings, with room to answer publicly.
 */
class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->providerProfile;

        $filter = $request->string('filter', 'all')->toString();

        $base = Review::query()->where('provider_profile_id', $profile->id)->approved();

        $counts = [
            'all' => (clone $base)->count(),
            'unanswered' => (clone $base)->whereNull('provider_reply')->count(),
        ];

        $reviews = $base
            ->when($filter === 'unanswered', fn ($query) => $query->whereNull('provider_reply'))
            ->with(['user:id,name', 'listing:id,title,slug'])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Review $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'author' => $review->user->name,
                'listing_title' => $review->listing?->title,
                'listing_slug' => $review->listing?->slug,
                'provider_reply' => $review->provider_reply,
                'provider_replied_at' => $review->provider_replied_at?->diffForHumans(),
                'created_at' => $review->created_at->diffForHumans(),
            ]);

        return Inertia::render('Provider/Reviews/Index', [
            'reviews' => $reviews,
            'filter' => $filter,
            'counts' => $counts,
            'summary' => [
                'rating' => $profile->averageRating() ?: null,
                'total' => $counts['all'],
            ],
        ]);
    }
}
