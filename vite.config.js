import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            includeAssets: [
                'favicon.svg',
                'favicon-16.png',
                'favicon-32.png',
                'logo.svg',
                'icons/icon-192.png',
                'icons/icon-512.png',
                'icons/icon-maskable-512.png',
                'icons/apple-touch-icon.png',
            ],
            manifest: {
                name: 'Bia Namur — Le carnet vivant des namurois',
                short_name: 'Bia Namur',
                description: 'Le carnet vivant des Namurois — brief hebdo, carte sentimentale, stories du patrimoine.',
                lang: 'fr',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                orientation: 'portrait',
                theme_color: '#C77F2C',
                background_color: '#F5EDDC',
                icons: [
                    {
                        src: '/icons/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icons/icon-maskable-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                    {
                        src: '/logo.svg',
                        sizes: 'any',
                        type: 'image/svg+xml',
                    },
                ],
            },
            workbox: {
                // Importe notre handler push (notifications + click) dans le SW genere.
                // Le fichier est servi depuis /push-sw.js (public/), donc relatif au scope.
                importScripts: ['/push-sw.js'],
                navigateFallbackDenylist: [/^\/admin/, /^\/api/, /^\/build/],
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                // Le chunk Map (Maplibre + deps, ~1 MB) ne sert que sur /carte.
                // Pas de precache — runtime caching plus bas s'en charge a la
                // 1re visite de /carte uniquement. Gain : -1 MB sur le first paint
                // pour les visiteurs qui n'ouvrent pas la carte.
                globIgnores: ['**/Map-*.js', '**/Map-*.css'],
                maximumFileSizeToCacheInBytes: 3 * 1024 * 1024,
                runtimeCaching: [
                    {
                        // Map chunk : cache des qu'il est telecharge (lors de la 1re
                        // visite de /carte), puis StaleWhileRevalidate pour les MAJ.
                        urlPattern: ({ url }) => /\/build\/assets\/Map-/.test(url.pathname),
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'bia-map-chunk',
                            expiration: {
                                maxEntries: 4,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                        },
                    },
                    {
                        urlPattern: /\/briefs\//,
                        handler: 'NetworkFirst',
                        options: { cacheName: 'bia-briefs', networkTimeoutSeconds: 5 },
                    },
                    {
                        urlPattern: /\/stories\//,
                        handler: 'CacheFirst',
                        options: { cacheName: 'bia-stories' },
                    },
                    {
                        urlPattern: /\/lieu\//,
                        handler: 'StaleWhileRevalidate',
                        options: { cacheName: 'bia-places' },
                    },
                ],
                skipWaiting: true,
                clientsClaim: true,
            },
            devOptions: {
                enabled: false,
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
