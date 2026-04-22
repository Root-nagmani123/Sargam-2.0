<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BoardNameMasterSeeder extends Seeder
{
    public function run(): void
    {
        $boards = [
            'CBSE – Central Board of Secondary Education',
            'ICSE – Council for the Indian School Certificate Examinations',
            'UP Board (UPMSP)',
            'Bihar Board (BSEB)',
            'RBSE – Rajasthan Board',
            'MSBSHSE – Maharashtra Board',
            'PSEB – Punjab School Education Board',
            'Delhi Board (CBSE Affiliated)',
            'State Board (Other)',
            'IIT','NIT','Central University','State University','Deemed University','Foreign University',
        ];
        foreach ($boards as $b) {
            DB::table('board_name_masters')->updateOrInsert(['board_name'=>$b],['board_name'=>$b]);
        }
    }
}
