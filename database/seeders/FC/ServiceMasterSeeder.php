<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ServiceMasterSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['service_name'=>'Indian Administrative Service','service_code'=>'IAS'],
            ['service_name'=>'Indian Police Service','service_code'=>'IPS'],
            ['service_name'=>'Indian Foreign Service','service_code'=>'IFS'],
            ['service_name'=>'Indian Revenue Service (IT)','service_code'=>'IRS-IT'],
            ['service_name'=>'Indian Revenue Service (C&CE)','service_code'=>'IRS-CE'],
            ['service_name'=>'Indian Audit & Accounts Service','service_code'=>'IAAS'],
            ['service_name'=>'Indian Civil Accounts Service','service_code'=>'ICAS'],
            ['service_name'=>'Indian Defence Accounts Service','service_code'=>'IDAS'],
            ['service_name'=>'Indian Information Service','service_code'=>'IIS'],
            ['service_name'=>'Indian Trade Service','service_code'=>'ITS'],
            ['service_name'=>'Indian P&T Accounts & Finance Service','service_code'=>'IPTAFS'],
            ['service_name'=>'Indian Postal Service','service_code'=>'IPoS'],
            ['service_name'=>'Indian Railway Accounts Service','service_code'=>'IRAS'],
            ['service_name'=>'Indian Railway Personnel Service','service_code'=>'IRPS'],
            ['service_name'=>'Indian Railway Traffic Service','service_code'=>'IRTS'],
            ['service_name'=>'Indian Statistical Service','service_code'=>'ISS'],
            ['service_name'=>'Central Secretariat Service','service_code'=>'CSS'],
        ];
        foreach ($services as $s) {
            DB::table('service_masters')->updateOrInsert(['service_code'=>$s['service_code']], $s);
        }
    }
}
