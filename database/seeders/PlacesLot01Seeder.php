<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDraftContent;
use Illuminate\Database\Seeder;

/**
 * Lot 1 — patrimoine (brouillons, à relire avant publication).
 * Sources : docs/contenu/lot-01-patrimoine-sources.md
 *
 * Règle du lot : une info non confirmée est omise (horaires, téléphones,
 * numéros de rue, coordonnées). Les coordonnées présentes viennent de
 * Wikipédia ; la Halle al'Chair n'en a pas (non confirmées).
 */
class PlacesLot01Seeder extends Seeder
{
    use SeedsDraftContent;

    public function run(): void
    {
        $this->seedPlaces([
            [
                'slug' => 'beffroi-de-namur',
                'name' => 'Beffroi de Namur',
                'type' => 'patrimoine',
                'description' => 'L\'ancienne tour Saint-Jacques de l\'enceinte médiévale, devenue beffroi de la ville au XVIIIe siècle et inscrite à l\'UNESCO.',
                'latitude' => 50.463895,
                'longitude' => 4.867072,
                'address' => 'Rue du Beffroi, 5000 Namur',
                'neighborhood' => 'Centre',
                'tags' => ['patrimoine', 'UNESCO', 'beffroi', 'centre', 'à pied'],
            ],
            [
                'slug' => 'eglise-saint-loup',
                'name' => 'Église Saint-Loup',
                'type' => 'patrimoine',
                'description' => 'L\'ancienne église des Jésuites, un chef-d\'œuvre baroque du XVIIe siècle classé patrimoine exceptionnel de Wallonie.',
                'latitude' => 50.46401,
                'longitude' => 4.86327,
                'address' => null,
                'neighborhood' => 'Centre',
                'tags' => ['patrimoine', 'baroque', 'religieux', 'centre', 'à pied'],
            ],
            [
                'slug' => 'halle-al-chair',
                'name' => 'Halle al\'Chair',
                'type' => 'patrimoine',
                'description' => 'L\'ancienne halle aux viandes de la fin du XVIe siècle, au bord de la Sambre, qui fut aussi école, arsenal, hôpital, musée et théâtre. Elle abrite aujourd\'hui l\'Office du tourisme.',
                'address' => null,
                'neighborhood' => 'Centre',
                'tags' => ['patrimoine', 'Sambre', 'Renaissance', 'centre', 'à pied'],
            ],
            [
                'slug' => 'trema-musee-des-arts-anciens',
                'name' => 'TreM.a — Musée des Arts anciens du Namurois',
                'type' => 'culture',
                'description' => 'Un hôtel patricien du XVIIIe siècle, rue de Fer, qui garde le Trésor d\'Oignies, chef-d\'œuvre d\'orfèvrerie du XIIIe siècle.',
                'latitude' => 50.4651806,
                'longitude' => 4.8653556,
                'address' => 'Rue de Fer, 5000 Namur',
                'neighborhood' => 'Centre',
                'tags' => ['musée', 'orfèvrerie', 'moyen âge', 'centre', 'à pied'],
            ],
            [
                'slug' => 'musee-felicien-rops',
                'name' => 'Musée Félicien Rops',
                'type' => 'culture',
                'description' => 'Le musée de la Province de Namur consacré à Félicien Rops, du caricaturiste des débuts à l\'illustrateur le plus sulfureux.',
                'latitude' => 50.463385,
                'longitude' => 4.86271,
                'address' => 'Rue Fumal 12, 5000 Namur',
                'neighborhood' => 'Centre',
                'contact' => [
                    'website' => 'https://museerops.be',
                ],
                'tags' => ['musée', 'art', 'Rops', 'centre', 'à pied'],
            ],
            [
                'slug' => 'pont-de-jambes',
                'name' => 'Pont de Jambes',
                'type' => 'patrimoine',
                'description' => 'Le vieux pont de Meuse entre Jambes et le pied de la Citadelle, classé depuis 1989 ; il marquait jadis la frontière entre le comté de Namur et la principauté de Liège.',
                'latitude' => 50.458333,
                'longitude' => 4.865833,
                'address' => null,
                'neighborhood' => 'Bord de Meuse',
                'tags' => ['patrimoine', 'Meuse', 'pont', 'à pied', 'vélo'],
            ],
        ]);
    }
}
