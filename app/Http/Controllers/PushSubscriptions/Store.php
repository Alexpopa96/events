<?php

namespace App\Http\Controllers\PushSubscriptions;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Persists the browser's PushSubscription object (from
 * pushManager.subscribe()), so future notifications can reach this device.
 */
class Store extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
        );

        return response()->json(['message' => 'Abonat cu succes.']);
    }
}
