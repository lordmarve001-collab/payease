<div class="px-4 py-6 md:p-8 max-w-lg mx-auto relative overflow-hidden">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Add Member') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Create or find a user and add them to an Ajo group.') }}</p>
    </div>

    @if(!$agent)
        <div class="text-center py-12">
            <x-lucide-alert-circle class="w-12 h-12 text-text-secondary mx-auto mb-4" />
            <p class="text-text-secondary">No agent profile found.</p>
        </div>
    @else

    <!-- Progress -->
    <div class="flex items-center gap-1 sm:gap-2 mb-6">
        @foreach(['Phone', 'Account', 'OTP', 'Group', 'Confirm'] as $i => $label)
            @php $stepNum = $i + 1; @endphp
            <div class="flex-1 flex items-center gap-1 sm:gap-2">
                <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold shrink-0
                    {{ $step > $stepNum ? 'bg-emerald-600 text-white' : ($step == $stepNum ? 'bg-emerald-600 text-white ring-2 ring-emerald-200' : 'bg-gray-200 dark:bg-gray-700 text-text-secondary') }}">
                    @if($step > $stepNum)
                        <x-lucide-check class="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                    @else
                        {{ $stepNum }}
                    @endif
                </div>
                @if($i < 4)
                    <div class="flex-1 h-0.5 {{ $step > $stepNum ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Step 1: Enter Phone -->
    @if($step === 1)
    <div class="space-y-4">
        <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide">{{ __('Enter Phone Number') }}</h2>
        <p class="text-xs text-text-secondary">{{ __('Enter the phone number of the person you want to add. If they don\'t have an account, we\'ll create one.') }}</p>

        <div>
            <input type="tel" wire:model="phone" wire:enter="lookupPhone"
                   class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary text-lg font-mono tracking-wider text-center outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                   placeholder="08012345678" maxlength="11">
            @error('phone') <p class="text-sm text-danger mt-1 text-center">{{ $message }}</p> @enderror
        </div>

        <button wire:click="lookupPhone" wire:loading.attr="disabled" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 disabled:opacity-50">
            <span wire:loading.remove wire:target="lookupPhone">{{ __('Continue') }}</span>
            <span wire:loading wire:target="lookupPhone" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ __('Looking up...') }}
            </span>
        </button>
    </div>
    @endif

    <!-- Step 2: Create Account -->
    @if($step === 2)
    <div class="space-y-4">
        <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
            <button wire:click="goBack" class="p-1 hover:text-emerald-600 transition-colors">&larr; {{ __('Back') }}</button>
            <span>/</span>
            <span class="font-medium text-text-primary">{{ __('New Account') }}</span>
        </div>

        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/30 rounded-card p-3 flex items-center gap-2">
            <x-lucide-user-plus class="w-4 h-4 text-emerald-600 shrink-0" />
            <p class="text-xs text-emerald-700 dark:text-emerald-300">No account found for <strong>{{ $phone }}</strong>. Create one below.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Full Name') }}</label>
            <input type="text" wire:model="fullName"
                   class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                   placeholder="e.g. Adebayo Johnson">
            @error('fullName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Login PIN') }}</label>
                <input type="password" wire:model="pin" maxlength="6" inputmode="numeric"
                       class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary text-center font-mono text-lg tracking-widest outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                       placeholder="******">
                @error('pin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Confirm PIN') }}</label>
                <input type="password" wire:model="pinConfirmation" maxlength="6" inputmode="numeric"
                       class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary text-center font-mono text-lg tracking-widest outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                       placeholder="******">
                @error('pinConfirmation') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-[11px] text-text-secondary text-center">The member will use this PIN to log in. Send it to them securely.</p>

        <button wire:click="createUser" wire:loading.attr="disabled" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 disabled:opacity-50">
            <span wire:loading.remove wire:target="createUser">{{ __('Create Account & Continue') }}</span>
            <span wire:loading wire:target="createUser" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ __('Creating...') }}
            </span>
        </button>
    </div>
    @endif

    <!-- Step 3: OTP Verification -->
    @if($step === 3)
    <div class="space-y-4">
        <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
            <button wire:click="goBack" class="p-1 hover:text-emerald-600 transition-colors">&larr; {{ __('Back') }}</button>
            <span>/</span>
            <span class="font-medium text-text-primary">{{ __('Verify OTP') }}</span>
        </div>

        <div class="text-center space-y-2">
            <div class="w-14 h-14 bg-emerald-100 rounded-full flex items-center justify-center mx-auto">
                <x-lucide-shield-check class="w-7 h-7 text-emerald-600" />
            </div>
            <h2 class="text-lg font-bold text-text-primary">{{ __('OTP Verification') }}</h2>
            <p class="text-xs text-text-secondary">
                We sent a 6-digit code to <span class="font-semibold text-text-primary">{{ $foundUser?->phone_number }}</span>.
                Enter it below to verify this user before adding them to a group.
            </p>
        </div>

        @if($otpResendMessage)
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/30 rounded-card p-3 text-center">
                <p class="text-xs text-emerald-700 dark:text-emerald-300">{{ $otpResendMessage }}</p>
            </div>
        @endif

        <div>
            <input type="text" wire:model="otpCode" inputmode="numeric" maxlength="6"
                   class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary text-center font-mono text-2xl tracking-[0.5em] outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                   placeholder="000000" autocomplete="one-time-code">
            @error('otpCode') <p class="text-sm text-danger mt-1 text-center">{{ $message }}</p> @enderror
        </div>

        <button wire:click="verifyOtp" wire:loading.attr="disabled" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 disabled:opacity-50">
            <span wire:loading.remove wire:target="verifyOtp">{{ __('Verify & Continue') }}</span>
            <span wire:loading wire:target="verifyOtp" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ __('Verifying...') }}
            </span>
        </button>

        <div class="text-center" x-data="{ cooldown: @entangle('otpCooldown') }" x-init="
            if (cooldown > 0) {
                let timer = setInterval(() => {
                    cooldown--;
                    $wire.set('otpCooldown', cooldown);
                    if (cooldown <= 0) { clearInterval(timer); }
                }, 1000);
            }
        ">
            <button wire:click="resendOtp" wire:loading.attr="disabled" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed" :disabled="cooldown > 0">
                <span x-show="cooldown > 0" x-cloak>Resend OTP in <span x-text="cooldown"></span>s</span>
                <span x-show="cooldown <= 0">Resend OTP</span>
            </button>
        </div>
    </div>
    @endif

    <!-- Step 4: Select Group -->
    @if($step === 4)
    <div class="space-y-4">
        <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
            <button wire:click="goBack" class="p-1 hover:text-emerald-600 transition-colors">&larr; {{ __('Back') }}</button>
            <span>/</span>
            <span class="font-medium text-text-primary">{{ __('Select Group') }}</span>
        </div>

        <!-- User Info Card -->
        <div class="bg-surface rounded-card border border-border p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm shrink-0">
                {{ strtoupper(substr($foundUser?->full_name ?? '?', 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-text-primary text-sm">{{ $foundUser?->full_name }}</p>
                <p class="text-xs text-text-secondary font-mono">{{ $foundUser?->phone_number }}</p>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 flex items-center gap-1">
                <x-lucide-shield-check class="w-3 h-3" /> Verified
            </span>
        </div>

        <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide">{{ __('Select a Group') }}</h2>

        @if($assignedGroups->isEmpty())
            <div class="bg-surface rounded-card border border-border p-8 text-center">
                <x-lucide-users class="w-12 h-12 text-text-secondary mx-auto mb-3" />
                <p class="text-text-secondary font-medium">{{ __('No active groups assigned.') }}</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($assignedGroups as $group)
                    @php
                        $currentMembers = $group->members->count();
                        $targetMembers = (int) $group->members_count;
                    @endphp
                    <button wire:click="selectGroup('{{ $group->id }}')" class="w-full bg-surface rounded-card border border-border p-4 text-left hover:shadow-elevation-1 hover:border-emerald-300 transition-all active:scale-[0.98]">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-text-primary text-sm">{{ $group->name }}</h3>
                                    @if($group->managing_agent_id === $agent->id)
                                        <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">Primary</span>
                                    @endif
                                </div>
                                <p class="text-xs text-text-secondary mt-0.5">
                                    {{ match($group->model_type) { 'savings_pool' => 'Savings Pool', 'continuous_pool' => 'Continuous Pool', default => 'Rotation' } }}
                                    &middot; {{ ucfirst($group->frequency) }}
                                    @if($group->model_type === 'rotational')
                                        &middot; ₦{{ number_format($group->contribution_amount, 0) }}/cycle
                                    @endif
                                </p>
                            </div>
                            <x-lucide-chevron-right class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
                        </div>
                        <p class="text-[11px] text-text-secondary mt-2">{{ $currentMembers }}/{{ $targetMembers }} {{ __('members') }}</p>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    <!-- Step 5: Confirm -->
    @if($step === 5)
    <div class="space-y-4">
        <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
            <button wire:click="goBack" class="p-1 hover:text-emerald-600 transition-colors">&larr; {{ __('Back') }}</button>
            <span>/</span>
            <span class="font-medium text-text-primary">{{ __('Confirm') }}</span>
        </div>

        <div class="bg-surface rounded-card border border-border p-5 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg shrink-0">
                    {{ strtoupper(substr($foundUser?->full_name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <p class="font-bold text-text-primary">{{ $foundUser?->full_name }}</p>
                    <p class="text-sm text-text-secondary font-mono">{{ $foundUser?->phone_number }}</p>
                </div>
            </div>

            <div class="border-t border-border pt-3">
                <p class="text-xs text-text-secondary uppercase tracking-wide mb-1">{{ __('Adding to Group') }}</p>
                <p class="font-bold text-text-primary">{{ $selectedGroup?->name }}</p>
                <p class="text-xs text-text-secondary">
                    {{ match($selectedGroup?->model_type) { 'savings_pool' => 'Savings Pool', 'continuous_pool' => 'Continuous Pool', default => 'Rotation' } }}
                    &middot; {{ ucfirst($selectedGroup?->frequency) }}
                    @if($selectedGroup?->model_type === 'rotational')
                        &middot; ₦{{ number_format($selectedGroup->contribution_amount, 0) }}/cycle
                    @endif
                </p>
            </div>
        </div>

        <button wire:click="confirmAdd" wire:loading.attr="disabled" wire:target="confirmAdd" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 disabled:opacity-50">
            <span wire:loading.remove wire:target="confirmAdd">{{ __('Confirm & Add Member') }}</span>
            <span wire:loading wire:target="confirmAdd" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ __('Adding...') }}
            </span>
        </button>
    </div>
    @endif

    <!-- Step 6: Result -->
    @if($step === 6)
    <div class="text-center space-y-6 py-4">
        @if($resultState === 'success')
            <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto">
                <x-lucide-check class="w-10 h-10 text-emerald-600" />
            </div>
            <div>
                <h2 class="text-xl font-bold text-text-primary">{{ __('Done!') }}</h2>
                <p class="text-text-secondary mt-1">{{ $resultMessage }}</p>
            </div>
            <div class="bg-surface rounded-card border border-border p-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-text-secondary">{{ __('Member') }}</span>
                    <span class="font-medium text-text-primary">{{ $foundUser?->full_name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-text-secondary">{{ __('Phone') }}</span>
                    <span class="font-medium text-text-primary font-mono">{{ $foundUser?->phone_number }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-text-secondary">{{ __('Group') }}</span>
                    <span class="font-medium text-text-primary">{{ $selectedGroup?->name }}</span>
                </div>
            </div>
        @else
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                <x-lucide-x class="w-10 h-10 text-red-600" />
            </div>
            <div>
                <h2 class="text-xl font-bold text-text-primary">{{ __('Failed') }}</h2>
                <p class="text-danger mt-1 text-sm">{{ $resultMessage }}</p>
            </div>
        @endif

        <button wire:click="resetForm" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98]">
            {{ __('Add Another Member') }}
        </button>
    </div>
    @endif

    @endif
</div>
