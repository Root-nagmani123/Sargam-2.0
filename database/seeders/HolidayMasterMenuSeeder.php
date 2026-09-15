<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Registers the Holiday Master screen in the dynamic RBAC sidebar
 * (Setup → Time Table, next to Calendar Creation) and creates the
 * permission that grants non-Super-Admin roles access to it.
 *
 * Safe to run more than once.
 */
class HolidayMasterMenuSeeder extends Seeder
{
    private const PERMISSION = 'holiday_master';
    private const MENU_ROUTE = 'admin/holiday-master';

    public function run()
    {
        $now = Carbon::now();

        // 1. Spatie permission — the sidebar shows the menu to any role holding it.
        if (! DB::table('permissions')->where('name', self::PERMISSION)->where('guard_name', 'web')->exists()) {
            DB::table('permissions')->insert([
                'name' => self::PERMISSION,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // NOTE: the permission is created but deliberately granted to NO role here.
        // Super Admin sees the menu regardless (isSidebarPrivilegedUser bypass); any
        // other role has to be given `holiday_master` explicitly from the
        // Roles & Permissions screen. The neighbouring Time Table screens are held by
        // Training IST / Training MCTP Admin / Training-Induction, which is the likely
        // set — but who may edit holidays is a call for the site owner, not a seeder.

        // 2. Sidebar menu entry. `menus.route` stores a URL path, never a named route.
        $categoryId = DB::table('sidebar_categories')->where('name', 'Setup')->value('id');
        $groupId = DB::table('menu_groups')
            ->where('category_id', $categoryId)
            ->where('name', 'Time Table')
            ->value('id');

        if (! $categoryId || ! $groupId) {
            $this->command?->warn('Setup → Time Table menu group not found; Holiday Master menu skipped.');

            return;
        }

        $existing = DB::table('menus')->where('route', self::MENU_ROUTE)->first();

        $payload = [
            'category_id' => $categoryId,
            'group_id' => $groupId,
            'parent_id' => null,
            'name' => 'Holiday Master',
            'route' => self::MENU_ROUTE,
            'is_container' => 0,
            'icon' => 'event',
            'permission_name' => self::PERMISSION,
            'order' => $this->orderAfterCalendarCreation($groupId),
            'is_active' => 1,
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('menus')->where('id', $existing->id)->update($payload);
        } else {
            $payload['created_at'] = $now;
            DB::table('menus')->insert($payload);
        }

        $this->clearSidebarCaches();
    }

    /**
     * Menus are ordered by `order` within their group. Drop Holiday Master into the
     * free slot just below the calendar/timetable items instead of appending it to
     * the very bottom of the group, where it sits under Leave Management's children
     * and is easy to miss. Nothing else is renumbered.
     */
    private function orderAfterCalendarCreation($groupId): int
    {
        $anchor = (int) DB::table('menus')
            ->where('group_id', $groupId)
            ->where('route', 'calendar')
            ->value('order');

        if ($anchor <= 0) {
            return (int) DB::table('menus')->max('order') + 1;
        }

        $taken = DB::table('menus')
            ->where('group_id', $groupId)
            ->where('route', '!=', self::MENU_ROUTE)
            ->pluck('order')
            ->map(function ($o) {
                return (int) $o;
            })
            ->all();

        // First free slot after the anchor, so no existing row has to move.
        for ($order = $anchor + 1; $order < $anchor + 50; $order++) {
            if (! in_array($order, $taken, true)) {
                return $order;
            }
        }

        return (int) DB::table('menus')->max('order') + 1;
    }

    /**
     * Two independent caches hide a freshly added menu: the sidebar STRUCTURE
     * (MenuService, 600s) and the route index used for active-state resolution
     * (SidebarNavResolver, 300s). Both must go or the menu only appears minutes later.
     */
    private function clearSidebarCaches(): void
    {
        if (method_exists(\App\Services\SidebarMenu\MenuService::class, 'clearStructureCache')) {
            \App\Services\SidebarMenu\MenuService::clearStructureCache();
        }

        if (method_exists(\App\Services\SidebarMenu\SidebarNavResolver::class, 'clearCache')) {
            \App\Services\SidebarMenu\SidebarNavResolver::clearCache();
        }
    }
}
