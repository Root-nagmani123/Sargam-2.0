<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Two-stage account-deletion approval, placed beside Quick Links Setup in the sidebar:
 *  - "Account Deletion Review"   (account_deletion_review)   - stage 1, granted to the new "Computer Section" role;
 *  - "Account Deletion Requests" (account_deletion_requests) - stage 2, the admin's final decision.
 * Super Admin passes both. Add people to "Computer Section" from the Users screen; grant stage 2 to any other
 * role from the role-permission screen. Permission names are Str::slug(menu name, '_'), as MenuService derives
 * them. Insert-only: existing rows are left alone.
 */
return new class extends Migration
{
    private const MENUS = [
        'account_deletion_review' => ['Account Deletion Review', 'admin/setup/account-deletion-review', 'fact_check'],
        'account_deletion_requests' => ['Account Deletion Requests', 'admin/setup/account-deletion-requests', 'person_remove'],
    ];

    private const REVIEWER_ROLE = 'Computer Section';

    public function up(): void
    {
        $now = now();
        $sibling = DB::table('menus')->where('route', 'admin/setup/quick-links')->first(['category_id', 'group_id', 'parent_id']);

        foreach (self::MENUS as $permission => [$name, $route, $icon]) {
            if (! DB::table('permissions')->where('name', $permission)->exists()) {
                DB::table('permissions')->insert(['name' => $permission, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
            }

            // No Setup anchor on this database: add the menu from the Menus screen instead.
            if ($sibling && ! DB::table('menus')->where('route', $route)->exists()) {
                DB::table('menus')->insert([
                    'category_id' => $sibling->category_id,
                    'group_id' => $sibling->group_id,
                    'parent_id' => $sibling->parent_id,
                    'name' => $name,
                    'route' => $route,
                    'icon' => $icon,
                    'permission_name' => $permission,
                    'order' => (int) DB::table('menus')->where('parent_id', $sibling->parent_id)->max('order') + 1,
                    'is_active' => 1,
                    'target' => '0',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $roleId = DB::table('roles')->where('name', self::REVIEWER_ROLE)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => self::REVIEWER_ROLE, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
        $permId = DB::table('permissions')->where('name', 'account_deletion_review')->value('id');
        DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permId, 'role_id' => $roleId]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        MenuService::clearStructureCache();
        SidebarNavResolver::clearCache();
    }

    public function down(): void
    {
        foreach (self::MENUS as $permission => [, $route]) {
            DB::table('menus')->where('route', $route)->delete();

            $permId = DB::table('permissions')->where('name', $permission)->value('id');
            if ($permId) {
                DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
                DB::table('model_has_permissions')->where('permission_id', $permId)->delete();
                DB::table('permissions')->where('id', $permId)->delete();
            }
        }

        // Drop the role only if this migration's grant was all it ever had.
        $roleId = DB::table('roles')->where('name', self::REVIEWER_ROLE)->value('id');
        if ($roleId && ! DB::table('model_has_roles')->where('role_id', $roleId)->exists()
            && ! DB::table('role_has_permissions')->where('role_id', $roleId)->exists()) {
            DB::table('roles')->where('id', $roleId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        MenuService::clearStructureCache();
        SidebarNavResolver::clearCache();
    }
};
