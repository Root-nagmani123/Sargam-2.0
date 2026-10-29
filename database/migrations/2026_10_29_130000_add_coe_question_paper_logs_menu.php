<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Adds "Question Paper Management Logs" (admin/coe/question-paper-logs) under
 * COE › Question Paper Management, after My Question Paper (Rajbhasha).
 * No role is granted it, like the admin Question Paper screen: Super Admin
 * passes, others get it through Roles & Permissions. Idempotent, same pattern
 * as 2026_10_15_120000_add_coe_component_master_menu.php.
 */
return new class extends Migration
{
    private const CATEGORY_SLUG = 'setup';

    private const GROUP_NAME = 'COE';

    private const CONTAINER_NAME = 'Question Paper Management';

    private const ROUTE = 'admin/coe/question-paper-logs';

    private const PERMISSION = 'coe_question_paper_logs';

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

        Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);

        if (! DB::table('menus')->where('route', self::ROUTE)->whereNull('deleted_at')->exists()) {
            $order = (int) DB::table('menus')->where('parent_id', $containerId)->max('order') + 1;

            DB::table('menus')->insert([
                'category_id' => $categoryId,
                'group_id' => $groupId,
                'parent_id' => $containerId,
                'name' => 'Question Paper Management Logs',
                'route' => self::ROUTE,
                'icon' => 'history',
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

    public function down(): void
    {
        DB::table('menus')->where('route', self::ROUTE)->delete();

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
