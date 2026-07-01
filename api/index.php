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

// [DEBUG FORCE]: Memaksa Laravel memunculkan error aslinya ke layar 
// agar kita tidak menebak-nebak dalam gelap.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
$_ENV['APP_DEBUG'] = 'true';
$_SERVER['APP_DEBUG'] = 'true';
putenv('APP_DEBUG=true');

// Komentar: Cek apakah aplikasi sedang dalam mode maintenance.
// Jika ya, tampilkan halaman maintenance dan hentikan eksekusi.
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// [UPDATE VERCEL FIX 500 SERVER ERROR]: 
// Vercel menggunakan file system yang Read-Only. Kita sudah mengarahkan cache dan views ke /tmp di vercel.json.
// Namun, jika foldernya belum ada, Laravel akan crash (500 Error). Script ini memastikan foldernya dibuat otomatis.
$tmpDirectories = ['/tmp/views', '/tmp/cache', '/tmp/sessions'];
foreach ($tmpDirectories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Komentar: Muat Composer autoloader agar semua class Laravel
// dan package vendor bisa digunakan.
require __DIR__ . '/../vendor/autoload.php';

// Komentar: Bootstrap aplikasi Laravel (service providers, facades, dll)
// dan langsung proses HTTP request yang masuk.
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
