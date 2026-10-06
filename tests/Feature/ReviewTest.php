<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewPendingApproval;
use App\Notifications\NewReviewReceived;
use App\Notifications\ReviewReplied;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;

    private User $client;

    private User $admin;

    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $county = County::create(['name' => 'Cluj']);
        $locality = Locality::create(['county_id' => $county->id, 'name' => 'Cluj-Napoca']);

        $this->provider = User::factory()->create(['status' => true]);
        $this->provider->assignRole('furnizor');

        $profile = ProviderProfile::create([
            'user_id' => $this->provider->id,
            'company_name' => 'Foto SRL',
            'cui' => '12345678',
            'slug' => 'foto-srl',
            'county_id' => $county->id,
            'locality_id' => $locality->id,
            'status' => 'active',
        ]);

        $category = Category::create(['slug' => 'fotografie', 'name' => 'Fotografie']);

        $this->listing = Listing::create([
            'provider_profile_id' => $profile->id,
            'category_id' => $category->id,
            'title' => 'Pachet foto nuntă',
            'slug' => 'pachet-foto-nunta',
            'status' => 'published',
        ]);

        $this->client = User::factory()->create(['status' => true]);
        $this->client->assignRole('client');

        $this->admin = User::factory()->create(['status' => true]);
        $this->admin->assignRole('admin');
    }

    /** Client writes, and (unless $providerReplied is false) the provider answers. */
    private function talk(User $client, bool $providerReplied = true): Conversation
    {
        $conversation = Conversation::create([
            'listing_id' => $this->listing->id,
            'client_id' => $client->id,
            'provider_profile_id' => $this->listing->provider_profile_id,
        ]);

        $conversation->addMessage($client, 'Sunteți liberi?', Conversation::SIDE_CLIENT);

        if ($providerReplied) {
            $conversation->addMessage($this->provider, 'Da, suntem.', Conversation::SIDE_PROVIDER);
        }

        return $conversation;
    }

    private function makeReview(string $status = 'approved', ?User $author = null): Review
    {
        return Review::create([
            'listing_id' => $this->listing->id,
            'provider_profile_id' => $this->listing->provider_profile_id,
            'user_id' => ($author ?? $this->client)->id,
            'rating' => 5,
            'comment' => 'Excelent',
            'status' => $status,
        ]);
    }

    public function test_client_who_talked_with_the_provider_can_leave_a_pending_review(): void
    {
        Notification::fake();
        $this->talk($this->client);

        $this->actingAs($this->client)
            ->post(route('listings.reviews.store', $this->listing->slug), ['rating' => 4, 'comment' => '  Foarte bine  '])
            ->assertRedirect()
            ->assertSessionHas('success');

        $review = Review::first();
        $this->assertSame(4, $review->rating);
        $this->assertSame('Foarte bine', $review->comment);
        $this->assertSame('pending', $review->status);
        $this->assertSame($this->listing->provider_profile_id, $review->provider_profile_id);

        Notification::assertSentOnDemand(NewReviewPendingApproval::class);
    }

    public function test_client_without_a_provider_reply_cannot_review(): void
    {
        $this->talk($this->client, providerReplied: false);

        $this->actingAs($this->client)
            ->post(route('listings.reviews.store', $this->listing->slug), ['rating' => 5])
            ->assertForbidden();

        $this->assertSame(0, Review::count());
    }

    public function test_client_who_never_talked_cannot_review(): void
    {
        $this->actingAs($this->client)
            ->post(route('listings.reviews.store', $this->listing->slug), ['rating' => 5])
            ->assertForbidden();
    }

    public function test_provider_cannot_review_their_own_listing(): void
    {
        $this->actingAs($this->provider)
            ->post(route('listings.reviews.store', $this->listing->slug), ['rating' => 5])
            ->assertForbidden();
    }

    public function test_only_one_review_per_client_and_listing(): void
    {
        $this->talk($this->client);
        $this->makeReview('pending');

        $this->actingAs($this->client)
            ->post(route('listings.reviews.store', $this->listing->slug), ['rating' => 1])
            ->assertSessionHas('error');

        $this->assertSame(1, Review::count());
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $this->talk($this->client);

        foreach ([0, 6, 'abc', null] as $rating) {
            $this->actingAs($this->client)
                ->post(route('listings.reviews.store', $this->listing->slug), ['rating' => $rating])
                ->assertSessionHasErrors('rating');
        }
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->post(route('listings.reviews.store', $this->listing->slug), ['rating' => 5])
            ->assertRedirect();

        $this->assertSame(0, Review::count());
    }

    public function test_listing_page_exposes_the_review_state(): void
    {
        $this->talk($this->client);

        $this->actingAs($this->client)->get(route('listings.show', $this->listing->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reviewState.can_review', true)
                ->where('reviewState.my_review', null));

        $this->makeReview('pending');

        $this->actingAs($this->client)->get(route('listings.show', $this->listing->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reviewState.can_review', false)
                ->where('reviewState.my_review.status', 'pending')
                ->where('listing.reviews_count', 0));
    }

    public function test_only_approved_reviews_are_public(): void
    {
        $this->makeReview('pending');

        $this->get(route('listings.show', $this->listing->slug))
            ->assertInertia(fn (Assert $page) => $page->where('listing.reviews_count', 0));

        Review::first()->update(['status' => 'approved']);

        $this->get(route('listings.show', $this->listing->slug))
            ->assertInertia(fn (Assert $page) => $page->where('listing.reviews_count', 1));
    }

    public function test_admin_approves_a_review_and_the_provider_is_notified(): void
    {
        Notification::fake();
        $review = $this->makeReview('pending');

        $this->actingAs($this->admin)
            ->post(route('administration.reviews.approve', $review))
            ->assertRedirect();

        $this->assertSame('approved', $review->fresh()->status);
        $this->assertNotNull($review->fresh()->moderated_at);
        Notification::assertSentTo($this->provider, NewReviewReceived::class);
    }

    public function test_approving_twice_notifies_only_once(): void
    {
        Notification::fake();
        $review = $this->makeReview('pending');

        $this->actingAs($this->admin)->post(route('administration.reviews.approve', $review));
        $this->actingAs($this->admin)->post(route('administration.reviews.approve', $review));

        Notification::assertSentToTimes($this->provider, NewReviewReceived::class, 1);
    }

    public function test_admin_rejects_a_review_and_it_loses_its_reply(): void
    {
        $review = $this->makeReview('approved');
        $review->update(['provider_reply' => 'Mulțumim!', 'provider_replied_at' => now()]);

        $this->actingAs($this->admin)
            ->post(route('administration.reviews.reject', $review))
            ->assertRedirect();

        $review->refresh();
        $this->assertSame('rejected', $review->status);
        $this->assertNull($review->provider_reply);
    }

    public function test_non_admins_cannot_moderate(): void
    {
        $review = $this->makeReview('pending');

        foreach ([$this->client, $this->provider] as $user) {
            $this->actingAs($user)->post(route('administration.reviews.approve', $review))->assertForbidden();
            $this->actingAs($user)->get(route('administration.reviews.index'))->assertForbidden();
        }

        $this->assertSame('pending', $review->fresh()->status);
    }

    public function test_admin_moderation_list_filters_by_status(): void
    {
        $this->makeReview('pending');

        $this->actingAs($this->admin)->get(route('administration.reviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Administration/Reviews/Index')
                ->where('filters.status', 'pending')
                ->has('reviews.data', 1)
                ->where('counts.pending', 1));

        $this->actingAs($this->admin)->get(route('administration.reviews.index', ['status' => 'approved']))
            ->assertInertia(fn (Assert $page) => $page->has('reviews.data', 0));
    }

    public function test_provider_replies_and_the_client_is_notified_once(): void
    {
        Notification::fake();
        $review = $this->makeReview('approved');

        $this->actingAs($this->provider)
            ->put(route('provider.reviews.reply', $review), ['reply' => 'Vă mulțumim!'])
            ->assertRedirect();

        $this->assertSame('Vă mulțumim!', $review->fresh()->provider_reply);
        $this->assertNotNull($review->fresh()->provider_replied_at);

        $this->actingAs($this->provider)
            ->put(route('provider.reviews.reply', $review), ['reply' => 'Vă mulțumim mult!']);

        $this->assertSame('Vă mulțumim mult!', $review->fresh()->provider_reply);
        Notification::assertSentToTimes($this->client, ReviewReplied::class, 1);
    }

    public function test_provider_cannot_reply_to_someone_elses_review_or_a_pending_one(): void
    {
        $pending = $this->makeReview('pending');

        $this->actingAs($this->provider)
            ->put(route('provider.reviews.reply', $pending), ['reply' => 'Test'])
            ->assertNotFound();

        $other = User::factory()->create(['status' => true]);
        $other->assignRole('furnizor');
        ProviderProfile::create([
            'user_id' => $other->id,
            'company_name' => 'Alt SRL',
            'cui' => '87654321',
            'slug' => 'alt-srl',
            'county_id' => County::first()->id,
            'locality_id' => Locality::first()->id,
            'status' => 'active',
        ]);

        $approved = $this->makeReview('approved', User::factory()->create());

        $this->actingAs($other)
            ->put(route('provider.reviews.reply', $approved), ['reply' => 'Test'])
            ->assertNotFound();

        $this->assertNull($approved->fresh()->provider_reply);
    }

    public function test_provider_can_delete_their_reply(): void
    {
        $review = $this->makeReview('approved');
        $review->update(['provider_reply' => 'Mulțumim!', 'provider_replied_at' => now()]);

        $this->actingAs($this->provider)
            ->delete(route('provider.reviews.reply.destroy', $review))
            ->assertRedirect();

        $this->assertNull($review->fresh()->provider_reply);
    }

    public function test_provider_reviews_page_lists_and_filters_unanswered(): void
    {
        $answered = $this->makeReview('approved');
        $answered->update(['provider_reply' => 'Mulțumim!']);
        $this->makeReview('approved', User::factory()->create());
        $this->makeReview('pending', User::factory()->create());

        $this->actingAs($this->provider)->get(route('provider.reviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Provider/Reviews/Index')
                ->has('reviews.data', 2)
                ->where('counts.all', 2)
                ->where('counts.unanswered', 1));

        $this->actingAs($this->provider)->get(route('provider.reviews.index', ['filter' => 'unanswered']))
            ->assertInertia(fn (Assert $page) => $page->has('reviews.data', 1));
    }
}
