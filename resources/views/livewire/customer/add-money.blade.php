<div class="px-4 py-6 md:p-8 max-w-xl mx-auto space-y-6" x-data x-on:redirect-to-monnify.window="window.location.href = $event.detail.url">
    <div>
        <h2 class="text-2xl font-bold text-text-primary">{{ __('Add Money') }}</h2>
        <p class="text-text-secondary mt-1">{{ __('Fund your') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('wallet using any of the options below.') }}</p>
    </div>

    {{-- Balance Card --}}
    <div class="rounded-card bg-gradient-to-br from-primary to-primary-dark p-5 text-white shadow-elevation-2">
        <div class="flex items-center justify-between mb-1">
            <p class="text-sm text-white/80">{{ __('Current Balance') }}</p>
            <button wire:click="syncBalance" wire:loading.attr="disabled" :disabled="$isSyncing"
                    class="flex items-center gap-1.5 text-white/60 hover:text-white text-xs transition-colors">
                <x-lucide-refresh-cw class="w-3.5 h-3.5 {{ $isSyncing ? 'animate-spin' : '' }}" />
                <span>{{ __('Sync') }}</span>
            </button>
        </div>
        <p class="text-3xl font-bold mt-1">₦{{ number_format($wallet?->available_balance ?? 0, 2) }}</p>
        @if($monnifyBalance !== null)
        <p class="text-white/50 text-xs mt-1">{{ __('Monnify Balance') }}: ₦{{ number_format($monnifyBalance, 2) }}</p>
        @endif
    </div>

    {{-- Bank Transfer --}}
    @if($accountDisplay && !$accountDisplay['is_pending'])
    <div class="rounded-card border border-border bg-surface p-6 shadow-elevation-1">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-primary-light flex items-center justify-center text-primary">
                <x-lucide-building-2 class="w-5 h-5" />
            </div>
            <div>
                <h3 class="font-semibold text-text-primary">{{ __('Bank Transfer') }}</h3>
                <p class="text-xs text-text-secondary">{{ __('Transfer directly to your wallet account') }}</p>
            </div>
        </div>
        <div class="bg-background rounded-btn p-4 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Bank') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ $accountDisplay['partner'] ?? 'Wema Bank' }}</span>
            </div>
            <div class="flex items-center justify-between" x-data="{ copied: false }">
                <span class="text-sm text-text-secondary">{{ __('Account Number') }}</span>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-text-primary tracking-wider tabular-nums">{{ $accountDisplay['formatted_account_number'] ?? 'N/A' }}</span>
                    <button @click="navigator.clipboard.writeText('{{ $accountDisplay['account_number'] }}'); copied = true; setTimeout(() => copied = false, 2000)" class="p-1.5 hover:bg-gray-200 rounded-lg transition-colors">
                        <x-lucide-copy class="w-4 h-4 text-primary" x-show="!copied" />
                        <x-lucide-check class="w-4 h-4 text-emerald-600" x-show="copied" style="display: none;" />
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Account Name') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ Auth::user()->full_name }}</span>
            </div>
        </div>
        <div class="mt-3 bg-amber-50 border border-amber-200 rounded-btn p-3 flex items-start gap-2">
            <x-lucide-info class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
            <p class="text-xs text-amber-800">{{ __('Transfer any amount to this account number. Your wallet will be credited automatically once the transfer is confirmed.') }}</p>
        </div>
    </div>
    @endif

    {{-- Card Payment --}}
    <div class="rounded-card border border-border bg-surface p-6 shadow-elevation-1" x-data="{ showForm: false, amount: '{{ $cardAmount }}', loading: false, cardError: '{{ $cardError }}', cardSuccess: '{{ $cardSuccess }}' }">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                <x-lucide-credit-card class="w-5 h-5" />
            </div>
            <div>
                <h3 class="font-semibold text-text-primary">{{ __('Pay with Card') }}</h3>
                <p class="text-xs text-text-secondary">{{ __('Fund instantly with your debit or credit card') }}</p>
            </div>
        </div>

        @if($cardSuccess)
        <div class="bg-emerald-50 border border-emerald-200 rounded-btn p-4 text-center">
            <x-lucide-check-circle class="w-10 h-10 text-emerald-600 mx-auto mb-2" />
            <p class="text-sm font-medium text-emerald-800">{{ $cardSuccess }}</p>
        </div>
        @else
        <div x-show="!showForm" class="text-center py-4">
            <button @click="showForm = true" class="w-full rounded-btn bg-emerald-600 text-white px-6 py-3 text-sm font-semibold hover:bg-emerald-700 transition-all active:scale-[0.98]">
                {{ __('Fund with Card') }}
            </button>
            <p class="text-xs text-text-secondary mt-3">{{ __('Secure card payment powered by Monnify. Your card details are never stored.') }}</p>
        </div>

        <div x-show="showForm" x-cloak class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Amount') }}</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-text-secondary font-medium">₦</span>
                    <input type="number" x-model="amount" wire:model.live="cardAmount"
                           class="w-full pl-10 pr-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                           placeholder="1000" min="100">
                </div>
                @error('cardAmount') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div x-show="cardError" x-cloak class="bg-red-50 border border-red-200 rounded-btn p-3 text-sm text-danger">
                <span x-text="cardError"></span>
            </div>

            <button @click="loading = true; $wire.initiateCardPayment().then(result => { loading = false; if (result?.checkout_url) { $dispatch('redirect-to-monnify', { url: result.checkout_url }); } else if (result === null) { /* error set in component */ } })"
                    wire:loading.attr="disabled" :disabled="loading || !amount || amount < 100"
                    class="w-full rounded-btn bg-emerald-600 text-white px-6 py-3.5 text-sm font-semibold hover:bg-emerald-700 transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                <span wire:loading.remove>{{ __('Pay with Card') }}</span>
                <span wire:loading class="flex items-center gap-2">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    {{ __('Redirecting...') }}
                </span>
            </button>

            <p class="text-xs text-text-secondary text-center">{{ __('Your card will be charged in Naira (NGN)') }}</p>
        </div>
        @endif
    </div>

    {{-- Visit an Agent --}}
    <div class="rounded-card border border-border bg-surface p-6 shadow-elevation-1">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center text-secondary">
                <x-lucide-store class="w-5 h-5" />
            </div>
            <div>
                <h3 class="font-semibold text-text-primary">{{ __('Visit an Agent') }}</h3>
                <p class="text-xs text-text-secondary">{{ __('Deposit cash at any') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('agent location') }}</p>
            </div>
        </div>
        <p class="text-sm text-text-secondary">{{ __('Visit any') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('agent near you, provide your phone number, and hand over cash. The agent will credit your wallet instantly.') }}</p>
    </div>
</div>
