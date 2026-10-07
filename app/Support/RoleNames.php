<?php

namespace App\Support;

/**
 * Role-name matching rules shared by the Member wizard (MemberController) and the
 * 2026_09_10_000001_sync_user_role_master_with_roles migration.
 *
 * They used to be hand-copied into both and kept in step only by a comment (PR #319
 * re-review F-058); if one changed and the other did not, a ticked checkbox would
 * silently stop matching its real `roles` row again — the F-005 defect.
 */
final class RoleNames
{
    /**
     * Roles the Member wizard never grants or revokes, whatever the checkboxes say. They
     * are assigned from Role & Permission > Users, which carries its own Super Admin gate.
     *
     * A development-time choice, NOT a recorded decision of the Engineering lead (PR #319
     * review F-040). To let the wizard grant one of these, remove it from this list.
     */
    public const NOT_GRANTABLE_FROM_MEMBER_WIZARD = ['Super Admin'];

    /**
     * Grant-lookup key: lowercase, trimmed, runs of space/hyphen/underscore collapsed to
     * one space — "Super-Admin" and "super_admin" both become "super admin".
     *
     * Deliberately strict enough to keep genuinely different roles apart; use it to map a
     * user_role_master option onto the `roles` row it names.
     */
    public static function normalize(string $name): string
    {
        return preg_replace('/[\s_-]+/', ' ', mb_strtolower(trim($name)));
    }

    /**
     * Deny-list key: lowercase with every separator REMOVED, so "Super Admin",
     * "Super-Admin" and "SuperAdmin" are one key (F-039: the space-collapsing key let the
     * concatenated spelling slip the block). Right for a deny-list, which may over-match
     * safely; wrong for the grant lookup, which may not.
     */
    public static function blockKey(string $name): string
    {
        return preg_replace('/[\s_-]+/', '', mb_strtolower(trim($name)));
    }

    public static function isNotGrantableFromMemberWizard(string $name): bool
    {
        static $blocked = null;

        $blocked ??= array_flip(array_map([self::class, 'blockKey'], self::NOT_GRANTABLE_FROM_MEMBER_WIZARD));

        return isset($blocked[self::blockKey($name)]);
    }
}
