<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Wrapper autour de minishlink/web-push pour envoyer des notifications
 * push aux utilisateurs abonnes.
 *
 * Gere :
 *  - construction du payload (titre, corps, url, icone, badge)
 *  - envoi groupe a une liste de subscriptions
 *  - cleanup des endpoints invalides (HTTP 404/410) → suppression BDD
 *  - logging des resultats (succes / echec / cleanup) en channel moderation
 *
 * En mode test (BIA_PUSH_FAKE=true), retourne un report mock sans appel
 * reseau. Permet aux tests Pest de verifier la logique sans dependre du
 * navigateur push gateway.
 */
class WebPushService
{
    private ?WebPush $webPush = null;

    /**
     * Envoie une notification a TOUTES les subscriptions de la liste.
     *
     * @param  iterable<PushSubscription>  $subscriptions
     * @return array{sent:int, expired:int, failed:int}
     */
    public function sendToMany(iterable $subscriptions, array $payload): array
    {
        $stats = ['sent' => 0, 'expired' => 0, 'failed' => 0];

        if (config('bia.push.fake', false)) {
            foreach ($subscriptions as $_) {
                $stats['sent']++;
            }

            return $stats;
        }

        $webPush = $this->client();
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $bySubscription = [];
        foreach ($subscriptions as $sub) {
            $reportSubscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh_key,
                'authToken' => $sub->auth_token,
            ]);
            $webPush->queueNotification($reportSubscription, $body);
            $bySubscription[$sub->endpoint] = $sub;
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            $sub = $bySubscription[$endpoint] ?? null;

            if ($report->isSuccess()) {
                $stats['sent']++;
                $sub?->update(['last_used_at' => now()]);

                continue;
            }

            // 404 / 410 : endpoint expire chez le push gateway → on supprime
            if ($report->isSubscriptionExpired()) {
                $stats['expired']++;
                $sub?->delete();
                Log::channel('moderation')->info('push.subscription_expired', [
                    'endpoint_hash' => $sub?->endpoint_hash,
                ]);

                continue;
            }

            $stats['failed']++;
            Log::channel('moderation')->warning('push.send_failed', [
                'endpoint_hash' => $sub?->endpoint_hash,
                'reason' => $report->getReason(),
            ]);
        }

        return $stats;
    }

    private function client(): WebPush
    {
        if ($this->webPush) {
            return $this->webPush;
        }

        $auth = [
            'VAPID' => [
                'subject' => config('bia.push.vapid_subject', 'mailto:contact@bianamur.be'),
                'publicKey' => config('bia.push.vapid_public_key'),
                'privateKey' => config('bia.push.vapid_private_key'),
            ],
        ];

        return $this->webPush = new WebPush($auth, ['TTL' => 86400]);
    }
}
