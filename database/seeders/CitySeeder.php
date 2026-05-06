<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        // Namur — première ville. Bounding box approximative : grand Namur + Jambes + Bouge + La Plante.
        City::updateOrCreate(
            ['slug' => 'namur'],
            [
                'name' => 'Namur',
                'latitude' => 50.4674,
                'longitude' => 4.8718,
                'bounding_box' => [
                    'sw' => ['lat' => 50.4280, 'lng' => 4.8090],
                    'ne' => ['lat' => 50.5050, 'lng' => 4.9410],
                ],
                'primary_color' => '#C77F2C',
            ],
        );
    }
}
