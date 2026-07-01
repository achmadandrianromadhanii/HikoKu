<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
        |--------------------------------------------------------------------------
        | [SOLUSI NGROK & DEPLOYMENT] (HTTPS / SSL FIX)
        |--------------------------------------------------------------------------
        | Mempercayai semua proxy (ngrok, cloudflare, load balancer dll)
        | agar Laravel tahu bahwa ia diakses via HTTPS (X-Forwarded-Proto).
        | Ini memperbaiki error "MethodNotAllowedHttpException" (POST berubah jadi GET)
        | ketika melakukan aksi seperti Logout atau form lainnya di Ngrok.
        |--------------------------------------------------------------------------
        */
        $middleware->trustProxies(at: '*');

        /*
        |--------------------------------------------------------------------------
        | [UPDATE KEAMANAN WEBHOOK] (VERCEL/PRODUCTION READY)
        |--------------------------------------------------------------------------
        | Fungsi ini memerintahkan mesin keamanan Laravel 11 untuk TIDAK menanyakan 
        | token CSRF pada rute '/webhooks/midtrans'. Karena rute ini menerima tembakan 
        | POST murni dari server pusat Midtrans (Bukan dari Form HTML pengguna).
        | Tanpa kode ini, seluruh pembayaran akan dianggap GAGAL (Error 419) oleh server.
        |--------------------------------------------------------------------------
        */
        $middleware->validateCsrfTokens(except: [
            '/webhooks/midtrans',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // [UPDATE]: Mengatur arah pantulan otomatis jika mengakses URL yang salah status loginnya
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) => 
            $request->is('admin/*') ? route('admin.login') : route('login')
        );
        
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => 
            $request->is('admin/*') ? route('admin.dashboard') : route('home')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
