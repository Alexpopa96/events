<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\EmailTwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Turning email two-step verification on and off from the account settings.
 * Codes always go to the account's own email; both directions are confirmed with one.
 */
class EmailTwoFactorSettingsController extends Controller
{
    /** Email a code to the account's address, to confirm turning it on. */
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        if (blank($user->email)) {
            throw ValidationException::withMessages(['email' => 'Contul tău nu are o adresă de email. Adaug-o mai întâi în Date personale.']);
        }

        return response()->json(EmailTwoFactor::issue(EmailTwoFactor::SCOPE_SETUP, $user) + ['email' => $user->email]);
    }

    /** Enable once the code from the emailed message is entered. */
    public function confirm(Request $request): JsonResponse
    {
        $code = $request->validate(['code' => ['required', 'digits:6']])['code'];

        if (! EmailTwoFactor::check(EmailTwoFactor::SCOPE_SETUP, $request->user(), $code)) {
            throw ValidationException::withMessages(['code' => 'Codul este invalid sau a expirat.']);
        }

        $request->user()->forceFill(['two_factor_email_confirmed_at' => now()])->save();

        return response()->json(['enabled' => true]);
    }

    /** Email a code to the account's address, to confirm turning it off. */
    public function sendDisableCode(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->hasEmailTwoFactor(), 404);

        return response()->json(EmailTwoFactor::issue(EmailTwoFactor::SCOPE_DISABLE, $user) + ['email' => $user->email]);
    }

    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->hasEmailTwoFactor(), 404);

        $code = $request->validate(['code' => ['required', 'digits:6']])['code'];

        if (! EmailTwoFactor::check(EmailTwoFactor::SCOPE_DISABLE, $user, $code)) {
            throw ValidationException::withMessages(['code' => 'Codul este invalid sau a expirat.']);
        }

        $user->forceFill(['two_factor_email_confirmed_at' => null])->save();

        return response()->json(['enabled' => false]);
    }
}
