<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\County;
use App\Models\Invoice;
use App\Models\Listing;
use App\Models\Locality;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoAccountsSeeder extends Seeder
{
    /**
     * City used by the demo merchant. Matched against the county and
     * locality names imported by LocalitySeeder.
     */
    private const CITIES = [
        'cluj' => ['county' => 'Cluj', 'locality' => 'Cluj-Napoca'],
    ];

    private int $invoiceCounter = 0;

    /**
     * Seeds one login-ready account per role (admin, supervisor, client,
     * furnizor); the furnizor is active on the premium plan and has listings
     * in the various moderation states. Safe to re-run.
     *
     * All emails use @evenimente.test and phones are stored in E.164 so both
     * email and phone login work.
     */
    public function run(): void
    {
        $this->seedStaffAndClients();
        $this->seedMerchants();
    }

    private function seedStaffAndClients(): void
    {
        collect([
            ['name' => 'Admin Demo', 'email' => 'admin@evenimente.test', 'phone' => '+40700000001', 'password' => 'admin2026', 'role' => 'admin'],
            ['name' => 'Sorin Supervisor', 'email' => 'supervisor@evenimente.test', 'phone' => '+40700000002', 'password' => 'supervisor2026', 'role' => 'supervisor'],
            ['name' => 'Utilizator Demo', 'email' => 'alex@mail.com', 'phone' => '+40700000003', 'password' => 'user2026', 'role' => 'client'],
        ])->each(fn (array $account) => $this->seedUser($account));
    }

    private function seedUser(array $account): User
    {
        // An account seeded earlier under a different email keeps its phone,
        // which is unique, so pick it up by phone and move it to the new email.
        $existing = User::where('phone', $account['phone'])->where('email', '!=', $account['email'])->first();
        $existing?->update(['email' => $account['email']]);

        $user = User::firstOrCreate(
            ['email' => $account['email']],
            [
                'name' => $account['name'],
                'password' => Hash::make($account['password']),
                'phone' => $account['phone'],
                'status' => $account['status'] ?? true,
                'obs' => $account['obs'] ?? null,
                'email_verified_at' => now(),
            ]
        );

        // Accounts created by DemoProviderSeeder have no phone yet.
        if (blank($user->phone)) {
            $user->update(['phone' => $account['phone']]);
        }

        $user->syncRoles([$account['role']]);

        return $user;
    }

    private function seedMerchants(): void
    {
        $plans = SubscriptionPlan::pluck('id', 'slug');

        foreach ($this->merchants() as $merchant) {
            $user = $this->seedUser([
                'name' => $merchant['owner'],
                'email' => $merchant['email'],
                'phone' => $merchant['phone'],
                'password' => 'furnizor2026',
                'role' => 'furnizor',
            ]);

            $location = $this->resolveLocation($merchant['city']);
            $status = $merchant['status'];
            $companySlug = str_replace('-', '', Str::slug($merchant['company']));

            $profile = ProviderProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $merchant['company'],
                    'cui' => $merchant['cui'],
                    'reg_com' => $merchant['reg_com'],
                    'slug' => Str::slug($merchant['company']),
                    'description' => $merchant['description'],
                    'phone' => $merchant['phone'],
                    'whatsapp' => $merchant['phone'],
                    'email' => "contact@{$companySlug}.ro",
                    'website' => "https://{$companySlug}.ro",
                    'address' => $merchant['address'],
                    'county_id' => $location['county_id'],
                    'locality_id' => $location['locality_id'],
                    'social_links' => [
                        'instagram' => "https://instagram.com/{$companySlug}",
                        'facebook' => "https://facebook.com/{$companySlug}",
                    ],
                    'status' => $status,
                    'approved_at' => in_array($status, ['active', 'suspended'], true) ? now()->subDays(60) : null,
                    'rejection_reason' => $status === 'rejected' ? 'CUI-ul introdus nu corespunde cu denumirea companiei. Te rugăm să corectezi datele și să retrimiți profilul.' : null,
                    'rejected_at' => $status === 'rejected' ? now()->subDays(4) : null,
                    'suspension_reason' => $status === 'suspended' ? 'Reclamații repetate din partea clienților privind anulări în ultimul moment.' : null,
                    'suspended_at' => $status === 'suspended' ? now()->subDays(7) : null,
                ]
            );

            $this->seedSubscription($profile, $plans[$merchant['plan']] ?? null, $status);

            foreach ($merchant['listings'] as $listing) {
                $this->seedListing($profile, $location, $listing);
            }

            $profile->update(['profile_completion_score' => $profile->calculateProfileCompletionScore()]);
        }
    }

    private function seedSubscription(ProviderProfile $profile, ?int $planId, string $profileStatus): void
    {
        if (! $planId || in_array($profileStatus, ['pending', 'rejected'], true)) {
            return;
        }

        $plan = SubscriptionPlan::find($planId);

        $subscription = ProviderSubscription::firstOrCreate(
            ['provider_profile_id' => $profile->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => $profileStatus === 'suspended' ? 'canceled' : 'active',
                'starts_at' => now()->subDays(20),
                'ends_at' => now()->addDays(10),
            ]
        );

        if (! $plan->isFree()) {
            $this->invoiceCounter++;

            Invoice::firstOrCreate(
                ['number' => sprintf('INV-DEMO-%04d', $this->invoiceCounter)],
                [
                    'provider_profile_id' => $profile->id,
                    'provider_subscription_id' => $subscription->id,
                    'amount' => $plan->price,
                    'currency' => $plan->currency,
                    'status' => 'paid',
                    'issued_at' => now()->subDays(20),
                    'paid_at' => now()->subDays(20),
                ]
            );
        }
    }

    private function seedListing(ProviderProfile $profile, array $location, array $data): void
    {
        $category = Category::where('slug', $data['category'])->first();

        if (! $category) {
            return;
        }

        $status = $data['status'] ?? 'published';

        Listing::firstOrCreate(
            ['slug' => Str::slug($profile->company_name.' '.$data['title'])],
            [
                'provider_profile_id' => $profile->id,
                'category_id' => $category->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'price_type' => $data['price_type'],
                'price_from' => $data['price_from'] ?? null,
                'price_to' => $data['price_to'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'currency' => 'RON',
                'county_id' => $location['county_id'],
                'locality_id' => $location['locality_id'],
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? ($data['rejection_reason'] ?? null) : null,
                'is_featured' => $data['is_featured'] ?? false,
                'views_count' => $status === 'published' ? random_int(20, 600) : 0,
                'published_at' => $status === 'published' ? now()->subDays(random_int(2, 60)) : null,
            ]
        );
    }

    private function resolveLocation(string $key): array
    {
        $definition = self::CITIES[$key];

        $county = County::where('name', $definition['county'])->first();
        $locality = $county
            ? Locality::where('county_id', $county->id)->where('name', $definition['locality'])->first()
            : null;

        return ['county_id' => $county?->id, 'locality_id' => $locality?->id];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function merchants(): array
    {
        return [
            [
                'owner' => 'Radu Mureșan',
                'email' => 'furnizor.premium@evenimente.test',
                'phone' => '+40700000101',
                'company' => 'Ritm & Lumină Events',
                'cui' => '50100101',
                'reg_com' => 'J12/2101/2018',
                'city' => 'cluj',
                'address' => 'Calea Turzii 178',
                'description' => 'DJ, sonorizare și lumini pentru nunți și evenimente corporate. Echipament propriu, echipă de 6 oameni, peste 400 de evenimente organizate.',
                'plan' => 'premium',
                'status' => 'active',
                'listings' => [
                    [
                        'category' => 'dj',
                        'title' => 'DJ pentru nuntă — pachet complet',
                        'description' => 'DJ cu experiență de peste 10 ani, playlist construit împreună cu voi și animație pe parcursul întregii petreceri.',
                        'price_type' => 'starting_from',
                        'price_from' => 2800,
                        'is_featured' => true,
                        'benefits' => ['Până la 8 ore de muzică', 'Sonorizare profesională inclusă', 'Lumini de ambianță', 'Consultare playlist înainte de eveniment'],
                    ],
                    [
                        'category' => 'sonorizare',
                        'title' => 'Sonorizare evenimente corporate',
                        'description' => 'Sistem de sonorizare, microfoane wireless și tehnician dedicat pentru conferințe, lansări și petreceri de firmă.',
                        'price_type' => 'per_hour',
                        'price_from' => 350,
                        'is_featured' => true,
                        'benefits' => ['Microfoane wireless', 'Tehnician dedicat', 'Montaj și demontaj incluse'],
                    ],
                    [
                        'category' => 'lumini',
                        'title' => 'Lumini arhitecturale pentru sală',
                        'description' => 'Iluminare colorată a pereților, tavanului și mesei mirilor, cu control DMX pe durata serii.',
                        'price_type' => 'fixed',
                        'price_from' => 1200,
                        'benefits' => ['Uplights LED', 'Efecte pentru primul dans', 'Control DMX'],
                    ],
                    [
                        'category' => 'cabina-360',
                        'title' => 'Cabină video 360° — închiriere',
                        'description' => 'Platformă 360° cu operator, filmări slow-motion și livrare instantanee pe telefonul invitaților.',
                        'price_type' => 'fixed',
                        'price_from' => 900,
                        'benefits' => ['Operator inclus', 'Fundal personalizat', 'Descărcare instantanee prin QR'],
                    ],
                    [
                        'category' => 'dj',
                        'title' => 'DJ pentru botez și petreceri private',
                        'description' => 'Pachet ușor pentru evenimente mici: muzică, sonorizare de bază și animație pentru copii.',
                        'price_type' => 'fixed',
                        'price_from' => 1500,
                        'status' => 'draft',
                    ],
                    [
                        'category' => 'lumini',
                        'title' => 'Efecte pirotehnice reci — ediție 2025',
                        'description' => 'Fântâni de scântei pentru intrarea mirilor. Anunț retras — înlocuit de pachetul de lumini.',
                        'price_type' => 'on_request',
                        'status' => 'archived',
                    ],
                ],
            ],
        ];
    }
}
