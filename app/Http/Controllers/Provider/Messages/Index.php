<?php

namespace App\Http\Controllers\Provider\Messages;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Support\Inbox;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Provider inbox: threads clients opened on the provider's listings.
 */
class Index extends Controller
{
    public function __invoke(Request $request, ?Conversation $conversation = null): Response
    {
        $user = $request->user();

        if ($conversation) {
            abort_unless($conversation->sideFor($user) === Conversation::SIDE_PROVIDER, 404);
            $conversation->markReadBy(Conversation::SIDE_PROVIDER);
        }

        return Inertia::render('Provider/Messages/Index', Inbox::props($user, $conversation, $request->integer('listing') ?: null) + [
            'activeId' => $conversation?->id,
            'listingFilter' => $request->integer('listing') ?: null,
        ]);
    }
}
