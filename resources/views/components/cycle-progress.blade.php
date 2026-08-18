@props([
    'total',
    'completed',
    'size' => 'default', // default, compact
    'amountCollected' => null,
    'amountTotal' => null,
])

@php
    $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
    
    // Size variants
    $svgClass = $size === 'compact' ? 'w-10 h-10' : 'w-24 h-24';
    $strokeWidth = $size === 'compact' ? '3' : '4';
    $radius = $size === 'compact' ? '16' : '36';
    $cx = $size === 'compact' ? '20' : '48';
    $cy = $size === 'compact' ? '20' : '48';
    
    // Circumference calculation
    $circumference = 2 * pi() * $radius;
@endphp

<div class="flex items-center gap-4" x-data="{ currentOffset: {{ $circumference }} }" x-init="setTimeout(() => currentOffset = {{ $circumference - ($percentage / 100 * $circumference) }}, 100)">
    <!-- Circular SVG Progress -->
    <div class="relative flex items-center justify-center shrink-0">
        <svg class="{{ $svgClass }} transform -rotate-90">
            <!-- Background track -->
            <circle 
                cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $radius }}" 
                stroke="currentColor" 
                stroke-width="{{ $strokeWidth }}" 
                fill="transparent" 
                class="text-gray-100 dark:text-gray-800" />
            
            <!-- Progress indicator -->
            <circle 
                cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $radius }}" 
                stroke="currentColor" 
                stroke-width="{{ $strokeWidth }}" 
                fill="transparent" 
                stroke-dasharray="{{ $circumference }}"
                :stroke-dashoffset="currentOffset"
                stroke-linecap="round"
                class="text-primary transition-all duration-1000 ease-out" />
        </svg>
        
        <!-- Center Text -->
        <div class="absolute inset-0 flex items-center justify-center">
            @if($size === 'compact')
                <span class="text-[10px] font-bold text-text-primary">{{ $percentage }}%</span>
            @else
                <div class="text-center">
                    <span class="block text-xl font-bold text-text-primary leading-none">{{ $percentage }}%</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Text Labels (only for default size) -->
    @if($size === 'default')
        <div>
            <p class="font-bold text-text-primary mb-0.5">{{ $completed }} of {{ $total }} members paid</p>
            @if($amountCollected && $amountTotal)
                <p class="text-sm text-text-secondary">₦{{ number_format($amountCollected) }} of ₦{{ number_format($amountTotal) }} collected</p>
            @endif
        </div>
    @endif
</div>
