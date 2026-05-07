# CLAUDE.md — Bia Namur

> Conventions et points d'entrée du projet pour les agents Claude Code.
> Brief produit complet : `brief-bia-namur.md` (à lire en premier).
> Secrets locaux : `secrets-bia-namur.md` (gitignored, jamais commité).

---

## TL;DR

- **Produit** : Bia Namur, carnet vivant des Namurois (PWA, brief hebdo IA, carte sentimentale, stories patrimoine).
- **Stack** : Laravel 11 + PHP 8.3 + MySQL 8 + Inertia + Vue 3 + Tailwind 3.4 + Vite + vite-plugin-pwa.
- **Mode local** (S1-S2) : Laragon sur Windows, base `bia_namur_local`, URL dev `http://127.0.0.1:8000`.
- **Multi-tenant** : `city_id` sur tables métier, slug city dans l'URL (futurs Mons/Liège). Une seule city au lancement : `namur`.
- **Identité** : palette ambré `#C77F2C` + crème `#F5EDDC` + ink `#1A1410`, serif éditorial (Lora), sans (Inter Tight). Ton namurois assumé.

---

## Démarrage rapide local (Laragon)

Les binaires Laragon ne sont pas dans le PATH PowerShell par défaut. Avant chaque session :

```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;C:\laragon\bin\composer;C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;$env:PATH"
```

### Vérifier que MySQL tourne
```powershell
Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -InformationLevel Quiet
```
Si `False`, démarrer Laragon (clic Start All) ou lancer mysqld :
```powershell
Start-Process 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe' -ArgumentList '--defaults-file=C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini' -WindowStyle Hidden
```

### Lancer le projet
Deux terminaux :
```bash
# Terminal 1 — backend
php artisan serve --port=8000

# Terminal 2 — frontend (Vite hot reload)
npm run dev
```
Puis ouvrir http://127.0.0.1:8000

### Build production
```bash
npm run build      # build Vite + génère SW + manifest
php artisan optimize  # config/route/view cache
```

---

## Stack et conventions

### Backend Laravel 11
- Format migrations : `YYYY_MM_DD_HHMMSS_description.php`, `up()` ET `down()` propres.
- Multi-tenant : trait `BelongsToCity` + global scope sur tables métier (à créer en J2).
- Soft deletes sur `places`, `stories`, `briefs`.
- Queue driver `database` (pas Redis). Cache et sessions aussi en `database`.
- Logging structuré : channels `ingestion`, `ai_pipeline`, `moderation` (à configurer).
- Aucun PII dans les logs ni dans les prompts Claude. Hash SHA-256 des emails si traçabilité.

