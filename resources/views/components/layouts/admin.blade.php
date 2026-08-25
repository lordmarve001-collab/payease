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
    <title>{{ $siteSettings->site_name ?? 'PayEase' }} — Admin</title>
    @if($siteSettings->favicon_path)
        <link rel="icon" type="image/png" href="{{ $siteSettings->faviconUrl() }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <header class="fixed top-0 left-0 right-0 h-16 bg-surface/70 backdrop-blur-xl border-b border-border z-30 flex items-center justify-between px-4 md:ml-[240px] transition-all duration-300">
        <!-- Mobile Left: Hamburger + Brand -->
        <div class="flex items-center gap-3 md:hidden">
            <button @click="$dispatch('open-mobile-sidebar')" class="p-2 -ml-2 text-text-secondary hover:text-text-primary transition-colors cursor-pointer">
                <x-lucide-menu class="w-6 h-6" />
            </button>
            <div class="flex items-center gap-2">
                @if($siteSettings->logo_path)
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name ?? 'PayEase' }}" class="h-7 object-contain">
                @else
                    <span class="font-display font-bold text-lg text-gradient-gold-violet">{{ $siteSettings->site_name ?? 'PayEase' }}</span>
                @endif
                <span class="bg-primary/15 text-primary-light text-[10px] font-bold px-1.5 py-0.5 rounded tracking-wide uppercase">Admin</span>
            </div>
        </div>

        <!-- Desktop Left: Brand & Global Search -->
        <div class="hidden md:flex items-center gap-6 flex-1">
            <div class="relative w-full max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-lucide-search class="w-4 h-4 text-text-secondary" />
                </div>
                <input type="text" class="block w-full pl-10 pr-3 py-2 border border-border rounded-btn bg-background/60 text-sm placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors" placeholder="Search users, agents, transactions...">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-xs text-text-secondary bg-surface border border-border px-1.5 py-0.5 rounded">⌘K</span>
                </div>
            </div>
        </div>

        <!-- Right Actions -->
        <div class="flex items-center gap-2 sm:gap-4 ml-auto">
            <!-- Theme Toggle -->
            <button @click="toggleTheme()" class="p-2 text-text-secondary hover:text-text-primary transition-colors rounded-full hover:bg-surface-2 cursor-pointer">
                <x-lucide-sun class="w-5 h-5" x-show="!lightMode" />
                <x-lucide-moon class="w-5 h-5" x-show="lightMode" x-cloak />
            </button>

            <!-- Notifications -->
            <livewire:admin.notification-bell />

            <!-- Admin Profile Dropdown -->
            <div x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false" class="relative">
                <button @click="open = !open" class="flex items-center gap-3 pl-2 sm:pl-4 sm:border-l border-border cursor-pointer">
                    <div class="hidden sm:block text-right">
                        <p class="text-sm font-semibold text-text-primary leading-tight">{{ auth()->user()->full_name ?? 'Admin' }}</p>
                        <p class="text-xs text-text-secondary">{{ ucfirst(str_replace('_', ' ', auth()->user()->getRoleNames()->first() ?? 'admin')) }}</p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-gradient-brand text-slate-950 flex items-center justify-center font-bold text-sm shadow-glow-primary">
                        {{ strtoupper(substr(auth()->user()->full_name ?? 'A', 0, 2)) }}
                    </div>
                    <svg class="w-4 h-4 text-text-secondary transition-transform duration-200" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95 translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-56 rounded-xl bg-surface border border-border shadow-elevation-3 py-1 z-50" x-cloak>
                    <div class="px-4 py-3 border-b border-border">
                        <p class="text-sm font-semibold text-text-primary">{{ auth()->user()->full_name ?? 'Admin' }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                    <a href="{{ url('/admin/overview') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-text-secondary hover:text-text-primary hover:bg-surface-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>
                    <div class="border-t border-border my-1"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar (Desktop Fixed) -->
    <x-sidebar class="hidden md:flex !w-[240px]">
        <div class="px-3 mb-2 mt-2 text-xs font-bold text-text-secondary uppercase tracking-wider">Main Menu</div>
        <x-sidebar-item href="{{ url('/admin/overview') }}" icon="layout-dashboard" label="Overview" :active="request()->is('admin/overview')" wire:navigate />
        <x-sidebar-item href="{{ url('/admin/users') }}" icon="users" label="Users" :active="request()->is('admin/users')" wire:navigate />
        <x-sidebar-item href="{{ url('/admin/ajo-owners') }}" icon="users-2" label="Ajo Owners" :active="request()->is('admin/ajo-owners')" wire:navigate />
        <x-sidebar-item href="{{ url('/admin/agents') }}" icon="briefcase" label="Agents" :active="request()->is('admin/agents')" wire:navigate />
        <x-sidebar-item href="{{ url('/admin/transactions') }}" icon="arrow-right-left" label="Transactions" :active="request()->is('admin/transactions')" wire:navigate />

        <div class="px-3 mb-2 mt-6 text-xs font-bold text-text-secondary uppercase tracking-wider">Operations</div>
        <x-sidebar-item href="{{ url('/admin/ajo-groups') }}" icon="users-2" label="Ajo Groups" :active="request()->is('admin/ajo-groups')" wire:navigate />
        <x-sidebar-item href="{{ url('/admin/kyc-queue') }}" icon="shield-check" label="KYC Queue" :active="request()->is('admin/kyc-queue')" wire:navigate />
        <x-sidebar-item href="{{ url('/admin/disbursements') }}" icon="banknote" label="Disbursements" :active="request()->is('admin/disbursements')" wire:navigate />
        <x-sidebar-item href="{{ url('/admin/ajo-payout-queue') }}" icon="users-2" label="Ajo Payout Queue" :active="request()->is('admin/ajo-payout-queue')" wire:navigate />
        <x-sidebar-item href="{{ url('/admin/float-management') }}" icon="wallet" label="Float Management" :active="request()->is('admin/float-management')" wire:navigate />
        <x-sidebar-item href="{{ url('/admin/liquidity') }}" icon="landmark" label="Liquidity" :active="request()->is('admin/liquidity')" wire:navigate />
        <x-sidebar-item href="#" icon="alert-triangle" label="Compliance" @click.prevent="$dispatch('notify-info', 'Compliance module coming soon')" />

        <div class="px-3 mb-2 mt-6 text-xs font-bold text-text-secondary uppercase tracking-wider">System</div>
        @if(auth()->user()?->hasRole('super_admin'))
            <x-sidebar-item href="{{ url('/admin/settings') }}" icon="settings" label="Settings" :active="request()->is('admin/settings')" wire:navigate />
            <x-sidebar-item href="{{ url('/admin/site-settings') }}" icon="palette" label="Site Settings" :active="request()->is('admin/site-settings')" wire:navigate />
        @else
            <x-sidebar-item href="#" icon="settings" label="Settings" @click.prevent="$dispatch('notify-info', 'Only super admins can access settings')" />
        @endif
    </x-sidebar>

    <!-- Mobile Sidebar Drawer -->
    <div x-data="{ open: false }"
         @open-mobile-sidebar.window="open = true"
         class="md:hidden">

        <!-- Backdrop -->
        <div x-show="open"
             x-transition.opacity
             class="fixed inset-0 bg-black/60 z-40 backdrop-blur-sm"
             @click="open = false" x-cloak></div>

        <!-- Drawer -->
        <div x-show="open"
             x-transition:enter="transition ease-spring duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-spring duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 w-[260px] glass-strong border-r border-border z-50 flex flex-col" x-cloak>

            <div class="h-16 flex items-center justify-between px-4 border-b border-border">
                <div class="flex items-center gap-2">
                    @if($siteSettings->logo_path)
                        <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name ?? 'PayEase' }}" class="h-7 object-contain">
                    @else
                        <span class="font-display font-bold text-lg text-gradient-gold-violet">{{ $siteSettings->site_name ?? 'PayEase' }}</span>
                    @endif
                    <span class="bg-primary/15 text-primary-light text-[10px] font-bold px-1.5 py-0.5 rounded tracking-wide uppercase">Admin</span>
                </div>
                <button @click="open = false" class="p-2 -mr-2 text-text-secondary hover:text-text-primary transition-colors cursor-pointer">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <div class="px-3 mb-2 mt-2 text-xs font-bold text-text-secondary uppercase tracking-wider">Main Menu</div>
                <x-sidebar-item href="{{ url('/admin/overview') }}" icon="layout-dashboard" label="Overview" :active="request()->is('admin/overview')" wire:navigate @click="open = false" />
                <x-sidebar-item href="{{ url('/admin/users') }}" icon="users" label="Users" :active="request()->is('admin/users')" wire:navigate @click="open = false" />
                <x-sidebar-item href="{{ url('/admin/ajo-owners') }}" icon="users-2" label="Ajo Owners" :active="request()->is('admin/ajo-owners')" wire:navigate @click="open = false" />
                <x-sidebar-item href="{{ url('/admin/agents') }}" icon="briefcase" label="Agents" :active="request()->is('admin/agents')" wire:navigate @click="open = false" />
                <x-sidebar-item href="{{ url('/admin/transactions') }}" icon="arrow-right-left" label="Transactions" :active="request()->is('admin/transactions')" wire:navigate @click="open = false" />

                <div class="px-3 mb-2 mt-6 text-xs font-bold text-text-secondary uppercase tracking-wider">Operations</div>
                <x-sidebar-item href="{{ url('/admin/ajo-groups') }}" icon="users-2" label="Ajo Groups" :active="request()->is('admin/ajo-groups')" wire:navigate @click="open = false" />
                <x-sidebar-item href="{{ url('/admin/kyc-queue') }}" icon="shield-check" label="KYC Queue" :active="request()->is('admin/kyc-queue')" wire:navigate @click="open = false" />
                <x-sidebar-item href="{{ url('/admin/disbursements') }}" icon="banknote" label="Disbursements" :active="request()->is('admin/disbursements')" wire:navigate @click="open = false" />
                <x-sidebar-item href="{{ url('/admin/ajo-payout-queue') }}" icon="users-2" label="Ajo Payout Queue" :active="request()->is('admin/ajo-payout-queue')" wire:navigate @click="open = false" />
                <x-sidebar-item href="{{ url('/admin/float-management') }}" icon="wallet" label="Float Management" :active="request()->is('admin/float-management')" wire:navigate @click="open = false" />
                <x-sidebar-item href="{{ url('/admin/liquidity') }}" icon="landmark" label="Liquidity" :active="request()->is('admin/liquidity')" wire:navigate @click="open = false" />
                @if(auth()->user()?->hasRole('super_admin'))
                    <div class="px-3 mb-2 mt-6 text-xs font-bold text-text-secondary uppercase tracking-wider">System</div>
                    <x-sidebar-item href="{{ url('/admin/settings') }}" icon="settings" label="Settings" :active="request()->is('admin/settings')" wire:navigate @click="open = false" />
                    <x-sidebar-item href="{{ url('/admin/site-settings') }}" icon="palette" label="Site Settings" :active="request()->is('admin/site-settings')" wire:navigate @click="open = false" />
                @endif
            </div>
        </div>
    </div>

    <!-- Bottom Nav (Mobile - Simplified) -->
    <x-bottom-nav class="md:hidden">
        <x-bottom-nav-item href="{{ url('/admin/overview') }}" icon="layout-dashboard" label="Overview" :active="request()->is('admin/overview')" wire:navigate />
        <x-bottom-nav-item href="{{ url('/admin/users') }}" icon="users" label="Users" :active="request()->is('admin/users')" wire:navigate />
        <x-bottom-nav-item href="{{ url('/admin/agents') }}" icon="briefcase" label="Agents" :active="request()->is('admin/agents')" wire:navigate />
        <x-bottom-nav-item href="{{ url('/admin/transactions') }}" icon="arrow-right-left" label="Transactions" :active="request()->is('admin/transactions')" wire:navigate />
        <x-bottom-nav-item href="#" icon="menu" label="More" @click.prevent="$dispatch('open-mobile-sidebar')" />
    </x-bottom-nav>

    <!-- Main Content -->
    <main class="pt-16 md:ml-[240px] transition-all duration-300 min-h-[calc(100vh-4rem)] relative"
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
