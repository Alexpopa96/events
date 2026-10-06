<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailTwoFactorCode;
use App\Support\EmailTwoFactor;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmailTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $this->user = User::factory()->create(['status' => true, 'password' => Hash::make('secret-pass-1')]);
        $this->user->assignRole('client');

        Cache::flush();
    }

    /** The plain code from the most recent EmailTwoFactorCode sent to `$email`. */
    private function sentCode(string $email, string $scope): string
    {
        $code = null;

        Notification::assertSentTo(
            new AnonymousNotifiable,
            EmailTwoFactorCode::class,
            function (EmailTwoFactorCode $notification, $channels, $notifiable) use ($email, $scope, &$code) {
                if (($notifiable->routes['mail'][$email] ?? null) === null && ($notifiable->routes['mail'] ?? null) !== $email) {
                    return false;
                }

                $data = $notification->toMail($notifiable)->viewData;
                $code = $data['code'];

                return str_contains($data['badge'], match ($scope) {
                    'login' => 'Autentificare',
                    'disable' => 'Dezactivare',
                    default => 'Activare',
                });
            }
        );

        return $code;
    }

    private function enable(): void
    {
        $this->user->forceFill(['two_factor_email_confirmed_at' => now()])->save();
    }

    public function test_user_enables_two_step_with_the_code_emailed_to_the_account_address(): void
    {
        Notification::fake();

        $this->actingAs($this->user)
            ->postJson(route('email-two-factor.send'))
            ->assertOk()
            ->assertJson(['sent' => true]);

        // Not enabled until the code is confirmed.
        $this->assertFalse($this->user->fresh()->hasEmailTwoFactor());

        $code = $this->sentCode($this->user->email, 'setup');

        $this->actingAs($this->user)
            ->postJson(route('email-two-factor.confirm'), ['code' => $code])
            ->assertOk()
            ->assertJson(['enabled' => true]);

        $user = $this->user->fresh();
        $this->assertTrue($user->hasEmailTwoFactor());
    }

    public function test_a_wrong_or_reused_code_does_not_enable_it(): void
    {
        Notification::fake();

        $this->actingAs($this->user)->postJson(route('email-two-factor.send'));
        $code = $this->sentCode($this->user->email, 'setup');

        $this->actingAs($this->user)->postJson(route('email-two-factor.confirm'), ['code' => $code === '000000' ? '111111' : '000000'])
            ->assertStatus(422)->assertJsonValidationErrors('code');
        $this->assertFalse($this->user->fresh()->hasEmailTwoFactor());

        $this->actingAs($this->user)->postJson(route('email-two-factor.confirm'), ['code' => $code])->assertOk();

        // A used code is gone.
        $this->user->forceFill(['two_factor_email_confirmed_at' => null])->save();
        $this->actingAs($this->user)->postJson(route('email-two-factor.confirm'), ['code' => $code])->assertStatus(422);
    }

    public function test_codes_always_go_to_the_account_email_and_are_cooldown_limited(): void
    {
        Notification::fake();

        // No address can be supplied: whatever is posted, the code goes to the account's email.
        $this->actingAs($this->user)->postJson(route('email-two-factor.send'), ['email' => 'altcineva@exemplu.ro'])
            ->assertOk()->assertJson(['sent' => true, 'email' => $this->user->email]);

        Notification::assertSentOnDemand(EmailTwoFactorCode::class, function ($notification, $channels, $notifiable) {
            return array_key_first($notifiable->routes['mail']) === $this->user->email;
        });

        $this->actingAs($this->user)->postJson(route('email-two-factor.send'))
            ->assertOk()->assertJson(['sent' => false]);

        Notification::assertSentOnDemandTimes(EmailTwoFactorCode::class, 1);
    }

    public function test_login_with_two_step_asks_for_the_code_and_only_then_signs_in(): void
    {
        Notification::fake();
        $this->enable();

        $this->post('/login', ['email' => $this->user->email, 'password' => 'secret-pass-1'])
            ->assertRedirect(route('email-two-factor.challenge'));

        // Password alone is not enough.
        $this->assertGuest();
        $this->get('/user/profile')->assertRedirect();

        $this->get(route('email-two-factor.challenge'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/EmailTwoFactorChallenge')->where('email', EmailTwoFactor::mask($this->user->email)));

        $code = $this->sentCode($this->user->email, 'login');

        $this->post(route('email-two-factor.verify'), ['code' => $code])->assertRedirect();

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_a_wrong_login_code_is_rejected_and_five_misses_burn_the_code(): void
    {
        Notification::fake();
        $this->enable();

        $this->post('/login', ['email' => $this->user->email, 'password' => 'secret-pass-1']);
        $code = $this->sentCode($this->user->email, 'login');
        $wrong = $code === '123456' ? '654321' : '123456';

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('email-two-factor.verify'), ['code' => $wrong])->assertSessionHasErrors('code');
        }

        $this->assertGuest();

        // The real code no longer works either: the user must ask for a new one.
        $this->post(route('email-two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_wrong_password_never_reaches_the_challenge(): void
    {
        Notification::fake();
        $this->enable();

        $this->post('/login', ['email' => $this->user->email, 'password' => 'gresita'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
        $this->get(route('email-two-factor.challenge'))->assertRedirect(route('login'));
    }

    public function test_users_without_two_step_still_sign_in_directly(): void
    {
        $this->post('/login', ['email' => $this->user->email, 'password' => 'secret-pass-1'])->assertRedirect();

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_the_challenge_needs_a_pending_login_and_expires(): void
    {
        Notification::fake();
        $this->enable();

        $this->get(route('email-two-factor.challenge'))->assertRedirect(route('login'));
        $this->post(route('email-two-factor.verify'), ['code' => '123456'])->assertRedirect(route('login'));

        $this->post('/login', ['email' => $this->user->email, 'password' => 'secret-pass-1']);
        $code = $this->sentCode($this->user->email, 'login');

        $this->travel(EmailTwoFactor::CHALLENGE_TTL_MINUTES + 1)->minutes();

        $this->post(route('email-two-factor.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_disabling_needs_a_code_from_the_enabled_address(): void
    {
        Notification::fake();
        $this->enable();

        $this->actingAs($this->user)->deleteJson(route('email-two-factor.disable'), ['code' => '123456'])->assertStatus(422);
        $this->assertTrue($this->user->fresh()->hasEmailTwoFactor());

        $this->actingAs($this->user)->postJson(route('email-two-factor.disable-code'))->assertJson(['sent' => true]);
        $code = $this->sentCode($this->user->email, 'disable');

        $this->actingAs($this->user)->deleteJson(route('email-two-factor.disable'), ['code' => $code])
            ->assertOk()->assertJson(['enabled' => false]);

        $this->assertFalse($this->user->fresh()->hasEmailTwoFactor());
    }

    public function test_settings_endpoints_require_login_and_a_state_to_change(): void
    {
        $this->postJson(route('email-two-factor.send'))->assertUnauthorized();
        $this->actingAs($this->user)->postJson(route('email-two-factor.disable-code'))->assertNotFound();
    }

    public function test_the_enabled_state_is_shared_with_the_frontend(): void
    {
        $this->enable();

        $this->actingAs($this->user)->get('/user/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->where('emailTwoFactor.enabled', true)
                ->where('emailTwoFactor.email', $this->user->email));
    }
}
