<div class="px-4 py-6 md:p-8 max-w-lg mx-auto relative overflow-hidden">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Buy Airtime') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Purchase airtime for yourself or someone else.') }}</p>
        @if($wallet)
            <p class="text-sm text-primary mt-2 font-medium">{{ __('Available Balance:') }} ₦{{ number_format($wallet->available_balance, 2) }}</p>
        @endif
    </div>

    {{-- Step: Select Network --}}
    @if($step === 'select')
    <div class="space-y-4">
        <p class="text-sm font-medium text-text-primary mb-2">{{ __('Select network provider:') }}</p>
        <div class="grid grid-cols-2 gap-3">
            @foreach($networks as $network)
                <button wire:click="selectNetwork('{{ $network }}')" class="rounded-2xl border border-border bg-surface p-5 text-center hover:border-primary hover:shadow-soft transition-all">
                    <div class="text-lg font-semibold text-text-primary">{{ $network }}</div>
                </button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Step: Enter Amount --}}
    @if($step === 'amount')
    <div class="space-y-6">
        <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
            <button wire:click="goBack" class="p-1 hover:text-primary">&larr; {{ __('Back') }}</button>
            <span>/</span>
            <span class="font-medium text-text-primary">{{ $selectedNetwork }}</span>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Phone Number') }}</label>
            <input type="tel" wire:model="phoneNumber" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="08012345678" maxlength="11">
        </div>

        <div class="pt-4 text-center">
            <label class="block text-sm font-medium text-text-secondary mb-2">{{ __('Amount') }}</label>
            <div class="flex items-center justify-center text-4xl font-bold text-text-primary">
                <span class="text-2xl mr-1 text-text-secondary">₦</span>
                <input type="text" wire:model.live="amount" class="w-40 text-center outline-none bg-transparent placeholder-gray-300" placeholder="0">
            </div>
        </div>

        <div class="flex justify-center gap-3">
            <button wire:click="setAmount(100)" class="px-4 py-1.5 rounded-full border border-border text-sm font-medium hover:bg-primary-light hover:border-primary hover:text-primary transition-colors">₦100</button>
            <button wire:click="setAmount(500)" class="px-4 py-1.5 rounded-full border border-border text-sm font-medium hover:bg-primary-light hover:border-primary hover:text-primary transition-colors">₦500</button>
            <button wire:click="setAmount(1000)" class="px-4 py-1.5 rounded-full border border-border text-sm font-medium hover:bg-primary-light hover:border-primary hover:text-primary transition-colors">₦1K</button>
            <button wire:click="setAmount(5000)" class="px-4 py-1.5 rounded-full border border-border text-sm font-medium hover:bg-primary-light hover:border-primary hover:text-primary transition-colors">₦5K</button>
        </div>

        @if($validationMessage !== '')
            <div class="rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">
                {{ $validationMessage }}
            </div>
        @endif

        <div class="pt-6">
                <x-button variant="primary" size="large" wire:click="goToPin" class="w-full">
                {{ __('Continue') }}
            </x-button>
        </div>
    </div>
    @endif

    {{-- Step: Confirm --}}
    @if($step === 'confirm')
    <div class="space-y-6">
        <div class="bg-surface rounded-card p-6 shadow-elevation-1 space-y-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
                    <x-lucide-phone class="w-8 h-8" />
                </div>
                <h3 class="text-xl font-bold text-text-primary">{{ $selectedNetwork }} Airtime</h3>
                <p class="text-sm text-text-secondary mt-2">{{ $phoneNumber }}</p>
            </div>

            <div class="border-t border-b border-border py-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Network') }}</span>
                    <span class="font-medium text-text-primary">{{ $selectedNetwork }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Phone') }}</span>
                    <span class="font-medium text-text-primary">{{ $phoneNumber }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Amount') }}</span>
                    <span class="font-bold text-primary">₦{{ number_format((float)$amount, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-3 pt-4">
            <x-button variant="primary" size="large" wire:click="goToPin" :disabled="$isProcessing" class="w-full">
                {{ __('Confirm & Pay') }} ₦{{ number_format((float)$amount, 2) }}
            </x-button>
            <x-button variant="secondary" size="large" wire:click="goBack" :disabled="$isProcessing" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Back') }}
            </x-button>
        </div>
    </div>
    @endif

    {{-- Step: PIN --}}
    @if($step === 'pin')
    <div class="space-y-6">
        <div class="text-center">
            <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
                <x-lucide-lock class="w-8 h-8" />
            </div>
            <h3 class="text-2xl font-bold text-text-primary">{{ __('Enter Transfer PIN') }}</h3>
            <p class="text-text-secondary text-sm mt-2">{{ __('Confirm your 6-digit PIN to authorize this airtime purchase.') }}</p>
        </div>

        <div class="flex gap-3 justify-center" x-data x-on:focus-airtime-pin.window="$nextTick(() => $refs.pin1?.focus())">
            @foreach(['pin1','pin2','pin3','pin4','pin5','pin6'] as $i => $pin)
            <input type="password"
                   wire:model.live="{{ $pin }}"
                   x-ref="pin{{ $i + 1 }}"
                   maxlength="1"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   @input="if ($event.target.value.length > 1) $event.target.value = $event.target.value.slice(-1); if ($event.target.value && {{ $i + 1 }} < 6) $refs.pin{{ $i + 2 }}?.focus();"
                   @keyup.backspace="if (!$event.target.value && {{ $i + 1 }} > 1) $refs.pin{{ $i }}?.focus();"
                   class="w-12 h-14 text-center text-2xl font-bold rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            @endforeach
        </div>

        @if($pinError)
        <div class="rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger text-center">
            {{ $pinError }}
        </div>
        @endif

        <div class="space-y-3 pt-4">
            <x-button variant="primary" size="large" wire:click="confirmPin" wire:loading.attr="disabled" :disabled="$isProcessing" class="w-full">
                <span wire:loading.remove wire:target="confirmPin">{{ __('Authorize Payment') }}</span>
                <span wire:loading wire:target="confirmPin">{{ __('Processing...') }}</span>
            </x-button>
            <x-button variant="secondary" size="large" wire:click="goBack" :disabled="$isProcessing" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Back') }}
            </x-button>
        </div>
    </div>
    @endif

    {{-- Step: Result --}}
    @if($step === 'result')
    <div class="text-center space-y-6 pt-8">
        <div class="mx-auto w-24 h-24 rounded-full {{ $resultState === 'failed' ? 'bg-red-100' : 'bg-primary-light' }} flex items-center justify-center">
            @if($resultState === 'failed')
                <x-lucide-x class="w-12 h-12 text-danger" />
            @else
                <svg class="w-12 h-12 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            @endif
        </div>

        <div>
            <h2 class="text-2xl font-bold text-text-primary">{{ $resultState === 'failed' ? __('Purchase Failed') : __('Purchase Successful!') }}</h2>
            <p class="text-text-secondary mt-2">{{ $resultMessage }}</p>
        </div>

        <div class="bg-gray-50 rounded-card p-4 space-y-3 text-sm text-left">
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('Network') }}</span>
                <span class="font-medium text-text-primary">{{ $selectedNetwork }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('Phone') }}</span>
                <span class="font-medium text-text-primary">{{ $phoneNumber }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('Amount') }}</span>
                <span class="font-bold text-primary">₦{{ number_format((float)$amount, 2) }}</span>
            </div>
        </div>

        <div class="space-y-3 pt-6">
            @if($resultState === 'failed')
                <x-button variant="primary" size="large" wire:click="goBack" class="w-full">
                    {{ __('Try Again') }}
                </x-button>
            @else
                <a href="{{ route('customer.dashboard') }}" wire:navigate class="inline-flex items-center justify-center w-full rounded-btn bg-primary text-white px-6 py-3.5 text-sm font-semibold transition-all hover:bg-primary-dark active:scale-[0.98]">
                    {{ __('Done') }}
                </a>
            @endif
            <x-button variant="secondary" size="large" wire:click="goBack" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Buy More') }}
            </x-button>
        </div>
    </div>
    @endif
</div>
