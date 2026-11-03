<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Two new containers under Setup › COE, after the existing ones:
 *
 *     ├─ Result Management
 *     │    ├─ Result Compilation        admin/coe/result-compilation
 *     │    └─ Merit & Ranking           admin/coe/merit-ranking
 *     └─ Document Management
 *          ├─ Signature Master          admin/coe/signature-master
 *          ├─ Marksheet Generation      admin/coe/marksheet-generation
 *          └─ Verified Marksheet        admin/coe/verified-marksheet
 *
 * No role is granted these: Super Admin passes, others get them through Roles &
 * Permissions. Idempotent, same pattern as 2026_09_30_120000_add_coe_sidebar_menus.php.
 */
return new class extends Migration
{
    private const CATEGORY_SLUG = 'setup';

    private const GROUP_NAME = 'COE';

    /** container => [icon, permission, children [name, route, icon, permission]] */
    private function tree(): array
    {
        return [
            'Result Management' => ['analytics', 'coe_result_management', [
                ['Result Compilation', 'admin/coe/result-compilation', 'fact_check', 'coe_result_compilation'],
                ['Merit & Ranking', 'admin/coe/merit-ranking', 'military_tech', 'coe_merit_ranking'],
            ]],
            'Document Management' => ['article', 'coe_document_management', [
                ['Signature Master', 'admin/coe/signature-master', 'draw', 'coe_signature_master'],
                ['Marksheet Generation', 'admin/coe/marksheet-generation', 'description', 'coe_marksheet_generation'],
                ['Verified Marksheet', 'admin/coe/verified-marksheet', 'verified', 'coe_verified_marksheet'],
            ]],
        ];
    }

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

        DB::transaction(function () use ($categoryId, $groupId, $now) {
            foreach ($this->tree() as $containerName => [$icon, $permission, $children]) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

                $containerId = DB::table('menus')
                    ->where('group_id', $groupId)->whereNull('parent_id')->where('name', $containerName)->whereNull('deleted_at')
                    ->value('id');
                if (! $containerId) {
                    $containerId = DB::table('menus')->insertGetId([
                        'category_id' => $categoryId,
                        'group_id' => $groupId,
                        'parent_id' => null,
                        'name' => $containerName,
                        'route' => null,
                        'icon' => $icon,
                        'permission_name' => $permission,
                        'order' => (int) DB::table('menus')->where('group_id', $groupId)->whereNull('parent_id')->max('order') + 1,
                        'is_active' => 1,
                        'is_container' => 1,
                        'target' => '0',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                foreach ($children as $i => [$name, $route, $childIcon, $childPermission]) {
                    Permission::firstOrCreate(['name' => $childPermission, 'guard_name' => 'web']);

                    if (DB::table('menus')->where('route', $route)->whereNull('deleted_at')->exists()) {
                        continue;
                    }

                    DB::table('menus')->insert([
                        'category_id' => $categoryId,
                        'group_id' => $groupId,
                        'parent_id' => $containerId,
                        'name' => $name,
                        'route' => $route,
                        'icon' => $childIcon,
                        'permission_name' => $childPermission,
                        'order' => $i + 1,
                        'is_active' => 1,
                        'is_container' => 0,
                        'target' => '0',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        $this->clearCaches();
    }

    public function down(): void
    {
        $categoryId = DB::table('sidebar_categories')->where('slug', self::CATEGORY_SLUG)->value('id');
        $groupId = DB::table('menu_groups')
            ->where('category_id', $categoryId)->where('name', self::GROUP_NAME)
            ->value('id');

        DB::transaction(function () use ($groupId) {
            $permissions = [];
            foreach ($this->tree() as $containerName => [, $permission, $children]) {
                $permissions[] = $permission;
                foreach ($children as [, $route, , $childPermission]) {
                    $permissions[] = $childPermission;
                    DB::table('menus')->where('route', $route)->delete();
                }
                if ($groupId) {
                    DB::table('menus')->where('group_id', $groupId)->whereNull('parent_id')
                        ->where('name', $containerName)->delete();
                }
            }

            $ids = DB::table('permissions')->whereIn('name', $permissions)->where('guard_name', 'web')->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
                DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
                DB::table('permissions')->whereIn('id', $ids)->delete();
            }
        });

        $this->clearCaches();
    }

    private function clearCaches(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }
};
