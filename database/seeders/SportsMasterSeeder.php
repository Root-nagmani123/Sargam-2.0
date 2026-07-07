<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SportsMasterSeeder extends Seeder
{
    public function run(): void
    {
        $sports = [
            'Athletics', 'Badminton', 'Basketball', 'Boxing', 'Chess', 'Cricket', 'Cycling',
            'Football', 'Golf', 'Gymnastics', 'Hockey', 'Judo', 'Kabaddi', 'Kho-Kho',
            'Martial Arts', 'Rowing', 'Shooting', 'Squash', 'Swimming', 'Table Tennis',
            'Tennis', 'Volleyball', 'Weight Lifting', 'Wrestling', 'Yoga', 'Other',
        ];

        $now = now();
        foreach ($sports as $name) {
            if (DB::table('sports_masters')->where('sport_name', $name)->exists()) {
                DB::table('sports_masters')->where('sport_name', $name)->update(['updated_at' => $now]);
                continue;
            }
            DB::table('sports_masters')->insert([
                'sport_name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
