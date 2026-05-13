# Telegram — Setup Bia Namur

Validation des briefs hebdo en 2 clics depuis Telegram, sans ouvrir l'admin.

## Architecture

```
GenerateBriefJob (vendredi 14h)
   ↓ crée Brief en status=draft_ai
BriefObserver::created
   ↓ dispatch SendBriefValidationNotifJob
TelegramNotifier::sendBriefForValidation
   ↓ POST api.telegram.org/sendMessage
   ↓ avec keyboard inline [✅ Publier] [❌ Rejeter] [🔍 Voir admin]
[Admin clique un bouton]
   ↓ Telegram POST /webhooks/telegram/{secret}
TelegramWebhookController
   ↓ change Brief.status + edit le message Telegram
```

## Setup initial (à faire 1 seule fois)

### 1. Créer le bot via @BotFather

Sur Telegram, ouvre une conversation avec **@BotFather** :

```
/newbot
Bia Namur
bianamur_bot           ← ou n'importe quel nom finissant par _bot
```

BotFather te renvoie un TOKEN du genre :
```
1234567890:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

→ Copie ce token, on en a besoin pour `TELEGRAM_BOT_TOKEN`.

### 2. Trouver ton chat_id

Sur Telegram, ouvre une conversation avec ton bot et envoie n'importe quel message (ex: `coucou`).

Puis sur le serveur (ou en local) :

```bash
TELEGRAM_BOT_TOKEN=ton_token_ici php artisan bia:telegram:get-updates
```

Ça t'affiche :
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  chat_id : 123456789  ← copie cette valeur dans TELEGRAM_ADMIN_CHAT_ID
  from    : Thibaut (@thibaut)
  text    : coucou
```

### 3. Générer un secret webhook

```bash
openssl rand -hex 32
```

Copie la sortie, on l'utilise pour `TELEGRAM_WEBHOOK_SECRET`.

### 4. Configurer les 3 secrets GitHub

Sur GitHub → Settings → Secrets and variables → Actions, ajoute :

| Nom | Valeur |
|---|---|
| `TELEGRAM_BOT_TOKEN` | Le token de l'étape 1 |
| `TELEGRAM_ADMIN_CHAT_ID` | Le chat_id de l'étape 2 |
| `TELEGRAM_WEBHOOK_SECRET` | Le secret de l'étape 3 |

⚠️ Ajoute aussi `TELEGRAM_ENABLED=true` dans le block "Generate production .env" du workflow.

### 5. Configurer le webhook côté Telegram

Une fois le déploiement passé (avec les 3 variables d'env actives en prod), lance via curl :

```bash
curl -X POST "https://api.telegram.org/bot{TOKEN}/setWebhook" \
  -d "url=https://bianamur.be/webhooks/telegram/{SECRET}" \
  -d "allowed_updates=[\"callback_query\"]"
```

Ou plus simple, via l'endpoint Laravel `/_deploy/schedule` qui pourrait être étendu, ou directement en SSH.

**Alternative pratique** : ajoute un step au workflow GitHub Actions qui appelle `php artisan bia:telegram:set-webhook` après les migrations.

## Test manuel

Une fois tout configuré :

```bash
# En local (Laragon) avec MOCK_MODE=true
php artisan bia:brief:generate-test --week=21
```

Le brief draft_ai créé déclenchera automatiquement la notif Telegram via l'observer.

## Désactiver Telegram temporairement

Mettre `TELEGRAM_ENABLED=false` dans `.env` (ou GitHub Secrets). Le `TelegramNotifier::isReady()` retournera `false` et toutes les méthodes deviendront des no-op.
