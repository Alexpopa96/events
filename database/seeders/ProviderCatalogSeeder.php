<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\County;
use App\Models\Invoice;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProviderCatalogSeeder extends Seeder
{
    /**
     * Major cities the demo catalog is spread across. Keys are matched
     * against county/locality names imported by LocalitySeeder.
     */
    private const CITY_KEYS = [
        'bucuresti1' => ['county' => 'Bucuresti', 'locality' => 'Sector 1'],
        'bucuresti2' => ['county' => 'Bucuresti', 'locality' => 'Sector 2'],
        'bucuresti3' => ['county' => 'Bucuresti', 'locality' => 'Sector 3'],
        'cluj' => ['county' => 'Cluj', 'locality' => 'Cluj-Napoca'],
        'timisoara' => ['county' => 'Timis', 'locality' => 'Timisoara'],
        'iasi' => ['county' => 'Iasi', 'locality' => 'Iasi'],
        'brasov' => ['county' => 'Brasov', 'locality' => 'Brasov'],
        'constanta' => ['county' => 'Constanta', 'locality' => 'Constanta'],
    ];

    /** @var array<string, array{county_id:int|null, locality_id:int|null, city:string}> */
    private array $locationCache = [];

    /** @var \Illuminate\Support\Collection<int, User> */
    private $reviewerPool;

    private int $reviewerCursor = 0;

    private int $providerIndex = 0;

    /**
     * Seeds a wide catalog of furnizori (providers) spanning every listing
     * category, so search, filters, and the admin moderation queue have
     * realistic volume to work with beyond the single demo account seeded
     * by DemoProviderSeeder.
     */
    public function run(): void
    {
        $standardPlan = SubscriptionPlan::where('slug', 'standard')->first();
        $premiumPlan = SubscriptionPlan::where('slug', 'premium')->first();
        $freePlan = SubscriptionPlan::where('slug', 'gratuit')->first();
        $plans = array_values(array_filter([$freePlan, $standardPlan, $premiumPlan]));

        $this->reviewerPool = collect(range(1, 12))->map(function (int $i) {
            $reviewer = User::firstOrCreate(
                ['email' => "recenzie{$i}@exemplu.ro"],
                [
                    'name' => $this->reviewerNames()[$i - 1],
                    'password' => Hash::make('client2026'),
                    'status' => true,
                ]
            );
            $reviewer->syncRoles(['client']);

            return $reviewer;
        });

        foreach ($this->catalog() as $slug => $config) {
            $category = Category::where('slug', $slug)->first();

            if (! $category) {
                continue;
            }

            foreach ($config['providers'] as $providerSpec) {
                $this->seedProvider($category, $providerSpec, $plans);
            }

            if (isset($config['lead'])) {
                $this->seedLead($category, $config['lead']);
            }
        }
    }

    private function seedProvider(Category $category, array $spec, array $plans): void
    {
        $this->providerIndex++;

        $companySlug = str_replace('-', '', Str::slug($spec['company']));
        $loginEmail = "{$companySlug}@evenimente.test";
        $contactEmail = "contact@{$companySlug}.ro";
        $location = $this->resolveLocation($spec['city_key']);

        // Every 6th provider is left pending, and one is put on ice
        // suspended, so the admin moderation queue isn't empty on a fresh seed.
        $isSuspended = $this->providerIndex === 33;
        $isPending = ! $isSuspended && $this->providerIndex % 6 === 0;
        $status = $isSuspended ? 'suspended' : ($isPending ? 'pending' : 'active');

        $user = User::firstOrCreate(
            ['email' => $loginEmail],
            [
                'name' => $spec['owner'],
                'password' => Hash::make('furnizor2026'),
                'phone' => $spec['phone'],
                'status' => true,
            ]
        );
        $user->syncRoles(['furnizor']);

        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $spec['company'],
                'cui' => sprintf('%08d', 40000000 + $this->providerIndex),
                'reg_com' => sprintf('J%d/%d/%d', ($this->providerIndex % 40) + 1, 1000 + $this->providerIndex, 2019 + ($this->providerIndex % 6)),
                'slug' => Str::slug($spec['company']),
                'description' => $spec['description'],
                'phone' => $spec['phone'],
                'whatsapp' => $spec['phone'],
                'email' => $contactEmail,
                'address' => $spec['address'],
                'county_id' => $location['county_id'],
                'locality_id' => $location['locality_id'],
                'social_links' => [
                    'instagram' => 'https://instagram.com/'.$companySlug,
                    'facebook' => 'https://facebook.com/'.$companySlug,
                ],
                'status' => $status,
                'approved_at' => $status === 'pending' ? null : now()->subDays(random_int(5, 400)),
                'suspension_reason' => $isSuspended ? 'Reclamații repetate din partea clienților privind lipsa de răspuns.' : null,
                'suspended_at' => $isSuspended ? now()->subDays(random_int(1, 20)) : null,
            ]
        );
        $profile->update(['profile_completion_score' => $profile->calculateProfileCompletionScore()]);

        if ($status !== 'pending' && ! empty($plans)) {
            $plan = $plans[$this->providerIndex % count($plans)];

            $subscription = ProviderSubscription::firstOrCreate(
                ['provider_profile_id' => $profile->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'status' => $isSuspended ? 'canceled' : 'active',
                    'starts_at' => now()->subDays(random_int(5, 90)),
                    'ends_at' => now()->addDays(random_int(5, 60)),
                ]
            );

            if ((float) $plan->price > 0) {
                Invoice::firstOrCreate(
                    ['number' => sprintf('INV-2026-%04d', 100 + $this->providerIndex)],
                    [
                        'provider_profile_id' => $profile->id,
                        'provider_subscription_id' => $subscription->id,
                        'amount' => $plan->price,
                        'currency' => $plan->currency,
                        'status' => 'paid',
                        'issued_at' => now()->subDays(random_int(6, 91)),
                        'paid_at' => now()->subDays(random_int(6, 91)),
                    ]
                );
            }
        }

        $listingStatus = match ($status) {
            'pending' => 'pending_review',
            default => 'published',
        };

        $listings = collect($spec['listings'])->map(fn (array $listing) => Listing::firstOrCreate(
            ['slug' => Str::slug($spec['company'].' '.$listing['title'])],
            [
                'provider_profile_id' => $profile->id,
                'category_id' => $category->id,
                'title' => $listing['title'],
                'description' => $listing['description'],
                'price_type' => $listing['price_type'],
                'price_from' => $listing['price_from'] ?? null,
                'price_to' => $listing['price_to'] ?? null,
                'currency' => 'RON',
                'county_id' => $location['county_id'],
                'locality_id' => $location['locality_id'],
                'status' => $listingStatus,
                'published_at' => $listingStatus === 'published' ? now()->subDays(random_int(1, 120)) : null,
            ]
        ));

        if ($listingStatus === 'published') {
            $listings->each(fn (Listing $listing) => $this->seedReviews($listing, $profile));
        }
    }

    private function seedReviews(Listing $listing, ProviderProfile $profile): void
    {
        if (Review::where('listing_id', $listing->id)->exists()) {
            return;
        }

        $comments = $this->reviewComments();
        $reviewCount = random_int(1, 3);
        $poolSize = $this->reviewerPool->count();

        for ($i = 0; $i < $reviewCount; $i++) {
            $reviewer = $this->reviewerPool[$this->reviewerCursor % $poolSize];
            $this->reviewerCursor++;

            $comment = $comments[array_rand($comments)];

            $review = Review::firstOrCreate(
                ['listing_id' => $listing->id, 'user_id' => $reviewer->id],
                [
                    'provider_profile_id' => $profile->id,
                    'rating' => $comment['rating'],
                    'comment' => $comment['text'],
                    'status' => 'approved',
                ]
            );

            $review->forceFill([
                'created_at' => now()->subDays(random_int(1, 60)),
                'updated_at' => now()->subDays(random_int(1, 60)),
            ])->save();
        }
    }

    private function seedLead(Category $category, array $lead): void
    {
        $location = $this->resolveLocation($lead['city_key']);

        QuoteRequest::firstOrCreate(
            ['email' => $lead['email']],
            [
                'category_id' => $category->id,
                'title' => $lead['title'],
                'name' => $lead['name'],
                'phone' => $lead['phone'],
                'event_date' => now()->addDays(random_int(20, 180))->toDateString(),
                'event_type' => $lead['event_type'],
                'county_id' => $location['county_id'],
                'locality_id' => $location['locality_id'],
                'city' => $location['city'],
                'county' => $lead['county_label'],
                'guest_count' => $lead['guest_count'] ?? null,
                'budget_range' => $lead['budget_range'] ?? null,
                'message' => $lead['message'],
                'status' => 'open',
            ]
        );
    }

    private function resolveLocation(string $key): array
    {
        if (isset($this->locationCache[$key])) {
            return $this->locationCache[$key];
        }

        $definition = self::CITY_KEYS[$key];

        $county = County::where('name', $definition['county'])->first();
        $locality = $county
            ? Locality::where('county_id', $county->id)->where('name', $definition['locality'])->first()
            : null;

        return $this->locationCache[$key] = [
            'county_id' => $county?->id,
            'locality_id' => $locality?->id,
            'city' => $definition['locality'],
        ];
    }

    private function reviewerNames(): array
    {
        return [
            'Maria Ionescu', 'Andrei Popa', 'Ioana Dumitru', 'Cristian Rusu',
            'Alexandra Stan', 'Gabriel Munteanu', 'Simona Constantin', 'Vlad Ciobanu',
            'Raluca Enache', 'Bogdan Florea', 'Diana Neagu', 'Mihai Barbu',
        ];
    }

    private function reviewComments(): array
    {
        return [
            ['rating' => 5, 'text' => 'Servicii impecabile, comunicare rapidă și rezultat exact cum ne-am dorit. Recomandăm cu toată încrederea!'],
            ['rating' => 5, 'text' => 'Profesionalism de la primul mesaj până la eveniment. Nu am avut nicio grijă în ziua evenimentului.'],
            ['rating' => 4, 'text' => 'Foarte mulțumiți de colaborare, au fost punctuali și organizați. Am fi vrut puțin mai multă flexibilitate pe buget.'],
            ['rating' => 5, 'text' => 'Exact ce ne-am dorit pentru evenimentul nostru. Vom reveni cu siguranță și la alte ocazii.'],
            ['rating' => 4, 'text' => 'Calitate foarte bună raportată la preț, echipă amabilă. Recomand pentru oricine caută seriozitate.'],
            ['rating' => 3, 'text' => 'În general ok, dar comunicarea în ultima săptămână înainte de eveniment ar putea fi îmbunătățită.'],
            ['rating' => 5, 'text' => 'Invitații noștri încă vorbesc despre cât de bine a fost organizat totul. Mulțumim mult!'],
            ['rating' => 4, 'text' => 'Recomand — au venit cu idei suplimentare la care nici nu ne gândeam și au respectat bugetul stabilit.'],
        ];
    }

    /**
     * Category slug => providers + one sample lead. Two providers per
     * high-demand category, one per niche category, spread across cities.
     */
    private function catalog(): array
    {
        return [
            'fotograf' => [
                'providers' => [
                    [
                        'owner' => 'Elena Vasilescu', 'company' => 'Studio Lumina Foto', 'city_key' => 'cluj',
                        'phone' => '0722334455', 'address' => 'Strada Napoca 15',
                        'description' => 'Studio foto specializat în nunți și botezuri, cu editare rapidă și livrare în galerie online privată.',
                        'listings' => [
                            ['title' => 'Pachet foto nuntă premium', 'description' => 'Fotograf principal + asistent, drone la cerere, album foto inclus.', 'price_type' => 'starting_from', 'price_from' => 4200],
                            ['title' => 'Ședință foto botez', 'description' => 'Acoperire completă a evenimentului, poze editate livrate în 10 zile.', 'price_type' => 'fixed', 'price_from' => 900],
                        ],
                    ],
                    [
                        'owner' => 'Radu Marin', 'company' => 'Radu Marin Photography', 'city_key' => 'bucuresti2',
                        'phone' => '0733445566', 'address' => 'Bulevardul Pache Protopopescu 44',
                        'description' => 'Fotografie de evenimente corporate și private, stil modern, editare cinematică.',
                        'listings' => [
                            ['title' => 'Pachet foto corporate', 'description' => 'Acoperire conferințe, gale și team building-uri, livrare rapidă pentru presă.', 'price_type' => 'per_hour', 'price_from' => 300],
                            ['title' => 'Foto majorat / aniversare', 'description' => '3 ore de fotografiat, 100+ poze editate, print opțional.', 'price_type' => 'fixed', 'price_from' => 1100],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Fotograf pentru nuntă în septembrie', 'name' => 'Vlad și Ioana', 'email' => 'vlad.ioana@exemplu.ro',
                    'phone' => '0755112233', 'event_type' => 'nunta', 'city_key' => 'cluj', 'county_label' => 'Cluj',
                    'guest_count' => '120', 'budget_range' => '4000 - 6000 RON',
                    'message' => 'Căutăm fotograf pentru nuntă în septembrie, la o locație lângă Cluj. Ne dorim stil natural, fără poze foarte regizate.',
                ],
            ],
            'videograf' => [
                'providers' => [
                    [
                        'owner' => 'Andrei Toma', 'company' => 'CineStory Films', 'city_key' => 'bucuresti1',
                        'phone' => '0744556677', 'address' => 'Șoseaua Kiseleff 22',
                        'description' => 'Filmări cinematice pentru nunți și evenimente corporate, montaj profesional cu dronă inclusă.',
                        'listings' => [
                            ['title' => 'Film de nuntă cinematic', 'description' => 'Filmare pe toată durata evenimentului, highlight de 5 minute + film complet.', 'price_type' => 'starting_from', 'price_from' => 3800],
                            ['title' => 'Video corporate / promo', 'description' => 'Filmare și montaj pentru materiale de prezentare companie.', 'price_type' => 'on_request'],
                        ],
                    ],
                    [
                        'owner' => 'Daniel Pop', 'company' => 'Timișoara Video Weddings', 'city_key' => 'timisoara',
                        'phone' => '0755667788', 'address' => 'Strada Circumvalațiunii 12',
                        'description' => 'Echipă de videografi specializată în filmări de nuntă în stil documentar, fără scenarii regizate.',
                        'listings' => [
                            ['title' => 'Pachet filmare nuntă full day', 'description' => 'De la pregătiri până la petrecere, livrare film în 3 săptămâni.', 'price_type' => 'starting_from', 'price_from' => 3200],
                            ['title' => 'Trailer eveniment 2 minute', 'description' => 'Montaj scurt pentru social media, livrat în 48 de ore.', 'price_type' => 'fixed', 'price_from' => 500],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Videograf pentru cununie civilă', 'name' => 'Cosmin D.', 'email' => 'cosmin.d@exemplu.ro',
                    'phone' => '0766223344', 'event_type' => 'cununie', 'city_key' => 'timisoara', 'county_label' => 'Timis',
                    'budget_range' => '1500 - 2500 RON',
                    'message' => 'Avem nevoie de un videograf doar pentru cununia civilă, aproximativ 2 ore, în centrul Timișoarei.',
                ],
            ],
            'dj' => [
                'providers' => [
                    [
                        'owner' => 'Cătălin Dobre', 'company' => 'DJ Vibe Events', 'city_key' => 'bucuresti3',
                        'phone' => '0722998877', 'address' => 'Calea Călărașilor 90',
                        'description' => 'DJ cu peste 10 ani de experiență la nunți și petreceri corporate, sonorizare proprie inclusă.',
                        'listings' => [
                            ['title' => 'DJ + sonorizare petrecere nuntă', 'description' => 'Muzică pe tot parcursul petrecerii, lumini de bază incluse.', 'price_type' => 'starting_from', 'price_from' => 2200],
                            ['title' => 'DJ eveniment corporate', 'description' => 'Playlist personalizat, echipament pentru sală de până la 200 persoane.', 'price_type' => 'per_hour', 'price_from' => 350],
                        ],
                    ],
                    [
                        'owner' => 'Andrei Costea', 'company' => 'DJ Andrei Sound', 'city_key' => 'cluj',
                        'phone' => '0733112244', 'address' => 'Strada Dorobanților 60',
                        'description' => 'DJ și MC în același pachet pentru nunți și majorate, mixaj live adaptat publicului.',
                        'listings' => [
                            ['title' => 'Pachet DJ + MC nuntă', 'description' => 'Coordonare program eveniment + muzică pe toată durata petrecerii.', 'price_type' => 'starting_from', 'price_from' => 2800],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'DJ pentru majorat 18 ani', 'name' => 'Familia Radu', 'email' => 'familia.radu@exemplu.ro',
                    'phone' => '0788112233', 'event_type' => 'majorat', 'city_key' => 'cluj', 'county_label' => 'Cluj',
                    'guest_count' => '80', 'budget_range' => '1500 - 2500 RON',
                    'message' => 'Căutăm DJ pentru majorat, 80 de invitați, sală cu sonorizare proprie deja instalată, doar nevoie de DJ.',
                ],
            ],
            'formatie' => [
                'providers' => [
                    [
                        'owner' => 'Costel Amariei', 'company' => 'Trupa Astral', 'city_key' => 'iasi',
                        'phone' => '0744223311', 'address' => 'Strada Lăpușneanu 33',
                        'description' => 'Formație live cu solist și solistă, repertoriu variat: populară, muzică ușoară, cover-uri internaționale.',
                        'listings' => [
                            ['title' => 'Formație live nuntă — 6 ore', 'description' => 'Solist, solistă, 3 instrumentiști, sonorizare și lumini incluse.', 'price_type' => 'fixed', 'price_from' => 6500],
                        ],
                    ],
                    [
                        'owner' => 'Mihai Suciu', 'company' => 'Formația Retro Live', 'city_key' => 'brasov',
                        'phone' => '0755334422', 'address' => 'Strada Republicii 21',
                        'description' => 'Formație specializată în muzică retro și cover-uri anii 80-2000 pentru evenimente private.',
                        'listings' => [
                            ['title' => 'Concert live evenimente private', 'description' => 'Repertoriu retro, 2 seturi a câte 45 minute, echipament propriu.', 'price_type' => 'starting_from', 'price_from' => 4500],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Formație pentru nuntă cu 150 invitați', 'name' => 'Andreea P.', 'email' => 'andreea.p@exemplu.ro',
                    'phone' => '0722556677', 'event_type' => 'nunta', 'city_key' => 'iasi', 'county_label' => 'Iasi',
                    'guest_count' => '150', 'budget_range' => '5000 - 8000 RON',
                    'message' => 'Căutăm formație live pentru nuntă, preferăm mix de muzică populară și ușoară, eveniment în Iași.',
                ],
            ],
            'mc' => [
                'providers' => [
                    [
                        'owner' => 'Bogdan Tase', 'company' => 'Vocea Evenimentelor - Bogdan Tase', 'city_key' => 'bucuresti1',
                        'phone' => '0733889900', 'address' => 'Strada Occidentului 8',
                        'description' => 'Prezentator de evenimente cu experiență la nunți, gale corporate și lansări de produse.',
                        'listings' => [
                            ['title' => 'Prezentare eveniment nuntă', 'description' => 'Coordonare program, jocuri și momente speciale, comunicare directă cu ceilalți furnizori.', 'price_type' => 'fixed', 'price_from' => 1800],
                            ['title' => 'Moderare gală corporate', 'description' => 'Prezentare profesionistă pentru gale, premii și conferințe.', 'price_type' => 'per_hour', 'price_from' => 400],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'MC pentru cununie religioasă și petrecere', 'name' => 'Ștefan M.', 'email' => 'stefan.m@exemplu.ro',
                    'phone' => '0744998811', 'event_type' => 'nunta', 'city_key' => 'bucuresti1', 'county_label' => 'Bucuresti',
                    'budget_range' => '1500 - 2000 RON',
                    'message' => 'Avem nevoie de un maestru de ceremonii care să coordoneze atât cununia cât și petrecerea de după.',
                ],
            ],
            'wedding-planner' => [
                'providers' => [
                    [
                        'owner' => 'Larisa Man', 'company' => 'Nunta Perfectă Events', 'city_key' => 'cluj',
                        'phone' => '0722113355', 'address' => 'Strada Memorandumului 5',
                        'description' => 'Organizare completă de nunți, de la buget și furnizori până la coordonare în ziua evenimentului.',
                        'listings' => [
                            ['title' => 'Organizare completă nuntă', 'description' => 'Planificare de la zero: buget, furnizori, decor, coordonare pe zi.', 'price_type' => 'starting_from', 'price_from' => 8000],
                            ['title' => 'Coordonare doar în ziua evenimentului', 'description' => 'Preluăm toate detaliile organizate de voi și coordonăm timeline-ul zilei.', 'price_type' => 'fixed', 'price_from' => 2200],
                        ],
                    ],
                    [
                        'owner' => 'Corina Alexe', 'company' => 'Elegance Wedding Planners', 'city_key' => 'bucuresti2',
                        'phone' => '0733225577', 'address' => 'Strada Traian 40',
                        'description' => 'Echipă de wedding planneri pentru nunți elegante, cu parteneriate directe cu locații premium.',
                        'listings' => [
                            ['title' => 'Pachet organizare nuntă premium', 'description' => 'Concept, decor, furnizori și coordonare completă pentru nunți de peste 100 invitați.', 'price_type' => 'starting_from', 'price_from' => 9500],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Wedding planner pentru nuntă în 2027', 'name' => 'Georgiana și Paul', 'email' => 'georgiana.paul@exemplu.ro',
                    'phone' => '0755223344', 'event_type' => 'nunta', 'city_key' => 'bucuresti2', 'county_label' => 'Bucuresti',
                    'guest_count' => '180', 'budget_range' => '8000 - 12000 RON',
                    'message' => 'Suntem la început de drum cu planificarea nunții și avem nevoie de sprijin complet, de la buget la furnizori.',
                ],
            ],
            'restaurant' => [
                'providers' => [
                    [
                        'owner' => 'Familia Croitoru', 'company' => 'Restaurant Casa Boierească', 'city_key' => 'iasi',
                        'phone' => '0744332211', 'address' => 'Șoseaua Bucium 12',
                        'description' => 'Restaurant cu grădină și salon interior, meniuri personalizate pentru nunți și botezuri.',
                        'listings' => [
                            ['title' => 'Meniu nuntă — clasic', 'description' => 'Meniu 4 feluri + desert, băuturi incluse, capacitate până la 250 persoane.', 'price_type' => 'per_hour', 'price_from' => 180],
                            ['title' => 'Închiriere salon pentru botez', 'description' => 'Salon privat, decor de bază inclus, meniu personalizabil.', 'price_type' => 'starting_from', 'price_from' => 3500],
                        ],
                    ],
                    [
                        'owner' => 'George Manea', 'company' => 'La Terasă Events', 'city_key' => 'constanta',
                        'phone' => '0755443322', 'address' => 'Bulevardul Mamaia 145',
                        'description' => 'Restaurant cu terasă la malul mării, potrivit pentru nunți de vară și evenimente corporate.',
                        'listings' => [
                            ['title' => 'Eveniment pe terasă vedere la mare', 'description' => 'Capacitate 150 persoane, meniu la alegere, muzică live permisă.', 'price_type' => 'starting_from', 'price_from' => 4200],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Locație restaurant pentru botez', 'name' => 'Familia Iancu', 'email' => 'familia.iancu@exemplu.ro',
                    'phone' => '0733556644', 'event_type' => 'botez', 'city_key' => 'iasi', 'county_label' => 'Iasi',
                    'guest_count' => '60', 'budget_range' => '3000 - 4500 RON',
                    'message' => 'Căutăm restaurant cu salon privat pentru botez, 60 de invitați, undeva în Iași sau împrejurimi.',
                ],
            ],
            'salon-evenimente' => [
                'providers' => [
                    [
                        'owner' => 'Ovidiu Barna', 'company' => 'Salon Regal Events', 'city_key' => 'timisoara',
                        'phone' => '0744556611', 'address' => 'Calea Șagului 100',
                        'description' => 'Sală de evenimente modernă, capacitate flexibilă, parcare proprie și zonă de grădină.',
                        'listings' => [
                            ['title' => 'Închiriere sală nuntă', 'description' => 'Capacitate până la 300 persoane, scenă, ring de dans, parcare inclusă.', 'price_type' => 'starting_from', 'price_from' => 5000],
                        ],
                    ],
                    [
                        'owner' => 'Adina Roman', 'company' => 'Grand Ballroom Brasov', 'city_key' => 'brasov',
                        'phone' => '0755667722', 'address' => 'Strada Lungă 88',
                        'description' => 'Sală elegantă în stil clasic, ideală pentru nunți și gale corporate de amploare.',
                        'listings' => [
                            ['title' => 'Închiriere Grand Ballroom', 'description' => 'Capacitate 400 persoane, sistem sonorizare inclus, opțional catering partener.', 'price_type' => 'starting_from', 'price_from' => 6000],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Sală de evenimente pentru 200 persoane', 'name' => 'Denis V.', 'email' => 'denis.v@exemplu.ro',
                    'phone' => '0744778899', 'event_type' => 'nunta', 'city_key' => 'timisoara', 'county_label' => 'Timis',
                    'guest_count' => '200', 'budget_range' => '5000 - 7000 RON',
                    'message' => 'Căutăm sală pentru nuntă de aproximativ 200 de persoane, cu parcare și acces facil.',
                ],
            ],
            'decor' => [
                'providers' => [
                    [
                        'owner' => 'Iulia Necula', 'company' => 'Decor de Poveste', 'city_key' => 'bucuresti3',
                        'phone' => '0733998822', 'address' => 'Strada Vitan 55',
                        'description' => 'Amenajări florale și decor tematic pentru nunți, botezuri și evenimente corporate.',
                        'listings' => [
                            ['title' => 'Decor complet sală nuntă', 'description' => 'Aranjamente flori, textile, lumânări și decor masă prezidențială.', 'price_type' => 'starting_from', 'price_from' => 3800],
                            ['title' => 'Decor tematic botez', 'description' => 'Decor personalizat pe tema aleasă, baloane și aranjamente florale.', 'price_type' => 'fixed', 'price_from' => 1200],
                        ],
                    ],
                    [
                        'owner' => 'Teodora Pascu', 'company' => 'Ambient Decor Studio', 'city_key' => 'cluj',
                        'phone' => '0722667799', 'address' => 'Strada Fabricii 30',
                        'description' => 'Studio de decor cu propriu inventar de mobilier vintage și instalații florale suspendate.',
                        'listings' => [
                            ['title' => 'Instalație florală suspendată', 'description' => 'Design personalizat pentru sală, montaj și demontaj incluse.', 'price_type' => 'on_request'],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Decor pentru nuntă tematică', 'name' => 'Roxana T.', 'email' => 'roxana.t@exemplu.ro',
                    'phone' => '0766889911', 'event_type' => 'nunta', 'city_key' => 'bucuresti3', 'county_label' => 'Bucuresti',
                    'budget_range' => '3000 - 5000 RON',
                    'message' => 'Vrem un decor boho pentru nunta noastră de vară, în grădină. Aveți portofoliu pentru stilul acesta?',
                ],
            ],
            'florist' => [
                'providers' => [
                    [
                        'owner' => 'Camelia Ilie', 'company' => 'Florăria Trandafirul de Aur', 'city_key' => 'iasi',
                        'phone' => '0744112288', 'address' => 'Strada Palas 3',
                        'description' => 'Florărie cu tradiție, buchete de mireasă și aranjamente florale pentru orice tip de eveniment.',
                        'listings' => [
                            ['title' => 'Buchet de mireasă personalizat', 'description' => 'Design pe baza paletei de culori a evenimentului, flori proaspete.', 'price_type' => 'starting_from', 'price_from' => 450],
                            ['title' => 'Aranjamente florale sală', 'description' => 'Flori de masă, arcadă florală și decor scenă.', 'price_type' => 'starting_from', 'price_from' => 2000],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Florist pentru buchet mireasă și aranjamente', 'name' => 'Elena C.', 'email' => 'elena.c@exemplu.ro',
                    'phone' => '0733112255', 'event_type' => 'nunta', 'city_key' => 'iasi', 'county_label' => 'Iasi',
                    'budget_range' => '2000 - 3000 RON',
                    'message' => 'Căutăm florist pentru buchet de mireasă și câteva aranjamente pentru masa prezidențială și arcadă.',
                ],
            ],
            'torturi' => [
                'providers' => [
                    [
                        'owner' => 'Simona Achim', 'company' => 'Cofetăria Dulce Vis', 'city_key' => 'bucuresti1',
                        'phone' => '0722445588', 'address' => 'Strada Aviatorilor 17',
                        'description' => 'Cofetărie artizanală, torturi personalizate și candy bar pentru nunți și botezuri.',
                        'listings' => [
                            ['title' => 'Tort de nuntă personalizat', 'description' => 'Design pe temă, minimum 4 etaje, degustare inclusă în comandă.', 'price_type' => 'starting_from', 'price_from' => 1500],
                            ['title' => 'Tort botez cu tematică', 'description' => 'Design personalizat, capacitate pentru 50 de porții.', 'price_type' => 'fixed', 'price_from' => 550],
                        ],
                    ],
                    [
                        'owner' => 'Alina Dragomir', 'company' => 'Tort de Poveste', 'city_key' => 'timisoara',
                        'phone' => '0755998833', 'address' => 'Strada Ștefan cel Mare 25',
                        'description' => 'Cofetărie specializată în torturi tematice pentru copii și evenimente private.',
                        'listings' => [
                            ['title' => 'Tort tematic pentru copii', 'description' => 'Design personalizat după tema petrecerii, ingrediente naturale.', 'price_type' => 'starting_from', 'price_from' => 400],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Tort de nuntă pentru 150 persoane', 'name' => 'Dan și Cristina', 'email' => 'dan.cristina@exemplu.ro',
                    'phone' => '0744556699', 'event_type' => 'nunta', 'city_key' => 'bucuresti1', 'county_label' => 'Bucuresti',
                    'guest_count' => '150', 'budget_range' => '1200 - 2000 RON',
                    'message' => 'Căutăm un tort de nuntă elegant, în nuanțe pastelate, pentru aproximativ 150 de porții.',
                ],
            ],
            'candy-bar' => [
                'providers' => [
                    [
                        'owner' => 'Miruna Stoica', 'company' => 'Sweet Corner Candy Bar', 'city_key' => 'cluj',
                        'phone' => '0733667744', 'address' => 'Strada Horea 50',
                        'description' => 'Candy bar tematic cu dulciuri artizanale, personalizat pentru culorile și tema evenimentului.',
                        'listings' => [
                            ['title' => 'Candy bar complet — 100 invitați', 'description' => 'Prăjiturele, macarons, cake pops și decor tematic pentru masa de dulciuri.', 'price_type' => 'starting_from', 'price_from' => 1800],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Candy bar pentru botez', 'name' => 'Familia Toma', 'email' => 'familia.toma@exemplu.ro',
                    'phone' => '0722889933', 'event_type' => 'botez', 'city_key' => 'cluj', 'county_label' => 'Cluj',
                    'guest_count' => '70', 'budget_range' => '1000 - 1500 RON',
                    'message' => 'Dorim un candy bar în nuanțe de roz și auriu pentru botezul fetiței noastre, 70 de invitați.',
                ],
            ],
            'cabina-foto' => [
                'providers' => [
                    [
                        'owner' => 'Alex Marinescu', 'company' => 'PhotoBooth Fun', 'city_key' => 'bucuresti2',
                        'phone' => '0744887722', 'address' => 'Strada Vaselor 14',
                        'description' => 'Cabină foto cu recuzită și printare instant, perfectă pentru nunți și petreceri corporate.',
                        'listings' => [
                            ['title' => 'Cabină foto 4 ore', 'description' => 'Recuzită inclusă, printuri nelimitate, album de oaspeți cadou.', 'price_type' => 'fixed', 'price_from' => 1300],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Cabină foto pentru petrecere corporate', 'name' => 'HR Events SRL', 'email' => 'hr.events@exemplu.ro',
                    'phone' => '0755112277', 'event_type' => 'corporate', 'city_key' => 'bucuresti2', 'county_label' => 'Bucuresti',
                    'guest_count' => '100', 'budget_range' => '1000 - 1500 RON',
                    'message' => 'Organizăm o petrecere corporate de sfârșit de an și dorim o cabină foto pentru aproximativ 4 ore.',
                ],
            ],
            'cabina-360' => [
                'providers' => [
                    [
                        'owner' => 'Robert Ene', 'company' => '360 Video Booth Events', 'city_key' => 'constanta',
                        'phone' => '0733224477', 'address' => 'Bulevardul Tomis 200',
                        'description' => 'Cabină video 360° cu efecte slow-motion, editare rapidă pentru distribuire pe social media.',
                        'listings' => [
                            ['title' => 'Cabină 360 — 3 ore', 'description' => 'Platformă rotativă, lumini LED, videoclipuri gata în câteva minute.', 'price_type' => 'fixed', 'price_from' => 1600],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Cabină 360 pentru nuntă de vară', 'name' => 'Bianca R.', 'email' => 'bianca.r@exemplu.ro',
                    'phone' => '0744223399', 'event_type' => 'nunta', 'city_key' => 'constanta', 'county_label' => 'Constanta',
                    'budget_range' => '1200 - 1800 RON',
                    'message' => 'Ne dorim o cabină 360 la petrecerea de nuntă, pentru aproximativ 3-4 ore, la malul mării.',
                ],
            ],
            'limuzine' => [
                'providers' => [
                    [
                        'owner' => 'Cristian Voicu', 'company' => 'Prestige Limo Rentals', 'city_key' => 'bucuresti1',
                        'phone' => '0722115599', 'address' => 'Șoseaua Nordului 30',
                        'description' => 'Flotă de limuzine și mașini de epocă pentru nunți, decorate la cerere.',
                        'listings' => [
                            ['title' => 'Limuzină albă — 3 ore', 'description' => 'Șofer inclus, decor floral opțional, capacitate 6 persoane.', 'price_type' => 'fixed', 'price_from' => 1400],
                        ],
                    ],
                    [
                        'owner' => 'Tudor Ilea', 'company' => 'Limuzine VIP Cluj', 'city_key' => 'cluj',
                        'phone' => '0733556699', 'address' => 'Strada Constanța 10',
                        'description' => 'Închirieri auto premium pentru nunți și evenimente corporate în zona Cluj.',
                        'listings' => [
                            ['title' => 'Mașină epocă pentru nuntă', 'description' => 'Model clasic, șofer în costum, decor personalizabil.', 'price_type' => 'starting_from', 'price_from' => 1200],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Limuzină pentru ziua nunții', 'name' => 'Adrian S.', 'email' => 'adrian.s@exemplu.ro',
                    'phone' => '0755337788', 'event_type' => 'nunta', 'city_key' => 'bucuresti1', 'county_label' => 'Bucuresti',
                    'budget_range' => '1000 - 1500 RON',
                    'message' => 'Avem nevoie de o limuzină pentru mire și mireasă, traseu de aproximativ 2 ore în ziua nunții.',
                ],
            ],
            'sonorizare' => [
                'providers' => [
                    [
                        'owner' => 'Marius Neagoe', 'company' => 'SoundPro Events', 'city_key' => 'timisoara',
                        'phone' => '0744663322', 'address' => 'Strada Take Ionescu 8',
                        'description' => 'Sonorizare profesională pentru evenimente outdoor și indoor, tehnician prezent pe toată durata.',
                        'listings' => [
                            ['title' => 'Sonorizare eveniment în aer liber', 'description' => 'Sistem audio pentru până la 300 persoane, microfoane wireless incluse.', 'price_type' => 'starting_from', 'price_from' => 1800],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Sonorizare pentru conferință', 'name' => 'TechConf SRL', 'email' => 'techconf@exemplu.ro',
                    'phone' => '0733889922', 'event_type' => 'corporate', 'city_key' => 'timisoara', 'county_label' => 'Timis',
                    'guest_count' => '250', 'budget_range' => '2000 - 3000 RON',
                    'message' => 'Organizăm o conferință de o zi și avem nevoie de sonorizare profesională cu microfoane pentru Q&A.',
                ],
            ],
            'lumini' => [
                'providers' => [
                    [
                        'owner' => 'Sebastian Iordan', 'company' => 'LightShow Design', 'city_key' => 'brasov',
                        'phone' => '0722998811', 'address' => 'Strada Mihail Kogălniceanu 19',
                        'description' => 'Lumini arhitecturale și efecte speciale pentru nunți, concerte și evenimente corporate.',
                        'listings' => [
                            ['title' => 'Lumini ambientale sală nuntă', 'description' => 'Instalație lumini calde + proiecție monogramă pe ringul de dans.', 'price_type' => 'starting_from', 'price_from' => 1500],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Lumini decorative pentru sală de nuntă', 'name' => 'Paula G.', 'email' => 'paula.g@exemplu.ro',
                    'phone' => '0744227788', 'event_type' => 'nunta', 'city_key' => 'brasov', 'county_label' => 'Brasov',
                    'budget_range' => '1200 - 2000 RON',
                    'message' => 'Vrem lumini ambientale calde și o proiecție cu inițialele noastre pe ringul de dans.',
                ],
            ],
            'machiaj' => [
                'providers' => [
                    [
                        'owner' => 'Ioana Dobrescu', 'company' => 'Makeup by Ioana', 'city_key' => 'bucuresti3',
                        'phone' => '0733225588', 'address' => 'Strada Ion Câmpineanu 9',
                        'description' => 'Makeup artist specializat în machiaj de mireasă, testare inclusă înainte de eveniment.',
                        'listings' => [
                            ['title' => 'Machiaj mireasă + testare', 'description' => 'Ședință de probă + machiaj în ziua nunții, produse hipoalergenice.', 'price_type' => 'fixed', 'price_from' => 700],
                        ],
                    ],
                    [
                        'owner' => 'Bianca Oros', 'company' => 'Glam Studio Cluj', 'city_key' => 'cluj',
                        'phone' => '0755334411', 'address' => 'Strada Regele Ferdinand 15',
                        'description' => 'Studio de makeup și styling pentru mirese și invitate, deplasare la locație disponibilă.',
                        'listings' => [
                            ['title' => 'Machiaj eveniment + deplasare', 'description' => 'Machiaj profesional la locație, pentru mireasă și domnișoare de onoare.', 'price_type' => 'starting_from', 'price_from' => 250],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Machiaj mireasă și domnișoare de onoare', 'name' => 'Alexandra M.', 'email' => 'alexandra.m@exemplu.ro',
                    'phone' => '0722556688', 'event_type' => 'nunta', 'city_key' => 'cluj', 'county_label' => 'Cluj',
                    'budget_range' => '700 - 1200 RON',
                    'message' => 'Căutăm makeup artist pentru mine și 3 domnișoare de onoare, cu deplasare la locația din Cluj.',
                ],
            ],
            'coafura' => [
                'providers' => [
                    [
                        'owner' => 'Anca Feraru', 'company' => 'Hair Style Events', 'city_key' => 'iasi',
                        'phone' => '0744331177', 'address' => 'Strada Anastasie Panu 22',
                        'description' => 'Coafeză specializată în coafuri de mireasă și styling pentru evenimente, deplasare la domiciliu.',
                        'listings' => [
                            ['title' => 'Coafură mireasă + probă', 'description' => 'Ședință de probă cu 2 săptămâni înainte + coafură în ziua evenimentului.', 'price_type' => 'fixed', 'price_from' => 500],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Coafură pentru nuntă cu deplasare', 'name' => 'Larisa D.', 'email' => 'larisa.d@exemplu.ro',
                    'phone' => '0733667711', 'event_type' => 'nunta', 'city_key' => 'iasi', 'county_label' => 'Iasi',
                    'budget_range' => '400 - 700 RON',
                    'message' => 'Am nevoie de coafeză cu deplasare la locația din Iași, pentru dimineața nunții.',
                ],
            ],
            'invitatii' => [
                'providers' => [
                    [
                        'owner' => 'Diana Coman', 'company' => 'Invitații Personalizate Chic', 'city_key' => 'bucuresti2',
                        'phone' => '0722445511', 'address' => 'Strada Popa Savu 3',
                        'description' => 'Design și tipar invitații personalizate pentru nunți, botezuri și evenimente corporate.',
                        'listings' => [
                            ['title' => 'Invitații nuntă personalizate — set 100', 'description' => 'Design la alegere, tipar premium, plicuri incluse.', 'price_type' => 'fixed', 'price_from' => 800],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Invitații personalizate pentru nuntă', 'name' => 'Oana și Sergiu', 'email' => 'oana.sergiu@exemplu.ro',
                    'phone' => '0744998822', 'event_type' => 'nunta', 'city_key' => 'bucuresti2', 'county_label' => 'Bucuresti',
                    'guest_count' => '120', 'budget_range' => '700 - 1000 RON',
                    'message' => 'Căutăm un design elegant, minimalist, pentru aproximativ 120 de invitații de nuntă.',
                ],
            ],
            'cazare' => [
                'providers' => [
                    [
                        'owner' => 'Familia Zamfir', 'company' => 'Pensiunea Conacul Regal', 'city_key' => 'brasov',
                        'phone' => '0733554422', 'address' => 'Strada Poiana Brașov 5',
                        'description' => 'Pensiune cu 20 de camere, aproape de principalele săli de evenimente din Brașov.',
                        'listings' => [
                            ['title' => 'Cazare grup nuntă — 20 camere', 'description' => 'Pachet cazare pentru invitați, mic dejun inclus, tarif preferențial la rezervare de grup.', 'price_type' => 'per_hour', 'price_from' => 220],
                        ],
                    ],
                    [
                        'owner' => 'Cristina Barbu', 'company' => 'Hotel Boutique Constanța', 'city_key' => 'constanta',
                        'phone' => '0744665533', 'address' => 'Strada Ștefan cel Mare 100',
                        'description' => 'Hotel boutique la malul mării, potrivit pentru cazarea invitaților la nunți de vară.',
                        'listings' => [
                            ['title' => 'Cazare invitați nuntă la malul mării', 'description' => 'Camere duble și triple, mic dejun inclus, discount pentru rezervări de grup.', 'price_type' => 'per_hour', 'price_from' => 280],
                        ],
                    ],
                ],
                'lead' => [
                    'title' => 'Cazare pentru invitați din afara orașului', 'name' => 'Mihaela V.', 'email' => 'mihaela.v@exemplu.ro',
                    'phone' => '0755448822', 'event_type' => 'nunta', 'city_key' => 'brasov', 'county_label' => 'Brasov',
                    'guest_count' => '30', 'budget_range' => '200 - 300 RON/cameră',
                    'message' => 'Avem nevoie de cazare pentru aproximativ 30 de invitați care vin din afara orașului, aproape de sala de nuntă.',
                ],
            ],
        ];
    }
}
