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
    // [UPDATE OPTIMASI]: Memisahkan bundle vendor untuk memecah ukuran file besar (chunking)
    // Tujuannya agar LCP dan INP lebih stabil dan loading pertama menjadi jauh lebih ngebut (skor Lighthouse maksimal).
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('apexcharts')) return 'vendor-charts';
                        if (id.includes('vue')) return 'vendor-vue';
                        return 'vendor'; // Memisahkan semua sisa library pihak ketiga
                    }
                }
            }
        }
    }
});
