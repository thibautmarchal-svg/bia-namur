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

## Calendrier S1 — état d'avancement

- ✅ J1 : Bootstrap Laravel 11 + Inertia + Vue 3 + Tailwind 3.4 (palette Bia) + vite-plugin-pwa + page d'accueil rendue HTTP 200
- ⏳ J2 : Schéma BDD complet (12 tables) + multi-tenant `BelongsToCity` + seeder city Namur
- ⏳ J3 : Auth magic link + Filament 3 + Spatie Permission + ressources base
- ⏳ J4 : Layout app + 3 composants prioritaires + icônes PWA depuis SVG
- ⏳ J5 : `ClaudeApiService` + `GenerateBriefJob` (mode mock)
- ⏳ J6 : Pages publiques (lieu, story, carte stub) + 5 places fondateurs + 2 stories rédigées main
- ⏳ J7 : Pest setup + GitHub Actions CI (lint + test, pas deploy) + dossier BOIP préparé

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
