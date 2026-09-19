<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MemberController;
use App\Models\User;
use App\Models\UserRoleMaster;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
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
}
