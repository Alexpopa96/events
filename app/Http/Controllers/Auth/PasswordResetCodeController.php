<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class PasswordResetCodeController extends Controller
{
    private const CODE_TTL_MINUTES = 10;

    private const RESEND_COOLDOWN_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    /**
     * Email a one-time code to the account matching the identifier (email or
     * phone). The response is identical whether or not an account exists.
     */
    public function send(Request $request): JsonResponse
    {
        $user = $this->user($request);

        if ($user && Cache::add($this->key('cooldown', $user), true, now()->addSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(self::CODE_TTL_MINUTES);

            Cache::put($this->key('code', $user), [
                'hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => $expiresAt,
            ], $expiresAt);

            try {
                $user->notify(new PasswordResetCode($code, self::CODE_TTL_MINUTES));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return response()->json(['sent' => true]);
    }

    /**
     * Check the code and hand back a password-broker token, so the password
     * itself is set through the regular `password.update` endpoint.
     */
    public function verify(Request $request): JsonResponse
    {
        $code = $request->validate(['code' => ['required', 'digits:6']])['code'];

        $user = $this->user($request);
        $entry = $user ? Cache::get($this->key('code', $user)) : null;

        if (! $entry || ! Hash::check($code, $entry['hash'])) {
            if ($entry) {
                $entry['attempts']++;

                $entry['attempts'] >= self::MAX_ATTEMPTS
                    ? Cache::forget($this->key('code', $user))
                    : Cache::put($this->key('code', $user), $entry, $entry['expires_at']);
            }

            throw ValidationException::withMessages([
                'code' => 'Codul este invalid sau a expirat.',
            ]);
        }

        Cache::forget($this->key('code', $user));

        return response()->json([
            'token' => Password::broker(config('fortify.passwords'))->createToken($user),
            'email' => $user->email,
        ]);
    }

    private function user(Request $request): ?User
    {
        $identifier = $request->validate(['identifier' => ['required', 'string', 'max:255']])['identifier'];

        return User::findByLoginIdentifier($identifier);
    }

    private function key(string $type, User $user): string
    {
        return "password-code:{$type}:{$user->id}";
    }
}
