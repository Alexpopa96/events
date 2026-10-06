<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_identify_finds_an_existing_account_by_email(): void
    {
        $user = User::factory()->create();

        $this->postJson('/login/identify', ['identifier' => strtoupper($user->email)])
            ->assertOk()
            ->assertJson(['exists' => true, 'type' => 'email', 'identifier' => $user->email]);
    }

    public function test_identify_finds_an_existing_account_by_phone_in_any_format(): void
    {
        User::factory()->create(['phone' => '+40712345678']);

        $this->postJson('/login/identify', ['identifier' => '0712 345 678'])
            ->assertOk()
            ->assertJson(['exists' => true, 'type' => 'phone', 'identifier' => '+40712345678']);
    }

    public function test_identify_reports_unknown_accounts(): void
    {
        $this->postJson('/login/identify', ['identifier' => 'nobody@example.com'])
            ->assertOk()
            ->assertJson(['exists' => false, 'type' => 'email']);
    }

    public function test_identify_rejects_invalid_identifiers(): void
    {
        $this->postJson('/login/identify', ['identifier' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('identifier');
    }

    public function test_users_can_authenticate_with_their_phone_number(): void
    {
        $user = User::factory()->create(['phone' => '+40712345678']);

        $this->post('/login', [
            'email' => '+40712345678',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }
}
