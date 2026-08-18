@props([
    'amount',
    'accountNumber',
    'accountStatus' => 'active',
    'accountMessage' => null,
    'accountHeadline' => 'Account Number',
    'isPending' => false,
    'isCopyable' => true,
])

<div {{ $attributes->merge(['class' => 'p-6 rounded-card shadow-elevation-2 bg-gradient-to-br from-primary to-primary-dark text-white transition-all duration-250 ease-material hover:shadow-elevation-3']) }}
    x-data="{ show: false }" 
    x-init="setTimeout(() => show = true, 50)"
    :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
    class="opacity-0 translate-y-4">
    
    <p class="text-primary-light text-sm font-medium mb-1">Available Balance</p>
    <h2 class="text-4xl font-bold tabular-nums mb-6">{{ $amount }}</h2>
    
    <div class="flex items-center justify-between border-t border-white/20 pt-4" x-data="{ copied: false }">
        <div>
            <p class="text-xs text-primary-light mb-0.5">{{ $accountHeadline }}</p>
            @if($isPending)
                <p class="text-sm font-semibold">{{ $accountMessage ?? 'Complete verification to activate your account number.' }}</p>
            @else
                <p class="text-sm font-semibold tracking-wider tabular-nums">{{ $accountNumber }}</p>
            @endif
        </div>
        
        @if($isCopyable && !$isPending && filled($accountNumber))
            <button 
                @click="navigator.clipboard.writeText('{{ $accountNumber }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="p-2 bg-white/10 hover:bg-white/20 rounded-full transition-colors active:scale-95 flex items-center justify-center"
                title="Copy Account Number"
            >
                <x-lucide-copy class="w-4 h-4" x-show="!copied" />
                <x-lucide-check class="w-4 h-4 text-white" x-show="copied" style="display: none;" />
            </button>
        @endif
    </div>
</div>
