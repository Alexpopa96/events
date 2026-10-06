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

    public function test_query_must_be_at_least_two_characters(): void
    {
        $this->getJson(route('search.suggest', ['q' => 'a']))->assertUnprocessable();
    }
}
