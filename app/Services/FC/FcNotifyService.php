<?php

namespace App\Services\FC;

use App\Models\PathPage;
use App\Services\Messaging\GupshupSmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * FC outbound notifications: SMS + Email together (same triggers).
 * Best-effort: failures are logged and never block the caller.
 * Memo/Notice (D1–D5) and Feedback (D6) intentionally not wired.
 */
class FcNotifyService
{
    /** Skip further emails in this request after repeated SMTP failures (bulk safety). */
    protected bool $emailCircuitOpen = false;

    protected int $emailFailStreak = 0;

    public function __construct(
        private GupshupSmsService $sms,
        private FcOtpService $otp,
    ) {
    }

    /** Call at the start of admin bulk send. */
    public function resetEmailCircuit(): void
    {
        $this->emailCircuitOpen = false;
        $this->emailFailStreak = 0;
    }

    public function isEmailCircuitOpen(): bool
    {
        return $this->emailCircuitOpen;
    }

    /** A1 — Registration OTP. Returns ['otp' => ?string, 'sent' => bool]. */
    public function registrationOtp(
        ?string $mobile,
        string $applicantName,
        string $programmeName,
        ?int $registrationPk = null,
        ?string $email = null,
    ): array {
        $mobile = trim((string) $mobile);
        $email = $this->resolveEmail($email, $registrationPk);
        if ($mobile === '' && $email === null) {
            return ['otp' => null, 'sent' => false];
        }

        $otpKey = $mobile !== '' ? $mobile : ('email:'.$email);
        $code = $this->otp->issue('registration', $otpKey);
        $replacements = [
            'Applicant_Name' => $applicantName !== '' ? $applicantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'OTP' => $code,
            'OTP_Validity' => (string) $this->otp->validityMinutes(),
            'Institute_Name' => $this->institute(),
        ];

        $sent = false;
        if ($mobile !== '') {
            $sent = $this->sendSms('registration_otp', $mobile, $replacements, $registrationPk) || $sent;
        }
        if ($email !== null) {
            $sent = $this->sendEmail('registration_otp', $email, $replacements, $registrationPk) || $sent;
        }

        return ['otp' => $code, 'sent' => $sent];
    }

    /** A2 — Credentials Created */
    public function credentialsCreated(
        ?string $mobile,
        string $participantName,
        string $programmeName,
        string $username,
        string $password,
        ?int $registrationPk = null,
        ?string $email = null,
    ): void {
        $mobile = trim((string) $mobile);
        $email = $this->resolveEmail($email, $registrationPk);
        if ($mobile === '' && $email === null) {
            return;
        }

        $replacements = [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Registration_ID' => $username,
            'Password' => $password,
            'Portal_Link' => $this->portal(),
            'Institute_Name' => $this->institute(),
        ];

        if ($mobile !== '') {
            $this->sendSms('credentials_created', $mobile, $replacements, $registrationPk);
        }
        if ($email !== null) {
            $this->sendEmail('credentials_created', $email, $replacements, $registrationPk);
        }
    }

    /** A3 — Successful Registration */
    public function registrationSuccessful(
        ?string $mobile,
        string $participantName,
        string $programmeName,
        string $registrationId,
        ?int $registrationPk = null,
        ?string $email = null,
    ): void {
        $mobile = trim((string) $mobile);
        $email = $this->resolveEmail($email, $registrationPk);
        if ($mobile === '' && $email === null) {
            return;
        }

        $replacements = [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Registration_ID' => $registrationId !== '' ? $registrationId : 'N/A',
            'Programme_Dates' => $this->programmeDates(),
            'Portal_Link' => $this->portal(),
            'Institute_Name' => $this->institute(),
        ];

        if ($mobile !== '') {
            $this->sendSms('registration_successful', $mobile, $replacements, $registrationPk);
        }
        if ($email !== null) {
            $this->sendEmail('registration_successful', $email, $replacements, $registrationPk);
        }
    }

