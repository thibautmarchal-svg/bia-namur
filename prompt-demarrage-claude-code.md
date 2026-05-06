# Prompt de démarrage — Claude Code (v2)

> À copier-coller comme **premier message** dans une nouvelle session Claude Code, après avoir créé le repo `bia-namur`, déposé le brief et les assets, **et rempli le fichier `secrets-bia-namur.md`**.

---

## ⚠️ Avant de coller le message

Tu dois avoir, dans le repo cloné localement, ces fichiers :

```
bia-namur/
├── brief-bia-namur.md                    (committé, public)
├── prompt-demarrage-claude-code.md       (committé, public)
├── secrets-bia-namur.md                  (LOCAL UNIQUEMENT, jamais commité)
├── assets-logo/
│   ├── logo-full.svg
│   ├── logo-mark-512.svg
│   ├── logo-maskable-512.svg
│   ├── favicon-32.svg
│   └── logo-monochrome.svg
└── .gitignore                            (avec secrets-bia-namur.md ignoré)
```

Le fichier `secrets-bia-namur.md` doit être **rempli avec tous tes accès** avant le premier lancement (sinon Claude Code te demandera ces infos une par une).

---

## Message initial à coller

```
Tu es l'agent principal de développement du projet Bia Namur.

ÉTAPE 0 — Configuration de gitignore (avant tout)
Vérifie que .gitignore contient bien :
- secrets-bia-namur.md
- .env, .env.local, .env.staging, .env.production
- /vendor, /node_modules, /storage/logs/*, etc.
Si le fichier .gitignore n'existe pas ou est incomplet, crée-le ou complète-le AVANT
toute autre action. Cette étape est critique — un secret commité est compromis.

ÉTAPE 1 — Lecture intégrale du brief
Lis intégralement le fichier `brief-bia-namur.md` à la racine du repo. C'est le brief
produit et technique complet : vision, architecture, stack, modèle de données,
pipelines IA, branding, plans d'exécution, conformité légale, monétisation.

Une fois lu, fais-moi une synthèse en 15 lignes maximum couvrant :
- L'objectif produit en une phrase
- L'architecture cross-platform retenue (PWA)
- La stack technique principale
- Le pipeline d'autonomie IA (les 3 flux : brief hebdo, stories, modération)
- La position éthique (RGPD, monétisation freemium, défense compétitive)
- Le calendrier de la semaine 1

ÉTAPE 2 — Lecture du fichier secrets et synthèse de l'environnement
Lis le fichier `secrets-bia-namur.md` à la racine. Ce fichier contient TOUS les accès
nécessaires pour le développement : domaines, hébergement FTP, MySQL, Cloudflare R2 +
proxy, Anthropic API, Sentry, Plausible, SMTP, GitHub PAT, Stripe (test).

Vérifie :
- Que toutes les sections critiques pour la semaine 1 sont remplies (hébergement,
  GitHub PAT, Anthropic API minimum). Les sections Stripe / BOIP peuvent attendre.
- Qu'il n'y a aucune valeur de placeholder du type [email] ou xxx restante dans les
  champs requis pour la S1.

Si quelque chose manque pour la S1, fais-moi UNE SEULE liste consolidée de ce qu'il
manque. Pas de demandes morcelées plus tard.

Si tout est complet pour la S1, confirme-moi : "Configuration complète, je peux
travailler en autonomie sur la semaine 1 sans solliciter d'accès supplémentaires."

ÉTAPE 3 — Adaptation des 5 agents Claude Code
Le projet utilise 5 agents spécialisés. Leurs spécifications adaptées au projet
sont en section 9 du brief :
- backend-namur (section 9.1)
- security-namur (section 9.2)
- ux-ui-namur (section 9.3) — particulièrement détaillé, design est critique
- qa-testing-namur (section 9.4)
- deployment-namur (section 9.5)

Mes 5 agents génériques existants sont dans `~/.claude/agents/`. Crée le dossier
`.claude/agents/` à la racine du projet, et pour chaque agent :
1. Lis l'agent générique correspondant dans `~/.claude/agents/`
2. Adapte son contenu selon les spécifications de la section 9 du brief
3. Crée le fichier dans `.claude/agents/{nom-agent}.md`

Pour ux-ui-namur, sois particulièrement rigoureux : c'est l'agent le plus important
pour ce projet (produit éditorial, design = différenciation). Inclus tous les
principes éditoriaux, anti-patterns, composants prioritaires, références
d'inspiration, et exigences PWA mentionnés en section 9.3.

ÉTAPE 4 — Plan de bataille semaine 1 détaillé
Une fois les agents en place, propose-moi ton plan de bataille détaillé pour la
semaine 1 (section 10 du brief) avec :
- Découpage en jours (lundi, mardi, etc.)
- Ordre d'exécution (dépendances entre tâches)
- Points de validation où tu attends ma confirmation explicite
- Risques identifiés et plans de mitigation
- Liste des fichiers .env, secrets GitHub Actions, et configurations à générer
  depuis secrets-bia-namur.md (que tu produiras automatiquement après mon GO)

N'écris AUCUNE ligne de code avant que je valide le plan.

ÉTAPE 5 — Premier livrable de la semaine 1
Une fois le plan validé, le tout premier travail de code doit être :
1. Setup repo Laravel 11 + Inertia + Vue 3 + Tailwind 4 + Vite
2. Configuration PWA (manifest, service worker basique, icônes depuis assets-logo/)
3. Page d'accueil minimaliste avec le wordmark Bia Namur centré et la tagline
   "Le carnet vivant des namurois — bientôt"
4. Génération automatique de .env (depuis secrets-bia-namur.md) et configuration
   des secrets GitHub Actions via PAT
5. Pipeline GitHub Actions : lint + tests + build + deploy FTP staging
6. Validation que le PWA s'installe correctement sur iOS et Android
7. Lighthouse PWA score > 90 dès le premier déploiement

Une fois ce socle posé, on attaque le schéma de base de données et l'admin Filament
(toujours en autonomie, sans nouvelle demande d'accès).

PRINCIPES POUR TOUTE LA SUITE DU PROJET :
- Travaille en autonomie en utilisant secrets-bia-namur.md comme source de vérité
- Demande validation produit/UX, pas validation technique répétitive
- Communique en français
- Écris du code Laravel idiomatique, suis les conventions du brief
- Le ton namurois doit transparaître partout (commits, messages, contenu)
- Ne mets jamais de PII dans les logs ou les prompts Claude API

Démarre par l'étape 0, puis enchaîne jusqu'à l'étape 4 sans pause. Pause uniquement
à l'étape 4 pour la validation du plan.
```

