<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * PR #309 review F-017: who may write a ROLE onto a USER.
 *
 * `POST admin/users/assign-role-save` carried `auth` and nothing else, and
 * UserController::assignRoleSave() checked nothing: it synced whatever role ids it
 * was posted onto whatever user id it was posted. Any signed-in account could post
 * its own pk with the Super Admin role id and become Super Admin, which then admits
 * it through every other gate in the application.
 *
 * Two layers now stand in the way, and each has a test that only it can pass:
 *
 *   - the route and the controller require the `users` menu permission, so an
 *     ordinary account is refused outright; and
 *   - a `users` holder who is not Super Admin still may not CHANGE anyone's Super
 *     Admin membership. That actor passes the first layer, so only the second can
 *     refuse it - an ordinary account would be refused either way and would prove
 *     nothing about the guard.
 *
 * Every refusal is followed by a read of model_has_roles: a 403 that still wrote is
 * the same escalation with a tidier response. Runs inside a transaction that is
 * rolled back, so nothing it grants survives.
 */
class RoleAssignmentAuthorisationTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the role assignment tests need the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        parent::tearDown();
    }

    /** Two distinct accounts that hold no Super Admin role in the role tables. */
    private function accounts(): array
    {
        $superAdminIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'Super Admin')
            ->pluck('model_has_roles.model_id');

        $users = User::query()->whereNotIn('pk', $superAdminIds)->orderBy('pk')->limit(2)->get();

        if ($users->count() < 2) {
            $this->markTestSkipped('need two non-Super-Admin accounts');
        }

        return [$users[0], $users[1]];
    }

    private function superAdminRole(): Role
    {
        $role = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();

        if (! $role) {
            $this->markTestSkipped('no Super Admin role in this database');
        }

        return $role;
    }

    private function ordinaryRole(): Role
    {
        $role = Role::where('name', '!=', 'Super Admin')->where('guard_name', 'web')->orderBy('id')->first();

        if (! $role) {
            $this->markTestSkipped('no ordinary role in this database');
        }

        return $role;
    }

    private function roleNames(User $user): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->pk)
            ->pluck('roles.name')
            ->sort()->values()->all();
    }

    private function actAs(User $user, bool $withUsersPermission): void
    {
        if ($withUsersPermission) {
            $permission = Permission::where('name', 'users')->where('guard_name', 'web')->first();

            if (! $permission) {
                $this->markTestSkipped('the `users` permission row does not exist in this database');
            }

            $user->givePermissionTo($permission);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $user = $user->fresh();
        }

        $this->actingAs($user);
        // hasRole() answers from the session list written at login first.
        session(['user_roles' => ['FC-Sec-Audit']]);

        $this->assertFalse(isSidebarPrivilegedUser(), 'premise: the actor must not be Super Admin');
        $this->assertSame($withUsersPermission, hasMenuPermission('users'), 'premise: `users` held exactly when intended');
    }

    public function test_an_ordinary_account_cannot_make_itself_super_admin(): void
    {
        [$actor] = $this->accounts();
        $superAdmin = $this->superAdminRole();
        $before = $this->roleNames($actor);

        $this->actAs($actor, false);

        $this->post(route('admin.users.assignRoleSave'), [
            'user_id' => $actor->pk,
            'roles' => [$superAdmin->id],
        ])->assertForbidden();

        $this->assertSame($before, $this->roleNames($actor), 'refused, and the roles were written anyway');
    }

    public function test_an_ordinary_account_cannot_read_the_role_list_or_open_the_screen(): void
    {
        [$actor, $target] = $this->accounts();
        $this->actAs($actor, false);

        $this->get(route('admin.users.getRoles'))->assertForbidden();
        $this->get(route('admin.users.assignRole', encrypt($target->pk)))->assertForbidden();
    }

    public function test_a_users_holder_cannot_grant_itself_super_admin(): void
    {
        [$actor] = $this->accounts();
        $superAdmin = $this->superAdminRole();
        $before = $this->roleNames($actor);

        $this->actAs($actor, true);

        $this->post(route('admin.users.assignRoleSave'), [
            'user_id' => $actor->pk,
            'roles' => [$superAdmin->id],
        ])->assertForbidden();

        $this->assertSame($before, $this->roleNames($actor), 'refused, and the roles were written anyway');
    }

    public function test_a_users_holder_cannot_strip_super_admin_from_someone(): void
    {
        [$actor, $target] = $this->accounts();
        $superAdmin = $this->superAdminRole();

        $target->assignRole($superAdmin);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actAs($actor, true);

        // syncRoles() replaces the set, so a payload without Super Admin removes it.
        $this->post(route('admin.users.assignRoleSave'), [
            'user_id' => $target->pk,
            'roles' => [$this->ordinaryRole()->id],
        ])->assertForbidden();

        $this->assertContains('Super Admin', $this->roleNames($target));
    }

    /**
     * PR #309 review F-018: role CRUD is mounted twice, and the `admin/roles` family
     * carried `auth` alone. RoleController's constructor gates store/update/destroy
     * on every mount - this is the executed refusal against the admin-prefixed one.
     */
    public function test_an_ordinary_account_cannot_create_a_role_through_the_admin_mount(): void
    {
        [$actor] = $this->accounts();
        $this->actAs($actor, true);

        $name = 'zz_probe_role_f018';
        $this->assertFalse(Role::where('name', $name)->exists(), 'premise: probe role must not exist');

        $this->post(route('admin.roles.store'), ['name' => $name])->assertForbidden();

        $this->assertFalse(Role::where('name', $name)->exists(), 'refused, and the role was created anyway');
    }

    /** Control: ordinary role administration still works for a `users` holder. */
    public function test_a_users_holder_can_still_assign_an_ordinary_role(): void
    {
        [$actor, $target] = $this->accounts();
        $role = $this->ordinaryRole();

        $this->actAs($actor, true);

        $this->post(route('admin.users.assignRoleSave'), [
            'user_id' => $target->pk,
            'roles' => [$role->id],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame([$role->name], $this->roleNames($target));
    }
}
