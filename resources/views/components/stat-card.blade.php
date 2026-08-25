@props([
    'label',
    'value',
    'trend' => null,
    'trendDirection' => 'up',
    'accent' => 'gold',
])

@php
    $accentBar = match ($accent) {
        'violet' => 'from-secondary to-accent',
        'green' => 'from-emerald-400 to-success',
        'red' => 'from-red-400 to-danger',
        default => 'from-primary to-accent',
    };
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-surface border border-border p-6 rounded-card shadow-elevation-1 transition-all duration-300 ease-spring hover:-translate-y-1 hover:shadow-elevation-3 hover:border-white/10']) }}
    x-data="{ show: false }"
    x-init="setTimeout(() => show = true, 50)"
    :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
    class="opacity-0 translate-y-4">

    <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r {{ $accentBar }}"></div>

    <p class="text-text-secondary text-sm font-medium mb-2">{{ $label }}</p>
    <div class="flex items-baseline justify-between">
        <h3 class="text-3xl font-display font-bold text-text-primary tabular-nums">{{ $value }}</h3>
        @if($trend)
            <div class="flex items-center text-sm font-semibold {{ $trendDirection === 'up' ? 'text-success' : 'text-danger' }}">
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
