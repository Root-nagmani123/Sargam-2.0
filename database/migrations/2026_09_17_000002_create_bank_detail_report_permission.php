<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Repairs a live outage: the FC Bank Details report is gated on a permission that
 * was never created, so it returns 403 to every account including Super Admin.
 *
 * Five routes carry `can:bank_detail_report`:
 *
 *   admin/reports/bank-report
 *   admin/reports/bank-report/export-excel
 *   admin/reports/bank-report/export-pdf
 *   admin/reports/bank-report/export-documents
 *   admin/reports/bank-report/file
 *
 * This application registers no Gate::before, so `can:` has no Super Admin bypass:
 * a gate naming a permission with no row denies EVERYONE. Measured against the
 * review database at 700c44c9f — `permissions` has no `bank_detail_report` row,
 * while its three sibling reports do (vision_statement 273,
 * special_assistant_report 274, pre_medical_history 275), each held by Super Admin
 * and nobody else. Meanwhile `menus` row 260 "Fc Bank Details" is is_active=1 with
 * deleted_at NULL, so the sidebar advertises a screen that cannot be opened.
 *
 * That is the PR #306 failure mode this repository has already paid for once, and
 * it is why tests/Feature/FcStepReportGuardsTest has been red on main: both
 * `test_the_gated_permissions_exist_so_the_gate_can_ever_pass` and the Super Admin
 * half of `test_a_step_report_token_is_refused_by_the_unauthenticated_route` fail
 * for this one reason — the latter receives 403 from the absent gate before the
 * token-audience check it is actually asserting can return 404.
 *
 * The grant mirrors the siblings exactly: Super Admin only. Any wider audience is
 * a product decision and belongs on the Roles screen, which can grant this
 * permission normally once the row exists.
 *
 * Spatie caches the permission map for 24 hours (config/permission.php), so this
 * uses the models rather than the query builder and flushes the cache explicitly —
 * a raw INSERT would leave the repair invisible until the cache expired.
 */
return new class extends Migration
{
    private const PERMISSION = 'bank_detail_report';

    private const ROLE = 'Super Admin';

    public function up(): void
    {
        $permission = Permission::where('name', self::PERMISSION)->where('guard_name', 'web')->first();

        if (! $permission) {
            $permission = Permission::create(['name' => self::PERMISSION, 'guard_name' => 'web']);
            echo '  created permission: '.self::PERMISSION."\n";
        } else {
            echo '  already present: '.self::PERMISSION."\n";
        }

        $role = Role::where('name', self::ROLE)->where('guard_name', 'web')->first();

        if (! $role) {
            // Guarded rather than fatal: the role set differs between environments.
            // The permission row alone already lifts the outage for anyone the Roles
            // screen grants it to.
            echo '  skipped grant: role \''.self::ROLE."' does not exist here\n";
            $this->flush();

            return;
        }

        if ($role->hasPermissionTo($permission)) {
            echo '  already held by '.self::ROLE."\n";
        } else {
            $role->givePermissionTo($permission);
            echo '  granted to '.self::ROLE."\n";
        }

        $this->flush();
    }

    public function down(): void
    {
        // Faithful reverse: the row did not exist before this migration, so removing
        // it restores the prior state - including, deliberately, the outage. Spatie
        // detaches the pivot rows with the permission.
        $permission = Permission::where('name', self::PERMISSION)->where('guard_name', 'web')->first();

        if ($permission) {
            $permission->delete();
            echo '  removed permission: '.self::PERMISSION."\n";
        }

        $this->flush();
    }

    private function flush(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
