<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailTwoFactorCode;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmailChangeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $this->user = User::factory()->create(['status' => true, 'name' => 'Ana Popescu', 'email' => 'ana@exemplu.ro']);
        $this->user->assignRole('client');

        Cache::flush();
    }

    /** Ask for a change code and return the plain code that was emailed, plus who it was sent to. */
    private function requestCode(string $newEmail): array
    {
        Notification::fake();

        $this->actingAs($this->user)
            ->postJson(route('email-change.send'), ['email' => $newEmail])
            ->assertOk()
            ->assertJson(['sent' => true, 'email' => 'ana@exemplu.ro']);

        $found = [];

        Notification::assertSentTo(new AnonymousNotifiable, EmailTwoFactorCode::class, function ($notification, $channels, $notifiable) use (&$found) {
            $found = [
                'to' => array_key_first($notifiable->routes['mail']),
                'code' => $notification->toMail($notifiable)->viewData['code'],
                'lines' => $notification->toMail($notifiable)->viewData['lines'],
            ];

            return true;
        });

        return $found;
    }

    private function save(array $data)
    {
        return $this->actingAs($this->user)->put(route('user-profile-information.update'), $data + ['name' => 'Ana Popescu']);
    }

    public function test_the_code_goes_to_the_current_address_and_names_the_new_one(): void
    {
        $sent = $this->requestCode('noua@exemplu.ro');

        $this->assertSame('ana@exemplu.ro', $sent['to']);
        $this->assertStringContainsString('noua@exemplu.ro', $sent['lines'][0]);
    }

    public function test_changing_the_email_without_a_code_is_refused(): void
    {
        $this->save(['email' => 'noua@exemplu.ro'])->assertSessionHasErrorsIn('updateProfileInformation', 'email_change_code');

        $this->assertSame('ana@exemplu.ro', $this->user->fresh()->email);
    }

    public function test_the_email_changes_once_the_code_from_the_current_address_is_entered(): void
    {
        $sent = $this->requestCode('Noua@Exemplu.ro');

        $this->save(['email' => 'Noua@Exemplu.ro', 'email_change_code' => $sent['code']])
            ->assertSessionHasNoErrors();

        $this->assertSame('noua@exemplu.ro', $this->user->fresh()->email);

        // A code works once.
        $this->save(['email' => 'alta@exemplu.ro', 'email_change_code' => $sent['code']])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'email_change_code');
        $this->assertSame('noua@exemplu.ro', $this->user->fresh()->email);
    }

    public function test_a_wrong_code_or_a_code_for_another_address_does_not_change_it(): void
    {
        $sent = $this->requestCode('noua@exemplu.ro');
        $wrong = $sent['code'] === '000000' ? '111111' : '000000';

        $this->save(['email' => 'noua@exemplu.ro', 'email_change_code' => $wrong])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'email_change_code');

        // Right code, but it was issued for noua@, not for other@.
        $this->save(['email' => 'altceva@exemplu.ro', 'email_change_code' => $sent['code']])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'email_change_code');

        $this->assertSame('ana@exemplu.ro', $this->user->fresh()->email);
    }

    public function test_updating_only_the_name_needs_no_code(): void
    {
        $this->actingAs($this->user)
            ->put(route('user-profile-information.update'), ['name' => 'Ana Ionescu', 'email' => 'ana@exemplu.ro'])
            ->assertSessionHasNoErrors();

        $user = $this->user->fresh();
        $this->assertSame('Ana Ionescu', $user->name);
        $this->assertSame('ana@exemplu.ro', $user->email);
    }

    public function test_a_code_cannot_be_requested_for_an_invalid_taken_or_current_address(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ocupat@exemplu.ro']);

        foreach (['nu-e-email', 'ocupat@exemplu.ro', 'ANA@exemplu.ro'] as $bad) {
            $this->actingAs($this->user)->postJson(route('email-change.send'), ['email' => $bad])
                ->assertStatus(422)->assertJsonValidationErrors('email');
        }

        Notification::assertNothingSent();
    }

    public function test_the_profile_photo_feature_is_off(): void
    {
        $this->actingAs($this->user)->get('/user/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('jetstream.managesProfilePhotos', false));
    }
}
