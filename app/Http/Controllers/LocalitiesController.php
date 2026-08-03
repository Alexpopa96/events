<?php

namespace App\Http\Controllers;

use App\Models\Locality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalitiesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'judet_id' => ['required', 'integer', 'exists:counties,id'],
        ]);

        return response()->json(
            Locality::where('county_id', $data['judet_id'])
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }
}
