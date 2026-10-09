import './bootstrap';
import '../css/app.css';
import { primary } from './palette';
import directives from './directives';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast from "vue-toastification";
import { useToast } from "vue-toastification";
import 'animate.css';
import "vue-toastification/dist/index.css";
import "vue-multiselect/dist/vue-multiselect.css";

const brand = 'Invita';

createInertiaApp({
    title: (title) => (title.includes(brand) ? title : `${title} | ${brand}`),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const inertiaApp =   createApp({render: () => h(App, props)})
            .use(plugin)
            .use(Toast)
            .use(directives)
            .component('useToast', useToast)
            .use(ZiggyVue);
            inertiaApp.config.globalProperties.$toast = useToast();
            inertiaApp.mount(el);
    },
    progress: {
        color: primary.DEFAULT,
    },
});

// Registered app-wide (not gated on login) so "Add to Home Screen" and the
// offline fallback work even for guests browsing the public marketplace.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Non-fatal: the app still works without it, just without offline/push support.
        });
    });
}
