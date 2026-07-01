import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';
import { configureEcho } from '@laravel/echo-vue';

/**
 * [UPDATE MIGRASI REVERB → PUSHER]:
 * configureEcho dari package @laravel/echo-vue digunakan oleh Vue composables
 * (useEcho, useListen) untuk mengetahui broadcaster mana yang aktif.
 * Diubah dari 'reverb' ke 'pusher' agar sinkron dengan konfigurasi
 * di bootstrap.ts dan .env (BROADCAST_CONNECTION=pusher).
 */
configureEcho({
    broadcaster: 'pusher',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => 'hikoku',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});