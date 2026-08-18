    <div class="space-y-6 pb-6" x-data x-on:redirect-to-monnify.window="window.location.href = $event.detail.url">
    <div class="flex items-center gap-3">
        <a href="{{ route('ajo-owner.dashboard') }}" wire:navigate class="p-2 rounded-lg hover:bg-background text-text-secondary hover:text-text-primary transition-colors">
            <x-lucide-arrow-left class="w-5 h-5" />
        </a>
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Add Fund') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Fund your') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('wallet') }}</p>
        </div>
    </div>

    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 rounded-2xl p-5 text-white shadow-lg">
        <div class="flex items-center justify-between mb-1">
            <span class="text-purple-200 text-sm font-medium">{{ __('Wallet Balance') }}</span>
            <button wire:click="syncBalance" wire:loading.attr="disabled" :disabled="$isSyncing"
                    class="flex items-center gap-1.5 text-purple-200 hover:text-white text-xs transition-colors">
                <x-lucide-refresh-cw class="w-3.5 h-3.5 {{ $isSyncing ? 'animate-spin' : '' }}" />
                <span>{{ __('Sync') }}</span>
            </button>
        </div>
        <p class="text-3xl font-bold mb-1">₦{{ number_format($wallet?->available_balance ?? 0, 2) }}</p>
        @if($monnifyBalance !== null)
        <p class="text-purple-200 text-xs">{{ __('Monnify Balance') }}: ₦{{ number_format($monnifyBalance, 2) }}</p>
        @endif
    </div>

    <!-- Pay with Card -->
    <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <x-lucide-credit-card class="w-5 h-5 text-emerald-600" />
            </div>
            <div>
                <h3 class="font-semibold text-text-primary">{{ __('Pay with Card') }}</h3>
                <p class="text-xs text-text-secondary">{{ __('Fund instantly using your debit or credit card') }}</p>
            </div>
        </div>
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Amount') }} (₦)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary text-sm font-semibold">₦</span>
                    <input type="text" inputmode="numeric" wire:model.live="cardAmount" placeholder="0.00"
                           class="w-full pl-8 pr-4 py-3 bg-background border border-border rounded-xl text-text-primary text-sm font-semibold tabular-nums placeholder:text-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" />
                </div>
                @error('cardAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-text-secondary mt-1">{{ __('Minimum ₦100 — Maximum ₦500,000') }}</p>
            </div>
            <button wire:click="payWithCard" wire:loading.attr="disabled" wire:target="payWithCard"
                    :disabled="$isInitiatingPayment || !$cardAmount || (typeof $wire.cardAmount !== 'undefined' && parseInt($wire.cardAmount) < 100)"
                    class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 text-white rounded-xl font-semibold text-sm hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-[0.98]">
                <x-lucide-credit-card class="w-4 h-4" wire:loading.remove wire:target="payWithCard" />
                <span wire:loading wire:target="payWithCard" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    {{ __('Redirecting...') }}
                </span>
                <span wire:loading.remove wire:target="payWithCard">{{ __('Pay with Card') }}</span>
            </button>
        </div>
    </div>

    <!-- Bank Transfer -->
    @if($accountDisplay && !$accountDisplay['is_pending'])
    <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <x-lucide-building-2 class="w-5 h-5 text-purple-600" />
            </div>
            <div>
                <h3 class="font-semibold text-text-primary">{{ __('Bank Transfer') }}</h3>
                <p class="text-xs text-text-secondary">{{ __('Transfer directly to your wallet account') }}</p>
            </div>
        </div>
        <div class="bg-background rounded-xl p-4 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Bank') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ $accountDisplay['partner'] ?? 'Wema Bank' }}</span>
            </div>
            <div class="flex items-center justify-between" x-data="{ copied: false }">
                <span class="text-sm text-text-secondary">{{ __('Account Number') }}</span>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-text-primary tracking-wider tabular-nums">{{ $accountDisplay['formatted_account_number'] ?? 'N/A' }}</span>
                    <button @click="navigator.clipboard.writeText('{{ $accountDisplay['account_number'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="p-1.5 hover:bg-gray-200 rounded-lg transition-colors">
                        <x-lucide-copy class="w-4 h-4 text-purple-600" x-show="!copied" />
                        <x-lucide-check class="w-4 h-4 text-emerald-600" x-show="copied" style="display: none;" />
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Account Name') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ Auth::user()->full_name }}</span>
            </div>
        </div>
        <div class="mt-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-3 flex items-start gap-2">
            <x-lucide-info class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
            <p class="text-xs text-amber-800 dark:text-amber-300">{{ __('Transfer any amount to this account number. Your wallet will be credited automatically once the transfer is confirmed.') }}</p>
        </div>
    </div>
    @endif

    @if(!$accountDisplay || $accountDisplay['is_pending'])
    <div class="bg-surface rounded-xl border border-border p-6 shadow-sm text-center">
        <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center mx-auto mb-3">
            <x-lucide-alert-circle class="w-6 h-6 text-yellow-600" />
        </div>
        <h3 class="font-semibold text-text-primary mb-1">{{ __('Account Not Ready') }}</h3>
        <p class="text-sm text-text-secondary">{{ __('Complete your KYC verification to receive your wallet account number.') }}</p>
        <a href="{{ route('ajo-owner.kyc') }}" wire:navigate
           class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white rounded-xl font-semibold text-sm hover:bg-purple-700 transition-all active:scale-[0.98]">
            <x-lucide-shield-check class="w-4 h-4" />
            {{ __('Complete Verification') }}
        </a>
    </div>
    @endif

    <!-- Visit an Agent -->
    <div class="bg-surface rounded-xl border border-border p-6 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <x-lucide-store class="w-5 h-5 text-blue-600" />
            </div>
            <div>
                <h3 class="font-semibold text-text-primary">{{ __('Visit an Agent') }}</h3>
                <p class="text-xs text-text-secondary">{{ __('Deposit cash at any') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('agent location') }}</p>
            </div>
        </div>
        <p class="text-sm text-text-secondary">{{ __('Visit any') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('agent near you, provide your phone number, and hand over cash. The agent will credit your wallet instantly.') }}</p>
    </div>
</div>
