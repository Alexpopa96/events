<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Support\Inbox;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Client inbox: every thread the user started with a provider, one per listing.
 */
class Index extends Controller
{
    public function __invoke(Request $request, ?Conversation $conversation = null): Response
    {
        $user = $request->user();

        if ($conversation) {
            abort_unless($conversation->sideFor($user) === Conversation::SIDE_CLIENT, 404);
            $conversation->markReadBy(Conversation::SIDE_CLIENT);
        }

        return Inertia::render('Messages/Index', Inbox::props($user, $conversation, $request->integer('listing') ?: null) + [
            'activeId' => $conversation?->id,
        ]);
    }
}
