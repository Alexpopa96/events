<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Client jumps from a listing straight into its chat with the provider, without
 * writing a first message. The thread only shows up in the inbox list once a
 * message is sent (the list requires last_message_at).
 */
class Open extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->status === 'published', 404);

        $user = $request->user();

        // Providers can't message themselves through their own listing.
        abort_if($listing->providerProfile->user_id === $user->id, 403);

        $conversation = Conversation::firstOrCreate([
            'listing_id' => $listing->id,
            'client_id' => $user->id,
        ], [
            'provider_profile_id' => $listing->provider_profile_id,
        ]);

        return redirect()->route('messages.index', $conversation);
    }
}
