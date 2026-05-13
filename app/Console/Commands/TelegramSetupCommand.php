<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramNotifier;
use Illuminate\Console\Command;

/**
 * Commandes utiles pour configurer le bot Telegram Bia Namur.
 *
 * Usage typique au setup :
 *   php artisan bia:telegram:get-updates   → pour trouver son chat_id
 *   php artisan bia:telegram:set-webhook   → pour activer les callbacks
 *
 * En prod, set-webhook doit etre appele 1 seule fois apres avoir mis
 * TELEGRAM_BOT_TOKEN + TELEGRAM_WEBHOOK_SECRET dans .env. Telegram garde
 * l'URL configuree jusqu'a ce qu'on la change.
 */
class TelegramSetupCommand extends Command
{
    protected $signature = 'bia:telegram:set-webhook
        {--url= : URL complete du webhook (default: APP_URL/webhooks/telegram/{secret})}';

    protected $description = 'Configure le webhook Telegram pour recevoir les callback_query.';

    public function handle(TelegramNotifier $telegram): int
    {
        $secret = (string) config('services.telegram.webhook_secret');
        if (empty($secret)) {
            $this->components->error('TELEGRAM_WEBHOOK_SECRET vide. Genere via : openssl rand -hex 32');

            return self::FAILURE;
        }

        $url = $this->option('url') ?: url("/webhooks/telegram/{$secret}");

        $this->components->info('Configuration du webhook Telegram :');
        $this->components->info("  URL : {$url}");

        $result = $telegram->setWebhook($url);

        if (! ($result['ok'] ?? false)) {
            $this->components->error('Echec : ' . ($result['description'] ?? json_encode($result)));

            return self::FAILURE;
        }

        $this->components->success('Webhook configure. Telegram POSTera sur cette URL pour chaque clic inline.');

        return self::SUCCESS;
    }
}
