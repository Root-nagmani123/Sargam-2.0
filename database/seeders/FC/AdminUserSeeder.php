<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account
        DB::table('jbp_users')->updateOrInsert(
            ['username' => 'admin'],
            [
                'username'  => 'admin',
                'password'  => Hash::make('Admin@1234'),
                'email'     => 'admin@lbsnaa.gov.in',
                'role'      => 'ADMIN',
                'enabled'   => 1,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]
        );

        // Demo FC trainee account
        DB::table('jbp_users')->updateOrInsert(
            ['username' => 'fc_demo_001'],
            [
                'username'  => 'fc_demo_001',
                'password'  => Hash::make('FC@12345'),
                'email'     => 'demo.fc@lbsnaa.gov.in',
                'role'      => 'FC',
                'enabled'   => 1,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]
        );

        // Report-only user
        DB::table('jbp_users')->updateOrInsert(
            ['username' => 'report_user'],
            [
                'username'  => 'report_user',
                'password'  => Hash::make('Report@123'),
                'email'     => 'report@lbsnaa.gov.in',
                'role'      => 'REPORT',
                'enabled'   => 1,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]
        );
    }
}
