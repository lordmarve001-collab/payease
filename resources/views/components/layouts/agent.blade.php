<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>{{ $siteSettings->site_name ?? 'PayEase' }} — Agent</title>
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
                <div class="w-8 h-8 rounded-full bg-secondary flex items-center justify-center text-white shrink-0">
                    <x-lucide-briefcase class="w-4 h-4" />
                </div>
                <span class="font-bold text-lg text-secondary">{{ $siteSettings->site_name ?? 'PayEase' }}</span>
            @endif
            <span class="bg-secondary text-white text-[10px] font-bold px-1.5 py-0.5 rounded tracking-wide uppercase">Agent</span>
        </div>
        <div class="hidden md:flex items-center gap-2">
            <span class="bg-secondary text-white text-[10px] font-bold px-1.5 py-0.5 rounded tracking-wide uppercase">Agent Portal</span>
        </div>
        
        <div class="flex items-center gap-4 ml-auto">
            <button class="relative p-2 text-text-secondary opacity-60 cursor-not-allowed" disabled>
                <x-lucide-bell class="w-6 h-6" />
                <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-danger rounded-full border-2 border-surface"></span>
            </button>
            <div class="w-8 h-8 rounded-full bg-accent text-white flex items-center justify-center font-bold text-sm">
                {{ strtoupper(substr(Auth::user()->full_name ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', Auth::user()->full_name ?? ' ')[1] ?? '', 0, 1)) }}
            </div>
        </div>
    </header>

    <!-- Sidebar (Desktop) -->
    <x-sidebar>
        <x-sidebar-item href="{{ route('agent.dashboard') }}" icon="layout-dashboard" :label="__('Dashboard')" :active="request()->is('agent/dashboard')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.cash-in') }}" icon="arrow-down-circle" :label="__('Cash In')" :active="request()->is('agent/cash-in')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.cash-out') }}" icon="arrow-up-circle" :label="__('Cash Out')" :active="request()->is('agent/cash-out')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.ajo-collection') }}" icon="users" :label="__('Ajo Collection')" :active="request()->is('agent/ajo-collection')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.earnings') }}" icon="pie-chart" :label="__('Earnings')" :active="request()->is('agent/earnings')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.customers') }}" icon="users" :label="__('Customers')" :active="request()->is('agent/customers')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.settle-float') }}" icon="wallet" :label="__('Settle Float')" :active="request()->is('agent/settle-float')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.create-customer') }}" icon="user-plus" :label="__('Create Customer')" :active="request()->is('agent/create-customer')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.upgrade-customer') }}" icon="shield-check" :label="__('Upgrade KYC')" :active="request()->is('agent/upgrade-customer')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.verify-nin') }}" icon="id-card" :label="__('Verify NIN')" :active="request()->is('agent/verify-nin')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.request-topup') }}" icon="alert-triangle" :label="__('Request Top-up')" :active="request()->is('agent/request-topup')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.transactions') }}" icon="history" :label="__('Transactions')" :active="request()->is('agent/transactions')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.kyc-submissions') }}" icon="file-check" :label="__('KYC Submissions')" :active="request()->is('agent/kyc-submissions')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.notifications') }}" icon="bell" :label="__('Notifications')" :active="request()->is('agent/notifications')" wire:navigate />
        <x-sidebar-item href="{{ route('agent.profile') }}" icon="user" :label="__('Profile')" :active="request()->is('agent/profile')" wire:navigate />
    </x-sidebar>

    <!-- Bottom Nav (Mobile) -->
    <x-bottom-nav>
        <x-bottom-nav-item href="{{ route('agent.dashboard') }}" icon="layout-dashboard" :label="__('Home')" :active="request()->is('agent/dashboard')" wire:navigate />
        <x-bottom-nav-item href="{{ route('agent.cash-in') }}" icon="arrow-down-circle" :label="__('Cash In')" :active="request()->is('agent/cash-in')" wire:navigate />
        <a href="{{ route('agent.cash-out') }}" wire:navigate class="relative -top-5 flex flex-col items-center justify-center gap-1 text-text-secondary hover:text-secondary transition-colors">
            <div class="w-14 h-14 rounded-full bg-secondary text-white flex items-center justify-center shadow-elevation-2 active:scale-95 transition-transform border-4 border-surface {{ request()->is('agent/cash-out') ? 'ring-2 ring-secondary ring-offset-2 ring-offset-background' : '' }}">
                <x-lucide-arrow-up-circle class="w-6 h-6" />
            </div>
            <span class="text-[10px] font-medium mt-1">{{ __('Cash Out') }}</span>
        </a>
        <x-bottom-nav-item href="{{ route('agent.ajo-collection') }}" icon="users" :label="__('Ajo')" :active="request()->is('agent/ajo-collection')" wire:navigate />
        <x-bottom-nav-item href="{{ route('agent.earnings') }}" icon="pie-chart" :label="__('Earnings')" :active="request()->is('agent/earnings')" wire:navigate />
        <x-bottom-nav-item href="{{ route('agent.customers') }}" icon="users" :label="__('Customers')" :active="request()->is('agent/customers')" wire:navigate />
        <x-bottom-nav-item href="{{ route('agent.profile') }}" icon="user" :label="__('Profile')" :active="request()->is('agent/profile')" wire:navigate />
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
