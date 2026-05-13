<?php

namespace App\Observers;

use App\Jobs\SendBriefPublishedNotificationJob;
use App\Jobs\SendBriefValidationNotifJob;
use App\Models\Brief;

/**
 * Observer Brief.
 *
 * 2 use cases :
 *
 * 1. created() : quand un brief naitne en draft_ai (GenerateBriefJob du
 *    vendredi 14h), on dispatch SendBriefValidationNotifJob qui envoie
 *    le draft a l'admin sur Telegram avec boutons inline pour valider/
 *    rejeter en 2 clics.
 *
 * 2. updated() : quand un brief passe en published (validation manuelle
 *    via admin Filament ou via Telegram callback), on dispatch
 *    SendBriefPublishedNotificationJob qui envoie les push notifications
 *    aux abonnes PWA.
 *    Idempotent : si deja published, pas de re-dispatch.
 */
class BriefObserver
{
    public function created(Brief $brief): void
    {
        if ($brief->status === Brief::STATUS_DRAFT_AI) {
            SendBriefValidationNotifJob::dispatch($brief->id);
        }
    }

    public function updated(Brief $brief): void
    {
        if (! $brief->wasChanged('status')) {
            return;
        }

        $original = $brief->getOriginal('status');

        // Cas 1 : repasse en draft_ai (re-generation manuelle) → re-notif Telegram
        if ($brief->status === Brief::STATUS_DRAFT_AI && $original !== Brief::STATUS_DRAFT_AI) {
            SendBriefValidationNotifJob::dispatch($brief->id);

            return;
        }

        // Cas 2 : passe en published → push notif PWA aux abonnes
        if ($original === Brief::STATUS_PUBLISHED) {
            return;
        }

        if ($brief->status !== Brief::STATUS_PUBLISHED) {
            return;
        }

        SendBriefPublishedNotificationJob::dispatch($brief->id);
    }
}
