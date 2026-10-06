<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailTwoFactor;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\LoginResponse;

/**
 * Second step of sign-in for accounts with email two-step verification:
 * the password (or Google) already passed, now the emailed code.
 */
class EmailTwoFactorChallengeController extends Controller
{
    public function __construct(private readonly StatefulGuard $guard)
    {
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/EmailTwoFactorChallenge', [
            'email' => EmailTwoFactor::mask($user->email),
            'validForMinutes' => EmailTwoFactor::CODE_TTL_MINUTES,
            'cooldown' => EmailTwoFactor::cooldownRemaining(EmailTwoFactor::SCOPE_LOGIN, $user),
            'status' => session('status'),
        ]);
    }

    public function verify(Request $request)
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $code = $request->validate(['code' => ['required', 'digits:6']])['code'];

        if (! EmailTwoFactor::check(EmailTwoFactor::SCOPE_LOGIN, $user, $code)) {
            throw ValidationException::withMessages(['code' => 'Codul este invalid sau a expirat.']);
        }

        $remember = (bool) $request->session()->get('login.email2fa.remember');
        $request->session()->forget('login.email2fa');

        $this->guard->login($user, $remember);
        $request->session()->regenerate();

        return app(LoginResponse::class);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        EmailTwoFactor::issue(EmailTwoFactor::SCOPE_LOGIN, $user);

        return back()->with('status', 'verification-code-sent');
    }

    /**
     * The user whose password was accepted and who is waiting on a code, if that
     * wait is still valid; clears the parked login otherwise.
     */
    private function pendingUser(Request $request): ?User
    {
        $data = $request->session()->get('login.email2fa');

        if (! $data || now()->timestamp - $data['at'] > EmailTwoFactor::CHALLENGE_TTL_MINUTES * 60) {
            $request->session()->forget('login.email2fa');

            return null;
        }

        $user = User::find($data['id']);

        if (! $user?->hasEmailTwoFactor()) {
            $request->session()->forget('login.email2fa');

            return null;
        }

        return $user;
    }
}
