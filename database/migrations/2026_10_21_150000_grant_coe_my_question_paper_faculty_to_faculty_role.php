<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Shows COE › Question Paper Management › "My Question Paper"
 * (admin/coe/faculty-question-paper) to the Faculty role, and lets them through
 * its routes (menu.permission:coe_my_question_paper_faculty).
 *
 * The menu row already exists (2026_09_30_120000_add_coe_sidebar_menus.php); no
 * role held its permission, so faculty never saw it. The Question Paper
 * Management container needs no grant of its own: buildMenus() shows a
 * container when any child is visible. Idempotent.
 */
return new class extends Migration
{
    private const ROLE = 'Faculty';

    private const PERMISSION = 'coe_my_question_paper_faculty';

    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->where('guard_name', 'web')->value('id');
        if (! $roleId) {
            throw new RuntimeException('Role "'.self::ROLE.'" not found.');
        }

        $permissionId = Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web'])->id;

        DB::table('role_has_permissions')->insertOrIgnore([
            'permission_id' => $permissionId,
            'role_id' => $roleId,
        ]);

        $this->clearCaches();
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->where('guard_name', 'web')->value('id');
        $permissionId = DB::table('permissions')->where('name', self::PERMISSION)->where('guard_name', 'web')->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_has_permissions')
                ->where('role_id', $roleId)->where('permission_id', $permissionId)->delete();
        }

        $this->clearCaches();
    }

    private function clearCaches(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }
};