---

## Notes importantes pour Thibaut

### Avant de coller le prompt

✅ Vérifie que `secrets-bia-namur.md` est dans `.gitignore` :

```bash
cat .gitignore | grep secrets
# Doit afficher : secrets-bia-namur.md
```

Si ce n'est pas le cas, crée-le AVANT de pousser quoi que ce soit :

```bash
echo "secrets-bia-namur.md" >> .gitignore
echo ".env" >> .gitignore
echo ".env.local" >> .gitignore
echo ".env.staging" >> .gitignore
echo ".env.production" >> .gitignore
echo "/vendor" >> .gitignore
echo "/node_modules" >> .gitignore
git add .gitignore
git commit -m "chore: gitignore secrets et fichiers sensibles"
git push
```

### Pendant l'étape 2

Si Claude Code te dit qu'il manque des accès pour la S1, complète **tout d'un coup** avant de relancer. Cela t'évite des allers-retours.

**Accès strictement nécessaires pour la S1** :
- ✅ Hébergement FTP : host, user, password, chemin
- ✅ MySQL : host, port, user, password, database
- ✅ GitHub Personal Access Token
- ✅ Anthropic API Key (pour test minimal en S1)
- ✅ Cloudflare API Token (pour DNS automatique si besoin)

**Accès qui peuvent attendre la S2** :
- Cloudflare R2 (médias, pas urgent en S1)
- Sentry (monitoring, à activer S2)
- Plausible (analytics, S2)
- SMTP Mail (S2 dès qu'il y a de l'auth magic link)

**Accès qui peuvent attendre M6** :
- Stripe (monétisation)

### Pendant l'étape 4

Quand Claude Code te propose le plan de bataille, lis-le **attentivement**. Vérifie :
- Que l'ordre des jours te convient
- Qu'il n'y a pas de tâche bloquante ignorée
- Que les points de validation te conviennent (matin/soir, fréquence)

Si quelque chose ne va pas, demande des modifications. Une fois validé, tu n'auras plus à intervenir avant le mercredi/jeudi pour les premiers retours visibles.

### Cadence recommandée

- **Matin (15-20 min)** : lecture des dernières actions, validation des points en attente
- **Soir (10-15 min)** : check des résultats du jour, planification du lendemain
- **Samedi (2-3h)** : validation du sprint hebdo, test produit, feedback utilisateur

### Ce qui doit t'alerter

Si Claude Code :
- Demande un accès qu'il devrait déjà avoir → renvoie-le vers `secrets-bia-namur.md`
- Propose une feature non prévue dans le brief → demande pourquoi avant de valider
- Ignore une exigence du brief (RGPD, design, ton éditorial) → corrige-le explicitement
- Génère du contenu qui sonne « générique IA » → réfère-toi aux anti-patterns S 9.3

### Bonne route !

Tu es prêt. Le brief est solide, les agents sont calibrés, le design est précis,
les accès sont en bloc. Claude Code peut bosser en autonomie pendant 80 % du temps.

Tu construis quelque chose dont tu seras fier. À très vite sur Bia Namur.
