<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">

    <!-- Header: Avatar & Info -->
    <div class="bg-surface rounded-card p-6 shadow-elevation-1 flex flex-col items-center text-center">
        <div class="w-24 h-24 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-3xl mb-4 shadow-sm">
            {{ strtoupper(substr($user->full_name ?? '?', 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->full_name ?? ' ')[1] ?? '', 0, 1)) }}
        </div>
        <h2 class="text-2xl font-bold text-text-primary">{{ $user->full_name }}</h2>
        <p class="text-text-secondary mt-1">+234 {{ substr($user->phone_number, 1) }}</p>
        @if($user->email)
            <p class="text-text-secondary text-sm mt-0.5">{{ $user->email }}</p>
        @endif

        <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-sm font-medium">
            <x-lucide-briefcase class="w-4 h-4" />
            {{ __('Ajo Agent') }}
        </div>
    </div>

    <!-- Agent Info Card -->
    @if($agent)
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <div class="p-4 border-b border-border">
            <h3 class="font-bold text-text-primary">{{ __('Agent Details') }}</h3>
        </div>
        <div class="divide-y divide-border">
            <div class="flex items-center justify-between p-4">
                <span class="text-sm text-text-secondary">{{ __('Business') }}</span>
                <span class="text-sm font-medium text-text-primary">{{ $agent->business_name }}</span>
            </div>
            <div class="flex items-center justify-between p-4">
                <span class="text-sm text-text-secondary">{{ __('Location') }}</span>
                <span class="text-sm font-medium text-text-primary">{{ $agent->lga }}, {{ $agent->state }}</span>
            </div>
            <div class="flex items-center justify-between p-4">
                <span class="text-sm text-text-secondary">{{ __('Float Balance') }}</span>
                <span class="text-sm font-bold text-emerald-600 tabular-nums">₦{{ number_format($agent->float_balance, 2) }}</span>
            </div>
            <div class="flex items-center justify-between p-4">
                <span class="text-sm text-text-secondary">{{ __('Total Earnings') }}</span>
                <span class="text-sm font-bold text-text-primary tabular-nums">₦{{ number_format($agent->total_earnings, 2) }}</span>
            </div>
            <div class="flex items-center justify-between p-4">
                <span class="text-sm text-text-secondary">{{ __('Commission Rate') }}</span>
                <span class="text-sm font-medium text-text-primary">{{ $agent->commission_rate }}%</span>
            </div>
            <div class="flex items-center justify-between p-4">
                <span class="text-sm text-text-secondary">{{ __('Status') }}</span>
                <x-status-badge :status="$agent->status" />
            </div>
        </div>
    </div>
    @endif

    <!-- Wallet Info -->
    @if($wallet)
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <div class="p-4 border-b border-border">
            <h3 class="font-bold text-text-primary">{{ __('Wallet') }}</h3>
        </div>
        <div class="divide-y divide-border">
            <div class="flex items-center justify-between p-4">
                <span class="text-sm text-text-secondary">{{ __('Account Number') }}</span>
                <span class="text-sm font-mono font-medium text-text-primary">{{ $wallet->account_number ?? 'N/A' }}</span>
            </div>
            <div class="flex items-center justify-between p-4">
                <span class="text-sm text-text-secondary">{{ __('Balance') }}</span>
                <span class="text-sm font-bold text-primary tabular-nums">₦{{ number_format($wallet->available_balance ?? 0, 2) }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Menu List -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <div class="divide-y divide-border">
            <button type="button" wire:click="toggleChangePinForm" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors active:bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-text-secondary">
                        <x-lucide-key-round class="w-5 h-5" />
                    </div>
                    <span class="font-medium text-text-primary">{{ __('Reset Login PIN') }}</span>
                </div>
                <x-lucide-chevron-right class="w-5 h-5 text-gray-400" />
            </button>
        </div>
    </div>

    <!-- Change PIN Form -->
    @if($showChangePinForm)
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden" x-data x-transition>
        <div class="p-4 border-b border-border bg-gray-50 dark:bg-gray-800/50">
            <h3 class="font-bold text-text-primary">{{ __('Reset Login PIN') }}</h3>
            <p class="text-xs text-text-secondary mt-1">{{ __('Enter your current PIN and set a new 6-digit PIN.') }}</p>
        </div>
        <form wire:submit="changePin" class="p-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Current PIN') }}</label>
                <input type="password" wire:model="currentPin" inputmode="numeric" maxlength="6" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 tracking-[0.3em] text-center" placeholder="••••••">
                @error('currentPin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('New PIN') }}</label>
                <input type="password" wire:model="newPin" inputmode="numeric" maxlength="6" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 tracking-[0.3em] text-center" placeholder="••••••">
                @error('newPin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Confirm New PIN') }}</label>
                <input type="password" wire:model="newPinConfirmation" inputmode="numeric" maxlength="6" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 tracking-[0.3em] text-center" placeholder="••••••">
                @error('newPinConfirmation') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3 pt-2">
                <x-button type="button" variant="secondary" class="flex-1 bg-gray-100 text-text-primary hover:bg-gray-200" wire:click="toggleChangePinForm">{{ __('Cancel') }}</x-button>
                <x-button type="submit" variant="primary" class="flex-1 bg-emerald-600 hover:bg-emerald-700" wire:target="changePin" wire:loading.attr="disabled">{{ __('Save PIN') }}</x-button>
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
