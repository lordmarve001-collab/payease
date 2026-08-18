@props([
    'label',
    'value',
    'trend' => null,
    'trendDirection' => 'up',
])

<div {{ $attributes->merge(['class' => 'bg-surface p-6 rounded-card shadow-elevation-1 transition-all duration-250 ease-material hover:shadow-elevation-2']) }} 
    x-data="{ show: false }" 
    x-init="setTimeout(() => show = true, 50)"
    :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
    class="opacity-0 translate-y-4">
    <p class="text-text-secondary text-sm font-medium mb-2">{{ $label }}</p>
    <div class="flex items-baseline justify-between">
        <h3 class="text-3xl font-bold text-text-primary tabular-nums">{{ $value }}</h3>
        @if($trend)
            <div class="flex items-center text-sm font-medium {{ $trendDirection === 'up' ? 'text-primary' : 'text-danger' }}">
                @if($trendDirection === 'up')
                    <x-lucide-trending-up class="w-4 h-4 mr-1" />
                @else
                    <x-lucide-trending-down class="w-4 h-4 mr-1" />
                @endif
                {{ $trend }}
            </div>
        @endif
    </div>
</div>
