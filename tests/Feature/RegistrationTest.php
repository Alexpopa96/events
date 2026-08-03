<?php

namespace Tests\Feature;

use App\Models\County;
use App\Models\Locality;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAnaf(string $cui = '12345678'): void
    {
        Http::fake([
            'webservicesp.anaf.ro/*' => Http::response([
                'found' => [[
                    'date_generale' => [
                        'denumire' => 'Test Company SRL',
                        'nrRegCom' => 'J12/1234/2024',
                        'stare_inregistrare' => 'INREGISTRAT',
                    ],
                    'adresa_sediu_social' => [
                        'sdenumire_Strada' => 'Strada Exemplu',
                        'snumar_Strada' => '1',
                        'sdenumire_Judet' => 'Cluj',
                        'sdenumire_Localitate' => 'Cluj-Napoca',
                    ],
                ]],
                'notFound' => [],
            ]),
        ]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        if (Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_new_users_can_register(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $this->seed(RoleSeeder::class);
        $this->fakeAnaf();

        $county = County::create(['name' => 'Cluj']);
        $locality = Locality::create(['county_id' => $county->id, 'name' => 'Cluj-Napoca']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'cui' => '12345678',
            'company_name' => 'Test Company SRL',
            'address' => 'Strada Exemplu 1',
            'county_id' => $county->id,
            'locality_id' => $locality->id,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_client_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register/client');

        $response->assertStatus(200);
    }

    public function test_new_clients_can_register(): void
    {
        $this->seed(RoleSeeder::class);

        $response = $this->post('/register/client', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertTrue(auth()->user()->hasRole('client'));
    }
}
