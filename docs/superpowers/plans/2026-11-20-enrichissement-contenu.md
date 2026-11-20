# Enrichissement du contenu — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter des lots de lieux et de stories (patrimoine, bonnes adresses) vérifiés et sourcés, insérés en `draft`, pour étoffer le contenu indexable de Bia Namur.

**Architecture:** Un seeder par lot et par type (lieux, stories), appelé depuis `DatabaseSeeder`, qui crée sans jamais écraser (`firstOrCreate`). Un fichier de sources par lot. Un test de cohérence garantit slugs uniques, statut `draft` et liens story↔lieu valides. Publication par l'utilisateur via Filament.

**Tech Stack:** Laravel 11, Eloquent, Pest, Filament 3.

**Spec:** `docs/superpowers/specs/2026-11-20-enrichissement-contenu-design.md`

## Global Constraints

- Aucun fait, date, horaire, téléphone ou coordonnée inventé ou non confirmé ; info incertaine : omise (`opening_hours` / `contact` vides), jamais devinée.
- Tout contenu inséré en `status = draft`, `ai_generated = false`, `reviewed_by = null`.
- Stories : markdown de 200 à 400 mots, ton namurois du brief, titre, `excerpt`, `type` parmi `place|tradition|wallon|patrimoine`.
- Hébergement mutualisé sans SSH : tout passe par seeders (`db:seed` via `_seed.php` en prod) ; aucune commande artisan requise en prod, aucune nouvelle dépendance.
- Format des modèles `Place` / `Story` inchangé.
- Chaque lot : ~10 lieux + ~10 stories, un thème (patrimoine ou bonnes adresses), en alternant.
- Bonnes adresses : uniquement des commerces dont l'existence est confirmée par une source récente.
- Pas de commit sans demande explicite de l'utilisateur.

## Review Focus

- Re-seed en prod après publication par l'utilisateur : le statut `published` et les retouches Filament ne doivent pas être écrasés (`firstOrCreate`, pas `updateOrCreate`).
- Story dont `place_slug` n'existe pas : le seeder doit échouer clairement, pas créer une story orpheline silencieuse.
- Doublon de slug entre lots ou avec les 5 lieux / 2 stories existants : détecté par le test de cohérence.
- Lieu inséré avec `opening_hours` ou `contact` partiel : pas de clé vide ni de valeur « inconnu » affichée sur la fiche.
- Coordonnées hors zone namuroise (lat/lng inversés) : le test vérifie lat 50.3–50.6, lng 4.6–5.1.

---

### Task 1: Garde-fous de cohérence du contenu

**Files:**
- Create: `tests/Feature/Content/ContentConsistencyTest.php`
- Create: `database/seeders/Concerns/SeedsDraftContent.php`

**Interfaces:**
- Produces: trait `SeedsDraftContent` avec `seedPlaces(array $places): void` et `seedStories(array $stories): void`. Chaque place : clés du modèle `Place` ; chaque story : clés de `Story` plus `place_slug` (nullable). Création seule (`firstOrCreate` sur `city_id` + `slug`), forcent `status = draft`, `source = admin` (lieux), `ai_generated = false` (stories). `seedStories` lève `RuntimeException` si `place_slug` donné est introuvable ; après création, renseigne `places.story_id` si le lieu n'en a pas.

- [ ] **Step 1: Écrire le test** `ContentConsistencyTest` avec : `test_slugs_are_unique_per_city` ; `test_new_content_is_draft` ; `test_coordinates_in_namur_area` (lat 50.3–50.6, lng 4.6–5.1) ; `test_story_place_links_resolve` ; `test_reseed_does_not_overwrite_published` (publier un lieu seedé, re-seeder, statut toujours `published`) ; `test_unknown_place_slug_throws`. Utiliser `DatabaseSeeder` + une classe de seeder factice de test appliquant le trait.
- [ ] **Step 2: Lancer** `php artisan test --filter=ContentConsistencyTest` — attendu : FAIL (trait inexistant).
- [ ] **Step 3: Implémenter le trait** dans `database/seeders/Concerns/SeedsDraftContent.php` selon l'interface ci-dessus.
- [ ] **Step 4: Relancer** le même test — attendu : PASS.

### Task 2: Lot 1 — patrimoine

**Files:**
- Create: `database/seeders/PlacesLot01Seeder.php`, `database/seeders/StoriesLot01Seeder.php`
- Create: `docs/contenu/lot-01-patrimoine-sources.md`
- Modify: `database/seeders/DatabaseSeeder.php` (ajouter les deux seeders après `StorySeeder`)

**Interfaces:**
- Consumes: `SeedsDraftContent::seedPlaces()` / `seedStories()` (Task 1).
- Produces: ~10 lieux de patrimoine (Citadelle et souterrains, beffroi, église Saint-Loup, etc. — liste exacte arrêtée après recherche) et ~10 stories reliées.

- [ ] **Step 1: Recherche web** : pour chaque lieu, noter adresse, coordonnées, site officiel, horaires (seulement si confirmés) ; pour chaque story, 2 sources au moins. Consigner URL, date de consultation et niveau de confiance dans `docs/contenu/lot-01-patrimoine-sources.md`.
- [ ] **Step 2: Rédiger** les deux seeders ; slugs sans accents, lieux et stories croisés par `place_slug`. Faits non confirmés : omis.
- [ ] **Step 3: Lancer** `php artisan test --filter=ContentConsistencyTest` — attendu : PASS avec le lot inclus.
- [ ] **Step 4: Relecture par l'utilisateur** : présenter lieux et stories avec le fichier de sources ; corriger selon retours.

### Task 3: Lot 2 — bonnes adresses

**Files:**
- Create: `database/seeders/PlacesLot02Seeder.php`, `database/seeders/StoriesLot02Seeder.php`
- Create: `docs/contenu/lot-02-bonnes-adresses-sources.md`
- Modify: `database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Consumes: le trait de la Task 1.

- [ ] **Step 1: Recherche web** : pour chaque adresse, preuve d'existence récente (site, page officielle, presse datée) ; horaires et téléphone uniquement si confirmés ; sources dans `lot-02-bonnes-adresses-sources.md`.
- [ ] **Step 2: Rédiger** ~10 lieux (bars, restaurants, marchés, commerces indé) et les stories associées (type `place`) ; pas d'avis commerciaux ni de superlatifs invérifiables.
- [ ] **Step 3: Lancer** `php artisan test --filter=ContentConsistencyTest` — attendu : PASS.
- [ ] **Step 4: Relecture par l'utilisateur** et corrections.

### Task 4: Mise en ligne

**Files:**
- Modify: aucun fichier ; procédure documentée dans `docs/contenu/PUBLICATION.md` (créer).

- [ ] **Step 1:** Documenter : déploiement habituel (CI FTP), exécution de `db:seed` (script `_seed.php` existant, à usage ponctuel puis retiré du serveur), puis publication des entrées relues depuis Filament (`PlaceResource`, `StoryResource`).
- [ ] **Step 2:** Après publication, vérifier que `/sitemap.xml` liste les nouvelles URL et que `lastmod` des index a bougé.
- [ ] **Step 3:** Lots suivants : répéter Tasks 2–3 (patrimoine / bonnes adresses en alternance, puis wallon et traditions).
