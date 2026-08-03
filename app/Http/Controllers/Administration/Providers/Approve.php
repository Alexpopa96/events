<?php

namespace App\Http\Controllers\Administration\Providers;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Notifications\ProviderApproved;
use Illuminate\Http\RedirectResponse;

class Approve extends Controller
{
    public function __invoke(ProviderProfile $provider): RedirectResponse
    {
        $provider->update([
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $provider->user->notify(new ProviderApproved($provider));

        return redirect()
            ->back()
            ->with('success', ['message' => 'Furnizorul a fost aprobat.']);
    }
}
