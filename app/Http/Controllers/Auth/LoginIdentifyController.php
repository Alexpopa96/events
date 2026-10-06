<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginIdentifyController extends Controller
{
    /**
     * First step of the login: tell whether an account exists for the given
     * email address or phone number.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $identifier = trim((string) $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ])['identifier']);

        if (str_contains($identifier, '@')) {
            if (! filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'identifier' => 'Introdu o adresă de email sau un număr de telefon valid.',
                ]);
            }

            $type = 'email';
            $identifier = Str::lower($identifier);
        } else {
            $identifier = User::normalizePhone($identifier) ?? throw ValidationException::withMessages([
                'identifier' => 'Introdu o adresă de email sau un număr de telefon valid.',
            ]);

            $type = 'phone';
        }

        return response()->json([
            'exists' => User::findByLoginIdentifier($identifier) !== null,
            'type' => $type,
            'identifier' => $identifier,
        ]);
    }
}
