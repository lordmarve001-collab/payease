@props([
    'variant' => 'primary',
    'size' => 'default',
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-btn min-h-[52px] transition-all duration-200 ease-material disabled:opacity-40 disabled:cursor-not-allowed transform active:scale-95';
    
    $variants = [
        'primary' => 'bg-primary text-surface hover:bg-primary-dark',
        'secondary' => 'bg-secondary text-surface hover:bg-opacity-90',
        'danger' => 'bg-danger text-surface hover:bg-opacity-90',
    ];
    
    $sizes = [
        'default' => 'px-6 py-3 text-base',
        'large' => 'px-8 py-4 text-lg w-full',
    ];
    
    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['default']);
@endphp

<button {{ $attributes->merge(['class' => $classes]) }} x-data x-on:mousedown="$el.classList.add('scale-95')" x-on:mouseup="$el.classList.remove('scale-95')" x-on:mouseleave="$el.classList.remove('scale-95')">
    {{ $slot }}
</button>
