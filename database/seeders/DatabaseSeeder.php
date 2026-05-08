<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\ManageWordOfTheDayPermissionSeeder;
use Database\Seeders\WordOfTheDaySeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            ManageWordOfTheDayPermissionSeeder::class,
            WordOfTheDaySeeder::class,
        ]);
    }
}
