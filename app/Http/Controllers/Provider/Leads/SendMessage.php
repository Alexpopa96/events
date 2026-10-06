<?php

namespace App\Http\Controllers\Provider\Leads;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\QuoteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Provider opens the conversation with a client first, from that client's
 * quote request. A thread always hangs off one of the provider's listings.
 * Redirects back so the chat tab on the leads page picks the thread up in place.
 */
class SendMessage extends Controller
{
    public function __invoke(Request $request, QuoteRequest $lead): RedirectResponse
    {
        $profile = $request->user()->providerProfile;

        // Same visibility rule as the leads list: open requests in a category the provider sells in.
        abort_unless(
            $lead->status === 'open'
                && $lead->user_id
                && $profile->listings()->where('category_id', $lead->category_id)->exists(),
            404,
        );

        $data = $request->validate([
            'listing_id' => [
                'required',
                Rule::exists('listings', 'id')
                    ->where('provider_profile_id', $profile->id)
                    ->where('category_id', $lead->category_id)
                    ->where('status', 'published'),
            ],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = Conversation::firstOrCreate([
            'listing_id' => $data['listing_id'],
            'client_id' => $lead->user_id,
        ], [
            'provider_profile_id' => $profile->id,
        ]);

        $conversation->addMessage($request->user(), $data['body'], Conversation::SIDE_PROVIDER);

        // Reaching out counts as contacting the lead.
        $profile->events()->firstOrCreate([
            'quote_request_id' => $lead->id,
            'type' => 'quote_request_view',
        ]);

        return back();
    }
}
