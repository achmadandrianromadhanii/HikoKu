<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>hikoku</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
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
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
