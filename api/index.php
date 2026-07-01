<?php

/**
 * ==========================================================================
 * [FILE]: api/index.php
 * ==========================================================================
 * FUNGSI: Entry point serverless function Vercel untuk Laravel.
 *
 * TATA LETAK: File ini berada di folder /api/ karena Vercel mengenali
 *             folder ini sebagai lokasi serverless functions.
 *
 * CARA KERJA:
 * 1. Vercel menerima HTTP request dari browser.
 * 2. vercel.json mengarahkan request ke file ini (catch-all route).
 * 3. File ini mem-bootstrap aplikasi Laravel (autoload + app.php).
 * 4. Laravel memproses request (routing, controller, middleware, dll).
 * 5. Response dikirim kembali ke browser melalui Vercel.
 *
 * KEAMANAN: File ini identik dengan public/index.php bawaan Laravel,
 *           hanya path-nya yang disesuaikan agar bisa diakses dari /api/.
 *           Tidak ada logika custom yang ditambahkan.
 * ==========================================================================
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Komentar: Cek apakah aplikasi sedang dalam mode maintenance.
// Jika ya, tampilkan halaman maintenance dan hentikan eksekusi.
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Komentar: Muat Composer autoloader agar semua class Laravel
// dan package vendor bisa digunakan.
require __DIR__ . '/../vendor/autoload.php';

// Komentar: Bootstrap aplikasi Laravel (service providers, facades, dll)
// dan langsung proses HTTP request yang masuk.
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
