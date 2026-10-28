<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Adds "Subject Max Marks" and "Service Passing Marks" under the existing
 * COE › Master Data container, after Component Master. Idempotent, same
 * pattern as 2026_10_15_120000_add_coe_component_master_menu.php.
 */
return new class extends Migration
{
    private const CATEGORY_SLUG = 'setup';

    private const GROUP_NAME = 'COE';

    private const CONTAINER_NAME = 'Master Data';

    /** route => [name, icon, permission] */
    private const MENUS = [
        'admin/coe/subject-max-marks' => ['Subject Max Marks', 'grading', 'coe_subject_max_marks_master'],
        'admin/coe/service-passing-marks' => ['Service Passing Marks', 'verified', 'coe_service_passing_marks_master'],
    ];

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

        foreach (self::MENUS as $route => [$name, $icon, $permission]) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

            if (DB::table('menus')->where('route', $route)->whereNull('deleted_at')->exists()) {
                continue;
            }

            $order = (int) DB::table('menus')->where('parent_id', $containerId)->max('order') + 1;

            DB::table('menus')->insert([
                'category_id' => $categoryId,
                'group_id' => $groupId,
                'parent_id' => $containerId,
                'name' => $name,
                'route' => $route,
                'icon' => $icon,
                'permission_name' => $permission,
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

    public function down(): void
    {
        foreach (self::MENUS as $route => [, , $permission]) {
            DB::table('menus')->where('route', $route)->delete();

            $id = DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->value('id');
            if ($id) {
                DB::table('role_has_permissions')->where('permission_id', $id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $id)->delete();
                DB::table('permissions')->where('id', $id)->delete();
            }
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
