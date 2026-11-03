<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sidebar menus and Spatie permissions for the Protocol module.
 *
 * Every protocol route is gated with can:protocol.*, and `can:` has no Super Admin
 * bypass: without a permissions row the route is 403 for everyone. The roles screen
 * only offers permissions that sit on a menus row, so each gated screen gets one,
 * carrying the exact name its route checks.
 *
 * Employee screens go under Home → Protocol, masters under Setup → Protocol
 * Management (same split as Estate / Mess). Rows are found by route, never by id.
 * Grants nothing: assign the permissions on the roles screen or on
 * protocol/permissions. Idempotent.
 */
return new class extends Migration
{
    /** category slug => [group name, group icon, [menu name, route, permission, icon]...] */
    private const GROUPS = [
        'home' => ['Protocol', 'handshake', [
            ['Dashboard', 'protocol', 'protocol.my_requests', 'dashboard'],
            ['New Request', 'protocol/new', 'protocol.my_requests', 'add_circle'],
            ['My Requests', 'protocol/my-requests', 'protocol.my_requests', 'list_alt'],
            ['All Requests', 'protocol/all-requests', 'protocol.protocol_requests', 'fact_check'],
            ['Approval Queue', 'protocol/approval/queue', 'protocol.protocol_requests', 'pending_actions'],
            ['Recommended Booking', 'protocol/recommended_booking', 'protocol.recommended_booking', 'recommend'],
            // The route itself is ungated (controller checks recommended_to_id); this only decides who sees the link.
            ['Manager Queue', 'protocol/manager/queue', 'protocol.recommended_booking', 'how_to_reg'],
        ]],
        'setup' => ['Protocol Management', 'handshake', [
            ['Driver Master', 'protocol/driver-master', 'protocol.driver_master', 'badge'],
            ['Vehicle Master', 'protocol/vehicle-master', 'protocol.vehicle_master', 'directions_car'],
            ['Vehicle Categories', 'protocol/vehicle-categories', 'protocol.vehicle_master', 'category'],
            ['Vehicle Types', 'protocol/vehicle-types', 'protocol.vehicle_master', 'commute'],
            ['Protocol Permissions', 'protocol/permissions', 'protocol.permissions', 'admin_panel_settings'],
        ]],
    ];

    /** Checked in code (ApprovalService, ProtocolGate) but not a screen; granted on protocol/permissions. */
    private const PERMISSION_ONLY = [
        'protocol.all_booking',
        'protocol.guest_house_booking',
        'protocol.vehicle_booking',
        'protocol.ticket_booking',
        'protocol.requests.view_all',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permissionNames() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::GROUPS as $slug => [$groupName, $groupIcon, $menus]) {
            $categoryId = DB::table('sidebar_categories')->where('slug', $slug)->whereNull('deleted_at')->value('id');
            if (! $categoryId) {
                continue;
            }

            $groupId = DB::table('menu_groups')
                ->where('category_id', $categoryId)->where('name', $groupName)->whereNull('deleted_at')
                ->value('id');

            if (! $groupId) {
                $groupId = DB::table('menu_groups')->insertGetId([
                    'category_id' => $categoryId,
                    'name' => $groupName,
                    'icon' => $groupIcon,
                    'order' => ((int) DB::table('menu_groups')->where('category_id', $categoryId)->max('order')) + 1,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($menus as $order => [$name, $route, $permission, $icon]) {
                if (DB::table('menus')->where('route', $route)->whereNull('deleted_at')->exists()) {
                    continue;
                }

                DB::table('menus')->insert([
                    'category_id' => $categoryId,
                    'group_id' => $groupId,
                    'parent_id' => null,
                    'name' => $name,
                    'route' => $route,
                    'icon' => $icon,
                    'permission_name' => $permission,
                    'order' => $order + 1,
                    'is_active' => 1,
                    'target' => '0',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->flushCaches();
    }

    public function down(): void
    {
        foreach (self::GROUPS as $slug => [$groupName, , $menus]) {
            DB::table('menus')->whereIn('route', array_column($menus, 1))->delete();

            $categoryId = DB::table('sidebar_categories')->where('slug', $slug)->value('id');
            $groupId = $categoryId
                ? DB::table('menu_groups')->where('category_id', $categoryId)->where('name', $groupName)->value('id')
                : null;

            if ($groupId && ! DB::table('menus')->where('group_id', $groupId)->exists()) {
                DB::table('menu_groups')->where('id', $groupId)->delete();
            }
        }

        // Keep any permission a role or user has been given since, or rollback silently revokes access.
        foreach ($this->permissionNames() as $name) {
            $id = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            $granted = $id && (
                DB::table('role_has_permissions')->where('permission_id', $id)->exists()
                || DB::table('model_has_permissions')->where('permission_id', $id)->exists()
            );

            if ($id && ! $granted) {
                DB::table('permissions')->where('id', $id)->delete();
            }
        }

        $this->flushCaches();
    }

    private function permissionNames(): array
    {
        $fromMenus = [];
        foreach (self::GROUPS as [, , $menus]) {
            $fromMenus = array_merge($fromMenus, array_column($menus, 2));
        }

        return array_values(array_unique(array_merge($fromMenus, self::PERMISSION_ONLY)));
    }

    /** Rows were written with the query builder, so neither the sidebar nor Spatie has been told. */
    private function flushCaches(): void
    {
        MenuService::clearStructureCache();

        try {
            SidebarNavResolver::clearCache();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $e) {
            // No cache to reach: `php artisan cache:clear` is the manual equivalent.
        }
    }
};
