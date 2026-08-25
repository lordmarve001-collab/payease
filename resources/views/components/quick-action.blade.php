@props([
    'icon',
    'label',
])

<button {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 p-4 bg-surface border border-border rounded-card shadow-elevation-1 transition-all duration-250 ease-spring hover:-translate-y-1 hover:shadow-elevation-3 hover:border-white/10 active:scale-95 cursor-pointer']) }}
    x-data="{ show: false }"
    x-init="setTimeout(() => show = true, 150)"
    :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
    class="opacity-0 translate-y-4">

    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary/20 to-secondary/20 text-primary flex items-center justify-center transition-all duration-250 group-hover:scale-105">
        @svg('lucide-'.$icon, 'w-7 h-7')
    </div>

    <span class="text-sm font-medium text-text-primary text-center leading-tight">
        {{ $label }}
    </span>
</button>