    /** A4 — Forgot Password OTP */
    public function forgotPasswordOtp(
        ?string $mobile,
        string $participantName,
        ?int $registrationPk = null,
        ?string $email = null,
    ): ?string {
        $mobile = trim((string) $mobile);
        $email = $this->resolveEmail($email, $registrationPk);
        if ($mobile === '' && $email === null) {
            return null;
        }

        $otpKey = $mobile !== '' ? $mobile : ('email:'.$email);
        $code = $this->otp->issue('forgot_password', $otpKey);
        $replacements = [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'OTP' => $code,
            'OTP_Validity' => (string) $this->otp->validityMinutes(),
            'Institute_Name' => $this->institute(),
        ];

        if ($mobile !== '') {
            $this->sendSms('forgot_password_otp', $mobile, $replacements, $registrationPk);
        }
        if ($email !== null) {
            $this->sendEmail('forgot_password_otp', $email, $replacements, $registrationPk);
        }

        return $code;
    }

    /** A5 — Password Change OTP */
    public function passwordChangeOtp(
        ?string $mobile,
        string $participantName,
        ?int $registrationPk = null,
        ?string $email = null,
    ): ?string {
        $mobile = trim((string) $mobile);
        $email = $this->resolveEmail($email, $registrationPk);
        if ($mobile === '' && $email === null) {
            return null;
        }

        $otpKey = $mobile !== '' ? $mobile : ('email:'.$email);
        $code = $this->otp->issue('password_change', $otpKey);
        $replacements = [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'OTP' => $code,
            'OTP_Validity' => (string) $this->otp->validityMinutes(),
            'Institute_Name' => $this->institute(),
        ];

        if ($mobile !== '') {
            $this->sendSms('password_change_otp', $mobile, $replacements, $registrationPk);
        }
        if ($email !== null) {
            $this->sendEmail('password_change_otp', $email, $replacements, $registrationPk);
        }

        return $code;
    }

    /** B1 — Individual Form Step Incomplete */
    public function formStepIncomplete(
        ?string $mobile,
        string $participantName,
        string $stepName,
        ?int $registrationPk = null,
        ?string $email = null,
    ): void {
        $mobile = trim((string) $mobile);
        $email = $this->resolveEmail($email, $registrationPk);
        if ($mobile === '' && $email === null) {
            return;
        }

        $replacements = [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Step_Name' => $stepName !== '' ? $stepName : 'registration',
            'Portal_Link' => $this->portal(),
            'Institute_Name' => $this->institute(),
        ];

        if ($mobile !== '') {
            $this->sendSms('form_step_incomplete', $mobile, $replacements, $registrationPk);
        }
        if ($email !== null) {
            $this->sendEmail('form_step_incomplete', $email, $replacements, $registrationPk);
        }
    }

    /** B2 — Registration Steps Pending */
    public function registrationPending(
        ?string $mobile,
        string $participantName,
        string $programmeName,
        string $lastDate,
        ?int $registrationPk = null,
        ?string $email = null,
        ?string $pendingSteps = null,
    ): void {
        $mobile = trim((string) $mobile);
        $email = $this->resolveEmail($email, $registrationPk);
        if ($mobile === '' && $email === null) {
            return;
        }

        $replacements = [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Last_Date' => $lastDate !== '' ? $lastDate : 'the deadline',
            'Pending_Steps' => ($pendingSteps !== null && trim($pendingSteps) !== '')
                ? trim($pendingSteps)
                : 'pending steps',
            'Portal_Link' => $this->portal(),
            'Institute_Name' => $this->institute(),
        ];

        if ($mobile !== '') {
            $this->sendSms('registration_pending', $mobile, $replacements, $registrationPk);
        }
        if ($email !== null) {
            $this->sendEmail('registration_pending', $email, $replacements, $registrationPk);
        }
    }

    /** C1 — Exemption Confirmation */
    public function exemptionConfirmation(
        ?string $mobile,
        string $applicantName,
        string $programmeName,
        string $exemptionCategory,
        string $applicationNo,
        ?int $registrationPk = null,
        ?string $email = null,
    ): void {
        $mobile = trim((string) $mobile);
        $email = $this->resolveEmail($email, $registrationPk);
        if ($mobile === '' && $email === null) {
            return;
        }

        $replacements = [
            'Applicant_Name' => $applicantName !== '' ? $applicantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Exemption_Category' => $exemptionCategory !== '' ? $exemptionCategory : 'N/A',
            'Application_No' => $applicationNo !== '' ? $applicationNo : 'N/A',
            'Submission_Date' => now()->format('d-M-Y'),
            'Institute_Name' => $this->institute(),
        ];

        if ($mobile !== '') {
            $this->sendSms('exemption_confirmation', $mobile, $replacements, $registrationPk);
        }
        if ($email !== null) {
            $this->sendEmail('exemption_confirmation', $email, $replacements, $registrationPk);
        }
    }

