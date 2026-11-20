# Mettre en ligne un lot de contenu

Les lots (`PlacesLotNNSeeder`, `StoriesLotNNSeeder`) créent des fiches en **brouillon**. Rien n'est visible tant qu'elles ne sont pas publiées.

1. **Relire** le lot avec son fichier de sources (`docs/contenu/lot-NN-*-sources.md`) : faits, orthographe, ton. Corriger dans les seeders si besoin *avant* le premier seed en production (après, les seeders ne réécrivent plus une fiche existante : on corrige dans Filament).
2. **Déployer** comme d'habitude (GitHub Actions, FTP).
3. **Seeder** en production avec le script `_seed.php` existant (il exécute `db:seed --force`). Les seeders des lots utilisent `firstOrCreate` : relancer est sans risque, aucune fiche publiée ou retouchée n'est écrasée. **Retirer `_seed.php` du serveur ensuite** (et changer son jeton, qui a été exposé).
4. **Publier** dans Filament (`Lieux` puis `Stories`) : passer chaque fiche relue en « publié ». Publier le lieu avant sa story.
5. **Vérifier** `https://bianamur.be/sitemap.xml` : les nouvelles URL `/lieu/…` et `/story/…` y figurent et le `lastmod` des index a avancé. Puis, dans Google Search Console, redemander l'indexation de quelques URL clés.

Les fiches sans coordonnées (ex. Halle al'Chair) fonctionnent mais n'apparaissent pas sur la carte : à compléter dans Filament quand les coordonnées sont confirmées.
