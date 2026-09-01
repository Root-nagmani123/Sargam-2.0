<?php

use App\Models\SidebarMenu\Menu;
use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives Officer Trainees their Peer Evaluation link back.
 *
 * The menu row has existed since the module shipped (route peer/my-groups, in
 * Setup > Training Setup alongside the OT's other self-service links), but it was
 * unreachable for two independent reasons:
 *
 *   is_active = 0        hidden from everyone, admins included
 *   permission_name NULL SidebarController::menuVisibleToUser() treats an empty
 *                        permission as "not public" - a non-admin can never match
 *                        it - so activating the row alone would have shown the
 *                        link to admins and still not to OTs
 *
 * So this fixes both, then grants the new permission to the Officer Trainee role.
 *
 * Renamed to "My Peer Evaluation" rather than left as "Peer Evaluation" because
 * MenuService derives permission_name from the NAME (Str::slug(name, '_')) and
 * `peer_evaluation` is already taken by the admin parent menu, which MenuRequest
 * requires to be unique. "My ..." also matches how every other OT-facing row in
 * this group is labelled (My Time Table, My Feedback, my_attendance, my_leave),
 * and keeps the OT link distinguishable from the admin one for a Super Admin,
 * who sees both.
 *
 * Idempotent; matched on route, never on name.
 */
return new class extends Migration
{
    private string $route = 'peer/my-groups';
    private string $permissionName = 'my_peer_evaluation';
    private string $menuName = 'My Peer Evaluation';
    private string $roleName = 'Officer Trainee';

    public function up(): void
    {
        $menu = Menu::where('route', $this->route)->first();

        if (! $menu) {
            // No such row on this environment - nothing to switch on.
            return;
        }

        $menu->name = $this->menuName;
        $menu->permission_name = $this->permissionName;
        $menu->is_active = 1;
        $menu->save();

        $permissionId = $this->permissionId();

        if ($permissionId) {
            $this->grantToRole($permissionId);
        }

        $this->flush();
    }

    public function down(): void
    {
        $menu = Menu::where('route', $this->route)->first();

        if ($menu) {
            $menu->name = 'Peer Evaluation';
            $menu->permission_name = null;
            $menu->is_active = 0;
            $menu->save();
        }

        $permissionId = DB::table('permissions')
            ->where('name', $this->permissionName)
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        $this->flush();
    }

    /**
     * The Spatie permission id, creating the row on first run.
     *
     * Written as exists-check + insert rather than updateOrInsert: one shared
     * payload would reset created_at on a pre-existing permission row.
     */
    private function permissionId(): ?int
    {
        if (! DB::getSchemaBuilder()->hasTable('permissions')) {
            return null;
        }

        $id = DB::table('permissions')
            ->where('name', $this->permissionName)
            ->where('guard_name', 'web')
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('permissions')->insertGetId([
            'name' => $this->permissionName,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function grantToRole(int $permissionId): void
    {
        if (! DB::getSchemaBuilder()->hasTable('role_has_permissions')) {
            return;
        }

        $roleId = DB::table('roles')
            ->where('name', $this->roleName)
            ->where('guard_name', 'web')
            ->value('id');

        if (! $roleId) {
            return;
        }

        $alreadyGranted = DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)
            ->where('role_id', $roleId)
            ->exists();

        if (! $alreadyGranted) {
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    /**
     * Both caches, not just one: SidebarNavResolver holds the id-keyed structure
     * maps and the route index, MenuService holds the rendered nav tree. A stale
     * either one keeps the link hidden until its TTL expires.
     */
    private function flush(): void
    {
        SidebarNavResolver::clearCache();
        MenuService::clearStructureCache();
    }
};
