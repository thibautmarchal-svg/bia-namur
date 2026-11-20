<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Tous les lots de contenu éditorial, et rien d'autre.
 *
 * À utiliser seul en production (`--class`) : DatabaseSeeder rejoue aussi
 * PlaceSeeder / StorySeeder, qui font des updateOrCreate sur les fiches d'origine.
 * Ajouter ici chaque nouveau lot, lieux avant stories.
 */
class ContentLotsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlacesLot01Seeder::class,
            StoriesLot01Seeder::class,
            PlacesLot02Seeder::class,
            StoriesLot02Seeder::class,
        ]);
    }
}
