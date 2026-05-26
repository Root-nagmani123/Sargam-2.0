<?php

namespace App\Services\FC;

/**
 * Mutual exclusion and one-time rules for fc_registration_master
 * public registration vs exemption flows.
 *
 * application_type = 0 (N/A) is an admin reset: both registration and exemption are allowed again.
 */
class FcRosterApplicationGuardService
{
    public const APPLICATION_NA = 0;

    public const APPLICATION_REGISTRATION = 1;

    public const APPLICATION_EXEMPTION = 2;

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
            && ! $this->hasTakenExemption($row);
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
