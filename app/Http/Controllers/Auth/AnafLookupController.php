<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AnafLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnafLookupController extends Controller
{
    public function __invoke(Request $request, AnafLookupService $anaf): JsonResponse
    {
        $data = $request->validate([
            'cui' => ['required', 'string', 'max:20'],
        ]);

        $company = $anaf->lookup($data['cui']);

        if (! $company) {
            return response()->json([
                'message' => 'Nu am găsit nicio firmă activă la ANAF pentru acest CUI.',
            ], 422);
        }

        return response()->json($company);
    }
}
