<?php

namespace App\Support;

/**
 * Role names for the Security module's Approval II / Approval III stages.
 *
 * Since the Spatie RBAC switch (PR #133, PR #183) the session holds Spatie `roles.name`
 * values, which are "Security Approver II" / "Security Approver III". The pre-RBAC
 * user_role_master names ("Security Card" / "Admin Security") are still accepted so a
 * deployment that keeps either set keeps working.
 *
 * Approval II  = Level 1 for Family ID Card / Vehicle Pass, Approval II for Employee ID Card.
 * Approval III = Level 2 (final) for Family ID Card / Vehicle Pass, Approval III for Employee ID Card.
 */
final class SecurityApproverRoles
{
    public const APPROVER_II = ['Security Approver II', 'Security Card'];

    public const APPROVER_III = ['Security Approver III', 'Admin Security'];

    public static function isApproverII(): bool
    {
        return self::hasAny(self::APPROVER_II);
    }

    public static function isApproverIII(): bool
    {
        return self::hasAny(self::APPROVER_III);
    }

    public static function isAnyApprover(): bool
    {
        return self::isApproverII() || self::isApproverIII();
    }

    /**
     * @param  list<string>  $names
     */
    private static function hasAny(array $names): bool
    {
        foreach ($names as $name) {
            if (hasRole($name)) {
                return true;
            }
        }

        return false;
    }
}
