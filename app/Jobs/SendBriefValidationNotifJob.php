<?php

namespace App\Jobs;

use App\Models\Brief;
use App\Services\Telegram\TelegramNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Envoie le brief draft a l'admin via Telegram pour validation manuelle.
 *
 * Declenche par BriefObserver::created quand un brief passe en status
 * draft_ai (typiquement apres GenerateBriefJob vendredi 14h).
 *
 * Stocke le message_id retourne dans brief.telegram_message_id pour
 * pouvoir editer le message apres action (afficher "✅ Publie" par exemple
 * depuis le webhook controller).
 *
 * Async via queue database : si Telegram tombe ou rate-limit, la queue
 * retry sans bloquer le flux principal de generation du brief.
 */
class SendBriefValidationNotifJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(public readonly int $briefId) {}

    public function handle(TelegramNotifier $telegram): void
    {
        $brief = Brief::find($this->briefId);
        if (! $brief) {
            Log::info('telegram.brief_validation.skipped', [
                'brief_id' => $this->briefId,
                'reason' => 'brief not found',
            ]);

            return;
        }

        // On ne renotifie pas un brief deja publie / archive
        if ($brief->status !== Brief::STATUS_DRAFT_AI) {
            Log::info('telegram.brief_validation.skipped', [
                'brief_id' => $brief->id,
                'reason' => 'brief status changed',
                'status' => $brief->status,
            ]);

            return;
        }

        $messageId = $telegram->sendBriefForValidation($brief);

        if ($messageId !== null) {
            // On stocke le message_id pour pouvoir editer le message
            // depuis le webhook controller apres clic sur un bouton.
            $brief->update(['telegram_message_id' => $messageId]);

            Log::info('telegram.brief_validation.sent', [
                'brief_id' => $brief->id,
                'message_id' => $messageId,
            ]);
        }
    }
}
