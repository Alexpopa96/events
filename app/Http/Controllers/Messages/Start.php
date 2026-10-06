<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Client opens (or continues) the thread for a listing from its public page.
 */
class Start extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->status === 'published', 404);

        $user = $request->user();

        // Providers can't message themselves through their own listing.
        abort_if($listing->providerProfile->user_id === $user->id, 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $conversation = Conversation::firstOrCreate([
            'listing_id' => $listing->id,
            'client_id' => $user->id,
        ], [
            'provider_profile_id' => $listing->provider_profile_id,
        ]);

        $conversation->addMessage($user, $data['body'], Conversation::SIDE_CLIENT);

        return redirect()->route('messages.index', $conversation);
    }
}
