<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class JobTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Government (Central)','Government (State)','PSU','Private Sector','Self-Employed','NGO / Non-Profit','Academic / Teaching','Other'];
        foreach ($types as $t) {
            DB::table('job_type_masters')->updateOrInsert(['job_type_name'=>$t],['job_type_name'=>$t]);
        }
    }
}
