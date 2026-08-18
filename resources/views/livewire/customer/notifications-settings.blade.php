<div class="px-4 py-6 md:p-8 max-w-xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-text-primary">{{ __('Notifications') }}</h2>
        <p class="text-text-secondary mt-1">{{ __('Choose which alerts you\'d like to receive.') }}</p>
    </div>

    <div class="rounded-card border border-border bg-surface shadow-elevation-1 overflow-hidden">
        <div class="divide-y divide-border">
            <div class="flex items-center justify-between p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary-light flex items-center justify-center text-primary">
                        <x-lucide-message-square class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-text-primary">{{ __('SMS Notifications') }}</p>
                        <p class="text-xs text-text-secondary">{{ __('Receive alerts via text message') }}</p>
                    </div>
                </div>
                <button wire:click="$set('notifySms', {{ $notifySms ? 'false' : 'true' }})" class="relative w-12 h-7 rounded-full transition-colors duration-200 {{ $notifySms ? 'bg-primary' : 'bg-gray-300' }}" role="switch" aria-checked="{{ $notifySms ? 'true' : 'false' }}">
                    <span class="absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white shadow-sm transition-transform duration-200 {{ $notifySms ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>

            <div class="flex items-center justify-between p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center text-secondary">
                        <x-lucide-mail class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-text-primary">{{ __('Email Notifications') }}</p>
                        <p class="text-xs text-text-secondary">{{ __('Receive alerts via email') }}</p>
                    </div>
                </div>
                <button wire:click="$set('notifyEmail', {{ $notifyEmail ? 'false' : 'true' }})" class="relative w-12 h-7 rounded-full transition-colors duration-200 {{ $notifyEmail ? 'bg-primary' : 'bg-gray-300' }}" role="switch" aria-checked="{{ $notifyEmail ? 'true' : 'false' }}">
                    <span class="absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white shadow-sm transition-transform duration-200 {{ $notifyEmail ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>

            <div class="flex items-center justify-between p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <x-lucide-banknote class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-text-primary">{{ __('Payout Alerts') }}</p>
                        <p class="text-xs text-text-secondary">{{ __('When you receive money or Ajo payouts') }}</p>
                    </div>
                </div>
                <button wire:click="$set('notifyPayout', {{ $notifyPayout ? 'false' : 'true' }})" class="relative w-12 h-7 rounded-full transition-colors duration-200 {{ $notifyPayout ? 'bg-primary' : 'bg-gray-300' }}" role="switch" aria-checked="{{ $notifyPayout ? 'true' : 'false' }}">
                    <span class="absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white shadow-sm transition-transform duration-200 {{ $notifyPayout ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>

            <div class="flex items-center justify-between p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                        <x-lucide-users class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-text-primary">{{ __('Contribution Alerts') }}</p>
                        <p class="text-xs text-text-secondary">{{ __('Ajo group contribution reminders and updates') }}</p>
                    </div>
                </div>
                <button wire:click="$set('notifyContribution', {{ $notifyContribution ? 'false' : 'true' }})" class="relative w-12 h-7 rounded-full transition-colors duration-200 {{ $notifyContribution ? 'bg-primary' : 'bg-gray-300' }}" role="switch" aria-checked="{{ $notifyContribution ? 'true' : 'false' }}">
                    <span class="absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white shadow-sm transition-transform duration-200 {{ $notifyContribution ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>

            <div class="flex items-center justify-between p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                        <x-lucide-activity class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-text-primary">{{ __('Agent Activity Alerts') }}</p>
                        <p class="text-xs text-text-secondary">{{ __('Float balance and agent transaction updates') }}</p>
                    </div>
                </div>
                <button wire:click="$set('notifyAgentActivity', {{ $notifyAgentActivity ? 'false' : 'true' }})" class="relative w-12 h-7 rounded-full transition-colors duration-200 {{ $notifyAgentActivity ? 'bg-primary' : 'bg-gray-300' }}" role="switch" aria-checked="{{ $notifyAgentActivity ? 'true' : 'false' }}">
                    <span class="absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white shadow-sm transition-transform duration-200 {{ $notifyAgentActivity ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>
        </div>
    </div>

    <p class="text-xs text-text-secondary text-center">{{ __('Changes are saved automatically when you toggle a setting.') }}</p>
</div>
