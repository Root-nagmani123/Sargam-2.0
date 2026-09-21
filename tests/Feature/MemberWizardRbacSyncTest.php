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
}
