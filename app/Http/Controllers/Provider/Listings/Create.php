<?php

namespace App\Http\Controllers\Provider\Listings;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\County;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Create extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->providerProfile;
        $plan = $profile->activePlan();
        $activeListings = $profile->listings()->where('status', '!=', 'archived')->count();

        return Inertia::render('Provider/Listings/Create', [
            'categories' => Category::where('is_active', true)
                ->orderBy('position')
                ->get(['id', 'name', 'slug', 'parent_id']),
            'counties' => County::orderBy('name')->get(['id', 'name']),
            'quota' => [
                'used' => $activeListings,
                'max' => $plan?->max_listings,
                'plan_name' => $plan?->name,
            ],
        ]);
    }
}
