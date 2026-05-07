<?php

/*
|--------------------------------------------------------------------------
| Photos par défaut Bia Namur
|--------------------------------------------------------------------------
|
| Mapping slug → photo par défaut + crédit + licence.
| Sourcing : Wikimedia Commons (CC BY 2.0 / CC BY-SA 3.0).
|
| Override : si un fichier `public/images/places/{slug}.{webp|jpg}`
| existe (uploade par l'admin via Filament en S2 ou depose manuellement),
| il prime sur le default. Le helper App\Support\PhotoResolver gere la
| logique de resolution.
*/

return [

    'places' => [
        'citadelle-de-namur' => [
            'path' => 'images/defaults/places/citadelle-de-namur',
            'alt' => 'Vue de la Citadelle de Namur depuis le Pont des Ardennes',
            'credit' => 'Anoel',
            'license' => 'CC BY 2.0',
            'license_url' => 'https://creativecommons.org/licenses/by/2.0/',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:La_Citadelle_de_Namur.JPG',
        ],
        'cathedrale-saint-aubain' => [
            'path' => 'images/defaults/places/cathedrale-saint-aubain',
            'alt' => 'Cathédrale Saint-Aubain de Namur, vue depuis la Citadelle',
            'credit' => 'DasaBezak',
            'license' => 'CC BY-SA 3.0',
            'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Namur-Cath%C3%A9drale_Saint-Aubain_-_vue_de_la_Citadelle.jpg',
        ],
        'marche-saint-aubain' => [
            // La place du marche est devant la Cathedrale, photo coherente.
            'path' => 'images/defaults/places/cathedrale-saint-aubain',
            'alt' => 'Place Saint-Aubain, où se tient le marché du samedi',
            'credit' => 'DasaBezak',
            'license' => 'CC BY-SA 3.0',
            'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Namur-Cath%C3%A9drale_Saint-Aubain_-_vue_de_la_Citadelle.jpg',
        ],
        'le-delta' => [
            // Le Delta est sur le bord de Meuse au confluent.
            'path' => 'images/defaults/places/confluent-sambre-meuse',
            'alt' => 'Le confluent de la Sambre et de la Meuse, où se trouve Le Delta',
            'credit' => 'Calonne Marcel',
            'license' => 'CC BY-SA 3.0',
            'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Namur_Confluent_IMG_1408.JPG',
        ],
        'le-bia-bouquet' => [
            // Pas de photo dédiée du bistrot — on utilise le confluent comme image namuroise generique.
            'path' => 'images/defaults/places/confluent-sambre-meuse',
            'alt' => 'Vue de Namur depuis le confluent Sambre-Meuse',
            'credit' => 'Calonne Marcel',
            'license' => 'CC BY-SA 3.0',
            'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Namur_Confluent_IMG_1408.JPG',
        ],
    ],

    'stories' => [
        'la-rue-saintraint' => [
            // La rue Saintraint mene vers la Cathedrale Saint-Aubain.
            'path' => 'images/defaults/places/cathedrale-saint-aubain',
            'alt' => 'Cathédrale Saint-Aubain de Namur, au bout de la rue Saintraint',
            'credit' => 'DasaBezak',
            'license' => 'CC BY-SA 3.0',
            'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Namur-Cath%C3%A9drale_Saint-Aubain_-_vue_de_la_Citadelle.jpg',
        ],
        'lorigine-du-bia-bouquet' => [
            'path' => 'images/defaults/places/citadelle-de-namur',
            'alt' => 'Citadelle de Namur, écrin des Fêtes de Wallonie',
            'credit' => 'Anoel',
            'license' => 'CC BY 2.0',
            'license_url' => 'https://creativecommons.org/licenses/by/2.0/',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:La_Citadelle_de_Namur.JPG',
        ],
    ],

];
