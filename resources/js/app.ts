import '../css/app.css';
import './bootstrap';

import { createInertiaApp, Link } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
// [UPDATE VERSI VERCEL]: Mengimpor Ziggy dari NPM package (ziggy-js) alih-alih folder PHP (/vendor).
// Ini sangat penting karena Vercel mem-build JS secara terpisah dan tidak punya folder /vendor PHP di fase ini.
import { ZiggyVue } from 'ziggy-js';
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

        // Inisialisasi Vue App
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia) // Registrasi Pinia state management (diperlukan untuk shopping cart & auth)
            .use(ZiggyVue)
            .component('Link', Link);

        // Mount aplikasi ke DOM
        app.mount(el);
    },
    // [OPTIMASI UX & PERFORMANCE: Top Loading Progress Bar]
    // Menghidupkan progress bar dengan konfigurasi kustom agar website terasa sangat responsif.
    progress: {
        // [KUNCI KECEPATAN UX]: Mengubah delay bawaan (250ms) menjadi 0. 
        // Dengan begini, loading bar (garis biru di atas) langsung muncul SEKETIKA 
        // saat link atau tombol diklik, menghilangkan sensasi "lag" atau lemot.
        delay: 0,
        // Warna cyan terang yang serasi dengan desain tema Hiko
        color: '#22d3ee',
        // Menggunakan CSS bawaan dari NProgress (ringan dan teruji)
        includeCSS: true,
        // Mematikan ikon spinner berputar di pojok kanan agar tampilan tetap rapi, bersih, dan profesional (hanya garis atas)
        showSpinner: false,
    },
});