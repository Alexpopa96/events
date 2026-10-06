<?php

namespace App\Http\Controllers\Administration\Providers;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Services\AnafLookupService;
use Illuminate\Http\JsonResponse;

class AnafLookup extends Controller
{
    public function __invoke(ProviderProfile $provider, AnafLookupService $anaf): JsonResponse
    {
        if (! $provider->cui) {
            return response()->json([
                'message' => 'Acest furnizor nu are un CUI înregistrat.',
            ], 422);
        }

        $company = $anaf->lookup($provider->cui);

        if (! $company) {
            return response()->json([
                'message' => 'Nu am găsit nicio firmă activă la ANAF pentru acest CUI.',
            ], 422);
        }

        // The admin explicitly asked to check this CUI against ANAF right now — that's
        // itself a real verification, worth recording even outside the registration flow.
        $provider->update([
            'anaf_verified_at' => now(),
            'anaf_status' => $company['stare_inregistrare'],
        ]);

        return response()->json([...$company, 'verified_at' => $provider->anaf_verified_at->format('d.m.Y H:i')]);
    }
}
