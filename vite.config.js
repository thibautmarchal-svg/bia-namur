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
                'logo.svg',
                'icons/icon-512.svg',
                'icons/icon-maskable-512.svg',
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
                        src: '/icons/icon-512.svg',
                        sizes: '512x512',
                        type: 'image/svg+xml',
                    },
                    {
                        src: '/icons/icon-maskable-512.svg',
                        sizes: '512x512',
                        type: 'image/svg+xml',
                        purpose: 'maskable',
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
