<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\SavedSearchMatches;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Category $dj;

    private County $county;

    private Locality $locality;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $this->client = User::factory()->create(['status' => true]);
        $this->client->assignRole('client');

        $this->dj = Category::create(['slug' => 'dj', 'name' => 'DJ']);
        $this->county = County::create(['name' => 'Cluj']);
        $this->locality = Locality::create(['county_id' => $this->county->id, 'name' => 'Cluj-Napoca']);
    }

    private function makeProvider(): User
    {
        $user = User::factory()->create(['status' => true]);
        $user->assignRole('furnizor');
        ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => 'DJ Party SRL',
            'cui' => (string) random_int(10000000, 99999999),
            'slug' => 'dj-party-'.random_int(1000, 9999),
            'county_id' => $this->county->id,
            'locality_id' => $this->locality->id,
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    private function makeListing(User $provider, array $overrides = []): Listing
    {
        return Listing::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'category_id' => $this->dj->id,
            'county_id' => $this->county->id,
            'title' => 'DJ pentru petreceri',
            'slug' => 'dj-petreceri-'.random_int(1000, 9999),
            'status' => 'published',
            'price_type' => 'starting_from',
            'price_from' => 1000,
            'published_at' => now(),
            ...$overrides,
        ]);
    }

    // ---- Save / list / delete -------------------------------------------

    public function test_client_saves_a_search(): void
    {
        $this->actingAs($this->client)
            ->post(route('saved-searches.store'), [
                'name' => 'DJ în Cluj sub 1500 lei',
                'categories' => ['dj'],
                'county_ids' => [$this->county->id],
                'price_max' => 1500,
            ])
            ->assertRedirect();

        $search = SavedSearch::firstOrFail();
        $this->assertSame($this->client->id, $search->user_id);
        $this->assertSame('DJ în Cluj sub 1500 lei', $search->name);
        $this->assertSame(['dj'], $search->filters['categories']);
        $this->assertSame(1500.0, (float) $search->filters['price_max']);
    }

    public function test_saved_search_list_shows_a_readable_summary(): void
    {
        $this->actingAs($this->client)->post(route('saved-searches.store'), [
            'name' => 'DJ ieftin',
            'categories' => ['dj'],
            'price_max' => 1500,
        ]);

        $this->actingAs($this->client)->get(route('saved-searches.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SavedSearches/Index')
                ->has('savedSearches', 1)
                ->where('savedSearches.0.summary', 'dj · sub 1.500 lei'));
    }

    public function test_limit_of_saved_searches_per_user(): void
    {
        SavedSearch::factory(15)->for($this->client)->create();

        $this->actingAs($this->client)
            ->post(route('saved-searches.store'), ['name' => 'Una în plus'])
            ->assertSessionHas('error');

        $this->assertSame(15, SavedSearch::count());
    }

    public function test_client_deletes_their_own_saved_search(): void
    {
        $search = SavedSearch::factory()->for($this->client)->create();

        $this->actingAs($this->client)
            ->delete(route('saved-searches.destroy', $search))
            ->assertRedirect();

        $this->assertSame(0, SavedSearch::count());
    }

    public function test_client_cannot_delete_someone_elses_saved_search(): void
    {
        $other = User::factory()->create(['status' => true]);
        $other->assignRole('client');
        $search = SavedSearch::factory()->for($other)->create();

        $this->actingAs($this->client)
            ->delete(route('saved-searches.destroy', $search))
            ->assertForbidden();

        $this->assertSame(1, SavedSearch::count());
    }

    public function test_guests_cannot_save_a_search(): void
    {
        $this->post(route('saved-searches.store'), ['name' => 'Test'])->assertRedirect(route('login'));
    }

    // ---- Digest notification --------------------------------------------

    public function test_notify_command_emails_new_matching_listings(): void
    {
        Notification::fake();
        $provider = $this->makeProvider();

        $search = SavedSearch::create([
            'user_id' => $this->client->id,
            'name' => 'DJ în Cluj',
            'filters' => ['categories' => ['dj'], 'county_ids' => [$this->county->id]],
            'last_notified_at' => now()->subDay(),
        ]);

        $newListing = $this->makeListing($provider, ['published_at' => now()]);
        $this->makeListing($provider, ['published_at' => now()->subDays(3)]); // too old, shouldn't match

        $this->artisan('saved-searches:notify')->assertSuccessful();

        Notification::assertSentTo(
            $this->client,
            SavedSearchMatches::class,
            fn ($notification, $channels, $notifiable) => true
        );

        $this->assertNotNull($search->fresh()->last_notified_at);
        $this->assertTrue($search->fresh()->last_notified_at->gt(now()->subMinute()));
    }

    public function test_notify_command_skips_searches_with_no_new_matches(): void
    {
        Notification::fake();
        $provider = $this->makeProvider();
        $this->makeListing($provider, ['published_at' => now()->subDays(5)]);

        $search = SavedSearch::create([
            'user_id' => $this->client->id,
            'name' => 'DJ în Cluj',
            'filters' => ['categories' => ['dj']],
            'last_notified_at' => now()->subDay(),
        ]);

        $this->artisan('saved-searches:notify');

        Notification::assertNothingSent();
        $this->assertTrue($search->fresh()->last_notified_at->lt(now()->subHour()));
    }

    public function test_notify_command_respects_the_search_filters(): void
    {
        Notification::fake();
        $provider = $this->makeProvider();

        $otherCategory = Category::create(['slug' => 'foto', 'name' => 'Fotografie']);
        Listing::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'category_id' => $otherCategory->id,
            'title' => 'Foto nuntă',
            'slug' => 'foto-nunta-'.random_int(1000, 9999),
            'status' => 'published',
            'published_at' => now(),
        ]);

        SavedSearch::create([
            'user_id' => $this->client->id,
            'name' => 'Doar DJ',
            'filters' => ['categories' => ['dj']],
            'last_notified_at' => now()->subDay(),
        ]);

        $this->artisan('saved-searches:notify');

        Notification::assertNothingSent();
    }
}
