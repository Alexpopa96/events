<?php

namespace Tests\Feature;

use App\Models\AvailabilityBlock;
use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\Offer;
use App\Models\ProviderProfile;
use App\Models\QuoteRequest;
use App\Models\User;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private County $county;

    private Locality $locality;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $this->category = Category::create(['slug' => 'fotografie', 'name' => 'Fotografie']);
        $this->county = County::create(['name' => 'Cluj']);
        $this->locality = Locality::create(['county_id' => $this->county->id, 'name' => 'Cluj-Napoca']);
    }

    private function makeProvider(string $company = 'Foto SRL', string $slug = 'foto-srl'): User
    {
        $user = User::factory()->create(['status' => true]);
        $user->assignRole('furnizor');

        ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => $company,
            'cui' => (string) random_int(10000000, 99999999),
            'slug' => $slug,
            'county_id' => $this->county->id,
            'locality_id' => $this->locality->id,
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    private function makeListing(User $provider, string $slug = 'pachet-foto'): Listing
    {
        return Listing::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'category_id' => $this->category->id,
            'title' => 'Pachet foto nuntă',
            'slug' => $slug.'-'.random_int(1000, 9999),
            'status' => 'published',
        ]);
    }

    private function makeClient(): User
    {
        $user = User::factory()->create(['status' => true]);
        $user->assignRole('client');

        return $user;
    }

    public function test_provider_blocks_days_by_hand(): void
    {
        $provider = $this->makeProvider();

        $this->actingAs($provider)
            ->post(route('provider.availability.store'), [
                'dates' => [now()->addDays(3)->toDateString(), now()->addDays(4)->toDateString()],
                'note' => 'Nuntă privată',
            ])
            ->assertRedirect();

        $this->assertSame(2, AvailabilityBlock::count());
        $this->assertSame('Nuntă privată', AvailabilityBlock::first()->note);
        $this->assertSame(AvailabilityBlock::MANUAL, AvailabilityBlock::first()->source);
    }

    public function test_blocking_the_same_day_twice_is_a_no_op(): void
    {
        $provider = $this->makeProvider();
        $date = now()->addDays(3)->toDateString();

        $this->actingAs($provider)->post(route('provider.availability.store'), ['dates' => [$date]]);
        $this->actingAs($provider)->post(route('provider.availability.store'), ['dates' => [$date]]);

        $this->assertSame(1, AvailabilityBlock::count());
    }

    public function test_past_dates_cannot_be_blocked(): void
    {
        $provider = $this->makeProvider();

        $this->actingAs($provider)
            ->post(route('provider.availability.store'), ['dates' => [now()->subDay()->toDateString()]])
            ->assertSessionHasErrors('dates.0');

        $this->assertSame(0, AvailabilityBlock::count());
    }

    public function test_provider_unblocks_a_day(): void
    {
        $provider = $this->makeProvider();
        $block = AvailabilityBlock::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'date' => now()->addDays(3)->toDateString(),
            'source' => AvailabilityBlock::MANUAL,
        ]);

        $this->actingAs($provider)->delete(route('provider.availability.destroy', $block))->assertRedirect();

        $this->assertSame(0, AvailabilityBlock::count());
    }

    public function test_provider_cannot_unblock_someone_elses_day(): void
    {
        $provider = $this->makeProvider();
        $other = $this->makeProvider('Alt SRL', 'alt-srl');
        $block = AvailabilityBlock::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'date' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($other)->delete(route('provider.availability.destroy', $block))->assertNotFound();
        $this->assertSame(1, AvailabilityBlock::count());
    }

    public function test_calendar_page_lists_blocks_with_lead_titles(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $client = $this->makeClient();
        $eventDate = now()->addDays(10)->toDateString();

        $lead = QuoteRequest::create([
            'category_id' => $this->category->id,
            'user_id' => $client->id,
            'title' => 'Nuntă Ana & Vlad',
            'name' => $client->name,
            'email' => $client->email,
            'message' => 'Caut fotograf.',
            'status' => 'open',
            'event_date' => $eventDate,
        ]);

        $this->actingAs($provider)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listing->id,
            'price' => 1000,
            'valid_until' => $eventDate,
        ]);
        $offer = Offer::first();
        $this->actingAs($client)->post(route('offers.accept', $offer));

        $this->assertSame(1, AvailabilityBlock::count());
        $block = AvailabilityBlock::first();
        $this->assertSame(AvailabilityBlock::OFFER, $block->source);
        $this->assertSame($eventDate, $block->date->toDateString());

        $this->actingAs($provider)->get(route('provider.availability.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Provider/Availability/Index')
                ->has('blocks', 1)
                ->where('blocks.0.source', 'offer')
                ->where('blocks.0.lead_title', 'Nuntă Ana & Vlad'));
    }

    public function test_accepting_an_offer_does_not_override_an_existing_manual_block(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $client = $this->makeClient();
        $eventDate = now()->addDays(10)->toDateString();

        AvailabilityBlock::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'date' => $eventDate,
            'source' => AvailabilityBlock::MANUAL,
            'note' => 'Ziua mea',
        ]);

        $lead = QuoteRequest::create([
            'category_id' => $this->category->id,
            'user_id' => $client->id,
            'title' => 'Nuntă',
            'name' => $client->name,
            'email' => $client->email,
            'message' => 'Caut fotograf.',
            'status' => 'open',
            'event_date' => $eventDate,
        ]);

        $this->actingAs($provider)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listing->id, 'price' => 1000, 'valid_until' => $eventDate,
        ]);
        $offer = Offer::first();
        $this->actingAs($client)->post(route('offers.accept', $offer));

        $this->assertSame(1, AvailabilityBlock::count());
        $this->assertSame('Ziua mea', AvailabilityBlock::first()->note);
        $this->assertSame(AvailabilityBlock::MANUAL, AvailabilityBlock::first()->source);
    }

    public function test_non_providers_cannot_access_the_calendar(): void
    {
        $client = $this->makeClient();

        $this->actingAs($client)->get(route('provider.availability.index'))->assertForbidden();
        $this->actingAs($client)->post(route('provider.availability.store'), ['dates' => [now()->addDay()->toDateString()]])->assertForbidden();
    }

    public function test_listings_index_filters_out_providers_busy_on_the_chosen_date(): void
    {
        $freeProvider = $this->makeProvider('Liber SRL', 'liber-srl');
        $busyProvider = $this->makeProvider('Ocupat SRL', 'ocupat-srl');
        $this->makeListing($freeProvider, 'liber');
        $this->makeListing($busyProvider, 'ocupat');

        $date = now()->addDays(20)->toDateString();
        AvailabilityBlock::create(['provider_profile_id' => $busyProvider->providerProfile->id, 'date' => $date]);

        $response = $this->get(route('listings.index', ['available_on' => $date]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('listings.data', 1)
            ->where('listings.data.0.provider.company_name', 'Liber SRL'));
    }

    public function test_listing_show_exposes_provider_unavailable_dates(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $date = now()->addDays(15)->toDateString();
        AvailabilityBlock::create(['provider_profile_id' => $provider->providerProfile->id, 'date' => $date]);

        $this->get(route('listings.show', $listing->slug))
            ->assertInertia(fn (Assert $page) => $page->where('unavailableDates', [$date]));
    }
}
