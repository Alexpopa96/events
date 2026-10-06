<?php

namespace Tests\Feature;

use App\Events\ConversationRead;
use App\Events\MessageSent;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RealtimeMessagingTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;

    private User $client;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $county = County::create(['name' => 'Cluj']);
        $locality = Locality::create(['county_id' => $county->id, 'name' => 'Cluj-Napoca']);
        $category = Category::create(['slug' => 'fotografie', 'name' => 'Fotografie']);

        $this->provider = User::factory()->create(['status' => true]);
        $this->provider->assignRole('furnizor');
        ProviderProfile::create([
            'user_id' => $this->provider->id,
            'company_name' => 'Foto SRL',
            'cui' => '12345678',
            'slug' => 'foto-srl',
            'county_id' => $county->id,
            'locality_id' => $locality->id,
            'status' => 'active',
        ]);

        $listing = Listing::create([
            'provider_profile_id' => $this->provider->providerProfile->id,
            'category_id' => $category->id,
            'title' => 'Pachet foto nuntă',
            'slug' => 'pachet-foto-nunta',
            'status' => 'published',
        ]);

        $this->client = User::factory()->create(['status' => true]);
        $this->client->assignRole('client');

        $this->conversation = Conversation::create([
            'listing_id' => $listing->id,
            'client_id' => $this->client->id,
            'provider_profile_id' => $this->provider->providerProfile->id,
        ]);
    }

    public function test_sending_a_message_broadcasts_it_on_the_conversation_and_both_personal_channels(): void
    {
        Bus::fake([BroadcastEvent::class]);

        $this->conversation->addMessage($this->client, 'Salut!', Conversation::SIDE_CLIENT);

        Bus::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $job) {
            $event = $job->event;

            if (! $event instanceof MessageSent) {
                return false;
            }

            $channelNames = collect($event->broadcastOn())->map->name;

            return $channelNames->contains('private-conversation.'.$this->conversation->id)
                && $channelNames->contains('private-App.Models.User.'.$this->client->id)
                && $channelNames->contains('private-App.Models.User.'.$this->provider->id);
        });
    }

    public function test_message_broadcast_payload_shape(): void
    {
        $message = $this->conversation->addMessage($this->client, 'Salut!', Conversation::SIDE_CLIENT);

        $event = new MessageSent($message->fresh()->load('conversation.providerProfile'));
        $payload = $event->broadcastWith();

        $this->assertSame($message->id, $payload['id']);
        $this->assertSame($this->conversation->id, $payload['conversation_id']);
        $this->assertSame($this->client->id, $payload['sender_id']);
        $this->assertSame('Salut!', $payload['body']);
        $this->assertArrayHasKey('time', $payload);
        $this->assertSame('message.sent', $event->broadcastAs());
    }

    public function test_marking_read_broadcasts_on_the_conversation_channel(): void
    {
        Bus::fake([BroadcastEvent::class]);

        $this->conversation->addMessage($this->client, 'Salut!', Conversation::SIDE_CLIENT);
        $this->conversation->markReadBy(Conversation::SIDE_PROVIDER);

        Bus::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $job) {
            $event = $job->event;

            return $event instanceof ConversationRead
                && $event->side === Conversation::SIDE_PROVIDER
                && collect($event->broadcastOn())->map->name->contains('private-conversation.'.$this->conversation->id);
        });
    }

    public function test_marking_read_with_no_messages_does_not_broadcast(): void
    {
        Bus::fake([BroadcastEvent::class]);

        $this->conversation->markReadBy(Conversation::SIDE_PROVIDER);

        Bus::assertNotDispatched(BroadcastEvent::class);
    }

    public function test_participants_are_authorized_on_the_conversation_channel(): void
    {
        $this->useRealBroadcasterForAuth();

        $this->actingAs($this->client)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-conversation.'.$this->conversation->id,
                'socket_id' => '1234.5678',
            ])
            ->assertOk();

        $this->actingAs($this->provider)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-conversation.'.$this->conversation->id,
                'socket_id' => '1234.5678',
            ])
            ->assertOk();
    }

    public function test_outsiders_cannot_authorize_on_the_conversation_channel(): void
    {
        $this->useRealBroadcasterForAuth();

        $stranger = User::factory()->create(['status' => true]);
        $stranger->assignRole('client');

        $this->actingAs($stranger)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-conversation.'.$this->conversation->id,
                'socket_id' => '1234.5678',
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_authorize_on_the_conversation_channel(): void
    {
        $this->useRealBroadcasterForAuth();

        $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-conversation.'.$this->conversation->id,
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    }

    /**
     * routes/channels.php only runs once, at boot, against whichever broadcaster was
     * the default at that moment (the 'log' driver, which rubber-stamps every auth
     * check). Re-requiring it after switching to the real driver re-registers the
     * channel closures against that driver, so these tests exercise the actual
     * authorization logic instead of 'log's always-allow behavior. /broadcasting/auth
     * itself never makes a network call either way â it's pure local HMAC signing.
     */
    private function useRealBroadcasterForAuth(): void
    {
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');
    }
}
