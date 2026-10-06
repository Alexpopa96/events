<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetCodeTest extends TestCase
{
    use RefreshDatabase;

    private function requestCode(User $user, string $identifier): string
    {
        Notification::fake();

        $this->postJson('/forgot-password/code', ['identifier' => $identifier])
            ->assertOk()
            ->assertJson(['sent' => true]);

        $code = null;

        Notification::assertSentTo($user, PasswordResetCode::class, function ($notification) use (&$code) {
            $code = (fn () => $this->code)->call($notification);

            return true;
        });

        return $code;
    }

    public function test_code_is_emailed_and_can_be_used_to_reset_the_password(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user, $user->email);

        $response = $this->postJson('/forgot-password/code/verify', [
            'identifier' => $user->email,
            'code' => $code,
        ])->assertOk();

        $this->post('/reset-password', [
            'token' => $response->json('token'),
            'email' => $response->json('email'),
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_code_can_be_requested_with_a_phone_number(): void
    {
        $user = User::factory()->create(['phone' => '+40712345678']);

        $this->requestCode($user, '0712 345 678');
    }

    public function test_unknown_accounts_get_the_same_response_and_no_email(): void
    {
        Notification::fake();

        $this->postJson('/forgot-password/code', ['identifier' => 'nobody@example.com'])
            ->assertOk()
            ->assertJson(['sent' => true]);

        Notification::assertNothingSent();
    }

    public function test_wrong_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->requestCode($user, $user->email);

        $this->postJson('/forgot-password/code/verify', ['identifier' => $user->email, 'code' => '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_code_expires_after_ten_minutes(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user, $user->email);

        $this->travel(11)->minutes();

        $this->postJson('/forgot-password/code/verify', ['identifier' => $user->email, 'code' => $code])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_code_is_invalidated_after_too_many_wrong_attempts(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user, $user->email);
        $wrong = $code === '000000' ? '111111' : '000000';

        foreach (range(1, 5) as $ignored) {
            $this->postJson('/forgot-password/code/verify', ['identifier' => $user->email, 'code' => $wrong])
                ->assertStatus(422);
        }

        $this->postJson('/forgot-password/code/verify', ['identifier' => $user->email, 'code' => $code])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }
}
