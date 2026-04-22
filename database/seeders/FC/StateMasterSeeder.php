<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StateMasterSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            'AGMUT','Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh',
            'Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala',
            'Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland',
            'Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura',
            'Uttar Pradesh','Uttarakhand','West Bengal',
        ];
        foreach ($states as $i => $name) {
            $code = strtoupper(preg_replace('/[^A-Z]/i','',substr($name,0,6)));
            DB::table('state_masters')->updateOrInsert(
                ['state_name'=>$name],
                ['state_name'=>$name,'state_code'=>$code.$i]
            );
        }
    }
}
