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

        return response()->json($company);
    }
}
