<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\VerifyEmailCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class EmailVerificationCodeService
{
    public const VALID_FOR_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    /**
     * Generate a fresh 6-digit code for the user (replacing any previous one)
     * and email it to them.
     */
    public function send(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::VALID_FOR_MINUTES);

        $this->store($user, [
            'hash' => $this->hash($user, $code),
            'attempts' => 0,
            'expires_at' => $expiresAt->getTimestamp(),
        ]);

        $user->notify(new VerifyEmailCode($code, self::VALID_FOR_MINUTES));
    }

    /**
     * Check the submitted code and, when it matches, mark the email as verified.
     * A wrong guess counts as an attempt; after too many the code is discarded.
     */
    public function verify(User $user, string $code): bool
    {
        $entry = Cache::get($this->key($user));

        if (! $entry || $entry['expires_at'] <= now()->getTimestamp()) {
            $this->forget($user);

            return false;
        }

        if (hash_equals($entry['hash'], $this->hash($user, trim($code)))) {
            $this->forget($user);

            $user->forceFill(['email_verified_at' => now()])->save();

            event(new Verified($user));

            return true;
        }

        $entry['attempts']++;

        $entry['attempts'] >= self::MAX_ATTEMPTS
            ? $this->forget($user)
            : $this->store($user, $entry);

        return false;
    }

    private function store(User $user, array $entry): void
    {
        Cache::put($this->key($user), $entry, Carbon::createFromTimestamp($entry['expires_at']));
    }

    private function forget(User $user): void
    {
        Cache::forget($this->key($user));
    }

    private function key(User $user): string
    {
        return 'email-verification-code:'.$user->getKey();
    }

    private function hash(User $user, string $code): string
    {
        return hash_hmac('sha256', $user->getKey().'|'.$user->email.'|'.$code, (string) config('app.key'));
    }
}
