<?php

namespace App\Http\Controllers\Administration\Providers;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Notifications\ProviderRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Reject extends Controller
{
    public function __invoke(Request $request, ProviderProfile $provider): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $provider->update([
            'status' => 'rejected',
            'rejection_reason' => $data['reason'],
            'rejected_at' => now(),
        ]);

        $provider->user->notify(new ProviderRejected($provider, $data['reason']));

        return redirect()
            ->back()
            ->with('success', ['message' => 'Furnizorul a fost respins.']);
    }
}
