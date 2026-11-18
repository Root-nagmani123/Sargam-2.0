<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Setup › COE › Result Management › OMR Upload   admin/coe/omr-upload   coe_omr_upload
 *
 * Goes under the "Result Management" container of the COE group (made by
 * 2026_11_03_000002 / 2026_11_05_000002, or by Menu Management); if that container
 * is missing the link is added at the top of the COE group instead. A menu that
 * already has the route is moved, not duplicated. No role gets the permission —
 * Super Admin passes, others via Roles & Permissions.
 */
return new class extends Migration
{
    private const ROUTE = 'admin/coe/omr-upload';

    private const PERMISSION = 'coe_omr_upload';

    private const CONTAINER = 'Result Management';

    public function up(): void
    {
        $now = now();

        DB::transaction(function () use ($now) {
            Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);

            $coe = $this->live(DB::table('menus'), 'menus')
                ->where('route', 'like', 'admin/coe/%')->whereNotNull('group_id')
                ->orderBy('id')->first(['category_id', 'group_id']);
            if (! $coe) {
                throw new RuntimeException('No COE menu found — run the earlier COE menu migrations first.');
            }

            $containerId = $this->live(DB::table('menus'), 'menus')
                ->where('group_id', $coe->group_id)->whereNull('parent_id')->where('name', self::CONTAINER)
                ->value('id');

            $siblings = DB::table('menus')->where('group_id', $coe->group_id);
            $containerId ? $siblings->where('parent_id', $containerId) : $siblings->whereNull('parent_id');
            $order = (int) $siblings->max('order') + 1;

            $existing = $this->live(DB::table('menus'), 'menus')->where('route', self::ROUTE)->value('id');
            if ($existing) {
                DB::table('menus')->where('id', $existing)->update([
                    'parent_id' => $containerId,
                    'group_id' => $coe->group_id,
                    'category_id' => $coe->category_id,
                    'permission_name' => self::PERMISSION,
                    'updated_at' => $now,
                ]);

                return;
            }

            DB::table('menus')->insert($this->menuColumns([
                'category_id' => $coe->category_id,
                'group_id' => $coe->group_id,
                'parent_id' => $containerId,
                'name' => 'OMR Upload',
                'route' => self::ROUTE,
                'icon' => 'upload_file',
                'permission_name' => self::PERMISSION,
                'order' => $order,
                'is_active' => 1,
                'is_container' => 0,
                'target' => '0',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        });

        $this->clearCaches();
    }

    public function down(): void
    {
        DB::transaction(function () {
            DB::table('menus')->where('route', self::ROUTE)->delete();

            $id = DB::table('permissions')->where('name', self::PERMISSION)->where('guard_name', 'web')->value('id');
            if ($id) {
                DB::table('role_has_permissions')->where('permission_id', $id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $id)->delete();
                DB::table('permissions')->where('id', $id)->delete();
            }
        });

        $this->clearCaches();
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

    private function clearCaches(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }
};