### Frontend Vue 3 + Inertia
- Composition API uniquement (`<script setup>`).
- Pages dans `resources/js/Pages/{Feature}/{Action}.vue` (ex: `Places/Show.vue`).
- Layouts dans `resources/js/Layouts/`.
- Composants partagés dans `resources/js/Components/`.
- Toujours via Inertia (pas d'API REST séparée).
- Resources/DTOs côté Laravel pour contrôler l'expo (jamais `return $model`).

### Tailwind CSS — design system Bia
- Palette : `bia.primary` (#C77F2C), `bia.cream` (#F5EDDC), `bia.ink` (#1A1410), accent `bia.accent` (#B23A48).
- Fontes : `font-serif` (Lora), `font-sans` (Inter Tight) — chargées via Bunny Fonts (alternative RGPD).
- Tailles : `text-hero`, `text-h1`, `text-h2`, `text-h3`, `text-body`, `text-caption`.
- Espacements : `space-editorial` (4rem), `space-reading` (1.5rem).
- Largeur lecture : `max-w-reading` (65ch).
- Mode sombre : `darkMode: 'class'` (palette repensée, pas inversion).
- **Aucun hardcoded color/spacing dans les composants** — toujours via tokens.

### Anti-patterns à refuser (cf. agent ux-ui-namur)
- Carrousel sur la home, sticky banner d'inscription, emoji UI, CTA flashy, lorem ipsum, footer dense WordPress, dark patterns FOMO.

---

## Pipelines IA (Claude Anthropic SDK)

- Package PHP : `anthropic-ai/sdk` (à installer en J5/S2).
- Modèle défaut : `claude-sonnet-4-6`. Modèle stories complexes : `claude-opus-4-7`.
- Prompts versionnés dans `config/bia.php` (`prompts.brief_v1`, `prompts.story_v1`, `prompts.moderation_v1`).
- Wrapper `App\Services\Ai\ClaudeApiService` avec retry 3x + logging dans table `ai_runs`.
- Mode mock (`BIA_AI_MOCK_MODE=true` en local) → retourne fixtures, jamais d'appel réseau, pas de coût.
- **JAMAIS de PII dans les prompts** (pas d'email, pas de nom utilisateur).

---

## Sécurité — points critiques

- Clé Anthropic (`ANTHROPIC_API_KEY`) : `.env` uniquement, jamais frontend, jamais commitée.
- Magic link auth : tokens Str::random(64) hashés en DB, expire 15 min, rate limit 3/email/h, lien à usage unique.
- Photos uploads : suppression EXIF (géoloc), MIME validation (magic bytes), UUID rename, max 10 MB.
- Anti-spam contributions : 3/user/24h + Cloudflare Turnstile (S3) + score Claude.
- Headers sécu via middleware `SecurityHeaders` (CSP, HSTS, X-Frame-Options, Permissions-Policy).
- RGPD : magic link only, export `/me/export`, suppression `/me/delete`, opt-in notif/géoloc explicites.

Détail complet : `.claude/agents/security-namur.md`.

---

## Agents Claude Code spécialisés

5 agents adaptés au projet dans `.claude/agents/` :

| Agent | Quand l'utiliser |
|---|---|
| `backend-namur` | Migrations, models, jobs, services Laravel, scheduler, intégration Anthropic SDK |
| `security-namur` | Audits sécurité, revue avant merge, RGPD, headers, sanitization |
| `ux-ui-namur` | Toute création de composant Vue, page, validation visuelle, audit accessibilité |
| `qa-testing-namur` | Plans de test, fixtures pipelines IA, tests Pest, Lighthouse CI |
| `deployment-namur` | (Standby S1-S2) FTP shared hosting, GH Actions, Cloudflare/R2/Sentry — **PAS en S1** |

---

## Scripts utiles

```bash
# Migrer + rollback
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed   # destructif : drop all + reseed

# Cache (à clear pendant le dev si comportement bizarre)
php artisan optimize:clear

# Queue (lancer manuellement quand on teste les jobs)
php artisan queue:work --max-time=55

# Pest (à activer en J7)
./vendor/bin/pest

# Pint (linter PHP)
./vendor/bin/pint

# Build prod
npm run build

# Dev hot reload (Vite)
npm run dev
```

---

## Photos par défaut + override perso

Bia Namur utilise un système deux niveaux pour les photos de couverture (lieux + stories).

### Niveau 1 — Override perso (priorité)
Dépose une photo dans `public/images/places/{slug}.{webp|jpg|png}` ou `public/images/stories/{slug}.{webp|jpg|png}` et elle remplace automatiquement la photo par défaut. Aucun crédit affiché (assume copyright Bia Namur ou contribution sous CGU).

Exemples :
```
public/images/places/le-bia-bouquet.jpg          # ta photo perso du bistrot
public/images/places/citadelle-de-namur.webp     # ta photo perso de la Citadelle
public/images/stories/lorigine-du-bia-bouquet.jpg
```

Format recommandé : 1600×900 (aspect 16:9) en `.jpg` qualité 82 ou `.webp`. Le helper `App\Support\PhotoResolver` cherche les extensions dans cet ordre : `webp`, `jpg`, `jpeg`, `png`.

### Niveau 2 — Photos par défaut (fallback Wikimedia Commons)
Si aucune photo perso n'est trouvée, le helper utilise les photos déclarées dans `config/bia-photos.php` (sourcing Wikimedia Commons, licences CC BY 2.0 / CC BY-SA 3.0). Le crédit photo + lien licence sont affichés automatiquement via le composant `<PhotoCredit />`.

Pour ajouter une nouvelle photo par défaut :
1. Trouve un fichier sur [commons.wikimedia.org](https://commons.wikimedia.org) avec licence CC BY ou CC BY-SA
2. Télécharge-le dans `public/images/defaults/places/{slug}.jpg` ou `public/images/defaults/stories/{slug}.jpg`
3. Lance `node scripts/optimize-photos.mjs` pour générer les variantes WebP 800/1600 + JPG optimisé
4. Ajoute l'entrée dans `config/bia-photos.php` avec `path`, `alt`, `credit`, `license`, `license_url`, `source_url`
5. `php artisan optimize:clear`

---

## ✅ Semaine 1 — bilan complet

| Jour | Livré |
|---|---|
| J1 | Bootstrap Laravel 11 + Inertia + Vue 3 + Tailwind 3.4 (palette Bia) + `vite-plugin-pwa` |
| J1.5 | Audit UX agent + corrections (header sobre, eyebrow namurois, tokens dark mode) |
| J2 | Schéma BDD complet : 13 migrations, 11 modèles Eloquent + trait `BelongsToCity`, seeders city Namur + entitlements + admin local |
| J3 | Auth magic link custom (token sha256, 15 min, rate limit) + Filament 3 admin avec brand Bia Namur + 4 ressources (Lieux, Stories, Briefs avec RelationManager items, Contributions) |
| J4 | Icônes PWA générées via sharp (192/512/maskable/apple-touch + favicons) + 3 composants prioritaires (`<EditorialHero>`, `<PlaceCard>`, `<DataAttribution>`) + page démo `/dev/components` |
| J4.5 | Fix PWA : `<link rel="manifest">` dans Blade + `registerSW()` dans app.js → installable Chrome OK |
| J5 | `ClaudeApiService` + `ClaudeCompletion` DTO + `GenerateBriefJob` (mode mock S1) + commande `bia:brief:generate-test` + 12 tests Pest. Pipeline brief hebdo fonctionne en mock avec logging `ai_runs` (cost 0,016 USD/run mocké) |
| J6 | 10 routes publiques (Home, Briefs, Lieu, Story, Carte stub) + 5 controllers + 4 Resources DTO + 5 lieux fondateurs seedés + 2 stories patrimoine main-écrites + composants `<BriefList>` + `<StoryArticle>` lettrine |
| J6.5 | Photos par défaut Wikimedia Commons CC + override perso : `App\Support\PhotoResolver` + `<PhotoCredit>` + 3 photos optimisées (Citadelle, Cathédrale, Confluent) avec variantes WebP 800/1600 |
| J7 | Tests Pest élargis (39 tests / 249 assertions verts) + Laravel Pint + GitHub Actions CI (lint + test MySQL 8 + build Vite) + dossier BOIP préparé + bilan S1 + handoff S2 |

### Livrables S1
- **Repo GitHub** `bia-namur` avec `main` propre, 12 commits cohérents
- **App Laravel** fonctionnelle sur `http://127.0.0.1:8000` avec :
  - Brief 2026-W19 visible avec photo + 6 items en mode mock
  - 5 lieux fondateurs avec photos CC sourcées
  - 2 stories patrimoine avec lettrines ambrées
  - Page carte stub + liste lieux géolocalisés
- **Filament admin** sur `/admin` avec magic link auth, 4 ressources, palette ambré custom
- **Tests Pest** : 39 tests, 249 assertions, BDD `bia_namur_testing` dédiée
- **CI GitHub Actions** prêt : lint Pint + Pest + build Vite + manifest check
- **PWA** installable Chrome, manifest + SW + icônes maskable
- **Système photos** 2 niveaux (override perso / défauts Wikimedia)
- **Dossier BOIP** prêt à déposer : `BOIP-DOSSIER.md`

---

## ➡️ Handoff Semaine 2 — priorités

### Bloc 1 — Activation Claude réel (à faire en premier)
Le SDK `anthropic-ai/sdk` est installé et le `ClaudeApiService` a un `callApi()` stub qui throw. À implémenter :
1. Brancher le client SDK dans `callApi()` avec retry 3x + backoff exponentiel selon `config('bia.ai.max_retries')`
2. Timeout 60s configurable
3. Extraction text + `usage.input_tokens` + `usage.output_tokens` du payload Anthropic
4. **Tester en local avec un seul appel** sur 1 brief réel pour valider le ton avant de désactiver le mock
5. Bascule progressive : `BIA_AI_MOCK_MODE=false` en local d'abord, puis en staging quand on l'aura

### Bloc 2 — Carte Maplibre fonctionnelle
La page `/carte` est actuellement un stub. À faire :
1. `npm install maplibre-gl` + composant `<MapView>` Vue 3
2. Tiles : commencer avec OSM puis basculer MapTiler si besoin de style custom (clé MapTiler gratuite jusqu'à 100k tiles/mois)
3. Style custom Bia : palette ambré/crème/encre via JSON style Maplibre
4. Marqueurs : pin ambré custom avec icône type de lieu
5. Popup compact au clic : photo miniature + nom + bouton "voir la fiche"
6. Filtres : type, quartier, mood (chips au-dessus de la carte)
7. Geolocation API : "autour de moi" avec consentement explicite

### Bloc 3 — Pipelines ingestion sources externes
Le brief §7.1 décrit l'orchestration. À développer :
- `App\Services\Ingestion\OpenDataNamurService` — wrapper API `data.namur.be`
- `App\Services\Ingestion\RssIngestService` — feeds Le Delta, Belvédère, Théâtre Royal, KIKK, Citadelle, MCN
- `App\Jobs\IngestSourcesJob` (orchestrator hourly) + sub-jobs par source
- `App\Jobs\NormalizeEventsJob` : dédoublonnage (similarity 0.85), géocodage Nominatim, catégorisation
- Scheduler dans `routes/console.php` : ingestion horaire + brief vendredi 14h + auto-publish lundi 09h si pas relu

### Bloc 4 — Frontend pages restantes
- Form de contribution `/contribuer` + `App\Jobs\ModerateContributionJob` (pipeline IA dédié) + intégration Filament
- Page `/wallon` dédiée vocabulaire wallon namurois (cf. brief annexe C + `config/bia.php` `wallon`)
- Page `/a-propos` avec tagline + valeurs + équipe
- Pages légales : Mentions légales, CGU, Politique de confidentialité (cf. brief §14)

### Bloc 5 — Achats & comptes (admin perso)
- [ ] Acheter `bianamur.be` + `bianamur.app` + `bianamur.eu` (registrar Gandi/OVH)
- [ ] Déposer marque BOIP (cf. `BOIP-DOSSIER.md`)
- [ ] Créer comptes Cloudflare (proxy + R2) + Sentry + Plausible + provider SMTP (Mailgun/Postmark)
- [ ] Choisir hébergement mutualisé final (PHP 8.3 + MySQL 8 confirmés)
- [ ] Compléter `secrets-bia-namur.md` au fur et à mesure

### Bloc 6 — S3 (déploiement)
Rien à faire en S2 sur le déploiement. L'agent `deployment-namur` (currently standby) prend le relais en S3 avec le workflow GH Actions FTP qui injectera les secrets via `sed` ancré.

---

## Calendrier S1 (clos)

---

## Décisions techniques (à figer)

- **Tailwind 3.4** retenu pour stabilité (pas Tailwind 4 alpha) — possible migration en S5+.
- **vite-plugin-pwa 1.3** : SW généré automatiquement, manifest custom.
- **Auth magic link custom** (pas Laravel Fortify standard) — table `magic_links` à créer en J3.
- **Mailpit** Laragon sur 127.0.0.1:1025 pour les emails locaux.
- **Bunny Fonts** au lieu de Google Fonts (plus respectueux RGPD).
- **Maplibre GL** (pas Mapbox/Google Maps) — open source, OSM/MapTiler tiles.

---

## Repo & branches

- Repo : https://github.com/thibautmarchal-svg/bia-namur
- Branche principale : `main`
- Convention commits : `chore:`, `feat:`, `fix:`, `docs:`, `refactor:`, `test:` + ton namurois bienvenu si pertinent.
- Pas de force-push sur `main`.
- Dependabot actif (à configurer en S2).
