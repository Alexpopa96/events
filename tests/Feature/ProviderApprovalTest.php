<?php

namespace Tests\Feature;

use App\Models\County;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProviderApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makeProvider(string $status = 'pending'): User
    {
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

    public function test_pending_provider_is_redirected_away_from_dashboard(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $user = $this->makeProvider('pending');

        $response = $this->actingAs($user)->get('/furnizor/dashboard');

        $response->assertRedirect(route('provider.pending'));
    }

    public function test_active_provider_can_access_dashboard(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $user = $this->makeProvider('active');

        $response = $this->actingAs($user)->get('/furnizor/dashboard');

        $response->assertOk();
    }

    public function test_admin_can_approve_a_pending_provider(): void
    {
        Notification::fake();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $provider = $this->makeProvider('pending');
        $admin = User::factory()->create(['status' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post(route('administration.providers.approve', $provider->providerProfile));

        $response->assertRedirect(route('administration.providers.index'));

        $this->assertSame('active', $provider->providerProfile->fresh()->status);
        $this->assertNotNull($provider->providerProfile->fresh()->approved_at);

        Notification::assertSentTo($provider, \App\Notifications\ProviderApproved::class);
    }

    public function test_admin_can_reject_a_pending_provider(): void
    {
        Notification::fake();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $provider = $this->makeProvider('pending');
        $admin = User::factory()->create(['status' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post(route('administration.providers.reject', $provider->providerProfile));

        $response->assertRedirect(route('administration.providers.index'));

        $this->assertSame('suspended', $provider->providerProfile->fresh()->status);

        Notification::assertSentTo($provider, \App\Notifications\ProviderRejected::class);
    }
}
