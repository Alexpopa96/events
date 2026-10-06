<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\RedirectIfEmailTwoFactor;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\County;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::registerView(function () {
            return Inertia::render('Auth/Register', [
                'counties' => County::orderBy('name')->get(['id', 'name']),
            ]);
        });

        // The "email" field of the login form accepts an email address or a phone number.
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::findByLoginIdentifier((string) $request->input(Fortify::username()));

            return $user && Hash::check((string) $request->input('password'), $user->password)
                ? $user
                : null;
        });

        // Two-step verification is emailed codes (see EmailTwoFactor), not Fortify's authenticator app.
        // Login is throttled by the `login` limiter below, hence no EnsureLoginIsNotThrottled step.
        Fortify::authenticateThrough(fn () => [
            config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
            RedirectIfEmailTwoFactor::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
