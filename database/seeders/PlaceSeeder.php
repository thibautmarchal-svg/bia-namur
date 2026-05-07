<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Place;
use Illuminate\Database\Seeder;

/**
 * 5 lieux fondateurs de Bia Namur (cf. brief annexe B).
 * Vrais lieux namurois, descriptions main-ecrites — pas de lorem ipsum.
 * En J6, sans photos uploadees encore : photos arrivent en S2/S3 via R2.
 */
class PlaceSeeder extends Seeder
{
    public function run(): void
    {
        $namur = City::where('slug', 'namur')->firstOrFail();

        $places = [
            [
                'slug' => 'citadelle-de-namur',
                'name' => 'Citadelle de Namur',
                'type' => 'patrimoine',
                'description' => 'La forteresse historique sur les hauteurs du confluent. Promenade panoramique entre Sambre et Meuse, souterrains à explorer, herbe pour s\'asseoir un soir d\'été.',
                'latitude' => 50.4615,
                'longitude' => 4.8635,
                'address' => 'Route Merveilleuse, 5000 Namur',
                'neighborhood' => 'Citadelle',
                'opening_hours' => [
                    'general' => 'Domaine accessible 24h/24',
                    'visitor_center' => 'Avril à novembre, 10h-18h',
                ],
                'contact' => [
                    'website' => 'https://citadelle.namur.be',
                    'phone' => '+32 81 24 73 70',
                ],
                'tags' => ['patrimoine', 'panorama', 'famille', 'à pied', 'grand espace'],
                'source' => Place::SOURCE_ADMIN,
                'status' => Place::STATUS_PUBLISHED,
            ],
            [
                'slug' => 'cathedrale-saint-aubain',
                'name' => 'Cathédrale Saint-Aubain',
                'type' => 'patrimoine',
                'description' => 'L\'unique cathédrale néoclassique de Belgique. Façade en pierre bleue, intérieur lumineux, et la place qui prend les premiers rayons le samedi matin du marché.',
                'latitude' => 50.4651,
                'longitude' => 4.8642,
                'address' => 'Place du Chapitre, 5000 Namur',
                'neighborhood' => 'Centre',
                'opening_hours' => [
                    'general' => 'Tous les jours 8h-19h',
                    'guided_tours' => 'Samedi 14h, sur réservation',
                ],
                'contact' => [
                    'website' => 'https://www.cathedrale-namur.be',
                ],
                'tags' => ['patrimoine', 'religieux', 'matin', 'centre'],
                'source' => Place::SOURCE_ADMIN,
                'status' => Place::STATUS_PUBLISHED,
            ],
            [
                'slug' => 'marche-saint-aubain',
                'name' => 'Marché Saint-Aubain',
                'type' => 'marche',
                'description' => 'Tous les samedis matin sur la place de la cathédrale. Maraîchers du Condroz, fromager bio de Profondeville, boucher campagnard, fleurs de saison. À l\'aise.',
                'latitude' => 50.4651,
                'longitude' => 4.8642,
                'address' => 'Place du Chapitre, 5000 Namur',
                'neighborhood' => 'Centre',
                'opening_hours' => [
                    'samedi' => '7h30 — 13h00',
                ],
                'contact' => [],
                'tags' => ['marché', 'samedi matin', 'bio', 'frais', 'producteurs locaux'],
                'source' => Place::SOURCE_ADMIN,
                'status' => Place::STATUS_PUBLISHED,
            ],
            [
                'slug' => 'le-delta',
                'name' => 'Le Delta',
                'type' => 'culture',
                'description' => 'Le centre culturel de la Province sur le bord de Meuse. Expos contemporaines, concerts intimistes, café-restaurant en terrasse, programmation jamais bavarde.',
                'latitude' => 50.4587,
                'longitude' => 4.8744,
                'address' => 'Avenue Fernand Golenvaux 18, 5000 Namur',
                'neighborhood' => 'Bord de Meuse',
                'opening_hours' => [
                    'expos' => 'Mardi à dimanche 10h-18h',
                    'cafe' => 'Mardi à dimanche 10h-23h',
                ],
                'contact' => [
                    'website' => 'https://www.ledelta.be',
                    'phone' => '+32 81 77 67 73',
                    'email' => 'info@ledelta.be',
                ],
                'tags' => ['culture', 'expo', 'concert', 'terrasse', 'soir'],
                'source' => Place::SOURCE_ADMIN,
                'status' => Place::STATUS_PUBLISHED,
            ],
            [
                'slug' => 'le-bia-bouquet',
                'name' => 'Le Bia Bouquet',
                'type' => 'bar',
                'description' => 'Le bistrot historique du quai, encore tenu en famille. Pèkèt maison, bières du coin, ambiance qui parle vraiment namurois — surtout le 3e dimanche de septembre.',
                'latitude' => 50.4632,
                'longitude' => 4.8644,
                'address' => 'Rue de Bruxelles 70, 5000 Namur',
                'neighborhood' => 'Centre',
                'opening_hours' => [
                    'mardi-samedi' => '11h00 — 23h30',
                    'dimanche' => '11h00 — 18h00',
                    'lundi' => 'Fermé',
                ],
                'contact' => [
                    'phone' => '+32 81 22 12 35',
                ],
                'tags' => ['bar', 'pèkèt', 'tradition', 'soir', 'centre'],
                'source' => Place::SOURCE_ADMIN,
                'status' => Place::STATUS_PUBLISHED,
            ],
        ];

        foreach ($places as $data) {
            Place::updateOrCreate(
                ['city_id' => $namur->id, 'slug' => $data['slug']],
                array_merge($data, ['city_id' => $namur->id]),
            );
        }
    }
}
