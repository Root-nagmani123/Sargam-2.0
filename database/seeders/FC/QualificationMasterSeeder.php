<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QualificationMasterSeeder extends Seeder
{
    public function run(): void
    {
        $quals = [
            'Secondary (Class X)','Senior Secondary (Class XII)',
            'Graduation (B.A.)','Graduation (B.Sc.)','Graduation (B.Com.)',
            'Graduation (B.Tech./B.E.)','Graduation (MBBS)','Graduation (LLB)',
            'Graduation (B.Ed.)','Post Graduation (M.A.)','Post Graduation (M.Sc.)',
            'Post Graduation (M.Tech./M.E.)','Post Graduation (MBA)','Post Graduation (LLM)',
            'PhD','Other',
        ];
        foreach ($quals as $q) {
            DB::table('qualification_masters')->updateOrInsert(['qualification_name'=>$q],['qualification_name'=>$q]);
        }
    }
}
