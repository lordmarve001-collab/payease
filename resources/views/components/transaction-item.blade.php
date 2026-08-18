@props([
    'type' => 'credit', // credit, debit, failed
    'title',
    'subtitle' => null,
    'amount',
    'timestamp' => null,
])

@php
    $iconBg = match($type) {
        'credit' => 'bg-primary-light text-primary',
        'debit' => 'bg-red-100 text-danger',
        'failed' => 'bg-gray-100 text-text-secondary',
        default => 'bg-gray-100 text-gray-500',
    };

    $amountColor = match($type) {
        'credit' => 'text-primary',
        'debit' => 'text-text-primary',
        'failed' => 'text-text-secondary line-through',
        default => 'text-text-primary',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-between p-4 bg-surface rounded-card shadow-sm hover:shadow-elevation-1 transition-all duration-250 ease-material']) }}
    x-data="{ show: false }" 
    x-init="setTimeout(() => show = true, 100)"
    :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
    class="opacity-0 translate-y-4">
    
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 {{ $iconBg }}">
            @if($type === 'credit')
                <x-lucide-arrow-down-left class="w-6 h-6" />
            @elseif($type === 'debit')
                <x-lucide-arrow-up-right class="w-6 h-6" />
            @else
                <x-lucide-x class="w-6 h-6" />
            @endif
        </div>
        
        <div>
            <h4 class="text-base font-semibold text-text-primary">{{ $title }}</h4>
            @if($subtitle)
                <p class="text-sm text-text-secondary">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    
    <div class="text-right">
        <p class="text-base font-bold {{ $amountColor }} tabular-nums">
            {{ $type === 'credit' ? '+' : ($type === 'debit' ? '-' : '') }}{{ $amount }}
        </p>
        @if($timestamp)
            <p class="text-xs text-text-secondary mt-1">{{ $timestamp }}</p>
        @endif
    </div>
</div>
