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
 * Sidebar entries for the two "my" pages a frozen seating plan feeds (both already notified
 * by the bell, but unreachable from the sidebar until now):
 *
 *     My Invigilation Duty   admin/coe/my-invigilation-duty   coe_my_invigilation_duty   Faculty roles
 *     My Exam Seat           admin/coe/my-exam-seat           coe_my_exam_seat           OT roles
 *
 * Placed next to Venue Allocation (its container, whatever it is called on this database),
 * else at the top level of the group holding the COE screens. A route already on the sidebar
 * (added through Menu Management) is left where it is and only gets its permission. Roles
 * that do not exist here are skipped. The pages show only the user's own data.
 */
return new class extends Migration
{
    /** [name, route, icon, permission, roles] */
    private const MENUS = [
        ['My Invigilation Duty', 'admin/coe/my-invigilation-duty', 'assignment_ind', 'coe_my_invigilation_duty',
            ['Faculty', 'Internal Faculty', 'Guest Faculty']],
        ['My Exam Seat', 'admin/coe/my-exam-seat', 'event_seat', 'coe_my_exam_seat',
            ['Officer Trainee', 'Student-OT']],
    ];

    private const ANCHOR_ROUTE = 'admin/coe/venue-allocation';

    public function up(): void
    {
        $now = now();

        DB::transaction(function () use ($now) {
            $anchor = $this->live(DB::table('menus'), 'menus')->where('route', self::ANCHOR_ROUTE)
                ->first(['category_id', 'group_id', 'parent_id', 'order']);
            $anchor ??= $this->live(DB::table('menus'), 'menus')->where('route', 'like', 'admin/coe/%')
                ->whereNotNull('group_id')->orderBy('id')->first(['category_id', 'group_id', 'parent_id', 'order']);
            if ($anchor === null) {
                throw new RuntimeException('No COE screen on the sidebar — cannot place My Invigilation Duty / My Exam Seat.');
            }
            $parentId = $anchor->parent_id ? (int) $anchor->parent_id : null;

            foreach (self::MENUS as $i => [$name, $route, $icon, $permission, $roles]) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

                $existing = $this->live(DB::table('menus'), 'menus')->where('route', $route)->value('id');
                if ($existing) {
                    DB::table('menus')->where('id', $existing)->update(['permission_name' => $permission, 'updated_at' => $now]);
                } else {
                    DB::table('menus')->insert($this->menuColumns([
                        'category_id' => $anchor->category_id,
                        'group_id' => $anchor->group_id,
                        'parent_id' => $parentId,
                        'name' => $name,
                        'route' => $route,
                        'icon' => $icon,
                        'permission_name' => $permission,
                        'order' => (int) $anchor->order + $i + 1,
                        'is_active' => 1,
                        'is_container' => 0,
                        'target' => '0',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]));
                }

                foreach ($roles as $roleName) {
                    $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                    if ($role && ! $role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        });

        $this->clearCaches();
    }

    /** Removes the two menus and their permissions. */
    public function down(): void
    {
        DB::transaction(function () {
            foreach (self::MENUS as [, $route, , $permission]) {
                DB::table('menus')->where('route', $route)->delete();

                $id = DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->value('id');
                if ($id) {
                    DB::table('role_has_permissions')->where('permission_id', $id)->delete();
                    DB::table('model_has_permissions')->where('permission_id', $id)->delete();
                    DB::table('permissions')->where('id', $id)->delete();
                }
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
