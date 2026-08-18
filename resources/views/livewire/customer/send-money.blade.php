<div class="px-4 py-6 md:p-8 max-w-lg mx-auto relative overflow-hidden">
    
    <!-- Header/Title -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Send Money') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Send funds instantly to any') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('user, phone number, or bank account.') }}</p>
        @if($wallet)
            <p class="text-sm text-primary mt-2 font-medium">{{ __('Available Balance:') }} ₦{{ number_format($wallet->available_balance, 2) }}</p>
        @endif
    </div>

    <!-- Step 1: Recipient & Amount -->
    @if($step === 1)
    <div x-data x-transition:enter="transition ease-material duration-300 transform"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-material duration-300 transform absolute top-0 left-0 w-full"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 -translate-x-8"
         class="space-y-6">
        
        <!-- Toggle -->
        <div class="flex p-1 bg-gray-100 rounded-lg">
            <button wire:click="$set('recipientType', 'phone')" class="flex-1 py-2 text-sm font-medium rounded-md transition-all {{ $recipientType === 'phone' ? 'bg-surface shadow-sm text-primary' : 'text-text-secondary' }}">
                {{ __('Phone Number') }}
            </button>
            <button wire:click="$set('recipientType', 'bank')" class="flex-1 py-2 text-sm font-medium rounded-md transition-all {{ $recipientType === 'bank' ? 'bg-surface shadow-sm text-primary' : 'text-text-secondary' }}">
                {{ __('Other Bank') }}
            </button>
        </div>

        <!-- Phone Input -->
        @if($recipientType === 'phone')
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Recipient Phone') }}</label>
            <div class="flex">
                <span class="inline-flex items-center px-4 rounded-l-btn border border-r-0 border-border bg-gray-50 text-text-secondary sm:text-sm">
                    +234
                </span>
                <input type="tel" wire:model.live="phone" class="flex-1 block w-full min-w-0 rounded-none rounded-r-btn sm:text-sm border-border focus:ring-primary focus:border-primary px-4 py-3 border outline-none" placeholder="801 234 5678">
            </div>
            @if($validationMessage === 'Recipient not found.')
                <p class="text-sm text-danger mt-2">{{ $validationMessage }}</p>
            @endif
        </div>
        @else
        <!-- Bank Account Input -->
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Select Bank') }}</label>
                <select wire:model.live="selectedBankCode" class="w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    <option value="">{{ __('-- Select Bank --') }}</option>
                    @foreach($banks as $bank)
                        <option value="{{ $bank['code'] }}">{{ $bank['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Account Number') }}</label>
                <input type="tel" wire:model.live="accountNumber" maxlength="10" class="w-full rounded-btn border border-border bg-background text-text-primary placeholder-text-secondary/50 px-4 py-3 outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" placeholder="0123456789">
                @if($accountNameLoading)
                    <p class="text-sm text-primary mt-1">{{ __('Verifying account...') }}</p>
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

        <!-- Amount Input -->
        <div class="pt-4 text-center">
            <label class="block text-sm font-medium text-text-secondary mb-2">{{ __('Amount') }}</label>
            <div class="flex items-center justify-center text-4xl font-bold text-text-primary">
                <span class="text-2xl mr-1 text-text-secondary">₦</span>
                <input type="text" wire:model.live="amount" class="w-40 text-center outline-none bg-transparent placeholder-gray-300" placeholder="0">
            </div>
        </div>

        <!-- Quick Amount Chips -->
        <div class="flex justify-center gap-3">
            <button wire:click="setAmount(500)" class="px-4 py-1.5 rounded-full border border-border text-sm font-medium hover:bg-primary-light hover:border-primary hover:text-primary transition-colors">₦500</button>
            <button wire:click="setAmount(1000)" class="px-4 py-1.5 rounded-full border border-border text-sm font-medium hover:bg-primary-light hover:border-primary hover:text-primary transition-colors">₦1K</button>
            <button wire:click="setAmount(5000)" class="px-4 py-1.5 rounded-full border border-border text-sm font-medium hover:bg-primary-light hover:border-primary hover:text-primary transition-colors">₦5K</button>
            <button wire:click="setAmount(10000)" class="px-4 py-1.5 rounded-full border border-border text-sm font-medium hover:bg-primary-light hover:border-primary hover:text-primary transition-colors">₦10K</button>
        </div>

        <!-- Fee & Total -->
        @if($amount > 0)
        <div class="bg-gray-50 rounded-card p-4 space-y-2 text-sm">
            <div class="flex justify-between text-text-secondary">
                <span>{{ __('Fee') }}</span>
                <span>₦{{ number_format($fee, 2) }}</span>
            </div>
            <div class="flex justify-between font-bold text-text-primary border-t border-border pt-2">
                <span>{{ __('Total Deducted') }}</span>
                <span>₦{{ number_format($total, 2) }}</span>
            </div>
        </div>
        @endif

        @if($validationMessage !== '' && $validationMessage !== 'Recipient not found.')
        <div class="rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">
            {{ $validationMessage }}
        </div>
        @endif

        <!-- Continue Button -->
        <div class="pt-6">
            <x-button variant="primary" size="large" wire:click="continueToConfirm" :disabled="!$canContinue">
                {{ __('Continue') }}
            </x-button>
        </div>
    </div>
    @endif

    <!-- Step 2: Confirm -->
    @if($step === 2)
    <div x-data x-transition:enter="transition ease-material duration-300 transform"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-material duration-300 transform absolute top-0 left-0 w-full"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 -translate-x-8"
         class="space-y-6">
        
        <div class="bg-surface rounded-card p-6 shadow-elevation-1 space-y-6 relative overflow-hidden">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
                    <x-lucide-user class="w-8 h-8" />
                </div>
                <h3 class="text-3xl font-bold text-text-primary leading-tight">{{ $recipientName }}</h3>
                <p class="text-sm font-medium text-text-secondary mt-2">{{ __('Confirm this is the right person before sending') }}</p>
                @if($recipientType === 'phone')
                    <p class="text-text-secondary text-sm mt-3">+234 {{ $phone }}</p>
                @else
                    @php $bank = collect($banks)->firstWhere('code', $selectedBankCode); @endphp
                    <p class="text-text-secondary text-sm mt-3">{{ $bank['name'] ?? '' }} - {{ $accountNumber }}</p>
                @endif
            </div>

            <div class="border-t border-b border-border py-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Amount') }}</span>
                    <span class="font-medium text-text-primary">₦{{ number_format((float)$amount, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Fee') }}</span>
                    <span class="font-medium text-text-primary">₦{{ number_format($fee, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-base pt-2">
                    <span class="text-text-primary">{{ __('Total to Pay') }}</span>
                    <span class="text-primary">₦{{ number_format($total, 2) }}</span>
                </div>
            </div>

            @if($validationMessage !== '')
            <div class="rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">
                {{ $validationMessage }}
            </div>
            @endif
        </div>

        <div class="space-y-3 pt-4">
            <x-button variant="primary" size="large" wire:click="continueToPinStep" :disabled="!$canAdvanceToPin" class="w-full relative">
                {{ __('Continue to PIN') }}
            </x-button>
            <x-button variant="secondary" size="large" wire:click="goBack" wire:loading.attr="disabled" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Back') }}
            </x-button>
        </div>
    </div>
    @endif

    <!-- Step 2.5: Transfer PIN -->
    @if($step === 25)
    <div x-data="{ countdown: {{ $pinLockSeconds }} }"
         x-init="if (countdown > 0) { const timer = setInterval(() => { if (countdown > 0) { countdown--; } else { clearInterval(timer); } }, 1000) }"
         x-on:focus-transfer-pin.window="$refs.pin1.focus()"
         x-transition:enter="transition ease-material duration-300 transform"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-material duration-300 transform absolute top-0 left-0 w-full"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 -translate-x-8"
         class="space-y-6">

        <div class="bg-surface rounded-card p-6 shadow-elevation-1 space-y-6 relative overflow-hidden">
            @if($isLoading)
            <div class="absolute inset-0 bg-surface/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center">
                <x-lucide-loader-2 class="w-8 h-8 text-primary animate-spin mb-4" />
                <p class="text-sm font-medium text-text-primary animate-pulse">{{ __('Processing transfer...') }}</p>
            </div>
            @endif

            <div class="text-center">
                <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
                    <x-lucide-lock class="w-8 h-8" />
                </div>
                <h3 class="text-2xl font-bold text-text-primary">{{ __('Enter Transfer PIN') }}</h3>
                <p class="text-text-secondary text-sm mt-2">{{ __('Confirm your 6-digit PIN before money moves.') }}</p>
            </div>

            <div class="bg-gray-50 rounded-card p-4 border border-border text-sm space-y-2">
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Recipient') }}</span>
                    <span class="font-semibold text-text-primary text-right">{{ $recipientName }}</span>
                </div>
                @if($recipientType === 'phone')
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Phone') }}</span>
                    <span class="font-medium text-text-primary">+234 {{ $phone }}</span>
                </div>
                @else
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Bank') }}</span>
                    <span class="font-medium text-text-primary">{{ collect($banks)->firstWhere('code', $selectedBankCode)['name'] ?? '' }} - {{ $accountNumber }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Total to Pay') }}</span>
                    <span class="font-bold text-primary">₦{{ number_format($total, 2) }}</span>
                </div>
            </div>

            <div class="flex gap-3 justify-center" x-data>
                @for ($i = 1; $i <= 6; $i++)
                    <input type="password"
                           wire:model.live="pin{{ $i }}"
                           x-ref="pin{{ $i }}"
                           @focus="$event.target.select()"
                           @input="
                               if ($event.target.value.length > 1) $event.target.value = $event.target.value.slice(-1);
                               if ($event.target.value && {{ $i }} < 6) $refs.pin{{ $i + 1 }}.focus();
                           "
                           @keyup.backspace="if (!$event.target.value && {{ $i }} > 1) $refs.pin{{ $i - 1 }}.focus()"
                           maxlength="1"
                           inputmode="numeric"
                           class="w-12 h-14 text-center text-2xl font-bold rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                @endfor
            </div>

            @if($pinError !== '')
            <div class="rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger text-center">
                {{ $pinError }}
                <div x-show="countdown > 0" class="mt-1 font-medium" style="display: none;">
                    {{ __('Try again in') }} <span x-text="countdown"></span>s
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-3 pt-4">
            <x-button variant="primary" size="large" wire:click="confirmTransferPin" wire:loading.attr="disabled" :disabled="$pinLength < 6 || $pinLockSeconds > 0" class="w-full relative">
                <span wire:loading.remove wire:target="confirmTransferPin">{{ __('Confirm & Send') }}</span>
                <span wire:loading wire:target="confirmTransferPin">{{ __('Sending...') }}</span>
            </x-button>
            <x-button variant="secondary" size="large" wire:click="goBack" wire:loading.attr="disabled" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Back') }}
            </x-button>
        </div>
    </div>
    @endif

    <!-- Step 3: Success -->
    @if($step === 3)
    <div x-data x-transition:enter="transition ease-material duration-300 transform delay-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="text-center space-y-6 pt-8">
        
        <!-- Animated checkmark -->
        <div class="mx-auto w-24 h-24 rounded-full {{ $resultState === 'failed' ? 'bg-red-100' : 'bg-primary-light' }} flex items-center justify-center">
            @if($resultState === 'failed')
                <x-lucide-x class="w-12 h-12 text-danger" />
            @else
                <svg class="w-12 h-12 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" 
                          stroke-dasharray="24" stroke-dashoffset="24"
                          x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)"
                          :class="show ? 'animate-[dash_0.5s_ease-out_forwards]' : ''" />
                </svg>
            @endif
        </div>

        <style>
            @keyframes dash {
                to { stroke-dashoffset: 0; }
            }
        </style>

        <div>
            <h2 class="text-2xl font-bold text-text-primary">{{ $resultState === 'failed' ? __('Transaction Failed') : __('Transaction Successful!') }}</h2>
            <p class="text-text-secondary mt-2">{{ $resultState === 'failed' ? $resultMessage : __("You've successfully sent money.") }}</p>
        </div>

        <div class="bg-gray-50 rounded-card p-4 space-y-3 text-sm text-left">
            <div class="flex justify-between border-b border-border pb-3">
                <span class="text-text-secondary">{{ __('Amount Sent') }}</span>
                <span class="font-bold text-text-primary">₦{{ number_format((float)$amount, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('Recipient') }}</span>
                <span class="font-medium text-text-primary text-right">{{ $recipientName }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('Date') }}</span>
                <span class="font-medium text-text-primary">{{ $date }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('Reference') }}</span>
                <span class="font-medium text-text-primary">{{ $reference !== '' ? $reference : __('N/A') }}</span>
            </div>
            @if($resultState !== 'failed')
                <div class="flex justify-between border-t border-border pt-3">
                    <span class="text-text-secondary">{{ __('New Balance') }}</span>
                    <span class="font-bold text-primary">₦{{ number_format($newBalance, 2) }}</span>
                </div>
            @endif
        </div>

        <div class="space-y-3 pt-6">
            @if($resultState === 'failed')
                <x-button variant="danger" size="large" wire:click="tryAgain" class="w-full">
                    {{ __('Try Again') }}
                </x-button>
                <x-button variant="secondary" size="large" wire:click="goBack" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                    {{ __('Edit Transfer') }}
                </x-button>
            @else
                <a href="{{ route('customer.dashboard') }}" wire:navigate class="inline-flex items-center justify-center w-full rounded-btn bg-primary text-white px-6 py-3.5 text-sm font-semibold transition-all hover:bg-primary-dark active:scale-[0.98]">
                    {{ __('Done') }}
                </a>
                <button disabled class="w-full rounded-btn bg-gray-100 text-text-secondary px-6 py-3.5 text-sm font-semibold cursor-not-allowed opacity-60">
                    {{ __('Share Receipt') }} ({{ __('Coming soon') }})
                </button>
            @endif
        </div>
    </div>
    @endif

</div>
