<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Notifications\NewQuoteRequestPendingApproval;
use App\Notifications\QuoteRequestPackageReceived;
use App\Notifications\QuoteRequestReceived;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuoteRequestPackageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $photo;

    private Category $dj;

    private Category $venue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $this->user = User::factory()->create(['status' => true]);
        $this->user->assignRole('client');

        $this->photo = Category::create(['name' => 'Fotografie', 'slug' => 'fotografie']);
        $this->dj = Category::create(['name' => 'DJ', 'slug' => 'dj']);
        $this->venue = Category::create(['name' => 'Locație', 'slug' => 'locatie']);
    }

    private function payload(array $categoryIds, array $overrides = []): array
    {
        return [
            'category_ids' => $categoryIds,
            'title' => 'Nuntă Ana & Vlad',
            'message' => 'Căutăm furnizori pentru nunta noastră.',
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => '0700000000',
            ...$overrides,
        ];
    }

    public function test_a_single_category_creates_one_request_with_no_group_token(): void
    {
        Notification::fake();

        $this->actingAs($this->user)->post(route('quote-requests.store'), $this->payload([$this->photo->id]));

        $this->assertSame(1, QuoteRequest::count());
        $this->assertNull(QuoteRequest::first()->group_token);

        Notification::assertSentOnDemand(QuoteRequestReceived::class);
        Notification::assertSentOnDemandTimes(QuoteRequestPackageReceived::class, 0);
    }

    public function test_several_categories_create_one_request_per_category_sharing_a_group_token(): void
    {
        Notification::fake();

        $this->actingAs($this->user)->post(
            route('quote-requests.store'),
            $this->payload([$this->photo->id, $this->dj->id, $this->venue->id])
        )->assertRedirect();

        $requests = QuoteRequest::all();
        $this->assertSame(3, $requests->count());
        $this->assertSame(1, $requests->pluck('group_token')->unique()->count());
        $this->assertNotNull($requests->first()->group_token);
        $this->assertSame([$this->photo->id, $this->dj->id, $this->venue->id], $requests->pluck('category_id')->sort()->values()->all());

        // Every row gets the shared title/message/contact details.
        $this->assertTrue($requests->every(fn (QuoteRequest $r) => $r->title === 'Nuntă Ana & Vlad'));

        // One combined client email, not three.
        Notification::assertSentOnDemandTimes(QuoteRequestPackageReceived::class, 1);
        Notification::assertSentOnDemandTimes(QuoteRequestReceived::class, 0);

        // The support inbox still hears about each one individually, since moderation is per-category.
        Notification::assertSentOnDemandTimes(NewQuoteRequestPendingApproval::class, 3);
    }

    public function test_duplicate_category_ids_are_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('quote-requests.store'), $this->payload([$this->photo->id, $this->photo->id]))
            ->assertSessionHasErrors('category_ids.1');

        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_more_than_five_categories_are_rejected(): void
    {
        $ids = collect(range(1, 6))->map(fn ($n) => Category::create(['name' => "Categorie {$n}", 'slug' => "categorie-{$n}"])->id)->all();

        $this->actingAs($this->user)
            ->post(route('quote-requests.store'), $this->payload($ids))
            ->assertSessionHasErrors('category_ids');

        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_success_page_lists_the_other_package_members(): void
    {
        $this->actingAs($this->user)->post(
            route('quote-requests.store'),
            $this->payload([$this->photo->id, $this->dj->id])
        );

        $first = QuoteRequest::where('category_id', $this->photo->id)->firstOrFail();

        $this->actingAs($this->user)->get(route('quote-requests.success', $first))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('package', 1)
                ->where('package.0.category', 'DJ'));
    }

    public function test_show_page_links_to_sibling_requests(): void
    {
        $this->actingAs($this->user)->post(
            route('quote-requests.store'),
            $this->payload([$this->photo->id, $this->venue->id])
        );

        $photoRequest = QuoteRequest::where('category_id', $this->photo->id)->firstOrFail();

        $this->actingAs($this->user)->get(route('quote-requests.show', $photoRequest))
            ->assertInertia(fn (Assert $page) => $page
                ->has('package', 1)
                ->where('package.0.category', 'Locație'));
    }

    public function test_index_reports_the_package_size_per_request(): void
    {
        $this->actingAs($this->user)->post(
            route('quote-requests.store'),
            $this->payload([$this->photo->id, $this->dj->id, $this->venue->id])
        );
        $this->actingAs($this->user)->post(route('quote-requests.store'), $this->payload([$this->photo->id], overrides: ['title' => 'Solo']));

        $response = $this->actingAs($this->user)->get(route('quote-requests.index'))
            ->assertOk();

        $sizes = collect($response->viewData('page')['props']['quoteRequests'])->pluck('package_size', 'title');
        $this->assertSame(1, $sizes['Solo']);
        $this->assertSame(3, $sizes['Nuntă Ana & Vlad']);
    }

    public function test_approving_one_package_member_does_not_affect_the_others(): void
    {
        $this->actingAs($this->user)->post(
            route('quote-requests.store'),
            $this->payload([$this->photo->id, $this->dj->id])
        );

        $photoRequest = QuoteRequest::where('category_id', $this->photo->id)->firstOrFail();
        $djRequest = QuoteRequest::where('category_id', $this->dj->id)->firstOrFail();

        $admin = User::factory()->create(['status' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('administration.quote-requests.approve', $photoRequest));

        $this->assertSame('open', $photoRequest->fresh()->status);
        $this->assertSame('pending_review', $djRequest->fresh()->status);
    }
}
