@props([
    'amount',
    'accountNumber',
    'accountStatus' => 'active',
    'accountMessage' => null,
    'accountHeadline' => 'Account Number',
    'isPending' => false,
    'isCopyable' => true,
])

<div {{ $attributes->merge(['class' => 'relative p-6 rounded-card overflow-hidden bg-gradient-brand text-white shadow-elevation-3 glow-gold transition-all duration-500 ease-spring hover:shadow-glow-primary']) }}
    x-data="{ show: false }"
    x-init="setTimeout(() => show = true, 50)"
    :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
    class="opacity-0 translate-y-4">

    {{-- Decorative highlights --}}
    <div class="pointer-events-none absolute -top-16 -right-16 w-56 h-56 bg-white/15 rounded-full blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-20 -left-10 w-56 h-56 bg-secondary/30 rounded-full blur-3xl"></div>
    <div class="pointer-events-none absolute inset-0 shimmer opacity-60"></div>

    <div class="relative">
        <p class="text-amber-100/90 text-sm font-medium mb-1">{{ __('Available Balance') }}</p>
        <h2 class="text-4xl font-display font-extrabold tabular-nums tracking-tight mb-6 drop-shadow-sm">{{ $amount }}</h2>

        <div class="flex items-center justify-between border-t border-white/25 pt-4" x-data="{ copied: false }">
            <div>
                <p class="text-xs text-amber-100/80 mb-0.5">{{ $accountHeadline }}</p>
                @if($isPending)
                    <p class="text-sm font-semibold">{{ $accountMessage ?? 'Complete verification to activate your account number.' }}</p>
                @else
                    <p class="text-sm font-semibold tracking-wider tabular-nums">{{ $accountNumber }}</p>
                @endif
            </div>

            @if($isCopyable && !$isPending && filled($accountNumber))
                <button
                    @click="navigator.clipboard.writeText('{{ $accountNumber }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="p-2.5 bg-white/15 hover:bg-white/25 backdrop-blur-sm rounded-full transition-all duration-200 active:scale-90 flex items-center justify-center cursor-pointer"
                    title="Copy Account Number"
                >
                    <x-lucide-copy class="w-4 h-4" x-show="!copied" />
                    <x-lucide-check class="w-4 h-4 text-white" x-show="copied" style="display: none;" />
                </button>
            @endif
        </div>
    </div>
</div>
