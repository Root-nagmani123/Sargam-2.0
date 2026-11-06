<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Removes Setup › COE › Examination Management › Result Calculation.
 *
 * The screen (a sample-data mock-up) is deleted along with its route, so its
 * sidebar link — made by 2026_09_30_120000 or by hand in Menu Management —
 * would only 404. Drops the menu row(s) and the coe_result_calculation
 * permission with any role/user grants of it.
 */
return new class extends Migration
{
    private const ROUTE = 'admin/coe/result-calculation';

    private const PERMISSION = 'coe_result_calculation';

    public function up(): void
    {
        DB::transaction(function () {
            DB::table('menus')->whereIn('route', [self::ROUTE, '/'.self::ROUTE])->delete();

            $id = DB::table('permissions')->where('name', self::PERMISSION)->where('guard_name', 'web')->value('id');
            if ($id) {
                DB::table('role_has_permissions')->where('permission_id', $id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $id)->delete();
                DB::table('permissions')->where('id', $id)->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }

    /** Nothing to restore: the screen this menu pointed at no longer exists. */
    public function down(): void
    {
    }
};
