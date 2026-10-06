<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\EmailTwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmailChangeController extends Controller
{
    /**
     * Email a verification code to the account's CURRENT address, bound to the
     * new address the user wants. The change itself is saved with the profile form.
     */
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        $new = Str::lower(trim($request->validate([
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ])['email']));

        if ($new === Str::lower($user->email)) {
            throw ValidationException::withMessages(['email' => 'Aceasta este deja adresa contului tău.']);
        }

        return response()->json(
            EmailTwoFactor::issue(EmailTwoFactor::SCOPE_EMAIL_CHANGE, $user, ['email' => $new]) + ['email' => $user->email]
        );
    }
}
