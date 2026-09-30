<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sidebar entries for the COE screens (routes under admin/coe/*):
 *
 *   Setup  ›  COE
 *     ├─ Question Paper Management   (container)
 *     │    ├─ Question Paper                     admin/coe/question-paper
 *     │    ├─ My Question Paper                  admin/coe/faculty-question-paper
 *     │    └─ My Question Paper (Rajbhasha)      admin/coe/my-question-paper
 *     ├─ Laptop Management           (container)
 *     │    └─ OT Laptop Status                   admin/coe/ot-laptop-status
 *     └─ Master Data                 (container)
 *          ├─ Examination Type Master            admin/coe/examination-type
 *          ├─ Examination Term Master            admin/coe/examination-term
 *          ├─ Building Master                    admin/coe/building
 *          ├─ Floor Master                       admin/coe/floor
 *          └─ Room Master                        admin/coe/room
 *
 * Visibility: Super Admin sees every menu (isSidebarPrivilegedUser). Anyone else
 * needs the menu's permission, and NO role is granted one here — grant them per
 * role on the Roles screen (Setup › Role & Permission), which lists these menus.
 *
 * Permission names are explicit `coe_*` slugs rather than slug(name): the two
 * "My Question Paper" screens would otherwise derive the same name, and
 * "building_master" / "room_master" style names risk colliding with the hostel
 * and estate masters. Permissions are created through the Spatie model so its
 * cache is flushed (a raw insert leaves the Roles screen 500ing until reset).
 *
 * Idempotent: every row is looked up before it is inserted, so re-running is a
 * no-op, and down() removes only what up() describes.
 */
return new class extends Migration
{
    private const CATEGORY_SLUG = 'setup';

    private const GROUP_NAME = 'COE';

    private const GROUP_ICON = 'fact_check';

    /** container => [icon, permission, children [name, route, icon, permission]] */
    private function tree(): array
    {
        return [
            'Question Paper Management' => ['description', 'coe_question_paper_management', [
                ['Question Paper', 'admin/coe/question-paper', 'quiz', 'coe_question_paper'],
                ['My Question Paper', 'admin/coe/faculty-question-paper', 'edit_document', 'coe_my_question_paper_faculty'],
                ['My Question Paper (Rajbhasha)', 'admin/coe/my-question-paper', 'translate', 'coe_my_question_paper_rajbhasha'],
            ]],
            'Laptop Management' => ['laptop_mac', 'coe_laptop_management', [
                ['OT Laptop Status', 'admin/coe/ot-laptop-status', 'laptop_chromebook', 'coe_ot_laptop_status'],
            ]],
            'Master Data' => ['dataset', 'coe_master_data', [
                ['Examination Type Master', 'admin/coe/examination-type', 'category', 'coe_examination_type_master'],
                ['Examination Term Master', 'admin/coe/examination-term', 'date_range', 'coe_examination_term_master'],
                ['Building Master', 'admin/coe/building', 'apartment', 'coe_building_master'],
                ['Floor Master', 'admin/coe/floor', 'stacks', 'coe_floor_master'],
                ['Room Master', 'admin/coe/room', 'meeting_room', 'coe_room_master'],
            ]],
        ];
    }

    public function up(): void
    {
        $now = now();

        $categoryId = DB::table('sidebar_categories')
            ->where('slug', self::CATEGORY_SLUG)->whereNull('deleted_at')->value('id');
        if (! $categoryId) {
            throw new RuntimeException('Sidebar category "'.self::CATEGORY_SLUG.'" not found — cannot place the COE menus.');
        }

        DB::transaction(function () use ($categoryId, $now) {
            // 1) The COE group, last in Setup.
            $groupId = DB::table('menu_groups')
                ->where('category_id', $categoryId)->where('name', self::GROUP_NAME)->whereNull('deleted_at')
                ->value('id');
            if (! $groupId) {
                $groupId = DB::table('menu_groups')->insertGetId([
                    'category_id' => $categoryId,
                    'name' => self::GROUP_NAME,
                    'icon' => self::GROUP_ICON,
                    'order' => (int) DB::table('menu_groups')->where('category_id', $categoryId)->max('order') + 1,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 2) Containers, then their children.
            $containerOrder = 0;
            foreach ($this->tree() as $containerName => [$icon, $permission, $children]) {
                $containerOrder++;
                $this->ensurePermission($permission);

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
                        'order' => $containerOrder,
                        'is_active' => 1,
                        'is_container' => 1,          // holds sub-menus only (no Url/Attachment)
                        'target' => '0',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                foreach ($children as $i => [$name, $route, $childIcon, $childPermission]) {
                    $this->ensurePermission($childPermission);

                    if (DB::table('menus')->where('route', $route)->whereNull('deleted_at')->exists()) {
                        continue;
                    }

                    DB::table('menus')->insert([
                        'category_id' => $categoryId,
                        'group_id' => $groupId,
                        'parent_id' => $containerId,
                        'name' => $name,
                        'route' => $route,              // stored as a URL path, like every other menu
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

            // Drop the group only if nothing else was added to it since.
            if ($groupId && ! DB::table('menus')->where('group_id', $groupId)->exists()) {
                DB::table('menu_groups')->where('id', $groupId)->delete();
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

    private function ensurePermission(string $name): void
    {
        // Through the model, so Spatie's permission cache is flushed.
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    private function clearCaches(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }
};
