<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Support\Inbox;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;
    private User $client;
    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $this->provider = $this->makeProvider('Foto SRL', 'foto-srl');
        $this->client = User::factory()->create(['status' => true]);
        $this->client->assignRole('client');

        $this->listing = $this->makeListing($this->provider, 'published');
    }

    private function makeProvider(string $company, string $slug): User
    {
        $county = County::firstOrCreate(['name' => 'Cluj']);
        $locality = Locality::firstOrCreate(['county_id' => $county->id, 'name' => 'Cluj-Napoca']);

        $user = User::factory()->create(['status' => true]);
        $user->assignRole('furnizor');

        ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => $company,
            'cui' => (string) random_int(10000000, 99999999),
            'slug' => $slug,
            'county_id' => $county->id,
            'locality_id' => $locality->id,
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    private function makeListing(User $provider, string $status): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'fotografie'], ['name' => 'Fotografie']);

        return Listing::create([
            'provider_profile_id' => $provider->providerProfile->id,
            'category_id' => $category->id,
            'title' => 'Pachet foto nuntă',
            'slug' => 'pachet-foto-nunta-'.random_int(1000, 9999),
            'status' => $status,
        ]);
    }

    public function test_client_starts_a_conversation_from_a_listing(): void
    {
        $this->actingAs($this->client)
            ->post(route('listings.messages.start', $this->listing->slug), ['body' => 'Sunteți liberi pe 12 iunie?'])
            ->assertRedirect();

        $conversation = Conversation::first();

        $this->assertSame($this->listing->id, $conversation->listing_id);
        $this->assertSame($this->client->id, $conversation->client_id);
        $this->assertSame($this->provider->providerProfile->id, $conversation->provider_profile_id);
        $this->assertSame('Sunteți liberi pe 12 iunie?', $conversation->messages->first()->body);
    }

    public function test_starting_twice_reuses_the_same_thread(): void
    {
        foreach (['Bună', 'Mai sunteți acolo?'] as $body) {
            $this->actingAs($this->client)->post(route('listings.messages.start', $this->listing->slug), ['body' => $body]);
        }

        $this->assertSame(1, Conversation::count());
        $this->assertSame(2, Conversation::first()->messages()->count());
    }

    public function test_provider_cannot_message_their_own_listing(): void
    {
        $this->actingAs($this->provider)
            ->post(route('listings.messages.start', $this->listing->slug), ['body' => 'Test'])
            ->assertForbidden();
    }

    public function test_cannot_message_an_unpublished_listing(): void
    {
        $draft = $this->makeListing($this->provider, 'draft');

        $this->actingAs($this->client)
            ->post(route('listings.messages.start', $draft->slug), ['body' => 'Test'])
            ->assertNotFound();
    }

    public function test_body_is_required_and_limited(): void
    {
        $this->actingAs($this->client)
            ->post(route('listings.messages.start', $this->listing->slug), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->actingAs($this->client)
            ->post(route('listings.messages.start', $this->listing->slug), ['body' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('body');
    }

    public function test_provider_sees_and_replies_and_unread_counts_flip(): void
    {
        $this->actingAs($this->client)->post(route('listings.messages.start', $this->listing->slug), ['body' => 'Bună ziua']);
        $conversation = Conversation::first();

        $this->assertSame(1, Inbox::unreadCount($this->provider));
        $this->assertSame(0, Inbox::unreadCount($this->client));

        $this->actingAs($this->provider)
            ->get(route('provider.messages.index', $conversation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Provider/Messages/Index')
                ->where('active.counterpart.name', $this->client->name)
                ->has('active.messages', 1)
                ->where('active.messages.0.mine', false));

        $this->assertSame(0, Inbox::unreadCount($this->provider));

        $this->actingAs($this->provider)
            ->post(route('provider.messages.store', $conversation), ['body' => 'Da, suntem liberi!'])
            ->assertRedirect();

        $this->assertSame(1, Inbox::unreadCount($this->client));
        $this->assertSame(0, Inbox::unreadCount($this->provider));

        $this->actingAs($this->client)
            ->get(route('messages.index', $conversation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Messages/Index')
                ->where('active.counterpart.name', 'Foto SRL')
                ->has('active.messages', 2)
                ->where('active.messages.1.mine', false));

        $this->assertSame(0, Inbox::unreadCount($this->client));
    }

    public function test_a_message_arriving_right_after_reading_is_still_unread(): void
    {
        $this->actingAs($this->client)->post(route('listings.messages.start', $this->listing->slug), ['body' => 'Prima']);
        $conversation = Conversation::first();

        $this->actingAs($this->provider)->get(route('provider.messages.index', $conversation));
        $this->assertSame(0, Inbox::unreadCount($this->provider));

        // Same second as the read marker: must still count.
        $this->actingAs($this->client)->post(route('messages.store', $conversation), ['body' => 'A doua']);

        $this->assertSame(1, Inbox::unreadCount($this->provider));
    }

    public function test_outsiders_cannot_read_or_write_a_thread(): void
    {
        $this->actingAs($this->client)->post(route('listings.messages.start', $this->listing->slug), ['body' => 'Bună']);
        $conversation = Conversation::first();

        $otherClient = User::factory()->create(['status' => true]);
        $otherClient->assignRole('client');
        $otherProvider = $this->makeProvider('Alt SRL', 'alt-srl');

        $this->actingAs($otherClient)->get(route('messages.index', $conversation))->assertNotFound();
        $this->actingAs($otherClient)->post(route('messages.store', $conversation), ['body' => 'x'])->assertNotFound();
        $this->actingAs($otherProvider)->get(route('provider.messages.index', $conversation))->assertNotFound();
        $this->actingAs($otherProvider)->post(route('provider.messages.store', $conversation), ['body' => 'x'])->assertNotFound();

        // A client cannot open the provider view of their own thread, and vice versa.
        $this->actingAs($this->client)->get(route('provider.messages.index', $conversation))->assertForbidden();
        $this->actingAs($this->provider)->get(route('messages.index', $conversation))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->post(route('listings.messages.start', $this->listing->slug), ['body' => 'x'])->assertRedirect(route('login'));
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }

    public function test_unread_total_is_shared_with_the_frontend(): void
    {
        $this->actingAs($this->client)->post(route('listings.messages.start', $this->listing->slug), ['body' => 'Bună']);

        $this->actingAs($this->provider)
            ->get(route('provider.messages.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('unreadMessages', 1)
                ->has('conversations', 1)
                ->where('conversations.0.unread', 1));
    }

    private function makeLead(?User $client, string $status = 'open'): \App\Models\QuoteRequest
    {
        return \App\Models\QuoteRequest::create([
            'category_id' => $this->listing->category_id,
            'user_id' => $client?->id,
            'name' => 'Ioana Client',
            'email' => 'ioana@example.test',
            'message' => 'Caut fotograf pentru nuntă.',
            'status' => $status,
        ]);
    }

    public function test_provider_can_message_a_client_first_from_a_lead(): void
    {
        $lead = $this->makeLead($this->client);

        $this->actingAs($this->provider)
            ->get(route('provider.leads.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('leads.0.message_listings.0.id', $this->listing->id));

        $this->actingAs($this->provider)
            ->post(route('provider.leads.message', $lead), ['listing_id' => $this->listing->id, 'body' => 'Bună! Sunt disponibil.'])
            ->assertRedirect();

        $conversation = Conversation::first();
        $this->assertSame($this->client->id, $conversation->client_id);
        $this->assertSame(1, Inbox::unreadCount($this->client));
        $this->assertSame(0, Inbox::unreadCount($this->provider));

        // The lead now shows as contacted.
        $this->assertTrue($this->provider->providerProfile->events()->where('quote_request_id', $lead->id)->exists());

        // The client sees it in their inbox and can answer.
        $this->actingAs($this->client)->get(route('messages.index', $conversation))
            ->assertInertia(fn (Assert $page) => $page->where('active.messages.0.mine', false)->where('active.counterpart.name', 'Foto SRL'));
    }

    public function test_provider_cannot_message_from_a_foreign_or_unpublished_listing(): void
    {
        $lead = $this->makeLead($this->client);
        $otherProvider = $this->makeProvider('Alt SRL', 'alt-srl');
        $foreign = $this->makeListing($otherProvider, 'published');
        $draft = $this->makeListing($this->provider, 'draft');

        foreach ([$foreign->id, $draft->id] as $listingId) {
            $this->actingAs($this->provider)
                ->post(route('provider.leads.message', $lead), ['listing_id' => $listingId, 'body' => 'x'])
                ->assertSessionHasErrors('listing_id');
        }

        $this->assertSame(0, Conversation::count());
    }

    public function test_leads_without_an_account_or_outside_the_category_cannot_be_messaged(): void
    {
        $guestLead = $this->makeLead(null);
        $closedLead = $this->makeLead($this->client, 'closed');

        foreach ([$guestLead, $closedLead] as $lead) {
            $this->actingAs($this->provider)
                ->post(route('provider.leads.message', $lead), ['listing_id' => $this->listing->id, 'body' => 'x'])
                ->assertNotFound();
        }

        $otherProvider = $this->makeProvider('Alt SRL', 'alt-srl');
        $otherCategory = Category::create(['name' => 'Muzică', 'slug' => 'muzica']);
        Listing::create([
            'provider_profile_id' => $otherProvider->providerProfile->id,
            'category_id' => $otherCategory->id,
            'title' => 'DJ',
            'slug' => 'dj-1',
            'status' => 'published',
        ]);

        $this->actingAs($otherProvider)
            ->post(route('provider.leads.message', $this->makeLead($this->client)), ['listing_id' => 2, 'body' => 'x'])
            ->assertNotFound();
    }

    public function test_lead_chat_tab_loads_the_thread_only_on_request_and_marks_it_read(): void
    {
        $lead = $this->makeLead($this->client);

        // Plain visit: nothing loaded.
        $this->actingAs($this->provider)->get(route('provider.leads.index'))
            ->assertInertia(fn (Assert $page) => $page->where('chat', null));

        // Requested, but no conversation yet.
        $this->actingAs($this->provider)->get(route('provider.leads.index', ['cerere' => $lead->id, 'tab' => 'chat']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('chat.lead_id', $lead->id)
                ->where('chat.conversation_id', null)
                ->has('chat.messages', 0));

        // Client writes on the listing; the provider opens the chat tab for their lead.
        $this->actingAs($this->client)->post(route('listings.messages.start', $this->listing->slug), ['body' => 'Bună!']);
        $this->assertSame(1, Inbox::unreadCount($this->provider));

        $this->actingAs($this->provider)->get(route('provider.leads.index', ['cerere' => $lead->id, 'tab' => 'chat']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('chat.conversation_id', Conversation::first()->id)
                ->where('chat.listing.id', $this->listing->id)
                ->has('chat.messages', 1)
                ->where('chat.messages.0.mine', false));

        $this->assertSame(0, Inbox::unreadCount($this->provider));

        // Someone else's lead can't be opened.
        $otherClient = User::factory()->create(['status' => true]);
        $otherClient->assignRole('client');
        $closed = $this->makeLead($otherClient, 'closed');

        $this->actingAs($this->provider)->get(route('provider.leads.index', ['cerere' => $closed->id, 'tab' => 'chat']))
            ->assertInertia(fn (Assert $page) => $page->where('chat', null));
    }

    public function test_leads_page_restores_selection_tab_and_filter_from_the_url(): void
    {
        $lead = $this->makeLead($this->client);

        $this->actingAs($this->provider)
            ->get(route('provider.leads.index', ['cerere' => $lead->id, 'tab' => 'email', 'filtru' => 'new']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected', $lead->id)
                ->where('tab', 'email')
                ->where('filter', 'new')
                ->where('q', ''));

        $this->actingAs($this->provider)
            ->get(route('provider.leads.index', ['q' => '  ștefan  ']))
            ->assertInertia(fn (Assert $page) => $page->where('q', 'ștefan'));

        // Junk values fall back to the defaults.
        $this->actingAs($this->provider)
            ->get(route('provider.leads.index', ['cerere' => 'abc', 'tab' => 'x', 'filtru' => 'y']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected', null)
                ->where('tab', 'call')
                ->where('filter', 'all'));
    }

    public function test_leads_carry_thread_status_for_the_badges(): void
    {
        $lead = $this->makeLead($this->client);

        // Untouched: no thread, not contacted.
        $this->actingAs($this->provider)->get(route('provider.leads.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('leads.0.thread', null)
                ->where('leads.0.contacted', false));

        // Client writes: one unread message, the client spoke last, not yet answered.
        $this->actingAs($this->client)->post(route('listings.messages.start', $this->listing->slug), ['body' => 'Bună!']);

        $this->actingAs($this->provider)->get(route('provider.leads.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('leads.0.thread.unread', 1)
                ->where('leads.0.thread.last_from', 'client')
                ->where('leads.0.thread.replied', false)
                ->where('leads.0.contacted', false));

        // Provider answers from the chat tab: read, provider spoke last, and now counts as contacted.
        $conversation = Conversation::first();
        $this->actingAs($this->provider)->post(route('provider.messages.store', $conversation), ['body' => 'Salut!']);

        $this->actingAs($this->provider)->get(route('provider.leads.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('leads.0.thread.unread', 0)
                ->where('leads.0.thread.last_from', 'me')
                ->where('leads.0.thread.replied', true)
                ->where('leads.0.contacted', true));

        // The client answers back: unread again, client spoke last, still contacted.
        $this->actingAs($this->client)->post(route('messages.store', $conversation), ['body' => 'Perfect, mulțumesc!']);

        $this->actingAs($this->provider)->get(route('provider.leads.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('leads.0.thread.unread', 1)
                ->where('leads.0.thread.last_from', 'client')
                ->where('leads.0.contacted', true));

        // Opening the chat tab clears the unread count in the same response.
        $this->actingAs($this->provider)->get(route('provider.leads.index', ['cerere' => $lead->id, 'tab' => 'chat']))
            ->assertInertia(fn (Assert $page) => $page->where('leads.0.thread.unread', 0));
    }
}
