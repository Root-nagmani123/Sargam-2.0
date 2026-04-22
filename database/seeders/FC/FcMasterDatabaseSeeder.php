<?php

namespace Database\Seeders\FC;

use Database\Seeders\MctpTravelModeMasterSeeder;
use Database\Seeders\SportsMasterSeeder;
use Database\Seeders\TravelTypeMasterSeeder;
use Illuminate\Database\Seeder;

/**
 * Runs all FC master-data seeders under database/seeders/FC/.
 * Invoke: php artisan db:seed --class=Database\\Seeders\\FC\\FcMasterDatabaseSeeder --force
 */
class FcMasterDatabaseSeeder extends Seeder
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
