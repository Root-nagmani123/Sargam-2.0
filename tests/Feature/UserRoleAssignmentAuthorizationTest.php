<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression coverage for PR #319 review round 4, F-028/F-029.
 *
 * UserController::assignRoleSave() (Role & Permission > Users) had no
 * authorization check at all — any authenticated account could POST its own
 * user_id with roles=[<Super Admin's id>] and grant itself the highest role
 * in the application. This was discovered because MemberController's own
 * F-018 fix cited this method as its authorization precedent, and it turned
 * out not to have one. Both are now gated the same way.
 */
class UserRoleAssignmentAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * PR #319 review (F-002): with no database reachable, DatabaseTransactions'
     * beginDatabaseTransaction() (invoked from parent::setUp()) threw a raw PDOException
     * and every test in this file errored rather than skipping — indistinguishable from a
     * real defect on a run where the environment, not the code, is the reason nothing ran.
     */
    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not reachable: ' . $e->getMessage());
        }
    }

    private function makeTestUser(string $suffix): User
    {
        $user = new User();
        $user->timestamps = false;
        $user->first_name = 'Test';
        $user->last_name = $suffix;
        $user->user_name = 'rbac_uc_test_' . $suffix . '_' . uniqid();
        $user->user_category = 'E';
        $user->reg_date = now();
        $user->save();

        return $user;
    }

    /** F-028 denied case: a non-admin actor cannot grant itself (or anyone) a role via this screen. */
    public function test_non_admin_actor_cannot_use_assign_role_save(): void
    {
        $nonAdmin = $this->makeTestUser('nonadmin');
        $superAdminRoleId = Role::where('name', 'Super Admin')->value('id');
        $this->assertNotNull($superAdminRoleId, 'Fixture assumption: a "Super Admin" role must exist.');

        $response = $this->actingAs($nonAdmin)->post(route('admin.users.assignRoleSave'), [
            'user_id' => $nonAdmin->pk,
            'roles' => [$superAdminRoleId],
        ]);

        $response->assertForbidden();

        $nonAdmin->refresh();
        $this->assertFalse($nonAdmin->hasRole('Super Admin'));
    }

    /** An admin actor can still use the screen for its intended purpose. */
    public function test_admin_actor_can_use_assign_role_save(): void
    {
        $admin = $this->makeTestUser('admin');
        $admin->assignRole('Super Admin');
        $target = $this->makeTestUser('target');
        $doctorRoleId = Role::where('name', 'Doctor')->value('id');

        $response = $this->actingAs($admin)->post(route('admin.users.assignRoleSave'), [
            'user_id' => $target->pk,
            'roles' => [$doctorRoleId],
        ]);

        $response->assertRedirect();

        $target->refresh();
        $this->assertTrue($target->hasRole('Doctor'));
    }

    /**
     * F-029: the admin gate must not widen its authority if a role literally named
     * "Admin" is ever created — only "Super Admin" (and its "SuperAdmin" alias,
     * handled inside hasRole() itself) should qualify.
     */
    public function test_a_role_literally_named_admin_does_not_qualify_for_the_gate(): void
    {
        $plainAdminRoleName = 'Admin';
        if (!Role::where('name', $plainAdminRoleName)->exists()) {
            Role::create(['name' => $plainAdminRoleName, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $actor = $this->makeTestUser('plainadmin');
        $actor->assignRole($plainAdminRoleName);

        $superAdminRoleId = Role::where('name', 'Super Admin')->value('id');

        $response = $this->actingAs($actor)->post(route('admin.users.assignRoleSave'), [
            'user_id' => $actor->pk,
            'roles' => [$superAdminRoleId],
        ]);

        $response->assertForbidden();

        $actor->refresh();
        $this->assertFalse($actor->hasRole('Super Admin'));

        // Cleanup: this test is the only place a "Admin" role is ever created.
        DB::table('model_has_roles')->where('model_id', $actor->pk)->delete();
        Role::where('name', $plainAdminRoleName)->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
