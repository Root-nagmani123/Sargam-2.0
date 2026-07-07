<?php

namespace Database\Seeders\FC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FcActivityMasterSeeder extends Seeder
{
    public function run(): void
    {
        $activeSession = DB::table('session_masters')->where('is_active', 1)->value('session_name') ?? 'FC2024';

        $activities = [
            ['joined', 'Joined (Admin)', $activeSession],
            ['idcard', 'ID Card Issued (Security)', $activeSession],
            ['biometric', 'Biometric Registration (IT)', $activeSession],
            ['trgind', 'Training Induction (Training)', $activeSession],
            ['height', 'Height (Medical)', $activeSession],
            ['weight', 'Weight (Medical)', $activeSession],
            ['spo2', 'SpO2 (Medical)', $activeSession],
            ['pulse', 'Pulse (Medical)', $activeSession],
            ['bp', 'Blood Pressure (Medical)', $activeSession],
            ['souvenir', 'Souvenir/Kit (Shop)', $activeSession],
            ['preremarks', 'Pre-Medical Remarks', $activeSession],
            ['vialtube', 'Vial Tube (Medical)', $activeSession],
            ['bloodsample', 'Blood Sample Collected', $activeSession],
        ];

        foreach ($activities as [$menuid, $menun, $ccode]) {
            DB::table('fc_activity_master')->updateOrInsert(
                ['menuid' => $menuid],
                ['menun' => $menun, 'ccode' => $ccode, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
