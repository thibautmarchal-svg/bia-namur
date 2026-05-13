<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Recupere les derniers messages recus par le bot pour aider l'admin
 * a trouver son chat_id la premiere fois (etape obligatoire au setup).
 *
 * Workflow :
 *   1. L'admin envoie n'importe quel message au bot sur Telegram
 *      (ex: "ouverture" via @BiaNamurBot ou le nom configure).
 *   2. Lance `php artisan bia:telegram:get-updates`
 *   3. Trouve son chat_id dans le payload affiche
 *   4. Met-le dans .env (TELEGRAM_ADMIN_CHAT_ID)
 *
 * Attention : si setWebhook deja appele, getUpdates retourne vide
 * (Telegram pousse les updates au webhook au lieu de polling). Dans
 * ce cas, supprimer temporairement le webhook puis le re-set.
 */
class TelegramGetUpdatesCommand extends Command
{
    protected $signature = 'bia:telegram:get-updates';

    protected $description = 'Recupere les derniers messages recus par le bot (utile pour trouver son chat_id).';

    public function handle(): int
    {
        // On lit le token directement depuis env() pour permettre d'appeler
        // cette commande SANS TELEGRAM_ENABLED=true (juste pour trouver son
        // chat_id au setup initial).
        $token = (string) (env('TELEGRAM_BOT_TOKEN', '') ?: config('services.telegram.bot_token'));
        if (empty($token)) {
            $this->components->error('TELEGRAM_BOT_TOKEN vide dans .env.');
            $this->newLine();
            $this->line('  Ajoute dans ton .env :');
            $this->line('    <fg=cyan>TELEGRAM_BOT_TOKEN=1234567890:AAExxxxxxxxxxxxxxxxxxxxxxxx</>');
            $this->newLine();
            $this->line('  (Token recu depuis @BotFather apres /newbot)');

            return self::FAILURE;
        }

        $this->components->info('Fetching last updates from Telegram...');

        $response = Http::timeout(10)
            ->get("https://api.telegram.org/bot{$token}/getUpdates");

        if (! $response->successful()) {
            $this->components->error('Erreur API : ' . $response->status() . ' — ' . $response->body());

            return self::FAILURE;
        }

        $updates = $response->json('result') ?? [];

        if (empty($updates)) {
            $this->components->warn('Aucun message recu.');
            $this->newLine();
            $this->line('  1. Envoie n\'importe quel message a ton bot depuis Telegram');
            $this->line('     (ex: cherche son @username sur Telegram, ouvre la conv, envoie "coucou")');
            $this->line('  2. Relance cette commande');
            $this->newLine();
            $this->line('  Si setWebhook est deja configure, getUpdates est inactif :');
            $this->line('  appelle deleteWebhook d\'abord :');
            $this->line('    <fg=cyan>curl "https://api.telegram.org/bot{TOKEN}/deleteWebhook"</>');

            return self::SUCCESS;
        }

        $this->components->info(count($updates) . ' update(s) recente(s) :');
        $this->newLine();

        foreach ($updates as $update) {
            $message = $update['message'] ?? $update['callback_query']['message'] ?? null;
            if (! $message) {
                continue;
            }

            $chat = $message['chat'] ?? [];
            $from = $message['from'] ?? [];

            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line("  chat_id : <fg=cyan>{$chat['id']}</> ← copie cette valeur dans TELEGRAM_ADMIN_CHAT_ID");
            $this->line("  from    : {$from['first_name']} (@{$from['username']})");
            $this->line('  text    : ' . ($message['text'] ?? '(no text)'));
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
