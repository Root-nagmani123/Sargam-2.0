<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * "Athletics Meet" group under Setup, laid out as in the Sargam 2.0 Figma:
 * Athletics Master (Event / Venue / House), Athletics Drive, Nominations,
 * Result Entry, Reports (Overall Champion) — plus My Nominations for the OT.
 *
 * Every leaf carries its own permission so roles can be granted screen by
 * screen; parents carry none and show whenever a child is visible. The OT
 * entry's permission is granted to the Officer Trainee role here. Idempotent.
 */
return new class extends Migration
{
    private const GROUP = 'Athletics Meet';

    private const OT_PERMISSION = 'athletics_my_nominations';

    /** [name, route, icon, permission, children] */
    private function tree(): array
    {
        return [
            ['Athletics Master', null, 'database', null, [
                ['Event Master', 'admin/athletics/events', 'event', 'athletics_event_master'],
                ['Venue Master', 'admin/athletics/venues', 'stadium', 'athletics_venue_master'],
                ['House Master', 'admin/athletics/houses', 'home_work', 'athletics_house_master'],
            ]],
            ['Athletics Drive', 'admin/athletics/drives', 'sports_score', 'athletics_drive', []],
            ['Nominations', 'admin/athletics/nominations', 'approval_delegation', 'athletics_nominations', []],
            ['Result Entry', 'admin/athletics/results', 'lab_profile', 'athletics_result_entry', []],
            ['Reports', null, 'article', null, [
                ['Overall Champion', 'admin/athletics/reports/overall-champion', 'emoji_events', 'athletics_overall_champion'],
            ]],
            ['My Nominations', 'athletics/my-nominations', 'how_to_reg', self::OT_PERMISSION, []],
        ];
    }

    public function up(): void
    {
        $category = DB::table('sidebar_categories')->where('slug', 'setup')->whereNull('deleted_at')->first();
        if (! $category) {
            return;
        }

        $now = now();
        $groupId = DB::table('menu_groups')
            ->where('category_id', $category->id)->where('name', self::GROUP)->whereNull('deleted_at')
            ->value('id');

        if (! $groupId) {
            $groupId = DB::table('menu_groups')->insertGetId([
                'category_id' => $category->id,
                'name' => self::GROUP,
                'icon' => 'sports_score',
                'order' => ((int) DB::table('menu_groups')->where('category_id', $category->id)->max('order')) + 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $order = 0;
        foreach ($this->tree() as [$name, $route, $icon, $permission, $children]) {
            $parentId = $this->menu($category->id, $groupId, null, $name, $route, $icon, $permission, ++$order);
            foreach ($children as $i => [$cName, $cRoute, $cIcon, $cPermission]) {
                $this->menu($category->id, $groupId, $parentId, $cName, $cRoute, $cIcon, $cPermission, $i + 1);
            }
        }

        // The OT self-nomination screen is for every officer trainee.
        $otRoleId = DB::table('roles')->where('name', 'Officer Trainee')->value('id');
        $permId = DB::table('permissions')->where('name', self::OT_PERMISSION)->value('id');
        if ($otRoleId && $permId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permId, 'role_id' => $otRoleId]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }

    private function menu(int $categoryId, int $groupId, ?int $parentId, string $name, ?string $route, string $icon, ?string $permission, int $order): int
    {
        if ($permission && ! DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->exists()) {
            DB::table('permissions')->insert([
                'name' => $permission, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Leaves are found by route, parents (no route) by name within the group.
        $existing = DB::table('menus')
            ->where('group_id', $groupId)
            ->when($route, fn ($q) => $q->where('route', $route), fn ($q) => $q->whereNull('route')->where('name', $name))
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('menus')->insertGetId([
            'category_id' => $categoryId,
            'group_id' => $groupId,
            'parent_id' => $parentId,
            'name' => $name,
            'route' => $route,
            'icon' => $icon,
            'permission_name' => $permission,
            'order' => $order,
            'is_active' => 1,
            'target' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Forward-fix only, like the other menu migrations (PR #334 F-027): roles may
     * have been granted these permissions since, and deleting them would silently
     * revoke access. Remove the group from Setup → Menus by hand if needed.
     */
    public function down(): void
    {
        //
    }
};
