<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Baseline clubs / societies for Communications → Define Club/ Society.
 * This is also the source list for the Club/ Society Programme Mapping modal.
 * Idempotent: re-running never duplicates a row.
 */
class ClubSocietyMasterSeeder extends Seeder
{
    public function run(): void
    {
        $clubs = [
            'Officers Club',
            'Officer Mess',
            'Fine Arts Association',
            'Film Society',
            'Society for Contemporary Affairs',
            'Hobbies Club',
            'Society for Social Service',
            'Rifle and Archery Club',
            'House Journal Society',
            'Nature Lovers Club',
            'Computer Society',
            'Management Circle',
            'Adventure Sports Club',
            'Alumni Association',
            'Committee for Overseeing the Management of Hostels',
            'HAM Radio Club',
            'Innovation Club',
            'Rahul Sankrityayan Hindi Club',
            'Stok Kangri',
            'Nanda Devi',
            'Kangchenjunga',
            'Namcha Barwa',
        ];

        foreach ($clubs as $name) {
            if (DB::table('club_society_master')->where('club_society_name', $name)->exists()) {
                continue;
            }

            DB::table('club_society_master')->insert([
                'club_society_name' => $name,
                'active_inactive'   => 1,
            ]);
        }
    }
}
