<?php

namespace Tests\Feature;

use App\Models\SidebarMenu\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * PR #309 review F-077.
 *
 * MenuService::update() renames the `permissions` row whenever a menu's name changes,
 * and a rename moves that permission to every role holding it. Behind `auth` alone,
 * an account holding one menu permission could rename it to `users` or
 * `member_pii_read` and pass the gates those names protect. The sidebar write routes
 * are now Super Admin only.
 *
 * The actor holds a real menu permission, not nothing: a role-less account has no
 * permission row to rename, so a test written with one passes against the unfixed
 * routes and proves nothing. The Super Admin control shows the write path still
 * works - and still renames - for the one role allowed to use it.
 */
class SidebarMenuRenameEscalationTest extends TestCase
{
    use DatabaseTransactions;

    private function forget(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** A menu whose permission_name has a permissions row, other than `users` itself. */
    private function menuWithPermission(): array
    {
        foreach (Menu::query()->whereNotNull('group_id')->where('permission_name', '!=', 'users')->orderBy('id')->get() as $menu) {
            $permission = Permission::where('name', $menu->permission_name)->where('guard_name', 'web')->first();
            if ($permission) {
                return [$menu, $permission];
            }
        }

        $this->markTestSkipped('No menu with a matching permissions row in this database.');
    }

    private function actorHolding(Permission $permission, int $offset, ?string $extraRole = null): User
    {
        $user = User::query()->orderBy('pk')->skip($offset)->first();
        if (! $user) {
            $this->markTestSkipped('Not enough user_credentials rows.');
        }

        $user->syncRoles([]);
        $user->syncPermissions([]);
        $role = Role::create(['name' => 'zz_f077_role_'.$offset, 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        if ($extraRole) {
            $user->assignRole(Role::where('name', $extraRole)->where('guard_name', 'web')->firstOrFail());
        }
        $this->forget();

        return $user->fresh();
    }

    private function renamePayload(Menu $menu, string $name): array
    {
        return [
            'category_id' => $menu->category_id,
            'group_id' => $menu->group_id,
            'parent_id' => $menu->parent_id,
            'name' => $name,
            'route' => $menu->route,
            'order' => $menu->order,
            'is_active' => (string) $menu->is_active,
            'icon' => $menu->icon,
            'target' => (string) $menu->target,
        ];
    }

    public function test_an_ordinary_account_cannot_rename_a_menu_into_a_gated_permission(): void
    {
        [$menu, $permission] = $this->menuWithPermission();
        $actor = $this->actorHolding($permission, 5);
        $this->assertFalse($actor->hasRole('Super Admin'));

        $before = DB::table('permissions')->where('id', $permission->id)->value('name');

        foreach (['Users', 'Member Pii Read'] as $name) {
            $this->actingAs($actor)
                ->put('/sidebar/menus/'.$menu->id, $this->renamePayload($menu, $name))
                ->assertStatus(403);
        }

        $this->forget();
        $this->assertSame($before, DB::table('permissions')->where('id', $permission->id)->value('name'));
        $this->assertSame($menu->name, DB::table('menus')->where('id', $menu->id)->value('name'));

        // The gates the rename would have opened stay shut.
        $this->actingAs($actor->fresh())
            ->get('/admin/users/assign-role/'.encrypt($actor->pk))
            ->assertStatus(403);
    }

    public function test_an_ordinary_account_cannot_create_or_toggle_sidebar_entries(): void
    {
        [$menu, $permission] = $this->menuWithPermission();
        $actor = $this->actorHolding($permission, 5);
        $menusBefore = DB::table('menus')->count();

        $this->actingAs($actor)
            ->post('/sidebar/menus', $this->renamePayload($menu, 'Zz F077 Probe'))
            ->assertStatus(403);
        $this->actingAs($actor)
            ->get('/sidebar/menus/status/'.$menu->id.'?is_active=0')
            ->assertStatus(403);
        $this->actingAs($actor)
            ->delete('/sidebar/menus/'.$menu->id)
            ->assertStatus(403);

        $this->assertSame($menusBefore, DB::table('menus')->count());
        $this->assertSame((int) $menu->is_active, (int) DB::table('menus')->where('id', $menu->id)->value('is_active'));
    }

    public function test_super_admin_can_still_edit_a_menu_and_the_rename_still_follows(): void
    {
        [$menu, $permission] = $this->menuWithPermission();
        $admin = $this->actorHolding($permission, 6, 'Super Admin');
        $this->assertTrue($admin->hasRole('Super Admin'));

        $this->actingAs($admin)
            ->from('/sidebar/menus')
            ->put('/sidebar/menus/'.$menu->id, $this->renamePayload($menu, 'Zz F077 Renamed'))
            ->assertStatus(302)
            ->assertSessionHasNoErrors();

        $this->assertSame('Zz F077 Renamed', DB::table('menus')->where('id', $menu->id)->value('name'));
        $this->assertSame('zz_f077_renamed', DB::table('permissions')->where('id', $permission->id)->value('name'));
    }

    public function test_the_sidebar_feed_stays_open_to_every_signed_in_account(): void
    {
        [, $permission] = $this->menuWithPermission();
        $actor = $this->actorHolding($permission, 5);

        $this->assertNotSame(403, $this->actingAs($actor)->get('/sidebar/menu')->status());
    }
}
