<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\EmailTwoFactorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Email-based two-step verification: one-time 6-digit codes kept in the cache
 * (hashed, expiring, attempt-limited), for three purposes — confirming the
 * address when enabling, signing in, and confirming a disable.
 */
class EmailTwoFactor
{
    public const SCOPE_SETUP = 'setup';

    public const SCOPE_LOGIN = 'login';

    public const SCOPE_DISABLE = 'disable';

    /** Changing the account email: the code goes to the CURRENT address and is bound to the new one. */
    public const SCOPE_EMAIL_CHANGE = 'email_change';

    public const CODE_TTL_MINUTES = 10;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    /** How long a password-verified login may wait for its code. */
    public const CHALLENGE_TTL_MINUTES = 15;

    /**
     * Generate a code and email it to the account's own address.
     *
     * @return array{sent: bool, retry_in: int} `sent` is false while the resend cooldown is running.
     */
    public static function issue(string $scope, User $user, array $payload = []): array
    {
        $cooldownKey = self::key($scope, 'cooldown', $user);
        $retryAt = now()->addSeconds(self::RESEND_COOLDOWN_SECONDS)->timestamp;

        if (! Cache::add($cooldownKey, $retryAt, $retryAt)) {
            return ['sent' => false, 'retry_in' => self::cooldownRemaining($scope, $user)];
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::CODE_TTL_MINUTES);

        Cache::put(self::key($scope, 'code', $user), [
            'hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => $expiresAt,
            'payload' => $payload,
        ], $expiresAt);

        try {
            Notification::route('mail', [$user->email => $user->name])
                ->notify(new EmailTwoFactorCode($code, self::CODE_TTL_MINUTES, $scope, $user->name, $payload['email'] ?? null));
        } catch (Throwable $e) {
            report($e);
        }

        return ['sent' => true, 'retry_in' => self::RESEND_COOLDOWN_SECONDS];
    }

    /**
     * Verify a code: true on success, false when it is wrong, expired or out of attempts.
     * A correct code can be used only once. When `$payload` is given, the code must also
     * have been issued for exactly that payload (e.g. the new email address).
     */
    public static function check(string $scope, User $user, string $code, ?array $payload = null): bool
    {
        $key = self::key($scope, 'code', $user);
        $entry = Cache::get($key);

        if (! $entry) {
            return false;
        }

        if (! Hash::check($code, $entry['hash']) || ($payload !== null && ($entry['payload'] ?? []) != $payload)) {
            $entry['attempts']++;

            $entry['attempts'] >= self::MAX_ATTEMPTS
                ? Cache::forget($key)
                : Cache::put($key, $entry, $entry['expires_at']);

            return false;
        }

        Cache::forget($key);
        Cache::forget(self::key($scope, 'cooldown', $user));

        return true;
    }

    public static function cooldownRemaining(string $scope, User $user): int
    {
        return max(0, (int) Cache::get(self::key($scope, 'cooldown', $user), 0) - now()->timestamp);
    }

    /**
     * Park a password-verified login until its emailed code is entered, and send the code.
     */
    public static function beginLogin(Request $request, User $user, bool $remember): void
    {
        $request->session()->put('login.email2fa', [
            'id' => $user->getKey(),
            'remember' => $remember,
            'at' => now()->timestamp,
        ]);

        self::issue(self::SCOPE_LOGIN, $user);
    }

    /** j***@example.com */
    public static function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 1).str_repeat('*', max(2, mb_strlen($name) - 1)).($domain ? "@{$domain}" : '');
    }

    private static function key(string $scope, string $type, User $user): string
    {
        return "email-2fa:{$scope}:{$type}:{$user->id}";
    }
}
