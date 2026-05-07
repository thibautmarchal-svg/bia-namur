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
