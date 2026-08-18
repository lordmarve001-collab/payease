<div class="space-y-6 pb-6">
    <div class="flex items-center gap-3">
        @if($step > 1 && $step < 3)
            <button wire:click="goBack" class="p-2 rounded-lg hover:bg-background text-text-secondary hover:text-text-primary transition-colors">
                <x-lucide-arrow-left class="w-5 h-5" />
            </button>
        @else
            <a href="{{ route('ajo-owner.dashboard') }}" wire:navigate class="p-2 rounded-lg hover:bg-background text-text-secondary hover:text-text-primary transition-colors">
                <x-lucide-arrow-left class="w-5 h-5" />
            </a>
        @endif
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Send Fund') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Transfer to') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('users or bank accounts') }}</p>
        </div>
    </div>

    <!-- Balance -->
    <div class="bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 rounded-2xl p-4 text-white shadow-lg">
        <p class="text-purple-200 text-xs font-medium">{{ __('Available Balance') }}</p>
        <p class="text-2xl font-bold mt-1">₦{{ number_format($wallet?->available_balance ?? 0, 2) }}</p>
    </div>

    @if($step === 1)
    <!-- Recipient Type Toggle -->
    <div class="flex bg-background rounded-xl border border-border p-1">
        <button wire:click="$set('recipientType', 'phone')" class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all {{ $recipientType === 'phone' ? 'bg-purple-600 text-white shadow' : 'text-text-secondary hover:text-text-primary' }}">
            {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('User') }}
        </button>
        <button wire:click="$set('recipientType', 'bank')" class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all {{ $recipientType === 'bank' ? 'bg-purple-600 text-white shadow' : 'text-text-secondary hover:text-text-primary' }}">
            {{ __('Other Bank') }}
        </button>
    </div>

    @if($recipientType === 'phone')
    <!-- Phone Transfer -->
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Recipient Phone') }}</label>
            <input type="tel" wire:model.live="phone" placeholder="08012345678"
                   class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
        </div>
        @if($recipientName)
        <div class="flex items-center gap-2 text-emerald-600">
            <x-lucide-check-circle class="w-4 h-4" />
            <span class="text-sm font-medium">{{ $recipientName }}</span>
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Amount') }}</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-text-secondary font-medium">₦</span>
                <input type="number" wire:model.live="amount" placeholder="0" min="1"
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
            </div>
        </div>
    </div>
    @else
    <!-- Bank Transfer -->
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Select Bank') }}</label>
            <select wire:model.live="selectedBankCode"
                    class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500">
                <option value="">-- {{ __('Select Bank') }} --</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank['code'] }}">{{ $bank['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Account Number') }}</label>
            <input type="text" wire:model.live="accountNumber" maxlength="10" placeholder="0123456789"
                   class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
        </div>
        @if($accountNameLoading)
        <div class="flex items-center gap-2 text-purple-600">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <span class="text-sm">{{ __('Verifying account...') }}</span>
        </div>
        @elseif($accountName)
        <div class="flex items-center gap-2 text-emerald-600">
            <x-lucide-check-circle class="w-4 h-4" />
            <span class="text-sm font-medium">{{ $accountName }}</span>
        </div>
        @endif
        @if($accountNameError)
        <p class="text-sm text-red-600">{{ $accountNameError }}</p>
        @endif

        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Amount') }}</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-text-secondary font-medium">₦</span>
                <input type="number" wire:model.live="amount" placeholder="0" min="1"
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
            </div>
        </div>
    </div>
    @endif

    @if($validationMessage && $amount !== '' && (float) $amount > 0)
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-3 text-sm text-red-700 dark:text-red-400">
        {{ $validationMessage }}
    </div>
    @endif

    <!-- Summary -->
    @if($canAdvanceToPin && $amount !== '' && (float) $amount > 0)
    <div class="bg-surface rounded-xl border border-border p-4 space-y-2">
        <div class="flex items-center justify-between text-sm">
            <span class="text-text-secondary">{{ __('Amount') }}</span>
            <span class="font-semibold text-text-primary">₦{{ number_format((float) $this->amount, 2) }}</span>
        </div>
        @if($this->fee > 0)
        <div class="flex items-center justify-between text-sm">
            <span class="text-text-secondary">{{ __('Fee') }}</span>
            <span class="font-semibold text-text-primary">₦{{ number_format($this->fee, 2) }}</span>
        </div>
        @endif
        <div class="border-t border-border pt-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-text-primary">{{ __('Total') }}</span>
            <span class="text-lg font-bold text-purple-600">₦{{ number_format($this->total, 2) }}</span>
        </div>
    </div>

    <button wire:click="continueToConfirm"
            class="w-full py-3.5 bg-purple-600 text-white rounded-xl font-semibold text-sm hover:bg-purple-700 transition-all active:scale-[0.98] shadow-lg">
        {{ __('Continue') }}
    </button>
    @endif
    @endif

    @if($step === 2)
    <!-- Review Step -->
    <div class="bg-surface rounded-xl border border-border p-5 space-y-4">
        <h3 class="font-semibold text-text-primary text-center">{{ __('Confirm Transfer') }}</h3>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Recipient') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ $recipientName }}</span>
            </div>
            @if($recipientType === 'phone')
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Phone') }}</span>
                <span class="text-sm font-semibold text-text-primary tabular-nums">{{ $phone }}</span>
            </div>
            @else
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Bank Account') }}</span>
                <span class="text-sm font-semibold text-text-primary tabular-nums">{{ $accountNumber }}</span>
            </div>
            @endif
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Amount') }}</span>
                <span class="text-sm font-bold text-purple-600">₦{{ number_format((float) $this->amount, 2) }}</span>
            </div>
        </div>
    </div>

    <button wire:click="continueToPinStep"
            class="w-full py-3.5 bg-purple-600 text-white rounded-xl font-semibold text-sm hover:bg-purple-700 transition-all active:scale-[0.98] shadow-lg">
        {{ __('Proceed to PIN') }}
    </button>
    @endif

    @if($step === 25)
    <!-- PIN Step -->
    <div class="bg-surface rounded-xl border border-border p-6 text-center space-y-4">
        <div class="w-14 h-14 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mx-auto">
            <x-lucide-lock class="w-7 h-7 text-purple-600" />
        </div>
        <h3 class="font-bold text-text-primary">{{ __('Enter Transfer PIN') }}</h3>
        <p class="text-sm text-text-secondary">Enter your 6-digit PIN to authorize ₦{{ number_format((float) $this->amount, 2) }} to {{ $recipientName }}</p>

        <div class="flex justify-center gap-3 mt-4" x-data x-init="$watch('$wire.pin1', v => { if(v && document.querySelector('[data-pin-input=\"1\"]')) document.querySelector('[data-pin-input=\"2\"]')?.focus() })" @focus-transfer-pin.window="$nextTick(() => document.querySelector('[data-pin-input=\"1\"]')?.focus())">
            @foreach(['pin1','pin2','pin3','pin4','pin5','pin6'] as $i => $pin)
            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]"
                   wire:model.live="{{ $pin }}" data-pin-input="{{ $i+1 }}"
                   class="w-12 h-14 text-center text-xl font-bold rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums"
                   autocomplete="one-time-code" />
            @endforeach
        </div>

        @if($pinError)
        <p class="text-sm text-red-600 mt-2">{{ $pinError }}</p>
        @endif

        @if($pinLockSeconds > 0)
        <p class="text-xs text-text-secondary mt-1">Locked for {{ $pinLockSeconds }}s</p>
        @endif

        <button wire:click="confirmTransferPin" wire:loading.attr="disabled"
                class="w-full py-3.5 bg-purple-600 text-white rounded-xl font-semibold text-sm hover:bg-purple-700 transition-all active:scale-[0.98] shadow-lg mt-2 flex items-center justify-center gap-2">
            <span wire:loading.remove>{{ __('Confirm Transfer') }}</span>
            <span wire:loading class="flex items-center gap-2">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ __('Processing...') }}
            </span>
        </button>
    </div>
    @endif

    @if($step === 3)
    <!-- Result -->
    <div class="bg-surface rounded-xl border border-border p-6 text-center space-y-4">
        @if($resultState === 'success')
        <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mx-auto">
            <x-lucide-check-circle class="w-9 h-9 text-emerald-600" />
        </div>
        <h3 class="text-xl font-bold text-text-primary">{{ __('Transfer Successful') }}</h3>
        @else
        <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto">
            <x-lucide-x-circle class="w-9 h-9 text-red-600" />
        </div>
        <h3 class="text-xl font-bold text-text-primary">{{ __('Transfer Failed') }}</h3>
        @endif

        <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-text-secondary">{{ __('Amount') }}</span>
                <span class="font-bold text-text-primary">₦{{ number_format((float) $this->amount, 2) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-text-secondary">{{ __('Recipient') }}</span>
                <span class="font-semibold text-text-primary">{{ $recipientName }}</span>
            </div>
            @if($reference)
            <div class="flex items-center justify-between">
                <span class="text-text-secondary">{{ __('Reference') }}</span>
                <span class="font-mono text-xs text-text-primary">{{ $reference }}</span>
            </div>
            @endif
            <div class="flex items-center justify-between">
                <span class="text-text-secondary">{{ __('Date') }}</span>
                <span class="text-text-primary">{{ $date }}</span>
            </div>
            @if($resultState === 'success')
            <div class="flex items-center justify-between">
                <span class="text-text-secondary">{{ __('Balance') }}</span>
                <span class="font-bold text-emerald-600">₦{{ number_format($newBalance, 2) }}</span>
            </div>
            @endif
        </div>

        @if($resultMessage)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-3 text-sm text-red-700 dark:text-red-400">
            {{ $resultMessage }}
        </div>
        @endif

        <button wire:click="tryAgain"
                class="w-full py-3.5 bg-purple-600 text-white rounded-xl font-semibold text-sm hover:bg-purple-700 transition-all active:scale-[0.98] shadow-lg">
            {{ $resultState === 'success' ? __('Send Another') : __('Try Again') }}
        </button>

        <a href="{{ route('ajo-owner.dashboard') }}" wire:navigate
           class="block text-sm font-medium text-purple-600 hover:text-purple-700">{{ __('Back to Dashboard') }}</a>
    </div>
    @endif
</div>
