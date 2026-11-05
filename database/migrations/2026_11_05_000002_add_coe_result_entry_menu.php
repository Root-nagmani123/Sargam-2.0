<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Setup › COE › Result Management as a container of three screens:
 *
 *     Result Management                      (container, coe_result_management)
 *      ├─ Result Compilation                 admin/coe/result-compilation    coe_result_compilation
 *      ├─ Result Entry (Faculty)             admin/coe/faculty-result-entry  coe_faculty_result_entry
 *      └─ Merit & Ranking                    admin/coe/merit-ranking         coe_merit_ranking
 *
 * Works whatever state a database is in: 2026_11_03_000002 may already have made
 * the container, or Menu Management may have made "Result Management" as a plain
 * link — either is turned into the container, keeping its icon and position.
 * A child whose route already exists is moved under the container, not duplicated.
 *
 * Faculty get Result Entry (the faculty-end screen, like Examination Drive (Faculty));
 * the admin screens go to no role — Super Admin passes, others via Roles & Permissions.
 */
return new class extends Migration
{
    private const CATEGORY_SLUG = 'setup';

    private const GROUP_NAME = 'COE';

    private const CONTAINER = 'Result Management';

    private const CONTAINER_PERMISSION = 'coe_result_management';

    /** [name, route, icon, permission] */
    private const CHILDREN = [
        ['Result Compilation', 'admin/coe/result-compilation', 'fact_check', 'coe_result_compilation'],
        ['Result Entry (Faculty)', 'admin/coe/faculty-result-entry', 'edit_note', 'coe_faculty_result_entry'],
        ['Merit & Ranking', 'admin/coe/merit-ranking', 'military_tech', 'coe_merit_ranking'],
    ];

    private const FACULTY_ROLE = 'Faculty';

    private const FACULTY_PERMISSION = 'coe_faculty_result_entry';

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
            Permission::firstOrCreate(['name' => self::CONTAINER_PERMISSION, 'guard_name' => 'web']);

            $containerId = DB::table('menus')
                ->where('group_id', $groupId)->whereNull('parent_id')->where('name', self::CONTAINER)->whereNull('deleted_at')
                ->value('id');

            if ($containerId) {
                DB::table('menus')->where('id', $containerId)->update([
                    'route' => null,
                    'is_container' => 1,
                    'target' => '0',
                    'permission_name' => self::CONTAINER_PERMISSION,
                    'updated_at' => $now,
                ]);
            } else {
                $containerId = DB::table('menus')->insertGetId([
                    'category_id' => $categoryId,
                    'group_id' => $groupId,
                    'parent_id' => null,
                    'name' => self::CONTAINER,
                    'route' => null,
                    'icon' => 'analytics',
                    'permission_name' => self::CONTAINER_PERMISSION,
                    'order' => (int) DB::table('menus')->where('group_id', $groupId)->whereNull('parent_id')->max('order') + 1,
                    'is_active' => 1,
                    'is_container' => 1,
                    'target' => '0',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach (self::CHILDREN as $i => [$name, $route, $icon, $permission]) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

                $existing = DB::table('menus')
                    ->where('route', $route)->where('is_container', 0)->whereNull('deleted_at')
                    ->value('id');

                if ($existing) {
                    DB::table('menus')->where('id', $existing)->update([
                        'parent_id' => $containerId,
                        'group_id' => $groupId,
                        'category_id' => $categoryId,
                        'permission_name' => $permission,
                        'updated_at' => $now,
                    ]);

                    continue;
                }

                DB::table('menus')->insert([
                    'category_id' => $categoryId,
                    'group_id' => $groupId,
                    'parent_id' => $containerId,
                    'name' => $name,
                    'route' => $route,
                    'icon' => $icon,
                    'permission_name' => $permission,
                    'order' => $i + 1,
                    'is_active' => 1,
                    'is_container' => 0,
                    'target' => '0',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $faculty = Role::where('name', self::FACULTY_ROLE)->where('guard_name', 'web')->first();
            if ($faculty && ! $faculty->hasPermissionTo(self::FACULTY_PERMISSION)) {
                $faculty->givePermissionTo(self::FACULTY_PERMISSION);
            }
        });

        $this->clearCaches();
    }

    /**
     * Removes only what this migration alone adds: the Result Entry menu and its
     * permission. The container, Result Compilation and Merit & Ranking belong to
     * 2026_11_03_000002 (or Menu Management) and are left in place.
     */
    public function down(): void
    {
        DB::transaction(function () {
            DB::table('menus')->where('route', 'admin/coe/faculty-result-entry')->delete();

            $id = DB::table('permissions')->where('name', self::FACULTY_PERMISSION)->where('guard_name', 'web')->value('id');
            if ($id) {
                DB::table('role_has_permissions')->where('permission_id', $id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $id)->delete();
                DB::table('permissions')->where('id', $id)->delete();
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
