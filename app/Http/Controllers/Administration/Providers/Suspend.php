<?php

namespace App\Http\Controllers\Administration\Providers;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Notifications\ProviderSuspended;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Suspend extends Controller
{
    public function __invoke(Request $request, ProviderProfile $provider): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $provider->update([
            'status' => 'suspended',
            'suspension_reason' => $data['reason'],
            'suspended_at' => now(),
        ]);

        $provider->user->notify(new ProviderSuspended($provider, $data['reason']));

        return redirect()
            ->back()
            ->with('success', ['message' => 'Furnizorul a fost suspendat.']);
    }
}
