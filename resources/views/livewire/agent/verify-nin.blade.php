@php
    $kycCompletion = app(\App\Services\KycCompletionService::class);
@endphp

<div class="px-4 py-6 md:p-8 w-full max-w-2xl mx-auto space-y-6">
    <div class="mb-2">
        <h1 class="text-2xl font-bold text-text-primary">Verify Customer NIN</h1>
        <p class="text-text-secondary text-sm">Capture and verify a customer's National Identity Number.</p>
    </div>

    @if($step === 1)
        <div class="bg-surface rounded-xl shadow-card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-text-primary">Step 1: Find Customer</h2>
            <div>
                <label class="block text-sm font-medium text-text-secondary mb-1">Customer Phone Number</label>
                <input type="text" wire:model="searchPhone" placeholder="08012345678" class="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                @error('searchPhone') <p class="text-danger text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <button wire:click="searchCustomer" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-btn bg-primary px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50">
                <span wire:loading.remove wire:target="searchCustomer">Search</span>
                <span wire:loading wire:target="searchCustomer">Searching...</span>
            </button>
        </div>
    @endif

    @if($step >= 2 && $customer)
        <div class="bg-surface rounded-xl shadow-card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-text-primary">{{ $customer->full_name }}</h2>
                <span class="text-sm text-text-secondary">{{ $customer->phone_number }}</span>
            </div>

            <div class="rounded-lg bg-background border border-border p-4">
                <h3 class="text-xs font-semibold text-text-secondary uppercase tracking-wide mb-3">Verification Progress</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">NIN</span>
                        <span class="{{ filled($customer->nin_verified_at) ? 'text-green-600 dark:text-green-400 font-medium' : 'text-text-secondary' }}">
                            {{ filled($customer->nin_verified_at) ? '✓ Verified' : 'Not verified' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">BVN</span>
                        <span class="{{ filled($customer->bvn_verified_at) ? 'text-green-600 dark:text-green-400 font-medium' : 'text-text-secondary' }}">
                            {{ filled($customer->bvn_verified_at) ? '✓ Verified' : 'Not verified' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">Next of Kin</span>
                        <span class="{{ filled($customer->next_of_kin_submitted_at) ? 'text-green-600 dark:text-green-400 font-medium' : 'text-text-secondary' }}">
                            {{ filled($customer->next_of_kin_submitted_at) ? '✓ Submitted' : 'Not submitted' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($step === 2)
        <div class="bg-surface rounded-xl shadow-card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-text-primary">Step 2: Capture NIN</h2>

            @if(filled($customer->nin_verified_at))
                <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-sm text-green-700 dark:text-green-300">
                    This customer already has a verified NIN. You can continue to complete Tier 2 if the remaining requirements are met.
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-text-secondary mb-1">NIN (11 digits)</label>
                <input type="text" wire:model="nin" maxlength="11" placeholder="12345678901" class="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                @error('nin') <p class="text-danger text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-secondary mb-1">Full Name (must match account name)</label>
                <input type="text" wire:model="fullName" placeholder="Customer full name" class="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                @error('fullName') <p class="text-danger text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            @if($verificationStatus === 'failed')
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-sm text-red-700 dark:text-red-300">
                    {{ $verificationMessage }}
                </div>
            @endif

            <button wire:click="verifyNin" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-btn bg-primary px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50">
                <span wire:loading.remove wire:target="verifyNin">Verify NIN</span>
                <span wire:loading wire:target="verifyNin">Verifying...</span>
            </button>
        </div>
    @endif

    @if($step === 3)
        <div class="bg-surface rounded-xl shadow-card p-6 space-y-4">
            <h2 class="text-lg font-semibold text-text-primary">Step 3: Agent Confirmation</h2>

            <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 flex items-start gap-3">
                <x-lucide-check-circle class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                <p class="text-sm text-green-700 dark:text-green-300">{{ $verificationMessage }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-text-secondary mb-1">Enter Your Agent PIN</label>
                <input type="password" wire:model="agentPin" maxlength="6" placeholder="******" class="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                @error('agentPin') <p class="text-danger text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <button wire:click="verifyAgentPin" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-btn bg-primary px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50">
                <span wire:loading.remove wire:target="verifyAgentPin">Confirm & Save</span>
                <span wire:loading wire:target="verifyAgentPin">Saving...</span>
            </button>
        </div>
    @endif

    @if($step === 4)
        <div class="bg-surface rounded-xl shadow-card p-6 space-y-4 text-center">
            @if($tier2Completed)
                <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center mx-auto mb-3">
                    <x-lucide-party-popper class="w-8 h-8" />
                </div>
                <h2 class="text-xl font-bold text-text-primary">Tier 2 Completed!</h2>
                <p class="text-text-secondary text-sm">{{ $customer->full_name }} is now Tier 2. A Monnify reserved account has been provisioned and an SMS confirmation has been sent.</p>
            @else
                <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-3">
                    <x-lucide-check-circle class="w-8 h-8" />
                </div>
                <h2 class="text-xl font-bold text-text-primary">NIN Verified</h2>
                <p class="text-text-secondary text-sm">{{ $verificationMessage }} The customer still needs the following before reaching Tier 2:</p>
                <ul class="text-sm text-text-secondary space-y-1 mt-2">
                    @if(blank($customer->fresh()->bvn_verified_at)) <li>BVN verification</li> @endif
                    @if(blank($customer->fresh()->next_of_kin_submitted_at)) <li>Next of Kin details</li> @endif
                </ul>
            @endif

            <button wire:click="resetAndStart" class="inline-flex items-center justify-center rounded-btn bg-primary px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 mt-4">
                Verify Another Customer
            </button>
        </div>
    @endif
</div>
