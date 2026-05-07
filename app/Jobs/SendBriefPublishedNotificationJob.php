<?php

namespace App\Jobs;

use App\Models\Brief;
use App\Models\PushSubscription;
use App\Services\Push\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Envoie une notification push a tous les abonnes quand un nouveau brief
 * est publie. Lance par l'observer BriefObserver quand status passe a
 * 'published'.
 *
 * Si BIA_PUSH_ENABLED=false : le job se contente de logger et sort sans
 * envoyer. Permet de batir l'infrastructure progressivement sans risquer
 * d'envoyer des notifications par accident.
 */
class SendBriefPublishedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public readonly int $briefId) {}

    public function handle(WebPushService $webPush): void
    {
        if (! config('bia.push.enabled')) {
            Log::channel('moderation')->info('push.brief.disabled', ['brief_id' => $this->briefId]);

            return;
        }

        $brief = Brief::find($this->briefId);
        if (! $brief || $brief->status !== Brief::STATUS_PUBLISHED) {
            Log::channel('moderation')->info('push.brief.not_published', ['brief_id' => $this->briefId]);

            return;
        }

        $subscriptions = PushSubscription::query()->lazy(200);

        $payload = [
            'title' => 'Bia Namur — nouveau brief',
            'body' => $brief->title,
            'url' => '/brief/'.$brief->slug,
            'tag' => 'brief-'.$brief->slug,
        ];

        $stats = $webPush->sendToMany($subscriptions, $payload);

        Log::channel('moderation')->info('push.brief.dispatched', [
            'brief_id' => $brief->id,
            'sent' => $stats['sent'],
            'expired' => $stats['expired'],
            'failed' => $stats['failed'],
        ]);
    }
}
