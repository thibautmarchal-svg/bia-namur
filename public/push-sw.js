/**
 * Push handler Bia Namur — importe via workbox.importScripts dans le SW
 * genere par vite-plugin-pwa.
 *
 * Listeners :
 *  - 'push'             : recoit le payload JSON et affiche une notification
 *  - 'notificationclick': focus sur l'onglet Bia ou ouvre l'URL fournie
 */

self.addEventListener('push', (event) => {
    if (! event.data) {
        return;
    }

    let payload;
    try {
        payload = event.data.json();
    } catch (_) {
        payload = { title: 'Bia Namur', body: event.data.text() };
    }

    const title = payload.title || 'Bia Namur';
    const options = {
        body: payload.body || '',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        lang: 'fr',
        tag: payload.tag || 'bia-default',
        renotify: false,
        data: {
            url: payload.url || '/',
            timestamp: Date.now(),
        },
        // Le contributeur a 1 chance de cliquer la notif quand elle apparait,
        // au-dela ca disparait — pas de spam.
        requireInteraction: false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                // Si un onglet Bia est deja ouvert, on le focus + navigate
                for (const client of windowClients) {
                    if ('focus' in client && client.url.includes(self.location.origin)) {
                        client.navigate(targetUrl);
                        return client.focus();
                    }
                }
                // Sinon nouveau tab
                if (self.clients.openWindow) {
                    return self.clients.openWindow(targetUrl);
                }
            }),
    );
});
