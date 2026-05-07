<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sources externes (ingestion)
    |--------------------------------------------------------------------------
    |
    | Feature flags pour activer/desactiver chaque source d'ingestion.
    | Permet de couper une source en prod sans modifier le code (ex: si
    | le format RSS d'un site change et casse le parsing).
    */

    'sources' => [
        'opendata_namur' => env('BIA_SRC_OPENDATA', true),
        'rss_delta' => env('BIA_SRC_RSS_DELTA', true),
        'rss_belvedere' => env('BIA_SRC_RSS_BELVEDERE', true),
        'rss_theatre_royal' => env('BIA_SRC_RSS_THEATRE', true),
        'rss_kikk' => env('BIA_SRC_RSS_KIKK', true),
        'rss_citadelle' => env('BIA_SRC_RSS_CITADELLE', true),
        'quefaire' => env('BIA_SRC_QUEFAIRE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipelines d'ingestion
    |--------------------------------------------------------------------------
    |
    | fixture_mode : utilise les snapshots locaux dans tests/Fixtures/opendata
    |                et tests/Fixtures/rss au lieu d'appeler les APIs reelles.
    |                Active par defaut en local + testing pour ne pas hammer
    |                les sources externes en dev.
    | user_agent   : envoye sur tous les appels HTTP sortants (OpenData,
    |                Nominatim, RSS, scraping). Identifie pour respect
    |                des conditions des sources et joignabilite admin.
    | nominatim_rate_limit_ms : delai minimum entre 2 appels Nominatim
    |                (1 req/s requis par les conditions OSM).
    */

    'ingestion' => [
        'fixture_mode' => env('BIA_INGEST_FIXTURE_MODE', true),
        'user_agent' => env('BIA_USER_AGENT', 'BiaNamurBot/1.0 (+contact@bianamur.be)'),
        'opendata_namur_url' => env(
            'BIA_OPENDATA_NAMUR_URL',
            'https://data.namur.be/api/records/1.0/search/?dataset=namur-agenda-des-evenements&rows=200',
        ),
        'nominatim_url' => env('BIA_NOMINATIM_URL', 'https://nominatim.openstreetmap.org/search'),
        'nominatim_rate_limit_ms' => 1100,
        'http_timeout_seconds' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feeds RSS / Atom des sites culturels namurois
    |--------------------------------------------------------------------------
    |
    | Une cle = un slug source (utilise dans events.source). Chaque feed :
    |   url             : URL du flux RSS/Atom
    |   name            : nom human readable affiche dans le dashboard
    |   venue_default   : lieu pré-rempli si l'item RSS n'a pas de balise lieu
    |                     (cas frequent : Le Delta publie ses events sans
    |                     repreciser "au Delta" dans chaque item)
    |   category_default: categorie pré-remplie idem
    |   fixture         : nom du snapshot XML local pour le mode fixture_mode
    |
    | La feature flag config('bia.sources.{slug}') permet de couper
    | individuellement chaque feed sans modifier ce fichier.
    */

    'rss_feeds' => [
        'rss_delta' => [
            'name' => 'Le Delta',
            'url' => env('BIA_RSS_DELTA_URL', 'https://www.ledelta.be/agenda/feed'),
            'venue_default' => 'Le Delta',
            'category_default' => 'culture',
            'fixture' => 'delta.xml',
        ],
        'rss_belvedere' => [
            'name' => 'Belvédère',
            'url' => env('BIA_RSS_BELVEDERE_URL', 'https://www.belvedere-namur.be/agenda/feed'),
            'venue_default' => 'Le Belvédère',
            'category_default' => 'concert',
            'fixture' => 'belvedere.xml',
        ],
        'rss_theatre_royal' => [
            'name' => 'Théâtre Royal de Namur',
            'url' => env('BIA_RSS_THEATRE_URL', 'https://www.theatredenamur.be/feed'),
            'venue_default' => 'Théâtre Royal de Namur',
            'category_default' => 'theatre',
            'fixture' => 'theatre-royal.xml',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Categorisation auto par mots-cles (sans Claude)
    |--------------------------------------------------------------------------
    |
    | Mapping cles → liste de mots qui declenchent la categorisation.
    | Premiere correspondance gagne. Si rien ne matche, category=null
    | et Claude tranche en S2 sur les cas ambigus.
    */

    'categorization_rules' => [
        'concert' => ['concert', 'musique', 'live', 'jazz', 'rock', 'classique', 'symphonique'],
        'electro' => ['dj', 'electro', 'techno', 'house', 'club night'],
        'expo' => ['expo', 'exposition', 'vernissage', 'galerie'],
        'theatre' => ['theatre', 'théâtre', 'piece', 'spectacle', 'comedie'],
        'famille' => ['famille', 'enfants', 'jeunesse', 'kids', 'goûter'],
        'gastronomie' => ['degustation', 'dégustation', 'menu', 'cuisine', 'gastronomie', 'pèkèt', 'biere', 'asperges'],
        'patrimoine' => ['patrimoine', 'visite guidée', 'historique', 'souterrains', 'citadelle', 'historien'],
        'marche' => ['marché', 'marche', 'producteurs', 'brocante'],
        'nature' => ['balade', 'randonnée', 'nature', 'parc', 'biodiversité'],
        'culture' => ['conférence', 'lecture', 'rencontre', 'apero littéraire', 'litterature'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipelines IA
    |--------------------------------------------------------------------------
    |
    | mock_mode : retourne des fixtures sans appeler l'API Anthropic
    |             (utilise en dev/test pour ne pas consommer de credit).
    | min_moderation_score_auto_approve : seuil au-dessus duquel une
    |             contribution est auto-approuvee (cf. brief §7.3).
    */

    'ai' => [
        'mock_mode' => env('BIA_AI_MOCK_MODE', false),
        'auto_publish_brief' => env('BIA_AI_AUTO_PUBLISH', false),
        'min_moderation_score_auto_approve' => 75,
        'min_moderation_score_manual_review' => 40,

        'models' => [
            'default' => env('CLAUDE_DEFAULT_MODEL', 'claude-sonnet-4-6'),
            'premium' => env('CLAUDE_PREMIUM_MODEL', 'claude-opus-4-7'),
        ],

        'timeout_seconds' => 60,
        'max_retries' => 3,
        'retry_initial_delay_ms' => 500,    // backoff exponentiel : 500ms, 1s, 2s
    ],

    /*
    |--------------------------------------------------------------------------
    | Prix indicatifs des modeles Claude (USD par 1M tokens)
    |--------------------------------------------------------------------------
    |
    | Utilise par ClaudeApiService pour calculer le cout reel d'un appel
    | et le stocker dans ai_runs.cost_usd. A ajuster si les tarifs Anthropic
    | changent.
    */

    'pricing' => [
        'claude-sonnet-4-6' => ['input' => 3.00, 'output' => 15.00],
        'claude-opus-4-7' => ['input' => 15.00, 'output' => 75.00],
        'claude-haiku-4-5' => ['input' => 1.00, 'output' => 5.00],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompts versionnes
    |--------------------------------------------------------------------------
    |
    | Chaque prompt a un identifiant de version (brief_v1, story_v1, etc.)
    | qui est trace dans ai_runs.prompt_template_version pour permettre :
    | - les A/B tests de prompts
    | - la detection de regressions de qualite via tests Pest fixtures
    | - le rollback si un nouveau prompt produit moins bien
    */

    'prompts' => [

        'brief_v1' => [
            'system' => <<<'PROMPT'
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

STRUCTURE DU BRIEF (JSON strict en sortie) :
{
  "intro": "2-3 phrases qui campent la semaine (saison, météo attendue, énergie générale)",
  "items": [
    {
      "event_id": <id de l'événement choisi>,
      "title": "...",
      "venue": "lieu nommé sans adresse",
      "when_text": "vendredi 19h, samedi 14h-18h, etc.",
      "angle": "2-3 phrases : pourquoi celui-là, ce qui le rend spécifique",
      "reasoning": "courte note interne sur le critère de sélection (rareté, ancrage local, accessibilité)"
    }
  ],
  "outro": "phrase de clôture optionnelle (peut être vide)"
}

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
- Mettre plus de 7 items ou moins de 5

Tu retournes UNIQUEMENT du JSON valide. Pas de texte avant ou après.
PROMPT,
            'temperature' => 0.5,
            'max_tokens' => 4000,
        ],

        'story_v1' => [
            'system' => <<<'PROMPT'
Tu es le raconteur de Bia Namur. Tu rédiges des stories de patrimoine,
de tradition ou de wallon namurois pour les Namurois.

TON :
- Chaleureux, précis, comme le voisin érudit qui adore sa ville
- Pas du touriste-marketing, pas du fonctionnaire, pas du blogueur
  lifestyle
- Précis sur les faits, généreux sur le contexte
- Mots de wallon assumés mais doux pour les néo-namurois

FORMAT (markdown) :
- 200 à 400 mots, jamais plus
- Premier paragraphe : accroche concrète (un détail, une scène)
- Corps : 2-3 paragraphes qui développent
- Pas de sections H2/H3 (texte fluide, pas web SEO)

NE PAS :
- Inventer des dates, noms ou anecdotes non présents dans le contexte
  fourni. Si tu ne sais pas, tu coupes.
- Utiliser des emojis ou des superlatifs vides
- Mentionner Bia Namur dans le texte
PROMPT,
            'temperature' => 0.7,
            'max_tokens' => 1500,
        ],

        'moderation_v1' => [
            'system' => <<<'PROMPT'
Tu modères les contributions des utilisateurs de Bia Namur (carnet
hyperlocal namurois).

Tu retournes UNIQUEMENT un JSON :
{
  "score": <0..100>,
  "verdict": "approve" | "review" | "reject",
  "reasoning": {
    "quality": "<note sur la qualité éditoriale>",
    "tone": "<note sur le ton (cohérent / hors sujet / pub déguisée)>",
    "factual": "<doute factuel ou OK>",
    "duplicate": "<oui/non + raison>"
  }
}

CRITÈRES :
- score >= 75 : contribution intégrable telle quelle (ton OK, fait
  vérifiable, pas de doublon évident, pas de pub)
- score 40-74 : contenu acceptable mais à relire (ton à ajuster,
  formulation à retravailler, doute factuel)
- score < 40 : pub déguisée, hors sujet, doublon, ou texte douteux

Tu retournes uniquement le JSON. Aucun texte avant/après.
PROMPT,
            'temperature' => 0.2,
            'max_tokens' => 800,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Wallon namurois — vocabulaire de base (cf. brief annexe C)
    |--------------------------------------------------------------------------
    */

    'wallon' => [
        'bia' => 'beau',
        'biesse' => 'bête',
        'à l\'aise' => 'tranquille',
        'tchafyî' => 'bavarder',
        'cougnou' => 'pain de Noël',
        'djin' => 'gens',
        'on côp' => 'un coup',
        'spotchî' => 'écraser',
        'nin' => 'pas',
        'on ptchot' => 'un petit',
    ],

];
