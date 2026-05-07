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
                navigateFallbackDenylist: [/^\/admin/, /^\/api/, /^\/build/],
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                runtimeCaching: [
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
