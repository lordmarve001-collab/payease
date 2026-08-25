<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteSettings->site_name ?? 'PayEase' }} — {{ $siteSettings->site_tagline ?: __('Fast, Secure Payments for Nigeria') }}</title>
    <meta name="description" content="{{ $siteSettings->site_description ?: __('PayEase is a fast, secure digital payment platform for Nigerians. Send money, pay bills, save with Ajo, and more.') }}">
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
        .hero-gradient { background: linear-gradient(135deg, #0F172A 0%, #1a1040 40%, #1E1050 70%, #0F172A 100%); }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); }
        .glass-light { background: rgba(255,255,255,0.08); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.12); }
        .gold-glow { box-shadow: 0 0 60px rgba(217,119,6,0.25), 0 0 120px rgba(217,119,6,0.1); }
        .purple-glow { box-shadow: 0 0 60px rgba(124,58,237,0.25), 0 0 120px rgba(124,58,237,0.1); }
        .text-gradient { background: linear-gradient(135deg, #F59E0B, #FBBF24, #F59E0B); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .text-gradient-purple { background: linear-gradient(135deg, #A78BFA, #7C3AED, #A78BFA); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero-dot { background-image: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px); background-size: 32px 32px; }
        .section-fade { opacity: 0; transform: translateY(30px); transition: opacity 0.7s ease, transform 0.7s ease; }
        .section-fade.visible { opacity: 1; transform: translateY(0); }
        .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card-hover:hover { transform: translateY(-6px); box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .phone-mockup { filter: drop-shadow(0 20px 60px rgba(217,119,6,0.3)); }
        .slider-slide { transition: opacity 0.8s ease-in-out, transform 0.8s ease-in-out; }
        @keyframes scroll-hint { 0%, 100% { transform: translateY(0); opacity: 1; } 50% { transform: translateY(8px); opacity: 0.5; } }
        .scroll-hint { animation: scroll-hint 2s ease-in-out infinite; }
        .feature-icon-ring { background: linear-gradient(135deg, rgba(217,119,6,0.15), rgba(124,58,237,0.15)); }
        .cta-gradient { background: linear-gradient(135deg, #D97706, #B45309, #7C3AED); }
        .stats-divider { background: linear-gradient(90deg, transparent, rgba(217,119,6,0.3), transparent); }

        /* ── Hero slider (vanilla JS, no Alpine dependency) ── */
        .hero-slide { opacity: 0; transform: translateY(24px); transition: opacity 0.7s cubic-bezier(0.16,1,0.3,1), transform 0.7s cubic-bezier(0.16,1,0.3,1); pointer-events: none; }
        .hero-slide.is-active { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .phone-screen { display: none; }
        .phone-screen.is-active { display: block; animation: hero-screen-in 0.5s cubic-bezier(0.16,1,0.3,1) both; }
        @keyframes hero-screen-in { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .hero-dot-btn { width: 10px; background: rgba(255,255,255,0.2); transition: all 0.3s ease; }
        .hero-dot-btn:hover { background: rgba(255,255,255,0.4); }
        .hero-dot-btn.is-active { width: 40px; background: linear-gradient(90deg, #F59E0B, #A78BFA); box-shadow: 0 0 12px rgba(245,158,11,0.6); }
        .text-gradient-animate {
            background: linear-gradient(120deg, #FDE68A, #F59E0B, #FBBF24, #8B5CF6, #F59E0B);
            background-size: 300% 300%;
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent; color: transparent;
            animation: hero-gradient-text 6s ease infinite;
        }
        @keyframes hero-gradient-text { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
    </style>
</head>
<body class="font-sans antialiased bg-[#0F172A] text-white overflow-x-hidden">

    {{-- ═══════════════ TOP BAR ═══════════════ --}}
    @if(($siteSettings->support_phone ?? '') !== '' || ($siteSettings->support_email ?? '') !== '' || count($siteSettings->social_links ?? []) > 0)
    <div class="bg-white/5 border-b border-white/5 py-2 text-sm hidden sm:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <div class="flex items-center gap-5 text-white/50">
                @if(($siteSettings->support_phone ?? '') !== '')
                    <a href="tel:{{ $siteSettings->support_phone }}" class="flex items-center gap-1.5 hover:text-white/80 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $siteSettings->support_phone }}
                    </a>
                @endif
                @if(($siteSettings->support_email ?? '') !== '')
                    <a href="mailto:{{ $siteSettings->support_email }}" class="flex items-center gap-1.5 hover:text-white/80 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $siteSettings->support_email }}
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @foreach(($siteSettings->social_links ?? []) as $social)
                    @if(($social['url'] ?? '') !== '')
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" class="text-white/30 hover:text-white/70 transition-colors">
                            @if(($social['platform'] ?? '') === 'twitter')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            @elseif(($social['platform'] ?? '') === 'facebook')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            @elseif(($social['platform'] ?? '') === 'instagram')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            @elseif(($social['platform'] ?? '') === 'linkedin')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            @elseif(($social['platform'] ?? '') === 'youtube')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            @elseif(($social['platform'] ?? '') === 'tiktok')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                            @elseif(($social['platform'] ?? '') === 'telegram')
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════ NAVBAR ═══════════════ --}}
    <nav id="landing-nav" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div id="nav-bar" class="glass rounded-2xl px-4 sm:px-6 py-3 flex items-center justify-between transition-all duration-300">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0">
                    @if($siteSettings->logo_path)
                        <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name ?? 'PayEase' }}" class="h-9 w-auto object-contain bg-white rounded-lg px-1.5 py-0.5">
                    @else
                        <div class="w-9 h-9 bg-gradient-to-br from-primary to-accent rounded-xl flex items-center justify-center shadow-glow-primary">
                            <span class="text-white text-lg font-bold">₦</span>
                        </div>
                    @endif
                    <span class="text-xl font-bold font-display text-white hidden sm:block">{{ $siteSettings->site_name ?? 'PayEase' }}</span>
                </a>

                {{-- Desktop Links --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="#features" class="px-4 py-2 text-sm font-medium text-white/70 hover:text-white rounded-xl hover:bg-white/5 transition-all duration-200 cursor-pointer">Features</a>
                    <a href="#how-it-works" class="px-4 py-2 text-sm font-medium text-white/70 hover:text-white rounded-xl hover:bg-white/5 transition-all duration-200 cursor-pointer">How It Works</a>
                    <a href="#security" class="px-4 py-2 text-sm font-medium text-white/70 hover:text-white rounded-xl hover:bg-white/5 transition-all duration-200 cursor-pointer">Security</a>
                    <a href="#testimonials" class="px-4 py-2 text-sm font-medium text-white/70 hover:text-white rounded-xl hover:bg-white/5 transition-all duration-200 cursor-pointer">Reviews</a>
                </div>

                {{-- Auth Buttons --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex px-5 py-2.5 text-sm font-semibold text-white/90 hover:text-white rounded-xl hover:bg-white/5 transition-all duration-200 cursor-pointer">
                        {{ __('Log In') }}
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-primary to-primary-dark rounded-xl hover:shadow-glow-primary transition-all duration-300 active:scale-95 cursor-pointer">
                        {{ __('Get Started') }}
                    </a>

                    {{-- Mobile Menu Button --}}
                    <button id="mobile-menu-btn" class="md:hidden ml-1 p-2 text-white/70 hover:text-white rounded-xl hover:bg-white/5 transition-colors cursor-pointer" aria-label="Toggle menu">
                        <svg id="menu-icon-open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        <svg id="menu-icon-close" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            {{-- Mobile Menu --}}
            <div id="mobile-menu" class="md:hidden mt-2 glass rounded-2xl p-4 space-y-1 hidden"
                 style="transition: opacity 0.2s ease, transform 0.2s ease;">
                <a href="#features" class="mobile-menu-link block px-4 py-3 text-sm font-medium text-white/70 hover:text-white rounded-xl hover:bg-white/5 transition-all cursor-pointer">Features</a>
                <a href="#how-it-works" class="mobile-menu-link block px-4 py-3 text-sm font-medium text-white/70 hover:text-white rounded-xl hover:bg-white/5 transition-all cursor-pointer">How It Works</a>
                <a href="#security" class="mobile-menu-link block px-4 py-3 text-sm font-medium text-white/70 hover:text-white rounded-xl hover:bg-white/5 transition-all cursor-pointer">Security</a>
                <a href="#testimonials" class="mobile-menu-link block px-4 py-3 text-sm font-medium text-white/70 hover:text-white rounded-xl hover:bg-white/5 transition-all cursor-pointer">Reviews</a>
                <div class="border-t border-white/10 my-2"></div>
                <a href="{{ route('login') }}" class="mobile-menu-link block px-4 py-3 text-sm font-semibold text-white/80 hover:text-white rounded-xl hover:bg-white/5 transition-all cursor-pointer">Log In</a>
            </div>
        </div>
    </nav>

    {{-- ═══════════════ HERO SECTION ═══════════════ --}}
    <section id="hero" class="hero-gradient relative min-h-screen flex items-center pt-28 pb-20 overflow-hidden">
        {{-- Background decorations --}}
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute inset-0 hero-dot opacity-40"></div>
            <div class="absolute -top-32 -left-32 w-[34rem] h-[34rem] bg-primary/20 rounded-full blur-[140px] animate-blob"></div>
            <div class="absolute bottom-0 right-0 w-[40rem] h-[40rem] bg-secondary/20 rounded-full blur-[160px] animate-blob" style="animation-delay: -5s"></div>
            <div class="absolute top-1/3 right-1/4 w-72 h-72 bg-accent/15 rounded-full blur-[120px] animate-float-slow"></div>
            <div class="absolute bottom-0 inset-x-0 h-40 bg-gradient-to-b from-transparent to-[#0F172A]"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">

                {{-- Left: Content --}}
                <div class="text-center lg:text-left">
                    {{-- Badge --}}
                    <div class="inline-flex items-center gap-2.5 glass-light rounded-full pl-2 pr-4 py-2 mb-8 animate-fade-in-down">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
                        </span>
                        <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        <span class="text-xs font-semibold text-white/80 tracking-wide">Partner-Bank Secured · Funds held with our licensed banking partner</span>
                    </div>

                    {{-- Slider Headlines --}}
                    <div class="relative h-[200px] sm:h-[220px] lg:h-[240px]" id="hero-headlines">
                        @foreach([
                            ['title' => 'Send Money', 'subtitle' => 'Instantly', 'desc' => 'Transfer money to anyone, anywhere in Nigeria. Fast, secure, and always available.'],
                            ['title' => 'Grow Your', 'subtitle' => 'Savings', 'desc' => 'Join Ajo groups and watch your savings multiply. Earn more with collective saving power.'],
                            ['title' => 'Pay Bills', 'subtitle' => 'Effortlessly', 'desc' => 'Airtime, data, electricity, cable TV — pay all your bills in one place.'],
                        ] as $i => $slide)
                            <div class="hero-slide absolute inset-0" data-slide="{{ $i }}">
                                <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold leading-[1.1] tracking-tight">
                                    <span class="text-white">{{ $slide['title'] }}</span><br>
                                    <span class="text-gradient-animate">{{ $slide['subtitle'] }}</span>
                                </h1>
                                <p class="mt-5 text-lg text-white/60 max-w-lg mx-auto lg:mx-0 leading-relaxed">{{ $slide['desc'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- CTA Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mt-8 animate-fade-in-up" style="animation-delay: 0.15s">
                        <a href="{{ route('register') }}" class="group relative inline-flex items-center justify-center gap-2.5 px-8 py-4 text-white font-bold text-base rounded-2xl bg-gradient-animate shadow-glow-primary hover:-translate-y-0.5 hover:shadow-glow-primary transition-all duration-300 active:scale-95 cursor-pointer overflow-hidden">
                            <span class="absolute inset-0 shimmer opacity-50"></span>
                            <span class="relative">{{ __('Create Free Account') }}</span>
                            <svg class="relative w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                        <a href="#how-it-works" class="inline-flex items-center justify-center gap-2.5 px-8 py-4 glass-light text-white font-semibold text-base rounded-2xl hover:bg-white/10 hover:-translate-y-0.5 transition-all duration-300 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ __('See How It Works') }}
                        </a>
                    </div>

                    {{-- Slider Dots --}}
                    <div class="flex items-center gap-3 mt-10 justify-center lg:justify-start" id="hero-dots">
                        @foreach([0, 1, 2] as $i)
                            <button type="button" data-index="{{ $i }}" aria-label="Slide {{ $i + 1 }}"
                                    class="hero-dot-btn h-2.5 rounded-full cursor-pointer"></button>
                        @endforeach
                    </div>
                </div>

                {{-- Right: Phone Mockup --}}
                <div class="relative flex justify-center lg:justify-end">
                    <div class="relative animate-float">
                        {{-- Glow ring behind phone --}}
                        <div class="absolute -inset-8 bg-gradient-to-br from-primary/25 via-transparent to-secondary/25 rounded-full blur-3xl pointer-events-none"></div>

                        {{-- Phone Frame --}}
                        <div class="relative w-[280px] sm:w-[300px] lg:w-[320px] phone-mockup">
                            <div class="bg-gradient-to-b from-slate-700 to-slate-900 rounded-[40px] p-3 shadow-elevation-4 border border-white/15">
                                <div class="bg-gradient-to-b from-[#1a1040] to-[#0F172A] rounded-[32px] overflow-hidden">
                                    {{-- Status Bar --}}
                                    <div class="flex items-center justify-between px-6 pt-4 pb-2">
                                        <span class="text-xs text-white/50 font-medium">9:41</span>
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-4 h-2.5 border border-white/30 rounded-sm"><div class="w-2 h-full bg-green-400 rounded-sm"></div></div>
                                        </div>
                                    </div>

                                    {{-- App Content (cycles with slider) --}}
                                    <div class="px-5 pb-8">
                                        {{-- Screen 1: Home --}}
                                        <div class="phone-screen is-active" data-screen="0">
                                            <p class="text-white/40 text-xs mb-1">Welcome back,</p>
                                            <p class="text-white font-bold text-lg mb-4">Adaeze</p>
                                            <div class="glass-light rounded-2xl p-4 mb-4">
                                                <p class="text-white/50 text-xs mb-1">Wallet Balance</p>
                                                <p class="text-white font-display text-2xl font-bold">₦ 245,800<span class="text-white/40 text-sm">.00</span></p>
                                            </div>
                                            <div class="grid grid-cols-4 gap-3 mb-4">
                                                @foreach(['Send', 'Pay', 'Saver', 'More'] as $action)
                                                    <div class="text-center">
                                                        <div class="w-11 h-11 rounded-xl bg-white/5 flex items-center justify-center mx-auto mb-1.5">
                                                            <div class="w-3 h-3 rounded-full bg-primary/60"></div>
                                                        </div>
                                                        <span class="text-white/50 text-[10px]">{{ $action }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="space-y-2">
                                                @foreach(['MTN Airtime', 'PHCN Electricity', 'DSTV Subscription'] as $tx)
                                                    <div class="glass-light rounded-xl px-3 py-2.5 flex items-center justify-between">
                                                        <span class="text-white/70 text-xs">{{ $tx }}</span>
                                                        <span class="text-white/40 text-[10px]">Today</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Screen 2: Ajo --}}
                                        <div class="phone-screen" data-screen="1">
                                            <p class="text-white font-bold text-lg mb-1">My Ajo</p>
                                            <p class="text-white/40 text-xs mb-4">Growing together</p>
                                            <div class="glass-light rounded-2xl p-4 mb-4">
                                                <div class="flex items-center justify-between mb-3">
                                                    <span class="text-white/60 text-xs">Market Warriors</span>
                                                    <span class="text-green-400 text-[10px] font-semibold px-2 py-0.5 bg-green-400/10 rounded-full">Active</span>
                                                </div>
                                                <p class="text-white font-display text-xl font-bold">₦ 120,000</p>
                                                <p class="text-white/40 text-xs mt-1">Your contribution this cycle</p>
                                                <div class="mt-3 h-1.5 bg-white/10 rounded-full overflow-hidden">
                                                    <div class="h-full bg-gradient-to-r from-primary to-accent rounded-full" style="width: 72%"></div>
                                                </div>
                                                <div class="flex justify-between mt-1.5">
                                                    <span class="text-white/30 text-[10px]">Cycle 3 of 5</span>
                                                    <span class="text-primary text-[10px] font-semibold">72%</span>
                                                </div>
                                            </div>
                                            <div class="glass-light rounded-2xl p-4">
                                                <div class="flex items-center justify-between mb-3">
                                                    <span class="text-white/60 text-xs">Next Payout</span>
                                                    <span class="text-white/30 text-[10px]">Aug 15</span>
                                                </div>
                                                <p class="text-white font-display text-xl font-bold">₦ 600,000</p>
                                                <p class="text-white/40 text-xs mt-1">Estimated payout amount</p>
                                            </div>
                                        </div>

                                        {{-- Screen 3: Bills --}}
                                        <div class="phone-screen" data-screen="2">
                                            <p class="text-white font-bold text-lg mb-4">Pay Bills</p>
                                            <div class="grid grid-cols-2 gap-3 mb-4">
                                                @foreach(['Airtime' => 'phone', 'Electricity' => 'bolt', 'Cable TV' => 'tv', 'Data' => 'wifi'] as $name => $icon)
                                                    <div class="glass-light rounded-2xl p-4 text-center">
                                                        <div class="w-10 h-10 rounded-xl bg-primary/20 flex items-center justify-center mx-auto mb-2">
                                                            <div class="w-4 h-4 rounded bg-primary/60"></div>
                                                        </div>
                                                        <span class="text-white/70 text-xs font-medium">{{ $name }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="glass-light rounded-2xl p-4">
                                                <p class="text-white/60 text-xs mb-3">Quick Top Up</p>
                                                <div class="flex gap-2">
                                                    @foreach(['₦100', '₦200', '₦500'] as $amt)
                                                        <div class="flex-1 py-2 text-center text-xs font-semibold text-white/60 bg-white/5 rounded-xl cursor-pointer hover:bg-primary/20 hover:text-primary transition-all">{{ $amt }}</div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Bottom Nav --}}
                                    <div class="px-5 pb-6">
                                        <div class="glass-light rounded-2xl px-4 py-3 flex items-center justify-around">
                                            @foreach(['Home', 'Ajo', 'Pay', 'More'] as $i => $nav)
                                                <div class="text-center cursor-pointer">
                                                    <div class="w-5 h-5 rounded-md mx-auto mb-1 {{ $i === 0 ? 'bg-primary' : 'bg-white/20' }}"></div>
                                                    <span class="text-[9px] {{ $i === 0 ? 'text-primary font-semibold' : 'text-white/30' }}">{{ $nav }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Floating Elements --}}
                        <div class="absolute -top-4 -right-4 glass-strong rounded-2xl px-4 py-3 animate-float-slow hidden sm:block">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-green-500/20 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <div>
                                    <p class="text-white text-xs font-semibold">Transfer Successful</p>
                                    <p class="text-white/40 text-[10px]">₦15,000 sent</p>
                                </div>
                            </div>
                        </div>

                        <div class="absolute -bottom-2 -left-6 glass-strong rounded-2xl px-4 py-3 animate-float-slow hidden sm:block" style="animation-delay: -3s">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-primary/20 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <p class="text-white text-xs font-semibold">Ajo Payout</p>
                                    <p class="text-white/40 text-[10px]">₦600,000 received</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trust indicators --}}
            <div class="mt-14 flex flex-col sm:flex-row items-center justify-center gap-5 lg:gap-10">
                @foreach([
                    ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'label' => 'CBN Partner-Bank Secured'],
                    ['icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'label' => 'NDIC-Insured Deposits'],
                    ['icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'label' => '256-bit Encryption'],
                ] as $trust)
                    <div class="flex items-center gap-2.5 text-white/50 animate-fade-in-up" style="animation-delay: 0.35s">
                        <svg class="w-4 h-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $trust['icon'] }}" /></svg>
                        <span class="text-xs font-medium tracking-wide">{{ $trust['label'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Scroll Indicator --}}
            <div class="hidden lg:flex justify-center mt-10">
                <div class="scroll-hint text-white/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ FEATURES ═══════════════ --}}
    <section id="features" class="py-20 lg:py-28 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Section Header --}}
            <div class="text-center max-w-2xl mx-auto mb-16 section-fade">
                <span class="inline-block text-xs font-bold tracking-widest uppercase text-primary mb-4">Everything You Need</span>
                <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">
                    One app. <span class="text-gradient">Infinite possibilities.</span>
                </h2>
                <p class="mt-5 text-white/50 text-lg leading-relaxed">From daily payments to long-term savings, {{ $siteSettings->site_name ?? 'PayEase' }} handles it all with speed and security.</p>
            </div>

            {{-- Feature Grid --}}
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach([
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />',
                        'title' => 'Send Money',
                        'desc' => 'Instant transfers to any bank account or ' . ($siteSettings->site_name ?? 'PayEase') . ' wallet. Fast, secure, and reliable.',
                        'delay' => '0s',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />',
                        'title' => 'Pay Bills',
                        'desc' => 'Airtime, data, electricity, cable TV — all your bills in one place with instant confirmation.',
                        'delay' => '0.1s',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
                        'title' => 'Ajo Savings',
                        'desc' => 'Join or create savings groups. Multiply your savings with the power of collective contribution.',
                        'delay' => '0.2s',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                        'title' => 'Add Money',
                        'desc' => 'Fund your wallet via bank transfer, card payment, or through any of our growing network of trusted local agents.',
                        'delay' => '0.3s',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
                        'title' => 'Bank-Grade Security',
                        'desc' => 'PIN & OTP verification, 256-bit encryption, and tiered KYC protection keep your money safe.',
                        'delay' => '0.4s',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />',
                        'title' => 'Micro-Loans',
                        'desc' => 'Access small, short-term loans based on your transaction history. Currently in early rollout.',
                        'delay' => '0.5s',
                    ],
                ] as $feature)
                    <div class="glass-light rounded-card p-6 lg:p-8 card-hover cursor-default section-fade"
                         style="transition-delay: {{ $feature['delay'] }}"
                        >
                        <div class="w-14 h-14 feature-icon-ring rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">{!! $feature['icon'] !!}</svg>
                        </div>
                        <h3 class="font-display text-xl font-bold text-white mb-2">{{ $feature['title'] }}</h3>
                        <p class="text-white/50 text-sm leading-relaxed">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ HOW IT WORKS ═══════════════ --}}
    <section id="how-it-works" class="py-20 lg:py-28 relative">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-secondary/5 to-transparent"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16 section-fade">
                <span class="inline-block text-xs font-bold tracking-widest uppercase text-secondary mb-4">Simple & Fast</span>
                <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">
                    Start in <span class="text-gradient-purple">three easy steps</span>
                </h2>
                <p class="mt-5 text-white/50 text-lg">No lengthy forms. No branch visits. Get started from your phone in under 2 minutes.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 relative">
                {{-- Connecting Line --}}
                <div class="hidden md:block absolute top-24 left-[20%] right-[20%] h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>

                @foreach([
                    [
                        'num' => '01',
                        'title' => 'Sign Up',
                        'desc' => 'Enter your phone number, verify with OTP, and set your PIN. Done.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />',
                    ],
                    [
                        'num' => '02',
                        'title' => 'Fund Wallet',
                        'desc' => 'Add money via bank transfer, debit card, or visit any ' . ($siteSettings->site_name ?? 'PayEase') . ' agent near you.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />',
                    ],
                    [
                        'num' => '03',
                        'title' => 'Transact',
                        'desc' => 'Send money, pay bills, join Ajo groups — enjoy seamless financial freedom.',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />',
                    ],
                ] as $step)
                    <div class="text-center relative section-fade">
                        <div class="w-16 h-16 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-secondary/20 to-primary/20 flex items-center justify-center border border-white/10 relative z-10">
                            <span class="font-display text-2xl font-extrabold text-gradient">{{ $step['num'] }}</span>
                        </div>
                        <h3 class="font-display text-xl font-bold text-white mb-3">{{ $step['title'] }}</h3>
                        <p class="text-white/50 text-sm leading-relaxed max-w-xs mx-auto">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ AJO OWNER CTA ═══════════════ --}}
    <section class="py-20 lg:py-28 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass rounded-sheet p-8 sm:p-12 lg:p-16 relative overflow-hidden section-fade">
                <div class="absolute top-0 left-0 w-64 h-64 bg-primary/10 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-0 right-0 w-48 h-48 bg-secondary/10 rounded-full blur-[80px]"></div>

                <div class="relative grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <span class="inline-block text-xs font-bold tracking-widest uppercase text-primary mb-4">For Community Leaders</span>
                        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-white leading-tight mb-6">
                            Run your own <span class="text-gradient">digital Ajo groups</span>
                        </h2>
                        <p class="text-white/50 text-lg leading-relaxed mb-8">
                            The same trusted savings model Nigerians have used for generations — now digital, secure, and scalable. Create groups, recruit agents, and earn management fees.
                        </p>

                        <div class="space-y-4 mb-8">
                            @foreach([
                                ['title' => 'Create & manage multiple groups', 'desc' => 'Custom cycle rules, contribution amounts, and payout schedules.'],
                                ['title' => 'Assign agents for collections', 'desc' => 'Agents collect daily contributions from members in your groups.'],
                                ['title' => 'Earn management fees', 'desc' => 'Revenue from every successful group cycle. Build a sustainable business.'],
                            ] as $item)
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 shrink-0 rounded-full bg-primary/20 flex items-center justify-center mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-white text-sm font-semibold">{{ $item['title'] }}</p>
                                        <p class="text-white/40 text-sm">{{ $item['desc'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <a href="{{ route('ajo-owner.signup') }}"
                           class="group inline-flex items-center justify-center gap-2.5 px-8 py-4 bg-gradient-to-r from-primary to-primary-dark text-white font-bold text-base rounded-2xl hover:shadow-glow-primary transition-all duration-300 active:scale-95 cursor-pointer">
                            Become an Ajo Owner
                            <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                    </div>

                    <div class="flex justify-center">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="glass-light rounded-card p-5 text-center">
                                <div class="w-12 h-12 bg-primary/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <p class="text-white font-display text-lg font-bold">Groups</p>
                                <p class="text-white/40 text-xs mt-1">Create & manage</p>
                            </div>
                            <div class="glass-light rounded-card p-5 text-center">
                                <div class="w-12 h-12 bg-secondary/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                </div>
                                <p class="text-white font-display text-lg font-bold">Agents</p>
                                <p class="text-white/40 text-xs mt-1">Recruit & assign</p>
                            </div>
                            <div class="glass-light rounded-card p-5 text-center">
                                <div class="w-12 h-12 bg-green-500/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <p class="text-white font-display text-lg font-bold">Earnings</p>
                                <p class="text-white/40 text-xs mt-1">Management fees</p>
                            </div>
                            <div class="glass-light rounded-card p-5 text-center">
                                <div class="w-12 h-12 bg-accent/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                </div>
                                <p class="text-white font-display text-lg font-bold">Secure</p>
                                <p class="text-white/40 text-xs mt-1">PIN & encrypted</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ SECURITY & TRUST ═══════════════ --}}
    <section id="security" class="py-20 lg:py-28 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass rounded-sheet p-8 sm:p-12 lg:p-16 relative overflow-hidden section-fade">
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary/10 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-secondary/10 rounded-full blur-[80px]"></div>

                <div class="relative grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <span class="inline-block text-xs font-bold tracking-widest uppercase text-primary mb-4">Enterprise-Grade Security</span>
                        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-white leading-tight mb-6">
                            Your money is <span class="text-gradient">always safe</span> with us.
                        </h2>
                        <p class="text-white/50 text-lg leading-relaxed mb-8">We partner with the best security providers in the industry to ensure your funds and data are always protected.</p>

                        <div class="space-y-4">
                            @foreach([
                                ['title' => 'Partner-Bank Secured', 'desc' => 'Funds held with our CBN-licensed banking partner'],
                                ['title' => 'NDIC-Insured Funds', 'desc' => 'Customer deposits are insured by the Nigeria Deposit Insurance Corporation'],
                                ['title' => '256-bit Encryption', 'desc' => 'Military-grade data protection at rest and in transit'],
                            ] as $item)
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 shrink-0 rounded-xl bg-green-500/10 flex items-center justify-center mt-0.5">
                                        <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-white font-semibold text-sm">{{ $item['title'] }}</p>
                                        <p class="text-white/40 text-sm">{{ $item['desc'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <div class="relative">
                            <div class="w-56 h-56 lg:w-72 lg:h-72 rounded-full bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center border border-white/10">
                                <div class="w-40 h-40 lg:w-52 lg:h-52 rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 flex items-center justify-center border border-white/5">
                                    <div class="text-center">
                                        <svg class="w-12 h-12 text-primary mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        <p class="font-display text-sm font-bold text-white">Always-On Security</p>
                                        <p class="text-white/40 text-xs mt-0.5">256-bit encrypted</p>
                                    </div>
                                </div>
                            </div>
                            {{-- Orbiting dots --}}
                            <div class="absolute inset-0 animate-spin" style="animation-duration: 20s">
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-3 h-3 bg-primary rounded-full"></div>
                            </div>
                            <div class="absolute inset-0 animate-spin" style="animation-duration: 15s; animation-direction: reverse">
                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-2 h-2 bg-secondary rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ TESTIMONIALS ═══════════════ --}}
    <section id="testimonials" class="py-20 lg:py-28 relative">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-primary/3 to-transparent"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16 section-fade">
                <span class="inline-block text-xs font-bold tracking-widest uppercase text-primary mb-4">Early User Feedback</span>
                <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">
                    What our users <span class="text-gradient">say about us</span>
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach([
                    [
                        'name' => 'Chidinma O.',
                        'role' => 'Small Business Owner',
                        'quote' => ($siteSettings->site_name ?? 'PayEase') . ' changed how I receive payments from customers. The agent network means I can cash out anywhere. Game changer!',
                        'stars' => 5,
                    ],
                    [
                        'name' => 'Tunde A.',
                        'role' => 'Ajo Group Member',
                        'quote' => 'The Ajo feature helped me save over ₦500,000 in 6 months. I never thought saving could be this easy and rewarding.',
                        'stars' => 5,
                    ],
                    [
                        'name' => 'Amina B.',
                        'role' => 'University Student',
                        'quote' => 'I use ' . ($siteSettings->site_name ?? 'PayEase') . ' for everything — airtime, data, even splitting bills with roommates. The UI is beautiful and it just works.',
                        'stars' => 5,
                    ],
                ] as $review)
                    <div class="glass-light rounded-card p-6 lg:p-8 card-hover section-fade">
                        {{-- Stars --}}
                        <div class="flex gap-1 mb-4">
                            @for($s = 0; $s < $review['stars']; $s++)
                                <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                            @endfor
                        </div>
                        <p class="text-white/70 text-sm leading-relaxed mb-6">"{{ $review['quote'] }}"</p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary/30 to-secondary/30 flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ substr($review['name'], 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="text-white text-sm font-semibold">{{ $review['name'] }}</p>
                                <p class="text-white/40 text-xs">{{ $review['role'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ CTA SECTION ═══════════════ --}}
    <section class="py-20 lg:py-28 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="cta-gradient rounded-sheet p-8 sm:p-12 lg:p-16 text-center relative overflow-hidden section-fade">
                <div class="absolute inset-0 hero-dot opacity-20"></div>
                <div class="absolute top-0 left-1/4 w-48 h-48 bg-white/10 rounded-full blur-[80px]"></div>
                <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-white/5 rounded-full blur-[100px]"></div>

                <div class="relative">
                    <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight mb-6">
                        Ready to take control<br class="hidden sm:block"> of your finances?
                    </h2>
                    <p class="text-white/80 text-lg max-w-xl mx-auto mb-10">Built for every Nigerian community, one agent at a time. Fast, secure, and always available.</p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('register') }}" class="group inline-flex items-center justify-center gap-2.5 px-8 py-4 bg-white text-[#0F172A] font-bold text-base rounded-2xl hover:bg-white/90 hover:shadow-elevation-4 transition-all duration-300 active:scale-95 cursor-pointer">
                            {{ __('Create Free Account') }}
                            <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2.5 px-8 py-4 glass-light text-white font-semibold text-base rounded-2xl hover:bg-white/10 transition-all duration-300 cursor-pointer">
                            {{ __('Log In to Dashboard') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ FOOTER ═══════════════ --}}
    <footer class="border-t border-white/5 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8 mb-12">
                {{-- Brand --}}
                <div class="col-span-2 md:col-span-4 lg:col-span-1 mb-4 lg:mb-0">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 mb-4">
                        @if($siteSettings->logo_path)
                            <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name ?? 'PayEase' }}" class="h-9 w-auto object-contain bg-white rounded-lg px-1.5 py-0.5">
                        @else
                            <div class="w-9 h-9 bg-gradient-to-br from-primary to-accent rounded-xl flex items-center justify-center">
                                <span class="text-white text-lg font-bold">₦</span>
                            </div>
                        @endif
                        <span class="text-xl font-bold font-display text-white">{{ $siteSettings->site_name ?? 'PayEase' }}</span>
                    </a>
                    <p class="text-white/40 text-sm leading-relaxed max-w-xs">{{ $siteSettings->site_tagline ?: __('Fast, secure digital payments built for Nigerian communities.') }}</p>
                </div>

                {{-- Links --}}
                @foreach([
                    'Product' => ['Features', 'Pricing', 'Security', 'API Docs'],
                    'Company' => ['About', 'Blog', 'Careers', 'Press'],
                    'Support' => ['Help Center', 'Contact', 'Status', 'Privacy'],
                ] as $heading => $links)
                    <div>
                        <h4 class="text-white font-semibold text-sm mb-4">{{ $heading }}</h4>
                        <ul class="space-y-2.5">
                            @foreach($links as $link)
                                <li><a href="#" class="text-white/40 hover:text-white/70 text-sm transition-colors duration-200 cursor-pointer">{{ $link }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-white/5 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-white/30 text-xs">&copy; {{ date('Y') }} {{ $siteSettings->site_name ?? 'PayEase' }}. {{ __('All rights reserved.') }}</p>
                <div class="flex items-center gap-4">
                    @php $socials = $siteSettings->social_links ?? []; @endphp
                    @forelse($socials as $social)
                        @if(($social['url'] ?? '') !== '')
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-xl bg-white/5 flex items-center justify-center text-white/30 hover:text-white/70 hover:bg-white/10 transition-all duration-200 cursor-pointer">
                                @if(($social['platform'] ?? '') === 'twitter')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                @elseif(($social['platform'] ?? '') === 'linkedin')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                @elseif(($social['platform'] ?? '') === 'instagram')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                @elseif(($social['platform'] ?? '') === 'facebook')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                @elseif(($social['platform'] ?? '') === 'youtube')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                @elseif(($social['platform'] ?? '') === 'tiktok')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                @elseif(($social['platform'] ?? '') === 'telegram')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/></svg>
                                @endif
                            </a>
                        @endif
                    @empty
                        <a href="#" class="w-9 h-9 rounded-xl bg-white/5 flex items-center justify-center text-white/30 hover:text-white/70 hover:bg-white/10 transition-all duration-200 cursor-pointer">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/></svg>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-xl bg-white/5 flex items-center justify-center text-white/30 hover:text-white/70 hover:bg-white/10 transition-all duration-200 cursor-pointer">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/></svg>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-xl bg-white/5 flex items-center justify-center text-white/30 hover:text-white/70 hover:bg-white/10 transition-all duration-200 cursor-pointer">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/></svg>
                        </a>
                    @endforelse
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

            document.querySelectorAll('.section-fade').forEach(function(el) {
                observer.observe(el);
            });

            initHeroSlider();
            initLandingNav();
        });

        function initLandingNav() {
            var nav = document.getElementById('landing-nav');
            var bar = document.getElementById('nav-bar');
            var btn = document.getElementById('mobile-menu-btn');
            var menu = document.getElementById('mobile-menu');
            var iconOpen = document.getElementById('menu-icon-open');
            var iconClose = document.getElementById('menu-icon-close');
            if (!nav) return;

            var menuOpen = false;

            window.addEventListener('scroll', function() {
                var scrolled = window.scrollY > 40;
                nav.classList.toggle('py-2', scrolled);
                nav.classList.toggle('py-4', !scrolled);
                if (bar) bar.classList.toggle('shadow-elevation-4', scrolled);
            }, { passive: true });

            if (btn && menu) {
                btn.addEventListener('click', function() {
                    menuOpen = !menuOpen;
                    if (menuOpen) {
                        menu.classList.remove('hidden');
                        requestAnimationFrame(function() { menu.style.opacity = '1'; menu.style.transform = 'translateY(0)'; });
                    } else {
                        menu.style.opacity = '0';
                        menu.style.transform = 'translateY(-8px)';
                        setTimeout(function() { menu.classList.add('hidden'); }, 200);
                    }
                    if (iconOpen) iconOpen.classList.toggle('hidden', menuOpen);
                    if (iconClose) iconClose.classList.toggle('hidden', !menuOpen);
                });

                menu.querySelectorAll('.mobile-menu-link').forEach(function(link) {
                    link.addEventListener('click', function() {
                        menuOpen = false;
                        menu.style.opacity = '0';
                        menu.style.transform = 'translateY(-8px)';
                        setTimeout(function() { menu.classList.add('hidden'); }, 200);
                        if (iconOpen) iconOpen.classList.remove('hidden');
                        if (iconClose) iconClose.classList.add('hidden');
                    });
                });
            }
        }

        function initHeroSlider() {
            var slides = document.querySelectorAll('.hero-slide');
            var screens = document.querySelectorAll('.phone-screen');
            var dots = document.querySelectorAll('.hero-dot-btn');
            if (!slides.length) return;

            var current = 0, timer = null;

            function show(i) {
                current = (i + slides.length) % slides.length;
                slides.forEach(function (s, k) { s.classList.toggle('is-active', k === current); });
                screens.forEach(function (s, k) { s.classList.toggle('is-active', k === current); });
                dots.forEach(function (d, k) {
                    d.classList.toggle('is-active', k === current);
                    d.setAttribute('aria-current', k === current ? 'true' : 'false');
                });
            }
            function next() { show(current + 1); }
            function start() { stop(); timer = setInterval(next, 4000); }
            function stop() { if (timer) clearInterval(timer); }

            dots.forEach(function (d) {
                d.addEventListener('click', function () { show(Number(d.dataset.index)); start(); });
            });

            var section = document.getElementById('hero');
            if (section) {
                section.addEventListener('mouseenter', stop);
                section.addEventListener('mouseleave', start);
            }

            show(0);
            start();
        }

    </script>
</body>
</html>