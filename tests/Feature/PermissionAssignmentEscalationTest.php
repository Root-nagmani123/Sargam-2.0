<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Cover for the privilege AMPLIFICATION path left open beside review finding F-023.
 *
 * F-023 closed the route that writes a ROLE to a USER (assignRoleSave): a caller who
 * is not Super Admin may no longer change anyone's Super Admin membership. The route
 * that writes a PERMISSION to a ROLE - assign.roles.permissions - kept the shape it
 * always had. RoleController::assignPermission() constrains WHICH names may be written
 * (a name must already exist or be defined by a `menus` row) but never asked who may
 * write them.
 *
 * ON THIS BRANCH the route is also behind EnsureRoleAdmin (Super Admin only, from
 * RoleController's constructor), so a `roles` holder is refused before the controller
 * guard runs; there is no `menu.permission` alias here (PR #309 review F-019). The
 * paragraph below describes PR #311, where the file originated and the route is gated
 * on the `roles` permission instead.
 *
 * PR #311's condition-1 migration grants `roles` to the Training-Induction role, so the
 * set of accounts passing that gate went from 2 to 12. Confirmed by executed probe
 * against the review database before this guard existed: an account holding only
 * Training-Induction was refused `/sidebar/menus` with 403, POSTed once to grant
 * `menus` to its own role, and got 200 on the same URL. Every `menu.permission` gate in
 * the application is reachable that way, one request at a time.
 *
 * The actor in these tests is deliberately a `roles` HOLDER, never a role-less account.
 * A role-less account is refused by the middleware whether or not the controller guard
 * exists, so a test written that way passes against the unfixed code and proves
 * nothing. These fail against it.
 *
 * The two control tests matter as much as the refusals: the guard has to stop
 * amplification without breaking ordinary permission administration.
 */
class PermissionAssignmentEscalationTest extends TestCase
{
    use DatabaseTransactions;

    private const SUPER_ADMIN = 'Super Admin';

    /** A real permission that gates a real screen and that the actor does not hold. */
    private const WITHHELD = 'menus';

    /** Spatie caches the permission map; without this a just-granted permission is invisible. */
    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
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

    private function permission(string $name): Permission
    {
        $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();

        if (! $permission) {
            $this->markTestSkipped("The `{$name}` permission row does not exist in this database.");
        }

        return $permission;
    }

    /** An account that passes menu.permission:roles, and nothing more. */
    private function rolesHolder(): User
    {
        $user = $this->accountAt(0);
        $user->givePermissionTo($this->permission('roles'));
        $this->forgetPermissionCache();

        return $user->fresh();
    }

    /** A role to write to that is not Super Admin, with a known starting state. */
    private function targetRole(): Role
    {
        $role = Role::where('name', 'Officer Trainee')->where('guard_name', 'web')->first()
            ?: Role::create(['name' => 'Officer Trainee', 'guard_name' => 'web']);

        $role->revokePermissionTo($this->permission(self::WITHHELD));
        $this->forgetPermissionCache();

        return $role->fresh();
    }

    private function superAdminRole(): Role
    {
        $role = Role::where('name', self::SUPER_ADMIN)->where('guard_name', 'web')->first();

        if (! $role) {
            $this->markTestSkipped('The Super Admin role does not exist in this database.');
        }

        return $role;
    }

    private function roleHas(Role $role, string $permission): bool
    {
        $this->forgetPermissionCache();

        return $role->fresh()->permissions->pluck('name')->contains($permission);
    }

    private function assignAs(User $actor, Role $role, string $permission, int $status)
    {
        return $this->actingAs($actor)->post(
            route('assign.roles.permissions', ['id' => $role->id]),
            ['permission' => $permission, 'status' => $status]
        );
    }

    /**
     * The amplification itself. This is the test that fails against the unguarded
     * controller: the actor passes the route gate, so only the controller can refuse.
     */
    public function test_a_roles_holder_cannot_grant_a_permission_it_does_not_hold(): void
    {
        $actor = $this->rolesHolder();
        $role = $this->targetRole();

        $this->assertTrue(
            $actor->getAllPermissions()->pluck('name')->contains('roles'),
            'premise: the actor must pass menu.permission:roles, or this tests the middleware instead'
        );
        $this->assertFalse(
            $actor->getAllPermissions()->pluck('name')->contains(self::WITHHELD),
            'premise: the actor must NOT already hold the permission it is trying to hand out'
        );
        $this->assertFalse($this->roleHas($role, self::WITHHELD), 'premise: the target role must not already hold it');

        $this->assignAs($actor, $role, self::WITHHELD, 1)->assertForbidden();

        $this->assertFalse(
            $this->roleHas($role, self::WITHHELD),
            'a roles holder granted a permission it does not hold - every menu.permission gate is reachable this way'
        );
    }

    /** The exact shape the probe used: grant it to the role the actor is in. */
    public function test_a_roles_holder_cannot_grant_itself_a_permission_it_does_not_hold(): void
    {
        $actor = $this->rolesHolder();
        $role = $this->targetRole();

        $actor->assignRole($role);
        $this->forgetPermissionCache();
        $actor = $actor->fresh();

        $this->assignAs($actor, $role, self::WITHHELD, 1)->assertForbidden();

        $this->assertFalse(
            $actor->fresh()->getAllPermissions()->pluck('name')->contains(self::WITHHELD),
            'the account granted itself a permission it did not hold'
        );
    }

    /**
     * The other direction. A permission the caller does not hold is not theirs to strip
     * from another role either - that is sabotage rather than escalation, but it is the
     * same missing question: may this caller hand this out.
     */
    public function test_a_roles_holder_cannot_revoke_a_permission_it_does_not_hold(): void
    {
        $actor = $this->rolesHolder();
        $role = $this->targetRole();

        $role->givePermissionTo($this->permission(self::WITHHELD));
        $this->forgetPermissionCache();
        $this->assertTrue($this->roleHas($role, self::WITHHELD), 'premise: the target role holds it');

        $this->assignAs($actor, $role, self::WITHHELD, 0)->assertForbidden();

        $this->assertTrue(
            $this->roleHas($role, self::WITHHELD),
            'a roles holder revoked a permission it does not hold'
        );
    }

    /** The Super Admin role is not editable by anyone who is not Super Admin. */
    public function test_a_roles_holder_cannot_modify_the_super_admin_role(): void
    {
        $actor = $this->rolesHolder();

        // The permission named here is one the actor DOES hold, so the only rule that
        // can refuse this request is the Super-Admin-role rule.
        $this->assignAs($actor, $this->superAdminRole(), 'roles', 1)->assertForbidden();
    }

    /**
     * On PR #311 this was a CONTROL asserting a `roles` holder may still grant a
     * permission it holds. On this branch permission administration is Super Admin
     * only - EnsureRoleAdmin, applied in RoleController's constructor - so the same
     * request is refused at the gate, and nothing is written. If the two PRs merge,
     * which rule wins is the Engineering lead's decision, not this test's; the Super
     * Admin control below is what shows administration still works here.
     */
    public function test_a_roles_holder_is_refused_at_the_gate_on_this_branch(): void
    {
        $actor = $this->rolesHolder();
        $role = $this->targetRole();

        $role->revokePermissionTo($this->permission('roles'));
        $this->forgetPermissionCache();

        $this->assignAs($actor, $role, 'roles', 1)->assertForbidden();

        $this->assertFalse($this->roleHas($role, 'roles'), 'refused at the gate, and the grant was written anyway');
    }

    /** CONTROL - Super Admin is exempt from both rules, as it is everywhere else here. */
    public function test_a_super_admin_may_still_assign_any_permission(): void
    {
        $actor = $this->accountAt(0);
        $actor->assignRole($this->superAdminRole());
        $this->forgetPermissionCache();

        $role = $this->targetRole();

        $this->assignAs($actor->fresh(), $role, self::WITHHELD, 1)->assertOk();

        $this->assertTrue(
            $this->roleHas($role, self::WITHHELD),
            'Super Admin must retain full permission administration'
        );
    }
}
