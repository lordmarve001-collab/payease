<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">
    @php
        $status = strtolower((string) ($agent?->status ?? 'inactive'));
        $statusClasses = match ($status) {
            'active' => 'bg-primary-light text-primary',
            'pending' => 'bg-orange-100 text-orange-700',
            default => 'bg-red-100 text-danger',
        };
    @endphp

    <div class="bg-surface rounded-card p-6 shadow-elevation-1 flex flex-col items-center text-center border-t-4 border-accent">
        <div class="w-24 h-24 rounded-full bg-accent text-white flex items-center justify-center font-bold text-3xl mb-4 shadow-sm border-4 border-surface ring-2 ring-accent/20">
            {{ strtoupper(substr($user->full_name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->full_name)[1] ?? '', 0, 1)) }}
        </div>
        <h2 class="text-2xl font-bold text-text-primary">{{ $agent?->business_name ?? $user->full_name }}</h2>
        <p class="text-text-secondary mt-1 font-medium">{{ $user->full_name }}</p>
        <p class="text-text-secondary text-sm mt-0.5">+234 {{ substr($user->phone_number, 1) }}</p>
        
        <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold uppercase tracking-wider {{ $statusClasses }}">
            <span class="w-2 h-2 rounded-full {{ $status === 'active' ? 'bg-primary animate-pulse' : ($status === 'pending' ? 'bg-orange-600' : 'bg-danger') }}"></span>
            {{ str_replace('_', ' ', $status) }} Agent
        </div>
    </div>

    <!-- Menu List -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <div class="divide-y divide-border">
            
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-store class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="font-medium text-text-primary block">{{ __('Business Info') }}</span>
                        <span class="text-sm text-text-secondary">{{ $agent?->business_name ?? __('Not set') }}</span>
                    </div>
                </div>
                <span class="text-sm text-text-secondary">{{ $agent?->business_address ?? 'No address' }}</span>
            </div>

            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-wallet class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="font-medium text-text-primary block">{{ __('Float Management') }}</span>
                        <span class="text-sm text-text-secondary">{{ __('Current / Max Float') }}</span>
                    </div>
                </div>
                <span class="text-sm font-semibold text-text-primary">₦{{ number_format($agent?->float_balance ?? 0, 2) }} / ₦{{ number_format($agent?->max_float ?? 0, 2) }}</span>
            </div>

            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-award class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="font-medium text-text-primary block">{{ __('Commission Rate') }}</span>
                        <span class="text-sm text-text-secondary">{{ __('Per cash transaction') }}</span>
                    </div>
                </div>
                <span class="text-sm font-semibold text-text-primary">{{ number_format($agent?->commission_rate ?? 0, 2) }}%</span>
            </div>

            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-trending-up class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="font-medium text-text-primary block">{{ __('Total Earnings') }}</span>
                        <span class="text-sm text-text-secondary">{{ __('Lifetime commissions earned') }}</span>
                    </div>
                </div>
                <span class="text-sm font-semibold text-text-primary">₦{{ number_format($agent?->total_earnings ?? 0, 2) }}</span>
            </div>

            <button wire:click="toggleChangePinForm" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors active:bg-gray-100 text-left">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-lock class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Change PIN') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-gray-400" />
            </button>
            
        </div>
    </div>

    @if($showChangePinForm)
    <div class="bg-surface rounded-card shadow-elevation-1 p-6 space-y-4">
        <h3 class="font-bold text-text-primary">{{ __('Change PIN') }}</h3>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Current PIN') }}</label>
            <input type="password" wire:model="currentPin" inputmode="numeric" maxlength="6" class="w-full rounded-btn border border-border px-4 py-3 outline-none focus:border-accent">
            @error('currentPin') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('New PIN') }}</label>
            <input type="password" wire:model="newPin" inputmode="numeric" maxlength="6" class="w-full rounded-btn border border-border px-4 py-3 outline-none focus:border-accent">
            @error('newPin') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Confirm New PIN') }}</label>
            <input type="password" wire:model="newPinConfirmation" inputmode="numeric" maxlength="6" class="w-full rounded-btn border border-border px-4 py-3 outline-none focus:border-accent">
            @error('newPinConfirmation') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-button variant="primary" size="large" wire:click="changePin" class="flex-1 bg-accent hover:bg-accent/90">
                {{ __('Save New PIN') }}
            </x-button>
            <x-button variant="secondary" size="large" wire:click="toggleChangePinForm" class="flex-1 bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Cancel') }}
            </x-button>
        </div>
    </div>
    @endif

    @if($showChangePasswordForm)
    <div class="bg-surface rounded-card shadow-elevation-1 p-6 space-y-4">
        <h3 class="font-bold text-text-primary">{{ __('Change Password') }}</h3>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Current Password') }}</label>
            <input type="password" wire:model="currentPassword" class="w-full rounded-btn border border-border px-4 py-3 outline-none focus:border-accent">
            @error('currentPassword') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('New Password') }}</label>
            <input type="password" wire:model="newPassword" class="w-full rounded-btn border border-border px-4 py-3 outline-none focus:border-accent">
            @error('newPassword') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Confirm New Password') }}</label>
            <input type="password" wire:model="newPasswordConfirmation" class="w-full rounded-btn border border-border px-4 py-3 outline-none focus:border-accent">
            @error('newPasswordConfirmation') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-button variant="primary" size="large" wire:click="changePassword" class="flex-1 bg-accent hover:bg-accent/90">
                {{ __('Save Password') }}
            </x-button>
            <x-button variant="secondary" size="large" wire:click="toggleChangePasswordForm" class="flex-1 bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Cancel') }}
            </x-button>
        </div>
    </div>
    @endif

    <button wire:click="toggleChangePasswordForm" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors active:bg-gray-100 text-left bg-surface rounded-card shadow-elevation-1">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                <x-lucide-key class="w-5 h-5" />
            </div>
            <span class="font-medium text-text-primary">{{ __('Change Password') }}</span>
        </div>
        <x-lucide-chevron-right class="w-5 h-5 text-gray-400" />
    </button>

    <!-- Logout -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <button wire:click="logout" class="w-full text-left flex items-center justify-between p-4 hover:bg-red-50 transition-colors active:bg-red-100">
            <div class="flex items-center gap-4 text-danger">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <x-lucide-log-out class="w-5 h-5" />
                </div>
                <span class="font-bold">{{ __('Log Out Agent') }}</span>
            </div>
        </button>
    </div>

</div>
