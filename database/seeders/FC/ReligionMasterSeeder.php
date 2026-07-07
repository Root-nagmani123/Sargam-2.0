<?php

namespace Database\Seeders\FC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ReligionMasterSeeder extends Seeder
{
    public function run(): void
    {
        $religions = ['Hindu','Muslim','Christian','Sikh','Buddhist','Jain','Parsi','Other'];
        foreach ($religions as $r) {
            DB::table('religion_masters')->updateOrInsert(['religion_name'=>$r],['religion_name'=>$r]);
        }
    }
}
