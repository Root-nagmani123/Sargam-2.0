<?php

namespace Database\Seeders\FC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CategoryMasterSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            ['category_name'=>'General','category_code'=>'GEN'],
            ['category_name'=>'OBC','category_code'=>'OBC'],
            ['category_name'=>'SC','category_code'=>'SC'],
            ['category_name'=>'ST','category_code'=>'ST'],
            ['category_name'=>'EWS','category_code'=>'EWS'],
        ];
        foreach ($cats as $c) {
            DB::table('category_masters')->updateOrInsert(['category_code'=>$c['category_code']], $c);
        }
    }
}
