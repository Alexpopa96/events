<?php

namespace Tests\Feature;

use App\Http\Controllers\Seo\Sitemap;
use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\User;
use App\Support\Seo\CategoryLandingSeo;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private Category $photo;

    private County $cluj;

    private Locality $clujNapoca;

    private ProviderProfile $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $this->photo = Category::create(['slug' => 'fotograf', 'name' => 'Fotograf', 'is_active' => true]);
        $this->cluj = County::create(['name' => 'Cluj']);
        $this->clujNapoca = Locality::create(['county_id' => $this->cluj->id, 'name' => 'Cluj-Napoca']);

        $user = User::factory()->create(['status' => true]);
        $user->assignRole('furnizor');

        $this->provider = ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Studio Lumina',
            'cui' => (string) random_int(10000000, 99999999),
            'slug' => 'studio-lumina',
            'county_id' => $this->cluj->id,
            'locality_id' => $this->clujNapoca->id,
            'status' => 'active',
        ]);

        Cache::forget(Sitemap::CACHE_KEY);
    }

    private function makeListing(array $overrides = []): Listing
    {
        return Listing::create([
            'provider_profile_id' => $this->provider->id,
            'category_id' => $this->photo->id,
            'county_id' => $this->cluj->id,
            'locality_id' => $this->clujNapoca->id,
            'title' => 'Fotografie de nuntă',
            'slug' => 'foto-nunta-'.random_int(1000, 9999),
            'status' => 'published',
            'price_type' => 'starting_from',
            'price_from' => 1500,
            'published_at' => now(),
            ...$overrides,
        ]);
    }

    public function test_counties_and_localities_get_slugs_automatically(): void
    {
        $this->assertSame('cluj', $this->cluj->slug);
        $this->assertSame('cluj-napoca', $this->clujNapoca->slug);
        $this->assertSame('targu-mures', Locality::create(['county_id' => $this->cluj->id, 'name' => 'Târgu Mureș'])->slug);
    }

    public function test_county_landing_page_is_indexable_with_data_driven_copy(): void
    {
        $listing = $this->makeListing();
        Review::create([
            'listing_id' => $listing->id,
            'provider_profile_id' => $this->provider->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 5,
            'comment' => 'Super',
            'status' => 'approved',
        ]);
        $this->makeListing(['county_id' => County::create(['name' => 'Iasi'])->id, 'locality_id' => null]);

        $this->get('/categorii/fotograf/cluj')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/categorii/fotograf/cluj').'">', false)
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Show')
                ->where('place.county.slug', 'cluj')
                ->where('place.locality', null)
                ->has('listings.data', 1)
                ->where('stats.minPrice', 1500)
                ->where('seo.title', 'Fotograf Cluj — 1 furnizor, prețuri și recenzii')
                ->where('intro', fn (string $intro) => str_contains($intro, 'de la 1.500 RON') && str_contains($intro, '5/5 din 1 recenzie'))
                ->where('places', fn ($groups) => collect($groups)->pluck('title')->contains('Fotograf în alte județe')));
    }

    public function test_locality_landing_page_only_shows_that_locality(): void
    {
        $this->makeListing();
        $this->makeListing(['locality_id' => Locality::create(['county_id' => $this->cluj->id, 'name' => 'Turda'])->id]);

        $this->get('/categorii/fotograf/cluj/cluj-napoca')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('place.locality.slug', 'cluj-napoca')
                ->has('listings.data', 1)
                ->where('counties', []));
    }

    public function test_locality_is_resolved_within_its_county(): void
    {
        $alba = County::create(['name' => 'Alba']);
        $valeaAlba = Locality::create(['county_id' => $alba->id, 'name' => 'Valea Mare']);
        $valeaCluj = Locality::create(['county_id' => $this->cluj->id, 'name' => 'Valea Mare']);
        $this->makeListing(['locality_id' => $valeaCluj->id]);

        $this->get('/categorii/fotograf/cluj/valea-mare')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 1));

        $this->get('/categorii/fotograf/alba/valea-mare')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));

        $this->assertNotSame($valeaAlba->id, $valeaCluj->id);
        $this->get('/categorii/fotograf/alba/cluj-napoca')->assertNotFound();
        $this->get('/categorii/fotograf/nicaieri')->assertNotFound();
    }

    public function test_empty_and_filtered_pages_are_noindex(): void
    {
        $this->makeListing();
        County::create(['name' => 'Iasi']);

        $this->get('/categorii/fotograf/iasi')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertInertia(fn (Assert $page) => $page->where('intro', fn (string $intro) => str_contains($intro, 'Încă nu avem furnizori')));

        $this->get('/categorii/fotograf/cluj?sort=price_asc')
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        $this->get('/anunturi?q=foto')
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_event_landing_page_shows_tagged_and_untagged_listings(): void
    {
        $this->makeListing(['event_types' => ['nunta'], 'title' => 'Foto nuntă']);
        $this->makeListing(['event_types' => null, 'title' => 'Foto orice eveniment']);
        $this->makeListing(['event_types' => ['corporate'], 'title' => 'Foto corporate']);

        $this->get('/nunta/fotograf/cluj')
            ->assertOk()
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertSee('<link rel="canonical" href="'.url('/nunta/fotograf/cluj').'">', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Show')
                ->has('listings.data', 2)
                ->where('eventType.value', 'nunta')
                ->where('heading', 'Fotograf pentru nuntă în Cluj')
                ->where('pageUrl', url('/nunta/fotograf/cluj'))
                ->where('seo.title', 'Fotograf nuntă Cluj — 2 furnizori, prețuri și recenzii')
                ->where('breadcrumbs.3', ['name' => 'Nuntă', 'url' => url('/nunta/fotograf')])
                ->where('places', fn ($groups) => collect($groups)
                    ->firstWhere('title', 'Fotograf în Cluj pentru alte evenimente')['items'] === [
                        ['name' => 'Toate evenimentele', 'url' => url('/categorii/fotograf/cluj'), 'count' => 3],
                        ['name' => 'Corporate', 'url' => url('/corporate/fotograf/cluj'), 'count' => 2],
                    ]));
    }

    public function test_event_page_without_tagged_listings_is_noindex(): void
    {
        $this->makeListing(['event_types' => null]);

        $this->get('/botez/fotograf')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 1));

        $this->get('/categorii/fotograf')
            ->assertInertia(fn (Assert $page) => $page->where('places', fn ($groups) => ! collect($groups)->contains('title', 'Fotograf pe tip de eveniment')));

        $this->get('/altul/fotograf')->assertNotFound();
    }

    public function test_sitemap_lists_event_pages_only_for_tagged_listings(): void
    {
        $this->makeListing(['event_types' => ['nunta']]);

        $this->get('/sitemap.xml')
            ->assertSee('<loc>'.url('/nunta/fotograf').'</loc>', false)
            ->assertSee('<loc>'.url('/nunta/fotograf/cluj').'</loc>', false)
            ->assertSee('<loc>'.url('/nunta/fotograf/cluj/cluj-napoca').'</loc>', false)
            ->assertDontSee(url('/botez/fotograf'));
    }

    public function test_copy_uses_county_names_with_diacritics(): void
    {
        $iasi = County::create(['name' => 'Iasi']);
        $bucuresti = County::create(['name' => 'Bucuresti']);
        $this->makeListing(['county_id' => $iasi->id, 'locality_id' => null]);
        $this->makeListing(['county_id' => $bucuresti->id, 'locality_id' => null]);

        $this->get('/categorii/fotograf/iasi')
            ->assertInertia(fn (Assert $page) => $page
                ->where('heading', 'Fotograf în Iași')
                ->where('intro', fn (string $intro) => str_contains($intro, 'în județul Iași.')));

        $this->get('/categorii/fotograf/bucuresti')
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.title', 'Fotograf București — 1 furnizor, prețuri și recenzii')
                ->where('intro', fn (string $intro) => str_contains($intro, 'Fotograf în București.')));
    }

    public function test_romanian_numerals_take_de_from_twenty(): void
    {
        $this->assertSame('1 furnizor', CategoryLandingSeo::count(1, 'furnizor', 'furnizori'));
        $this->assertSame('19 furnizori', CategoryLandingSeo::count(19, 'furnizor', 'furnizori'));
        $this->assertSame('20 de furnizori', CategoryLandingSeo::count(20, 'furnizor', 'furnizori'));
        $this->assertSame('105 furnizori', CategoryLandingSeo::count(105, 'furnizor', 'furnizori'));
        $this->assertSame('120 de furnizori', CategoryLandingSeo::count(120, 'furnizor', 'furnizori'));
    }

    public function test_sitemap_lists_only_landing_pages_with_listings(): void
    {
        $listing = $this->makeListing();
        $this->makeListing(['status' => 'draft', 'slug' => 'ciorna', 'county_id' => County::create(['name' => 'Iasi'])->id]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.url('/categorii/fotograf/cluj').'</loc>', false)
            ->assertSee('<loc>'.url('/categorii/fotograf/cluj/cluj-napoca').'</loc>', false)
            ->assertSee('<loc>'.url("/anunturi/{$listing->slug}").'</loc>', false)
            ->assertSee('<loc>'.url('/furnizori/studio-lumina').'</loc>', false)
            ->assertDontSee('/categorii/fotograf/iasi')
            ->assertDontSee('/anunturi/ciorna');
    }

    public function test_robots_points_to_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_listing_and_provider_pages_have_structured_data(): void
    {
        $listing = $this->makeListing();

        $this->get("/anunturi/{$listing->slug}")
            ->assertOk()
            ->assertSee('"@type":"Service"', false)
            ->assertSee('"priceCurrency":"RON"', false)
            ->assertSee('<link rel="canonical" href="'.url("/anunturi/{$listing->slug}").'">', false);

        $this->get('/furnizori/studio-lumina')
            ->assertOk()
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertDontSee('"aggregateRating"', false);
    }
}
