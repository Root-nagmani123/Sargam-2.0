<?php

namespace App\Services\FC;

use Illuminate\Support\Facades\Cache;

/**
 * Short-lived OTP store for FC SMS flows (A1 / A4 / A5).
 */
class FcOtpService
{
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
