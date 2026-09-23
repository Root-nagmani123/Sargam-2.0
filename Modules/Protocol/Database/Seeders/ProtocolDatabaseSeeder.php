<?php

namespace Modules\Protocol\Database\Seeders;

use Illuminate\Database\Seeder;

class ProtocolDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProtocolRolesSeeder::class,
        ]);
    }
}
