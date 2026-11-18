<?php

namespace Modules\Protocol\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Protocol\Services\ProtocolDirectory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProtocolPermissionsSeeder extends Seeder
{
    /**
     * Every permission the module checks. Routes are gated with can:<name> and Spatie
     * denies everyone when the row is missing, so the deploy runs this once per
     * environment. Creates rows only, grants nothing; safe to run again (firstOrCreate).
     * Sidebar menus are added at /sidebar/menus, not here.
     */
    const PERMISSIONS = [
        // Route gates (routes/web.php)
        'protocol.my_requests',
        'protocol.protocol_requests',
        'protocol.recommended_booking',
        'protocol.driver_master',
        'protocol.vehicle_master',
        'protocol.permissions',
        // Request-type scope (ApprovalService)
        'protocol.all_booking',
        'protocol.guest_house_booking',
        'protocol.vehicle_booking',
        'protocol.ticket_booking',
    ];

    /**
     * Roles the module checks by name. Only "Recommend to" reads one: its list is the
     * holders of this role (ProtocolDirectory::approvers). Created empty; holders are
     * assigned per environment (PR #348 F-027).
     */
    const ROLES = [
        ProtocolDirectory::APPROVER_ROLE,
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
