<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Support\Search\FuzzySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Powers the header's global search palette: top matches across listings,
 * providers, categories, locations and the app's own pages. Matching is
 * forgiving (diacritics, singular/plural, word order) via FuzzySearch.
 * Without a query it returns the most popular categories as a starting point.
 */
class Suggest extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'min:2', 'max:80']]);
        $term = trim($data['q'] ?? '');

        if ($term === '' || FuzzySearch::terms($term) === []) {
            return response()->json(['results' => [], 'popular' => $this->popularCategories(), 'stems' => []]);
        }

        $results = $this->categories($term)
            ->concat($this->locations($term))
            ->concat($this->listings($term))
            ->concat($this->providers($term))
            ->concat($this->pages($term, $request->user()));

        return response()->json([
            'results' => $results->values(),
            'stems' => FuzzySearch::stems($term),
        ]);
    }

    private function listings(string $term): Collection
    {
        $query = Listing::query()
            ->where('status', 'published')
            ->with([
                'category:id,name,slug',
                'locality:id,name',
                'providerProfile:id,company_name',
                'media' => fn ($query) => $query->where('is_cover', true),
            ]);

        return FuzzySearch::apply($query, $term, ['title', 'category.name', 'providerProfile.company_name', 'locality.name', 'description'])
            ->orderByDesc('is_featured')
            ->take(30)
            ->get()
            ->sortByDesc(fn (Listing $listing) => [
                FuzzySearch::score($listing->title, $term) * 3
                    + FuzzySearch::score("{$listing->category?->name} {$listing->providerProfile?->company_name} {$listing->locality?->name}", $term) * 2
                    + min(FuzzySearch::score($listing->description, $term), 2),
                $listing->is_featured,
            ])
            ->take(5)
            ->map(fn (Listing $listing) => [
                'type' => 'listing',
                'id' => $listing->id,
                'title' => $listing->title,
                'subtitle' => collect([$listing->category?->name, $listing->locality?->name])->filter()->implode(' · '),
                'meta' => $listing->price_from ? 'de la '.number_format((float) $listing->price_from, 0, ',', '.').' '.($listing->currency ?: 'RON') : null,
                'url' => route('listings.show', $listing->slug),
                'image' => $listing->media->first() ? "/storage/{$listing->media->first()->path}" : null,
            ]);
    }

    private function providers(string $term): Collection
    {
        $query = ProviderProfile::query()
            ->where('status', 'active')
            ->with('locality:id,name');

        return FuzzySearch::apply($query, $term, ['company_name', 'locality.name', 'description'])
            ->take(20)
            ->get()
            ->sortByDesc(fn (ProviderProfile $provider) => FuzzySearch::score($provider->company_name, $term) * 3
                + FuzzySearch::score($provider->locality?->name, $term) * 2
                + min(FuzzySearch::score($provider->description, $term), 2))
            ->take(4)
            ->map(fn (ProviderProfile $provider) => [
                'type' => 'provider',
                'id' => $provider->id,
                'title' => $provider->company_name,
                'subtitle' => collect(['Furnizor', $provider->locality?->name])->filter()->implode(' · '),
                'url' => route('providers.show', $provider->slug),
                'image' => $provider->logoUrl(),
            ]);
    }

    private function categories(string $term): Collection
    {
        $query = Category::query()
            ->where('is_active', true)
            ->with('parent:id,name')
            ->withCount(['listings' => fn ($query) => $query->where('status', 'published')]);

        return FuzzySearch::apply($query, $term, ['name', 'parent.name'], matchAll: false)
            ->get()
            // Only word-start matches, so a short stem doesn't drag in unrelated names.
            ->filter(fn (Category $category) => FuzzySearch::score("{$category->name} {$category->parent?->name}", $term) >= 3)
            ->sortByDesc(fn (Category $category) => [FuzzySearch::score($category->name, $term), $category->listings_count])
            ->take(3)
            ->map(fn (Category $category) => [
                'type' => 'category',
                'id' => $category->id,
                'title' => $category->name,
                'subtitle' => $category->parent ? "Categorie · {$category->parent->name}" : 'Categorie',
                'meta' => $category->listings_count ? "{$category->listings_count} anunțuri" : null,
                'url' => route('categories.show', $category->slug),
                'image' => null,
            ]);
    }

    /** Counties and localities, each leading to the listings filtered by that county. */
    private function locations(string $term): Collection
    {
        $counties = FuzzySearch::apply(County::query(), $term, ['name'], matchAll: false)
            ->take(10)
            ->get()
            ->filter(fn (County $county) => $this->namesPlace($county->name, $term))
            ->sortByDesc(fn (County $county) => FuzzySearch::score($county->name, $term))
            ->take(2)
            ->map(fn (County $county) => [
                'type' => 'location',
                'id' => "county-{$county->id}",
                'title' => "Județul {$county->name}",
                'subtitle' => 'Anunțuri din județ',
                'url' => route('listings.index', ['county_ids' => [$county->id]]),
                'image' => null,
            ]);

        $localities = FuzzySearch::apply(Locality::query()->with('county:id,name'), $term, ['name'], matchAll: false)
            ->take(40)
            ->get()
            ->filter(fn (Locality $locality) => $this->namesPlace($locality->name, $term))
            ->sortByDesc(fn (Locality $locality) => FuzzySearch::score($locality->name, $term))
            ->take(3)
            ->map(fn (Locality $locality) => [
                'type' => 'location',
                'id' => "locality-{$locality->id}",
                'title' => $locality->name,
                'subtitle' => $locality->county ? "jud. {$locality->county->name} · anunțuri din zonă" : 'Anunțuri din zonă',
                'url' => route('listings.index', ['county_ids' => [$locality->county_id]]),
                'image' => null,
            ]);

        return $counties->concat($localities)->take(4)->values();
    }

    /**
     * Place names are matched strictly: the name must start with a whole typed
     * word, so a stem like "salo" (from "saloane") doesn't surface "Salonta".
     */
    private function namesPlace(string $name, string $term): bool
    {
        $place = FuzzySearch::normalize($name);

        return collect(explode(' ', FuzzySearch::normalize($term)))
            ->contains(fn (string $word) => strlen($word) >= 3 && str_starts_with($place, $word));
    }

    /** The app's own pages the visitor may open, matched by title and keywords. */
    private function pages(string $term, ?User $user): Collection
    {
        return collect($this->availablePages($user))
            ->map(fn (array $page) => [...$page, 'score' => FuzzySearch::score("{$page['title']} {$page['keywords']}", $term, requireAll: true)])
            // Every word must match at the start of a word of the title/keywords.
            ->filter(fn (array $page) => $page['score'] >= 3 * count(FuzzySearch::terms($term)))
            ->sortByDesc('score')
            ->take(4)
            ->map(fn (array $page) => [
                'type' => 'page',
                'id' => $page['url'],
                'title' => $page['title'],
                'subtitle' => $page['subtitle'],
                'url' => $page['url'],
                'image' => null,
            ])
            ->values();
    }

    /** @return list<array{title: string, subtitle: string, keywords: string, url: string}> */
    private function availablePages(?User $user): array
    {
        $page = fn (string $title, string $subtitle, string $keywords, string $url) => compact('title', 'subtitle', 'keywords', 'url');

        $pages = [
            $page('Toate anunțurile', 'Explorează', 'anunturi servicii oferte cauta', route('listings.index')),
            $page('Hartă anunțuri', 'Explorează', 'harta map locatie aproape', route('listings.map')),
            $page('Furnizori', 'Explorează', 'furnizori firme companii vendori', route('providers.index')),
            $page('Categorii', 'Explorează', 'categorii servicii tipuri', route('categories.index')),
            $page('Cereri de ofertă', 'Explorează', 'cereri oferta licitatie solicitari', route('quote-requests.browse')),
            $page('Abonamente', 'Pentru furnizori', 'abonamente preturi planuri tarife pachete premium', route('subscriptions.index')),
        ];

        if (! $user) {
            return [
                ...$pages,
                $page('Autentificare', 'Cont', 'login autentificare intra cont conectare', route('login')),
                $page('Creează cont', 'Cont', 'inregistrare cont nou register creeaza', route('register')),
            ];
        }

        $pages = [
            ...$pages,
            $page('Profilul meu', 'Cont', 'profil cont setari parola email date personale', route('profile.show')),
            $page('Notificări', 'Cont', 'notificari alerte', route('notifications.index')),
        ];

        if ($user->can('submit quote request')) {
            $pages[] = $page('Cere ofertă', 'Client', 'cere oferta cerere noua solicita pret', route('quote-requests.create'));
            $pages[] = $page('Cererile mele', 'Client', 'cereri oferte primite', route('quote-requests.index'));
        }
        if ($user->can('manage own favorites')) {
            $pages[] = $page('Favorite', 'Client', 'favorite salvate inimioara preferate', route('favorites.index'));
        }
        if ($user->can('save search')) {
            $pages[] = $page('Căutări salvate', 'Client', 'cautari salvate alerte', route('saved-searches.index'));
        }
        if (! $user->isProvider()) {
            $pages[] = $page('Mesaje', 'Cont', 'mesaje conversatii chat inbox', route('messages.index'));
        }

        if ($user->isProvider()) {
            $pages = [
                ...$pages,
                $page('Panou furnizor', 'Furnizor', 'dashboard panou statistici furnizor', route('provider.dashboard')),
                $page('Anunțurile mele', 'Furnizor', 'anunturi listari servicii gestionare', route('provider.listings.index')),
                $page('Adaugă anunț', 'Furnizor', 'adauga anunt nou creeaza publica', route('provider.listings.create')),
                $page('Calendar disponibilitate', 'Furnizor', 'calendar disponibilitate zile ocupate rezervari', route('provider.availability.index')),
                $page('Cereri de ofertă primite', 'Furnizor', 'cereri lead clienti oportunitati', route('provider.leads.index')),
                $page('Ofertele mele', 'Furnizor', 'oferte trimise', route('provider.offers.index')),
                $page('Recenzii', 'Furnizor', 'recenzii review rating pareri', route('provider.reviews.index')),
                $page('Profil firmă', 'Furnizor', 'profil firma companie logo descriere', route('provider.profile.edit')),
                $page('Abonamentul meu', 'Furnizor', 'abonament facturi plata plan', route('provider.subscription.index')),
                $page('Mesaje', 'Furnizor', 'mesaje conversatii chat inbox', route('provider.messages.index')),
            ];
        }

        if ($user->can('view administration')) {
            $pages = [
                ...$pages,
                $page('Administrare · Utilizatori', 'Admin', 'utilizatori useri conturi admin', url('/administration/users')),
                $page('Administrare · Roluri', 'Admin', 'roluri permisiuni admin', url('/administration/roles')),
            ];
        }
        if ($user->can('moderate providers')) {
            $pages[] = $page('Moderare furnizori', 'Admin', 'moderare furnizori aprobare admin', url('/administration/providers'));
        }
        if ($user->can('moderate quote requests')) {
            $pages[] = $page('Moderare cereri de ofertă', 'Admin', 'moderare cereri oferta admin', url('/administration/quote-requests'));
        }
        if ($user->can('moderate reviews')) {
            $pages[] = $page('Moderare recenzii', 'Admin', 'moderare recenzii admin', url('/administration/reviews'));
        }

        return $pages;
    }

    private function popularCategories(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->withCount(['listings' => fn ($query) => $query->where('status', 'published')])
            ->orderByDesc('listings_count')
            ->take(8)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category) => [
                'title' => $category->name,
                'url' => route('categories.show', $category->slug),
            ])
            ->all();
    }
}
