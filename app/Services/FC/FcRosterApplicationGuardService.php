<?php

namespace App\Services\FC;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Mutual exclusion and one-time rules for fc_registration_master
 * public registration vs exemption flows.
 *
 * application_type = 0 (N/A) is an admin reset: both registration and exemption are allowed again.
 *
 * Some services must complete the Foundation Course and may not apply for an
 * exemption at all — see hasExemptionRestrictedService().
 */
class FcRosterApplicationGuardService
{
    public const APPLICATION_NA = 0;

    public const APPLICATION_REGISTRATION = 1;

    public const APPLICATION_EXEMPTION = 2;

    /** Cache key for the resolved restricted-service id list. */
    private const RESTRICTED_SERVICES_CACHE_KEY = 'fc_exemption_restricted_service_ids';

    /**
     * Admin set status to N/A — public flows are reopened (ignore prior registration/exemption flags).
     */
    public function isApplicationReset(object $row): bool
    {
        $type = $row->application_type ?? null;

        if ($type === null || $type === '') {
            return true;
        }

        return (int) $type === self::APPLICATION_NA;
    }

    public function hasCompletedRegistration(object $row): bool
    {
        if ($this->isApplicationReset($row)) {
            return false;
        }

        if ((int) ($row->application_type ?? 0) === self::APPLICATION_EXEMPTION) {
            return false;
        }

        return (int) ($row->is_registered ?? 0) === 1
            || (int) ($row->application_type ?? 0) === self::APPLICATION_REGISTRATION;
    }

    public function hasStagedCredentials(object $row): bool
    {
        if ($this->isApplicationReset($row)) {
            return false;
        }

        $userId = trim((string) ($row->user_id ?? ''));

        return $userId !== '' && ! empty($row->password);
    }

    public function hasTakenExemption(object $row): bool
    {
        if ($this->isApplicationReset($row)) {
            return false;
        }

        if ((int) ($row->application_type ?? 0) === self::APPLICATION_EXEMPTION) {
            return true;
        }

        return (int) ($row->fc_exemption_master_pk ?? 0) !== 0;
    }

    public function canStartRegistration(object $row): bool
    {
        return ! $this->hasTakenExemption($row)
            && ! $this->hasCompletedRegistration($row)
            && ! $this->hasStagedCredentials($row);
    }

    public function canApplyExemption(object $row): bool
    {
        return ! $this->hasCompletedRegistration($row)
            && ! $this->hasTakenExemption($row)
            && ! $this->hasExemptionRestrictedService($row);
    }

    /**
     * Whether this trainee's service is barred from applying for an exemption.
     *
     * Reads service_master_pk off the roster row the caller already loaded, so
     * the common case costs no query at all — only the short-code-to-id list is
     * looked up, and that is cached.
     *
     * Fails OPEN: a roster row with no service recorded is allowed to apply.
     * Wrongly blocking a trainee from an exemption they are entitled to is the
     * worse error, and an unset service is not evidence of a restricted one.
     */
    public function hasExemptionRestrictedService(object $row): bool
    {
        $servicePk = (int) ($row->service_master_pk ?? 0);

        if ($servicePk <= 0) {
            return false;
        }

        return in_array($servicePk, $this->exemptionRestrictedServiceIds(), true);
    }

    /**
     * service_master pks for the configured restricted short codes.
     *
     * Resolved from short codes rather than hardcoded pks because the ids differ
     * between environments. Matching is exact and case-insensitive after
     * trimming — NOT a prefix or LIKE match, because 'IFS' (Indian Foreign
     * Service) and 'IFS(AIS)' (Indian Forest Service) are different services and
     * only the former is restricted.
     *
     * Memoised per request and cached for a day; service_master is small,
     * rarely-changing reference data. A cache failure degrades to a direct
     * query rather than letting anyone through unchecked.
     *
     * @return list<int>
     */
    public function exemptionRestrictedServiceIds(): array
    {
        static $memo = null;

        if ($memo !== null) {
            return $memo;
        }

        $codes = array_values(array_filter(array_map(
            static fn ($code) => strtoupper(trim((string) $code)),
            (array) config('fc.exemption_restricted_services', [])
        )));

        if ($codes === []) {
            return $memo = [];
        }

        $resolve = static fn (): array => DB::table('service_master')
            ->whereIn(DB::raw('UPPER(TRIM(service_short_name))'), $codes)
            ->pluck('pk')
            ->map(static fn ($pk) => (int) $pk)
            ->all();

        try {
            return $memo = Cache::remember(
                self::RESTRICTED_SERVICES_CACHE_KEY.':'.md5(implode(',', $codes)),
                now()->addDay(),
                $resolve
            );
        } catch (\Throwable $e) {
            // Cache unavailable — resolve directly rather than fail open.
            return $memo = $resolve();
        }
    }

