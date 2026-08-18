<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>{{ $siteSettings->site_name ?? 'PayEase' }}</title>
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
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white shrink-0">
                    <x-lucide-wallet class="w-5 h-5" />
                </div>
                <span class="font-bold text-lg text-primary">{{ $siteSettings->site_name ?? 'PayEase' }}</span>
            @endif
        </div>
        <div class="hidden md:block"></div>
        
        <div class="flex items-center gap-4 ml-auto">
            <a href="{{ route('customer.notifications') }}" wire:navigate class="relative p-2 text-text-secondary hover:text-text-primary transition-colors active:scale-95">
                <x-lucide-bell class="w-6 h-6" />
                <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-danger rounded-full border-2 border-surface"></span>
            </a>
            <div class="w-8 h-8 rounded-full bg-secondary text-white flex items-center justify-center font-bold text-sm">
                {{ strtoupper(substr(Auth::user()->full_name ?? 'U', 0, 1)) }}{{ strtoupper(substr(explode(' ', Auth::user()->full_name ?? ' ')[1] ?? '', 0, 1)) }}
            </div>
        </div>
    </header>

    <!-- Sidebar (Desktop) -->
    <x-sidebar>
        <x-sidebar-item :href="route('customer.dashboard')" icon="home" :label="__('Home')" :active="request()->is('dashboard')" wire:navigate />
        <x-sidebar-item :href="route('customer.send-money')" icon="send" :label="__('Send Money')" :active="request()->is('send-money')" wire:navigate />
        <x-sidebar-item :href="route('customer.add-money')" icon="plus-circle" :label="__('Add Money')" :active="request()->is('add-money')" wire:navigate />
        <x-sidebar-item :href="route('customer.buy-airtime')" icon="smartphone" :label="__('Buy Airtime')" :active="request()->is('buy-airtime')" wire:navigate />
        <x-sidebar-item :href="route('customer.pay-bills')" icon="receipt" :label="__('Pay Bills')" :active="request()->is('pay-bills')" wire:navigate />
        <x-sidebar-item :href="route('customer.my-ajo')" icon="users" :label="__('My Ajo')" :active="request()->is('my-ajo*')" wire:navigate />
        <x-sidebar-item :href="route('customer.history')" icon="history" :label="__('History')" :active="request()->is('history')" wire:navigate />
        <x-sidebar-item :href="route('customer.profile')" icon="user" :label="__('Profile')" :active="request()->is('profile')" wire:navigate />
    </x-sidebar>

    <!-- Bottom Nav (Mobile) -->
    <x-bottom-nav>
        <x-bottom-nav-item :href="route('customer.dashboard')" icon="home" :label="__('Home')" :active="request()->is('dashboard')" wire:navigate />
        <x-bottom-nav-item :href="route('customer.add-money')" icon="plus-circle" :label="__('Add Money')" :active="request()->is('add-money')" wire:navigate />
        <x-bottom-nav-item :href="route('customer.buy-airtime')" icon="smartphone" :label="__('Buy Airtime')" :active="request()->is('buy-airtime')" wire:navigate />
        <x-bottom-nav-item :href="route('customer.pay-bills')" icon="receipt" :label="__('Pay Bills')" :active="request()->is('pay-bills')" wire:navigate />
        <x-bottom-nav-item :href="route('customer.send-money')" icon="send" :label="__('Send')" :active="request()->is('send-money')" wire:navigate />
        <x-bottom-nav-item :href="route('customer.my-ajo')" icon="users" :label="__('Ajo')" :active="request()->is('my-ajo*')" wire:navigate />
        <x-bottom-nav-item :href="route('customer.history')" icon="history" :label="__('History')" :active="request()->is('history')" wire:navigate />
        <x-bottom-nav-item :href="route('customer.profile')" icon="user" :label="__('Profile')" :active="request()->is('profile')" wire:navigate />
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
