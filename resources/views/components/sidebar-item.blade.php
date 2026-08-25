@props([
    'icon',
    'label',
    'active' => false,
])

@php
    $colorClass = $active
        ? 'text-primary bg-gradient-to-r from-primary/15 to-transparent shadow-[inset_3px_0_0_var(--color-primary)]'
        : 'text-text-secondary hover:bg-surface-2 hover:text-text-primary';
@endphp

<a {{ $attributes->merge(['class' => "relative flex items-center gap-3 px-3 py-2.5 rounded-btn transition-all duration-200 cursor-pointer $colorClass"]) }}>
    <div class="shrink-0 flex items-center justify-center">
        @svg('lucide-'.$icon, 'w-5 h-5')
    </div>
    <span class="font-medium whitespace-nowrap overflow-hidden transition-all duration-200"
          x-show="expanded"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 w-0"
          x-transition:enter-end="opacity-100 w-auto"
          x-transition:leave="transition ease-in duration-200"
          x-transition:leave-start="opacity-100 w-auto"
          x-transition:leave-end="opacity-0 w-0"
          style="display: none;">
        {{ $label }}
    </span>
</a>
