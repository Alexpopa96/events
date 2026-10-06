<?php

namespace Tests\Feature;

use App\Models\County;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProviderNavTest extends TestCase
{
    use RefreshDatabase;

    private function makeProvider(string $status): User
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $county = County::create(['name' => 'Cluj']);
        $locality = Locality::create(['county_id' => $county->id, 'name' => 'Cluj-Napoca']);

        $user = User::factory()->create(['status' => true]);
        $user->assignRole('furnizor');

        ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Test Company SRL',
            'cui' => '12345678',
            'slug' => 'test-company-srl',
            'county_id' => $county->id,
            'locality_id' => $locality->id,
            'status' => $status,
        ]);

        return $user;
    }

    public function test_active_provider_pages_share_nav_data(): void
    {
        $user = $this->makeProvider('active');

        foreach (['provider.dashboard', 'provider.leads.index', 'provider.listings.index', 'provider.subscription.index', 'provider.profile.edit'] as $name) {
            $this->actingAs($user)->get(route($name))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('providerNav.company_name', 'Test Company SRL')
                    ->where('providerNav.new_leads', 0));
        }
    }

    public function test_pending_provider_gets_no_nav_data(): void
    {
        $user = $this->makeProvider('pending');

        $this->actingAs($user)->get(route('provider.pending'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('providerNav', null));
    }
}
