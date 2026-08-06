<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\Listing;
use App\Models\ListingEvent;
use App\Models\ListingMedia;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoProviderSeeder extends Seeder
{
    /**
     * Seeds one login-ready furnizor account and one client account,
     * with enough sample data (profile, subscription, listings) to
     * exercise the marketplace flows end to end.
     */
    public function run(): void
    {
        $provider = User::firstOrCreate(
            ['email' => 'furnizor@evenimente.test'],
            [
                'name' => 'Ana Popescu',
                'password' => Hash::make('furnizor2026'),
                'status' => true,
            ]
        );
        $provider->syncRoles(['furnizor']);

        $photoCategory = Category::where('slug', 'fotograf')->first();

        $clujNapoca = Locality::whereHas('county', fn ($query) => $query->where('name', 'Cluj'))
            ->where('name', 'Cluj-Napoca')
            ->first();

        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $provider->id],
            [
                'company_name' => 'Ana Popescu Fotografie',
                'cui' => '12345678',
                'reg_com' => 'J12/1234/2020',
                'slug' => Str::slug('Ana Popescu Fotografie'),
                'description' => 'Fotografie de nuntă și evenimente private în stil documentar, cu accent pe momente naturale, nu pozate.',
                'phone' => '0722123456',
                'whatsapp' => '0722123456',
                'email' => 'contact@anapopescufoto.ro',
                'address' => 'Strada Memorandumului 28',
                'county_id' => $clujNapoca?->county_id,
                'locality_id' => $clujNapoca?->id,
                'social_links' => [
                    'instagram' => 'https://instagram.com/anapopescufoto',
                    'facebook' => 'https://facebook.com/anapopescufoto',
                ],
                'status' => 'active',
                'approved_at' => now(),
            ]
        );
        $profile->update(['profile_completion_score' => $profile->calculateProfileCompletionScore()]);

        $standardPlan = SubscriptionPlan::where('slug', 'standard')->first();

        if ($standardPlan) {
            $subscription = ProviderSubscription::firstOrCreate(
                ['provider_profile_id' => $profile->id],
                [
                    'subscription_plan_id' => $standardPlan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                ]
            );

            Invoice::firstOrCreate(
                ['number' => 'INV-2026-0001'],
                [
                    'provider_profile_id' => $profile->id,
                    'provider_subscription_id' => $subscription->id,
                    'amount' => $standardPlan->price,
                    'currency' => $standardPlan->currency,
                    'status' => 'paid',
                    'issued_at' => now()->subDays(3),
                    'paid_at' => now()->subDays(3),
                ]
            );
        }

        $listings = collect();

        if ($photoCategory) {
            $listings = collect([
                [
                    'title' => 'Pachet foto nuntă — o zi completă',
                    'description' => 'Acoperire completă: pregătiri, cununie, petrecere. Toate pozele editate livrate în galerie online.',
                    'price_type' => 'starting_from',
                    'price_from' => 3500,
                    'benefits' => [
                        '8 ore de acoperire foto',
                        '2 fotografi',
                        'Peste 500 de poze editate',
                        'Galerie online privată',
                        'Album foto printat',
                    ],
                ],
                [
                    'title' => 'Ședință foto logodnă',
                    'description' => 'Ședință de o oră în locația aleasă de voi, cu 40+ poze editate.',
                    'price_type' => 'fixed',
                    'price_from' => 600,
                    'benefits' => [
                        '1 oră de ședință foto',
                        'Locație la alegere',
                        '40+ poze editate',
                        'Livrare în 5 zile lucrătoare',
                    ],
                ],
            ])->map(fn (array $listing) => Listing::firstOrCreate(
                ['slug' => Str::slug($listing['title'])],
                $listing + [
                    'provider_profile_id' => $profile->id,
                    'category_id' => $photoCategory->id,
                    'currency' => 'RON',
                    'county_id' => $clujNapoca?->county_id,
                    'locality_id' => $clujNapoca?->id,
                    'status' => 'published',
                    'published_at' => now(),
                ]
            ));

            $listings->each(fn (Listing $listing) => $this->attachGalleryPhotos($listing));
        }

        $leads = collect();

        if ($photoCategory) {
            $leads = collect([
                [
                    'name' => 'Diana și Radu',
                    'email' => 'diana.radu@exemplu.ro',
                    'phone' => '0733112233',
                    'event_date' => now()->addMonths(4)->toDateString(),
                    'city' => 'Cluj-Napoca',
                    'county' => 'Cluj',
                    'budget_range' => '3000 - 5000 RON',
                    'message' => 'Căutăm fotograf pentru nuntă în luna octombrie, la o locație în afara orașului. Ne-ar plăcea stil documentar, fără poze foarte pozate.',
                ],
                [
                    'name' => 'Cristina M.',
                    'email' => 'cristina.m@exemplu.ro',
                    'phone' => '0744556677',
                    'event_date' => now()->addMonths(1)->toDateString(),
                    'city' => 'Turda',
                    'county' => 'Cluj',
                    'budget_range' => null,
                    'message' => 'Caut fotograf pentru o ședință de logodnă, undeva în natură. Ce pachete aveți disponibile?',
                ],
            ])->map(fn (array $lead) => QuoteRequest::firstOrCreate(
                ['email' => $lead['email']],
                $lead + ['category_id' => $photoCategory->id, 'status' => 'open']
            ));
        }

        // Backfills 30 days of view/click activity plus a handful of approved
        // reviews so the provider dashboard's trend chart, listing conversion
        // table, and reviews widget aren't empty on a fresh seed.
        if ($listings->isNotEmpty() && ! ListingEvent::where('provider_profile_id', $profile->id)->exists()) {
            $eventRows = [];

            foreach (range(29, 0) as $daysAgo) {
                $day = now()->subDays($daysAgo);

                foreach ($listings as $listing) {
                    $counts = [
                        'view' => random_int(3, 14),
                        'phone_click' => random_int(0, 3),
                        'whatsapp_click' => random_int(0, 4),
                    ];

                    foreach ($counts as $type => $count) {
                        for ($i = 0; $i < $count; $i++) {
                            $eventRows[] = [
                                'provider_profile_id' => $profile->id,
                                'listing_id' => $listing->id,
                                'quote_request_id' => null,
                                'user_id' => null,
                                'type' => $type,
                                'ip_hash' => null,
                                'created_at' => $day->copy()->addMinutes(random_int(0, 1439)),
                            ];
                        }
                    }
                }
            }

            if ($leads->isNotEmpty()) {
                $eventRows[] = [
                    'provider_profile_id' => $profile->id,
                    'listing_id' => null,
                    'quote_request_id' => $leads->first()->id,
                    'user_id' => null,
                    'type' => 'quote_request_view',
                    'ip_hash' => null,
                    'created_at' => now()->subDays(1),
                ];
            }

            ListingEvent::insert($eventRows);
        }

        if ($listings->isNotEmpty() && ! Review::where('provider_profile_id', $profile->id)->exists()) {
            $reviewers = User::factory()->count(3)->create();
            $reviewers->each(fn (User $reviewer) => $reviewer->syncRoles(['client']));

            collect([
                ['rating' => 5, 'comment' => 'Fotografii superbe, Ana a surprins exact atmosfera pe care ne-o doream. Recomand cu toată încrederea!', 'daysAgo' => 2],
                ['rating' => 4, 'comment' => 'Foarte punctuală și profesionistă, poze editate frumos. Am primit galeria puțin mai târziu decât estimat.', 'daysAgo' => 6],
                ['rating' => 5, 'comment' => 'Ședința de logodnă a ieșit perfect, ne-a pus imediat în largul nostru în fața camerei.', 'daysAgo' => 12],
            ])->values()->each(function (array $data, int $index) use ($listings, $profile, $reviewers) {
                $review = Review::create([
                    'listing_id' => $listings[$index % $listings->count()]->id,
                    'provider_profile_id' => $profile->id,
                    'user_id' => $reviewers[$index]->id,
                    'rating' => $data['rating'],
                    'comment' => $data['comment'],
                    'status' => 'approved',
                ]);

                $review->forceFill([
                    'created_at' => now()->subDays($data['daysAgo']),
                    'updated_at' => now()->subDays($data['daysAgo']),
                ])->save();
            });
        }

        $client = User::firstOrCreate(
            ['email' => 'client@evenimente.test'],
            [
                'name' => 'Mihai Ionescu',
                'password' => Hash::make('client2026'),
                'status' => true,
            ]
        );
        $client->syncRoles(['client']);
    }

    /**
     * Attaches a small real-photo gallery to a demo listing so the gallery,
     * cover image, and card thumbnails aren't empty on a fresh seed. Photos
     * come from Lorem Picsum (real photography, seeded per listing/index so
     * a fresh seed always reproduces the same set) and fall back to a
     * generated gradient if the seeder is run offline. Skipped once a
     * listing already has media, matching the firstOrCreate idempotency
     * used for the rest of this seeder.
     */
    private function attachGalleryPhotos(Listing $listing): void
    {
        if ($listing->media()->exists()) {
            return;
        }

        $fallbackPalettes = [
            [[22, 40, 31], [168, 127, 46]],   // ink -> gold
            [[124, 46, 59], [201, 162, 79]],  // wine -> gold-bright
            [[31, 58, 44], [111, 132, 101]],  // ink-2 -> sage
            [[239, 234, 219], [124, 46, 59]], // paper-3 -> wine
        ];

        foreach ($fallbackPalettes as $index => $palette) {
            $path = "listings/{$listing->id}/".Str::random(12).'.jpg';
            $seed = "invita-listing-{$listing->id}-{$index}";

            $contents = $this->fetchStockPhoto($seed) ?? $this->placeholderPhoto($palette);

            Storage::disk('public')->put($path, $contents);

            ListingMedia::create([
                'listing_id' => $listing->id,
                'type' => 'photo',
                'path' => $path,
                'is_cover' => $index === 0,
                'position' => $index,
            ]);
        }
    }

    /**
     * Fetches a real photo from Lorem Picsum keyed by a stable seed, so
     * reseeding from scratch always produces the same image per slot.
     * Returns null (letting the caller fall back to a placeholder) if the
     * request fails, so seeding still works without network access.
     */
    private function fetchStockPhoto(string $seed): ?string
    {
        try {
            $response = Http::timeout(10)->get("https://picsum.photos/seed/{$seed}/1200/800");

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Renders a vertical-gradient JPEG in-process with GD so demo photos
     * still work without network access to fetch stock images.
     */
    private function placeholderPhoto(array $palette): string
    {
        [$from, $to] = $palette;
        [$width, $height] = [1200, 800];

        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $color = imagecolorallocate(
                $image,
                (int) ($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) ($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) ($from[2] + ($to[2] - $from[2]) * $ratio),
            );
            imageline($image, 0, $y, $width, $y, $color);
        }

        ob_start();
        imagejpeg($image, null, 85);
        $contents = ob_get_clean();
        imagedestroy($image);

        return $contents;
    }
}
