<div class="px-4 py-6 md:p-8 max-w-lg mx-auto relative overflow-hidden">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Cash Out (Withdrawal)') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Process a customer withdrawal.') }}</p>
    </div>

    @if($step === 1)
    <div x-data x-transition:enter="transition ease-material duration-300 transform"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-material duration-300 transform absolute top-0 left-0 w-full"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 -translate-x-8"
         class="space-y-6">

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Phone Number') }}</label>
            <div class="flex">
                <span class="inline-flex items-center px-4 rounded-l-btn border border-r-0 border-border bg-gray-50 text-text-secondary sm:text-sm">
                    +234
                </span>
                <input type="tel" wire:model.live="phone" class="flex-1 block w-full min-w-0 rounded-none rounded-r-btn sm:text-sm border-border focus:ring-secondary focus:border-secondary px-4 py-3 border outline-none" placeholder="801 234 5678">
            </div>
            @error('phone') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
        </div>

        <div class="pt-4">
            <x-button variant="primary" size="large" wire:click="lookupCustomer" wire:loading.attr="disabled" x-bind:disabled="@js(strlen($phone) < 10)" class="w-full relative bg-secondary hover:bg-secondary/90">
                <span wire:loading.remove wire:target="lookupCustomer">{{ __('Lookup Customer') }}</span>
                <span wire:loading wire:target="lookupCustomer" class="flex items-center justify-center gap-2">
                    <x-lucide-loader-2 class="w-5 h-5 animate-spin" />
                    Searching...
                </span>
            </x-button>
        </div>
    </div>
    @endif

    @if($step === 2)
    <div x-data x-transition:enter="transition ease-material duration-300 transform"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-material duration-300 transform absolute top-0 left-0 w-full"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 -translate-x-8"
         class="space-y-6">

        <div class="bg-surface rounded-card p-4 shadow-sm border border-border flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-secondary/10 text-secondary rounded-full flex items-center justify-center">
                    <x-lucide-user class="w-5 h-5" />
                </div>
                <div>
                    <h4 class="font-bold text-text-primary text-sm">{{ $customerName }}</h4>
                    <p class="text-xs text-text-secondary">+234 {{ $phone }}</p>
                </div>
            </div>

            <div class="text-right">
                @if($kycTier === 2)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-primary-light text-primary uppercase tracking-wider">
                        <x-lucide-check-circle class="w-3 h-3" /> Tier 2
                    </span>
                @elseif($kycTier === 1)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-700 uppercase tracking-wider">
                        Tier 1
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-danger uppercase tracking-wider">
                        Tier 0
                    </span>
                @endif
            </div>
        </div>

        <div class="bg-gray-50 rounded-card p-3 border border-border flex items-center justify-between text-sm">
            <span class="text-text-secondary">{{ __('Available Balance') }}</span>
            <span class="font-semibold text-text-primary">₦{{ number_format($customerBalance, 2) }}</span>
        </div>

        @if($validationCode === 'insufficient_float' && $validationMessage !== '')
        <div class="rounded-card border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
            <div class="font-semibold mb-1">{{ __('Insufficient Float') }}</div>
            <div>{{ $validationMessage }}</div>
        </div>
        @elseif($validationMessage !== '')
        <div class="rounded-card border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
            {{ $validationMessage }}
        </div>
        @endif

        <div class="pt-2 text-center">
            <label class="block text-sm font-medium text-text-secondary mb-2">{{ __('Withdrawal Amount') }}</label>
            <div class="flex items-center justify-center text-4xl font-bold text-text-primary">
                <span class="text-2xl mr-1 text-text-secondary">₦</span>
                <input type="text" wire:model.live="amount" class="w-40 text-center outline-none bg-transparent placeholder-gray-300" placeholder="0">
            </div>
        </div>

        <div class="flex justify-center gap-2 flex-wrap">
            <button wire:click="setAmount(1000)" class="px-4 py-2 rounded-full border border-border text-sm font-medium hover:bg-secondary/10 hover:border-secondary hover:text-secondary transition-colors">₦1K</button>
            <button wire:click="setAmount(5000)" class="px-4 py-2 rounded-full border border-border text-sm font-medium hover:bg-secondary/10 hover:border-secondary hover:text-secondary transition-colors">₦5K</button>
            <button wire:click="setAmount(10000)" class="px-4 py-2 rounded-full border border-border text-sm font-medium hover:bg-secondary/10 hover:border-secondary hover:text-secondary transition-colors">₦10K</button>
            <button wire:click="setAmount(20000)" class="px-4 py-2 rounded-full border border-border text-sm font-medium hover:bg-secondary/10 hover:border-secondary hover:text-secondary transition-colors">₦20K</button>
        </div>

        @if($amount > 0)
        <div class="bg-secondary/10 border border-secondary/20 rounded-card p-4 text-center">
            <p class="text-secondary/80 text-sm font-medium mb-1">{{ __('Your Commission') }}</p>
            <p class="text-2xl font-bold text-secondary">₦{{ number_format($commission, 2) }}</p>
        </div>
        @endif

        <div class="space-y-3 pt-4">
            <x-button variant="primary" size="large" wire:click="continueToPin" wire:loading.attr="disabled" x-bind:disabled="@js(!$canProceed)" class="w-full bg-secondary hover:bg-secondary/90">
                {{ __('Continue') }}
            </x-button>
            <x-button variant="secondary" size="large" wire:click="goBack" wire:loading.attr="disabled" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Cancel') }}
            </x-button>
        </div>
    </div>
    @endif

    @if($step === 3)
    <div x-data x-transition:enter="transition ease-material duration-300 transform"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         class="space-y-6">

        <div class="bg-surface rounded-card p-4 shadow-sm border border-border space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-text-secondary">Customer</span>
                <span class="font-medium text-text-primary">{{ $customerName }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-text-secondary">Withdrawal Amount</span>
                <span class="font-bold text-text-primary">₦{{ number_format((float) $amount, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-text-secondary">Commission</span>
                <span class="font-bold text-secondary">₦{{ number_format($commission, 2) }}</span>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Enter Agent PIN') }}</label>
            <input type="password" wire:model.live="agentPin" inputmode="numeric" maxlength="6" class="block w-full rounded-btn border border-border px-4 py-3 outline-none focus:border-secondary focus:ring-secondary" placeholder="••••••">
            @error('agentPin') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-3 pt-4 relative">
            @if($isLoading)
            <div class="absolute inset-0 bg-background/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center rounded-lg">
                <x-lucide-loader-2 class="w-8 h-8 text-secondary animate-spin" />
            </div>
            @endif

            <x-button variant="primary" size="large" wire:click="confirmWithdrawal" wire:loading.attr="disabled" x-bind:disabled="@js(strlen($agentPin) !== 6)" class="w-full bg-secondary hover:bg-secondary/90">
                {{ __('Confirm Withdrawal') }}
            </x-button>
            <x-button variant="secondary" size="large" wire:click="goBack" wire:loading.attr="disabled" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Back') }}
            </x-button>
        </div>
    </div>
    @endif

    @if($step === 4)
    <div x-data x-transition:enter="transition ease-material duration-300 transform delay-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="text-center space-y-6 pt-8">

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
            @keyframes fade-in {
                from { opacity: 0; transform: translateY(5px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>

        <div>
            <h2 class="text-2xl font-bold text-text-primary">{{ $resultState === 'failed' ? __('Withdrawal Failed') : __('Withdrawal Successful!') }}</h2>
            <p class="text-text-secondary mt-1 text-sm">
                {{ $resultState === 'failed' ? $resultMessage : __('Please hand cash to customer.') }}
            </p>
        </div>

        <div class="bg-surface shadow-sm border border-border rounded-card p-4 space-y-3 text-sm text-left">
            <div class="flex justify-between border-b border-border pb-3">
                <span class="text-text-secondary">{{ __('Amount Withdrawn') }}</span>
                <span class="font-bold text-text-primary">₦{{ number_format((float) $amount, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('Customer') }}</span>
                <span class="font-medium text-text-primary">{{ $customerName }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('Reference') }}</span>
                <span class="font-medium text-text-primary">{{ $reference }}</span>
            </div>
            <div class="flex justify-between border-t border-border pt-3">
                <span class="text-text-secondary">{{ __('Customer New Balance') }}</span>
                <span class="font-bold text-text-primary">{{ $resultState === 'failed' ? __('Unavailable') : '₦' . number_format($newCustomerBalance, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">{{ __('New Float Balance') }}</span>
                <span class="font-bold text-text-primary">{{ $resultState === 'failed' ? __('Unavailable') : '₦' . number_format($newFloatBalance, 2) }}</span>
            </div>
        </div>

        <div class="{{ $resultState === 'failed' ? 'bg-red-50 border-red-200' : 'bg-secondary/10 border-secondary/20' }} border rounded-card p-3 flex justify-between items-center">
            <span class="{{ $resultState === 'failed' ? 'text-danger' : 'text-secondary' }} font-medium">
                {{ $resultState === 'failed' ? __('Transaction Status') : __('Commission Earned') }}
            </span>
            <span class="text-lg font-bold {{ $resultState === 'failed' ? 'text-danger' : 'text-secondary' }}">
                {{ $resultState === 'failed' ? __('Failed') : '+₦' . number_format($commission, 2) }}
            </span>
        </div>

        <div class="space-y-3 pt-4">
            @if($resultState === 'failed')
                <x-button variant="primary" size="large" wire:click="tryAgain" class="w-full bg-secondary hover:bg-secondary/90">
                    {{ __('Try Again') }}
                </x-button>
            @else
                <x-button variant="primary" size="large" wire:click="goBack" class="w-full bg-secondary hover:bg-secondary/90">
                    {{ __('New Transaction') }}
                </x-button>
            @endif
            <a href="{{ route('agent.dashboard') }}" wire:navigate
               class="block w-full py-3 bg-gray-100 text-text-primary hover:bg-gray-200 rounded-btn font-medium transition-colors text-center">
                {{ __('Done') }}
            </a>
        </div>
    </div>
    @endif
</div>
