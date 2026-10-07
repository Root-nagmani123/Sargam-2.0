<?php

namespace Tests\Feature;

use App\Http\Controllers\RoleController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Executes the privilege-amplification guard in RoleController::assignPermission()
 * (PR #309 review F-069).
 *
 * No route reaches that guard on this branch: assign.roles.permissions sits behind
 * EnsureRoleAdmin, which admits Super Admin only, and the guard runs only for callers
 * who are NOT Super Admin. So an HTTP test is refused at the gate and never touches it.
 * These tests call the controller method directly, without the route gate, to prove the
 * guard still works if the gate is ever widened - the reason it was kept.
 *
 * Every case runs inside a transaction that is rolled back in tearDown().
 */
class PermissionAmplificationGuardDirectTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the guard tests need the application database');
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

    /**
     * A non-Super-Admin actor holding exactly one role, $held, plus a second ordinary
     * role to act on, and one permission the actor holds and one it does not.
     *
     * @return array{0: User, 1: Role, 2: string, 3: string}
     */
    private function scenario(): array
    {
        $superAdminIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'Super Admin')
            ->pluck('model_has_roles.model_id');

        $actor = User::query()->whereNotIn('pk', $superAdminIds)->orderBy('pk')->first();
        $held = Role::where('name', '!=', 'Super Admin')->where('guard_name', 'web')
            ->has('permissions')->orderBy('id')->first();

        if (! $actor || ! $held) {
            $this->markTestSkipped('need a non-Super-Admin account and an ordinary role with permissions');
        }

        $heldNames = $held->permissions()->pluck('name');
        $target = Role::where('name', '!=', 'Super Admin')->where('guard_name', 'web')
            ->where('id', '!=', $held->id)->orderBy('id')->first();

        if (! $target) {
            $this->markTestSkipped('need a second ordinary role');
        }

        $grantable = $heldNames->first(fn ($n) => ! $target->hasPermissionTo($n));
        $notHeld = DB::table('permissions')->where('guard_name', 'web')
            ->whereNotIn('name', $heldNames)->orderBy('id')->value('name');

        if (! $grantable || ! $notHeld) {
            $this->markTestSkipped('fixture roles leave nothing to grant or nothing un-held');
        }

        $actor->syncRoles([$held->name]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $actor = $actor->fresh();

        $this->actingAs($actor);
        // hasRole() answers from the session list written at login first.
        session(['user_roles' => [$held->name]]);

        $this->assertFalse(isSidebarPrivilegedUser(), 'premise: the actor must not be Super Admin');

        return [$actor, $target, $grantable, $notHeld];
    }

    private function callGuard(Role $role, string $permission, int $status = 1)
    {
        $request = Request::create('/roles/permissions/'.$role->id, 'POST', [
            'permission' => $permission,
            'status' => $status,
        ]);

        return app(RoleController::class)->assignPermission($request, $role->id);
    }

    private function roleHolds(Role $role, string $permission): bool
    {
        return DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id', $role->id)
            ->where('permissions.name', $permission)
            ->exists();
    }

    public function test_a_non_super_admin_cannot_change_the_super_admin_role(): void
    {
        [, , $grantable] = $this->scenario();
        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'web')->firstOrFail();
        $before = $this->roleHolds($superAdmin, $grantable);

        $response = $this->callGuard($superAdmin, $grantable, $before ? 0 : 1);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame($before, $this->roleHolds($superAdmin, $grantable), 'refused, and the Super Admin role changed anyway');
    }

    public function test_a_non_super_admin_cannot_grant_a_permission_it_does_not_hold(): void
    {
        [, $target, , $notHeld] = $this->scenario();
        $before = $this->roleHolds($target, $notHeld);

        $response = $this->callGuard($target, $notHeld, 1);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame($before, $this->roleHolds($target, $notHeld), 'refused, and the grant was written anyway');
    }

    /** CONTROL - the guard is not a blanket lockout: a permission the actor holds may be passed on. */
    public function test_a_non_super_admin_may_grant_a_permission_it_holds(): void
    {
        [, $target, $grantable] = $this->scenario();
        $this->assertFalse($this->roleHolds($target, $grantable), 'premise: target must not already hold it');

        $response = $this->callGuard($target, $grantable, 1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->roleHolds($target, $grantable), 'admitted, but the grant was not written');
    }
}
