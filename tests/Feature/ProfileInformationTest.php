<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attributes = []): User
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $user = User::factory()->create($attributes + ['status' => true]);
        $user->assignRole('client');

        return $user;
    }

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($user = $this->makeUser());

        // Same email: no verification code needed. Changing it requires one (see EmailChangeTest).
        $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $this->assertEquals('Test Name', $user->fresh()->name);
    }

    public function test_the_email_cannot_be_changed_without_a_verification_code(): void
    {
        $this->actingAs($user = $this->makeUser(['email' => 'vechi@example.com']));

        $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => 'test@example.com',
        ])->assertSessionHasErrorsIn('updateProfileInformation', 'email_change_code');

        $this->assertEquals('vechi@example.com', $user->fresh()->email);
    }
}
