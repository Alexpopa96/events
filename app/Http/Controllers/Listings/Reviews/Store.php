<?php

namespace App\Http\Controllers\Listings\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewPendingApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

/**
 * A client who talked with the provider leaves a rating. It stays hidden until an admin approves it.
 */
class Store extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->status === 'published', 404);

        /** @var User $user */
        $user = $request->user();

        abort_unless($listing->canBeReviewedBy($user), 403, 'Poți lăsa o recenzie după ce ai discutat cu furnizorul.');

        if ($listing->reviews()->where('user_id', $user->id)->exists()) {
            return back()->with('error', ['message' => 'Ai lăsat deja o recenzie pentru acest anunț.']);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = Review::create([
            'listing_id' => $listing->id,
            'provider_profile_id' => $listing->provider_profile_id,
            'user_id' => $user->id,
            'rating' => $data['rating'],
            'comment' => filled($data['comment'] ?? null) ? trim($data['comment']) : null,
            'status' => 'pending',
        ]);

        Notification::route('mail', config('mail.support_address'))
            ->notify(new NewReviewPendingApproval($review->load(['user', 'listing'])));

        return back()->with('success', ['message' => 'Mulțumim! Recenzia ta va fi publicată după verificare.']);
    }
}
