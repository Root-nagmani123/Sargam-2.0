<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MemberController;
use App\Models\User;
use App\Models\UserRoleMaster;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regression coverage for PR #319 review round 2, F-018/F-019/F-022.
 *
 * MemberController::syncSpatieRolesFromWizardSelection() grants/revokes real
 * Spatie roles from the Member wizard's Role Options step. Round 2 found two
 * defects in that code: any authenticated user could grant themselves roles
 * through it (F-018, no authorization check), and saving a member could
 * silently strip Spatie roles assigned elsewhere (F-019, because the
 * "preserve what this screen doesn't offer" logic degenerated once a
 * separate migration in the same PR made this screen offer almost every
 * role). These tests exercise the actual private method via reflection —
 * the same fix verified manually against an isolated clone database before
 * being committed here as a repeatable regression test.
 */
class MemberWizardRbacSyncTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTestUser(string $suffix): User
    {
        // user_credentials has no created_at/updated_at columns, and User::$fillable
        // doesn't cover these HR fields, so both are set directly rather than via
        // mass assignment / Eloquent's default timestamp handling.
        $user = new User();
        $user->timestamps = false;
        $user->first_name = 'Test';
        $user->last_name = $suffix;
        $user->user_name = 'rbac_test_' . $suffix . '_' . uniqid();
        $user->user_category = 'E';
        $user->reg_date = now();
        $user->save();

        return $user;
    }

    private function invokeSync(User $target, array $selected, array $previouslySelected = []): void
    {
        $controller = new MemberController();
        $method = new ReflectionMethod($controller, 'syncSpatieRolesFromWizardSelection');
        $method->setAccessible(true);
        $method->invoke($controller, $target->pk, $selected, $previouslySelected);
    }

    /** F-018 denied case: a non-admin actor must not be able to grant a role via the wizard. */
    public function test_non_admin_actor_cannot_grant_roles_through_the_wizard(): void
    {
        $nonAdmin = $this->makeTestUser('nonadmin');
        $target = $this->makeTestUser('target1');

        $doctorPk = UserRoleMaster::where('user_role_display_name', 'Doctor')->value('pk');
        $this->assertNotNull($doctorPk, 'Fixture assumption: a "Doctor" user_role_master row must exist.');

        Auth::login($nonAdmin);
        $this->invokeSync($target, [$doctorPk]);

        $target->refresh();
        $this->assertFalse($target->hasRole('Doctor'));
    }

    /** An admin actor can still grant a role through the wizard (F-007's intended behavior). */
    public function test_admin_actor_can_grant_roles_through_the_wizard(): void
    {
        $admin = $this->makeTestUser('admin1');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('target2');

        $doctorPk = UserRoleMaster::where('user_role_display_name', 'Doctor')->value('pk');

        Auth::login($admin);
        $this->invokeSync($target, [$doctorPk]);

        $target->refresh();
        $this->assertTrue($target->hasRole('Doctor'));
    }

    /**
     * F-019: granting a role through the wizard must not strip a role the member
     * already holds that was assigned some other way (e.g. Role & Permission > Users).
     */
    public function test_saving_the_wizard_does_not_revoke_a_role_assigned_elsewhere(): void
    {
        $admin = $this->makeTestUser('admin2');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('target3');
        $target->assignRole('FC-Sec-Audit'); // simulates a grant made via the Users screen

        $doctorPk = UserRoleMaster::where('user_role_display_name', 'Doctor')->value('pk');

        Auth::login($admin);
        $this->invokeSync($target, [$doctorPk]);

        $target->refresh();
        $this->assertTrue($target->hasRole('Doctor'));
        $this->assertTrue($target->hasRole('FC-Sec-Audit'));
    }

    /**
     * F-019: unchecking a role the wizard itself previously granted DOES revoke it,
     * while a role assigned elsewhere is still left alone.
     */
    public function test_unchecking_a_previously_wizard_granted_role_revokes_only_that_role(): void
    {
        $admin = $this->makeTestUser('admin3');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('target4');
        $target->assignRole('FC-Sec-Audit');

        $doctorPk = UserRoleMaster::where('user_role_display_name', 'Doctor')->value('pk');

        Auth::login($admin);
        $this->invokeSync($target, [$doctorPk]); // grant Doctor via the wizard
        $this->invokeSync($target, [], [$doctorPk]); // now uncheck it

        $target->refresh();
        $this->assertFalse($target->hasRole('Doctor'));
        $this->assertTrue($target->hasRole('FC-Sec-Audit'));
    }

    /**
     * F-018, end-to-end through the real route/middleware/controller stack — not just the
     * private method — reproducing the exact scenario an independent re-review demonstrated
     * against the pre-fix code: an authenticated account holding zero Spatie roles posts to
     * POST member/update for its own employee record with userrole[] set to a role's
     * user_role_master pk, and must NOT come out holding that role.
     */
    public function test_http_post_by_zero_role_actor_does_not_self_grant_a_role(): void
    {
        $employeePk = DB::table('employee_master')->insertGetId([
            'first_name' => 'Attacker',
            'last_name' => 'Self',
        ]);

        $attackerCredPk = DB::table('user_credentials')->insertGetId([
            'user_name' => 'rbac_http_attacker_' . uniqid(),
            'user_id' => $employeePk,
            'first_name' => 'Attacker',
            'last_name' => 'Self',
            'user_category' => 'E',
        ]);

        $attacker = User::find($attackerCredPk);
        $this->assertSame([], $attacker->getRoleNames()->all(), 'Fixture assumption: attacker starts with zero Spatie roles.');

        $trainingInductionPk = UserRoleMaster::where('user_role_display_name', 'Training-Induction')->value('pk');
        $this->assertNotNull($trainingInductionPk, 'Fixture assumption: a "Training-Induction" user_role_master row must exist.');

        $countryPk = DB::table('country_master')->value('pk');
        $statePk = DB::table('state_master')->value('pk');
        $departmentPk = DB::table('department_master')->where('pk', '>', 0)->value('pk');
        $designationPk = DB::table('designation_master')->value('pk');
        $groupPk = DB::table('employee_group_master')->value('pk');
        $employeeTypePk = DB::table('employee_type_master')->value('pk');
        $castePk = DB::table('caste_category_master')->where('active_inactive', 1)->value('pk');

        $payload = [
            'emp_id' => $employeePk,
            // Step 1
            'first_name' => 'Attacker',
            'last_name' => 'Self',
            'father_husband_name' => 'Father Name',
            'marital_status' => 'Unmarried',
            'gender' => 'Male',
            'caste_category' => $castePk,
            'date_of_birth' => '1990-01-01',
            // Step 2
            'type' => $employeeTypePk,
            'id' => 'ATTACK-001',
            'group' => $groupPk,
            'designation' => $designationPk,
            'userid' => 'rbac_http_attacker_uid_' . uniqid(),
            'section' => $departmentPk,
            // Step 3 — the actual attack payload
            'userrole' => [$trainingInductionPk],
            // Step 4
            'address' => 'Some Address',
            'country' => $countryPk,
            'state' => $statePk,
            'city' => 'Some City',
            'postal' => '110001',
            'permanentaddress' => 'Some Address',
            'permanentcountry' => $countryPk,
            'permanentstate' => $statePk,
            'permanentcity' => 'Some City',
            'permanentpostal' => '110001',
            'personalemail' => 'attacker_' . uniqid() . '@example.com',
            'officialemail' => 'attacker_official_' . uniqid() . '@example.com',
            'mnumber' => '9999999999',
        ];

        $response = $this->actingAs($attacker)->post(route('member.update'), $payload);

        $response->assertStatus(200);

        $attacker->refresh();
        $this->assertFalse(
            $attacker->hasRole('Training-Induction'),
            'A zero-role actor must not be able to grant themselves a Spatie role via the Member wizard (F-018).'
        );
    }

    /**
     * F-005: a wizard option whose name differs from the real role only by a separator
     * must still grant that role.
     *
     * The sync migration deduplicates user_role_master against `roles` on a NORMALISED
     * key (lowercase, trim, /[\s_-]+/ collapsed), while this method used to link the two
     * with array_intersect — an exact string comparison. So `roles` holding "Super Admin"
     * against user_role_master holding "Super-Admin" meant the migration inserted nothing
     * and the intersect matched nothing: ticking the box wrote an employee_role_mapping
     * row, reported success, and granted no permission. Measured against live data after
     * the migration had run, exactly two options were dead this way — "Super-Admin" and
     * "Mess-Admin" — one of them the highest-privilege role in the system.
     *
     * The option row is created here rather than assumed, so the test states its own
     * premise instead of depending on which spelling a given environment happens to hold.
     */
    public function test_a_separator_variant_option_still_grants_its_real_role(): void
    {
        $admin = $this->makeTestUser('sepadmin');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('septarget');

        $realRoleName = 'Mess Admin';
        $this->assertTrue(
            DB::table('roles')->where('name', $realRoleName)->exists(),
            'Fixture assumption: a "' . $realRoleName . '" Spatie role must exist.'
        );

        // The same role, spelled with a hyphen — the shape the live user_role_master holds.
        $variantPk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Mess-Admin',
            'user_role_display_name' => 'Mess-Admin',
            'active_inactive'        => 1,
        ]);

        Auth::login($admin);
        $this->invokeSync($target, [$variantPk]);

        $target->refresh();
        $this->assertTrue(
            $target->hasRole($realRoleName),
            'Ticking "Mess-Admin" must grant the real "Mess Admin" role — an option that grants '
            . 'nothing is indistinguishable from a working grant (F-005).'
        );
    }

    /**
     * The other half of F-005: normalising must not start matching roles that are
     * genuinely different. "Training-Induction" and "Training MCTP Admin" normalise to
     * different keys and must stay distinct, so the fix cannot be a loose substring match.
     */
    public function test_normalisation_does_not_conflate_genuinely_different_roles(): void
    {
        $admin = $this->makeTestUser('sepadmin2');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('septarget2');

        $inductionPk = UserRoleMaster::where('user_role_display_name', 'Training-Induction')->value('pk');
        $this->assertNotNull($inductionPk, 'Fixture assumption: a "Training-Induction" option must exist.');

        Auth::login($admin);
        $this->invokeSync($target, [$inductionPk]);

        $target->refresh();
        $this->assertTrue($target->hasRole('Training-Induction'), 'The ticked role must be granted.');
        $this->assertFalse(
            $target->hasRole('Training MCTP Admin'),
            'Normalising separators must not conflate two distinct roles.'
        );
    }

    /**
     * R-002: the Member wizard must not grant Super Admin, whatever the checkbox says.
     *
     * Correcting the F-005 name mismatch widened what this screen can grant from 19 roles
     * to 21, and one of the two it added was Super Admin — 171 permissions, the highest
     * privilege in the application. The old bug had accidentally prevented that. The
     * Engineering lead's decision (2026-09-22) is that Super Admin is granted only from
     * Role & Permission > Users, so the wizard refuses it explicitly rather than relying
     * on a name mismatch to do it by accident.
     *
     * Note the actor here IS a Super Admin — so this is not testing the F-018 gate. It is
     * testing that even a fully privileged actor cannot mint one from THIS screen.
     */
    public function test_the_wizard_cannot_grant_super_admin_even_for_an_admin_actor(): void
    {
        $admin = $this->makeTestUser('sa_admin');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('sa_target');

        // The option as the live data spells it: hyphenated in user_role_master, spaced
        // in `roles`. Created here so the test states its own premise.
        $optionPk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Super-Admin',
            'user_role_display_name' => 'Super-Admin',
            'active_inactive'        => 1,
        ]);

        Auth::login($admin);
        $this->invokeSync($target, [$optionPk]);

        $target->refresh();
        $this->assertFalse(
            $target->hasRole('Super Admin'),
            'The Member wizard must never grant Super Admin — it is granted from Role & Permission > Users.'
        );
        $this->assertSame([], $target->getRoleNames()->all(), 'No role at all should have been granted.');
    }

    /**
     * The other side of R-002: blocking Super Admin must not block anything else. A
     * deny-list that over-matches would silently break the 20 roles this screen is
     * supposed to grant, which is the same silent-failure shape F-005 was about.
     */
    public function test_blocking_super_admin_does_not_block_other_roles(): void
    {
        $admin = $this->makeTestUser('sa_admin2');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('sa_target2');

        $doctorPk = UserRoleMaster::where('user_role_display_name', 'Doctor')->value('pk');
        $this->assertNotNull($doctorPk, 'Fixture assumption: a "Doctor" option must exist.');

        Auth::login($admin);
        $this->invokeSync($target, [$doctorPk]);

        $target->refresh();
        $this->assertTrue($target->hasRole('Doctor'), 'An ordinary role must still be grantable.');
    }

    /**
     * R-002 follow-through: blocking Super Admin must not re-create F-005.
     *
     * Refusing to grant it is correct, but refusing SILENTLY would put that one option
     * back into exactly the state F-005 was raised about — tick the box, see "Member
     * updated successfully!", get no permission, with nothing to tell that apart from a
     * working grant. The refusal has to announce itself.
     */
    public function test_ticking_a_blocked_role_tells_the_administrator_why_nothing_was_granted(): void
    {
        $admin = $this->makeTestUser('sa_admin3');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('sa_target3');

        $optionPk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Super-Admin',
            'user_role_display_name' => 'Super-Admin',
            'active_inactive'        => 1,
        ]);

        Auth::login($admin);

        $controller = new MemberController();
        $method = new ReflectionMethod($controller, 'syncSpatieRolesFromWizardSelection');
        $method->setAccessible(true);
        $warning = $method->invoke($controller, $target->pk, [$optionPk], []);

        $this->assertNotNull($warning, 'A refused role must return a reason, not null.');
        $this->assertStringContainsString('Super-Admin', $warning, 'The message must name the option that was clicked.');
        $this->assertStringContainsString('Role & Permission', $warning, 'The message must say where the role IS granted.');

        $target->refresh();
        $this->assertFalse($target->hasRole('Super Admin'), 'And it still must not be granted.');
    }

    /**
     * The block is symmetric — the wizard cannot revoke Super Admin either — and that
     * half was silent until round 4 found it. An administrator who UNTICKS the option on
     * a member who really holds the role saw "Member updated successfully!" while the
     * role stayed. Refusing is right; refusing quietly is the same defect as F-005.
     *
     * Note this case could not even be reached at first: with the blocked role filtered
     * out of both the old and the new set, both were empty and the method short-circuited
     * before it ever looked at what the member holds.
     */
    public function test_unticking_a_blocked_role_the_member_holds_says_it_was_not_removed(): void
    {
        $admin = $this->makeTestUser('sa_admin4');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('sa_target4');

        // The member already holds it — e.g. assigned from Role & Permission > Users.
        $target->assignRole('Super Admin');

        $optionPk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Super-Admin',
            'user_role_display_name' => 'Super-Admin',
            'active_inactive'        => 1,
        ]);

        Auth::login($admin);

        $controller = new MemberController();
        $method = new ReflectionMethod($controller, 'syncSpatieRolesFromWizardSelection');
        $method->setAccessible(true);

        // Previously ticked, now unticked.
        $warning = $method->invoke($controller, $target->pk, [], [$optionPk]);

        $target->refresh();
        $this->assertTrue(
            $target->hasRole('Super Admin'),
            'The wizard must not revoke Super Admin — that is done from Role & Permission > Users.'
        );
        $this->assertNotNull($warning, 'And it must say so rather than reporting plain success.');
        $this->assertStringContainsString('was NOT removed', $warning);
    }

    /**
     * The noise guard on that warning: a member who does NOT hold the blocked role must
     * not be told anything when the option is unticked, or every save of every member
     * would carry a warning about a role they never had.
     */
    public function test_unticking_a_blocked_role_the_member_does_not_hold_is_silent(): void
    {
        $admin = $this->makeTestUser('sa_admin5');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('sa_target5');   // holds nothing

        $optionPk = DB::table('user_role_master')->insertGetId([
            'user_role_name'         => 'Super-Admin',
            'user_role_display_name' => 'Super-Admin',
            'active_inactive'        => 1,
        ]);

        Auth::login($admin);

        $controller = new MemberController();
        $method = new ReflectionMethod($controller, 'syncSpatieRolesFromWizardSelection');
        $method->setAccessible(true);
        $warning = $method->invoke($controller, $target->pk, [], [$optionPk]);

        $this->assertNull($warning, 'Nothing was lost, so nothing should be reported.');
    }
}
