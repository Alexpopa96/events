<?php

namespace App\Http\Controllers\Providers;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ToggleFavorite extends Controller
{
    public function __invoke(Request $request, ProviderProfile $providerProfile): RedirectResponse
    {
        $favorites = $request->user()->providerFavorites();

        $existing = $favorites->where('provider_profile_id', $providerProfile->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $favorites->create(['provider_profile_id' => $providerProfile->id]);
        }

        return back();
    }
}
