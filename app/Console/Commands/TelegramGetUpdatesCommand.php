<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramNotifier;
use Illuminate\Console\Command;

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

    public function handle(TelegramNotifier $telegram): int
    {
        $this->components->info('Fetching last updates from Telegram...');
        $updates = $telegram->getUpdates();

        if (empty($updates)) {
            $this->components->warn('Aucun message recu.');
            $this->newLine();
            $this->line('  1. Verifie que TELEGRAM_BOT_TOKEN est defini dans .env');
            $this->line('  2. Envoie un message a ton bot depuis Telegram');
            $this->line('  3. Si setWebhook est deja configure, getUpdates est inactif :');
            $this->line('     supprime temporairement le webhook via la BotFather, recommence,');
            $this->line('     puis relance bia:telegram:set-webhook.');

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
