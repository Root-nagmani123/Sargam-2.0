<?php

use App\Models\SidebarMenu\Menu;
use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds "Club/ Society Role Programme Mapping" alongside "Define Club/ Society" in the
 * Communications -> Club/ Society sidebar group, plus its Spatie permission so
 * the menu can be granted to non-admin roles (admins bypass the permission check
 * in MenuService::buildMenus()). Idempotent.
 */
return new class extends Migration
{
    private string $route          = 'master/club-society-role-programme-mapping';
    private string $permissionName = 'club_society_role_programme_mapping';
    private string $menuName       = 'Club/ Society Role Programme Mapping';
    private string $groupName      = 'Club/ Society';
    private string $categorySlug   = 'communications';

    public function up(): void
    {
        $category = DB::table('sidebar_categories')
            ->where('slug', $this->categorySlug)
            ->whereNull('deleted_at')
            ->first();

        if (! $category) {
            // No Communications category on this environment — nothing to attach to.
            return;
        }

        $group = DB::table('menu_groups')
            ->where('category_id', $category->id)
            ->where('name', $this->groupName)
            ->whereNull('deleted_at')
            ->first();

        if (! $group) {
            // The Club/ Society group is created by the Define Club/ Society
            // migration; create it here too so this one can stand alone.
            $groupId = DB::table('menu_groups')->insertGetId([
                'category_id' => $category->id,
                'name'        => $this->groupName,
                'icon'        => 'groups',
                'order'       => ((int) DB::table('menu_groups')->where('category_id', $category->id)->max('order')) + 1,
                'is_active'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } else {
            $groupId = $group->id;
        }

        Menu::firstOrCreate(
            ['route' => $this->route],
            [
                'category_id'     => $category->id,
                'group_id'        => $groupId,
                'parent_id'       => null,
                'name'            => $this->menuName,
                'icon'            => 'account_tree',
                'permission_name' => $this->permissionName,
                'order'           => ((int) Menu::where('category_id', $category->id)->max('order')) + 1,
                'is_active'       => 1,
                'target'          => '0',
            ]
        );

        // updateOrInsert would apply one payload to both branches and reset
        // created_at on a pre-existing permission, so created_at is insert-only.
        if (DB::getSchemaBuilder()->hasTable('permissions')) {
            $exists = DB::table('permissions')
                ->where('name', $this->permissionName)
                ->where('guard_name', 'web')
                ->exists();

            if ($exists) {
                DB::table('permissions')
                    ->where('name', $this->permissionName)
                    ->where('guard_name', 'web')
                    ->update(['updated_at' => now()]);
            } else {
                DB::table('permissions')->insert([
                    'name'       => $this->permissionName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }

    public function down(): void
    {
        Menu::where('route', $this->route)->delete();

        $category = DB::table('sidebar_categories')->where('slug', $this->categorySlug)->first();

        if ($category) {
            // Only drop the group if no menu is left in it (Define Club/ Society
            // normally still is, so this is a no-op in practice).
            $group = DB::table('menu_groups')
                ->where('category_id', $category->id)
                ->where('name', $this->groupName)
                ->first();

            if ($group && ! DB::table('menus')->where('group_id', $group->id)->exists()) {
                DB::table('menu_groups')->where('id', $group->id)->delete();
            }
        }

        if (DB::getSchemaBuilder()->hasTable('permissions')) {
            DB::table('permissions')
                ->where('name', $this->permissionName)
                ->where('guard_name', 'web')
                ->delete();
        }

        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }
};
