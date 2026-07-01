<?php

/**
 * ============================================================================
 * [MIGRATION]: Menambahkan kolom notification_email ke tabel users.
 * ============================================================================
 * FUNGSI: Menyimpan preferensi user apakah ingin menerima notifikasi email
 *         terkait update pesanan, promosi, dan info penting lainnya.
 * CARA KERJA: Kolom boolean, default true (aktif). User bisa toggle on/off
 *             dari halaman Keamanan di Profile mobile.
 * TATA LETAK: Kolom ditaruh setelah 'is_active' di tabel users.
 * ============================================================================
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menjalankan migration — menambah kolom notification_email.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // [PENJELASAN]: Kolom boolean untuk preferensi notifikasi email.
            // Default true agar user otomatis menerima notifikasi saat pertama mendaftar.
            $table->boolean('notification_email')->default(true)->after('is_active');
        });
    }

    /**
     * Membatalkan migration — menghapus kolom notification_email.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_email');
        });
    }
};
