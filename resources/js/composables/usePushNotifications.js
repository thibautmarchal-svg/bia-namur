import { ref, computed, onMounted } from 'vue';

/**
 * Composable pour gerer l'opt-in push notifications.
 *
 * Etats :
 *  - 'unsupported'     : navigateur sans support PushManager
 *  - 'denied'          : utilisateur a deja refuse au navigateur
 *  - 'prompt'          : aucune decision prise → on peut demander
 *  - 'granted-not-sub' : permission OK mais pas encore de subscription
 *  - 'subscribed'      : tout est en place
 *
 * Cycle d'opt-in :
 *  1. Verifier le support + l'etat actuel (au mount)
 *  2. Si l'utilisateur clique "Activer" → request permission browser
 *  3. Si granted → PushManager.subscribe() avec la VAPID public key
 *  4. POST /push/subscribe avec endpoint + cles → BDD
 *
 * Aucune permission n'est demandee tant que l'utilisateur ne clique pas
 * explicitement (pas de prompt automatique anti-pattern).
 */
export function usePushNotifications(vapidPublicKey) {
    const status = ref('unsupported');
    const busy = ref(false);
    const error = ref(null);

    const canSubscribe = computed(() => status.value === 'prompt' || status.value === 'granted-not-sub');
    const isSubscribed = computed(() => status.value === 'subscribed');

    const checkStatus = async () => {
        if (typeof window === 'undefined') return;

        if (! ('serviceWorker' in navigator) || ! ('PushManager' in window) || ! ('Notification' in window)) {
            status.value = 'unsupported';
            return;
        }

        if (Notification.permission === 'denied') {
            status.value = 'denied';
            return;
        }

        const reg = await navigator.serviceWorker.getRegistration();
        if (! reg) {
            status.value = 'prompt';
            return;
        }

        const sub = await reg.pushManager.getSubscription();
        if (sub) {
            status.value = 'subscribed';
            return;
        }

        status.value = Notification.permission === 'granted' ? 'granted-not-sub' : 'prompt';
    };

    const subscribe = async () => {
        if (! vapidPublicKey) {
            error.value = 'VAPID public key manquante (config serveur).';
            return false;
        }
        if (! canSubscribe.value) return false;

        busy.value = true;
        error.value = null;

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                status.value = permission === 'denied' ? 'denied' : 'prompt';
                return false;
            }

            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });

            const json = sub.toJSON();
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch('/push/subscribe', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    endpoint: json.endpoint,
                    keys: json.keys,
                }),
            });

            if (! response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            status.value = 'subscribed';
            return true;
        } catch (e) {
            error.value = e?.message || String(e);
            return false;
        } finally {
            busy.value = false;
        }
    };

    const unsubscribe = async () => {
        busy.value = true;
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (! sub) {
                status.value = 'prompt';
                return true;
            }

            const endpoint = sub.endpoint;
            await sub.unsubscribe();

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            await fetch('/push/unsubscribe', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ endpoint }),
            });

            status.value = Notification.permission === 'granted' ? 'granted-not-sub' : 'prompt';
            return true;
        } catch (e) {
            error.value = e?.message || String(e);
            return false;
        } finally {
            busy.value = false;
        }
    };

    onMounted(checkStatus);

    return {
        status,
        busy,
        error,
        canSubscribe,
        isSubscribed,
        subscribe,
        unsubscribe,
        recheck: checkStatus,
    };
}

/** Convertit la cle VAPID base64-url en Uint8Array attendu par PushManager. */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    const buffer = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) {
        buffer[i] = raw.charCodeAt(i);
    }
    return buffer;
}
