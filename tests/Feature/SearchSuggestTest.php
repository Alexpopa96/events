<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchSuggestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);
    }

    public function test_it_suggests_matching_listings_providers_and_categories(): void
    {
        $county = County::create(['name' => 'Cluj']);
        $locality = Locality::create(['county_id' => $county->id, 'name' => 'Cluj-Napoca']);
        $category = Category::create(['slug' => 'fotografie', 'name' => 'Fotografie de nuntă']);

        $user = User::factory()->create(['status' => true]);
        $user->assignRole('furnizor');
        ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Nuntă Foto SRL',
            'cui' => '12345678',
            'slug' => 'nunta-foto-srl',
            'county_id' => $county->id,
            'locality_id' => $locality->id,
            'status' => 'active',
        ]);

        Listing::create([
            'provider_profile_id' => $user->providerProfile->id,
            'category_id' => $category->id,
            'title' => 'Pachet foto nuntă premium',
            'slug' => 'pachet-foto-nunta-premium',
            'status' => 'published',
        ]);

        $response = $this->getJson(route('search.suggest', ['q' => 'nunt']))->assertOk();

        $types = collect($response->json('results'))->pluck('type');
        $this->assertContains('listing', $types);
        $this->assertContains('provider', $types);
        $this->assertContains('category', $types);
    }

    public function test_it_excludes_unpublished_listings_and_inactive_providers(): void
    {
        $county = County::create(['name' => 'Cluj']);
        $locality = Locality::create(['county_id' => $county->id, 'name' => 'Cluj-Napoca']);
        $category = Category::create(['slug' => 'dj', 'name' => 'DJ']);

        $pending = User::factory()->create(['status' => true]);
        $pending->assignRole('furnizor');
        ProviderProfile::create([
            'user_id' => $pending->id,
            'company_name' => 'DJ Pending SRL',
            'cui' => '11223344',
            'slug' => 'dj-pending-srl',
            'county_id' => $county->id,
            'locality_id' => $locality->id,
            'status' => 'pending',
        ]);

        Listing::create([
            'provider_profile_id' => $pending->providerProfile->id,
            'category_id' => $category->id,
            'title' => 'DJ draft anunț',
            'slug' => 'dj-draft-anunt',
            'status' => 'draft',
        ]);

        $response = $this->getJson(route('search.suggest', ['q' => 'DJ']))->assertOk();

        $titles = collect($response->json('results'))->pluck('title');
        $this->assertNotContains('DJ draft anunț', $titles);
        $this->assertNotContains('DJ Pending SRL', $titles);
    }

    public function test_it_matches_plural_singular_and_unaccented_forms(): void
    {
        // Diacritic folding is done by MySQL's unicode_ci collation (SQLite in tests
        // doesn't fold), so DB fixtures stay ASCII; FuzzySearchTest covers diacritics.
        $category = Category::create(['slug' => 'fotograf', 'name' => 'Fotograf', 'is_active' => true]);
        Category::create(['slug' => 'torturi', 'name' => 'Torturi', 'is_active' => true]);
        Category::create(['slug' => 'salon', 'name' => 'Salon evenimente', 'is_active' => true]);
        $this->publishedListing($category, 'Fotografie de nunta');

        $titles = fn (string $q) => collect($this->getJson(route('search.suggest', ['q' => $q]))->assertOk()->json('results'))->pluck('title');

        $this->assertContains('Fotograf', $titles('fotografi'));
        $this->assertContains('Fotografie de nunta', $titles('FOTOGRAFI nunți'));
        $this->assertContains('Torturi', $titles('tort'));
        $this->assertContains('Salon evenimente', $titles('saloane'));
    }

    public function test_listing_index_search_is_forgiving_too(): void
    {
        $category = Category::create(['slug' => 'fotograf', 'name' => 'Fotograf', 'is_active' => true]);
        $this->publishedListing($category, 'Sedinta foto botez');
        $this->publishedListing(Category::create(['slug' => 'dj', 'name' => 'DJ', 'is_active' => true]), 'DJ petrecere');

        $this->get(route('listings.index', ['q' => 'fotografi']))
            ->assertOk()
            ->assertSee('Sedinta foto botez', false)
            ->assertDontSee('DJ petrecere', false);
    }

    public function test_it_suggests_app_pages_the_visitor_can_open(): void
    {
        $results = collect($this->getJson(route('search.suggest', ['q' => 'abonamente']))->json('results'));
        $this->assertContains('Abonamente', $results->where('type', 'page')->pluck('title'));

        $guestPages = collect($this->getJson(route('search.suggest', ['q' => 'anunturile mele']))->json('results'))->where('type', 'page');
        $this->assertNotContains('Anunțurile mele', $guestPages->pluck('title'));
    }

    public function test_empty_query_returns_popular_categories(): void
    {
        Category::create(['slug' => 'dj', 'name' => 'DJ', 'is_active' => true]);

        $this->getJson(route('search.suggest'))
            ->assertOk()
            ->assertJsonPath('results', [])
            ->assertJsonPath('popular.0.title', 'DJ');
    }

    private function publishedListing(Category $category, string $title): Listing
    {
        $county = County::firstOrCreate(['name' => 'Cluj']);
        $locality = Locality::firstOrCreate(['county_id' => $county->id, 'name' => 'Cluj-Napoca']);

        $user = User::factory()->create(['status' => true]);
        $user->assignRole('furnizor');
        $profile = ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Firma '.$user->id,
            'cui' => (string) (10000000 + $user->id),
            'slug' => 'firma-'.$user->id,
            'county_id' => $county->id,
            'locality_id' => $locality->id,
            'status' => 'active',
        ]);

        return Listing::create([
            'provider_profile_id' => $profile->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'status' => 'published',
        ]);
    }

    public function test_query_must_be_at_least_two_characters(): void
    {
        $this->getJson(route('search.suggest', ['q' => 'a']))->assertUnprocessable();
    }
}
