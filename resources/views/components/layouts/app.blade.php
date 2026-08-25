<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
    <title>{{ $siteSettings->site_name ?? 'PayEase' }}</title>

    {{-- PWA Meta --}}
    <meta name="theme-color" content="#F59E0B">
    <meta name="color-scheme" content="dark">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $siteSettings->site_name ?? 'PayEase' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="{{ $siteSettings->site_name ?? 'PayEase' }}">
    <meta name="msapplication-TileColor" content="#03381e">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="format-detection" content="telephone=no">
    <meta name="description" content="{{ $siteSettings->site_tagline ?? 'Fast, secure digital payments for Nigerians.' }}">

    {{-- PWA Manifest --}}
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/icon-72x72.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/icons/icon-144x144.png">
    <link rel="apple-touch-icon" sizes="128x128" href="/icons/icon-128x128.png">

    {{-- iOS Splash Screens --}}
    <link rel="apple-touch-startup-image" href="/icons/screenshot-narrow.png">

    @if($siteSettings->favicon_path)
        <link rel="icon" type="image/png" href="{{ $siteSettings->faviconUrl() }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --color-primary: {{ $siteSettings->primary_color ?? '#F59E0B' }};
            --color-secondary: {{ $siteSettings->secondary_color ?? '#8B5CF6' }};
            @if($siteSettings->accent_color ?? null)
                --color-accent: {{ $siteSettings->accent_color }};
            @endif
        }
    </style>
    {!! $siteSettings->custom_head_html ?? '' !!}
</head>
<body class="app-bg text-text-primary font-sans antialiased min-h-screen">
    {{ $slot }}

    {{-- PWA Install Banner --}}
    <div id="pwa-install-banner"
         class="fixed bottom-0 inset-x-0 z-[9999] p-4 transform translate-y-full transition-transform duration-500 ease-out pointer-events-none"
         style="pointer-events: auto;">
        <div class="max-w-lg mx-auto rounded-2xl border border-border overflow-hidden shadow-2xl"
             style="background: linear-gradient(135deg, #0F172A 0%, #1a1a2e 100%);">
            <div class="p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                     style="background: var(--color-primary, #F59E0B);">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white font-semibold text-sm">Install PayEase</p>
                    <p class="text-white/60 text-xs mt-0.5">Add to home screen for quick access</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button id="pwa-install-btn"
                            class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition-all active:scale-95"
                            style="background: var(--color-primary, #F59E0B);">
                        Install
                    </button>
                    <button id="pwa-dismiss-btn"
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-white/40 hover:text-white/70 hover:bg-white/10 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => {
                        console.log('SW registered:', reg.scope);
                        reg.addEventListener('updatefound', () => {
                            const newWorker = reg.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    newWorker.postMessage({ type: 'SKIP_WAITING' });
                                }
                            });
                        });
                    })
                    .catch(err => console.warn('SW registration failed:', err));
            });
        }

        let deferredPrompt = null;
        const installBanner = document.getElementById('pwa-install-banner');
        const installBtn = document.getElementById('pwa-install-btn');
        const dismissBtn = document.getElementById('pwa-dismiss-btn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const dismissed = localStorage.getItem('pwa_install_dismissed');
            const installed = localStorage.getItem('pwa_installed');
            if (!dismissed && !installed) {
                setTimeout(() => {
                    installBanner.classList.remove('translate-y-full');
                    installBanner.classList.add('translate-y-0');
                }, 2000);
            }
        });

        installBtn.addEventListener('click', async () => {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                localStorage.setItem('pwa_installed', 'true');
                installBanner.classList.add('translate-y-full');
            }
            deferredPrompt = null;
        });

        dismissBtn.addEventListener('click', () => {
            localStorage.setItem('pwa_install_dismissed', 'true');
            installBanner.classList.add('translate-y-full');
        });

        window.addEventListener('appinstalled', () => {
            localStorage.setItem('pwa_installed', 'true');
            installBanner.classList.add('translate-y-full');
            deferredPrompt = null;
        });
    </script>

    {!! $siteSettings->custom_footer_html ?? '' !!}
</body>
</html>
