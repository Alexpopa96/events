<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Prices are placeholders for the RO market and should be revisited
     * once real subscription tiers are finalized with the client.
     */
    public function run(): void
    {
        collect([
            [
                'name' => 'Gratuit',
                'slug' => 'gratuit',
                'description' => 'Prezență de bază pe platformă, ideală pentru a testa canalul.',
                'price' => 0,
                'max_listings' => 1,
                'max_photos_per_listing' => 5,
                'max_videos_per_listing' => 0,
                'allows_featured_placement' => false,
                'features' => [
                    '1 anunț activ',
                    'Până la 5 fotografii',
                    'Profil companie de bază',
                ],
                'position' => 0,
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'description' => 'Pentru furnizori activi care vor vizibilitate constantă.',
                'price' => 99,
                'max_listings' => 3,
                'max_photos_per_listing' => 20,
                'max_videos_per_listing' => 2,
                'allows_featured_placement' => false,
                'features' => [
                    'Până la 3 anunțuri active',
                    'Până la 20 fotografii și 2 videoclipuri per anunț',
                    'Statistici de bază (vizualizări, contacte)',
                ],
                'position' => 1,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Vizibilitate maximă pentru furnizorii care vor cele mai multe lead-uri.',
                'price' => 249,
                'max_listings' => null,
                'max_photos_per_listing' => null,
                'max_videos_per_listing' => null,
                'allows_featured_placement' => true,
                'features' => [
                    'Anunțuri nelimitate',
                    'Foto și video nelimitate',
                    'Poziționare prioritară în căutare',
                    'Statistici complete + export',
                ],
                'position' => 2,
            ],
        ])->each(function (array $plan) {
            SubscriptionPlan::firstOrCreate(
                ['slug' => $plan['slug']],
                $plan + ['currency' => 'RON', 'billing_period' => 'monthly', 'is_active' => true]
            );
        });
    }
}
