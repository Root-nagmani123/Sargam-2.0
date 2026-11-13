<?php

namespace Tests\Unit;

use App\Support\RoleNames;
use PHPUnit\Framework\TestCase;

/**
 * PR #319 re-review F-050 / F-058 / F-062: the two role-name keys and the Member
 * wizard's not-grantable list live in one place, shared by MemberController and the
 * 2026_09_10_000001 role-sync migration. These pin the behaviour both depend on.
 */
class RoleNamesTest extends TestCase
{
    public function test_normalize_collapses_separators_to_one_space(): void
    {
        $this->assertSame('super admin', RoleNames::normalize('  Super-Admin '));
        $this->assertSame('super admin', RoleNames::normalize('super_admin'));
        $this->assertSame('mess admin', RoleNames::normalize('Mess  -  Admin'));
        // Genuinely different roles stay different under the grant key.
        $this->assertNotSame(RoleNames::normalize('Training-Induction'), RoleNames::normalize('Training MCTP Admin'));
    }

    public function test_block_key_drops_separators_so_every_spelling_matches(): void
    {
        foreach (['Super Admin', 'Super-Admin', 'super_admin', 'SuperAdmin', ' SUPER ADMIN '] as $spelling) {
            $this->assertSame('superadmin', RoleNames::blockKey($spelling), $spelling);
        }
    }

    public function test_super_admin_is_not_grantable_from_the_member_wizard_in_any_spelling(): void
    {
        foreach (['Super Admin', 'Super-Admin', 'SuperAdmin'] as $spelling) {
            $this->assertTrue(RoleNames::isNotGrantableFromMemberWizard($spelling), $spelling);
        }

        $this->assertFalse(RoleNames::isNotGrantableFromMemberWizard('Mess Admin'));
        $this->assertFalse(RoleNames::isNotGrantableFromMemberWizard('Doctor'));
    }
}
