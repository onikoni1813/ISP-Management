<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
        <meta name="theme-color" content="#0f172a">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <!-- Favicon & App Icons -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico?v=3">
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=3">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=3">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=3">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=3">
        <link rel="icon" type="image/png" sizes="192x192" href="/icon-192.png?v=3">
        <link rel="icon" type="image/png" sizes="512x512" href="/icon-512.png?v=3">
        <link rel="icon" type="image/png" href="/logo.png?v=3">

        <!-- PWA Manifest -->
        <link rel="manifest" href="/manifest.json?v=3">

        <title inertia>{{ config('app.name', 'Pirgacha Internet') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js').catch(err => console.log('SW registration error:', err));
                });
            }
        </script>
    </head>
    <body class="font-sans antialiased bg-slate-950 text-slate-100">
        @inertia
    </body>
</html>
