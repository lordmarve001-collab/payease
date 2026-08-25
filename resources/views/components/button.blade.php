@props([
    'variant' => 'primary',
    'size' => 'default',
])

@php
    $baseClasses = 'group inline-flex items-center justify-center font-semibold rounded-btn min-h-[48px] transition-all duration-300 ease-spring disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/60 focus-visible:ring-offset-2 focus-visible:ring-offset-background';

    $variants = [
        'primary' => 'text-slate-950 font-bold bg-gradient-to-br from-primary to-primary-dark shadow-elevation-2 hover:shadow-glow-primary hover:-translate-y-0.5',
        'secondary' => 'text-text-primary glass hover:bg-white/10 hover:-translate-y-0.5',
        'danger' => 'text-white font-bold bg-gradient-to-br from-red-500 to-danger shadow-elevation-2 hover:shadow-glow-danger hover:-translate-y-0.5',
    ];

    $sizes = [
        'default' => 'px-6 py-3 text-sm',
        'large' => 'px-8 py-4 text-base w-full',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['default']);
@endphp

<button {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
