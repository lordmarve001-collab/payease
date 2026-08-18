<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ 
        darkMode: localStorage.getItem('darkMode') === 'true',
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
      }"
      x-init="if(darkMode) document.documentElement.classList.add('dark')">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>{{ $siteSettings->site_name ?? 'PayEase' }} — Ajo Agent</title>
    @if($siteSettings->favicon_path)
        <link rel="icon" type="image/png" href="{{ $siteSettings->faviconUrl() }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --color-primary: {{ $siteSettings->primary_color ?? '#D97706' }};
            --color-secondary: {{ $siteSettings->secondary_color ?? '#7C3AED' }};
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
<body class="bg-background text-text-primary font-sans antialiased min-h-screen pb-20 md:pb-0">

    <!-- Top Bar -->
    <header class="fixed top-0 left-0 right-0 h-16 bg-surface border-b border-border z-30 flex items-center justify-between px-4 md:ml-[72px] lg:ml-[240px] transition-all duration-300">
        <div class="flex items-center gap-2 md:hidden">
                @if($siteSettings->logo_path)
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name ?? 'PayEase' }}" class="h-7 object-contain">
            @else
                <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center text-white shrink-0">
                    <x-lucide-briefcase class="w-4 h-4" />
                </div>
                <span class="font-bold text-lg text-emerald-600">{{ $siteSettings->site_name ?? 'PayEase' }}</span>
            @endif
            <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-1.5 py-0.5 rounded tracking-wide uppercase">{{ __('Ajo Agent') }}</span>
        </div>
        <div class="hidden md:flex items-center gap-2">
            <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-1.5 py-0.5 rounded tracking-wide uppercase">{{ __('Ajo Agent Portal') }}</span>
        </div>

        <div class="flex items-center gap-4 ml-auto">
            <button @click="toggleDarkMode()" class="p-2 text-text-secondary hover:text-text-primary transition-colors rounded-full hover:bg-background">
                <x-lucide-moon class="w-5 h-5" x-show="!darkMode" />
                <x-lucide-sun class="w-5 h-5" x-show="darkMode" x-cloak />
            </button>
            <button class="relative p-2 text-text-secondary opacity-60 cursor-not-allowed rounded-full hover:bg-background" disabled>
                <x-lucide-bell class="w-5 h-5" />
            </button>
            <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                {{ strtoupper(substr(Auth::user()->full_name ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', Auth::user()->full_name ?? ' ')[1] ?? '', 0, 1)) }}
            </div>
        </div>
    </header>

    <!-- Sidebar (Desktop) -->
    <x-sidebar>
        <x-sidebar-item href="{{ url('/ajo-agent/dashboard') }}" icon="layout-dashboard" :label="__('Dashboard')" :active="request()->is('ajo-agent/dashboard')" wire:navigate />
        <x-sidebar-item href="{{ url('/ajo-agent/groups') }}" icon="users" :label="__('My Groups')" :active="request()->is('ajo-agent/groups*')" wire:navigate />
        <x-sidebar-item href="{{ url('/ajo-agent/collect') }}" icon="circle-dollar-sign" :label="__('Collect')" :active="request()->is('ajo-agent/collect*')" wire:navigate />
        <x-sidebar-item href="{{ url('/ajo-agent/create-member') }}" icon="user-plus" :label="__('Add Member')" :active="request()->is('ajo-agent/create-member')" wire:navigate />
        <x-sidebar-item href="{{ url('/ajo-agent/transactions') }}" icon="history" :label="__('Transactions')" :active="request()->is('ajo-agent/transactions')" wire:navigate />
        <x-sidebar-item href="{{ url('/ajo-agent/send-money') }}" icon="send" :label="__('Send Money')" :active="request()->is('ajo-agent/send-money')" wire:navigate />
        <x-sidebar-item href="{{ url('/ajo-agent/pay-bills') }}" icon="receipt" :label="__('Pay Bills')" :active="request()->is('ajo-agent/pay-bills')" wire:navigate />
        <x-sidebar-item href="{{ url('/ajo-agent/profile') }}" icon="user" :label="__('Profile')" :active="request()->is('ajo-agent/profile')" wire:navigate />
    </x-sidebar>

    <!-- Bottom Nav (Mobile) -->
    <x-bottom-nav>
        <x-bottom-nav-item href="{{ url('/ajo-agent/dashboard') }}" icon="layout-dashboard" :label="__('Home')" :active="request()->is('ajo-agent/dashboard')" wire:navigate />
        <x-bottom-nav-item href="{{ url('/ajo-agent/groups') }}" icon="users" :label="__('Groups')" :active="request()->is('ajo-agent/groups*')" wire:navigate />
        <a href="{{ url('/ajo-agent/collect') }}" wire:navigate class="relative -top-5 flex flex-col items-center justify-center gap-1 text-text-secondary hover:text-emerald-600 transition-colors">
            <div class="w-14 h-14 rounded-full bg-emerald-600 text-white flex items-center justify-center shadow-elevation-2 active:scale-95 transition-transform border-4 border-surface {{ request()->is('ajo-agent/collect*') ? 'ring-2 ring-emerald-600 ring-offset-2 ring-offset-background' : '' }}">
                <x-lucide-circle-dollar-sign class="w-6 h-6" />
            </div>
            <span class="text-[10px] font-medium mt-1">{{ __('Collect') }}</span>
        </a>
        <x-bottom-nav-item href="{{ url('/ajo-agent/transactions') }}" icon="history" :label="__('History')" :active="request()->is('ajo-agent/transactions')" wire:navigate />
        <x-bottom-nav-item href="{{ url('/ajo-agent/profile') }}" icon="user" :label="__('Profile')" :active="request()->is('ajo-agent/profile')" wire:navigate />
    </x-bottom-nav>

    <!-- Main Content -->
    <main class="pt-16 md:ml-[72px] lg:ml-[240px] transition-all duration-300 min-h-[calc(100vh-4rem)] relative"
          x-data="{ show: false }"
          x-init="setTimeout(() => show = true, 50)"
          :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
          class="opacity-0 translate-y-4 transition-all duration-250 ease-material">
        {{ $slot }}
    </main>

    <!-- Global Toasts -->
    <x-toast type="success" />
    <x-toast type="error" />
    <x-toast type="info" />
    {!! $siteSettings->custom_footer_html ?? '' !!}
</body>
</html>
