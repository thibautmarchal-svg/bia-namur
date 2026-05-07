/**
 * Enregistrement du Service Worker Bia Namur (vite-plugin-pwa).
 * Ne fait rien en dev (devOptions.enabled=false dans vite.config.js).
 * En prod : auto-update du SW + reload silencieux quand une nouvelle
 * version est dispo (pas de prompt intrusif sur la home pre-lancement).
 */

import { registerSW } from 'virtual:pwa-register';

const updateSW = registerSW({
    onNeedRefresh() {
        // Pour l'instant : reload auto silencieux (pas de toast intrusif).
        // En J6/J7 on remplacera par un toast discret "Nouvelle version dispo".
        updateSW(true);
    },
    onOfflineReady() {
        // Hook pour afficher un message discret "Bia Namur fonctionne hors ligne".
        // À cabler sur un composant <ConnectivityToast /> en J5/J6.
        if (import.meta.env.DEV) {
            console.info('[Bia PWA] hors ligne ready');
        }
    },
    onRegisteredSW(swUrl) {
        if (import.meta.env.DEV) {
            console.info('[Bia PWA] SW enregistre :', swUrl);
        }
    },
    onRegisterError(error) {
        console.error('[Bia PWA] echec enregistrement SW :', error);
    },
});
