<?php

namespace App\Http\Controllers\Provider\Profile;

use App\Http\Controllers\Controller;
use App\Models\County;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Edit extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->providerProfile;

        return Inertia::render('Provider/Profile/Edit', [
            'profile' => [
                'company_name' => $profile->company_name,
                'cui' => $profile->cui,
                'description' => $profile->description,
                'phone' => $profile->phone,
                'whatsapp' => $profile->whatsapp,
                'email' => $profile->email,
                'website' => $profile->website,
                'address' => $profile->address,
                'county_id' => $profile->county_id,
                'locality_id' => $profile->locality_id,
                'social_links' => $profile->social_links ?? [],
                'logo_path' => $profile->logo_path ? "/storage/{$profile->logo_path}" : null,
                'cover_path' => $profile->cover_path ? "/storage/{$profile->cover_path}" : null,
                'status' => $profile->status,
                'completion_score' => $profile->calculateProfileCompletionScore(),
            ],
            'counties' => County::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
