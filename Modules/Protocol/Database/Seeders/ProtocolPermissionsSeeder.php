<?php

namespace Modules\Protocol\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ProtocolPermissionsSeeder extends Seeder
{
    /**
     * Permission rows the module needs that are not created elsewhere.
     * Replaces the old GET /protocol/assign-permission route. Safe to run multiple times (firstOrCreate).
     */
    public function run(): void
    {
        $permissions = [
            'protocol.recommended_booking',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
