import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/main.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            manifest: {
                name: 'Local POS',
                short_name: 'POS',
                description: 'Offline-first Point of Sale & Inventory System',
                theme_color: '#1E6FEB',
                background_color: '#ffffff',
                display: 'standalone',
                start_url: '/',
                icons: [
                    {
                        src: '/icons/icon-192.svg',
                        sizes: '192x192',
                        type: 'image/svg+xml',
                    },
                    {
                        src: '/icons/icon-512.svg',
                        sizes: '512x512',
                        type: 'image/svg+xml',
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,svg,png,woff2}'],
                navigateFallback: '/',
                navigateFallbackDenylist: [/^\/api/],
                runtimeCaching: [
                    {
                        urlPattern: /^\/api\/.*/i,
                        handler: 'NetworkOnly',
                    },
                ],
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        proxy: {
            '/api': 'http://127.0.0.1:8000',
        },
    },
});
