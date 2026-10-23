<?php

namespace App\Services\COE;

use App\Services\Messaging\GupshupSmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * One-time password the faculty confirms a question paper freeze with.
 *
 * One code per (user, paper), held hashed in the cache. Sending and wrong
 * entries are throttled separately (CWE-307), like FcOtpService.
 *
 * Delivery needs a DLT-approved SMS template (services.coe_freeze_otp). Until
 * one is registered, local/testing write the code to the log so the flow can be
 * exercised, and every other environment refuses to freeze.
 */
class FreezeOtpService
{
    /** Matches the freeze modal's OTP boxes (admin/coe/partials/freeze_modal). */
    public const DIGITS = 5;

    private const VALID_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    private const BLOCK_SECONDS = 600;

    public function __construct(private GupshupSmsService $sms)
    {
    }

    /** @throws ValidationException when throttled, there is no number, or the SMS cannot go out */
    public function send(int $userId, int $paperId, ?string $mobile): void
    {
        $mobile = preg_replace('/\D+/', '', (string) $mobile) ?? '';
        if (strlen($mobile) < 10) {
            $this->fail('There is no mobile number on your faculty record to send the OTP to. Contact the COE office.');
        }

        $sendKey = $this->throttleKey('send', $userId, $paperId);
        if (RateLimiter::tooManyAttempts($sendKey, self::MAX_ATTEMPTS)) {
            $this->fail('Too many OTP requests. Try again in '.$this->minutes(RateLimiter::availableIn($sendKey)).'.');
        }

        $code = str_pad((string) random_int(0, 10 ** self::DIGITS - 1), self::DIGITS, '0', STR_PAD_LEFT);
        $this->deliver($mobile, $code);

        Cache::put($this->codeKey($userId, $paperId), Hash::make($code), now()->addMinutes(self::VALID_MINUTES));
        RateLimiter::hit($sendKey, self::BLOCK_SECONDS);
    }

    /**
     * Consumes the code on success.
     *
     * @throws ValidationException when wrong, expired, or too many wrong entries
     */
    public function verify(int $userId, int $paperId, string $otp): void
    {
        $verifyKey = $this->throttleKey('verify', $userId, $paperId);
        if (RateLimiter::tooManyAttempts($verifyKey, self::MAX_ATTEMPTS)) {
            $this->fail('Too many wrong OTPs. Try again in '.$this->minutes(RateLimiter::availableIn($verifyKey)).'.');
        }

        $hash = Cache::get($this->codeKey($userId, $paperId));
        if ($hash === null) {
            $this->fail('The OTP has expired. Use Resend OTP to get a new one.');
        }
        if (! Hash::check($otp, $hash)) {
            RateLimiter::hit($verifyKey, self::BLOCK_SECONDS);
            $this->fail('The OTP is incorrect.');
        }

        Cache::forget($this->codeKey($userId, $paperId));
        RateLimiter::clear($verifyKey);
    }

    /** "+91 ******3274" for the modal, or a neutral text when there is no number. */
    public static function mask(?string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile) ?? '';

        return strlen($digits) >= 10 ? '+91 ******'.substr($digits, -4) : 'registered with the COE office';
    }

    private function deliver(string $mobile, string $code): void
    {
        $templateId = config('services.coe_freeze_otp.dlt_template_id');
        $body = (string) config('services.coe_freeze_otp.message');

        if (! $templateId || $body === '') {
            if (app()->environment(['local', 'testing'])) {
                Log::info('COE freeze OTP (no SMS template configured, not sent)', [
                    'mobile' => self::mask($mobile),
                    'otp' => $code,
                ]);

                return;
            }
            $this->fail('Freeze OTP SMS is not set up yet. Contact the COE office.');
        }

        if (! $this->sms->send($mobile, str_replace('{#var#}', $code, $body), $templateId, [$code])) {
            $this->fail('The OTP could not be sent. Please try again.');
        }
    }

    private function codeKey(int $userId, int $paperId): string
    {
        return "coe_freeze_otp:{$userId}:{$paperId}";
    }

    private function throttleKey(string $kind, int $userId, int $paperId): string
    {
        return "coe_freeze_otp_throttle:{$kind}:{$userId}:{$paperId}";
    }

    private function minutes(int $seconds): string
    {
        $m = max(1, (int) ceil($seconds / 60));

        return $m.' minute'.($m === 1 ? '' : 's');
    }

    /** @return never */
    private function fail(string $message): void
    {
        throw ValidationException::withMessages(['otp' => $message]);
    }
}
