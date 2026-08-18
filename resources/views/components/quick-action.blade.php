@props([
    'icon',
    'label',
])

<button {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-4 bg-surface rounded-card shadow-elevation-1 transition-all duration-250 ease-material hover:shadow-elevation-2 active:scale-95']) }}
    x-data="{ show: false }" 
    x-init="setTimeout(() => show = true, 150)"
    :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
    class="opacity-0 translate-y-4">
    
    <div class="w-14 h-14 rounded-full bg-primary-light text-primary flex items-center justify-center mb-3">
        @svg('lucide-'.$icon, 'w-7 h-7')
    </div>
    
    <span class="text-sm font-medium text-text-primary text-center leading-tight">
        {{ $label }}
    </span>
</button>