    public function registrationBlockedReason(object $row): ?string
    {
        if ($this->isApplicationReset($row)) {
            return null;
        }

        if ($this->hasTakenExemption($row)) {
            return 'You have already submitted an exemption application for this Foundation Course. Online registration is not available for your record. If you need assistance, please contact the Academy office.';
        }

        if ($this->hasCompletedRegistration($row)) {
            return 'You have already completed your Foundation Course registration. Please sign in using the login option on the previous page, or contact the Academy office if you need help.';
        }

        if ($this->hasStagedCredentials($row)) {
            return 'Your registration credentials have already been created. Please use the login option on the previous page to continue your registration form.';
        }

        return null;
    }

    public function exemptionBlockedReason(object $row): ?string
    {
        // Checked BEFORE the admin-reset short-circuit on purpose. A reset
        // reopens the flow by clearing prior registration/exemption state, but
        // it cannot make an officer of a restricted service eligible — that is
        // a standing policy rule, not a state flag. Placing this after the
        // reset check would leave the rule unenforced for every roster row
        // sitting at application_type = 0, which is most of them.
        if ($this->hasExemptionRestrictedService($row)) {
            return 'You are required to attend the Foundation Course, so an exemption application cannot be accepted. Please continue with online registration.';
        }

        if ($this->isApplicationReset($row)) {
            return null;
        }

        if ($this->hasCompletedRegistration($row)) {
            return 'You have already completed your Foundation Course registration. Exemption applications cannot be submitted for a completed registration. Please contact the Academy office if you require further assistance.';
        }

        if ($this->hasTakenExemption($row)) {
            return 'An exemption application has already been recorded for your mobile number and web authentication code. Only one exemption application is permitted. Please contact the Academy office if you need to update your submission.';
        }

        return null;
    }

    /**
     * Full labels (lists/exports).
     *
     * @return array<string, string>
     */
    public function applicationTypeOptions(): array
    {
        return [
            (string) self::APPLICATION_NA => 'Not Applicable (N/A)',
            (string) self::APPLICATION_REGISTRATION => 'Registration',
            (string) self::APPLICATION_EXEMPTION => 'Exemption',
        ];
    }

    /**
     * Admin edit dropdown: N/A, Registration, or Exemption.
     *
     * @return array<string, string>
     */
    public function adminApplicationTypeOptions(): array
    {
        return [
            (string) self::APPLICATION_NA => 'N/A',
            (string) self::APPLICATION_REGISTRATION => 'Registration',
            (string) self::APPLICATION_EXEMPTION => 'Exemption',
        ];
    }

    public function applicationTypeLabel(mixed $applicationType): string
    {
        return match ((int) ($applicationType ?? 0)) {
            self::APPLICATION_REGISTRATION => 'Registration',
            self::APPLICATION_EXEMPTION => 'Exemption',
            default => 'N/A',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function adminApplicationTypePayload(int $applicationType, ?int $exemptionPk = null): array
    {
        if ($applicationType === self::APPLICATION_REGISTRATION) {
            return [
                'application_type' => self::APPLICATION_REGISTRATION,
                'fc_exemption_master_pk' => 0,
                'is_registered' => 1,
            ];
        }

        if ($applicationType === self::APPLICATION_EXEMPTION) {
            return [
                'application_type' => self::APPLICATION_EXEMPTION,
                'fc_exemption_master_pk' => max(0, (int) $exemptionPk),
                'is_registered' => 0,
            ];
        }

        return [
            'application_type' => self::APPLICATION_NA,
            'fc_exemption_master_pk' => 0,
            'is_registered' => 0,
        ];
    }
}
