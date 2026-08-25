<aside {{ $attributes->merge(['class' => 'hidden md:flex flex-col bg-surface border-r border-border h-screen fixed top-0 left-0 transition-all duration-300 ease-spring z-40']) }}
    x-data="{ expanded: true }"
    :class="expanded ? 'w-[240px]' : 'w-[72px]'"
    @resize.window="if(window.innerWidth < 1024) { expanded = false } else { expanded = true }"
    x-init="if(window.innerWidth < 1024) { expanded = false }">

    <div class="h-16 flex items-center px-4 border-b border-border shrink-0" :class="expanded ? 'justify-between' : 'justify-center'">
        <div class="flex items-center gap-2 font-display font-bold text-lg text-text-primary overflow-hidden" x-show="expanded">
            <div class="w-9 h-9 rounded-xl bg-gradient-brand flex items-center justify-center text-slate-950 shadow-glow-primary shrink-0">
                <x-lucide-wallet class="w-5 h-5" />
            </div>
            <span class="whitespace-nowrap">{{ $siteSettings->site_name ?? 'PayEase' }}</span>
        </div>
        <div class="w-9 h-9 rounded-xl bg-gradient-brand flex items-center justify-center text-slate-950 shadow-glow-primary shrink-0" x-show="!expanded">
            <x-lucide-wallet class="w-5 h-5" />
        </div>
    </div>

    <div class="flex-1 py-6 flex flex-col gap-1.5 px-3 overflow-y-auto">
        {{ $slot }}
    </div>

    <div class="p-4 border-t border-border">
        <button @click="expanded = !expanded" class="flex items-center justify-center w-full p-2 text-text-secondary hover:text-text-primary hover:bg-surface-2 rounded-btn transition-colors cursor-pointer">
            <x-lucide-chevron-left class="w-5 h-5 transition-transform duration-300" x-bind:class="!expanded && 'rotate-180'" />
        </button>
    </div>
</aside>
