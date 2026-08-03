<?php

namespace App\Http\Controllers\Provider\Subscription;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Update extends Controller
{
    /**
     * Switches the provider's plan directly. There is no payment processor
     * wired up yet (see project proposal — Stripe/Cashier integration is a
     * separate follow-up), so this simulates what a successful checkout
     * would leave behind: an active subscription row against the chosen plan.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $plan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);
        $profile = $request->user()->providerProfile;

        $profile->subscriptions()->where('status', 'active')->update(['status' => 'canceled']);

        $profile->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => $plan->billing_period === 'yearly' ? now()->addYear() : now()->addMonth(),
        ]);

        return redirect()
            ->route('provider.subscription.index')
            ->with('success', ['message' => "Ai trecut pe planul {$plan->name}."]);
    }
}
