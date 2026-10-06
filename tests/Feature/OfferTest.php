<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\Offer;
use App\Models\ProviderProfile;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Notifications\OfferAccepted;
use App\Notifications\OfferDeclined;
use App\Notifications\OfferReceived;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OfferTest extends TestCase
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

    private function makeLead(User $client, string $status = 'open', array $overrides = []): QuoteRequest
    {
        return QuoteRequest::create([
            'category_id' => $this->category->id,
            'user_id' => $client->id,
            'title' => 'Foto nuntă Cluj',
            'name' => $client->name,
            'email' => $client->email,
            'message' => 'Caut fotograf pentru nuntă.',
            'status' => $status,
            ...$overrides,
        ]);
    }

    public function test_provider_sends_an_offer_and_the_client_is_notified(): void
    {
        Notification::fake();
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $client = $this->makeClient();
        $lead = $this->makeLead($client);

        $this->actingAs($provider)
            ->post(route('provider.leads.offer.store', $lead), [
                'listing_id' => $listing->id,
                'price' => 3500,
                'valid_until' => now()->addDays(5)->toDateString(),
                'includes' => ['8 ore filmare', 'Album foto'],
                'message' => 'Vă mulțumim pentru interes!',
            ])
            ->assertRedirect();

        $offer = Offer::first();
        $this->assertSame(3500, $offer->price);
        $this->assertSame(Offer::SENT, $offer->status);
        $this->assertSame($provider->providerProfile->id, $offer->provider_profile_id);
        $this->assertSame(['8 ore filmare', 'Album foto'], $offer->includes);

        Notification::assertSentTo($client, OfferReceived::class);
    }

    public function test_provider_can_only_offer_from_their_own_published_listing_in_the_right_category(): void
    {
        $provider = $this->makeProvider();
        $client = $this->makeClient();
        $lead = $this->makeLead($client);

        $other = $this->makeProvider('Alt SRL', 'alt-srl');
        $otherListing = $this->makeListing($other);

        $draft = Listing::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'category_id' => $this->category->id,
            'title' => 'Draft',
            'slug' => 'draft-'.random_int(1000, 9999),
            'status' => 'draft',
        ]);

        foreach ([$otherListing->id, $draft->id, 999999] as $listingId) {
            $this->actingAs($provider)
                ->post(route('provider.leads.offer.store', $lead), [
                    'listing_id' => $listingId,
                    'price' => 1000,
                    'valid_until' => now()->addDays(3)->toDateString(),
                ])
                ->assertSessionHasErrors('listing_id');
        }

        $this->assertSame(0, Offer::count());
    }

    public function test_price_must_be_positive_and_valid_until_required(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $lead = $this->makeLead($this->makeClient());

        $this->actingAs($provider)
            ->post(route('provider.leads.offer.store', $lead), [
                'listing_id' => $listing->id,
                'price' => 0,
                'valid_until' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['price', 'valid_until']);
    }

    public function test_offer_cannot_outlive_the_event_date(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $lead = $this->makeLead($this->makeClient(), overrides: ['event_date' => now()->addDays(5)->toDateString()]);

        $this->actingAs($provider)
            ->post(route('provider.leads.offer.store', $lead), [
                'listing_id' => $listing->id,
                'price' => 1000,
                'valid_until' => now()->addDays(10)->toDateString(),
            ])
            ->assertSessionHasErrors('valid_until');
    }

    public function test_sending_again_edits_the_same_offer_and_resets_it_to_sent(): void
    {
        Notification::fake();
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $client = $this->makeClient();
        $lead = $this->makeLead($client);

        $this->actingAs($provider)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listing->id, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);

        $offer = Offer::first();
        $offer->update(['status' => Offer::VIEWED, 'viewed_at' => now()]);

        $this->actingAs($provider)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listing->id, 'price' => 1200, 'valid_until' => now()->addDays(6)->toDateString(),
        ]);

        $this->assertSame(1, Offer::count());
        $offer->refresh();
        $this->assertSame(1200, $offer->price);
        $this->assertSame(Offer::SENT, $offer->status);
        $this->assertNull($offer->viewed_at);

        Notification::assertSentToTimes($client, OfferReceived::class, 2);
    }

    public function test_cannot_offer_on_a_request_that_is_not_open(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $lead = $this->makeLead($this->makeClient(), status: 'pending_review');

        $this->actingAs($provider)
            ->post(route('provider.leads.offer.store', $lead), [
                'listing_id' => $listing->id, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString(),
            ])
            ->assertNotFound();

        $this->assertSame(0, Offer::count());
    }

    public function test_provider_withdraws_an_offer(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $lead = $this->makeLead($this->makeClient());

        $this->actingAs($provider)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listing->id, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);

        $offer = Offer::first();

        $this->actingAs($provider)->delete(route('provider.offers.destroy', $offer))->assertRedirect();

        $this->assertSame(Offer::WITHDRAWN, $offer->fresh()->status);
    }

    public function test_provider_cannot_withdraw_someone_elses_offer(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $lead = $this->makeLead($this->makeClient());
        $this->actingAs($provider)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listing->id, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);
        $offer = Offer::first();

        $other = $this->makeProvider('Alt SRL', 'alt-srl-2');

        $this->actingAs($other)->delete(route('provider.offers.destroy', $offer))->assertNotFound();
        $this->assertSame(Offer::SENT, $offer->fresh()->status);
    }

    public function test_client_accepts_an_offer_and_others_are_auto_declined(): void
    {
        Notification::fake();
        $client = $this->makeClient();
        $lead = $this->makeLead($client);

        $providerA = $this->makeProvider('A SRL', 'a-srl');
        $providerB = $this->makeProvider('B SRL', 'b-srl');
        $listingA = $this->makeListing($providerA, 'a');
        $listingB = $this->makeListing($providerB, 'b');

        $this->actingAs($providerA)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listingA->id, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);
        $this->actingAs($providerB)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listingB->id, 'price' => 1500, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);

        $offerA = Offer::where('provider_profile_id', $providerA->providerProfile->id)->first();
        $offerB = Offer::where('provider_profile_id', $providerB->providerProfile->id)->first();

        $this->actingAs($client)->post(route('offers.accept', $offerA))->assertRedirect();

        $this->assertSame(Offer::ACCEPTED, $offerA->fresh()->status);
        $this->assertSame(Offer::DECLINED, $offerB->fresh()->status);
        $this->assertSame('closed', $lead->fresh()->status);

        Notification::assertSentTo($providerA, OfferAccepted::class);
        Notification::assertSentTo($providerB, OfferDeclined::class);
    }

    public function test_client_declines_an_offer_with_a_reason(): void
    {
        Notification::fake();
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $client = $this->makeClient();
        $lead = $this->makeLead($client);

        $this->actingAs($provider)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listing->id, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);
        $offer = Offer::first();

        $this->actingAs($client)
            ->post(route('offers.decline', $offer), ['reason' => 'Prea scump'])
            ->assertRedirect();

        $offer->refresh();
        $this->assertSame(Offer::DECLINED, $offer->status);
        $this->assertSame('Prea scump', $offer->decline_reason);
        $this->assertSame('open', $lead->fresh()->status);

        Notification::assertSentTo($provider, OfferDeclined::class);
    }

    public function test_client_cannot_accept_someone_elses_offer(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $lead = $this->makeLead($this->makeClient());
        $this->actingAs($provider)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listing->id, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);
        $offer = Offer::first();

        $stranger = $this->makeClient();

        $this->actingAs($stranger)->post(route('offers.accept', $offer))->assertForbidden();
        $this->assertSame(Offer::SENT, $offer->fresh()->status);
    }

    public function test_cannot_accept_an_already_answered_offer(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $client = $this->makeClient();
        $lead = $this->makeLead($client);
        $this->actingAs($provider)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listing->id, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);
        $offer = Offer::first();
        $offer->update(['status' => Offer::DECLINED]);

        $this->actingAs($client)->post(route('offers.accept', $offer))->assertForbidden();
    }

    public function test_expired_offer_cannot_be_accepted(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $client = $this->makeClient();
        $lead = $this->makeLead($client);
        $offer = Offer::create([
            'quote_request_id' => $lead->id,
            'provider_profile_id' => $provider->providerProfile->id,
            'listing_id' => $listing->id,
            'price' => 1000,
            'valid_until' => now()->subDay(),
            'status' => Offer::SENT,
        ]);

        $this->assertTrue($offer->isExpired());

        $this->actingAs($client)->post(route('offers.accept', $offer))->assertForbidden();
    }

    public function test_viewing_the_request_marks_offers_as_viewed(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $client = $this->makeClient();
        $lead = $this->makeLead($client);
        $this->actingAs($provider)->post(route('provider.leads.offer.store', $lead), [
            'listing_id' => $listing->id, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);
        $offer = Offer::first();
        $this->assertSame(Offer::SENT, $offer->status);

        $this->actingAs($client)->get(route('quote-requests.show', $lead))
            ->assertInertia(fn (Assert $page) => $page
                ->where('quoteRequest.offers_count', 1)
                ->has('offers', 1)
                ->where('offers.0.status', 'viewed'));

        $this->assertSame(Offer::VIEWED, $offer->fresh()->status);
        $this->assertNotNull($offer->fresh()->viewed_at);
    }

    public function test_provider_offers_index_lists_and_filters(): void
    {
        $provider = $this->makeProvider();
        $listingA = $this->makeListing($provider, 'a');
        $listingB = $this->makeListing($provider, 'b');
        $client = $this->makeClient();

        $leadA = $this->makeLead($client);
        $leadB = $this->makeLead($this->makeClient());

        $this->actingAs($provider)->post(route('provider.leads.offer.store', $leadA), [
            'listing_id' => $listingA->id, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);
        $this->actingAs($provider)->post(route('provider.leads.offer.store', $leadB), [
            'listing_id' => $listingB->id, 'price' => 2000, 'valid_until' => now()->addDays(5)->toDateString(),
        ]);

        $offerA = Offer::where('quote_request_id', $leadA->id)->first();
        $this->actingAs($client)->post(route('offers.accept', $offerA));

        $this->actingAs($provider)->get(route('provider.offers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Provider/Offers/Index')
                ->where('counts.all', 2)
                ->where('counts.accepted', 1)
                ->where('counts.awaiting', 1));

        $this->actingAs($provider)->get(route('provider.offers.index', ['filter' => 'accepted']))
            ->assertInertia(fn (Assert $page) => $page->has('offers', 1)->where('offers.0.client.name', $client->name));
    }

    public function test_non_providers_cannot_send_offers(): void
    {
        $client = $this->makeClient();
        $lead = $this->makeLead($this->makeClient());

        $this->actingAs($client)
            ->post(route('provider.leads.offer.store', $lead), ['listing_id' => 1, 'price' => 1000, 'valid_until' => now()->addDays(5)->toDateString()])
            ->assertForbidden();
    }
}
