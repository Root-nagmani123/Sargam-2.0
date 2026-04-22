<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CountryMasterSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['country_name'=>'India','country_code'=>'IN'],
            ['country_name'=>'United States','country_code'=>'US'],
            ['country_name'=>'United Kingdom','country_code'=>'GB'],
            ['country_name'=>'Australia','country_code'=>'AU'],
            ['country_name'=>'Canada','country_code'=>'CA'],
            ['country_name'=>'Germany','country_code'=>'DE'],
            ['country_name'=>'France','country_code'=>'FR'],
            ['country_name'=>'Japan','country_code'=>'JP'],
            ['country_name'=>'Other','country_code'=>'OT'],
        ];
        foreach ($countries as $c) {
            DB::table('country_masters')->updateOrInsert(['country_code'=>$c['country_code']], $c);
        }
    }
}
