<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nature Leave Master — the admin page, plus the first natures under "Leave".
 *
 * leave_nature_master was seed-only until now, with two buckets: PT_EXEMPTION
 * and STATIONED_LEAVE, one per officer-trainee form. "Apply Leave on Behalf of
 * OT" needs its own list, so this adds a LEAVE bucket and the CRUD page that
 * maintains all three.
 *
 * The seeded natures are a starting set — the page exists precisely so the
 * Training Section can add their own without a migration.
 */
return new class extends Migration
{
    private const PERMISSION = 'master_leave_nature_master';

    private const ROUTE = 'master/leave-nature-master';

    private const NATURES = [
        ['Casual Leave', 1],
        ['Earned Leave', 2],
        ['Medical Leave', 3],
        ['Personal', 4],
        ['Other', 5],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::NATURES as [$name, $order]) {
            $exists = DB::table('leave_nature_master')
                ->where('leave_type', 'LEAVE')
                ->where('nature_name', $name)
                ->exists();

            if (! $exists) {
                DB::table('leave_nature_master')->insert([
                    'leave_type' => 'LEAVE',
                    'nature_name' => $name,
                    'display_order' => $order,
                    'active_inactive' => 1,
                    'created_date' => $now,
                    'modified_date' => $now,
                ]);
            }
        }

        // A menu row alone does not make the page reachable for a non-admin:
        // the sidebar hides any row whose permission_name the user does not
        // hold, and a NULL permission_name counts as hidden. So the permission
        // is created and granted here too, to whoever already holds the sibling
        // Medical Case Master.
        $permId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        if (! $permId) {
            $permId = DB::table('permissions')->insertGetId([
                'name' => self::PERMISSION,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $siblingId = DB::table('permissions')->where('name', 'master_medical_case_master')->value('id');
        if ($siblingId) {
            $roleIds = DB::table('role_has_permissions')->where('permission_id', $siblingId)->pluck('role_id');
            foreach ($roleIds as $roleId) {
                $attached = DB::table('role_has_permissions')
                    ->where('permission_id', $permId)->where('role_id', $roleId)->exists();

                if (! $attached) {
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

        // Sits beside Medical Case Master, under the same parent.
        $sibling = DB::table('menus')
            ->where('route', 'master/medical-case-master')
            ->first(['category_id', 'group_id', 'parent_id']);

        if (! $sibling) {
            return;
        }

        $maxOrder = (int) DB::table('menus')->where('parent_id', $sibling->parent_id)->max('order');

        DB::table('menus')->insert([
            'category_id' => $sibling->category_id,
            'group_id' => $sibling->group_id,
            'parent_id' => $sibling->parent_id,
            'name' => 'Nature Leave Master',
            'route' => self::ROUTE,
            'icon' => 'event_note',
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

        // Only the seeded rows, and only where nothing has been filed against
        // them — a leave application pointing at a nature must not be orphaned.
        foreach (self::NATURES as [$name, $order]) {
            DB::table('leave_nature_master')
                ->where('leave_type', 'LEAVE')
                ->where('nature_name', $name)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('leave_application as la')
                        ->whereColumn('la.leave_nature_master_pk', 'leave_nature_master.pk');
                })
                ->delete();
        }
    }
};
