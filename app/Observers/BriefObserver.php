<?php

namespace App\Observers;

use App\Jobs\SendBriefPublishedNotificationJob;
use App\Models\Brief;

/**
 * Observer Brief : declenche le push notification quand un brief
 * passe a 'published' (transition depuis n'importe quel autre etat).
 *
 * Idempotent : si un brief est sauvegarde alors qu'il est deja
 * 'published' sans changement, le job n'est PAS re-dispatch.
 */
class BriefObserver
{
    public function updated(Brief $brief): void
    {
        if (! $brief->wasChanged('status')) {
            return;
        }

        $original = $brief->getOriginal('status');
        if ($original === Brief::STATUS_PUBLISHED) {
            return;
        }

        if ($brief->status !== Brief::STATUS_PUBLISHED) {
            return;
        }

        SendBriefPublishedNotificationJob::dispatch($brief->id);
    }
}
