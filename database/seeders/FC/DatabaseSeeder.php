<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SessionMasterSeeder::class,
            ServiceMasterSeeder::class,
            StateMasterSeeder::class,
            CountryMasterSeeder::class,
            CategoryMasterSeeder::class,
            ReligionMasterSeeder::class,
            QualificationMasterSeeder::class,
            BoardNameMasterSeeder::class,
            HighestStreamMasterSeeder::class,
            LanguageMasterSeeder::class,
            JobTypeMasterSeeder::class,
            FatherProfessionSeeder::class,
            SportsMasterSeeder::class,
            TravelTypeMasterSeeder::class,
            MctpTravelModeMasterSeeder::class,
            FcJoiningDocumentMasterSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
