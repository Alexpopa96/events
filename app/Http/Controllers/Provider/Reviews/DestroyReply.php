<?php

namespace App\Http\Controllers\Provider\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DestroyReply extends Controller
{
    public function __invoke(Request $request, Review $review): RedirectResponse
    {
        abort_unless($review->provider_profile_id === $request->user()->providerProfile->id, 404);

        $review->update(['provider_reply' => null, 'provider_replied_at' => null]);

        return back()->with('success', ['message' => 'Răspunsul a fost șters.']);
    }
}
