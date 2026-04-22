<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MctpTravelModeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $modes = ['Train', 'Air', 'Bus', 'Private Vehicle', 'Auto/Taxi', 'Other'];
        foreach ($modes as $m) {
            DB::table('mctp_travel_mode_masters')->updateOrInsert(
                ['travel_mode_name' => $m],
                [
                    'travel_mode_name' => $m,
                    'is_active'        => 1,
                    'updated_at'       => now(),
                    'created_at'       => now(),
                ]
            );
        }
    }
}
