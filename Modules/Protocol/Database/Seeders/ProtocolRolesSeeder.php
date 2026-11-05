<?php

namespace Modules\Protocol\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ProtocolRolesSeeder extends Seeder
{
    /**
     * Creates the three roles the module's routes are gated on.
     * Safe to run multiple times (firstOrCreate).
     */
    public function run(): void
    {
        foreach (config('protocol.roles') as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
