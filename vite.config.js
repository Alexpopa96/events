import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

// VITE_LAN_HOST=192.168.x.x npm run dev -> dev server reachable from phones on the same Wi-Fi
const lanHost = process.env.VITE_LAN_HOST;

export default defineConfig({
    server: lanHost
        ? { host: '0.0.0.0', hmr: { host: lanHost }, cors: true }
        : undefined,
    ssr: {
        // CommonJS packages imported with named exports must be bundled for the SSR build.
        noExternal: ['vue-toastification'],
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.js',
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
    ],
});
