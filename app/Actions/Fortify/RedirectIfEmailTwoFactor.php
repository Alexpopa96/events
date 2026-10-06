<?php

namespace App\Actions\Fortify;

use App\Support\EmailTwoFactor;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;

/**
 * Login pipeline step: once the password is right, users with email two-step
 * verification get a code emailed and are sent to the challenge instead of
 * being signed in.
 */
class RedirectIfEmailTwoFactor extends RedirectIfTwoFactorAuthenticatable
{
    public function handle($request, $next)
    {
        $user = $this->validateCredentials($request);

        if ($user->hasEmailTwoFactor()) {
            EmailTwoFactor::beginLogin($request, $user, $request->boolean('remember'));

            return $request->wantsJson()
                ? response()->json(['two_factor' => true])
                : redirect()->route('email-two-factor.challenge');
        }

        return $next($request);
    }
}
