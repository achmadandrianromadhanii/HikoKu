/**
 * ==========================================================================
 * [FILE]: bootstrap.ts
 * ==========================================================================
 * FUNGSI: Inisialisasi library dasar (Axios & Laravel Echo) sebelum
 *         aplikasi Vue dimuat. File ini diimpor oleh app.ts.
 *
 * [UPDATE MIGRASI]: Broadcaster diubah dari 'reverb' ke 'pusher'.
 * ALASAN: Reverb membutuhkan server WebSocket persisten (php artisan reverb:start)
 *         yang tidak bisa berjalan di platform serverless seperti Vercel.
 *         Pusher adalah layanan cloud yang menangani WebSocket di sisi mereka,
 *         sehingga cocok untuk deployment serverless.
 *
 * CARA KERJA:
 * 1. Axios di-setup dengan header X-Requested-With untuk identifikasi AJAX.
 * 2. Pusher-js di-import dan di-attach ke window global (dibutuhkan Laravel Echo).
 * 3. Laravel Echo dikonfigurasi dengan broadcaster 'pusher' menggunakan
 *    environment variables VITE_PUSHER_* yang di-inject oleh Vite saat build.
 * 4. Echo akan otomatis terhubung ke server Pusher untuk menerima event realtime
 *    (seperti NewRentalCreated, PaymentSuccess di admin dashboard).
 * ==========================================================================
 */

import axios from 'axios';
window.axios = axios;

// Komentar: Header ini memberitahu backend bahwa request berasal dari AJAX (XMLHttpRequest),
// bukan dari form submission biasa. Laravel menggunakan ini untuk mendeteksi request type.
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Komentar: Deklarasi tipe global agar TypeScript mengenali
// window.Pusher dan window.Echo tanpa error kompilasi.
declare global {
    interface Window {
        Pusher: any;
        Echo: any;
    }
}

// Komentar: Pusher-js harus di-attach ke window global karena
// Laravel Echo mengaksesnya melalui window.Pusher secara internal.
window.Pusher = Pusher;

/**
 * [UPDATE MIGRASI REVERB → PUSHER]:
 * Konfigurasi Echo sekarang menggunakan broadcaster 'pusher' dengan parameter:
 * - key: App Key dari dashboard Pusher (disimpan di .env sebagai VITE_PUSHER_APP_KEY)
 * - cluster: Region server Pusher terdekat (misal: 'ap1' untuk Asia Pacific)
 * - forceTLS: true — selalu gunakan koneksi terenkripsi (wss://) untuk keamanan
 * - disableStats: true — menonaktifkan statistik internal Pusher agar tidak
 *   menambah request yang bisa mempengaruhi performa (LCP/INP tetap optimal)
 */
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'ap1',
    forceTLS: true,
    disableStats: true,
});
