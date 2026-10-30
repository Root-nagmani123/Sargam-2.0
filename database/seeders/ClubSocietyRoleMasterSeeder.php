<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Baseline roles for Communications -> Define Club/ Society Role.
 * Idempotent: re-running never duplicates a row.
 */
class ClubSocietyRoleMasterSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Director',
            'Chairman',
            'Secretary',
            'Accountant',
            'Treasurer',
            'Coordinator',
        ];

        foreach ($roles as $name) {
            if (DB::table('club_society_role_master')->where('club_society_role_name', $name)->exists()) {
                continue;
            }

            DB::table('club_society_role_master')->insert([
                'club_society_role_name' => $name,
                'active_inactive'        => 1,
            ]);
        }
    }
}
