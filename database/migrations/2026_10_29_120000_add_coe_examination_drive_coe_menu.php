<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Adds COE › Examination Management › "Examination Drive (COE)"
 * (admin/coe/coe-examination-drive) right after "Examination Drive (Faculty)",
 * for the COE role. The COE role is created if missing. Super Admin sees every
 * menu anyway. Idempotent, same pattern as 2026_10_15_120000_add_coe_component_master_menu.php.
 */
return new class extends Migration
{
    private const CATEGORY_SLUG = 'setup';

    private const GROUP_NAME = 'COE';

    private const CONTAINER_NAME = 'Examination Management';

    private const AFTER_ROUTE = 'admin/coe/faculty-examination-drive';

    private const ROUTE = 'admin/coe/coe-examination-drive';

    private const PERMISSION = 'coe_coe_examination_drive';

    private const ROLE = 'COE';

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

        $permission = Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => self::ROLE, 'guard_name' => 'web'])->givePermissionTo($permission);

        if (! DB::table('menus')->where('route', self::ROUTE)->whereNull('deleted_at')->exists()) {
            // Directly after the faculty list; later siblings move down one.
            $after = (int) DB::table('menus')->where('parent_id', $containerId)->where('route', self::AFTER_ROUTE)->value('order');
            $order = $after > 0 ? $after + 1 : (int) DB::table('menus')->where('parent_id', $containerId)->max('order') + 1;
            DB::table('menus')->where('parent_id', $containerId)->where('order', '>=', $order)->increment('order');

            DB::table('menus')->insert([
                'category_id' => $categoryId,
                'group_id' => $groupId,
                'parent_id' => $containerId,
                'name' => 'Examination Drive (COE)',
                'route' => self::ROUTE,
                'icon' => 'fact_check',
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

    /** Removes the menu and permission; the COE role is left in place (it may have users). */
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
