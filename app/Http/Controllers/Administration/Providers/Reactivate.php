<?php

namespace App\Http\Controllers\Administration\Providers;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Notifications\ProviderReactivated;
use Illuminate\Http\RedirectResponse;

class Reactivate extends Controller
{
    public function __invoke(ProviderProfile $provider): RedirectResponse
    {
        $provider->update([
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $provider->user->notify(new ProviderReactivated($provider));

        return redirect()
            ->back()
            ->with('success', ['message' => 'Furnizorul a fost reactivat.']);
    }
}
