<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProviderTrustTest extends TestCase
{
    use RefreshDatabase;

    private County $county;

    private Locality $locality;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $this->county = County::create(['name' => 'Cluj']);
        $this->locality = Locality::create(['county_id' => $this->county->id, 'name' => 'Cluj-Napoca']);
        $this->category = Category::create(['slug' => 'fotografie', 'name' => 'Fotografie']);
    }

    private function makeProvider(array $overrides = []): User
    {
        $user = User::factory()->create(['status' => true]);
        $user->assignRole('furnizor');

        ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Foto SRL',
            'cui' => (string) random_int(10000000, 99999999),
            'slug' => 'foto-srl-'.random_int(1000, 9999),
            'county_id' => $this->county->id,
            'locality_id' => $this->locality->id,
            'status' => 'active',
            ...$overrides,
        ]);

        return $user->fresh();
    }

    // ---- ANAF verified badge -------------------------------------------

    public function test_registering_as_a_provider_marks_the_cui_as_anaf_verified(): void
    {
        Http::fake([
            '*' => Http::response([
                'found' => [[
                    'date_generale' => ['denumire' => 'Foto SRL', 'nrRegCom' => 'J12/34/2020', 'stare_inregistrare' => 'INREGISTRAT'],
                    'adresa_sediu_social' => ['sdenumire_Judet' => $this->county->name, 'sdenumire_Localitate' => $this->locality->name],
                ]],
            ]),
        ]);

        $this->post(route('register'), [
            'name' => 'Ana Pop',
            'email' => 'ana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cui' => '12345678',
            'company_name' => 'Foto SRL',
            'county_id' => $this->county->id,
            'locality_id' => $this->locality->id,
            'terms' => true,
        ]);

        $profile = ProviderProfile::where('cui', '12345678')->firstOrFail();
        $this->assertNotNull($profile->anaf_verified_at);
        $this->assertSame('INREGISTRAT', $profile->anaf_status);
        $this->assertTrue($profile->isAnafVerified());
    }

    public function test_admin_verifying_a_cui_persists_it(): void
    {
        Http::fake([
            '*' => Http::response([
                'found' => [[
                    'date_generale' => ['denumire' => 'Foto SRL', 'nrRegCom' => 'J12/34/2020', 'stare_inregistrare' => 'INREGISTRAT'],
                    'adresa_sediu_social' => [],
                ]],
            ]),
        ]);

        $provider = $this->makeProvider();
        $admin = User::factory()->create(['status' => true]);
        $admin->assignRole('admin');

        $this->assertFalse($provider->providerProfile->isAnafVerified());

        $this->actingAs($admin)
            ->post(route('administration.providers.anaf-lookup', $provider->providerProfile))
            ->assertOk();

        $this->assertTrue($provider->providerProfile->fresh()->isAnafVerified());
    }

    public function test_verified_badge_only_shows_when_anaf_verified(): void
    {
        $unverified = $this->makeProvider(['slug' => 'unverified-srl']);
        $verified = $this->makeProvider(['slug' => 'verified-srl', 'anaf_verified_at' => now(), 'anaf_status' => 'INREGISTRAT']);

        $this->get(route('providers.show', $unverified->providerProfile->slug))
            ->assertInertia(fn (Assert $page) => $page->where('provider.is_verified', false));

        $this->get(route('providers.show', $verified->providerProfile->slug))
            ->assertInertia(fn (Assert $page) => $page->where('provider.is_verified', true));
    }

    // ---- Response time label -------------------------------------------

    public function test_response_time_label_appears_after_enough_replied_conversations(): void
    {
        $provider = $this->makeProvider();
        $listing = Listing::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'category_id' => $this->category->id,
            'title' => 'Pachet foto',
            'slug' => 'pachet-foto-'.random_int(1000, 9999),
            'status' => 'published',
        ]);

        for ($i = 0; $i < 3; $i++) {
            $client = User::factory()->create(['status' => true]);
            $client->assignRole('client');

            $conversation = Conversation::create([
                'listing_id' => $listing->id,
                'client_id' => $client->id,
                'provider_profile_id' => $provider->providerProfile->id,
                'last_message_at' => now(),
            ]);

            $conversation->messages()->create(['sender_id' => $client->id, 'body' => 'Bună!'])
                ->forceFill(['created_at' => now()->subMinutes(30)])->save();
            $conversation->messages()->create(['sender_id' => $provider->id, 'body' => 'Salut!'])
                ->forceFill(['created_at' => now()->subMinutes(20)])->save();
        }

        $this->assertSame('Răspunde de obicei în mai puțin de o oră', $provider->providerProfile->fresh()->responseTimeLabel());
    }

    public function test_response_time_label_is_null_with_too_little_history(): void
    {
        $provider = $this->makeProvider();

        $this->assertNull($provider->providerProfile->responseTimeLabel());
    }

    public function test_response_time_ignores_conversations_the_provider_started(): void
    {
        $provider = $this->makeProvider();
        $listing = Listing::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'category_id' => $this->category->id,
            'title' => 'Pachet foto',
            'slug' => 'pachet-foto-'.random_int(1000, 9999),
            'status' => 'published',
        ]);

        for ($i = 0; $i < 3; $i++) {
            $client = User::factory()->create(['status' => true]);
            $client->assignRole('client');

            $conversation = Conversation::create([
                'listing_id' => $listing->id,
                'client_id' => $client->id,
                'provider_profile_id' => $provider->providerProfile->id,
                'last_message_at' => now(),
            ]);

            // Provider messaged first (e.g. cold outreach from a lead) — no client-initiated reply time.
            $conversation->messages()->create(['sender_id' => $provider->id, 'body' => 'Salut!', 'created_at' => now()->subMinutes(30)]);
        }

        $this->assertNull($provider->providerProfile->fresh()->responseTimeLabel());
    }
}
