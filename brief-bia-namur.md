# Bia Namur — Brief produit & technique

> **Document de référence pour le démarrage du projet via Claude Code.**
> Toute décision prise ici est révisable, mais sert de base d'exécution.
> Version 1.3 — mai 2026.
> *Mise à jour 1.3 : agent ux-ui-namur enrichi (design system Tailwind, principes éditoriaux, anti-patterns, composants prioritaires, références d'inspiration, exigences PWA détaillées). Prêt pour démarrage Claude Code.*

---

## 1. Vision

**Bia Namur** est le carnet vivant des Namurois : un compagnon hyperlocal éditorial qui mélange brief hebdo curaté, carte sentimentale des bonnes adresses, et stories du patrimoine. Pas un agenda exhaustif, pas un site touristique : une **expérience locale incarnée**, alimentée à 95 % par un pipeline IA autonome avec une supervision humaine de 15-20 minutes par semaine.

**Positionnement.** Pour les Namurois (et ceux qui aiment Namur), pas pour les touristes de passage. Ton chaleureux, précis, fier sans être cocardier, avec quelques mots de wallon namurois assumés.

**Extensibilité.** La marque Bia se décline ville par ville : *Bia Mons*, *Bia Liège*, *Bia Tournai*. Chaque ville reçoit son produit dédié — pas une marketplace fade. Le code est multi-tenant dès le départ pour rendre les déclinaisons rapides.

**Modèle économique (à valider en M3-M6).** Gratuit pour tous au lancement. Plus tard, freemium léger pour notifications personnalisées, favoris, et brief enrichi. À terme, encarts éditorialisés transparents pour commerces locaux, et partenariats avec Office du Tourisme / Namur CentreVille.

---

## 2. Public cible

**Persona principal — *Le Namurois éveillé*** (35-55 ans) : habite Namur ou environs (15 km), connait sa ville mais veut être surpris, valorise les commerces indé et le patrimoine, suit Funky Feet / KIKK / Delta sur les réseaux, lit *Vivre Ici Namur* ou écoute *Vivacité Namur*.

**Persona secondaire — *Le néo-Namurois*** (25-40 ans) : nouvel arrivant (job UNamur, mutation FW-B, retour au pays), cherche à s'enraciner, ne connaît pas encore les bonnes adresses.

**Persona tertiaire — *Le visiteur récurrent*** : famille à Namur, ami régulier, expat namurois nostalgique. Usage occasionnel, fidèle.

---

## 3. Concept produit

### Trois piliers fonctionnels

#### Le brief hebdo *« Cette semaine à Namur »*
Publication chaque vendredi soir. 5 à 7 sélections curatées (un vernissage au Belvédère, un concert à Saint-Loup, le retour des asperges au marché Saint-Aubain, une brocante à Jambes, une expo à la Citadelle…). Pas l'agenda exhaustif — une **sélection avec un ton**. Archive consultable.

#### La carte sentimentale
Carte interactive des bonnes adresses : terrasses au soleil le matin, banc avec vue sur le confluent, boulangerie ouverte le dimanche, bar à pèkèt méconnu, librairie indé, boucher de quartier. Filtrable par catégorie, mood, quartier. Chaque lieu a sa fiche avec photo, story, infos pratiques. Alimentée au lancement par l'admin (50 lieux fondateurs) puis enrichie par contributions utilisateurs (modération auto + curation).

#### Les stories de Namur
Récits de patrimoine et anecdotes par lieu : la rue Saintraint, l'origine du Bia Bouquet, les souterrains de la Citadelle, le wallon namurois (vocabulaire, expressions). Les archives de la ville (cartes postales d'époque dans l'OpenData) servent à enrichir avec des photos avant/après. Section dédiée + intégration dans les fiches lieux.

### Modes saisonniers
Activation automatique autour des grands rendez-vous : Fêtes de Wallonie (3e weekend de septembre), KIKK Festival, marché de Noël, Nuit des Musées… Programme + carte spéciale + ton dédié.

---

## 4. Architecture cross-platform : PWA

**Décision : une PWA unique pour iPhone, Android, et PC.**

### Pourquoi cette décision

- **iPhone (iOS 16.4+ et iOS 17+)** : installable depuis Safari (Ajouter à l'écran d'accueil), notifications push web supportées, expérience plein écran sans barre Safari.
- **Android (Chrome, Edge, Brave, Samsung Internet)** : support natif maximal, expérience indistinguable d'une app native, badge sur l'icône, notifications push complètes.
- **Desktop (Chrome, Edge, Brave sur Windows/Mac/Linux)** : installable en fenêtre dédiée depuis la barre d'adresse, propre accès depuis le menu Démarrer / Launchpad / dock.

### Ce qu'on évite
- Compte Apple Developer (99 €/an) et Google Play Developer (25 € à vie) inutiles
- Process de validation des stores (1 à 2 semaines de blocage à chaque release)
- Double codebase (Swift + Kotlin) ou framework hybride (React Native, Flutter)
- Mises à jour bloquées par les reviews

### Ce qu'on gagne
- Déploiement instantané depuis FTP (le workflow que tu maîtrises)
- Une seule codebase
- URL partageable directement (`bianamur.be/lieu/quai-de-meuse`)
- SEO côté résident (Google indexe les contenus, contrairement aux apps natives)

### Limites assumées
- Pas d'accès aux APIs natives très spécifiques (NFC, healthkit, etc.) — non nécessaires pour ce produit
- Notifications push iOS limitées (taille de payload, action buttons restreints) — acceptable
- Pas de présence dans les stores — soft cost en visibilité, mais on lance sans cette béquille

---

## 5. Stack technique

### Backend
- **Laravel 11** (ton stack par défaut) avec PHP 8.3+
- **MySQL 8** pour la base de données principale
- **Filament 3** comme panel admin (curation, modération, monitoring pipelines, gestion utilisateurs)
- **Inertia.js** comme pont serveur ↔ frontend (pas d'API REST séparée à maintenir)
- **Laravel Scheduler + Queue (database driver)** pour les jobs cron et asynchrones — compatible shared hosting FTP
- **Spatie Permission** pour rôles (admin, contributeur, lecteur)

### Frontend
- **Vue 3** (Composition API) sur Inertia
- **Tailwind CSS 4** (typo, couleurs, espacements)
- **Vite + vite-plugin-pwa** pour le service worker, le manifest et les icônes
- **Workbox** (intégré au plugin) pour la stratégie de cache offline
- **Maplibre GL** pour la carte (open-source, gratuit, performances natives, fonds OpenStreetMap ou MapTiler)
- **Lucide Vue Next** pour les icônes
- **Headless UI Vue** pour les composants accessibles (modales, dropdowns, tabs)

### Pipelines IA
- **Anthropic SDK officiel PHP** (`anthropic-ai/sdk`) pour appels Claude API
- **Modèle par défaut** : `claude-sonnet-4-6` (rapport qualité/prix optimal pour rédaction)
- **Modèle pour stories complexes** : `claude-opus-4-7` (qualité supérieure, usage parcimonieux)
- **Embeddings** : Voyage AI ou alternatives pour similarité de lieux (V2)

### Stockage médias
- **Cloudflare R2** (S3-compatible, gratuit jusqu'à 10 GB stockage et 1M requêtes/mois)
- **Intervention Image** côté Laravel pour resize + WebP automatique à l'upload
- Variantes générées : 200×200 (thumb), 800×800 (card), 1600×1600 (full)

### Authentification
- **Magic link par email** via Laravel Fortify (pas de mot de passe)
- **Optionnel V2** : Google OAuth, Apple Sign In
- Sessions courtes (30 jours) avec refresh transparent

### Déploiement
- **Hébergement mutualisé FTP** (ton stack actuel)
- **GitHub Actions** : build + deploy automatique sur merge en `main`
- **Gestionnaire de schedule** : `php artisan schedule:run` lancé toutes les minutes par cron mutualisé
- **Domaine** : `bianamur.be` (à vérifier disponibilité, voir checklist § 12)
- **Certificat SSL** : Let's Encrypt via cPanel hébergeur

### Observabilité
- **Sentry** (plan gratuit) pour erreurs PHP + JS
- **Plausible** (auto-hébergé ou cloud) pour analytics RGPD-friendly, sans cookies

---

## 6. Modèle de données

### Tables principales

```
users
  id, name, email, magic_link_token, role, locale
  preferences (JSON: notif_brief_hebdo, notif_proximity, etc.)
  created_at, updated_at

cities (multi-tenant pour extension future)
  id, slug ('namur', 'mons'), name, latitude, longitude
  bounding_box, primary_color, founder_admin_id

places
  id, city_id, slug, name, type (cafe, restaurant, bar, library, etc.)
  description (court, 200 chars max)
  story_id (FK vers stories, nullable)
  latitude, longitude
  address, neighborhood, opening_hours (JSON)
  contact (JSON: phone, email, website, instagram)
  tags (JSON: ['terrasse', 'matin', 'famille', 'bio'])
  cover_photo_id, photos (JSON FK)
  source ('admin', 'opendata', 'contribution')
  status ('draft', 'published', 'archived')
  created_at, updated_at

events
  id, city_id, source ('opendata', 'rss_delta', 'rss_belvedere', etc.)
  external_id (déduplication)
  title, description, full_text
  starts_at, ends_at, recurrence
  place_id (FK, nullable), venue_name, address
  category (JSON), price_info, url, image_url
  raw_payload (JSON: données brutes de la source)
  ingested_at, status

briefs
  id, city_id, week_number, year
  title, intro_text, generated_at
  status ('draft_ai', 'pending_review', 'published', 'archived')
  reviewer_id, reviewed_at
  selected_event_ids (JSON)

brief_items
  id, brief_id, event_id (FK, nullable), place_id (FK, nullable)
  position (ordre dans le brief)
  ai_text, edited_text (si modifié par admin)
  reasoning (JSON: pourquoi cet item a été sélectionné)

stories
  id, place_id (FK, nullable), city_id
  type ('place', 'tradition', 'wallon', 'patrimoine')
  title, slug, content (markdown), excerpt
  cover_photo_id, photos (JSON)
  ai_generated (bool), ai_model, ai_prompt_version
  reviewed_by, reviewed_at, status

contributions
  id, user_id, type ('place_suggestion', 'photo', 'correction', 'story_proposal')
  payload (JSON), target_place_id, target_story_id
  ai_score (0-100), ai_reasoning (JSON)
  status ('pending', 'auto_approved', 'manual_review', 'rejected', 'merged')
  reviewer_id, reviewed_at

photos
  id, uploadable_type, uploadable_id (polymorphic)
  filename, path, mime_type, size
  width, height, variants (JSON: thumb/card/full URLs)
  uploaded_by, license, credit
  created_at

ai_runs (logs de pipeline pour debug + cost tracking)
  id, type ('brief_weekly', 'story_generation', 'contribution_moderation')
  model_used, prompt_template_version
  input_tokens, output_tokens, cost_usd
  duration_ms, status, error_message
  related_id (polymorphic), created_at
```

### Notes de modélisation
- **Multi-tenant via `city_id`** dès le départ : prêt pour Mons/Liège sans refacto
- **Polymorphic photos** : un même système gère photos de lieux, stories, briefs
- **`ai_runs` est critique** : suivi des coûts API + debug + statistiques qualité
- **Soft deletes** activés sur places, stories, briefs (jamais perdre de contenu curé)

---

## 7. Pipelines IA — fonctionnement détaillé

### 7.1 Pipeline brief hebdo

**Cron** : tous les vendredis 14h00.

**Étapes** :

1. **Collecte** (job `IngestSourcesJob` lancé toutes les heures, mais l'agrégation se déclenche le vendredi)
   - OpenData Namur : `https://data.namur.be/api/records/1.0/search/?dataset=namur-agenda-des-evenements`
   - RSS Le Delta, Belvédère, Théâtre Royal, Citadelle, KIKK, Centre Culturel, UNamur, Maison de la Culture
   - Quefaire.be (filtre Namur)
   - Visit Namur (scraping respectueux : 1 req/s, User-Agent identifié)
   - L'Avenir Namur (RSS), Vivacité Namur (RSS)

2. **Normalisation** (`NormalizeEventsJob`)
   - Dédoublonnage par titre + date + lieu (algorithme : similarity score > 0.85)
   - Géocodage si adresse fournie sans coords
   - Catégorisation auto (mots-clés + Claude si ambigu)

3. **Sélection IA** (`GenerateBriefJob` — Claude API)
   - Prompt système : voir § 7.4
   - Input : tous les événements de la semaine à venir (lundi → dimanche), avec leur catégorie, lieu, prix, popularité estimée, récence
   - Output : 5-7 événements sélectionnés + reasoning par sélection + brief rédigé

4. **Notification reviewer** (toi)
   - Email avec preview du brief + lien magique de validation
   - Si non validé avant lundi 09h, auto-publication avec mention « édition automatique non relue »

5. **Publication**
   - Status passe à `published`
   - Notification push aux abonnés (segmentation par préférences)
   - Indexation SEO (sitemap.xml, schema.org Event)

### 7.2 Pipeline stories

**Déclenchement** : à la création d'un nouveau lieu, ou en batch initial (50 lieux fondateurs).

**Étapes** :

1. **Collecte de contexte** (`GatherStoryContextJob`)
   - Wikipedia FR (extraction du contenu pertinent au lieu)
   - Recherche web ciblée (DuckDuckGo HTML, ou API Brave Search)
   - Archives OpenData Namur : photos historiques, cartes postales
   - Connexions avec d'autres lieux/stories existants

2. **Génération** (`GenerateStoryJob` — Claude API)
   - Prompt système : ton « raconteur namurois », chaleureux et précis
   - Few-shot examples : 5-10 stories de référence (que tu rédigeras au lancement)
   - Output : story en markdown 200-400 mots, avec sections optionnelles (anecdote, histoire, contexte)

3. **Validation** :
   - Stories AI sont en `status = 'draft'`
   - Toi tu valides en lot dans Filament admin (5-10 min)
   - Une fois validées, deviennent `published`

### 7.3 Pipeline modération contributions

**Déclenchement** : à chaque soumission utilisateur (form `Contribuer`).

**Étapes** :

1. **Validation technique** (Laravel)
   - Champs obligatoires, taille des photos, formats acceptés
   - Anti-spam : rate limit (3 contributions / utilisateur / jour), captcha invisible

2. **Score IA** (`ModerateContributionJob` — Claude API)
   - Prompt système : critères de qualité, ton attendu, détection pub déguisée, doublons potentiels
   - Output : score 0-100 + reasoning + suggestion d'action (approve/review/reject)

3. **Routage** :
   - Score > 75 → auto-approuvée, publiée
   - Score 40-75 → file d'attente review (visible dans Filament)
   - Score < 40 → rejetée avec feedback poli envoyé au contributeur

### 7.4 Prompt système — exemple pour le brief hebdo

```
Tu es l'éditorialiste de Bia Namur, le carnet vivant des Namurois.
Tu rédiges chaque semaine un brief de 5 à 7 sélections culturelles et
sorties pour la semaine à venir.

TON :
- Chaleureux, précis, jamais condescendant
- Fier sans être cocardier ; le namurois reconnaît son ton sans qu'il
  soit caricatural
- Quelques mots de wallon namurois autorisés et bienvenus quand le
  contexte s'y prête : "à l'aise", "bia", "tchafyî", "biesse" — mais
  pas plus de 1-2 par brief, pour rester accessibles aux néo-namurois.
- Phrases courtes ou moyennes. Pas de superlatifs vides ("incroyable",
  "à ne pas rater absolument"). Décris ce qui rend le truc spécifique.

STRUCTURE DU BRIEF :
- Une intro de 2-3 phrases qui campe la semaine (saison, météo
  attendue, énergie générale).
- 5 à 7 sélections, chacune en 2-3 phrases :
  * Quoi (titre événement)
  * Où (lieu nommé, sans adresse)
  * Quand (date + heure)
  * Pourquoi celui-là (l'angle qui le rend intéressant)
- Une clôture optionnelle d'1 phrase.

CRITÈRES DE SÉLECTION :
- Diversité : pas 5 concerts, pas 5 expos. Mixer musique, expo,
  gastronomie, sport doux, marché, patrimoine, famille.
- Pertinence locale : privilégier ce qui est ancré namurois plutôt
  que les grosses tournées génériques.
- Rareté : un événement unique > un événement récurrent.
- Accessibilité : varier les prix (du gratuit au payant).

NE PAS :
- Rédiger une accroche artificielle ("Cette semaine, Namur vibre...")
- Utiliser des emojis
- Mentionner Bia Namur dans le brief lui-même
- Inventer des informations non présentes dans les données fournies

EXEMPLES DE STYLES À SUIVRE :
[insérer 3-5 briefs de référence rédigés à la main au lancement]

Voici les événements de la semaine du {date_debut} au {date_fin} :
{json_events}

Sélectionne les 5-7 meilleurs et rédige le brief en markdown.
```

### 7.5 Coûts estimés

- Brief hebdo : ~10 000 tokens input + 2 000 tokens output × 1/semaine = **~0,30 $/semaine**
- Stories (batch initial 50) : ~5 000 tokens × 50 = **~7,50 $ une fois**
- Stories (régulier) : ~2 nouvelles/semaine × 5 000 tokens = **~0,30 $/semaine**
- Modération contributions : ~500 tokens × 50/semaine = **~0,15 $/semaine**

**Total opérationnel : ~3 €/mois en charge IA, négligeable.**

---

## 8. Branding

### Nom : **Bia Namur**

*Bia* = beau en wallon namurois. Présent dans le célèbre *Bia Bouquet*, cérémonie traditionnelle du 3e dimanche de septembre. Court, identitaire, mémorable. Extensible : *Bia Mons*, *Bia Liège*, etc. La marque devient *Bia* tout court une fois étendue.

### Logo
Mark carré ambré (#C77F2C) avec lettre **B** en serif éditorial crème (#F5EDDC). Trois petits points en haut à droite évoquent le bouquet. Wordmark *Bia* en serif chaleureux + sous-marque *NAMUR* en sans-serif géométrique espacée + tagline italique *« Le carnet vivant des namurois »*.

### Palette de couleurs

```
Primary (chaleur namuroise)
  --color-bia-primary:    #C77F2C  /* ambré pierre */
  --color-bia-primary-dk: #8B5618  /* ambré profond */
  --color-bia-primary-lt: #E5A965  /* ambré clair */

Cream (papier)
  --color-bia-cream:      #F5EDDC  /* crème chaud */
  --color-bia-cream-dk:   #E8DDC5

Accent (bouquet floral)
  --color-bia-accent:     #B23A48  /* rouge flamboyant des Wallos */

Neutral (encre)
  --color-bia-ink:        #1A1410  /* noir chaud, pas pur */
  --color-bia-ink-soft:   #4A3F35
  --color-bia-ink-mute:   #8B7E72
```

### Typographie

- **Editorial / Wordmark / Stories long format** : *Recoleta* (Google Fonts) ou *Lora* (alternative gratuite) — serif chaleureux contemporain
- **UI / interface / brief** : *Inter Tight* (Google Fonts) — sans-serif neutre, lisible
- **Wallon / accents** : *Recoleta italic*

### Voix de marque

- *Le voisin érudit qui adore sa ville et te la raconte*
- Pas du touriste-marketing, pas du fonctionnaire, pas du blogueur lifestyle
- Précis sur les faits, généreux sur le contexte
- Mots de wallon assumés mais doux pour les néo-namurois
- Pas d'emoji, pas de superlatifs vides, pas de hype

---

## 8 bis. Licence des données OpenData Namur

### Cadre légal
Les données ingérées depuis `data.namur.be` sont sous **licence Creative Commons Attribution 4.0 (CC BY 4.0)**.

### Obligations à respecter dans Bia Namur

**Attribution visible**

Le footer du site (et page « Mentions légales ») doit contenir explicitement :

```
Données issues de la plateforme OpenData de la Ville de Namur
(data.namur.be), mises à disposition sous licence CC BY 4.0.
```

Idéalement : un lien direct vers `https://data.namur.be/pages/licence/`.

**Disclaimer sur les fiches événements / lieux issus de l'OpenData**

La Ville indique : *« les informations communiquées sur l'Open Data n'ont aucune valeur officielle et n'engagent pas la Ville de Namur. »*

→ Sur les fiches issues de l'OpenData, ajouter une mention discrète :
> *Informations fournies à titre indicatif. Vérifiez auprès de l'organisateur avant déplacement.*

**Logo de la Ville de Namur — interdiction**

Le logo officiel de la Ville de Namur est protégé séparément. **Ne pas l'utiliser** dans Bia Namur (logo, header, partenariats affichés) sans autorisation écrite. Aucun intérêt produit de toute façon.

**Pas de données personnelles**

La Ville confirme que la plate-forme Open Data ne contient pas de données personnelles → pas d'obligation RGPD spécifique sur les données ingérées (le RGPD s'applique uniquement aux utilisateurs de Bia Namur eux-mêmes, voir section 10).

### Implémentation
- Composant footer permanent avec attribution
- Helper Blade/Vue `<DataAttribution :source="$source" />` qui affiche le bon disclaimer selon la source du contenu
- Mentions légales accessibles depuis toutes les pages
- Page CGU avec rappel de la licence des données tierces

---

## 8 ter. Défense compétitive — protéger Bia Namur

### Principe directeur
**On ne peut pas empêcher quelqu'un de copier le concept**. Mais on peut rendre la copie non rentable et toujours en retard. Les défenses réelles, par ordre d'importance.

### 1. La communauté locale (le vrai moat)
À mesure que la marque devient reconnue chez les Namurois, un copycat doit reconstruire sa communauté de zéro. **Cette défense seule justifie 80 % de la sécurité du projet** — elle se construit en exécutant bien dès les premiers mois.

### 2. Marque déposée BOIP
**Étape prioritaire — semaine 1.**

- Office Bénélux de la Propriété Intellectuelle (BOIP)
- Classes à enregistrer :
  - **Classe 9** : applications, logiciels téléchargeables
  - **Classe 35** : services de publicité et information commerciale en ligne
  - **Classe 41** : édition électronique, services de loisirs et culturels
- Coût : **~250 € à 400 €** pour 10 ans, territoire Bénélux (Belgique + NL + Lux)
- Procédure en ligne : `boip.int`
- À déposer **avant** la communication publique du nom

### 3. Droit d'auteur sur le contenu éditorial
**Automatique dès publication** (pas de dépôt requis en Belgique). Protège :
- Briefs hebdomadaires (textes éditoriaux)
- Stories de patrimoine
- Descriptions des lieux rédigées par toi ou validées par toi
- Photos prises ou commandées

→ Un copycat qui copie tes textes est attaquable. Un copycat qui réécrit doit produire le même volume éditorial pour rattraper.

→ Dans les CGU : clause explicite *« Les contenus éditoriaux de Bia Namur sont protégés. Toute reproduction, totale ou partielle, sans autorisation écrite est interdite. »*

### 4. Partenariats locaux exclusifs
À développer dès le mois 3-6 :
- Office du Tourisme de Namur
- Namur CentreVille (asbl des commerçants)
- Maison de la Culture, Le Delta, Belvédère
- Groupes de parents, associations locales

→ Un statut de *« partenaire éditorial officiel »* crée une barrière concurrentielle réelle.

### 5. Données utilisateurs propriétaires
Les contributions, favoris, signalements, photos uploadées par les utilisateurs deviennent ton corpus propriétaire. Un copycat repart d'une base vide.

→ Soigner l'incitation à contribuer dès le M1 (gamification légère, reconnaissance des contributeurs actifs).

### 6. Vitesse d'exécution
Tu sors les premières features. Tu es perçu comme le pionnier de référence. Un copycat lance toujours *« comme l'autre, mais en moins bon »* et perd sur le terrain mental.

→ Cadence de release régulière (au moins 1 nouveauté visible par mois).

### 7. Contre-mesures techniques (faibles, mais utiles)
- **Cloudflare** devant le site avec rate limit agressif sur les endpoints publics
- **API tokens** rotatifs et limités sur l'API interne
- **Watermark invisible** (stéganographie légère) sur les photos uploadées
- **Robots.txt** : autoriser indexation moteurs (SEO) mais bloquer scrapers connus (`SemrushBot`, `AhrefsBot`, etc.)
- **CGU explicites** : clause de non-scraping et de non-réutilisation commerciale

### Ce qui n'est PAS protégeable
- L'idée *« guide local éditorial alimenté par IA »*
- Le concept de carte sentimentale
- L'usage de l'OpenData (publique par définition)
- L'architecture technique générale

### Checklist semaine 1
- [ ] Vérifier dispo nom *Bia Namur* sur BOIP (`boip.int/recherche`)
- [ ] Vérifier dispo nom de domaine `bianamur.be`, `bianamur.app`, `bianamur.eu`
- [ ] Déposer marque BOIP classes 9, 35, 41
- [ ] Rédiger CGU avec clauses anti-copie et non-scraping
- [ ] Configurer Cloudflare devant le domaine (plan gratuit suffit)
- [ ] Footer : attribution CC BY data.namur.be + copyright Bia Namur

---

## 9. Adaptation des 5 agents Claude Code

Les 5 agents de référence (`~/.claude/agents`) sont adaptés au contexte du projet Bia Namur. À placer dans `.claude/agents/` du projet pour surcharge locale.

### 9.1 Agent `backend-namur`
**Spécialisation Laravel 11 + ingestion OpenData + pipelines IA**

Responsabilités :
- Models Eloquent multi-tenant (city scope global)
- Jobs queue : `IngestSourcesJob`, `GenerateBriefJob`, `GenerateStoryJob`, `ModerateContributionJob`
- Service classes : `OpenDataNamurService`, `RssIngestService`, `ClaudeApiService`, `GeocodingService`
- API interne pour la carte (Maplibre + GeoJSON tiles dynamiques)
- Architecture : Repository pattern pour les sources externes (OpenData, RSS, Quefaire.be)
- Schedules (`app/Console/Kernel.php` ou `routes/console.php`) : ingestion horaire, brief vendredi 14h, modération continue

Conventions :
- Tous les jobs en queue avec retry + backoff exponentiel
- Logging structuré (`Log::channel('ingestion')`, `Log::channel('ai_pipeline')`)
- Feature flags via config (`config/bia.php`) pour activer/désactiver des sources

### 9.2 Agent `security-namur`
**Spécialisation OWASP + RGPD + protection clés API**

Responsabilités :
- Audit OWASP Top 10 sur tous les endpoints (en particulier formulaires de contribution)
- RGPD complet : consentement géoloc, export données utilisateur, droit à l'oubli, registre des traitements
- Protection clé Anthropic API : env vars, rotation, rate limit interne pour éviter les abus
- Headers sécurité : CSP stricte (Maplibre + Cloudflare R2 whitelistés), HSTS, X-Frame-Options
- Anti-spam contributions : rate limit, captcha invisible (Cloudflare Turnstile), heuristiques + Claude moderation
- Magic link auth : tokens à usage unique, expiration 15 min, lien par IP
- Photos uploads : scan EXIF (suppression géoloc cachée), MIME validation stricte, file scanning

Points de vigilance :
- L'OpenData Namur est public, mais respecter rate limits et User-Agent identifié
- Ne jamais exposer la clé Anthropic au client (frontend)
- Hashing des emails dans les logs (RGPD)
- Backup quotidien BDD + R2 médias

### 9.3 Agent `ux-ui-namur`
**Spécialisation éditorial chaleureux + WCAG AA + mobile-first PWA + identité Bia forte**

**Mission de l'agent**

Bia Namur est un produit **éditorial**, pas une app utilitaire. Le design doit donner envie de revenir, pas seulement de consulter. Chaque écran doit ressembler à une page de carnet de voyage soigné, pas à un dashboard SaaS. L'agent UX/UI doit toujours challenger les choix qui rendent le produit générique.

**Principes directeurs (do)**

- **Typographie respirée** — interlignage généreux (1.7 minimum sur les paragraphes), marges latérales confortables
- **Photo généreuse** — les visuels portent l'identité ; format pleine largeur fréquent, pas de timbre-poste
- **Couleur ambrée comme signature** — pas trop, mais toujours présente : titre, accent, bouton d'action principal
- **Serif éditorial** sur les titres et le wordmark, sans-serif uniquement pour l'UI fonctionnelle
- **Mode sombre travaillé**, pas juste l'inversion automatique
- **Animations subtiles et significatives** — fade transitions sur navigation, jamais d'animations gratuites
- **Espacement vertical généreux** — le produit doit *respirer*, ne pas être dense

**Anti-patterns (don't)**

- Pas de **carrousel** sur la home (tous les liens visibles, pas de slider auto)
- Pas de **sticky banner** d'inscription / cookie / promotion
- Pas d'**emoji** dans l'interface (tu peux dans les contenus éditoriaux si pertinent)
- Pas de **boutons CTA flashy** style « Découvrir maintenant ! » — un bouton ambré sobre suffit
- Pas de **"AI feel"** — l'IA est invisible, pas une feature à exposer
- Pas de **placeholder Lorem Ipsum** — toujours travailler avec du vrai contenu namurois en maquette
- Pas de **footer dense** type WordPress avec 50 liens
- Pas de **dark patterns** d'engagement (compteurs « 12 personnes regardent ce lieu », FOMO)

**Design system (Tailwind CSS 4 config)**

```js
// tailwind.config.js — extrait
theme: {
  extend: {
    colors: {
      bia: {
        primary: '#C77F2C',     // ambré pierre
        'primary-dk': '#8B5618',
        'primary-lt': '#E5A965',
        cream: '#F5EDDC',
        'cream-dk': '#E8DDC5',
        accent: '#B23A48',      // rouge Wallos (parcimonie)
        ink: '#1A1410',         // noir chaud
        'ink-soft': '#4A3F35',
        'ink-mute': '#8B7E72',
      },
    },
    fontFamily: {
      serif: ['Lora', 'Recoleta', 'Georgia', 'serif'],
      sans: ['Inter Tight', 'Inter', 'system-ui', 'sans-serif'],
    },
    fontSize: {
      // Hiérarchie éditoriale (mobile-first, scale fluide)
      'hero': ['2.5rem', { lineHeight: '1.15', letterSpacing: '-0.02em' }],
      'h1': ['2rem', { lineHeight: '1.2' }],
      'h2': ['1.5rem', { lineHeight: '1.3' }],
      'h3': ['1.25rem', { lineHeight: '1.4' }],
      'body': ['1rem', { lineHeight: '1.7' }],
      'caption': ['0.875rem', { lineHeight: '1.5' }],
    },
    spacing: {
      // Espacements éditoriaux (multiples de 4)
      'editorial': '4rem',  // entre grandes sections
      'reading': '1.5rem',  // entre paragraphes
    },
  },
}
```

**Composants UI prioritaires (à créer en S2)**

1. **`<EditorialHero />`** — bandeau d'intro pour le brief hebdo, photo pleine largeur + titre serif XL + intro 2-3 lignes
2. **`<PlaceCard />`** — fiche lieu en card : photo 4:3, nom serif, type minuscule espacé, mood tags discrets, distance optionnelle
3. **`<MapView />`** — carte Maplibre avec style custom (tons ambré/crème/encre, pas la palette OSM brute)
4. **`<StoryArticle />`** — page longue d'une story patrimoine : largeur de lecture max 65 caractères, titre serif XL, lettrine optionnelle au démarrage, photos d'archive intercalées
5. **`<BriefList />`** — liste des 5-7 sélections du brief : numérotation discrète, titre serif, contexte 2-3 phrases, infos pratiques en bas
6. **`<BottomNav />`** — navigation mobile : 4 sections max (Accueil, Carte, Stories, Compte), icônes Lucide minces, label sous icône
7. **`<TopBar />`** — barre du haut : logo Bia centré ou à gauche selon écran, icône recherche, icône profil
8. **`<ContributeForm />`** — formulaire d'ajout de lieu : minimal, photo en premier, champs guidés
9. **`<DataAttribution />`** — badge discret « Données : data.namur.be » sur les fiches OpenData
10. **`<LegalFooter />`** — footer permanent avec liens légaux et attribution

**Inspirations à étudier (pour le ton, pas la copie)**

- **The Infatuation** (theinfatuation.com) — ton éditorial pour recommandations urbaines
- **Eater** (eater.com) — typographie serif + photos généreuses
- **Le Carnet de Bord** (lecarnetdebord.fr) — guide éditorial français
- **Atlas Obscura** — stories de lieux, mise en page éditoriale
- **Are.na** — densité d'info maîtrisée, esthétique épurée
- **Field Mag** (fieldmag.com) — palette chaude, photo en hero

**Ce qu'il faut éviter en référence**

- Visit Namur, Visit Belgium, Office du Tourisme classiques (institutionnels, lourds)
- Yelp, TripAdvisor (générique, dense, pubs)
- Apps de navigation pure (Google Maps style)

**WCAG AA strict — exigences techniques**

- Contraste texte/fond minimum **4.5:1** sur le body, **3:1** sur les titres XL
- Tailles minimum : 16px sur le body
- Navigation clavier complète (Tab order logique, focus rings visibles ambrés)
- Tous les éléments interactifs ont un `aria-label` ou texte visible
- Images avec `alt` descriptifs (la story du lieu, pas juste « photo »)
- Pas de couleur seule pour porter une information (toujours doublée par icône ou texte)
- Tester avec NVDA / VoiceOver à chaque release

**PWA — exigences spécifiques**

- **Manifest** complet : name, short_name, theme_color (#C77F2C), background_color (#F5EDDC), display: standalone
- **Icônes PWA** : 192×192, 512×512, **maskable** (zone safe central de 80%)
- **Splash screen** auto-généré sur iOS depuis le manifest
- **Service worker** avec stratégies de cache adaptées :
  - Network first pour les briefs (fraîcheur)
  - Cache first pour les stories (rarement modifiées)
  - Stale while revalidate pour les fiches lieux
- **Prompt d'installation** : pas auto au premier visite (bouton « Installer Bia » dans le menu, ou suggestion contextuelle après 3 visites)
- **Mode hors ligne** : page dédiée avec dernière version du brief + favoris

**Design review — Plugin `ui-ux-pro-max`**

Activé par défaut sur le projet. Audit auto sur chaque composant avant merge :
- Contraste, taille de police, hiérarchie typographique
- Cohérence design system (jamais de hardcoded color/spacing)
- Mobile 375px, 414px, 768px, 1024px, 1440px
- Performance Lighthouse > 90 sur PWA, accessibilité, best practices

**Validation finale par écran**

Avant chaque merge sur main, l'agent doit confirmer :
- [ ] Le contenu serait beau imprimé sur du papier crème
- [ ] Aucun élément ne hurle « designed by AI »
- [ ] Un Namurois reconnaîtrait le ton éditorial
- [ ] Le contraste passe les outils Lighthouse / axe-core
- [ ] Mobile 375px parfait avant desktop
- [ ] Mode sombre soigné (pas juste inversion)
- [ ] Aucun lorem ipsum, aucun placeholder générique

### 9.4 Agent `qa-testing-namur`
**Spécialisation tests pipeline IA + ingestion + crowdsourcing**

Responsabilités :
- Tests unitaires (Pest) : services métier, normalisation données OpenData, parsing RSS
- Tests feature : flow brief hebdo de bout en bout (mock Claude API), flow contribution + modération
- Tests d'ingestion : fixtures réelles OpenData/RSS pour détecter les changements de format en amont
- Tests de prompts : suite de cas (prompt → output attendu) pour détecter les régressions de qualité IA
- Tests E2E (Pest Browser) : parcours utilisateur clés (consulter brief, ajouter favori, contribuer)
- Multi-rôle test plans : visiteur anonyme, contributeur connecté, modérateur, admin
- Performance : Lighthouse audit auto en CI (PWA score > 90, accessibilité > 95)

Cas critiques à couvrir :
- Une source RSS change de format : doit logger une erreur sans casser l'ingestion globale
- Claude API down : fallback gracieux (brief précédent reste affiché, story manquante = placeholder)
- Photo upload massive : protection mémoire + queue
- Contribution malveillante (XSS, SQL injection) : sanitization stricte avant + après Claude

### 9.5 Agent `deployment-namur`
**Spécialisation FTP shared hosting + GitHub Actions + scheduler cron**

Responsabilités :
- Workflow GitHub Actions : `lint → test → build (Vite) → deploy FTP` sur merge `main`
- Optimisation Laravel pour FTP shared : `config:cache`, `route:cache`, `view:cache`, `optimize`
- Compose `.env` géré via secrets GitHub, jamais commité
- Cron mutualisé : entrée unique `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
- Sentry release tracking : tag automatique du commit déployé
- Backup automatique pre-deploy (DB dump + R2 snapshot)
- Rollback procédure : conserver les 3 dernières versions en `releases/` avec symlink atomique

Particularités shared hosting :
- Pas de SSH : tout passe par FTP/SFTP via deployer dédié
- Pas de Redis : queue + cache en database
- Pas de supervisor : queue worker via cron `php artisan queue:work --max-time=55` lancé toutes les minutes
- PHP version pinnée à 8.3 (vérifier sur l'hébergeur)

---

## 10. Plan d'exécution — 4 semaines

### Semaine 1 : Socle technique + protection juridique
- [ ] **Vérifier dispo nom Bia Namur sur BOIP** (`boip.int/recherche`)
- [ ] **Déposer la marque Bia Namur (classes 9, 35, 41) sur BOIP** — ~300 €
- [ ] Achat domaines `bianamur.be` + `bianamur.app` + `bianamur.eu` (sécurité)
- [ ] Setup repo GitHub + Laravel 11 + Inertia + Vue 3 + Tailwind 4
- [ ] Config PWA (manifest, service worker, icônes)
- [ ] Schéma BDD + migrations
- [ ] Filament 3 admin avec ressources de base (Place, Event, Story, Brief, Contribution)
- [ ] Auth magic link
- [ ] Multi-tenant scope global (city slug en URL)
- [ ] CI GitHub Actions : lint + tests + build
- [ ] Deploy FTP automatisé sur staging
- [ ] SSL Let's Encrypt actif
- [ ] **Configuration Cloudflare** devant le domaine (plan gratuit) avec rate limit
- [ ] **Rédaction CGU + Mentions légales + Politique de confidentialité** (avec attribution CC BY)

### Semaine 2 : Pipelines IA + frontend de base
- [ ] Service `OpenDataNamurService` + ingestion programmée
- [ ] Service `RssIngestService` (5-10 sources clés)
- [ ] Service `ClaudeApiService` avec retry + logging
- [ ] Job `GenerateBriefJob` + prompt système + tests fixtures
- [ ] Job `GenerateStoryJob` + prompt système + 5 stories de référence rédigées à la main
- [ ] Frontend pages : Home (brief en cours), Liste briefs, Fiche brief
- [ ] Frontend page Carte (Maplibre, vue d'ensemble Namur)

### Semaine 3 : Contenu fondateur + pages restantes
- [ ] Rédaction de 50 lieux fondateurs (admin Filament + import CSV)
- [ ] Génération + validation des 50 stories associées (batch IA)
- [ ] Rédaction de 5-10 stories patrimoine pivots (à la main, servent d'exemples few-shot)
- [ ] Frontend pages : Fiche lieu, Story long-format, Wallon namurois
- [ ] Formulaire de contribution (Contribuer un lieu)
- [ ] Job `ModerateContributionJob` + intégration Filament
- [ ] Premier brief hebdo de test généré et validé manuellement

### Semaine 4 : Bêta privée + polish + lancement soft
- [ ] Tests E2E critiques (3-5 parcours utilisateurs)
- [ ] Audit Lighthouse + accessibilité WCAG AA
- [ ] Mode sombre vérifié sur tous les écrans
- [ ] Notifications push (web push, opt-in explicite)
- [ ] Bêta privée auprès de 20-30 namurois (voisins, Funky Feet, La Bruyère, collègues)
- [ ] Récolte feedback (formulaire intégré + appels)
- [ ] Itérations rapides
- [ ] Lancement soft (post LinkedIn perso + voisinage Hoplr + bouche-à-oreille)

---

## 11. Métriques de succès — fin du mois 1

- 50 lieux publiés et photographiés
- 10 stories patrimoine en ligne
- 4 briefs hebdomadaires publiés (semaines 1, 2, 3, 4)
- 100 utilisateurs uniques inscrits (magic link)
- 30 utilisateurs activement revenus en semaine 4
- 5 contributions utilisateurs validées
- Pipeline IA tournant en autonomie (≤30 min de supervision/semaine)
- Lighthouse PWA score > 90
- 0 incident sécurité

## 12. Checklist de démarrage avant Claude Code

### Vérifications externes
- [ ] **Dispo nom *Bia Namur* sur BOIP** (`boip.int/recherche`) — préalable absolu
- [ ] **Dépôt marque BOIP** (classes 9, 35, 41) — ~300 € pour 10 ans
- [ ] Domaine `bianamur.be` disponible (+ `bianamur.app`, `bianamur.eu` en backup défensif)
- [ ] Compte Anthropic API créé + crédit initial 20 €
- [ ] Compte Cloudflare R2 créé (ou alternative S3)
- [ ] Compte Cloudflare standard pour proxy + rate limit (plan gratuit)
- [ ] Compte Sentry plan gratuit créé
- [ ] Hébergement FTP : PHP 8.3 vérifié, MySQL 8 disponible
- [ ] Repo GitHub `bia-namur` créé
- [ ] Compte Plausible créé (ou alternative analytics)

### Comptes données externes
- [ ] Inscription développeur OpenData Namur (si requis)
- [ ] Test des flux RSS de tous les sites culturels namurois (Le Delta, Belvédère, etc.)
- [ ] Vérification des ToS de Quefaire.be (autorisation scraping ou API ?)

### Préparation contenu
- [ ] Liste des 50 lieux fondateurs rédigée en CSV (nom, type, adresse, quartier, mood)
- [ ] 5 stories de référence rédigées à la main (pour few-shot examples)
- [ ] 3 briefs hebdo de référence rédigés à la main (pour few-shot examples)
- [ ] Logo SVG finalisé + déclinaisons (favicon, icône PWA 192/512/maskable)

### Démarrage Claude Code
Le projet sera lancé avec ce brief comme contexte initial. Premier message à Claude Code :

> Voici le brief produit complet du projet **Bia Namur**. Lis-le intégralement.
> Adapte les 5 agents de `~/.claude/agents` selon les spécifications de la
> section 9 et place-les dans `.claude/agents/` du projet. Confirme la
> compréhension de l'architecture et propose ton plan de bataille pour
> attaquer la semaine 1.

---

## 13. Décisions validées (mai 2026)

- [x] **Nom** : **Bia Namur** ✓
- [x] **Logo** : version ambrée + serif éditorial + 3 points référence Bia Bouquet ✓
- [x] **Stack frontend** : Inertia + Vue 3 ✓
- [x] **Authentification** : magic link au lancement ; Google + Apple OAuth obligatoires en V2 ✓
- [x] **Modèle économique** : 100 % gratuit au lancement ; freemium ou abonnement ou pub à évaluer après traction ✓
- [x] **Première ville** : Namur uniquement ✓
- [x] **Tagline** : *Le carnet vivant des namurois* — à faire évoluer plus tard ✓

---

## 14. Conformité légale et RGPD

### 14.1 Principes
Bia Namur est gratuit au lancement, mais reste un service en ligne commercial à terme. La conformité doit être en place dès le M1 — gratuit ≠ exempté.

### 14.2 Pages légales obligatoires (en footer permanent)

**Politique de confidentialité** — collecte et usage des données personnelles, droits RGPD, transfert hors UE (Anthropic API → mention SCC), durée de conservation, contact `privacy@bianamur.be`

**Conditions Générales d'Utilisation** — règles d'usage, comptes, contributions (cession non exclusive de droits), propriété intellectuelle, modération, limites de responsabilité (informations indicatives), loi belge applicable, juridiction tribunal de Namur

**Mentions légales** — éditeur (Thibaut [Nom], adresse, email), numéro BCE si structure créée, hébergeur, directeur de la publication

### 14.3 Implémentation produit RGPD

**Au moment de l'inscription** :
- Acceptation explicite des CGU + politique de confidentialité (case à cocher non pré-cochée)
- Email magic link uniquement (pas de mot de passe = moins de risque)
- Consentement notifications push : opt-in après inscription, pas par défaut

**Dans le compte utilisateur** :
- Bouton **« Exporter mes données »** (génère un JSON avec tous les contenus utilisateur)
- Bouton **« Supprimer mon compte »** (suppression complète sous 30 jours, anonymisation des contributions)
- Modification consentements (notifications, géolocalisation)

**Permissions runtime** :
- Géolocalisation : demandée uniquement à la première utilisation de la carte « autour de moi », jamais en arrière-plan
- Notifications : opt-in explicite avec preview du type de notif

**Cookies** :
- Plausible Analytics est cookieless = pas de banner cookies obligatoire
- Si ajout futur de Stripe : cookies essentiels uniquement (légalement exemptés)
- **Pas de Google Analytics, pas de Meta Pixel, pas de tracking publicitaire**

### 14.4 Hébergement et transferts

| Service | Localisation | Données concernées | Mécanisme |
|---------|-------------|-------------------|-----------|
| Hébergement FTP | Belgique/France/UE | Toutes données utilisateur | RGPD natif |
| Cloudflare R2 | UE par défaut | Photos uploadées | RGPD natif |
| Anthropic API | États-Unis | Texte de prompts (pas de PII transmise) | Standard Contractual Clauses + DPA Anthropic |
| Sentry | UE (région EU) | Logs erreurs (peut contenir IDs utilisateurs) | RGPD natif |
| Plausible | UE (Allemagne) | Stats anonymisées | Cookieless, RGPD-natif |
| Stripe (future) | UE (Stripe Ireland) | Données paiement | RGPD natif |

→ **Anthropic est le seul transfert hors UE**, à mentionner explicitement dans la politique de confidentialité avec lien vers DPA Anthropic.

→ **Aucune PII envoyée à Anthropic** : prompts pour briefs/stories/modération contiennent uniquement contenu public + texte des contributions (sans email/nom utilisateur).

### 14.5 Registre des traitements
Document interne (Article 30 RGPD) listant chaque traitement de données. Modèle gratuit sur `autoriteprotectiondonnees.be`. Tenu à jour en parallèle, jamais publié.

### 14.6 Mineurs
- CGU : âge minimum **16 ans** (seuil RGPD belge), sinon refus inscription ou consentement parental requis
- Modération renforcée des contributions
- Pas de données sensibles collectées

### 14.7 Outils recommandés pour rédiger les pages légales

Pas besoin d'avocat à 1500 € pour la V1 :
- **donottrack.eu** (gratuit, générateur RGPD-natif)
- Modèles **Autorité de Protection des Données belge** (`autoriteprotectiondonnees.be`)
- **TermsFeed** ou **iubenda** (~30-100 €) pour version multilingue clé en main

→ Quand monétisation activée (M6+), passage à un avocat (~500-800 €) pour vérification.

### 14.8 Checklist conformité — fin semaine 4

- [ ] Politique de confidentialité publiée
- [ ] CGU publiées
- [ ] Mentions légales publiées
- [ ] Footer permanent avec liens + attribution CC BY data.namur.be
- [ ] Adresse `privacy@bianamur.be` qui fonctionne (mail forwardé)
- [ ] Bouton « Exporter mes données » dans compte utilisateur
- [ ] Bouton « Supprimer mon compte » dans compte utilisateur
- [ ] Géolocalisation : permission demandée à l'usage uniquement
- [ ] Notifications push : opt-in explicite, pas par défaut
- [ ] Registre des traitements rédigé (document interne)
- [ ] Mention transfert hors UE (Anthropic) dans politique de confidentialité
- [ ] Modération : système actif sur contributions
- [ ] CGU : clause âge minimum 16 ans

---

## 15. Stratégie de monétisation et architecture pré-monétisation

### 15.1 Principe directeur
**Lancer 100 % gratuit, construire la communauté en M1-M6, activer la monétisation progressivement à partir du M6 sans casser le ton ni l'expérience.**

L'architecture est préparée dès le M1 pour rendre toutes les voies possibles, mais aucune monétisation n'est visible avant que le produit ait sa traction.

### 15.2 Six voies de monétisation, par ordre de viabilité

**Voie 1 — Freemium *Bia +*** *(M6-M12, voie principale)*

Bia + : ~3 €/mois ou 25 €/an. Débloque :
- Sauvegarde illimitée de favoris et listes thématiques (« Mes terrasses », « Pour les beaux-parents »)
- Notifications de proximité (« un événement suivi à 500 m »)
- Brief personnalisé (filtrage thématique de la curation)
- Mode hors-ligne enrichi (stories complètes accessibles)
- Carnet de visite (journal personnel avec photos et notes)
- Statut visible « Mécène namurois »
- Sans pub

**Estimations** : 2-5 % de conversion sur utilisateurs actifs. À 5000 actifs et 3 % conversion : ~4500 €/an.

**Voie 2 — Encarts éditorialisés** *(M12-M18, complément naturel)*

Stories sponsorisées clairement marquées « Présenté par [commerçant] », rédigées en collaboration. Ton aligné, transparence totale (badge), maximum 5 commerçants/mois (rareté).

Tarifs : 80 €/mois ponctuel ou 200 €/mois récurrent.
**Estimation** : 5 × 100 €/mois = ~6000 €/an.

**Voie 3 — Billetterie partenaire** *(M12-M24)*

Commission 5-10 % sur ventes générées via Bia (partenariat TicketTac, Ticketmatic, EventBrite).
**Estimation prudente** : ~400 €/an par 1000 utilisateurs actifs.

**Voie 4 — Partenariats institutionnels** *(M6-M12)*

Office du Tourisme, Namur CentreVille, Province, Région, FW-B. Subventions et conventions, non-dilutif.
**Estimation** : 3000-15000 €/an si dossier solide.

**Voie 5 — Édition papier annuelle** *(M18-M24)*

*Almanach Bia Namur* : best-of de l'année, format beau livre, ~120 pages, 700 ex à 25 €.
**Estimation** : ~10 000 €/an si pré-vendu.

**Voie 6 — Publicité display classique** *(à éviter)*

Casse le ton et l'expérience. Génération marginale (10-30 €/mois sub-10k utilisateurs). À écarter.

### 15.3 Préparation architecturale dès M1

#### Modèle `users` étendu
```sql
ALTER TABLE users ADD COLUMN subscription_tier ENUM('free','plus','patron') DEFAULT 'free';
ALTER TABLE users ADD COLUMN subscription_started_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN subscription_renews_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(255) NULL;
```

#### Table `subscriptions`
```sql
id BIGINT PRIMARY KEY,
user_id BIGINT,
plan VARCHAR(50),  -- 'plus_monthly', 'plus_yearly', 'patron'
status VARCHAR(20), -- 'active', 'canceled', 'past_due'
started_at TIMESTAMP,
expires_at TIMESTAMP,
stripe_subscription_id VARCHAR(255),
payment_method VARCHAR(50),
auto_renew BOOLEAN,
created_at, updated_at
```

#### Table `entitlements` (capabilities)
```sql
id, code VARCHAR(100) UNIQUE,
-- ex: 'unlimited_favorites', 'proximity_notifs', 'offline_stories'
label, description,
tier_required ENUM('free','plus','patron')
```

#### Modèle `places` étendu
```sql
ALTER TABLE places ADD COLUMN is_sponsored BOOLEAN DEFAULT FALSE;
ALTER TABLE places ADD COLUMN sponsored_label VARCHAR(255) NULL;
ALTER TABLE places ADD COLUMN sponsored_until TIMESTAMP NULL;
ALTER TABLE places ADD COLUMN sponsorship_id BIGINT NULL;
```

#### Service central `EntitlementService`
Centralise toutes les vérifications de droits. Au M6, ajouter `$user->can('unlimited_favorites')` partout sans refacto profonde.

```php
// Exemple d'usage M1 (toujours true en gratuit)
if ($user->can('unlimited_favorites')) {
    // sauvegarde sans limite
} else {
    // sauvegarde limitée à 20
}
```

#### Stripe préparé mais inactif
- Package `laravel/cashier-stripe` installé
- Migrations Cashier exécutées
- Variables `.env` documentées (placeholder)
- Routes définies mais retournent 404 jusqu'au M6
- Webhook handler échafaudé

### 15.4 Roadmap monétisation

| Mois | Action | Voie |
|------|--------|------|
| M1-M3 | 100 % gratuit, focus produit + communauté | — |
| M3 | Premier dossier subvention rédigé | 4 |
| M4-M5 | Test usabilité Bia + auprès de bêta-utilisateurs | 1 |
| M6 | **Activation Bia + freemium** | 1 |
| M9 | Premier partenariat institutionnel signé (objectif) | 4 |
| M12 | **Encarts éditorialisés** : pilote 2-3 commerces | 2 |
| M12 | Premier dossier billetterie partenaire | 3 |
| M18 | Édition papier *Almanach Bia Namur* annoncée | 5 |

### 15.5 Indicateurs de viabilité économique

À surveiller mensuellement dans Filament admin :

- **MAU** (utilisateurs actifs mensuels)
- **Taux de conversion gratuit → Bia +**
- **Churn mensuel** (annulations / total abonnés)
- **ARPU** (revenu moyen par utilisateur actif)
- **CAC** (coût d'acquisition — proche de zéro pour produit communautaire local)
- **LTV** (lifetime value)

**Seuil de rentabilité estimé** :
- 100 abonnés Bia + à 30 €/an = 3000 €/an = couvre hébergement + API + outils + marge symbolique
- 500 abonnés Bia + + 3 commerces sponsorisés = ~21 000 €/an = activité significative

### 15.6 Position éthique
- **Jamais** de pub display intrusive, jamais de pop-up
- **Jamais** de revente ou partage de données utilisateurs à des tiers
- Encarts sponsorisés **toujours** clairement marqués
- Bia + reste optionnel, le produit principal toujours utilisable gratuitement
- Transparence sur les revenus dans une page « À propos / Modèle économique »

---

## Annexes

### A. Sources de données initiales (à intégrer en S2)

```
OpenData Namur
  https://data.namur.be/api/records/1.0/search/?dataset=namur-agenda-des-evenements
  https://data.namur.be/api/records/1.0/search/?dataset=annuaire-des-commerces

Sites culturels (RSS ou scraping)
  https://www.ledelta.be/agenda
  https://www.belvedere-namur.be/agenda
  https://www.theatredenamur.be/agenda
  https://www.citadelle.namur.be
  https://www.kikk.be
  https://www.centreculturelnamur.be
  https://www.unamur.be/agenda
  https://www.namurtourisme.be/fr/evenements

Médias locaux (RSS)
  https://www.lavenir.net/cnt/dmf-namur (RSS région)
  https://www.rtbf.be/info/regions/namur (RSS)

Agrégateurs (avec respect des ToS)
  https://www.quefaire.be/namur
  https://www.exploremeuse.be/agenda/calendrier
```

### B. Lieux fondateurs — pistes pour les 50 premiers

À compléter avec des choix éditoriaux personnels. Exemples :

- *Patrimoine* : Citadelle, Confluent (Le Grognon), Cathédrale Saint-Aubain, Église Saint-Loup, Beffroi, Halle al'Chair, Maison de la Culture Bomel
- *Marchés* : Saint-Aubain (samedi), Jambes (jeudi), Beauvallon (dimanche), marché du dimanche au Grognon
- *Cafés / bars* : Le Bia Bouquet, La Cuve à Bière, Le Chapitre, Piano Bar, O'Flaherty's, Henri (rooftop)
- *Restaurants* : L'Espièglerie, Brasserie François, Tempo, La Petite Fugue, La Bonne Chère, Saveurs d'Italie
- *Boulangeries / artisans* : Crusti Croc, Pâtisserie Vincent, Boucherie Bernard, Caves Bertinchamps
- *Espaces verts* : Parc Louise-Marie, Jardin du Maïeur, Plaine Saint-Nicolas, Parc d'Hastedon, Citadelle (parcours)
- *Culture* : Le Delta, Belvédère, Théâtre Royal, Maison de la Poésie, Cinéma Caméo, Pavillon (KIKK)
- *Hidden gems* : ruelles du vieux Namur, panorama de la Tour Saint-Jacques, Souterrains de la Citadelle, Pont des Ardennes au lever du soleil

### C. Wallon namurois — vocabulaire de base à intégrer

```
bia       — beau (« c'est bia, ça »)
biesse    — bête / idiot
à l'aise  — tranquille (« à l'aise, ça va »)
tchafyî   — bavarder
cougnou   — pain de Noël
djin      — gens
on côp    — un coup, une fois
spotchî   — écraser
nin       — pas (négation)
on ptchot — un petit
```

---

*Fin du brief. Document vivant — à mettre à jour au fil du projet.*
