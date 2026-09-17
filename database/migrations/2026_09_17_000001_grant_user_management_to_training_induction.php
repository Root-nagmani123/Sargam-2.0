<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * PR #311 review condition 1 (finding F-017) — restore User Management for Training-Induction.
 *
 * PR #311 put `menu.permission:users` on every `admin.users.*` route and
 * `menu.permission:roles` on the Roles screen. Before that, `admin/users` carried
 * only `web,auth`. The gate is correct, but it narrowed the module to the current
 * holders of those permissions — which is Super Admin alone — while
 * `resources/views/components/menu/setup_activities.blade.php` still advertises
 * "User Permissions" and "Roles" to the Training-Induction role. Measured at head
 * 473caf241: 10 accounts hold that role and every one of them received HTTP 403 on
 * both screens.
 *
 * This migration takes the "grant the permission" branch of that condition: the role
 * keeps the access it had before the gate.
 *
 * ---------------------------------------------------------------------------------
 * SECURITY NOTE — read before extending this to another role.
 *
 * Granting `roles` is not merely read access to the Roles screen. It admits the
 * holder to POST `assign.roles.permissions` (routes/web.php:157), whose handler
 * `RoleController::assignPermission()` firstOrCreate()s ANY permission name posted
 * to it and grants it to ANY role by id. A holder of `roles` can therefore grant
 * itself any permission in the system. This migration hands that capability to 10
 * accounts, deliberately and on instruction.
 *
 * To narrow it later without reverting the User Management fix, remove `roles` from
 * PERMISSIONS below and re-run, or run `down()` and re-apply with `users` only.
 * ---------------------------------------------------------------------------------
 *
 * Written with the Spatie models rather than the query builder on purpose. Spatie
 * caches the permission map — including the role/permission pivot — for 24 hours
 * (config/permission.php:179). A raw INSERT into `role_has_permissions` leaves that
 * cache untouched, so the grant stays invisible until the cache expires. The model
 * methods flush it; the explicit flush below covers the no-op paths too.
 */
return new class extends Migration
{
    private const ROLE = 'Training-Induction';

    /** Screen permission => the route group it unlocks, for the log line. */
    private const PERMISSIONS = [
        'users' => 'admin.users.* — the User Management screen',
        'roles' => 'admin.roles.* — the Roles screen (see the security note above)',
    ];

    public function up(): void
    {
        $role = $this->role();

        if (! $role) {
            // Guarded rather than fatal: the role set differs between environments.
            // The PR #311 review found that three of the five roles named in
            // setup_activities.blade.php do not exist in this database at all.
            echo "  skipped: role '" . self::ROLE . "' does not exist here\n";

            return;
        }

        foreach (array_keys(self::PERMISSIONS) as $name) {
            $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();

            if (! $permission) {
                // Deliberately NOT created here. Every permission in this application is
                // derived from a `menus` row; inventing one would produce a permission no
                // screen can ever grant. If it is missing, the menu row is the bug.
                echo "  skipped: permission '{$name}' does not exist — check its menus row\n";

                continue;
            }

            if ($role->hasPermissionTo($permission)) {
                echo "  already held: {$name}\n";

                continue;
            }

            $role->givePermissionTo($permission);
            echo "  granted: {$name}\n";
        }

        $this->flush();
    }

    public function down(): void
    {
        $role = $this->role();

        if (! $role) {
            return;
        }

        // A faithful reverse: verified at head 473caf241 that Training-Induction held
        // NEITHER permission before this migration — the only role holding `users` was
        // Super Admin — so revoking both restores the prior state exactly.
        foreach (array_keys(self::PERMISSIONS) as $name) {
            $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();

            if ($permission && $role->hasPermissionTo($permission)) {
                $role->revokePermissionTo($permission);
                echo "  revoked: {$name}\n";
            }
        }

        $this->flush();
    }

    private function role(): ?Role
    {
        return Role::where('name', self::ROLE)->where('guard_name', 'web')->first();
    }

    private function flush(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
