<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendingApproval extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $profile = $request->user()->providerProfile;

        if ($profile?->status === 'active') {
            return redirect()->route('provider.dashboard');
        }

        return Inertia::render('Provider/PendingApproval', [
            'status' => $profile?->status,
            'reason' => match ($profile?->status) {
                'rejected' => $profile?->rejection_reason,
                'suspended' => $profile?->suspension_reason,
                default => null,
            },
        ]);
    }
}
