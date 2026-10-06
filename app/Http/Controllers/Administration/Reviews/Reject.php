<?php

namespace App\Http\Controllers\Administration\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;

class Reject extends Controller
{
    public function __invoke(Review $review): RedirectResponse
    {
        // A rejected review can no longer carry a public reply.
        $review->update([
            'status' => 'rejected',
            'moderated_at' => now(),
            'provider_reply' => null,
            'provider_replied_at' => null,
        ]);

        return back()->with('success', ['message' => 'Recenzia a fost respinsă.']);
    }
}
