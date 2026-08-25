@props([
    'type' => 'success',
])

@php
    $borderColor = match ($type) {
        'success' => 'border-success',
        'info' => 'border-secondary',
        default => 'border-danger',
    };
    $iconWrap = match ($type) {
        'success' => 'bg-emerald-500/15 text-success',
        'info' => 'bg-secondary/15 text-accent',
        default => 'bg-red-500/15 text-danger',
    };
@endphp

<div x-data="{ 
        show: false, 
        message: '',
        timeout: null,
        init() {
            window.addEventListener('notify-{{ $type }}', (e) => {
                this.message = typeof e.detail === 'string' ? e.detail : (e.detail.message ?? '');
                this.show = true;
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => this.show = false, 3000);
            });
        }
     }"
     x-show="show"
     x-transition:enter="transition ease-spring duration-300"
     x-transition:enter-start="opacity-0 translate-y-[-100%] sm:translate-y-0 sm:translate-x-[100%]"
     x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
     x-transition:leave="transition ease-spring duration-300"
     x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
     x-transition:leave-end="opacity-0 translate-y-[-100%] sm:translate-y-0 sm:translate-x-[100%]"
     {{ $attributes->merge(['class' => "fixed top-4 right-4 sm:top-6 sm:right-6 w-[calc(100%-2rem)] sm:w-auto sm:min-w-[320px] z-50 glass-strong shadow-elevation-4 rounded-card border-l-4 $borderColor p-4 flex items-start gap-3"]) }}
     style="display: none;">

    <div class="shrink-0 {{ $iconWrap }} w-9 h-9 rounded-full flex items-center justify-center mt-0.5">
        @if($type === 'success')
            <x-lucide-check class="w-5 h-5" />
        @elseif($type === 'info')
            <x-lucide-info class="w-5 h-5" />
        @else
            <x-lucide-alert-triangle class="w-5 h-5" />
        @endif
    </div>

    <div class="flex-1">
        <p class="text-sm font-medium text-text-primary" x-text="message"></p>
    </div>

    <button @click="show = false" class="shrink-0 text-text-secondary hover:text-text-primary transition-colors cursor-pointer">
        <x-lucide-x class="w-4 h-4" />
    </button>
</div>