    public function otpService(): FcOtpService
    {
        return $this->otp;
    }

    protected function programme(string $programmeName): string
    {
        return $programmeName !== ''
            ? $programmeName
            : (string) config('gupshup.default_programme_name', 'Foundation Course');
    }

    protected function portal(): string
    {
        return rtrim((string) config('gupshup.portal_url'), '/');
    }

    protected function institute(): string
    {
        return (string) config('gupshup.institute_name', 'LBSNAA');
    }

    protected function programmeDates(): string
    {
        try {
            $path = PathPage::query()->first();
            $start = $path->course_start_date ?? null;
            $end = $path->course_end_date ?? null;
            if ($start && $end) {
                return Carbon::parse($start)->format('d-M-Y').' to '.Carbon::parse($end)->format('d-M-Y');
            }
            if ($start) {
                return Carbon::parse($start)->format('d-M-Y');
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return 'N/A';
    }

    protected function resolveEmail(?string $email, ?int $registrationPk): ?string
    {
        $email = trim((string) $email);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        if ($registrationPk && Schema::hasTable('fc_registration_master')
            && Schema::hasColumn('fc_registration_master', 'email')) {
            $fromDb = trim((string) (DB::table('fc_registration_master')
                ->where('pk', $registrationPk)
                ->value('email') ?? ''));
            if ($fromDb !== '' && filter_var($fromDb, FILTER_VALIDATE_EMAIL)) {
                return $fromDb;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string|int|float|null>  $replacements
     */
    protected function sendSms(
        string $templateKey,
        string $mobile,
        array $replacements,
        ?int $registrationPk = null,
    ): bool {
        try {
            $sent = $this->sms->sendTemplate($templateKey, $mobile, $replacements);
            if (! $sent) {
                Log::warning('FcNotifyService: SMS not sent.', [
                    'template' => $templateKey,
                    'registration_pk' => $registrationPk,
                ]);
            }

            return $sent;
        } catch (\Throwable $e) {
            Log::error('FcNotifyService: SMS failed: '.$e->getMessage(), [
                'template' => $templateKey,
                'registration_pk' => $registrationPk,
            ]);

            return false;
        }
    }

    /**
     * @param  array<string, string|int|float|null>  $replacements
     */
    protected function sendEmail(
        string $templateKey,
        string $email,
        array $replacements,
        ?int $registrationPk = null,
    ): bool {
        if ($this->emailCircuitOpen) {
            return false;
        }

        try {
            $template = config("fc_email.templates.{$templateKey}");
            if (! is_array($template) || empty($template['subject']) || empty($template['body'])) {
                Log::error('FcNotifyService: unknown email template.', ['template' => $templateKey]);

                return false;
            }

            $subject = $this->applyReplacements((string) $template['subject'], $replacements);
            $body = $this->applyReplacements((string) $template['body'], $replacements);
            $fromAddress = config('mail.from.address') ?: 'no-reply@lbsnaa.gov.in';
            $fromName = config('mail.from.name') ?: $this->institute();

            Mail::raw($body, function ($mail) use ($email, $subject, $fromAddress, $fromName) {
                $mail->from($fromAddress, $fromName)
                    ->to($email)
                    ->subject($subject);
            });

            $this->emailFailStreak = 0;

            return true;
        } catch (\Throwable $e) {
            $this->emailFailStreak++;
            Log::error('FcNotifyService: Email failed: '.$e->getMessage(), [
                'template' => $templateKey,
                'registration_pk' => $registrationPk,
                'email' => $email,
            ]);

            // After repeated SMTP auth/timeout failures, skip remaining emails this request
            // so bulk SMS is not blocked for hundreds of recipients.
            if ($this->emailFailStreak >= 3) {
                $this->emailCircuitOpen = true;
                Log::warning('FcNotifyService: email circuit open — further emails skipped for this request.', [
                    'fail_streak' => $this->emailFailStreak,
                ]);
            }

            return false;
        }
    }

    /**
     * @param  array<string, string|int|float|null>  $replacements
     */
    protected function applyReplacements(string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $text = str_replace('{'.$key.'}', (string) ($value ?? ''), $text);
        }

        return $text;
    }
}
