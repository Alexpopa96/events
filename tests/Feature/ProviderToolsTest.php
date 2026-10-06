<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\County;
use App\Models\Invoice;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionExpiringSoon;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProviderToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);
        $this->seed(SubscriptionPlanSeeder::class);

        $county = County::create(['name' => 'Cluj']);
        $locality = Locality::create(['county_id' => $county->id, 'name' => 'Cluj-Napoca']);

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

        $this->category = Category::create(['slug' => 'fotografie', 'name' => 'Fotografie']);
    }

    private function makeSecondProvider(): User
    {
        $user = User::factory()->create(['status' => true]);
        $user->assignRole('furnizor');
        ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Video SRL',
            'cui' => '11223344',
            'slug' => 'video-srl',
            'county_id' => County::first()->id,
            'locality_id' => Locality::first()->id,
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    /** Puts the main provider on a plan with room for more than one listing. */
    private function giveProviderRoom(): void
    {
        $plan = SubscriptionPlan::where('slug', 'standard')->firstOrFail();

        ProviderSubscription::create([
            'provider_profile_id' => $this->provider->providerProfile->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    private function makeListing(array $overrides = []): Listing
    {
        return Listing::create([
            'provider_profile_id' => $this->provider->providerProfile->id,
            'category_id' => $this->category->id,
            'title' => 'Pachet foto nuntă',
            'slug' => 'pachet-foto-nunta-'.random_int(1000, 9999),
            'status' => 'published',
            'price_type' => 'starting_from',
            'price_from' => 2000,
            'benefits' => ['8 ore filmare'],
            ...$overrides,
        ]);
    }

    // ---- Duplicate -----------------------------------------------------

    public function test_provider_duplicates_a_listing_as_a_draft(): void
    {
        $this->giveProviderRoom();
        $listing = $this->makeListing();

        $this->actingAs($this->provider)
            ->post(route('provider.listings.duplicate', $listing))
            ->assertRedirect();

        $this->assertSame(2, Listing::count());
        $copy = Listing::where('id', '!=', $listing->id)->firstOrFail();

        $this->assertSame('Pachet foto nuntă (copie)', $copy->title);
        $this->assertSame('draft', $copy->status);
        $this->assertSame($listing->price_from, $copy->price_from);
        $this->assertSame($listing->benefits, $copy->benefits);
        $this->assertNotSame($listing->slug, $copy->slug);
    }

    public function test_duplicating_copies_media_files(): void
    {
        Storage::fake('public');
        $this->giveProviderRoom();
        $listing = $this->makeListing();

        $path = UploadedFile::fake()->image('cover.jpg')->store('listings/media', 'public');
        $listing->media()->create(['type' => 'photo', 'path' => $path, 'is_cover' => true, 'position' => 0]);

        $this->actingAs($this->provider)->post(route('provider.listings.duplicate', $listing));

        $copy = Listing::where('id', '!=', $listing->id)->firstOrFail();
        $this->assertSame(1, $copy->media()->count());

        $copiedMedia = $copy->media()->first();
        $this->assertNotSame($path, $copiedMedia->path);
        Storage::disk('public')->assertExists($copiedMedia->path);
        $this->assertTrue($copiedMedia->is_cover);
    }

    public function test_duplicating_respects_the_listing_quota(): void
    {
        $listing = $this->makeListing(); // free plan: max_listings = 1

        $this->actingAs($this->provider)
            ->post(route('provider.listings.duplicate', $listing))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, Listing::count());
    }

    public function test_provider_cannot_duplicate_someone_elses_listing(): void
    {
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

        $listing = $this->makeListing();

        $this->actingAs($other)->post(route('provider.listings.duplicate', $listing))->assertForbidden();
        $this->assertSame(1, Listing::count());
    }

    // ---- Quick price edit -----------------------------------------------

    public function test_provider_updates_the_price_inline(): void
    {
        $listing = $this->makeListing();

        $this->actingAs($this->provider)
            ->patch(route('provider.listings.price', $listing), [
                'price_type' => 'fixed',
                'price_from' => 1500,
                'price_to' => null,
            ])
            ->assertRedirect();

        $listing->refresh();
        $this->assertSame('fixed', $listing->price_type);
        $this->assertSame('1500.00', $listing->price_from);
        $this->assertSame('published', $listing->status);
    }

    public function test_price_on_request_clears_the_amounts(): void
    {
        $listing = $this->makeListing();

        $this->actingAs($this->provider)->patch(route('provider.listings.price', $listing), [
            'price_type' => 'on_request',
            'price_from' => 999,
        ]);

        $listing->refresh();
        $this->assertNull($listing->price_from);
        $this->assertNull($listing->price_to);
    }

    public function test_price_to_must_be_at_least_price_from(): void
    {
        $listing = $this->makeListing();

        $this->actingAs($this->provider)
            ->patch(route('provider.listings.price', $listing), [
                'price_type' => 'fixed',
                'price_from' => 2000,
                'price_to' => 1000,
            ])
            ->assertSessionHasErrors('price_to');
    }

    public function test_provider_cannot_edit_someone_elses_price(): void
    {
        $other = $this->makeSecondProvider();

        $listing = $this->makeListing();

        $this->actingAs($other)
            ->patch(route('provider.listings.price', $listing), ['price_type' => 'fixed', 'price_from' => 1])
            ->assertForbidden();
    }

    // ---- Invoice download -------------------------------------------------

    public function test_provider_can_view_their_printable_invoice(): void
    {
        $invoice = Invoice::create([
            'provider_profile_id' => $this->provider->providerProfile->id,
            'number' => 'INV-0001',
            'amount' => 99,
            'status' => 'paid',
            'issued_at' => now(),
        ]);

        $this->actingAs($this->provider)->get(route('provider.invoices.show', $invoice))
            ->assertOk()
            ->assertViewIs('invoices.show')
            ->assertSee('INV-0001')
            ->assertSee('Foto SRL');
    }

    public function test_invoice_with_a_real_pdf_redirects_there(): void
    {
        $invoice = Invoice::create([
            'provider_profile_id' => $this->provider->providerProfile->id,
            'number' => 'INV-0002',
            'amount' => 99,
            'status' => 'paid',
            'pdf_url' => 'https://files.stripe.com/invoice.pdf',
        ]);

        $this->actingAs($this->provider)->get(route('provider.invoices.show', $invoice))
            ->assertRedirect('https://files.stripe.com/invoice.pdf');
    }

    public function test_provider_cannot_view_someone_elses_invoice(): void
    {
        $other = $this->makeSecondProvider();

        $invoice = Invoice::create([
            'provider_profile_id' => $this->provider->providerProfile->id,
            'number' => 'INV-0003',
            'amount' => 99,
            'status' => 'paid',
        ]);

        $this->actingAs($other)->get(route('provider.invoices.show', $invoice))->assertForbidden();
    }

    // ---- Renewal reminder --------------------------------------------------

    public function test_reminder_command_notifies_subscriptions_expiring_within_three_days(): void
    {
        Notification::fake();

        $plan = SubscriptionPlan::where('slug', 'standard')->firstOrFail();

        $soon = ProviderSubscription::create([
            'provider_profile_id' => $this->provider->providerProfile->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDays(2),
        ]);

        $this->artisan('subscriptions:notify-expiring')->assertSuccessful();

        Notification::assertSentTo($this->provider, SubscriptionExpiringSoon::class);
        $this->assertNotNull($soon->fresh()->reminder_sent_at);
    }

    public function test_reminder_command_skips_subscriptions_far_from_expiry_or_already_reminded(): void
    {
        Notification::fake();
        $plan = SubscriptionPlan::where('slug', 'standard')->firstOrFail();

        $farOut = ProviderSubscription::create([
            'provider_profile_id' => $this->provider->providerProfile->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDays(10),
        ]);

        $otherProvider = $this->makeSecondProvider();
        $alreadyReminded = ProviderSubscription::create([
            'provider_profile_id' => $otherProvider->providerProfile->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDay(),
            'reminder_sent_at' => now()->subHour(),
        ]);

        $this->artisan('subscriptions:notify-expiring');

        Notification::assertNothingSent();
    }
}
