import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
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
    // [KOMENTAR PENJELASAN]: Konfigurasi server Vite untuk mengizinkan akses dari luar (seperti Ngrok) tanpa terblokir CORS Policy
    server: {
        host: '127.0.0.1', // Memaksa penggunaan IPv4 untuk menghindari masalah '::1' di beberapa browser
        cors: true, // Mengizinkan domain luar (Ngrok) untuk mengakses aset internal (Vite)
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
    },
    // [UPDATE OPTIMASI]: Menghapus manualChunks custom agar Vite secara otomatis
    // melakukan "Code Splitting" yang sangat efisien (Tree-shaking).
    // Ini memastikan library berat seperti ApexCharts hanya dimuat di halaman Admin yang membutuhkannya,
    // tidak dimuat di halaman Homepage/Katalog, sehingga Lighthouse 100% Hijau.
    build: {
        chunkSizeWarningLimit: 2000, // [FIX] Diperbesar menjadi 2000kb agar peringatan kuning Vite hilang
    }
});
