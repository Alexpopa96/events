<?php

namespace App\Http\Controllers\Administration\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Notifications\NewReviewReceived;
use Illuminate\Http\RedirectResponse;

class Approve extends Controller
{
    public function __invoke(Review $review): RedirectResponse
    {
        $wasApproved = $review->status === 'approved';

        $review->update(['status' => 'approved', 'moderated_at' => now()]);

        if (! $wasApproved) {
            $review->load(['user', 'listing', 'providerProfile.user'])
                ->providerProfile->user->notify(new NewReviewReceived($review));
        }

        return back()->with('success', ['message' => 'Recenzia a fost aprobată.']);
    }
}
