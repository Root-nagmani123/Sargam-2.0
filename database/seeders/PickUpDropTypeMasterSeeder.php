<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PickUpDropTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Railway Station',
            'Airport',
            'Bus Stand',
            'Hotel / Guest House',
            'Other',
        ];
        foreach ($types as $name) {
            DB::table('pick_up_drop_type_masters')->updateOrInsert(
                ['type_name' => $name],
                [
                    'type_name'  => $name,
                    'is_active'  => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
