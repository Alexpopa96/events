<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Reply inside an existing thread, from either side.
 */
class Store extends Controller
{
    public function __invoke(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $side = $conversation->sideFor($user);

        abort_unless($side, 404);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $conversation->addMessage($user, $data['body'], $side);

        return back();
    }
}
