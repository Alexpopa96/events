<?php

namespace App\Http\Controllers\Provider\Subscription;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->providerProfile;
        $profile->loadMissing(['currentSubscription.plan', 'invoices' => fn ($query) => $query->latest()]);

        return Inertia::render('Provider/Subscription/Index', [
            'currentPlanId' => $profile->currentSubscription?->subscription_plan_id,
            'subscription' => $profile->currentSubscription ? [
                'status' => $profile->currentSubscription->status,
                'ends_at' => optional($profile->currentSubscription->ends_at)->format('d.m.Y'),
            ] : null,
            'plans' => SubscriptionPlan::where('is_active', true)
                ->orderBy('position')
                ->get()
                ->map(fn ($plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => (float) $plan->price,
                    'currency' => $plan->currency,
                    'features' => $plan->features ?? [],
                ]),
            'invoices' => $profile->invoices->map(fn ($invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'amount' => (float) $invoice->amount,
                'currency' => $invoice->currency,
                'status' => $invoice->status,
                'issued_at' => optional($invoice->issued_at)->format('d.m.Y'),
            ]),
        ]);
    }
}
