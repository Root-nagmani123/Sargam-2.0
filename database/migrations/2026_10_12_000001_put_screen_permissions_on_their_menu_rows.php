<?php

use App\Http\Controllers\Admin\MedicalExceptionFacultyViewController;
use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Make two screen permissions grantable (PR #335 F-022, and Duty Type the same way).
 *
 * The Medical Exception admin view (medical_exception_faculty) and the Duty Type
 * routes (duty_type) are gated on a permission, but the role-assignment screen
 * only offers names that sit on an active menus row (RoleService::permissionMatrix),
 * and neither screen's row carried one: "Medical Exception Faculty" had no
 * permission_name and was inactive, "Duty Type" had no permission_name. So no role
 * could be granted either, and both screens were Super-Admin-only.
 *
 * This puts each permission on its screen's row, found by route because menu ids
 * are environment data. It grants nothing: which roles hold them is decided on the
 * role screen. A row that already carries another permission is left alone.
 */
return new class extends Migration
{
    /** menus.route => [permission, the row's is_active before this migration]. */
    private const SCREENS = [
        'medical-exception-faculty-view' => [MedicalExceptionFacultyViewController::ADMIN_PERMISSION, 0],
        'master/mdo' => ['duty_type', 1],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('menus')) {
            return;
        }

        foreach (self::SCREENS as $route => [$permission]) {
            if (! DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->exists()) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('menus')
                ->where('route', $route)
                ->where(fn ($q) => $q->whereNull('permission_name')->orWhere('permission_name', '')->orWhere('permission_name', $permission))
                ->update(['permission_name' => $permission, 'is_active' => 1, 'updated_at' => now()]);
        }

        $this->flushPermissionCache();
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        // The permissions rows predate this migration on the databases read, so they stay.
        foreach (self::SCREENS as $route => [$permission, $wasActive]) {
            DB::table('menus')
                ->where('route', $route)
                ->where('permission_name', $permission)
                ->update(['permission_name' => null, 'is_active' => $wasActive, 'updated_at' => now()]);
        }

        $this->flushPermissionCache();
    }

    /** The rows were written with the query builder, so neither Spatie's cache (PR #309 F-025) nor the sidebar's has been told. */
    private function flushPermissionCache(): void
    {
        // What MenuService::status() clears after a menu row changes.
        MenuService::clearStructureCache();

        try {
            SidebarNavResolver::clearCache();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $e) {
            // No cache to reach: `php artisan cache:clear` is the manual equivalent.
        }
    }
};
