<?php

namespace App\Services\FC;

use App\Services\Messaging\GupshupSmsService;
use Illuminate\Support\Facades\Log;

/**
 * FC outbound notifications (SMS now; WhatsApp can be added later without changing triggers).
 * Best-effort: failures are logged and never block the caller.
 * Memo/Notice (D1–D5) intentionally not wired yet.
 */
class FcNotifyService
{
    public function __construct(
        private GupshupSmsService $sms,
        private FcOtpService $otp,
    ) {
    }

    /** A1 — Registration OTP. Returns ['otp' => ?string, 'sent' => bool]. */
    public function registrationOtp(
        ?string $mobile,
        string $applicantName,
        string $programmeName,
        ?int $registrationPk = null,
    ): array {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            return ['otp' => null, 'sent' => false];
        }

        $code = $this->otp->issue('registration', $mobile);
        $sent = $this->sendSms('registration_otp', $mobile, [
            'Applicant_Name' => $applicantName !== '' ? $applicantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'OTP' => $code,
            'OTP_Validity' => (string) $this->otp->validityMinutes(),
            'Institute_Name' => $this->institute(),
        ], $registrationPk);

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
    ): void {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            return;
        }

        $this->sendSms('credentials_created', $mobile, [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Registration_ID' => $username,
            'Password' => $password,
            'Portal_Link' => $this->portal(),
            'Institute_Name' => $this->institute(),
        ], $registrationPk);
    }

    /** A3 — Successful Registration */
    public function registrationSuccessful(
        ?string $mobile,
        string $participantName,
        string $programmeName,
        string $registrationId,
        ?int $registrationPk = null,
    ): void {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            return;
        }

        $this->sendSms('registration_successful', $mobile, [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Registration_ID' => $registrationId !== '' ? $registrationId : 'N/A',
            'Portal_Link' => $this->portal(),
            'Institute_Name' => $this->institute(),
        ], $registrationPk);
    }

    /** A4 — Forgot Password OTP */
    public function forgotPasswordOtp(
        ?string $mobile,
        string $participantName,
        ?int $registrationPk = null,
    ): ?string {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            return null;
        }

        $code = $this->otp->issue('forgot_password', $mobile);
        $this->sendSms('forgot_password_otp', $mobile, [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'OTP' => $code,
            'OTP_Validity' => $this->otp->validityMinutes(),
            'Institute_Name' => $this->institute(),
        ], $registrationPk);

        return $code;
    }

    /** A5 — Password Change OTP */
    public function passwordChangeOtp(
        ?string $mobile,
        string $participantName,
        ?int $registrationPk = null,
    ): ?string {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            return null;
        }

        $code = $this->otp->issue('password_change', $mobile);
        $this->sendSms('password_change_otp', $mobile, [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'OTP' => $code,
            'OTP_Validity' => $this->otp->validityMinutes(),
            'Institute_Name' => $this->institute(),
        ], $registrationPk);

        return $code;
    }

    /** B1 — Individual Form Step Incomplete */
    public function formStepIncomplete(
        ?string $mobile,
        string $participantName,
        string $stepName,
        ?int $registrationPk = null,
    ): void {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            return;
        }

        $this->sendSms('form_step_incomplete', $mobile, [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Step_Name' => $stepName !== '' ? $stepName : 'registration',
            'Portal_Link' => $this->portal(),
            'Institute_Name' => $this->institute(),
        ], $registrationPk);
    }

    /** B2 — Registration Steps Pending */
    public function registrationPending(
        ?string $mobile,
        string $participantName,
        string $programmeName,
        string $lastDate,
        ?int $registrationPk = null,
    ): void {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            return;
        }

        $this->sendSms('registration_pending', $mobile, [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Last_Date' => $lastDate !== '' ? $lastDate : 'the deadline',
            'Portal_Link' => $this->portal(),
            'Institute_Name' => $this->institute(),
        ], $registrationPk);
    }

    /** C1 — Exemption Confirmation */
    public function exemptionConfirmation(
        ?string $mobile,
        string $applicantName,
        string $programmeName,
        string $exemptionCategory,
        string $applicationNo,
        ?int $registrationPk = null,
    ): void {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            return;
        }

        $this->sendSms('exemption_confirmation', $mobile, [
            'Applicant_Name' => $applicantName !== '' ? $applicantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Exemption_Category' => $exemptionCategory !== '' ? $exemptionCategory : 'N/A',
            'Application_No' => $applicationNo !== '' ? $applicationNo : 'N/A',
            'Institute_Name' => $this->institute(),
        ], $registrationPk);
    }

    /** D6 — Feedback Request */
    public function feedbackRequest(
        ?string $mobile,
        string $participantName,
        string $programmeName,
        string $lastDate,
        ?string $feedbackLink = null,
        ?int $registrationPk = null,
    ): void {
        $mobile = trim((string) $mobile);
        if ($mobile === '') {
            return;
        }

        $this->sendSms('feedback_request', $mobile, [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Last_Date' => $lastDate !== '' ? $lastDate : 'the deadline',
            'Feedback_Link' => $feedbackLink ?: $this->portal(),
            'Institute_Name' => $this->institute(),
        ], $registrationPk);
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
            if (!$sent) {
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
}
