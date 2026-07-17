<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1) Spatie permission — mirror the sibling "Exemption Category" master.
        $permName = 'master_medical_case_master';
        $permId = DB::table('permissions')->where('name', $permName)->value('id');
        if (! $permId) {
            $permId = DB::table('permissions')->insertGetId([
                'name'       => $permName,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2) Grant to the same roles that already hold the sibling master
        //    (master_exemption_category_master), so existing access carries over.
        $siblingId = DB::table('permissions')->where('name', 'master_exemption_category_master')->value('id');
        if ($siblingId) {
            $roleIds = DB::table('role_has_permissions')->where('permission_id', $siblingId)->pluck('role_id');
            foreach ($roleIds as $roleId) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permId)->where('role_id', $roleId)->exists();
                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permId,
                        'role_id'       => $roleId,
                    ]);
                }
            }
        }

        // 3) Sidebar menu row — child of "Exemption" (menu 92), alongside the two
        //    existing exemption masters (group 3 / category 2, medical_services icon).
        $route = 'master/medical-case-master';
        if (! DB::table('menus')->where('route', $route)->exists()) {
            $parent = DB::table('menus')->where('route', 'exemption')->first(['id', 'group_id', 'category_id']);

            $categoryId = $parent->category_id ?? 2;
            $groupId    = $parent->group_id ?? 3;
            $parentId   = $parent->id ?? 92;

            $maxOrder = (int) DB::table('menus')->where('parent_id', $parentId)->max('order');

            DB::table('menus')->insert([
                'category_id'     => $categoryId,
                'group_id'        => $groupId,
                'parent_id'       => $parentId,
                'name'            => 'Medical Case Master',
                'route'           => $route,
                'icon'            => 'medical_services',
                'permission_name' => $permName,
                'order'           => $maxOrder + 1,
                'is_active'       => 1,
                'target'          => '0',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menus')->where('route', 'master/medical-case-master')->delete();

        $permId = DB::table('permissions')->where('name', 'master_medical_case_master')->value('id');
        if ($permId) {
            DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};
