<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SectorMaster;
use App\Models\MinistryMaster;

class SectorMinistrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Sectors
        $sectors = [
            ['sector_name' => 'Defence', 'sector_description' => 'Defence and Security Sector'],
            ['sector_name' => 'Health', 'sector_description' => 'Health and Medical Sector'],
            ['sector_name' => 'Education', 'sector_description' => 'Education and Development Sector'],
            ['sector_name' => 'Infrastructure', 'sector_description' => 'Infrastructure and Public Works'],
            ['sector_name' => 'Finance', 'sector_description' => 'Finance and Commerce Sector'],
            ['sector_name' => 'Agriculture', 'sector_description' => 'Agriculture and Rural Development'],
        ];

        foreach ($sectors as $sector) {
            SectorMaster::create($sector);
        }

        // Create Ministries for each Sector
        $ministries = [
            // Defence Sector
            1 => [
                ['ministry_name' => 'Ministry of Defence', 'ministry_description' => 'Armed Forces Management'],
                ['ministry_name' => 'Ministry of Home Affairs', 'ministry_description' => 'Internal Security'],
                ['ministry_name' => 'Ministry of External Affairs', 'ministry_description' => 'Foreign Relations'],
            ],
            // Health Sector
            2 => [
                ['ministry_name' => 'Ministry of Health and Family Welfare', 'ministry_description' => 'Healthcare Services'],
                ['ministry_name' => 'Ministry of AYUSH', 'ministry_description' => 'Traditional Medicine Systems'],
                ['ministry_name' => 'Ministry of Food Processing Industries', 'ministry_description' => 'Food Safety and Quality'],
            ],
            // Education Sector
            3 => [
                ['ministry_name' => 'Ministry of Education', 'ministry_description' => 'Higher Education'],
                ['ministry_name' => 'Ministry of Skill Development', 'ministry_description' => 'Vocational Training'],
                ['ministry_name' => 'Ministry of Human Resource Development', 'ministry_description' => 'Education and Development'],
            ],
            // Infrastructure Sector
            4 => [
                ['ministry_name' => 'Ministry of Road Transport', 'ministry_description' => 'Road Infrastructure'],
                ['ministry_name' => 'Ministry of Railways', 'ministry_description' => 'Railway Network'],
                ['ministry_name' => 'Ministry of Shipping', 'ministry_description' => 'Ports and Shipping'],
            ],
            // Finance Sector
            5 => [
                ['ministry_name' => 'Ministry of Finance', 'ministry_description' => 'Financial Management'],
                ['ministry_name' => 'Ministry of Commerce and Industry', 'ministry_description' => 'Trade and Commerce'],
                ['ministry_name' => 'Ministry of Corporate Affairs', 'ministry_description' => 'Business Regulations'],
            ],
            // Agriculture Sector
            6 => [
                ['ministry_name' => 'Ministry of Agriculture', 'ministry_description' => 'Agricultural Development'],
                ['ministry_name' => 'Ministry of Rural Development', 'ministry_description' => 'Rural Infrastructure'],
                ['ministry_name' => 'Ministry of Irrigation', 'ministry_description' => 'Water Management'],
            ],
        ];

        foreach ($ministries as $sectorId => $ministryList) {
            foreach ($ministryList as $ministry) {
                MinistryMaster::create([
                    'sector_master_pk' => $sectorId,
                    'ministry_name' => $ministry['ministry_name'],
                    'ministry_description' => $ministry['ministry_description'],
                    'status' => 1,
                ]);
            }
        }
    }
}

