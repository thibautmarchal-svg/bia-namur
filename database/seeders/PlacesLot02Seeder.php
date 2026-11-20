<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDraftContent;
use Illuminate\Database\Seeder;

/**
 * Lot 2 — bonnes adresses (brouillons, à relire avant publication).
 * Sources : docs/contenu/lot-02-bonnes-adresses-sources.md
 *
 * Règle du lot : seuls les lieux dont l'existence est confirmée par une
 * source récente. Horaires, téléphones et coordonnées omis quand non
 * confirmés. Aucun superlatif ni avis commercial.
 */
class PlacesLot02Seeder extends Seeder
{
    use SeedsDraftContent;

    public function run(): void
    {
        $this->seedPlaces([
            [
                'slug' => 'marche-du-bord-de-leau',
                'name' => 'Marché du bord de l\'eau',
                'type' => 'marche',
                'description' => 'Le marché hebdomadaire de Namur, installé depuis mars 2024 le long de la Sambre et du boulevard Frère Orban.',
                'address' => 'Boulevard Frère Orban, 5000 Namur',
                'neighborhood' => 'Bord de Sambre',
                'tags' => ['marché', 'samedi', 'Sambre', 'centre'],
            ],
            [
                'slug' => 'parc-louise-marie',
                'name' => 'Parc Louise-Marie',
                'type' => 'parc',
                'description' => 'Un parc d\'environ trois hectares, aménagé en 1879-1880 dans l\'ancien fossé de l\'enceinte médiévale, à deux pas de la gare.',
                'address' => null,
                'neighborhood' => 'Quartier de la gare',
                'tags' => ['parc', 'promenade', 'gare', 'famille', 'à pied'],
            ],
            [
                'slug' => 'place-du-marche-aux-legumes',
                'name' => 'Place du Marché aux Légumes',
                'type' => 'hidden_gem',
                'description' => 'La place du Vieux, au cœur du centre historique : lieu de rencontre bordé de cafés, et décor du marché de Noël de Namur.',
                'address' => 'Place du Marché aux Légumes, 5000 Namur',
                'neighborhood' => 'Centre',
                'tags' => ['place', 'cafés', 'centre', 'marché de Noël', 'à pied'],
            ],
            [
                'slug' => 'librairie-tulitu',
                'name' => 'Tulitu',
                'type' => 'librairie',
                'description' => 'Une librairie indépendante dans une rue calme du centre de Namur.',
                'address' => 'Rue Bas de la Place 16, 5000 Namur',
                'neighborhood' => 'Centre',
                'tags' => ['librairie', 'indépendant', 'centre', 'à pied'],
            ],
            [
                'slug' => 'mabaliyo-cafe-librairie',
                'name' => 'MaBaLiYo',
                'type' => 'librairie',
                'description' => 'Un café-librairie ouvert fin 2025 rue des Brasseurs, autour de la santé mentale et de la sexologie.',
                'address' => 'Rue des Brasseurs 13, 5000 Namur',
                'neighborhood' => 'Centre',
                'tags' => ['librairie', 'café', 'indépendant', 'centre', 'à pied'],
            ],
            [
                'slug' => 'librairie-ad-hoc',
                'name' => 'Ad Hoc',
                'type' => 'librairie',
                'description' => 'La librairie spécialisée en bandes dessinées de la rue Saint-Jean, qui a fêté ses trente ans en 2024.',
                'address' => 'Rue Saint-Jean, 5000 Namur',
                'neighborhood' => 'Centre',
                'tags' => ['librairie', 'BD', 'indépendant', 'centre', 'à pied'],
            ],
        ]);
    }
}
