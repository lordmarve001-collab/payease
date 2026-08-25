<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ 
        lightMode: localStorage.getItem('lightMode') === 'true',
        toggleTheme() {
            this.lightMode = !this.lightMode;
            localStorage.setItem('lightMode', this.lightMode);
            document.documentElement.classList.toggle('light', this.lightMode);
        }
      }"
      x-init="if(lightMode) document.documentElement.classList.add('light')">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>{{ $siteSettings->site_name ?? 'PayEase' }} — Ajo Owner</title>
    @if($siteSettings->favicon_path)
        <link rel="icon" type="image/png" href="{{ $siteSettings->faviconUrl() }}">
    @endif
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
        body, .bg-surface, .bg-background, .text-text-primary, .text-text-secondary, .border-border {
            transition-property: background-color, border-color, color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }
    </style>
    {!! $siteSettings->custom_head_html ?? '' !!}
</head>
<body class="app-bg text-text-primary font-sans antialiased min-h-screen pb-20 md:pb-0">

    <!-- Top Bar -->
    <header class="fixed top-0 left-0 right-0 h-16 bg-surface/70 backdrop-blur-xl border-b border-border z-30 flex items-center justify-between px-4 md:ml-[72px] lg:ml-[240px] transition-all duration-300">
        <div class="flex items-center gap-2 md:hidden">
                @if($siteSettings->logo_path)
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name ?? 'PayEase' }}" class="h-7 object-contain">
            @else
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-secondary to-accent flex items-center justify-center text-white shadow-glow-secondary shrink-0">
                    <x-lucide-users class="w-4 h-4" />
                </div>
                <span class="font-display font-bold text-lg text-gradient-violet">{{ $siteSettings->site_name ?? 'PayEase' }}</span>
            @endif
            <span class="bg-secondary/20 text-accent text-[10px] font-bold px-1.5 py-0.5 rounded tracking-wide uppercase">{{ __('Ajo Owner') }}</span>
        </div>
        <div class="hidden md:flex items-center gap-2">
            <span class="bg-secondary/20 text-accent text-[10px] font-bold px-1.5 py-0.5 rounded tracking-wide uppercase">{{ __('Ajo Owner Portal') }}</span>
        </div>

        <div class="flex items-center gap-4 ml-auto">
            <button @click="toggleTheme()" class="p-2 text-text-secondary hover:text-text-primary transition-colors rounded-full hover:bg-surface-2 cursor-pointer">
                <x-lucide-sun class="w-5 h-5" x-show="!lightMode" />
                <x-lucide-moon class="w-5 h-5" x-show="lightMode" x-cloak />
            </button>
            <button class="relative p-2 text-text-secondary opacity-60 cursor-not-allowed rounded-full hover:bg-surface-2" disabled>
                <x-lucide-bell class="w-5 h-5" />
                <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-orange-500 rounded-full border-2 border-surface"></span>
            </button>
            <a href="{{ route('ajo-owner.profile') }}" wire:navigate class="w-8 h-8 rounded-full bg-gradient-to-br from-secondary to-accent text-white flex items-center justify-center font-bold text-sm shadow-glow-secondary cursor-pointer">
                {{ strtoupper(substr(Auth::user()->full_name ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', Auth::user()->full_name ?? ' ')[1] ?? '', 0, 1)) }}
            </a>
        </div>
    </header>

    <!-- Sidebar (Desktop) -->
    <x-sidebar>
        <x-sidebar-item href="{{ route('ajo-owner.dashboard') }}" icon="layout-dashboard" :label="__('Overview')" :active="request()->is('ajo-owner/dashboard')" wire:navigate />
        <x-sidebar-item href="{{ route('ajo-owner.groups') }}" icon="users-2" :label="__('My Groups')" :active="request()->is('ajo-owner/groups*')" wire:navigate />
        <x-sidebar-item href="{{ route('ajo-owner.agents') }}" icon="briefcase" :label="__('My Agents')" :active="request()->is('ajo-owner/agents')" wire:navigate />
        <x-sidebar-item href="{{ route('ajo-owner.payouts') }}" icon="banknote" :label="__('Payouts')" :active="request()->is('ajo-owner/payouts')" wire:navigate />
        <x-sidebar-item href="{{ route('ajo-owner.add-fund') }}" icon="plus-circle" :label="__('Add Fund')" :active="request()->is('ajo-owner/add-fund')" wire:navigate />
        <x-sidebar-item href="{{ route('ajo-owner.send-fund') }}" icon="send" :label="__('Send Fund')" :active="request()->is('ajo-owner/send-fund')" wire:navigate />
        <x-sidebar-item href="{{ route('ajo-owner.pay-bills') }}" icon="receipt" :label="__('Pay Bills')" :active="request()->is('ajo-owner/pay-bills')" wire:navigate />
        <x-sidebar-item href="{{ route('ajo-owner.profile') }}" icon="user" :label="__('Profile')" :active="request()->is('ajo-owner/profile')" wire:navigate />

        <div class="mt-auto pt-2 border-t border-border">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-red-300 hover:bg-red-500/10 hover:text-red-200 transition-colors text-sm font-medium cursor-pointer">
                    <x-lucide-log-out class="w-5 h-5 shrink-0" />
                    <span class="sidebar-label">{{ __('Logout') }}</span>
                </button>
            </form>
        </div>
    </x-sidebar>

    <!-- Bottom Nav (Mobile) -->
    <x-bottom-nav>
        <x-bottom-nav-item href="{{ route('ajo-owner.dashboard') }}" icon="layout-dashboard" :label="__('Overview')" :active="request()->is('ajo-owner/dashboard')" wire:navigate />
        <x-bottom-nav-item href="{{ route('ajo-owner.groups') }}" icon="users-2" :label="__('Groups')" :active="request()->is('ajo-owner/groups*')" wire:navigate />
        <a href="{{ route('ajo-owner.groups.create') }}" wire:navigate class="relative -top-5 flex flex-col items-center justify-center gap-1 text-text-secondary hover:text-accent transition-colors">
            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-secondary to-accent text-white flex items-center justify-center shadow-glow-secondary active:scale-95 transition-transform border-4 border-surface {{ request()->is('ajo-owner/groups/create') ? 'ring-2 ring-secondary ring-offset-2 ring-offset-background' : '' }}">
                <x-lucide-plus class="w-6 h-6" />
            </div>
            <span class="text-[10px] font-medium mt-1">{{ __('New Ajo') }}</span>
        </a>
        <x-bottom-nav-item href="{{ route('ajo-owner.send-fund') }}" icon="send" :label="__('Send')" :active="request()->is('ajo-owner/send-fund')" wire:navigate />
        <x-bottom-nav-item href="{{ route('ajo-owner.pay-bills') }}" icon="receipt" :label="__('Bills')" :active="request()->is('ajo-owner/pay-bills')" wire:navigate />
    </x-bottom-nav>

    <!-- Main Content -->
    <main class="pt-16 md:ml-[72px] lg:ml-[240px] transition-all duration-300 min-h-[calc(100vh-4rem)] relative"
          x-data="{ show: false }"
          x-init="setTimeout(() => show = true, 50)"
          :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
          class="opacity-0 translate-y-4 transition-all duration-500 ease-spring">
        {{ $slot }}
    </main>

    <!-- Global Toasts -->
    <x-toast type="success" />
    <x-toast type="error" />
    <x-toast type="info" />
    {!! $siteSettings->custom_footer_html ?? '' !!}
</body>
</html>
