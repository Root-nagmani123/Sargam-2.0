<?php

namespace Database\Seeders\FC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FatherProfessionSeeder extends Seeder
{
    public function run(): void
    {
        $profs = [
            'Agriculture / Farmer','Government Service (Gazetted)','Government Service (Non-Gazetted)',
            'Defence Services','Business / Trade','Professional (Doctor/Lawyer/Engineer)',
            'Teaching','Retired','Deceased','Other',
        ];
        foreach ($profs as $p) {
            DB::table('father_professions')->updateOrInsert(['profession_name'=>$p],['profession_name'=>$p]);
        }
    }
}
