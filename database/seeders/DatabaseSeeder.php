<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CitySeeder::class,
            EntitlementSeeder::class,
            AdminUserSeeder::class,
            PlaceSeeder::class,
            StorySeeder::class,
        ]);
    }
}
