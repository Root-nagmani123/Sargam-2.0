<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds activity_master with all activity codes used in the original system.
 * These were hardcoded in the original PHP (no admin UI for them — just upload.php).
 *
 * Also seeds a sample course for testing.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Activity Master ───────────────────────────────────────────────────
        // From showotjoined.php, showreportall.php, showstatus.php, view_otreport.php
        $activities = [
            // code        display name                    dept
            ['joined',     'Joined (Admin)',               'FC2024'],
            ['idcard',     'ID Card Issued (Security)',    'FC2024'],
            ['biometric',  'Biometric Registration (IT)',  'FC2024'],
            ['trgind',     'Training Induction (Trg)',     'FC2024'],
            ['height',     'Height (Medical)',             'FC2024'],
            ['weight',     'Weight (Medical)',             'FC2024'],
            ['spo2',       'SpO2 (Medical)',               'FC2024'],
            ['pulse',      'Pulse (Medical)',              'FC2024'],
            ['bp',         'Blood Pressure (Medical)',     'FC2024'],
            ['souvenir',   'Souvenir/Kit (Shop)',          'FC2024'],
            ['preremarks', 'Pre-Medical Remarks',          'FC2024'],
            ['vialtube',   'Vial Tube (Medical)',          'FC2024'],
            ['bloodsample','Blood Sample Collected',       'FC2024'],
        ];

        foreach ($activities as [$menuid, $menun, $ccode]) {
            DB::table('activity_master')->updateOrInsert(
                ['menuid' => $menuid],
                ['menun' => $menun, 'ccode' => $ccode, 'status' => 1,
                 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // ── Sample Course ─────────────────────────────────────────────────────
        DB::table('course_master')->updateOrInsert(
            ['c_code' => 'FC2024'],
            ['c_name' => 'Foundation Course 2024', 'status' => 1,
             'created_at' => now(), 'updated_at' => now()]
        );

        $this->command->info('Activity master and sample course seeded.');
    }
}
