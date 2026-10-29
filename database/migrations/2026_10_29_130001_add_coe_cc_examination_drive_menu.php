<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Adds COE › Examination Management › "Examination Drive (CC)"
 * (admin/coe/cc-examination-drive) right after "Examination Drive (COE)", for
 * the Faculty role: a Course Coordinator is a faculty, and the page lists only
 * the drives of courses they coordinate. Super Admin sees every menu anyway.
 * Idempotent, same pattern as 2026_10_29_120000_add_coe_examination_drive_coe_menu.php.
 */
return new class extends Migration
{
    private const CATEGORY_SLUG = 'setup';

    private const GROUP_NAME = 'COE';

    private const CONTAINER_NAME = 'Examination Management';

    private const AFTER_ROUTE = 'admin/coe/coe-examination-drive';

    private const ROUTE = 'admin/coe/cc-examination-drive';

    private const PERMISSION = 'coe_cc_examination_drive';

    private const ROLE = 'Faculty';

    public function up(): void
    {
        $now = now();

        $categoryId = DB::table('sidebar_categories')
            ->where('slug', self::CATEGORY_SLUG)->whereNull('deleted_at')->value('id');
        if (! $categoryId) {
            throw new RuntimeException('Sidebar category "'.self::CATEGORY_SLUG.'" not found.');
        }

        $groupId = DB::table('menu_groups')
            ->where('category_id', $categoryId)->where('name', self::GROUP_NAME)->whereNull('deleted_at')
            ->value('id');
        if (! $groupId) {
            throw new RuntimeException('Menu group "'.self::GROUP_NAME.'" not found — run the COE sidebar migration first.');
        }

        $containerId = DB::table('menus')
            ->where('group_id', $groupId)->whereNull('parent_id')->where('name', self::CONTAINER_NAME)->whereNull('deleted_at')
            ->value('id');
        if (! $containerId) {
            throw new RuntimeException('Menu container "'.self::CONTAINER_NAME.'" not found — run the COE sidebar migration first.');
        }

        $role = Role::where('name', self::ROLE)->where('guard_name', 'web')->first();
        if (! $role) {
            throw new RuntimeException('Role "'.self::ROLE.'" not found.');
        }
        $role->givePermissionTo(Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']));

        if (! DB::table('menus')->where('route', self::ROUTE)->whereNull('deleted_at')->exists()) {
            // Directly after the COE list; later siblings move down one.
            $after = (int) DB::table('menus')->where('parent_id', $containerId)->where('route', self::AFTER_ROUTE)->value('order');
            $order = $after > 0 ? $after + 1 : (int) DB::table('menus')->where('parent_id', $containerId)->max('order') + 1;
            DB::table('menus')->where('parent_id', $containerId)->where('order', '>=', $order)->increment('order');

            DB::table('menus')->insert([
                'category_id' => $categoryId,
                'group_id' => $groupId,
                'parent_id' => $containerId,
                'name' => 'Examination Drive (CC)',
                'route' => self::ROUTE,
                'icon' => 'approval',
                'permission_name' => self::PERMISSION,
                'order' => $order,
                'is_active' => 1,
                'is_container' => 0,
                'target' => '0',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->clearCaches();
    }

    /** Removes the menu and the permission (with the Faculty role's grant of it). */
    public function down(): void
    {
        $menu = DB::table('menus')->where('route', self::ROUTE)->first(['id', 'parent_id', 'order']);
        if ($menu) {
            DB::table('menus')->where('id', $menu->id)->delete();
            DB::table('menus')->where('parent_id', $menu->parent_id)->where('order', '>', $menu->order)->decrement('order');
        }

        $id = DB::table('permissions')->where('name', self::PERMISSION)->where('guard_name', 'web')->value('id');
        if ($id) {
            DB::table('role_has_permissions')->where('permission_id', $id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }

        $this->clearCaches();
    }

    private function clearCaches(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }
};
