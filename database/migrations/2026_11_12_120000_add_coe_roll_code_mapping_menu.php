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
 * Setup › COE › Examination Management › Roll No ↔ Unique Code Mapping
 * (admin/coe/roll-code-mapping, permission coe_roll_code_mapping).
 *
 * No table: the page reads coe_roll_numbers + coe_participant_codes
 * (App\Services\COE\RollCodeMapping). This adds only the sidebar link and its
 * permission.
 *
 * Placed right after Roll Numbers, under the same parent (Examination
 * Management on this database); the siblings after it move down one place.
 * If Roll Numbers is not found, under the "Examination Management" container of
 * the COE group, else at the end of the COE group.
 *
 * The mapping undoes the anonymity of the Unique Participant Codes, so only the
 * COE role is given the permission (Super Admin always passes). Faculty and
 * other roles are not — grant it in Roles & Permissions only to COE staff.
 * Safe to run twice: an existing menu for the route is updated, not duplicated.
 */
return new class extends Migration
{
    private const ROUTE = 'admin/coe/roll-code-mapping';

    private const NAME = 'Roll No ↔ Unique Code Mapping';

    private const PERMISSION = 'coe_roll_code_mapping';

    private const ICON = 'swap_horiz';

    private const ROLL_NUMBER_ROUTE = 'admin/coe/roll-number';

    private const CONTAINER = 'Examination Management';

    private const ROLE = 'COE';

    public function up(): void
    {
        $now = now();

        DB::transaction(function () use ($now) {
            Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);

            // Anchor: the Roll Numbers menu (by route, with or without a leading slash).
            $anchor = $this->live(DB::table('menus'), 'menus')
                ->whereIn('route', [self::ROLL_NUMBER_ROUTE, '/'.self::ROLL_NUMBER_ROUTE])
                ->orderBy('id')->first(['id', 'category_id', 'group_id', 'parent_id', 'order']);

            if ($anchor) {
                [$categoryId, $groupId, $parentId] = [$anchor->category_id, $anchor->group_id, $anchor->parent_id];
                $order = (int) $anchor->order + 1;
            } else {
                $coe = $this->live(DB::table('menus'), 'menus')
                    ->where('route', 'like', 'admin/coe/%')->whereNotNull('group_id')
                    ->orderBy('id')->first(['category_id', 'group_id']);
                if (! $coe) {
                    throw new RuntimeException('No COE menu found — run the COE sidebar migrations first.');
                }
                [$categoryId, $groupId] = [$coe->category_id, $coe->group_id];
                $parentId = $this->live(DB::table('menus'), 'menus')
                    ->where('group_id', $groupId)->whereNull('parent_id')->where('name', self::CONTAINER)
                    ->value('id');
                $order = (int) $this->live(DB::table('menus'), 'menus')
                    ->where('group_id', $groupId)
                    ->when($parentId, fn ($q) => $q->where('parent_id', $parentId), fn ($q) => $q->whereNull('parent_id'))
                    ->max('order') + 1;
            }

            $existing = $this->live(DB::table('menus'), 'menus')
                ->whereIn('route', [self::ROUTE, '/'.self::ROUTE])->value('id');

            if ($existing) {
                DB::table('menus')->where('id', $existing)->update($this->menuColumns([
                    'name' => self::NAME,
                    'permission_name' => self::PERMISSION,
                    'updated_at' => $now,
                ]));
            } else {
                // Make room right after Roll Numbers.
                $this->live(DB::table('menus'), 'menus')
                    ->where('group_id', $groupId)
                    ->when($parentId, fn ($q) => $q->where('parent_id', $parentId), fn ($q) => $q->whereNull('parent_id'))
                    ->where('order', '>=', $order)
                    ->increment('order');

                DB::table('menus')->insert($this->menuColumns([
                    'category_id' => $categoryId,
                    'group_id' => $groupId,
                    'parent_id' => $parentId,
                    'name' => self::NAME,
                    'route' => self::ROUTE,
                    'icon' => self::ICON,
                    'permission_name' => self::PERMISSION,
                    'order' => $order,
                    'is_active' => 1,
                    'is_container' => 0,
                    'target' => '0',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }

            $role = Role::where('name', self::ROLE)->where('guard_name', 'web')->first();
            if ($role && ! $role->hasPermissionTo(self::PERMISSION)) {
                $role->givePermissionTo(self::PERMISSION);
            }
        });

        $this->clearCaches();
    }

    /** Removes the menu and its permission; the siblings' order is closed up again. */
    public function down(): void
    {
        DB::transaction(function () {
            $menu = DB::table('menus')->whereIn('route', [self::ROUTE, '/'.self::ROUTE])->first(['id', 'group_id', 'parent_id', 'order']);
            if ($menu) {
                DB::table('menus')->where('id', $menu->id)->delete();
                DB::table('menus')
                    ->where('group_id', $menu->group_id)
                    ->when($menu->parent_id, fn ($q) => $q->where('parent_id', $menu->parent_id), fn ($q) => $q->whereNull('parent_id'))
                    ->where('order', '>', $menu->order)
                    ->decrement('order');
            }

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
