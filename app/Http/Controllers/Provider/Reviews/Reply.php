<?php

namespace App\Http\Controllers\Provider\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Notifications\ReviewReplied;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Reply extends Controller
{
    public function __invoke(Request $request, Review $review): RedirectResponse
    {
        $profile = $request->user()->providerProfile;

        abort_unless($review->provider_profile_id === $profile->id && $review->status === 'approved', 404);

        $data = $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ]);

        $isNew = $review->provider_reply === null;

        $review->update([
            'provider_reply' => trim($data['reply']),
            'provider_replied_at' => now(),
        ]);

        // The client is told once; later edits of the reply stay quiet.
        if ($isNew) {
            $review->load(['user', 'listing', 'providerProfile'])->user->notify(new ReviewReplied($review));
        }

        return back()->with('success', ['message' => 'Răspunsul tău a fost publicat.']);
    }
}
