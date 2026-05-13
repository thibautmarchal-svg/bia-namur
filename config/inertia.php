<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inertia — Bia Namur
    |--------------------------------------------------------------------------
    |
    | On override la config par defaut du package pour 2 raisons :
    |
    | 1. Le path par defaut pointe sur `resources/js/pages` (minuscule), mais
    |    notre dossier est `resources/js/Pages` (majuscule). En local Windows
    |    c'est case-insensitive donc ca passe. En CI Linux (Ubuntu) c'est
    |    strict, donc `inertia.view-finder` ne trouvait aucune page →
    |    assertInertia plantait avec "Inertia page component file [X] does
    |    not exist" sur 21 tests.
    |
    | 2. Le SSR est desactive (Inertia::SSR pas configure cote front et
    |    pas de service Node tournant). Sans cet override, le package
    |    cherche un bundle SSR au boot → bruit dans les logs.
    */

    'ssr' => [
        'enabled' => false,
        'ensure_bundle_exists' => false,
    ],

    'pages' => [
        'paths' => [resource_path('js/Pages')],
        'extensions' => ['vue'],
    ],

    'testing' => [
        // Garde true pour catch les typos dans Inertia::render('NomMalEcrit')
        // en local. La case-sensitivity du path est fixee au-dessus.
        'ensure_pages_exist' => true,
    ],

    'expose_shared_prop_keys' => true,

    'history' => [
        'encrypt' => false,
    ],

];
