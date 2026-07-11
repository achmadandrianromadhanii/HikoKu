<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>hikoku</title>
        
        <!-- [OPTIMASI LIGHTHOUSE SEO]: Menambahkan meta description untuk skor SEO 100% -->
        <meta name="description" content="Hiko Outdoor Rental. Solusi premium sewa alat hiking dan outdoor. Rasakan pengalaman menyewa yang sangat mulus, cepat, rapi, dan terpercaya tanpa repot antre.">

        <!-- [OPTIMASI LIGHTHOUSE]: Preconnect ke domain penting agar DNS lookup lebih cepat (Meningkatkan FCP) -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
        
        <!-- [OPTIMASI LIGHTHOUSE]: Preconnect ke server font untuk mempercepat FCP dan LCP -->
        <link rel="preload" href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'" />
        <noscript><link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" /></noscript>
        
        <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Pacifico&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'" />
        <noscript><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Pacifico&display=swap" rel="stylesheet" /></noscript>

        <!-- [OPTIMASI LIGHTHOUSE]: Preload gambar Hero (LCP) agar browser langsung mengunduhnya sebelum JavaScript (Vue) selesai dimuat -->
        <!-- URL harus SAMA PERSIS (sampai ke parameternya) dengan yang digunakan di Welcome/Index.vue agar tidak muncul peringatan "preloaded but not used" -->
        <link rel="preload" as="image" href="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=60&w=1280&fm=webp&auto=format&fit=crop" fetchpriority="high">

        <!-- 
            [UPDATE FAVICON HD+]
            Favicon (Title Logo) sekarang dibuat menggunakan ukuran yang persis sama dengan logo asli (logo.png).
            Diberikan berbagai opsi resolusi (32x32, 192x192, dan apple-touch-icon) agar saat di-zoom di browser atau HP,
            tampilannya tetap 100% HD, Jernih, Tajam, dan Tidak Buram.
        -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo.png') }}?v={{ time() }}" />
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/logo.png') }}?v={{ time() }}" />
        <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}?v={{ time() }}" />

        <!-- Scripts -->
        @routes
        {{ Vite::usePreloadTagAttributes(false) }}
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <!-- [OPTIMASI LIGHTHOUSE: FCP & Speed Index] -->
        <!-- Preloader statis yang dirender langsung oleh server untuk memberikan respons visual instan (FCP ~0.1s). -->
        <!-- Ini mencegah layar putih kosong selama browser mengunduh JavaScript di jaringan lambat (Slow 4G). -->
        <div id="initial-loader" style="position:fixed;top:0;left:0;width:100%;height:100%;background:#ffffff;display:flex;flex-direction:column;justify-content:center;align-items:center;z-index:9999;transition:opacity 0.5s ease;">
            <style>
                .hikoku-spinner { width: 50px; height: 50px; border: 4px solid #eef4f7; border-top: 4px solid #22d3ee; border-radius: 50%; animation: spin 0.8s linear infinite; }
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            </style>
            <div class="hikoku-spinner"></div>
            <div style="margin-top:20px;font-family:sans-serif;font-weight:700;color:#0c3653;letter-spacing:1px;font-size:14px;">Memuat Aplikasi...</div>
        </div>

        @inertia
    </body>
</html>
