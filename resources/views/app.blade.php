<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'RacePack Pro') }}</title>

        <!-- Favicon -->
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
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
