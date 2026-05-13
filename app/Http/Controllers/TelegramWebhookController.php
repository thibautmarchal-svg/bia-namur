<?php

namespace App\Http\Controllers;

use App\Models\Brief;
use App\Services\Telegram\TelegramNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint webhook Telegram.
 *
 * URL : POST /webhooks/telegram/{secret}
 * Configure via : php artisan bia:telegram:set-webhook
 *
 * Telegram POST ici a chaque event d'interet (notamment quand l'admin
 * clique sur un bouton inline d'un message envoye par notre bot).
 *
 * Securite :
 *  - Le secret en URL est verifie via hash_equals (timing-safe).
 *  - On verifie aussi que le chat_id de l'expediteur = admin_chat_id,
 *    pour ignorer les messages d'autres utilisateurs qui auraient
 *    decouvert le bot.
 *  - On retourne TOUJOURS 200 OK a Telegram (meme en cas d'erreur)
 *    pour eviter qu'il retente en boucle et nous spam.
 *
 * Actions supportees (callback_data) :
 *  - brief_publish:{id} → Brief.status = published + published_at = now()
 *  - brief_reject:{id}  → Brief.delete() (soft)
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, TelegramNotifier $telegram): JsonResponse
    {
        $expectedSecret = (string) config('services.telegram.webhook_secret');
        if (empty($expectedSecret) || ! hash_equals($expectedSecret, $secret)) {
            // 404 silencieux pour ne pas reveler l'existence de l'endpoint
            abort(404);
        }

        $update = $request->all();

        // Filtre 1 : on ne traite que les callback_query (clics inline)
        $callback = $update['callback_query'] ?? null;
        if (! $callback) {
            return response()->json(['ok' => true]);
        }

        // Filtre 2 : verification du chat_id (anti-impersonation)
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $adminChatId = (string) config('services.telegram.admin_chat_id');
        if ($chatId !== $adminChatId) {
            Log::warning('telegram.webhook.unauthorized_chat', ['chat_id' => $chatId]);

            return response()->json(['ok' => true]);
        }

        $callbackId = (string) ($callback['id'] ?? '');
        $messageId = (int) ($callback['message']['message_id'] ?? 0);
        $data = (string) ($callback['data'] ?? '');

        try {
            $this->routeCallback($data, $chatId, $messageId, $callbackId, $telegram);
        } catch (\Throwable $e) {
            Log::error('telegram.webhook.callback_error', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            $telegram->answerCallbackQuery($callbackId, '⚠️ Erreur — voir les logs');
        }

        return response()->json(['ok' => true]);
    }

    protected function routeCallback(
        string $data,
        string $chatId,
        int $messageId,
        string $callbackId,
        TelegramNotifier $telegram,
    ): void {
        if (preg_match('/^brief_publish:(\d+)$/', $data, $m)) {
            $this->handlePublish((int) $m[1], $chatId, $messageId, $callbackId, $telegram);

            return;
        }

        if (preg_match('/^brief_reject:(\d+)$/', $data, $m)) {
            $this->handleReject((int) $m[1], $chatId, $messageId, $callbackId, $telegram);

            return;
        }

        Log::info('telegram.webhook.unknown_callback', ['data' => $data]);
        $telegram->answerCallbackQuery($callbackId, '❓ Action inconnue');
    }

    protected function handlePublish(
        int $briefId,
        string $chatId,
        int $messageId,
        string $callbackId,
        TelegramNotifier $telegram,
    ): void {
        $brief = Brief::find($briefId);
        if (! $brief) {
            $telegram->answerCallbackQuery($callbackId, 'Brief introuvable');

            return;
        }

        if ($brief->status === Brief::STATUS_PUBLISHED) {
            $telegram->answerCallbackQuery($callbackId, 'Deja publie');

            return;
        }

        $brief->update([
            'status' => Brief::STATUS_PUBLISHED,
            'published_at' => now(),
            'reviewed_at' => now(),
        ]);

        $publicUrl = url("/brief/{$brief->slug}");

        $telegram->answerCallbackQuery($callbackId, '✅ Publie !');
        $telegram->editMessageText(
            $chatId,
            $messageId,
            "✅ *Brief publie*\n\n📅 {$brief->title}\n\n🔗 {$publicUrl}",
        );

        Log::info('telegram.brief.published', ['brief_id' => $brief->id]);
    }

    protected function handleReject(
        int $briefId,
        string $chatId,
        int $messageId,
        string $callbackId,
        TelegramNotifier $telegram,
    ): void {
        $brief = Brief::find($briefId);
        if (! $brief) {
            $telegram->answerCallbackQuery($callbackId, 'Brief introuvable');

            return;
        }

        $brief->delete();    // soft-delete

        $telegram->answerCallbackQuery($callbackId, '❌ Rejete (soft-delete)');
        $telegram->editMessageText(
            $chatId,
            $messageId,
            "❌ *Brief rejete*\n\n📅 {$brief->title}\n\n_Soft-deleted — restaurable via /admin/briefs._",
        );

        Log::info('telegram.brief.rejected', ['brief_id' => $brief->id]);
    }
}
