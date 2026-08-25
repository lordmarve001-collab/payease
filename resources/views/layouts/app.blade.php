<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">

        <title>{{ $title ?? config('app.name') }}</title>

        {{-- PWA Meta --}}
        <meta name="theme-color" content="#F59E0B">
        <meta name="color-scheme" content="dark">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'PayEase') }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="application-name" content="{{ config('app.name', 'PayEase') }}">
        <meta name="msapplication-TileColor" content="#03381e">
        <meta name="msapplication-tap-highlight" content="no">
        <meta name="format-detection" content="telephone=no">

        {{-- PWA Manifest --}}
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-96x96.png">
        <link rel="apple-touch-startup-image" href="/icons/screenshot-narrow.png">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-bg text-text-primary font-sans antialiased min-h-screen">
        {{ $slot }}

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(reg => console.log('SW registered:', reg.scope))
                        .catch(err => console.warn('SW registration failed:', err));
                });
            }
        </script>
    </body>
</html>
