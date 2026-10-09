<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_active_plans_in_order(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);
        SubscriptionPlan::where('slug', 'premium')->update(['is_active' => false]);

        $this->get(route('subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Subscriptions/Index')
                ->has('plans', 2)
                ->where('plans.0.slug', 'gratuit')
                ->where('plans.1.slug', 'standard')
                ->where('plans.1.max_listings', 3)
                ->where('plans.1.price', 99)
            );
    }
}
