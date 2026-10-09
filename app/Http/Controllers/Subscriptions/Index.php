<?php

namespace App\Http\Controllers\Subscriptions;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\SubscriptionPlan;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Subscriptions/Index', [
            'plans' => SubscriptionPlan::where('is_active', true)
                ->orderBy('position')
                ->get()
                ->map(fn (SubscriptionPlan $plan) => $plan->toPublicArray()),
            'stats' => [
                'providers' => ProviderProfile::where('status', 'active')->count(),
            ],
        ]);
    }
}
