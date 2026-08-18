<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">
    
    <!-- Header: Avatar & Business -->
    <div class="bg-surface rounded-card p-6 shadow-elevation-1 flex flex-col items-center text-center border-t-4 border-purple-600">
        <div class="w-24 h-24 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold text-3xl mb-4 shadow-sm border-4 border-surface ring-2 ring-purple-600/20">
            {{ strtoupper(substr($user->full_name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->full_name)[1] ?? '', 0, 1)) }}
        </div>
        <h2 class="text-2xl font-bold text-text-primary">{{ $user->full_name }}</h2>
        <p class="text-text-secondary mt-1 font-medium">{{ $ajoOwner?->business_name ?? __('Ajo Organizer') }}</p>
        <p class="text-text-secondary text-sm mt-0.5">+234 {{ substr($user->phone_number, 1) }}</p>
        
        <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-bold uppercase tracking-wider">
            {{ __('Verified Partner') }}
        </div>
    </div>

    <!-- Menu List -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <div class="divide-y divide-border">
            
            <a href="{{ route('ajo-owner.profile.business-info') }}" wire:navigate class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-text-secondary">
                        <x-lucide-building class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Business Info') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-text-secondary" />
            </a>

            <a href="{{ route('ajo-owner.profile.payout-settings') }}" wire:navigate class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-text-secondary">
                        <x-lucide-settings class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Payout Settings') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-text-secondary" />
            </a>

            <a href="{{ route('ajo-owner.profile.notifications') }}" wire:navigate class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-text-secondary">
                        <x-lucide-bell class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Notifications') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-text-secondary" />
            </a>

            <a href="{{ route('ajo-owner.profile.help-support') }}" wire:navigate class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-text-secondary">
                        <x-lucide-headphones class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Help & Support') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-text-secondary" />
            </a>
            
        </div>
    </div>

    <!-- Reset PIN Section -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <div class="p-4 border-b border-border">
            <h3 class="font-bold text-text-primary flex items-center gap-2">
                <x-lucide-key-round class="w-5 h-5 text-purple-600" />
                {{ __('Security PIN') }}
            </h3>
        </div>

        <!-- Login PIN (6-digit) -->
        <div class="border-b border-border">
            <button wire:click="toggleChangeLoginPin" class="w-full text-left flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <x-lucide-fingerprint class="w-5 h-5 text-blue-600" />
                    </div>
                    <div>
                        <span class="font-medium text-text-primary block">{{ __('Login PIN') }}</span>
                        <span class="text-xs text-text-secondary">6 digits &middot; Used to sign in</span>
                    </div>
                </div>
                <x-lucide-chevron-down class="w-5 h-5 text-text-secondary transition-transform {{ $showChangeLoginPin ? 'rotate-180' : '' }}" />
            </button>

            @if($showChangeLoginPin)
            <div class="px-4 pb-4 space-y-3">
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1.5">{{ __('Current Login PIN') }}</label>
                    <input type="password" wire:model="currentLoginPin" inputmode="numeric" maxlength="6"
                           class="w-full rounded-xl border border-border bg-background text-text-primary px-4 py-3 text-center text-lg tracking-[0.3em] outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 transition-all"
                           placeholder="------">
                    @error('currentLoginPin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1.5">{{ __('New Login PIN') }}</label>
                    <input type="password" wire:model="newLoginPin" inputmode="numeric" maxlength="6"
                           class="w-full rounded-xl border border-border bg-background text-text-primary px-4 py-3 text-center text-lg tracking-[0.3em] outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 transition-all"
                           placeholder="------">
                    @error('newLoginPin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1.5">{{ __('Confirm New PIN') }}</label>
                    <input type="password" wire:model="newLoginPinConfirm" inputmode="numeric" maxlength="6"
                           class="w-full rounded-xl border border-border bg-background text-text-primary px-4 py-3 text-center text-lg tracking-[0.3em] outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 transition-all"
                           placeholder="------">
                    @error('newLoginPinConfirm') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button wire:click="toggleChangeLoginPin" class="px-5 py-2.5 rounded-xl border border-border text-text-secondary font-medium hover:bg-gray-50 transition-colors text-sm">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="changeLoginPin" wire:loading.attr="disabled" wire:target="changeLoginPin"
                            class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-xl font-semibold transition-all active:scale-[0.98] text-sm disabled:opacity-50">
                        <span wire:loading.remove wire:target="changeLoginPin">{{ __('Save Login PIN') }}</span>
                        <span wire:loading wire:target="changeLoginPin" class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('Saving...') }}
                        </span>
                    </button>
                </div>
            </div>
            @endif
        </div>

        <!-- Transfer PIN (6-digit) -->
        <div>
            <button wire:click="toggleChangeTransferPin" class="w-full text-left flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <x-lucide-shield class="w-5 h-5 text-amber-600" />
                    </div>
                    <div>
                        <span class="font-medium text-text-primary block">{{ __('Transfer PIN') }}</span>
                        <span class="text-xs text-text-secondary">6 digits &middot; Used for transactions</span>
                    </div>
                </div>
                <x-lucide-chevron-down class="w-5 h-5 text-text-secondary transition-transform {{ $showChangeTransferPin ? 'rotate-180' : '' }}" />
            </button>

            @if($showChangeTransferPin)
            <div class="px-4 pb-4 space-y-3">
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1.5">{{ __('Current Transfer PIN') }}</label>
                    <input type="password" wire:model="currentTransferPin" inputmode="numeric" maxlength="6"
                           class="w-full rounded-xl border border-border bg-background text-text-primary px-4 py-3 text-center text-lg tracking-[0.5em] outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 transition-all"
                           placeholder="------">
                    @error('currentTransferPin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1.5">{{ __('New Transfer PIN') }}</label>
                    <input type="password" wire:model="newTransferPin" inputmode="numeric" maxlength="6"
                           class="w-full rounded-xl border border-border bg-background text-text-primary px-4 py-3 text-center text-lg tracking-[0.5em] outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 transition-all"
                           placeholder="------">
                    @error('newTransferPin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1.5">{{ __('Confirm New PIN') }}</label>
                    <input type="password" wire:model="newTransferPinConfirm" inputmode="numeric" maxlength="6"
                           class="w-full rounded-xl border border-border bg-background text-text-primary px-4 py-3 text-center text-lg tracking-[0.5em] outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 transition-all"
                           placeholder="------">
                    @error('newTransferPinConfirm') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button wire:click="toggleChangeTransferPin" class="px-5 py-2.5 rounded-xl border border-border text-text-secondary font-medium hover:bg-gray-50 transition-colors text-sm">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="changeTransferPin" wire:loading.attr="disabled" wire:target="changeTransferPin"
                            class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-xl font-semibold transition-all active:scale-[0.98] text-sm disabled:opacity-50">
                        <span wire:loading.remove wire:target="changeTransferPin">{{ __('Save Transfer PIN') }}</span>
                        <span wire:loading wire:target="changeTransferPin" class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('Saving...') }}
                        </span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Logout -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <button wire:click="logout" class="w-full text-left flex items-center justify-between p-4 hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors active:bg-red-100">
            <div class="flex items-center gap-4 text-danger">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <x-lucide-log-out class="w-5 h-5" />
                </div>
                <span class="font-bold">{{ __('Log Out Owner') }}</span>
            </div>
        </button>
    </div>

</div>
