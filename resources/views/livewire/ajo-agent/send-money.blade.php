<div class="px-4 py-6 md:p-8 max-w-lg mx-auto relative overflow-hidden">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Send Money') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Send from your personal wallet to any') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('user or bank account.') }}</p>
        @if($wallet)
            <p class="text-sm text-emerald-600 mt-2 font-medium">{{ __('Personal Balance:') }} ₦{{ number_format($wallet->available_balance, 2) }}</p>
        @else
            <p class="text-sm text-amber-600 mt-2 font-medium">{{ __('No wallet found. Contact your Ajo Owner.') }}</p>
        @endif
    </div>

    <!-- Step 1: Recipient & Amount -->
    @if($step === 1)
    <div class="space-y-6">
        <div class="flex p-1 bg-gray-100 rounded-lg">
            <button wire:click="$set('recipientType', 'phone')" class="flex-1 py-2 text-sm font-medium rounded-md transition-all {{ $recipientType === 'phone' ? 'bg-surface shadow-sm text-emerald-600' : 'text-text-secondary' }}">
                {{ __('Phone Number') }}
            </button>
            <button wire:click="$set('recipientType', 'bank')" class="flex-1 py-2 text-sm font-medium rounded-md transition-all {{ $recipientType === 'bank' ? 'bg-surface shadow-sm text-emerald-600' : 'text-text-secondary' }}">
                {{ __('Bank Account') }}
            </button>
        </div>

        @if($recipientType === 'phone')
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Recipient Phone') }}</label>
            <div class="flex">
                <span class="inline-flex items-center px-4 rounded-l-btn border border-r-0 border-border bg-gray-50 text-text-secondary text-sm">+234</span>
                <input type="tel" wire:model.live="phone" class="flex-1 block w-full min-w-0 rounded-none rounded-r-btn border-border focus:ring-emerald-500 focus:border-emerald-500 px-4 py-3 border outline-none" placeholder="801 234 5678">
            </div>
            @if($recipientFound && $recipientName)
                <div class="bg-emerald-50 border border-emerald-200 rounded-btn p-3 mt-2 flex items-center gap-2">
                    <x-lucide-check-circle class="w-4 h-4 text-emerald-600 shrink-0" />
                    <p class="text-sm text-emerald-800 font-medium">{{ $recipientName }}</p>
                </div>
            @endif
            @if($validationMessage)
                <p class="text-sm text-danger mt-1">{{ $validationMessage }}</p>
            @endif
        </div>
        @else
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Select Bank') }}</label>
                <select wire:model.live="selectedBankCode" class="w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('-- Select Bank --') }}</option>
                    @foreach($banks as $bank)
                        <option value="{{ $bank['code'] }}">{{ $bank['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Account Number') }}</label>
                <input type="tel" wire:model.live="accountNumber" maxlength="10" class="w-full rounded-btn border border-border bg-background text-text-primary placeholder-text-secondary/50 px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="0123456789">
                @if($accountNameLoading)
                    <p class="text-sm text-emerald-600 mt-1">{{ __('Verifying account...') }}</p>
                @endif
                @if($accountName && !$accountNameLoading)
                    <div class="bg-emerald-50 border border-emerald-200 rounded-btn p-3 mt-2 flex items-center gap-2">
                        <x-lucide-check-circle class="w-4 h-4 text-emerald-600 shrink-0" />
                        <p class="text-sm text-emerald-800 font-medium">{{ $accountName }}</p>
                    </div>
                @endif
                @if($accountNameError)
                    <p class="text-sm text-danger mt-1">{{ $accountNameError }}</p>
                @endif
            </div>
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-text-secondary mb-2 text-center">{{ __('Amount') }}</label>
            <div class="flex items-center justify-center text-4xl font-bold text-text-primary">
                <span class="text-text-secondary">₦</span>
                <input type="number" wire:model="amount" min="1" step="100" class="text-center bg-transparent border-none outline-none focus:ring-0 w-48 tabular-nums" placeholder="0">
            </div>
            @error('amount') <p class="text-sm text-danger mt-1 text-center">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Description (optional)') }}</label>
            <input type="text" wire:model="description" class="w-full rounded-btn border border-border bg-background text-text-primary placeholder-text-secondary/50 px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="e.g. Payment for goods">
        </div>

        @if((float)$amount > 0)
        <div class="bg-surface rounded-card border border-border p-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('Amount') }}</span>
                <span class="font-medium tabular-nums">₦{{ number_format((float)$amount, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('Fee') }}</span>
                <span class="font-medium tabular-nums">₦{{ number_format($fee, 2) }}</span>
            </div>
            <div class="flex justify-between border-t border-border pt-2 font-bold">
                <span class="text-text-primary">{{ __('Total') }}</span>
                <span class="text-emerald-600 tabular-nums">₦{{ number_format($total, 2) }}</span>
            </div>
        </div>
        @endif

        <button wire:click="proceedToReview" @if(!(($recipientType === 'phone' && $recipientFound) || ($recipientType === 'bank' && $accountName !== ''))) disabled @endif class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 disabled:opacity-50 disabled:cursor-not-allowed">
            {{ __('Review Transfer') }}
        </button>
    </div>
    @endif

    <!-- Step 2: Review & PIN -->
    @if($step === 2)
    <div class="space-y-6">
        <button wire:click="$set('step', 1)" class="text-sm text-text-secondary hover:text-emerald-600 transition-colors">&larr; {{ __('Back') }}</button>

        <div class="bg-surface rounded-card border border-border p-5 space-y-4">
            <h3 class="font-bold text-text-primary">{{ __('Transfer Summary') }}</h3>
            <div class="flex justify-between text-sm">
                <span class="text-text-secondary">{{ __('Recipient') }}</span>
                <span class="font-medium text-text-primary">{{ $recipientType === 'phone' ? $recipientName : $accountName }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-text-secondary">{{ __('Account') }}</span>
                <span class="font-medium text-text-primary font-mono">{{ $recipientType === 'phone' ? $phone : $accountNumber }}</span>
            </div>
            @if($description)
            <div class="flex justify-between text-sm">
                <span class="text-text-secondary">{{ __('Description') }}</span>
                <span class="font-medium text-text-primary">{{ $description }}</span>
            </div>
            @endif
            <div class="border-t border-border pt-3 flex justify-between">
                <span class="text-text-secondary text-sm">{{ __('You Send') }}</span>
                <span class="font-bold text-lg tabular-nums">₦{{ number_format((float)$amount, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-text-secondary">{{ __('Fee') }}</span>
                <span class="font-medium tabular-nums">₦{{ number_format($fee, 2) }}</span>
            </div>
            <div class="flex justify-between border-t border-border pt-3">
                <span class="font-bold text-text-primary">{{ __('Total Deduction') }}</span>
                <span class="font-bold text-emerald-600 text-lg tabular-nums">₦{{ number_format($total, 2) }}</span>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Enter Your PIN') }}</label>
            <input type="password" wire:model="pin" inputmode="numeric" maxlength="6" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary text-center tracking-[0.3em] text-xl outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="••••••">
            @error('pin') <p class="text-sm text-danger mt-1 text-center">{{ $message }}</p> @enderror
        </div>

        <button wire:click="submitTransfer" wire:target="submitTransfer" wire:loading.attr="disabled" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 disabled:opacity-50">
            <span wire:loading.remove wire:target="submitTransfer">{{ __('Send ₦' . number_format((float)$amount, 2)) }}</span>
            <span wire:loading wire:target="submitTransfer" class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ __('Sending...') }}
            </span>
        </button>
    </div>
    @endif

    <!-- Step 3: Result -->
    @if($step === 3)
    <div class="text-center space-y-6 py-4">
        @if($resultState === 'success')
            <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto">
                <x-lucide-check class="w-10 h-10 text-emerald-600" />
            </div>
            <div>
                <h2 class="text-xl font-bold text-text-primary">{{ __('Transfer Successful!') }}</h2>
                <p class="text-text-secondary mt-1">{{ $resultMessage }}</p>
            </div>
            <div class="bg-surface rounded-card border border-border p-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Amount') }}</span>
                    <span class="font-bold text-emerald-600 tabular-nums">₦{{ number_format($resultAmount, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Reference') }}</span>
                    <span class="font-medium font-mono text-xs">{{ $resultReference }}</span>
                </div>
                <div class="flex justify-between border-t border-border pt-3">
                    <span class="text-text-secondary">{{ __('New Float Balance') }}</span>
                    <span class="font-bold text-text-primary tabular-nums">₦{{ number_format($resultNewBalance, 2) }}</span>
                </div>
            </div>
        @else
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                <x-lucide-x class="w-10 h-10 text-red-600" />
            </div>
            <div>
                <h2 class="text-xl font-bold text-text-primary">{{ __('Transfer Failed') }}</h2>
                <p class="text-danger mt-1 text-sm">{{ $resultMessage }}</p>
            </div>
        @endif

        <button wire:click="resetForm" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98]">
            {{ __('Send Another') }}
        </button>
    </div>
    @endif
</div>
