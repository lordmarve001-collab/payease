@props([
    'icon',
    'label',
    'active' => false,
])

@php
    $colorClass = $active ? 'text-primary' : 'text-text-secondary hover:text-text-primary';
@endphp

<a {{ $attributes->merge(['class' => "relative flex flex-col items-center justify-center gap-1 px-3 py-1.5 rounded-btn $colorClass transition-all duration-200 cursor-pointer"]) }}>
    @if($active)
        <span class="absolute -top-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-primary glow-gold"></span>
    @endif
    @svg('lucide-'.$icon, 'w-6 h-6 ' . ($active ? 'fill-primary/20' : ''))
    <span class="text-[10px] font-medium">{{ $label }}</span>
</a>
