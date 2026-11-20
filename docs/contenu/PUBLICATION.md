# Mettre en ligne un lot de contenu

Les lots (`PlacesLotNNSeeder`, `StoriesLotNNSeeder`, appelés par `ContentLotsSeeder`) créent des fiches en **brouillon**. Rien n'est visible tant qu'elles ne sont pas publiées. Un seeder de lot ne modifie jamais une fiche existante (publiée, retouchée ou supprimée) ; il ne relie pas non plus une story à un lieu déjà publié.

1. **Relire** le lot avec son fichier de sources (`docs/contenu/lot-NN-*-sources.md`) : faits, orthographe, ton. Corriger dans les seeders *avant* le premier seed en production (ensuite, on corrige dans Filament).
2. **Déployer** comme d'habitude (GitHub Actions, FTP).
3. **Seeder uniquement les lots** : ne pas lancer `db:seed` complet. `DatabaseSeeder` rejoue `CitySeeder`, `AdminUserSeeder`, `PlaceSeeder` et `StorySeeder`, qui font des `updateOrCreate` et écraseraient les retouches faites dans Filament sur les 5 lieux et 2 stories d'origine. Sans SSH : dupliquer `_seed.php` en `_seed-content.php`, **avec un nouveau jeton**, et y remplacer l'appel par `$kernel->call('db:seed', ['--class' => 'Database\\Seeders\\ContentLotsSeeder', '--force' => true]);`. Supprimer ce fichier du serveur juste après. Le jeton actuel de `_seed.php` a été affiché dans une conversation : à changer, et à retirer du serveur.
4. **Publier** dans Filament (`Lieux` puis `Stories`) : passer chaque fiche relue en « publié ». Publier le lieu avant sa story, puis relier lieu et story dans la fiche du lieu si besoin (la fiche de la Citadelle, déjà publiée, n'est pas reliée automatiquement à la story des souterrains). Attention : relier une story encore en brouillon à un lieu publié l'affiche sur la page du lieu.
5. **Vérifier** `https://bianamur.be/sitemap.xml` : les nouvelles URL `/lieu/…` et `/story/…` y figurent et le `lastmod` des index a avancé. Puis, dans Google Search Console, redemander l'indexation de quelques URL clés.

Les fiches sans coordonnées (ex. Halle al'Chair) fonctionnent mais n'apparaissent pas sur la carte : à compléter dans Filament quand les coordonnées sont confirmées.
