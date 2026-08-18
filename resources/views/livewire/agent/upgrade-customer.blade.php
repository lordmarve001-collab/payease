<div class="px-4 py-6 md:p-8 max-w-lg mx-auto space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Upgrade Customer KYC') }}</h1>
            <p class="text-sm text-text-secondary">{{ __('Submit KYC upgrade on behalf of a customer') }}</p>
        </div>
        <button wire:click="resetAndStart" class="text-sm text-secondary hover:text-secondary/80 transition-colors">{{ __('Start Over') }}</button>
    </div>

    <!-- Step Indicator -->
    <div class="flex items-center gap-2">
        @foreach ([__('Find'), __('Tier'), __('Details'), 'PIN', __('Done')] as $i => $label)
            <div class="flex items-center gap-2">
                @if($i > 0)<div class="w-6 h-px bg-border {{ $step > $i ? 'bg-secondary' : '' }}"></div>@endif
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $step > $i + 1 ? 'bg-secondary text-white' : ($step === $i + 1 ? 'bg-secondary text-white ring-2 ring-secondary/30' : 'bg-surface text-text-secondary border border-border') }}">
                    {{ $i + 1 }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- Step 1: Customer Search -->
    @if($step === 1)
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 space-y-4">
            <h2 class="text-lg font-semibold text-text-primary">{{ __('Find Customer') }}</h2>
            <p class="text-sm text-text-secondary">{{ __('Enter the customer\'s phone number to look up their account.') }}</p>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Phone Number') }}</label>
                <input type="tel" wire:model.live="searchPhone" placeholder="08012345678" maxlength="14"
                    class="w-full px-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                @error('searchPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <button wire:click="searchCustomer" class="w-full py-2.5 bg-secondary hover:bg-secondary/90 text-white rounded-btn font-medium transition-all active:scale-[0.98]">
                {{ __('Search') }}
            </button>
        </div>
    @endif

    <!-- Step 2: Customer Info & Tier Selection -->
    @if($step === 2 && $customer)
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 space-y-4">
            <div class="flex items-center gap-4 pb-4 border-b border-border">
                <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center">
                    <span class="text-lg font-bold text-secondary">{{ substr($customer->full_name, 0, 2) }}</span>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-text-primary">{{ $customer->full_name }}</h2>
                    <p class="text-sm text-text-secondary">{{ __('Tier') }} {{ $customer->kyc_level }} · {{ $customer->phone_number }}</p>
                </div>
            </div>

            <div class="rounded-lg bg-background border border-border p-4">
                <h3 class="text-xs font-semibold text-text-secondary uppercase tracking-wide mb-3">{{ __('Verification Progress') }}</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">{{ __('NIN') }}</span>
                        <span class="{{ filled($customer->nin_verified_at) ? 'text-green-600 dark:text-green-400 font-medium' : 'text-text-secondary' }}">
                            {{ filled($customer->nin_verified_at) ? '✓ Verified' : __('Not verified') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">{{ __('BVN') }}</span>
                        <span class="{{ filled($customer->bvn_verified_at) ? 'text-green-600 dark:text-green-400 font-medium' : 'text-text-secondary' }}">
                            {{ filled($customer->bvn_verified_at) ? '✓ Verified' : __('Not verified') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">{{ __('Next of Kin') }}</span>
                        <span class="{{ filled($customer->next_of_kin_submitted_at) ? 'text-green-600 dark:text-green-400 font-medium' : 'text-text-secondary' }}">
                            {{ filled($customer->next_of_kin_submitted_at) ? '✓ Submitted' : __('Not submitted') }}
                        </span>
                    </div>
                </div>
            </div>

            <p class="text-sm text-text-secondary">{{ __('Select the target KYC tier:') }}</p>

            <div class="grid grid-cols-2 gap-3">
                @if((int)$customer->kyc_level < 2)
                    <button wire:click="selectTier(2)" class="p-4 rounded-card border-2 border-border hover:border-secondary hover:bg-secondary/5 transition-all text-left">
                        <h3 class="font-bold text-text-primary">{{ __('Tier 2') }}</h3>
                        <p class="text-xs text-text-secondary mt-1">{{ __('NIN + BVN + Next of Kin') }}</p>
                    </button>
                @endif
                @if((int)$customer->kyc_level < 3)
                    <button wire:click="selectTier(3)" class="p-4 rounded-card border-2 border-border hover:border-secondary hover:bg-secondary/5 transition-all text-left">
                        <h3 class="font-bold text-text-primary">{{ __('Tier 3') }}</h3>
                        <p class="text-xs text-text-secondary mt-1">{{ __('Address Proof') }}</p>
                    </button>
                @endif
            </div>

            @if((int)$customer->kyc_level >= 2 && (int)$customer->kyc_level < 3)
                <p class="text-xs text-amber-600 bg-amber-50 p-3 rounded-btn">{{ __('Customer is already Tier 2. Only Tier 3 upgrade is available.') }}</p>
            @endif
        </div>
    @endif

    <!-- Step 3: Tier Details -->
    @if($step === 3)
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 space-y-4">
            <h2 class="text-lg font-semibold text-text-primary">{{ __('Tier') }} {{ $targetTier }} {{ __('Details') }}</h2>

            @if($targetTier === 2)
                <div class="grid grid-cols-1 gap-4">
                    @if($this->ninAlreadyVerified())
                        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 text-sm text-green-700 dark:text-green-300">
                            ✓ {{ __('NIN already verified') }}
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('NIN (11 digits)') }}</label>
                            <input type="text" wire:model="nin" placeholder="12345678901" maxlength="11" inputmode="numeric"
                                class="w-full px-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                            @error('nin') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if($this->bvnAlreadyVerified())
                        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 text-sm text-green-700 dark:text-green-300">
                            ✓ {{ __('BVN already verified') }}
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('BVN (11 digits)') }}</label>
                            <input type="text" wire:model="bvn" placeholder="12345678901" maxlength="11" inputmode="numeric"
                                class="w-full px-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                            @error('bvn') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if($this->nextOfKinAlreadySubmitted())
                        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 text-sm text-green-700 dark:text-green-300">
                            ✓ {{ __('Next of Kin already submitted') }}
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Next of Kin Name') }}</label>
                            <input type="text" wire:model="nextOfKinName" placeholder="Jane Doe"
                                class="w-full px-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                            @error('nextOfKinName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Next of Kin Relationship') }}</label>
                            <input type="text" wire:model="nextOfKinRelationship" placeholder="Spouse"
                                class="w-full px-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                            @error('nextOfKinRelationship') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Next of Kin Phone') }}</label>
                            <input type="tel" wire:model="nextOfKinPhone" placeholder="08012345678" maxlength="14"
                                class="w-full px-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                            @error('nextOfKinPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('NIN Slip (photo)') }}</label>
                        <input type="file" wire:model="ninSlip" accept="image/*"
                            class="w-full text-sm text-text-secondary file:mr-3 file:py-1.5 file:px-3 file:rounded-btn file:border-0 file:text-sm file:bg-secondary/10 file:text-secondary hover:file:bg-secondary/20" />
                        @error('ninSlip') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('BVN Slip (photo)') }}</label>
                        <input type="file" wire:model="bvnSlip" accept="image/*"
                            class="w-full text-sm text-text-secondary file:mr-3 file:py-1.5 file:px-3 file:rounded-btn file:border-0 file:text-sm file:bg-secondary/10 file:text-secondary hover:file:bg-secondary/20" />
                        @error('bvnSlip') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Liveness Capture (photo)') }}</label>
                        <input type="file" wire:model="livenessCapture" accept="image/*"
                            class="w-full text-sm text-text-secondary file:mr-3 file:py-1.5 file:px-3 file:rounded-btn file:border-0 file:text-sm file:bg-secondary/10 file:text-secondary hover:file:bg-secondary/20" />
                        @error('livenessCapture') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Proof of Address (photo)') }}</label>
                        <input type="file" wire:model="proofOfAddress" accept="image/*"
                            class="w-full text-sm text-text-secondary file:mr-3 file:py-1.5 file:px-3 file:rounded-btn file:border-0 file:text-sm file:bg-secondary/10 file:text-secondary hover:file:bg-secondary/20" />
                        @error('proofOfAddress') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Address Indemnity Form (photo)') }}</label>
                        <input type="file" wire:model="addressIndemnityForm" accept="image/*"
                            class="w-full text-sm text-text-secondary file:mr-3 file:py-1.5 file:px-3 file:rounded-btn file:border-0 file:text-sm file:bg-secondary/10 file:text-secondary hover:file:bg-secondary/20" />
                        @error('addressIndemnityForm') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif

            <div class="flex gap-3">
                <button wire:click="goBackToTierSelection" class="flex-1 py-2.5 border border-border text-text-primary rounded-btn font-medium transition-all hover:bg-surface-alt">
                    {{ __('Back') }}
                </button>
                <button wire:click="submitTierDetails" class="flex-[2] py-2.5 bg-secondary hover:bg-secondary/90 text-white rounded-btn font-medium transition-all active:scale-[0.98]">
                    {{ __('Continue') }}
                </button>
            </div>
        </div>
    @endif

    <!-- Step 4: Customer PIN (Hand-off) -->
    @if($step === 4)
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 space-y-4 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-secondary/10 flex items-center justify-center mb-2">
                <x-lucide-shield-check class="w-8 h-8 text-secondary" />
            </div>
            <h2 class="text-lg font-semibold text-text-primary">{{ __('Customer PIN Confirmation') }}</h2>
            <p class="text-sm text-text-secondary">{{ __('Please hand the device to the customer to enter their 6-digit PIN to authorize this upgrade.') }}</p>
            <div>
                <input type="password" wire:model="customerPin" placeholder="••••••" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                    class="w-full text-center text-2xl tracking-[0.5em] px-4 py-3 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                @error('customerPin') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
            </div>
            <button wire:click="verifyCustomerPin" class="w-full py-2.5 bg-secondary hover:bg-secondary/90 text-white rounded-btn font-medium transition-all active:scale-[0.98]">
                {{ __('Confirm & Submit') }}
            </button>
        </div>
    @endif

    <!-- Step 5: Done -->
    @if($step === 5)
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 space-y-4 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-2">
                <x-lucide-check-circle class="w-8 h-8 text-green-600" />
            </div>
            <h2 class="text-lg font-semibold text-text-primary">{{ __('Upgrade Submitted') }}</h2>
            <p class="text-sm text-text-secondary">{{ __('Tier') }} {{ $targetTier }} {{ __('upgrade for') }} {{ $customer?->full_name }} {{ __('has been submitted for review.') }}</p>
            <button wire:click="resetAndStart" class="w-full py-2.5 bg-secondary hover:bg-secondary/90 text-white rounded-btn font-medium transition-all active:scale-[0.98]">
                {{ __('Upgrade Another Customer') }}
            </button>
        </div>
    @endif
</div>
