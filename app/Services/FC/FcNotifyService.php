<?php

namespace App\Services\FC;

use App\Models\PathPage;
use App\Models\FC\FcForm;
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

    /**
     * A2 — Credentials Created.
     * The DLT-approved FC-CRED1 template (id 1477178461144840794) has a Password
     * variable slot; omitting it sends a 5-variable message against a 6-variable
     * approved template, which the gateway rejects as a template mismatch. Password
     * is therefore included here to match the approved text exactly.
     */
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
            'Portal_Link' => $this->loginPortal($registrationPk),
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
            'Portal_Link' => $this->loginPortal($registrationPk),
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

    /**
     * B1 — Individual Form Step Incomplete. Returns true if at least one channel delivered.
     *
     * $stepName is the single next-pending step (used as-is for SMS — the DLT-approved
     * template has one fixed {Step_Name} slot and cannot list multiple steps). $pendingSteps,
     * when given, is the full comma-separated list of all pending steps and is used to render
     * a bullet list in the email (which has no DLT constraint).
     */
    public function formStepIncomplete(
        ?string $mobile,
        string $participantName,
        string $stepName,
        ?int $registrationPk = null,
        ?string $email = null,
        string $programmeName = '',
        ?string $pendingSteps = null,
    ): bool {
        $mobile = trim((string) $mobile);
        $email = $this->resolveEmail($email, $registrationPk);
        if ($mobile === '' && $email === null) {
            return false;
        }

        $stepName = $stepName !== '' ? $stepName : 'registration';
        $pendingStepsList = $this->formatPendingStepsList($pendingSteps, $stepName);

        $replacements = [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Step_Name' => $stepName,
            'Pending_Steps' => $pendingStepsList,
            'Programme_Name' => $this->programme($programmeName),
            'Portal_Link' => $this->loginPortal($registrationPk),
            'Institute_Name' => $this->institute(),
        ];

        $delivered = false;
        if ($mobile !== '') {
            $delivered = $this->sendSms('form_step_incomplete', $mobile, $replacements, $registrationPk) || $delivered;
        }
        if ($email !== null) {
            $delivered = $this->sendEmail('form_step_incomplete', $email, $replacements, $registrationPk) || $delivered;
        }

        return $delivered;
    }

    /** Renders the pending step names as a "- Step" bulleted list for the B1 email body. */
    protected function formatPendingStepsList(?string $pendingSteps, string $fallbackStep): string
    {
        $steps = array_values(array_filter(array_map('trim', explode(',', (string) $pendingSteps))));
        if ($steps === []) {
            $steps = [$fallbackStep];
        }

        return implode("\n", array_map(fn (string $step) => '- '.$step, $steps));
    }

    /** B2 — Registration Steps Pending. Returns true if at least one channel delivered. */
    public function registrationPending(
        ?string $mobile,
        string $participantName,
        string $programmeName,
        string $lastDate,
        ?int $registrationPk = null,
        ?string $email = null,
        ?string $pendingSteps = null,
    ): bool {
        $mobile = trim((string) $mobile);
        $email = $this->resolveEmail($email, $registrationPk);
        if ($mobile === '' && $email === null) {
            return false;
        }

        $replacements = [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Last_Date' => $lastDate !== '' ? $lastDate : 'the deadline',
            'Pending_Steps' => ($pendingSteps !== null && trim($pendingSteps) !== '')
                ? trim($pendingSteps)
                : 'pending steps',
            'Portal_Link' => $this->portal($registrationPk),
            'Institute_Name' => $this->institute(),
        ];

        $delivered = false;
        if ($mobile !== '') {
            $delivered = $this->sendSms('registration_pending', $mobile, $replacements, $registrationPk) || $delivered;
        }
        if ($email !== null) {
            $delivered = $this->sendEmail('registration_pending', $email, $replacements, $registrationPk) || $delivered;
        }

        return $delivered;
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

    /**
     * B3 — Travel Pending. All registration form steps are complete but the
     * (separately tracked) travel plan has not been submitted yet.
     * Email only — no DLT-approved SMS template exists for this reminder yet.
     * Returns true if delivered.
     */
    public function travelPending(
        ?string $mobile,
        string $participantName,
        string $programmeName,
        ?int $registrationPk = null,
        ?string $email = null,
    ): bool {
        $email = $this->resolveEmail($email, $registrationPk);
        if ($email === null) {
            return false;
        }

        $replacements = [
            'Participant_Name' => $participantName !== '' ? $participantName : 'Candidate',
            'Programme_Name' => $this->programme($programmeName),
            'Portal_Link' => $this->loginPortal($registrationPk),
            'Institute_Name' => $this->institute(),
        ];

        return $this->sendEmail('travel_pending', $email, $replacements, $registrationPk);
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

    /**
     * Portal link sent in SMS/email. Must be the query-string landing page
     * (…/registration/foundation-course?form=TOKEN), not the path-based dashboard
     * URL (…/fc-reg/forms/TOKEN) — BSNL's CTA whitelisting only accepts a dynamic
     * URL whose variable part is a "?"-prefixed query string with no "/" in it.
     */
    protected function portal(?int $registrationPk = null): string
    {
        $form = $this->resolveFormForPortal($registrationPk);
        if ($form) {
            return $form->landingPageUrl();
        }

        return rtrim((string) config('gupshup.portal_url'), '/');
    }

    /**
     * Same resolution + query-string constraint as portal(), but points already-
     * registered trainees straight to the login page instead of the registration
     * landing page. Used where the recipient has nothing left to register for
     * (registration successful, or only a non-form step like travel is pending).
     */
    protected function loginPortal(?int $registrationPk = null): string
    {
        $form = $this->resolveFormForPortal($registrationPk);
        if ($form) {
            return $form->loginUrl();
        }

        return rtrim((string) config('gupshup.portal_url'), '/');
    }

    protected function resolveFormForPortal(?int $registrationPk): ?FcForm
    {
        $sessionFormId = session(FcRegistrationIntentService::SESSION_FORM_ID);
        if (is_numeric($sessionFormId) && (int) $sessionFormId > 0) {
            $fromSession = FcForm::query()
                ->whereKey((int) $sessionFormId)
                ->where('is_active', true)
                ->first();
            if ($fromSession) {
                return $fromSession;
            }
        }

        if ($registrationPk && $registrationPk > 0) {
            $activeForm = FcForm::activeRegistrationDynamicForm();
            $trackerTable = $activeForm?->trackerStorageTable() ?? 'student_masters';

            if (Schema::hasTable($trackerTable) && Schema::hasColumn($trackerTable, 'form_id')) {
                $userCol = fc_user_col($trackerTable);
                $formId = (int) (DB::table($trackerTable)
                    ->where($userCol, $registrationPk)
                    ->value('form_id') ?? 0);

                if ($formId > 0) {
                    $fromTracker = FcForm::query()
                        ->whereKey($formId)
                        ->where('is_active', true)
                        ->first();
                    if ($fromTracker) {
                        return $fromTracker;
                    }
                }
            }
        }

        return FcForm::activeRegistrationDynamicForm();
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

            $emailReplacements = $replacements;
            $emailReplacements['Institute_Signature'] = "Lal Bahadur Shastri National Academy of Administration (LBSNAA)\nMussoorie – 248179, Uttarakhand";
            $emailReplacements['Login_Link_Label'] = 'Login Link:';

            $subject = $this->applyReplacements((string) $template['subject'], $replacements);
            $body = $this->applyReplacements((string) $template['body'], $emailReplacements, markForBold: true);
            $htmlBody = $this->formatEmailBodyAsHtml($body);
            $fromAddress = config('mail.from.address') ?: 'no-reply@lbsnaa.gov.in';
            $fromName = config('mail.from.name') ?: $this->institute();

            Mail::html($htmlBody, function ($mail) use ($email, $subject, $fromAddress, $fromName) {
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

    /** Sentinel markers wrapped around a replacement value (or static label text in
     *  the template itself) so it can be made bold after HTML-escaping, without ever
     *  HTML-escaping the markers themselves. */
    private const BOLD_MARK_OPEN = "\x01B\x01";

    private const BOLD_MARK_CLOSE = "\x01/B\x01";

    /** Replacement keys whose value must stay plain text even when markForBold is on
     *  — a URL is functional content, not emphasis, and shouldn't compete visually
     *  with the actually-important bolded fields (name, OTP, programme, etc.). */
    private const BOLD_EXCLUDED_KEYS = ['Portal_Link'];

    /**
     * @param  array<string, string|int|float|null>  $replacements
     */
    protected function applyReplacements(string $text, array $replacements, bool $markForBold = false): string
    {
        foreach ($replacements as $key => $value) {
            $value = (string) ($value ?? '');
            if ($markForBold && $value !== '' && ! in_array($key, self::BOLD_EXCLUDED_KEYS, true)) {
                $value = self::BOLD_MARK_OPEN.$value.self::BOLD_MARK_CLOSE;
            }
            $text = str_replace('{'.$key.'}', $value, $text);
        }

        return $text;
    }

    protected function formatEmailBodyAsHtml(string $body): string
    {
        $escaped = htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escaped = str_replace(
            [self::BOLD_MARK_OPEN, self::BOLD_MARK_CLOSE],
            ['<strong>', '</strong>'],
            $escaped
        );
        $escaped = nl2br($escaped, false);

        return preg_replace_callback(
            '/(https?:\/\/[^\s<]+)/i',
            function (array $m): string {
                $url = $m[1];
                $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return '<a href="'.$safeUrl.'" target="_blank" rel="noopener noreferrer" style="color:#1a3c6e;font-weight:600;">Click here to login</a>';
            },
            $escaped
        ) ?? $escaped;
    }
}
