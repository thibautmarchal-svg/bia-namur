<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Endpoints subscribe / unsubscribe pour les push notifications.
 *
 * Le frontend appelle subscribe() apres que l'utilisateur ait
 * explicitement clique "Activer les notifications" ET accorde la
 * permission au navigateur. On stocke l'endpoint + cles publiques.
 */
class PushController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:191'],
            'keys.auth' => ['required', 'string', 'max:191'],
        ]);

        $endpoint = $data['endpoint'];
        $hash = PushSubscription::hashEndpoint($endpoint);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint_hash' => $hash],
            [
                'user_id' => Auth::id(),
                'endpoint' => $endpoint,
                'p256dh_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'user_agent' => substr((string) $request->userAgent(), 0, 191),
                'last_used_at' => null,
            ],
        );

        Log::channel('moderation')->info('push.subscribed', [
            'user_id' => Auth::id(),
            'endpoint_hash' => $hash,
            'is_new' => $subscription->wasRecentlyCreated,
        ]);

        return response()->json([
            'ok' => true,
            'subscription_id' => $subscription->id,
        ]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
        ]);

        $hash = PushSubscription::hashEndpoint($data['endpoint']);

        $deleted = PushSubscription::query()
            ->where('user_id', Auth::id())
            ->where('endpoint_hash', $hash)
            ->delete();

        Log::channel('moderation')->info('push.unsubscribed', [
            'user_id' => Auth::id(),
            'deleted' => $deleted,
        ]);

        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }
}
