<?php

namespace App\Services\Telegram;

use App\Models\Brief;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Wrapper Telegram Bot API pour Bia Namur.
 *
 * Usage principal : envoi de notifications "brief draft pret a relire"
 * a l'admin avec boutons inline pour valider/rejeter directement depuis
 * le chat (pattern repris du SaaS Socialbrain du user).
 *
 * Securite :
 *  - Le bot_token n'est jamais expose cote frontend.
 *  - L'admin_chat_id est verifie cote webhook pour ignorer les messages
 *    de gens random qui auraient trouve le nom du bot.
 *  - Le webhook URL contient un secret partage (cf. TELEGRAM_WEBHOOK_SECRET).
 *
 * Mode disabled : si TELEGRAM_ENABLED=false (defaut) ou bot_token manquant,
 * toutes les methodes deviennent no-op + log. Permet de tester en local
 * sans config et de couper en prod si Telegram tombe.
 */
class TelegramNotifier
{
    private const API_BASE = 'https://api.telegram.org/bot';

    public function __construct(
        protected ?string $botToken = null,
        protected ?string $adminChatId = null,
        protected bool $enabled = false,
    ) {
        $this->botToken = $botToken ?? (string) config('services.telegram.bot_token');
        $this->adminChatId = $adminChatId ?? (string) config('services.telegram.admin_chat_id');
        $this->enabled = $enabled || (bool) config('services.telegram.enabled', false);
    }

    /**
     * Envoie un brief draft_ai a l'admin pour validation manuelle.
     * Retourne l'ID du message Telegram envoye (utile pour editer apres
     * action), ou null si echec.
     */
    public function sendBriefForValidation(Brief $brief): ?int
    {
        if (! $this->isReady()) {
            return null;
        }

        $text = $this->formatBriefMessage($brief);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Publier', 'callback_data' => "brief_publish:{$brief->id}"],
                    ['text' => '❌ Rejeter', 'callback_data' => "brief_reject:{$brief->id}"],
                ],
                [
                    ['text' => '🔍 Voir dans l\'admin', 'url' => url("/admin/briefs/{$brief->id}/edit")],
                ],
            ],
        ];

        return $this->sendMessage($text, $keyboard);
    }

    /**
     * Edite le texte d'un message deja envoye (utilise pour signaler
     * "✅ Publie" apres action). Si echec, log et continue.
     */
    public function editMessageText(string $chatId, int $messageId, string $newText): void
    {
        if (! $this->isReady()) {
            return;
        }

        $response = Http::asJson()
            ->timeout(15)
            ->post(self::API_BASE . $this->botToken . '/editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $newText,
                'parse_mode' => 'Markdown',
            ]);

        if (! $response->successful()) {
            Log::warning('telegram.edit_message_failed', [
                'message_id' => $messageId,
                'response' => Str::limit($response->body(), 300),
            ]);
        }
    }

    /**
     * Repond au clic sur un bouton inline (toast en haut du chat Telegram
     * pour donner un feedback instantane meme avant que editMessageText
     * ait fini de tourner). 5 sec max.
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): void
    {
        if (! $this->isReady()) {
            return;
        }

        Http::asJson()
            ->timeout(5)
            ->post(self::API_BASE . $this->botToken . '/answerCallbackQuery', array_filter([
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
                'show_alert' => false,
            ]));
    }

    /**
     * Configure le webhook Telegram pour qu'il pointe sur notre endpoint.
     * Utilise par la commande artisan bia:telegram:set-webhook au setup.
     */
    public function setWebhook(string $webhookUrl): array
    {
        if (! $this->isReady()) {
            return ['ok' => false, 'reason' => 'telegram not configured'];
        }

        $response = Http::asJson()
            ->timeout(15)
            ->post(self::API_BASE . $this->botToken . '/setWebhook', [
                'url' => $webhookUrl,
                'allowed_updates' => ['callback_query'],
                'drop_pending_updates' => true,
            ]);

        return $response->json() ?? ['ok' => false];
    }

    /**
     * Sans webhook configure, recupere les updates en polling (utile pour
     * trouver son chat_id la premiere fois : envoie un message au bot,
     * puis appelle cette methode pour voir le payload).
     *
     * @return array<int, mixed>
     */
    public function getUpdates(): array
    {
        if (! $this->isReady()) {
            return [];
        }

        $response = Http::timeout(10)
            ->get(self::API_BASE . $this->botToken . '/getUpdates');

        return $response->json('result') ?? [];
    }

    /**
     * Construit le texte Markdown du brief pour Telegram. On garde court
     * (titre + intro + items resumes) pour rester lisible sur mobile.
     */
    protected function formatBriefMessage(Brief $brief): string
    {
        $brief->loadMissing('items');
        $week = sprintf('%d-W%02d', $brief->year, $brief->week_number);

        $lines = [
            '🆕 *Nouveau brief draft Bia Namur*',
            '',
            "📅 *{$week}* — {$brief->title}",
        ];

        if ($brief->intro_text) {
            $intro = Str::limit(strip_tags($brief->intro_text), 250);
            $lines[] = '';
            $lines[] = '_' . $intro . '_';
        }

        $items = $brief->items;
        if ($items->isNotEmpty()) {
            $lines[] = '';
            $lines[] = "*{$items->count()} items :*";
            foreach ($items->take(7) as $item) {
                $firstLine = Str::before($item->ai_text, "\n");
                $cleanLine = trim(str_replace('**', '', $firstLine));
                $lines[] = '• ' . Str::limit($cleanLine, 70);
            }
        }

        return implode("\n", $lines);
    }

    protected function sendMessage(string $text, ?array $replyMarkup = null): ?int
    {
        $payload = [
            'chat_id' => $this->adminChatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $response = Http::asJson()
            ->timeout(15)
            ->post(self::API_BASE . $this->botToken . '/sendMessage', $payload);

        if (! $response->successful()) {
            Log::warning('telegram.send_message_failed', [
                'response' => Str::limit($response->body(), 300),
            ]);

            return null;
        }

        return (int) $response->json('result.message_id');
    }

    protected function isReady(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if (empty($this->botToken) || empty($this->adminChatId)) {
            Log::debug('telegram.not_configured', [
                'has_token' => ! empty($this->botToken),
                'has_chat_id' => ! empty($this->adminChatId),
            ]);

            return false;
        }

        return true;
    }
}
