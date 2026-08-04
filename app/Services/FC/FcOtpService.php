<?php

namespace App\Services\FC;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Short-lived OTP store for FC SMS flows (A1 / A4 / A5).
 */
class FcOtpService
{
    /** Max OTP sends (per number) or wrong verifications before a temporary block. */
    public const MAX_ATTEMPTS = 5;

    /** How long the block lasts once the limit is reached, in seconds. */
    public const BLOCK_SECONDS = 600; // 10 minutes

    // ── Abuse throttling (CWE-307 / OTP flooding) ────────────────────────────
    // Keyed by mobile number so the limit follows the target number, not the
    // session/IP. Send and verify are counted separately.

    /** Seconds remaining if sending an OTP to this number is currently blocked, else 0. */
    public function sendBlockedSeconds(string $purpose, string $mobile): int
    {
        $key = $this->throttleKey('send', $purpose, $mobile);

        return RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)
            ? RateLimiter::availableIn($key)
            : 0;
    }

    /** Record one OTP send (call only after a send actually succeeds). */
    public function registerSend(string $purpose, string $mobile): void
    {
        RateLimiter::hit($this->throttleKey('send', $purpose, $mobile), self::BLOCK_SECONDS);
    }

    /** Seconds remaining if verifying an OTP for this number is currently blocked, else 0. */
    public function verifyBlockedSeconds(string $purpose, string $mobile): int
    {
        $key = $this->throttleKey('verify', $purpose, $mobile);

        return RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)
            ? RateLimiter::availableIn($key)
            : 0;
    }

    /** Record one wrong OTP entry for this number. */
    public function registerVerifyFailure(string $purpose, string $mobile): void
    {
        RateLimiter::hit($this->throttleKey('verify', $purpose, $mobile), self::BLOCK_SECONDS);
    }

    /** Clear the wrong-attempt counter for this number (call on a successful verify). */
    public function clearVerifyFailures(string $purpose, string $mobile): void
    {
        RateLimiter::clear($this->throttleKey('verify', $purpose, $mobile));
    }

    protected function throttleKey(string $kind, string $purpose, string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        return 'fc_otp_throttle:'.$kind.':'.$purpose.':'.$digits;
    }

    public function validityMinutes(): int
    {
        return max(1, (int) config('gupshup.otp_validity_minutes', 10));
    }

    public function generate(): string
    {
        return (string) random_int(100000, 999999);
    }

    public function put(string $purpose, string $mobile, string $otp): void
    {
        Cache::put($this->key($purpose, $mobile), $otp, now()->addMinutes($this->validityMinutes()));
    }

    public function issue(string $purpose, string $mobile): string
    {
        $otp = $this->generate();
        $this->put($purpose, $mobile, $otp);

        return $otp;
    }

    public function verify(string $purpose, string $mobile, ?string $otp): bool
    {
        $otp = preg_replace('/\D+/', '', trim((string) $otp)) ?? '';
        if ($otp === '') {
            return false;
        }

        $cached = Cache::get($this->key($purpose, $mobile));
        if ($cached === null || !hash_equals((string) $cached, $otp)) {
            return false;
        }

        Cache::forget($this->key($purpose, $mobile));

        return true;
    }

    public function hasPending(string $purpose, string $mobile): bool
    {
        return Cache::has($this->key($purpose, $mobile));
    }

    protected function key(string $purpose, string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        return 'fc_otp:'.$purpose.':'.$digits;
    }
}
