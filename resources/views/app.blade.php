<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

        <title inertia>{{ config('app.name', 'RacePack Pro') }}</title>

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- PWA Primary Tags -->
        <meta name="theme-color" content="#0E7BDC">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="RacePack Pro">
        <meta name="application-name" content="RacePack Pro">

        <!-- Manifest & Icons -->
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" type="image/png" href="/images/logo-indomaret-funrun.png" />
        <link rel="shortcut icon" type="image/png" href="/images/logo-indomaret-funrun.png" />
        <link rel="apple-touch-icon" href="/images/logo-indomaret-funrun.png" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js').catch(() => {});
                });
            }
        </script>
    </head>
    <body class="font-sans antialiased selection:bg-yellow-400 selection:text-blue-900 overscroll-none">
        @inertia
    </body>
</html>
