<?php

use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Two COE roles: the existing COE role becomes "COE-Incharge" (the main COE),
 * keeping its id, so its users and permissions stay with it; a new, empty "COE"
 * role is the COE section, given screens and users through Roles & Permissions.
 * The code that checked the COE role (marksheet COE signature, Examination Drive
 * (COE), question paper bells) now checks COE-Incharge. Idempotent.
 */
return new class extends Migration
{
    private const INCHARGE = 'COE-Incharge';

    private const SECTION = 'COE';

    private const GUARD = 'web';

    public function up(): void
    {
        $now = now();

        if (! $this->roleId(self::INCHARGE)) {
            $coeId = $this->roleId(self::SECTION);
            if ($coeId) {
                DB::table('roles')->where('id', $coeId)->update(['name' => self::INCHARGE, 'updated_at' => $now]);
            } else {
                DB::table('roles')->insert(['name' => self::INCHARGE, 'guard_name' => self::GUARD, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if (! $this->roleId(self::SECTION)) {
            DB::table('roles')->insert(['name' => self::SECTION, 'guard_name' => self::GUARD, 'created_at' => $now, 'updated_at' => $now]);
        }

        $this->clearCaches();
    }

    /** Drops the COE section role (and who held it), and names COE-Incharge COE again. */
    public function down(): void
    {
        $sectionId = $this->roleId(self::SECTION);
        if ($sectionId && $this->roleId(self::INCHARGE)) {
            DB::table('model_has_roles')->where('role_id', $sectionId)->delete();
            DB::table('role_has_permissions')->where('role_id', $sectionId)->delete();
            DB::table('roles')->where('id', $sectionId)->delete();
        }

        DB::table('roles')->where('name', self::INCHARGE)->where('guard_name', self::GUARD)
            ->update(['name' => self::SECTION, 'updated_at' => now()]);

        $this->clearCaches();
    }

    private function roleId(string $name): ?int
    {
        $id = DB::table('roles')->where('name', $name)->where('guard_name', self::GUARD)->value('id');

        return $id ? (int) $id : null;
    }

    private function clearCaches(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }
};
