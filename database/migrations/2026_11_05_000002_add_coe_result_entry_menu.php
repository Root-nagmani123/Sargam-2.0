<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
 * Where it goes: the group that already holds the COE screens (any admin/coe/ route),
 * else a "COE" group under Setup, else a new "COE" group — staging made its COE menus
 * through Menu Management, so its group need not be the one 2026_09_30 creates.
 * deleted_at / is_container / target are written only where the column exists.
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

        DB::transaction(function () use ($now) {
            [$categoryId, $groupId] = $this->coeGroup($now);

            Permission::firstOrCreate(['name' => self::CONTAINER_PERMISSION, 'guard_name' => 'web']);

            $containerId = $this->live(DB::table('menus'), 'menus')
                ->where('group_id', $groupId)->whereNull('parent_id')->where('name', self::CONTAINER)
                ->value('id');

            if ($containerId) {
                DB::table('menus')->where('id', $containerId)->update($this->menuColumns([
                    'route' => null,
                    'is_container' => 1,
                    'target' => '0',
                    'permission_name' => self::CONTAINER_PERMISSION,
                    'updated_at' => $now,
                ]));
            } else {
                $containerId = DB::table('menus')->insertGetId($this->menuColumns([
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
                ]));
            }

            foreach (self::CHILDREN as $i => [$name, $route, $icon, $permission]) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

                $existing = $this->live(DB::table('menus'), 'menus')
                    ->where('route', $route)->where('id', '!=', $containerId)
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

                DB::table('menus')->insert($this->menuColumns([
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
                ]));
            }

            $faculty = Role::where('name', self::FACULTY_ROLE)->where('guard_name', 'web')->first();
            if ($faculty && ! $faculty->hasPermissionTo(self::FACULTY_PERMISSION)) {
                $faculty->givePermissionTo(self::FACULTY_PERMISSION);
            }
        });

        $this->clearCaches();
    }

    /**
     * [category id, group id] of the COE menus: the group of an existing admin/coe/
     * screen, else the "COE" group under Setup, else a new one there.
     *
     * @return array{0:int, 1:int}
     */
    private function coeGroup($now): array
    {
        $existing = $this->live(DB::table('menus'), 'menus')
            ->where('route', 'like', 'admin/coe/%')->whereNotNull('group_id')
            ->orderBy('id')->first(['category_id', 'group_id']);
        if ($existing) {
            return [(int) $existing->category_id, (int) $existing->group_id];
        }

        $categoryId = $this->live(DB::table('sidebar_categories'), 'sidebar_categories')
            ->where('slug', self::CATEGORY_SLUG)->value('id');
        if (! $categoryId) {
            throw new RuntimeException('Sidebar category "'.self::CATEGORY_SLUG.'" not found — cannot place the COE menus.');
        }

        $groupId = $this->live(DB::table('menu_groups'), 'menu_groups')
            ->where('category_id', $categoryId)->where('name', self::GROUP_NAME)->value('id');
        if (! $groupId) {
            // Same group 2026_09_30_120000_add_coe_sidebar_menus would have made.
            $groupId = DB::table('menu_groups')->insertGetId(array_intersect_key([
                'category_id' => $categoryId,
                'name' => self::GROUP_NAME,
                'icon' => 'fact_check',
                'order' => (int) DB::table('menu_groups')->where('category_id', $categoryId)->max('order') + 1,
                'is_active' => '1', // enum('1','0'): the string, not the enum index
                'created_at' => $now,
                'updated_at' => $now,
            ], array_flip(Schema::getColumnListing('menu_groups'))));
        }

        return [(int) $categoryId, (int) $groupId];
    }

    /** Not soft-deleted, on tables that have deleted_at. */
    private function live(Builder $query, string $table): Builder
    {
        return Schema::hasColumn($table, 'deleted_at') ? $query->whereNull('deleted_at') : $query;
    }

    /** Only the columns the menus table has on this database. */
    private function menuColumns(array $row): array
    {
        static $columns;
        $columns ??= array_flip(Schema::getColumnListing('menus'));

        return array_intersect_key($row, $columns);
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
