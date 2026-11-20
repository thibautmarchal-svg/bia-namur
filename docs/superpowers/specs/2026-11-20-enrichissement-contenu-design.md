# Enrichissement du contenu (lieux et stories) — design court

## Intention
Bia Namur a 5 lieux et 2 stories : trop peu pour être référencé ou visité. Le SEO technique (sitemap, canonical, Open Graph, plan de liens noscript) est déjà en place ; le contenu est le goulot. Objectif : plus de pages indexables, donc plus de visites.

## Objectifs
- Viser le brief : ~50 lieux fondateurs et ~50 stories, par lots thématiques.
- Chaque entrée repose sur des faits vérifiés, avec sources.
- Maillage interne : chaque story liée à son lieu, et inversement (`stories.place_id`, `places.story_id`).
- Sujets choisis selon ce que les gens cherchent (long tail local), pas par ordre alphabétique.

## Non-objectifs
- Photos (chantier à part).
- SSR / rendu serveur du texte des stories (chantier SEO à part, à traiter après le contenu).
- Génération par le pipeline IA `story_v1`.

## Anti-objectifs
- Aucun fait, date, horaire, téléphone ou coordonnée inventé ou non confirmé. Info incertaine : omise ou marquée, jamais devinée.
- Aucune entrée publiée sans relecture humaine.

## Contraintes
- Hébergement mutualisé, sans SSH : le contenu passe par `PlaceSeeder` / `StorySeeder` (`updateOrCreate`, idempotents), déployés via la CI existante.
- Format des modèles `Place` / `Story` inchangé (slug, type, tags, `opening_hours`, `contact` ; stories en markdown de 200 à 400 mots, ton namurois du brief).
- Stories et lieux insérés en `draft` ; publication depuis Filament après relecture.
- Aucune nouvelle dépendance.

## Approches
1. **Recherche web par lots + rédaction sourcée + relecture humaine** — retenue (choix de l'utilisateur) : fiable et réversible, plus lent.
2. Pipeline IA `story_v1` — écartée : faits tirés de la mémoire du modèle, et pas de file d'attente en mutualisé.
3. Tout publier d'un coup — écartée : une erreur se propage partout, relecture ingérable.

## Mode de travail
Lot par thème (≈10 lieux + ≈10 stories). Pour chaque lot : recherche, rédaction dans les seeders, fichier de sources à côté, relecture par l'utilisateur, publication via Filament.

## Choix de l'assistant (à corriger)
- Thèmes de départ (choix de l'utilisateur) : **patrimoine** et **bonnes adresses** (bars, restaurants, marchés, commerces indé). Je commence par le patrimoine (Citadelle, souterrains, Saint-Aubain, beffroi, Rue Saintraint…), puis les bonnes adresses, en alternant les lots. Wallon et traditions viendront après.
- Pas d'horaires ni de téléphones sans confirmation sur source officielle.

## Laissé au réalisateur
Slugs, tags, découpage exact des lots, format du fichier de sources.
