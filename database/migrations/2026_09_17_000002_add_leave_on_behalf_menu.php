<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSION = 'apply_leave_on_behalf_of_ot';

    private const ROUTE = 'admin/leave-on-behalf';

    /**
     * Sidebar entry for "Apply Leave on Behalf of OT" — a third child of
     * Leave Managment, alongside PT Exemption Master and Stationed Leave Master.
     *
     * A menu row alone is not enough to make the page reachable for a non-admin:
     * SidebarController::menuVisibleToUser() hides any row whose permission_name
     * the user does not hold, and a NULL permission_name is treated as hidden.
     * So the permission is created and granted here as well, to the same roles
     * that already hold the sibling Stationed Leave Master.
     */
    public function up(): void
    {
        $now = now();

        $permId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        if (! $permId) {
            $permId = DB::table('permissions')->insertGetId([
                'name' => self::PERMISSION,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $siblingId = DB::table('permissions')->where('name', 'stationed_leave_master')->value('id');
        if ($siblingId) {
            $roleIds = DB::table('role_has_permissions')->where('permission_id', $siblingId)->pluck('role_id');
            foreach ($roleIds as $roleId) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permId)
                    ->where('role_id', $roleId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }

        if (DB::table('menus')->where('route', self::ROUTE)->exists()) {
            return;
        }

        $parent = DB::table('menus')
            ->where('route', 'leave-managment')
            ->first(['id', 'group_id', 'category_id']);

        if (! $parent) {
            return;
        }

        $maxOrder = (int) DB::table('menus')->where('parent_id', $parent->id)->max('order');

        DB::table('menus')->insert([
            'category_id' => $parent->category_id,
            'group_id' => $parent->group_id,
            'parent_id' => $parent->id,
            'name' => 'Apply Leave on Behalf of OT',
            'route' => self::ROUTE,
            'icon' => 'event_available',
            'permission_name' => self::PERMISSION,
            'order' => $maxOrder + 1,
            'is_active' => 1,
            'target' => '0',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('route', self::ROUTE)->delete();

        $permId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        if ($permId) {
            DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};
