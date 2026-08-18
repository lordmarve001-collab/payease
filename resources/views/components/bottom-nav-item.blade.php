@props([
    'icon',
    'label',
    'active' => false,
])

@php
    $colorClass = $active ? 'text-primary' : 'text-text-secondary hover:text-text-primary';
@endphp

<a {{ $attributes->merge(['class' => "flex flex-col items-center justify-center gap-1 $colorClass transition-colors"]) }}>
    @svg('lucide-'.$icon, 'w-6 h-6 ' . ($active ? 'fill-primary/20' : ''))
    <span class="text-[10px] font-medium">{{ $label }}</span>
</a>
