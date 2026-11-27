<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
use App\Services\FC\FcOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Self-service "request account deletion": the signed-in user fills the form,
 * confirms with an email OTP, and a pending request lands with the admin.
 * Nothing is deleted here — see Admin\AccountDeletionRequestController.
 */
class AccountDeletionController extends Controller
{
    private const OTP_PURPOSE = 'account_deletion';

    private const RESEND_SECONDS = 30;

    private const SESSION_KEY = 'account_deletion_draft';

    public function __construct(private FcOtpService $otp) {}

    public function index()
    {
        $user = Auth::user();
        $latest = AccountDeletionRequest::where('user_credentials_pk', $user->pk)->latest('id')->first();

        return view('account_deletion.index', [
            'account' => $this->account($user),
            'reasons' => AccountDeletionRequest::REASONS,
            'otherReason' => AccountDeletionRequest::REASON_OTHER,
            'openRequest' => in_array($latest?->status, AccountDeletionRequest::OPEN_STATUSES, true) ? $latest : null,
            'rejectedRequest' => $latest?->status === AccountDeletionRequest::STATUS_REJECTED ? $latest : null,
        ]);
    }

    /** Step 1: validate the form, stash it in the session and email a 6-digit OTP. */
    public function sendOtp(Request $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validate([
            'user_name' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', Rule::in(AccountDeletionRequest::REASONS)],
            'comments' => ['nullable', 'string', 'max:500',
                Rule::requiredIf($request->input('reason') === AccountDeletionRequest::REASON_OTHER)],
            'confirm' => ['accepted'],
        ], [
            'comments.required' => 'Please specify the reason in Additional Comments.',
            'confirm.accepted' => 'Please confirm that you have read the conditions.',
        ]);

        if (strcasecmp(trim($data['user_name']), (string) $user->user_name) !== 0) {
            throw ValidationException::withMessages(['user_name' => 'This does not match your registered User ID.']);
        }

        $account = $this->account($user);
        if ($account['email'] === '') {
            throw ValidationException::withMessages(['user_name' => 'No registered email is on record for this account. Please contact the Computer Section, LBSNAA.']);
        }

        if ($this->pendingRequest($user->pk)) {
            throw ValidationException::withMessages(['reason' => 'You already have a deletion request under review.']);
        }

        $key = (string) $user->pk;
        $sentAt = (int) data_get(session(self::SESSION_KEY), 'sent_at', 0);
        $wait = max($this->otp->sendBlockedSeconds(self::OTP_PURPOSE, $key), $sentAt + self::RESEND_SECONDS - time());
        if ($wait > 0) {
            return response()->json(['message' => "Please wait {$wait} seconds before requesting another OTP."], 429);
        }

        $otp = $this->otp->issue(self::OTP_PURPOSE, $key);
        $minutes = $this->otp->validityMinutes();

        try {
            AccountDeletionRequest::mailer()->raw(
                "Your SARGAM 2.0 account deletion OTP is {$otp}. It is valid for {$minutes} minutes.\n\n"
                .'If you did not ask to delete your account, ignore this email and change your password.',
                fn ($mail) => $mail->to($account['email'])->subject('SARGAM 2.0 - Account deletion OTP')
            );
        } catch (\Throwable $e) {
            Log::error('Account deletion OTP email failed.', ['user_pk' => $user->pk, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Could not send the OTP email. Please try again later.'], 500);
        }

        $this->otp->registerSend(self::OTP_PURPOSE, $key);
        session([self::SESSION_KEY => [
            'reason' => $data['reason'],
            'comments' => $data['comments'] ?? null,
            'sent_at' => time(),
        ]]);

        return response()->json([
            'masked_email' => $this->maskEmail($account['email']),
            'expires_in' => $minutes * 60,
            'resend_in' => self::RESEND_SECONDS,
        ]);
    }

    /** Step 2: check the OTP and file the request. */
    public function verify(Request $request): JsonResponse
    {
        $request->validate(['otp' => ['required', 'string', 'digits:6']], ['otp.*' => 'Enter the 6-digit OTP.']);

        $user = Auth::user();
        $key = (string) $user->pk;
        $draft = session(self::SESSION_KEY);

        if (! is_array($draft)) {
            throw ValidationException::withMessages(['otp' => 'Your session expired. Please start the request again.']);
        }

        if ($wait = $this->otp->verifyBlockedSeconds(self::OTP_PURPOSE, $key)) {
            return response()->json(['message' => 'Too many wrong attempts. Try again in '.ceil($wait / 60).' minute(s).'], 429);
        }

        if (! $this->otp->verify(self::OTP_PURPOSE, $key, $request->input('otp'))) {
            $this->otp->registerVerifyFailure(self::OTP_PURPOSE, $key);
            throw ValidationException::withMessages(['otp' => 'The OTP is invalid or has expired.']);
        }

        $this->otp->clearVerifyFailures(self::OTP_PURPOSE, $key);
        session()->forget(self::SESSION_KEY);

        $deletion = $this->pendingRequest($user->pk) ?? AccountDeletionRequest::create([
            'user_credentials_pk' => $user->pk,
            'user_name' => (string) $user->user_name,
            'reason' => $draft['reason'],
            'comments' => $draft['comments'],
            'status' => AccountDeletionRequest::STATUS_PENDING,
        ] + collect($this->account($user))->only(['full_name', 'email', 'mobile'])->all());

        if ($deletion->wasRecentlyCreated) {
            $deletion->notifyNextStage();
        }

        return response()->json([
            'code' => $deletion->code,
            'message' => "Your request {$deletion->code} has been submitted for review.",
        ]);
    }

    private function pendingRequest($userPk): ?AccountDeletionRequest
    {
        return AccountDeletionRequest::where('user_credentials_pk', $userPk)
            ->whereIn('status', AccountDeletionRequest::OPEN_STATUSES)
            ->latest('id')
            ->first();
    }

    private function account($user): array
    {
        return [
            'user_name' => (string) $user->user_name,
            'full_name' => trim($user->first_name.' '.$user->last_name),
            'email' => trim((string) $user->email_id),
            'mobile' => trim((string) $user->mobile_no),
        ];
    }

    /** manish.kumar@gov.in -> m*****.k****@gov.in */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $local = preg_replace_callback('/[^.]+/u', fn ($m) => mb_substr($m[0], 0, 1).str_repeat('*', max(1, mb_strlen($m[0]) - 1)), $local);

        return $local.'@'.$domain;
    }
}
