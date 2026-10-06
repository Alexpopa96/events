<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\Offer;
use App\Models\ProviderProfile;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewReceived;
use App\Notifications\OfferAccepted;
use App\Notifications\OfferReceived;
use App\Notifications\ReviewReplied;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $this->client = User::factory()->create(['status' => true]);
        $this->client->assignRole('client');
    }

    // ---- Subscription endpoints ------------------------------------------

    public function test_user_subscribes_to_push_notifications(): void
    {
        $this->actingAs($this->client)
            ->postJson(route('push-subscriptions.store'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
                'keys' => ['p256dh' => 'public-key-value', 'auth' => 'auth-token-value'],
            ])
            ->assertOk();

        $this->assertSame(1, $this->client->pushSubscriptions()->count());
        $subscription = $this->client->pushSubscriptions()->first();
        $this->assertSame('https://fcm.googleapis.com/fcm/send/abc123', $subscription->endpoint);
    }

    public function test_resubscribing_with_the_same_endpoint_updates_instead_of_duplicating(): void
    {
        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'key-one', 'auth' => 'auth-one'],
        ];
        $this->actingAs($this->client)->postJson(route('push-subscriptions.store'), $payload);

        $payload['keys']['p256dh'] = 'key-two';
        $this->actingAs($this->client)->postJson(route('push-subscriptions.store'), $payload)->assertOk();

        $this->assertSame(1, $this->client->pushSubscriptions()->count());
        $this->assertSame('key-two', $this->client->pushSubscriptions()->first()->public_key);
    }

    public function test_user_unsubscribes(): void
    {
        $this->client->updatePushSubscription('https://fcm.googleapis.com/fcm/send/abc123', 'key', 'auth');

        $this->actingAs($this->client)
            ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123'])
            ->assertOk();

        $this->assertSame(0, $this->client->pushSubscriptions()->count());
    }

    public function test_guests_cannot_manage_push_subscriptions(): void
    {
        $this->postJson(route('push-subscriptions.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'key', 'auth' => 'auth'],
        ])->assertUnauthorized();
    }

    // ---- Notifications go out over the webpush channel too ---------------

    public function test_review_reply_notification_includes_the_webpush_channel(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $review = Review::create([
            'listing_id' => $listing->id,
            'provider_profile_id' => $provider->providerProfile->id,
            'user_id' => $this->client->id,
            'rating' => 5,
            'status' => 'approved',
        ]);

        $notification = new ReviewReplied($review->load(['listing', 'providerProfile']));

        $this->assertContains(WebPushChannel::class, $notification->via($this->client));

        $message = $notification->toWebPush($this->client);
        $this->assertNotEmpty($message->toArray()['title']);
        $this->assertArrayHasKey('url', $message->toArray()['data']);
    }

    public function test_offer_received_webpush_payload_mentions_the_provider_and_amount(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $lead = QuoteRequest::create([
            'category_id' => $listing->category_id,
            'user_id' => $this->client->id,
            'title' => 'Nuntă',
            'name' => $this->client->name,
            'email' => $this->client->email,
            'message' => 'Test',
            'status' => 'open',
        ]);
        $offer = Offer::create([
            'quote_request_id' => $lead->id,
            'provider_profile_id' => $provider->providerProfile->id,
            'listing_id' => $listing->id,
            'price' => 2500,
            'valid_until' => now()->addDays(5),
            'status' => Offer::SENT,
        ]);

        $notification = new OfferReceived($offer->load(['quoteRequest', 'providerProfile']));
        $this->assertContains(WebPushChannel::class, $notification->via($this->client));

        $payload = $notification->toWebPush($this->client)->toArray();
        $this->assertStringContainsString('2.500', $payload['body']);
        $this->assertStringContainsString($provider->providerProfile->company_name, $payload['body']);
    }

    public function test_offer_accepted_notification_includes_webpush(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $lead = QuoteRequest::create([
            'category_id' => $listing->category_id,
            'user_id' => $this->client->id,
            'title' => 'Nuntă',
            'name' => $this->client->name,
            'email' => $this->client->email,
            'message' => 'Test',
            'status' => 'closed',
        ]);
        $offer = Offer::create([
            'quote_request_id' => $lead->id,
            'provider_profile_id' => $provider->providerProfile->id,
            'listing_id' => $listing->id,
            'price' => 2500,
            'valid_until' => now()->addDays(5),
            'status' => Offer::ACCEPTED,
        ]);

        $notification = new OfferAccepted($offer->load('quoteRequest'));
        $this->assertContains(WebPushChannel::class, $notification->via($provider));
    }

    public function test_new_review_received_notification_includes_webpush(): void
    {
        $provider = $this->makeProvider();
        $listing = $this->makeListing($provider);
        $review = Review::create([
            'listing_id' => $listing->id,
            'provider_profile_id' => $provider->providerProfile->id,
            'user_id' => $this->client->id,
            'rating' => 4,
            'status' => 'approved',
        ]);

        $notification = new NewReviewReceived($review->load(['user', 'listing']));
        $this->assertContains(WebPushChannel::class, $notification->via($provider));
    }

    private function makeProvider(): User
    {
        $county = County::firstOrCreate(['name' => 'Cluj']);
        $locality = Locality::firstOrCreate(['county_id' => $county->id, 'name' => 'Cluj-Napoca']);

        $user = User::factory()->create(['status' => true]);
        $user->assignRole('furnizor');
        ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Foto SRL',
            'cui' => (string) random_int(10000000, 99999999),
            'slug' => 'foto-srl-'.random_int(1000, 9999),
            'county_id' => $county->id,
            'locality_id' => $locality->id,
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    private function makeListing(User $provider): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'fotografie'], ['name' => 'Fotografie']);

        return Listing::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'category_id' => $category->id,
            'title' => 'Pachet foto',
            'slug' => 'pachet-foto-'.random_int(1000, 9999),
            'status' => 'published',
        ]);
    }
}
