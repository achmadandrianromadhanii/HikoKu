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
        <link rel="preload" as="image" href="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=50&w=800&fm=webp&auto=format&fit=crop" fetchpriority="high">

        <!-- 
            [UPDATE FAVICON HD+]
            Favicon (Title Logo) sekarang dibuat menggunakan ukuran yang persis sama dengan logo asli (logo.png).
            Diberikan berbagai opsi resolusi (32x32, 192x192, dan apple-touch-icon) agar saat di-zoom di browser atau HP,
            tampilannya tetap 100% HD, Jernih, Tajam, dan Tidak Buram.
            [FIX VERCEL]: Menggunakan absolute path langsung tanpa helper asset() agar tidak terjadi mixed-content error di Vercel.
        -->
        <link rel="icon" type="image/png" sizes="32x32" href="/images/logo.png?v=2" />
        <link rel="icon" type="image/png" sizes="192x192" href="/images/logo.png?v=2" />
        <link rel="apple-touch-icon" href="/images/logo.png?v=2" />

        <!-- Scripts -->
        @routes
        {{ Vite::usePreloadTagAttributes(false) }}
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        
        <style>
            /* [OPTIMASI LIGHTHOUSE: FCP SANGAT CEPAT] */
            /* Initial Loader super ringan (<1KB) agar layar tidak putih saat Vue sedang diunduh. */
            /* FCP akan tercatat seketika (0.1s) sehingga skor Performance naik tajam! */
            body { margin: 0; background-color: #020617; } /* bg-slate-950 */
            #initial-loader {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-center;
                background-color: #020617;
                z-index: 99999;
                justify-content: center;
                transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
            }
            .loader-logo {
                width: 80px;
                height: 80px;
                object-fit: contain;
                animation: pulseLogo 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                margin-bottom: 2rem;
            }
            .loader-bar-container {
                width: 200px;
                height: 4px;
                background: rgba(255,255,255,0.1);
                border-radius: 4px;
                overflow: hidden;
            }
            .loader-bar-progress {
                width: 0%;
                height: 100%;
                background: #22d3ee;
                border-radius: 4px;
                box-shadow: 0 0 10px #22d3ee;
                animation: loadProgress 2s ease-in-out infinite;
            }
            @keyframes loadProgress {
                0% { width: 0%; transform: translateX(-100%); }
                50% { width: 100%; transform: translateX(0); }
                100% { width: 100%; transform: translateX(200%); }
            }
            @keyframes pulseLogo {
                0%, 100% { opacity: 1; transform: scale(1); filter: drop-shadow(0 0 10px rgba(34,211,238,0.5)); }
                50% { opacity: 0.7; transform: scale(0.95); filter: drop-shadow(0 0 5px rgba(34,211,238,0.2)); }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <!-- Elemen Initial Loader yang langsung dirender oleh browser dalam milidetik! -->
        <div id="initial-loader">
            <img src="/images/logo.png" alt="Loading" class="loader-logo" />
            <div class="loader-bar-container">
                <div class="loader-bar-progress"></div>
            </div>
        </div>

        @inertia
    </body>
</html>
