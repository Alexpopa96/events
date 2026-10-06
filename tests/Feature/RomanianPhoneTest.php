<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RomanianPhoneTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function validNumbers(): array
    {
        return [
            'national mobile' => ['0722123456', '+40722123456'],
            'national with spaces' => ['0722 123 456', '+40722123456'],
            'national with separators' => ['0722-123-456', '+40722123456'],
            'plus prefix' => ['+40722123456', '+40722123456'],
            'plus prefix with trunk zero' => ['+40 (0)722 123 456', '+40722123456'],
            'double zero prefix' => ['0040722123456', '+40722123456'],
            'bare country code' => ['40722123456', '+40722123456'],
            'landline' => ['0264 123 456', '+40264123456'],
            'bucharest landline' => ['021 123 4567', '+40211234567'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'foreign uk' => ['+44 7911 123456'],
            'foreign germany' => ['+49 151 23456789'],
            'foreign us' => ['+1 202 555 0143'],
            'too short' => ['0722 123'],
            'too long' => ['0722 123 4567'],
            'bad prefix' => ['0122 123 456'],
            'letters' => ['abc'],
        ];
    }

    /**
     * @dataProvider validNumbers
     */
    public function test_romanian_numbers_are_normalized_to_e164(string $input, string $expected): void
    {
        $this->assertSame($expected, User::normalizePhone($input));
    }

    /**
     * @dataProvider invalidNumbers
     */
    public function test_non_romanian_numbers_are_rejected(string $input): void
    {
        $this->assertNull(User::normalizePhone($input));
    }

    public function test_models_store_phones_in_e164(): void
    {
        $user = User::factory()->create(['phone' => '0722 123 456']);
        $this->assertSame('+40722123456', $user->fresh()->phone);

        $profile = ProviderProfile::forceCreate([
            'user_id' => $user->id,
            'company_name' => 'Test',
            'slug' => 'test',
            'phone' => '0733 111 222',
            'whatsapp' => '0040744555666',
            'status' => 'active',
        ]);
        $this->assertSame('+40733111222', $profile->fresh()->phone);
        $this->assertSame('+40744555666', $profile->fresh()->whatsapp);
    }

    public function test_client_registration_normalizes_a_romanian_phone(): void
    {
        $this->seed(RoleSeeder::class);
        Notification::fake();

        $this->post('/register/client', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'phone' => '0722 123 456',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame('+40722123456', auth()->user()->phone);
    }

    public function test_client_registration_rejects_a_foreign_phone(): void
    {
        $this->seed(RoleSeeder::class);

        $this->post('/register/client', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'phone' => '+44 7911 123456',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_client_registration_rejects_a_phone_already_in_use_in_another_format(): void
    {
        $this->seed(RoleSeeder::class);
        User::factory()->create(['phone' => '+40722123456']);

        $this->post('/register/client', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'phone' => '0722123456',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('phone');
    }

    public function test_login_identify_rejects_a_foreign_phone(): void
    {
        $this->postJson('/login/identify', ['identifier' => '+44 7911 123456'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('identifier');
    }
}
