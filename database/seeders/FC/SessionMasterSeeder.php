<?php

namespace Database\Seeders\FC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SessionMasterSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = [
            ['session_name'=>'FC-2024 (100th FC)','session_code'=>'FC100','start_date'=>'2024-09-01','end_date'=>'2025-08-31','is_active'=>0],
            ['session_name'=>'FC-2025 (101st FC)','session_code'=>'FC101','start_date'=>'2025-09-01','end_date'=>'2026-08-31','is_active'=>1],
        ];
        foreach ($sessions as $s) {
            DB::table('session_masters')->updateOrInsert(['session_code'=>$s['session_code']], $s);
        }
    }
}
