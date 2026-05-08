# DEPLOYMENT.md — Bia Namur

> Guide de déploiement production. Le workflow CI/CD se trouve dans
> `.github/workflows/deploy.yml`. Ce fichier documente le contexte serveur,
> les secrets à configurer, le premier déploiement, le rollback et le monitoring.

---

## 1. Cible de déploiement

| Élément | Valeur |
|---|---|
| Hôte | VM Debian (CBlue, sysadmin Christophe Gossiaux, ticket #908225) |
| IP | `193.104.37.156` |
| Domaine | `bianamur.be` (DNS A pointé) |
| Web server | Apache 2.4.62 + PHP-FPM 8.4 (FastCGI) |
| PHP | 8.4.21 (extensions : pdo_mysql, mbstring, openssl, gd, intl, zip, curl, dom, exif) |
| OPcache | actif |
| DocumentRoot | `/var/www/bianamur.be/www` (à confirmer/basculer sur `public/` — voir §10) |
| MySQL | base `bianamur`, user `bianamur` |
| SSL | en cours de commande, déploiement HTTP d'abord puis bascule HTTPS |
| Accès | SSH (clé publique à transmettre) + FTP fallback |

---

## 2. Stack et conventions

- Laravel 11 + PHP 8.3+ (compatible 8.4)
- Inertia 1.x + Vue 3 + Vite 6 (vite-plugin-pwa pour SW + manifest)
- Driver DB / cache / sessions / queue : `database` (pas Redis)
- Anthropic SDK PHP (pipelines IA, `BIA_AI_MOCK_MODE=false` en prod)
- minishlink/web-push pour les notifications PWA (VAPID)
- Filament 3 admin sur `/admin` (auth magic link)

---

## 3. Pré-requis serveur (à valider avec Christophe)

### Arborescence cible

```
/var/www/bianamur.be/
├── app/
├── bootstrap/
│   └── cache/                    # 775, owner www-data
├── config/
├── database/
├── public/                       # ← idéalement DocumentRoot Apache
│   ├── index.php
│   ├── build/                    # généré par Vite (jamais commité)
│   └── storage/                  # symlink vers ../storage/app/public
├── resources/
├── routes/
├── storage/                      # 775, owner www-data, jamais écrasé par rsync
│   ├── app/
│   ├── framework/{cache,sessions,views}/
│   └── logs/
├── vendor/                       # uploadé par rsync depuis le runner
├── .env                          # 640, owner www-data, secret
└── artisan
```

### Permissions

| Chemin | Mode | Owner |
|---|---|---|
| `storage/` (récursif dirs) | `775` | `www-data:www-data` (ou user app) |
| `storage/` (récursif files) | `664` | idem |
| `bootstrap/cache/` (dirs) | `775` | idem |
| `bootstrap/cache/` (files) | `664` | idem |
| `.env` | `640` | idem |
| Tout le reste | `755` dirs / `644` files | idem |

### DocumentRoot Apache

Idéal : `/var/www/bianamur.be/public` (standard Laravel, c'est le seul dossier
qui doit être servi). Le `public/.htaccess` fourni gère le rewrite vers
`index.php` et les headers de cache pour `/build/`.

Si Christophe ne peut pas changer le DocumentRoot et qu'on reste sur
`/var/www/bianamur.be/www`, deux options :
- **A** : transformer `www` en symlink vers `public` (`ln -s /var/www/bianamur.be/public /var/www/bianamur.be/www`)
- **B** : workaround via un `index.php` à la racine de `www` qui require le bootstrap (à éviter si possible — ça expose tout le repo).

Recommandation : option A si possible, sinon demander vraiment à pointer sur `public/`.

---

## 4. GitHub Secrets requis

À créer dans **Settings → Secrets and variables → Actions** du repo
`thibautmarchal-svg/bia-namur`.

### Accès SSH (déploiement)

| Nom | Description | Valeur exemple |
|---|---|---|
| `SSH_USER` | User SSH sur la VM (probablement `bianamur` ou dédié) | `bianamur` |
| `SSH_PORT` | Port SSH (à confirmer avec Christophe, souvent custom) | `22` ou `2222` |
| `SSH_PRIVATE_KEY` | Clé privée OpenSSH dédiée GitHub Actions | bloc `-----BEGIN OPENSSH PRIVATE KEY-----` |

> Génère une paire dédiée : `ssh-keygen -t ed25519 -C "github-actions@bianamur.be" -f bianamur_deploy -N ""`.
> Pousse `bianamur_deploy.pub` dans `~/.ssh/authorized_keys` du user de déploiement (à donner à Christophe).
> Mets `bianamur_deploy` (clé privée) dans `SSH_PRIVATE_KEY`.

### App Laravel

| Nom | Description |
|---|---|
| `APP_KEY` | `base64:...` — généré une fois via `php artisan key:generate --show` en local, JAMAIS regénéré (sinon sessions/cookies invalidés) |
| `APP_URL` | `https://bianamur.be` (ou `http://bianamur.be` tant que SSL pas actif) |

### Base de données

| Nom | Description |
|---|---|
| `DB_HOST` | `127.0.0.1` (VM dédiée, MySQL local) |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `bianamur` |
| `DB_USERNAME` | `bianamur` |
| `DB_PASSWORD` | mot de passe MySQL fourni par CBlue (one-time link déjà ouvert, stocké dans `secrets-bia-namur.md`) |

### Mail SMTP

| Nom | Description |
|---|---|
| `MAIL_HOST` | ex `smtp.mailgun.org` |
| `MAIL_PORT` | `587` |
| `MAIL_USERNAME` | login SMTP |
| `MAIL_PASSWORD` | password SMTP |
| `MAIL_FROM_ADDRESS` | `noreply@bianamur.be` |

### Anthropic Claude

| Nom | Description |
|---|---|
| `ANTHROPIC_API_KEY` | `sk-ant-...` (compte production, séparé du dev) |

### Push notifications PWA (VAPID)

| Nom | Description |
|---|---|
| `BIA_VAPID_PUBLIC_KEY` | clé publique VAPID (87 chars en base64url) — déjà générée |
| `BIA_VAPID_PRIVATE_KEY` | clé privée VAPID — déjà générée |
| `BIA_VAPID_SUBJECT` | `mailto:contact@bianamur.be` |

> Le workflow injecte automatiquement `BIA_VAPID_PUBLIC_KEY` dans les deux variables
> `BIA_VAPID_PUBLIC_KEY` (back) et `VITE_VAPID_PUBLIC_KEY` (front) pour garantir la sync.

### Misc

| Nom | Description |
|---|---|
| `MIGRATE_SECRET` | `Str::random(64)` — secret pour endpoint HTTP `/migrate` (fallback si SSH down) |
| `SENTRY_LARAVEL_DSN` | DSN Sentry projet Laravel (vide tant que Sentry pas créé) |
| `SESSION_DOMAIN` | `.bianamur.be` (point initial obligatoire pour les sous-domaines + iOS Safari) |
| `R2_ACCESS_KEY_ID` | (optionnel, vide tant que R2 pas activé) |
| `R2_SECRET_ACCESS_KEY` | idem |
| `R2_BUCKET` | `bianamur-media` |
| `R2_ENDPOINT` | URL endpoint R2 |
| `R2_PUBLIC_URL` | URL publique du bucket (CDN) |

---

## 5. Premier déploiement (étapes manuelles, une seule fois)

À faire dans cet ordre, une fois Christophe a validé l'accès SSH et les permissions.

### 5.1 Créer la clé SSH dédiée GitHub Actions

```bash
# En local (Windows PowerShell ou WSL)
ssh-keygen -t ed25519 -C "github-actions@bianamur.be" -f bianamur_deploy -N ""
# → bianamur_deploy (privée, → SSH_PRIVATE_KEY) + bianamur_deploy.pub (publique)
```

Envoie `bianamur_deploy.pub` à Christophe en lui demandant de la mettre dans
`~/.ssh/authorized_keys` du user de déploiement (avec restrictions
optionnelles `command="rsync ..."` si on veut être strict).

### 5.2 Configurer tous les GitHub Secrets (cf. §4)

### 5.3 Préparer le serveur (Christophe ou nous via SSH ad-hoc)

```bash
# SSH manuel premier coup (avant que GH Actions prenne la main)
ssh bianamur@193.104.37.156 -p <port>

cd /var/www/bianamur.be
# Si le dossier n'existe pas encore, Christophe le crée + chown au user de deploy.

# Vérifier le user et les permissions
whoami
ls -la

# S'assurer que git n'est pas requis (rsync push depuis runner)
# Mais si on veut un premier bootstrap, on peut cloner pour avoir l'arbo :
# git clone https://github.com/thibautmarchal-svg/bia-namur.git .
```

### 5.4 Créer la base de données (si pas déjà fait par CBlue)

```sql
-- Sur la VM, avec credentials root MySQL CBlue
CREATE DATABASE bianamur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bianamur'@'localhost' IDENTIFIED BY '<password fourni>';
GRANT ALL PRIVILEGES ON bianamur.* TO 'bianamur'@'localhost';
FLUSH PRIVILEGES;
```

> Normalement déjà fait par CBlue via le ticket #908225, à vérifier.

### 5.5 Lancer le premier déploiement

Trois options :

**A — Push sur main + CI passe** : déclenche automatiquement `deploy.yml`.

**B — Workflow manuel** : `Actions → Deploy production → Run workflow`.

**C — En cas de pépin, deploy à la main** :
```bash
# Local : build + rsync
composer install --no-dev --optimize-autoloader --classmap-authoritative
npm ci && npm run build
# ... puis suit les étapes du workflow
```

### 5.6 Post-deploy : seeder admin + city Namur

Le seeder `DatabaseSeeder` crée la city Namur + un admin local.
**En production il ne tourne PAS automatiquement** (on ne lance que `migrate`).

Pour seeder en prod (UNE SEULE FOIS) :
```bash
ssh bianamur@193.104.37.156 -p <port>
cd /var/www/bianamur.be
php artisan db:seed --class=CityNamurSeeder --force
php artisan db:seed --class=AdminUserSeeder --force
```

Adapter les noms de seeders à ceux réellement présents dans `database/seeders/`.

### 5.7 Vérifier

- `curl -I https://bianamur.be` → 200
- `curl -I https://bianamur.be/admin` → 200 (page login Filament)
- `tail -f /var/www/bianamur.be/storage/logs/laravel.log` → pas d'erreur 500
- Chrome DevTools → Application → Manifest + Service Worker enregistré

---

## 6. Déploiements suivants

```bash
git push origin main
# → CI tourne (lint + tests + build)
# → Si CI passe, deploy.yml se déclenche automatiquement (workflow_run)
# → Maintenance ON → rsync → migrate --force → re-cache → Maintenance OFF
```

Durée typique : 4 à 6 minutes.

---

## 7. Rollback

### 7.1 Cas simple : revert d'un commit

```bash
# En local
git revert <sha-commit-foireux>
git push origin main
# → redeploy automatique avec le revert
```

### 7.2 Cas plus grave : rollback rapide manuel

Si le redeploy via `git revert` est trop lent ou casse encore plus :

```bash
ssh bianamur@193.104.37.156 -p <port>
cd /var/www/bianamur.be

# Mode maintenance immédiat
php artisan down --render="errors::503" --retry=60

# Rollback de la dernière migration si elle est responsable
php artisan migrate:rollback --force --step=1

# Si on a un dump précédent (cf. §9), restaurer la DB
mysql -u bianamur -p bianamur < /var/backups/bianamur/dump-YYYY-MM-DD.sql

# Rollback du code : repointer un déploiement précédent
# (idée : garder N derniers déploiements dans /var/www/bianamur.be-releases/
#  + un symlink atomique. À mettre en place en S4.)

# Sortir de maintenance
php artisan up
```

### 7.3 Cas catastrophe : .env corrompu

```bash
# Le .env de prod est régénéré à chaque deploy depuis GitHub Secrets,
# donc relancer le workflow suffit dans 90% des cas.
# Workflow Actions → Deploy production → Run workflow (sur main).
```

> **Ne jamais éditer `.env` directement sur le serveur** : il sera écrasé
> au prochain deploy. Toute modification permanente doit passer par
> les GitHub Secrets + un push.

---

## 8. Monitoring

### 8.1 Logs Laravel

```bash
ssh bianamur@193.104.37.156 -p <port>
tail -f /var/www/bianamur.be/storage/logs/laravel.log

# Channels métier (si configurés dans config/logging.php)
tail -f /var/www/bianamur.be/storage/logs/ingestion.log
tail -f /var/www/bianamur.be/storage/logs/ai_pipeline.log
tail -f /var/www/bianamur.be/storage/logs/moderation.log
```

### 8.2 Logs Apache

```bash
sudo tail -f /var/www/bianamur.be/logs/error.log
sudo tail -f /var/www/bianamur.be/logs/access.log
# Selon config Christophe, ils peuvent être à /var/log/apache2/bianamur.be-*.log
```

### 8.3 Sentry

Une fois `SENTRY_LARAVEL_DSN` configuré, toutes les exceptions remontent
dans le dashboard Sentry. Configure les alertes :
- Erreur 500 répétée
- Job IA en échec (`GenerateBriefJob`, `ModerateContributionJob`)
- Migration ratée

### 8.4 Healthcheck simple

Ajouter une route `/health` qui retourne 200 + version :
```php
// routes/web.php
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'version' => trim(file_get_contents(base_path('VERSION'))),
    'time' => now()->toIso8601String(),
]));
```

Puis monitor externe (UptimeRobot / BetterStack / Cloudflare healthchecks).

### 8.5 Queue (driver database)

```bash
# Statut des jobs en attente
mysql -u bianamur -p bianamur -e "SELECT COUNT(*) FROM jobs;"
mysql -u bianamur -p bianamur -e "SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;"

# Worker (à mettre en supervisor / systemd, voir §10 questions Christophe)
php artisan queue:work --max-time=55 --tries=3
```

---

## 9. Backups

> À confirmer avec Christophe : politique de backup CBlue (snapshot VM ?
> dump quotidien MySQL ?). Sinon, à mettre en place côté app :

```bash
# Cron quotidien sur la VM (à demander à Christophe)
0 3 * * * mysqldump -u bianamur -p<pwd> bianamur | gzip > /var/backups/bianamur/dump-$(date +\%Y-\%m-\%d).sql.gz
```

Avant **toute migration destructive** (drop column, alter de prod) :
```bash
ssh bianamur@193.104.37.156 -p <port>
mysqldump -u bianamur -p bianamur > /tmp/pre-migration-$(date +%Y%m%d-%H%M).sql
```

---

## 10. Questions à poser à Christophe (CBlue)

À envoyer dans le ticket #908225 ou par mail :

1. **DocumentRoot Apache** : peut-il pointer sur `/var/www/bianamur.be/public/`
   (standard Laravel) au lieu de `/var/www/bianamur.be/www/` ? Sinon, peut-il
   transformer `www` en symlink vers `public` (`ln -s ./public ./www`) ?

2. **SSH** :
   - Quel user dois-je utiliser pour SSH (`bianamur` ?) ?
   - Quel port (22 par défaut, ou custom) ?
   - Peux-tu ajouter cette clé publique dans `~/.ssh/authorized_keys` du user
     de déploiement ? (clé fournie : `bianamur_deploy.pub`)

3. **PHP-FPM reload** : peut-on autoriser le user de déploiement à lancer
   `sudo systemctl reload php8.4-fpm` (sans password) ?
   Sudoers minimal :
   ```
   bianamur ALL=(root) NOPASSWD: /bin/systemctl reload php8.4-fpm
   ```
   Sans ça OPcache ne sera rafraîchi qu'à expiration et certains assets cachés
   ne seront pas pris en compte immédiatement.

4. **Cron Laravel scheduler** : peut-on ajouter ce cron sur la VM (crontab du
   user app) ?
   ```
   * * * * * cd /var/www/bianamur.be && php artisan schedule:run >> /dev/null 2>&1
   ```
   Indispensable pour : ingestion horaire, brief vendredi 14h, auto-publish lundi 9h.

5. **Queue worker** : peut-on installer un service systemd ou un supervisor pour
   le worker de queue ? Service systemd recommandé :
   ```ini
   # /etc/systemd/system/bianamur-queue.service
   [Unit]
   Description=Bia Namur Laravel queue worker
   After=network.target mysql.service

   [Service]
   User=bianamur
   Group=bianamur
   Restart=always
   RestartSec=5
   ExecStart=/usr/bin/php8.4 /var/www/bianamur.be/artisan queue:work --tries=3 --max-time=3600 --sleep=3

   [Install]
   WantedBy=multi-user.target
   ```

6. **memory_limit PHP** : peut-on passer le `memory_limit` PHP-FPM de 128M à
   256M ? Filament admin et certains jobs IA peuvent dépasser 128M.

7. **MySQL** : peut-on confirmer le `max_allowed_packet` (16M minimum, 64M
   conseillé pour les uploads photos) ?

8. **Logs Apache** : où sont précisément les logs (path) ? `/var/www/bianamur.be/logs/`
   ou `/var/log/apache2/bianamur.be-*.log` ?

9. **SSL** : ETA du certificat ? On commence en HTTP, on bascule dès dispo.
   Cert fourni par CBlue ou on met en place Certbot/Let's Encrypt ?

10. **Backups MySQL** : quelle politique CBlue ? Si rien, on met en place un
    cron de dump local + sync vers R2.

---

## 11. Checklist avant chaque deploy majeur

- [ ] Tous les GitHub Secrets sont à jour (APP_KEY, DB, VAPID, ANTHROPIC, SMTP)
- [ ] `.env.production.example` à jour (toute nouvelle variable doit être documentée ici)
- [ ] Migration testée localement (up + rollback)
- [ ] Build local passe : `composer install --no-dev` + `npm run build`
- [ ] Pas de `dd()`, `console.log`, `APP_DEBUG=true` oublié
- [ ] Tests Pest verts en local et CI
- [ ] Backup DB pris si migration destructive
- [ ] Mode mock IA bien à OFF (`BIA_AI_MOCK_MODE=false`)
- [ ] VAPID public key sync entre `BIA_VAPID_PUBLIC_KEY` et `VITE_VAPID_PUBLIC_KEY`

---

## 12. Debug 500 après deploy — checklist

1. `tail -100 /var/www/bianamur.be/storage/logs/laravel.log`
2. `cat /var/www/bianamur.be/.env` → vérifier qu'aucun `${VAR}` ne traîne
3. `APP_KEY` présent et commence par `base64:` ?
4. Migrations passées ? `php artisan migrate:status`
5. `public/.htaccess` présent et lisible ?
6. Permissions storage/ et bootstrap/cache/ → `775` dirs / `664` files, owner correct ?
7. `php artisan optimize:clear && php artisan optimize` (purge tous les caches)
8. OPcache reset : `sudo systemctl reload php8.4-fpm`
9. Apache error log : `sudo tail -100 /var/www/bianamur.be/logs/error.log`

---

## 13. Sécurité — règles non-négociables

- Ne JAMAIS commiter `.env` (déjà dans `.gitignore`)
- Ne JAMAIS commiter `secrets-bia-namur.md` (déjà dans `.gitignore`)
- Ne JAMAIS supprimer manuellement `.env` du serveur (perte totale, regénéré
  par le workflow uniquement)
- Ne JAMAIS mettre un secret dans une URL (toujours en header)
- Ne JAMAIS regénérer `APP_KEY` une fois en prod (invalide cookies/sessions)
- Endpoint `/migrate` (fallback HTTP) protégé par header `X-Migrate-Secret`,
  rate limit `throttle:3,60`
- Headers sécu via middleware `SecurityHeaders` Laravel (PHP les envoie
  toujours, contrairement à `mod_headers` Apache qui peut être désactivé)
