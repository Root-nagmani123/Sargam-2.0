<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cover for the privilege-escalation path opened by this PR's own condition-1 fix.
 *
 * PR #311 gated every admin.users.* route on `menu.permission:users`, which closed
 * F-015: before it, any of 15,108 authenticated accounts could POST
 * admin.users.assign-role-save with its own pk and the Super Admin role id and become
 * Super Admin. Condition 1 (F-017) was then closed by GRANTING `users` to the
 * Training-Induction role, which hands those 10 accounts back through that same gate.
 *
 * assignRoleSave() validates only that the posted role ids EXIST. It never asked what
 * the caller was allowed to hand out, so a `users` holder could still self-assign
 * Super Admin - and Super Admin is not merely one more permission here:
 * EnsureMenuPermission admits isSidebarPrivilegedUser() BEFORE it reads any
 * permission, so holding that role bypasses every gate in the application.
 *
 * Confirmed by executed probe before the guard existed: a Training-Induction account
 * granted `users` posted its own pk with roles[]=1 and came back holding
 * ["Super Admin"].
 *
 * The actor in these tests is deliberately a `users` HOLDER rather than a role-less
 * account. A role-less account is refused by the middleware whether or not the
 * controller guard exists, so a test written that way passes against the unfixed code
 * and proves nothing. These fail against it.
 */
class RoleAssignmentEscalationTest extends TestCase
{
    use DatabaseTransactions;

    private const SUPER_ADMIN = 'Super Admin';

    /** Spatie caches the permission map; without this a just-granted permission is invisible. */
    private function forgetPermissionCache(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * An existing account stripped of every role and permission.
     *
     * Not a freshly created row: user_credentials has no updated_at column, so Eloquent
     * inserts fail on it. DatabaseTransactions rolls every pivot change back.
     */
    private function accountAt(int $offset): User
    {
        $user = User::query()->orderBy('pk')->skip($offset)->first();

        if (! $user) {
            $this->markTestSkipped('Not enough user_credentials rows to run this test.');
        }

        $user->syncRoles([]);
        $user->syncPermissions([]);
        $this->forgetPermissionCache();

        return $user->fresh();
    }

    /** Give an account the `users` permission, so it passes menu.permission:users. */
    private function grantUsers(User $user): User
    {
        $permission = Permission::where('name', 'users')->where('guard_name', 'web')->first();

        if (! $permission) {
            $this->markTestSkipped('The `users` permission row does not exist in this database.');
        }

        $user->givePermissionTo($permission);
        $this->forgetPermissionCache();

        return $user->fresh();
    }

    private function superAdminRole(): Role
    {
        $role = Role::where('name', self::SUPER_ADMIN)->where('guard_name', 'web')->first();

        if (! $role) {
            $this->markTestSkipped('The Super Admin role does not exist in this database.');
        }

        return $role;
    }

    private function ordinaryRole(): Role
    {
        return Role::where('name', 'Officer Trainee')->where('guard_name', 'web')->first()
            ?: Role::create(['name' => 'Officer Trainee', 'guard_name' => 'web']);
    }

    private function holdsSuperAdmin(User $user): bool
    {
        return DB::table('model_has_roles')
            ->where('model_id', $user->getKey())
            ->where('role_id', $this->superAdminRole()->id)
            ->exists();
    }

    /**
     * The escalation itself. This is the test that fails against the unguarded
     * controller: the actor passes the route gate, so only the controller can refuse.
     */
    public function test_a_users_permission_holder_cannot_grant_itself_super_admin(): void
    {
        $actor = $this->grantUsers($this->accountAt(0));

        $this->assertFalse($this->holdsSuperAdmin($actor),
            'premise: the actor must not already be Super Admin');
        $this->assertTrue($actor->getAllPermissions()->pluck('name')->contains('users'),
            'premise: the actor must pass menu.permission:users, or this tests the middleware instead');

        $this->actingAs($actor)
            ->post(route('admin.users.assignRoleSave'), [
                'user_id' => $actor->getKey(),
                'roles'   => [$this->superAdminRole()->id],
            ])
            ->assertForbidden();

        $this->forgetPermissionCache();

        $this->assertFalse($this->holdsSuperAdmin($actor->fresh()),
            'the account escalated itself to Super Admin, which bypasses every menu.permission gate');
    }

    /** The same guard must stop it handing Super Admin to somebody else. */
    public function test_a_users_permission_holder_cannot_grant_super_admin_to_another_account(): void
    {
        $actor  = $this->grantUsers($this->accountAt(0));
        $target = $this->accountAt(1);

        $this->actingAs($actor)
            ->post(route('admin.users.assignRoleSave'), [
                'user_id' => $target->getKey(),
                'roles'   => [$this->superAdminRole()->id],
            ])
            ->assertForbidden();

        $this->forgetPermissionCache();

        $this->assertFalse($this->holdsSuperAdmin($target->fresh()),
            'a users holder promoted another account to Super Admin');
    }

    /**
     * The other direction. syncRoles() REPLACES the role set, so omitting Super Admin
     * from the payload strips it - which would let a `users` holder demote every Super
     * Admin and strand the only accounts able to undo that.
     */
    public function test_a_users_permission_holder_cannot_revoke_super_admin(): void
    {
        $actor  = $this->grantUsers($this->accountAt(0));
        $target = $this->accountAt(1);

        $target->assignRole($this->superAdminRole());
        $this->forgetPermissionCache();
        $this->assertTrue($this->holdsSuperAdmin($target->fresh()), 'premise: the target is Super Admin');

        $this->actingAs($actor)
            ->post(route('admin.users.assignRoleSave'), [
                'user_id' => $target->getKey(),
                'roles'   => [$this->ordinaryRole()->id],
            ])
            ->assertForbidden();

        $this->forgetPermissionCache();

        $this->assertTrue($this->holdsSuperAdmin($target->fresh()),
            'a users holder stripped the Super Admin role from an account');
    }

    /**
     * Control: the guard is narrow. Ordinary role administration by a `users` holder
     * still works, so this is not a lockout of the capability the condition-1 grant
     * deliberately restored.
     */
    public function test_a_users_permission_holder_may_still_assign_an_ordinary_role(): void
    {
        $actor  = $this->grantUsers($this->accountAt(0));
        $target = $this->accountAt(1);
        $role   = $this->ordinaryRole();

        $response = $this->actingAs($actor)
            ->post(route('admin.users.assignRoleSave'), [
                'user_id' => $target->getKey(),
                'roles'   => [$role->id],
            ]);

        $this->assertNotSame(403, $response->getStatusCode(),
            'the guard is too broad: it blocked an ordinary role assignment');

        $this->forgetPermissionCache();

        $this->assertTrue($target->fresh()->hasRole($role->name),
            'the ordinary role assignment did not take effect');
    }

    /** Control: a real Super Admin is not locked out of the capability at all. */
    public function test_a_super_admin_may_still_grant_super_admin(): void
    {
        $actor  = $this->accountAt(0);
        $target = $this->accountAt(1);

        $actor->assignRole($this->superAdminRole());
        $this->forgetPermissionCache();
        $actor = $actor->fresh();

        $response = $this->actingAs($actor)
            ->post(route('admin.users.assignRoleSave'), [
                'user_id' => $target->getKey(),
                'roles'   => [$this->superAdminRole()->id],
            ]);

        $this->assertNotSame(403, $response->getStatusCode(),
            'a Super Admin was refused the Super Admin grant');

        $this->forgetPermissionCache();

        $this->assertTrue($this->holdsSuperAdmin($target->fresh()),
            'a Super Admin could not grant the Super Admin role');
    }
}
