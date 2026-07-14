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
        <link rel="preload" href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" as="style" /><link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
        
        
        <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Pacifico&display=swap" as="style" /><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Pacifico&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
        

        <!-- [OPTIMASI LIGHTHOUSE]: Preload gambar Hero (LCP) agar browser langsung mengunduhnya sebelum JavaScript (Vue) selesai dimuat -->
        <!-- [OPTIMASI LIGHTHOUSE]: Hanya lakukan preconnect dan preload Unsplash jika berada di Homepage (/) agar halaman lain tidak terbebani -->
        @if(request()->is('/'))
            <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
            <link rel="preload" as="image" href="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=50&w=800&fm=webp&auto=format&fit=crop" fetchpriority="high">
        @endif

        <!-- 
            [UPDATE FAVICON HD+]
            Favicon (Title Logo) sekarang dibuat menggunakan ukuran yang persis sama dengan logo asli (logo.png).
            Diberikan berbagai opsi resolusi (32x32, 192x192, dan apple-touch-icon) agar saat di-zoom di browser atau HP,
            tampilannya tetap 100% HD, Jernih, Tajam, dan Tidak Buram.
            [FIX VERCEL]: Menggunakan absolute path langsung tanpa helper asset() agar tidak terjadi mixed-content error di Vercel.
        -->
        <link rel="icon" type="image/webp" sizes="32x32" href="/images/logo.webp?v=2" />
        <link rel="icon" type="image/webp" sizes="192x192" href="/images/logo.webp?v=2" />
        <link rel="apple-touch-icon" href="/images/logo.webp?v=2" />

        <!-- Scripts -->
        {{ Vite::usePreloadTagAttributes(['crossorigin' => 'anonymous']) }}
        @routes
        
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        
        <style>
            /* [OPTIMASI LIGHTHOUSE: FCP SANGAT CEPAT] */
            body { margin: 0; background-color: #D4DCE4; } /* bg-slate-950 */
        </style>
    </head>
    <body class="font-sans antialiased">


        @inertia
    </body>
</html>
