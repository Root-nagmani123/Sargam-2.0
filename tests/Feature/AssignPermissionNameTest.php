<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * assignPermission() may attach a permission; it may not invent one.
 *
 * The handler called Permission::firstOrCreate() on the raw request value, so any
 * string posted to it became a permission row attached to whichever role id was
 * in the URL. The 48 permissions in this database with no `menus` row behind them
 * - including 'dashbaord' and 'faculty_test' - are what that produced over time.
 *
 * The rule pinned here: a name that already exists stays writable (this endpoint
 * is the only way to revoke the orphans), a name defined by a `menus` row may be
 * created, and anything else is refused.
 *
 * NOTE, deliberately not asserted here: a holder of the `roles` permission can
 * still grant any EXISTING permission to any role, including its own. That is
 * what the Roles screen is for, and narrowing it is a product decision rather
 * than a bug fix.
 */
class AssignPermissionNameTest extends TestCase
{
    use DatabaseTransactions;

    private function superAdmin(): User
    {
        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        $pk = $roleId ? DB::table('model_has_roles')->where('role_id', $roleId)->value('model_id') : null;
        $user = $pk ? User::where('pk', $pk)->first() : null;

        if (! $user) {
            $this->markTestSkipped('no Super Admin account in this database');
        }

        return $user;
    }

    private function throwawayRole(): Role
    {
        $role = Role::create(['name' => 'Tmp Role '.uniqid(), 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }

    public function test_a_permission_backed_by_a_menus_row_can_still_be_granted(): void
    {
        $name = DB::table('menus')->whereNotNull('permission_name')
            ->where('permission_name', '!=', '')->value('permission_name');

        if (! $name) {
            $this->markTestSkipped('no menus row carries a permission_name');
        }

        $role = $this->throwawayRole();

        $this->actingAs($this->superAdmin())
            ->post(route('assign.roles.permissions', $role->id), [
                'permission' => $name,
                'status' => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($role->fresh()->hasPermissionTo($name),
            'the legitimate grant must still work');
    }

    public function test_an_invented_permission_name_is_refused_and_no_row_is_created(): void
    {
        $invented = 'not_a_screen_'.uniqid();
        $role = $this->throwawayRole();

        $this->actingAs($this->superAdmin())
            ->post(route('assign.roles.permissions', $role->id), [
                'permission' => $invented,
                'status' => 1,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('permissions', ['name' => $invented, 'guard_name' => 'web']);
        $this->assertSame(0, DB::table('permissions')->where('name', $invented)->count());
    }

    /**
     * The orphans must stay revocable, or this change would strand 48 permissions
     * granted with no way to take them back.
     */
    public function test_an_existing_permission_with_no_menus_row_can_still_be_revoked(): void
    {
        $menuNames = DB::table('menus')->whereNotNull('permission_name')->pluck('permission_name')->all();

        $orphan = DB::table('permissions')->where('guard_name', 'web')
            ->whereNotIn('name', $menuNames ?: [''])
            ->value('name');

        if (! $orphan) {
            $this->markTestSkipped('no permission without a menus row in this database');
        }

        $role = $this->throwawayRole();
        $role->givePermissionTo(Permission::where('name', $orphan)->where('guard_name', 'web')->first());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($role->fresh()->hasPermissionTo($orphan), 'premise: the role holds it');

        $this->actingAs($this->superAdmin())
            ->post(route('assign.roles.permissions', $role->id), [
                'permission' => $orphan,
                'status' => 0,
            ])
            ->assertOk();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertFalse($role->fresh()->hasPermissionTo($orphan),
            'an existing permission must stay revocable through this endpoint');
    }
}
