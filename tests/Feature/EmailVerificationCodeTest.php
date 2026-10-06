<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailCode;
use App\Services\EmailVerificationCodeService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function client(bool $verified = false): User
    {
        $user = ($verified ? User::factory() : User::factory()->unverified())->create(['status' => true]);
        $user->assignRole('client');

        return $user;
    }

    /**
     * Send a code to the user and return it as it appears in the email.
     */
    private function issueCode(User $user): string
    {
        Notification::fake();

        app(EmailVerificationCodeService::class)->send($user);

        $code = null;
        Notification::assertSentTo($user, VerifyEmailCode::class, function (VerifyEmailCode $notification) use (&$code, $user) {
            $code = $notification->toMail($user)->viewData['code'];

            return true;
        });

        return $code;
    }

    public function test_verification_screen_can_be_rendered(): void
    {
        $user = $this->client();

        $this->actingAs($user)->get('/email/verify')->assertOk();
    }

    public function test_verified_users_are_sent_past_the_verification_screen(): void
    {
        $user = $this->client(verified: true);

        $this->actingAs($user)->get('/email/verify')->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_email_can_be_verified_with_the_emailed_code(): void
    {
        $user = $this->client();
        $code = $this->issueCode($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_a_wrong_code_is_rejected(): void
    {
        $user = $this->client();
        $code = $this->issueCode($user);
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $wrong])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_a_malformed_code_is_rejected(): void
    {
        $user = $this->client();
        $this->issueCode($user);

        $this->actingAs($user)
            ->post('/email/verify', ['code' => '12ab'])
            ->assertSessionHasErrors('code');
    }

    public function test_the_code_expires_after_ten_minutes(): void
    {
        $user = $this->client();
        $code = $this->issueCode($user);

        $this->travel(10)->minutes();
        $this->travel(1)->seconds();

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_the_code_is_still_valid_just_before_it_expires(): void
    {
        $user = $this->client();
        $code = $this->issueCode($user);

        $this->travel(9)->minutes();

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_the_code_is_discarded_after_too_many_wrong_attempts(): void
    {
        $user = $this->client();
        $code = $this->issueCode($user);
        $wrong = $code === '000000' ? '111111' : '000000';

        foreach (range(1, 5) as $ignored) {
            $this->actingAs($user)->post('/email/verify', ['code' => $wrong]);
        }

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_a_code_cannot_be_used_twice_and_a_new_code_replaces_the_old_one(): void
    {
        $user = $this->client();
        $first = $this->issueCode($user);
        $second = $this->issueCode($user);

        if ($first !== $second) {
            $this->actingAs($user)
                ->post('/email/verify', ['code' => $first])
                ->assertSessionHasErrors('code');
        }

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $second])
            ->assertSessionHasNoErrors();
    }

    public function test_a_code_can_be_resent(): void
    {
        Notification::fake();
        $user = $this->client();

        $this->actingAs($user)
            ->post('/email/verify/resend')
            ->assertSessionHas('status', 'verification-code-sent');

        Notification::assertSentTo($user, VerifyEmailCode::class);
    }

    public function test_the_email_shows_the_code_logo_and_validity(): void
    {
        $user = $this->client()->forceFill(['name' => 'Ana Popescu']);
        $code = $this->issueCode($user);

        Notification::assertSentTo($user, VerifyEmailCode::class, function (VerifyEmailCode $notification) use ($user, $code) {
            $mail = $notification->toMail($user);
            $html = view($mail->view, $mail->viewData)->render();

            $this->assertStringContainsString($code, $mail->subject);
            $this->assertStringContainsString($code, $html);
            $this->assertStringContainsString('Ana Popescu', $html);
            $this->assertStringContainsString('10 minute', $html);
            $this->assertStringContainsString('eventhub-logo.png', $html);

            return true;
        });
    }

    public function test_guests_cannot_reach_the_verification_routes(): void
    {
        $this->get('/email/verify')->assertRedirect('/login');
        $this->post('/email/verify', ['code' => '123456'])->assertRedirect('/login');
    }
}
