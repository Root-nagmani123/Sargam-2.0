<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HighestStreamMasterSeeder extends Seeder
{
    public function run(): void
    {
        $streams = ['Science','Arts / Humanities','Commerce','Engineering','Medical','Law','Management','Agriculture','Others'];
        foreach ($streams as $s) {
            DB::table('highest_stream_masters')->updateOrInsert(['stream_name'=>$s],['stream_name'=>$s]);
        }
    }
}
