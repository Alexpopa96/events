import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';
import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue, route } from '../../vendor/tightenco/ziggy';
import directives from './directives';

const brand = 'Invita';

createServer((page) => {
    const ziggy = { ...page.props.ziggy, location: new URL(page.props.ziggy.location) };

    // Components call route() from <script setup>, which in the browser resolves to the
    // global from @routes; on the server it has to be provided per request.
    globalThis.route = (name, params, absolute, config = ziggy) => route(name, params, absolute, config);

    return createInertiaApp({
        page,
        render: renderToString,
        title: (title) => (title.includes(brand) ? title : `${title} | ${brand}`),
        resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
        setup({ App, props, plugin }) {
            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(directives)
                .use(ZiggyVue, ziggy);
        },
    });
});
