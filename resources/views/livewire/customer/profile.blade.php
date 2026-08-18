<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">
    
    <!-- Header: Avatar & KYC -->
    <div class="bg-surface rounded-card p-6 shadow-elevation-1 flex flex-col items-center text-center">
        <div class="w-24 h-24 rounded-full bg-secondary text-white flex items-center justify-center font-bold text-3xl mb-4 shadow-sm">
            {{ strtoupper(substr($user->full_name ?? '?', 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->full_name ?? ' ')[1] ?? '', 0, 1)) }}
        </div>
        <h2 class="text-2xl font-bold text-text-primary">{{ $user->full_name }}</h2>
        <p class="text-text-secondary mt-1">+234 {{ substr($user->phone_number, 1) }}</p>
        
        <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 {{ $user->kyc_level > 0 ? 'bg-primary-light text-primary' : 'bg-orange-100 text-orange-700' }} rounded-full text-sm font-medium">
            @if($user->kyc_level > 0)
                <x-lucide-check-circle class="w-4 h-4" />
                {{ __('Tier') }} {{ $user->kyc_level }} {{ __('Verified') }}
            @else
                <x-lucide-alert-circle class="w-4 h-4" />
                {{ __('Tier 0 Pending Verification') }}
            @endif
        </div>
    </div>

    <!-- Menu List -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <div class="divide-y divide-border">
            
            <a href="{{ route('customer.personal-info') }}" wire:navigate class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-user class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Personal Info') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-gray-400" />
            </a>

            <a href="{{ route('customer.transaction-limits') }}" wire:navigate class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-sliders class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Transaction Limits') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-gray-400" />
            </a>

            <button type="button" wire:click="toggleChangePinForm" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-lock class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Change PIN') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-gray-400" />
            </button>

            <a href="{{ route('customer.notifications') }}" wire:navigate class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-bell class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Notifications') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-gray-400" />
            </a>

            <a href="{{ route('customer.language') }}" wire:navigate class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-globe class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Language') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-gray-400" />
            </a>

            <a href="{{ route('public.become-ajo-owner') }}" wire:navigate class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary-light flex items-center justify-center text-primary">
                        <x-lucide-users-2 class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Become an Ajo Owner') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-gray-400" />
            </a>

            <a href="{{ route('customer.help-support') }}" wire:navigate class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-help-circle class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Help & Support') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-gray-400" />
            </a>
            
        </div>
    </div>

    @if($showChangePinForm)
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <form wire:submit="changePin" class="p-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Current PIN') }}</label>
                <input type="password" wire:model="currentPin" inputmode="numeric" maxlength="6" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary" placeholder="••••••">
                @error('currentPin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('New PIN') }}</label>
                <input type="password" wire:model="newPin" inputmode="numeric" maxlength="6" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary" placeholder="••••••">
                @error('newPin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Confirm New PIN') }}</label>
                <input type="password" wire:model="newPinConfirmation" inputmode="numeric" maxlength="6" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary" placeholder="••••••">
                @error('newPinConfirmation') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3 pt-2">
                <x-button type="button" variant="secondary" class="flex-1 bg-gray-100 text-text-primary hover:bg-gray-200" wire:click="toggleChangePinForm">{{ __('Cancel') }}</x-button>
                <x-button type="submit" variant="primary" class="flex-1">{{ __('Save PIN') }}</x-button>
            </div>
        </form>
    </div>
    @endif

    <!-- Logout -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <button wire:click="logout" class="w-full text-left flex items-center justify-between p-4 hover:bg-red-50 transition-colors active:bg-red-100">
            <div class="flex items-center gap-3 text-danger">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <x-lucide-log-out class="w-5 h-5" />
                </div>
                <span class="font-medium">{{ __('Log Out') }}</span>
            </div>
        </button>
    </div>

</div>
