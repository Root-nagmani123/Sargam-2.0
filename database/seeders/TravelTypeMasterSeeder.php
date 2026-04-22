<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TravelTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Onward (To Mussoorie)', 'Return (From Mussoorie)', 'Mid-Course Travel', 'Other'];
        foreach ($types as $t) {
            DB::table('travel_type_masters')->updateOrInsert(
                ['travel_type_name' => $t],
                [
                    'travel_type_name' => $t,
                    'is_active'        => 1,
                    'updated_at'       => now(),
                    'created_at'       => now(),
                ]
            );
        }
    }
}
