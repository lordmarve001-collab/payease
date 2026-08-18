<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Notifications') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Choose which alerts you receive about your Ajo groups.') }}</p>
        </div>
        <a href="{{ route('ajo-owner.profile') }}" wire:navigate class="text-sm text-purple-600 hover:text-purple-700 font-medium flex items-center gap-1">
            <x-lucide-arrow-left class="w-4 h-4" /> {{ __('Back to Profile') }}
        </a>
    </div>

    <div class="bg-surface rounded-card shadow-elevation-1 border border-border overflow-hidden">
        <div class="divide-y divide-border">
            <div class="p-5 flex items-center justify-between">
                <div>
                    <p class="font-medium text-text-primary">{{ __('Email Alerts') }}</p>
                    <p class="text-sm text-text-secondary">{{ __('Receive updates via email') }}</p>
                </div>
                <button wire:click="$toggle('emailAlerts')" class="relative w-11 h-6 rounded-full transition-colors {{ $emailAlerts ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform {{ $emailAlerts ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>

            <div class="p-5 flex items-center justify-between">
                <div>
                    <p class="font-medium text-text-primary">{{ __('SMS Alerts') }}</p>
                    <p class="text-sm text-text-secondary">{{ __('Get text message notifications') }}</p>
                </div>
                <button wire:click="$toggle('smsAlerts')" class="relative w-11 h-6 rounded-full transition-colors {{ $smsAlerts ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform {{ $smsAlerts ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>

            <div class="p-5 flex items-center justify-between">
                <div>
                    <p class="font-medium text-text-primary">{{ __('Payout Reminders') }}</p>
                    <p class="text-sm text-text-secondary">{{ __('Alerts when payouts are due or overdue') }}</p>
                </div>
                <button wire:click="$toggle('payoutReminders')" class="relative w-11 h-6 rounded-full transition-colors {{ $payoutReminders ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform {{ $payoutReminders ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>

            <div class="p-5 flex items-center justify-between">
                <div>
                    <p class="font-medium text-text-primary">{{ __('Contribution Alerts') }}</p>
                    <p class="text-sm text-text-secondary">{{ __('Get notified when members make contributions') }}</p>
                </div>
                <button wire:click="$toggle('contributionAlerts')" class="relative w-11 h-6 rounded-full transition-colors {{ $contributionAlerts ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform {{ $contributionAlerts ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>

            <div class="p-5 flex items-center justify-between">
                <div>
                    <p class="font-medium text-text-primary">{{ __('Agent Activity') }}</p>
                    <p class="text-sm text-text-secondary">{{ __('Updates when agents join or leave your network') }}</p>
                </div>
                <button wire:click="$toggle('agentActivity')" class="relative w-11 h-6 rounded-full transition-colors {{ $agentActivity ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform {{ $agentActivity ? 'translate-x-5' : '' }}"></span>
                </button>
            </div>
        </div>

        <div class="p-5 border-t border-border">
            <button wire:click="save" wire:loading.attr="disabled" class="w-full inline-flex items-center justify-center px-4 py-3 rounded-btn bg-purple-600 text-white font-medium hover:bg-purple-700 transition-colors text-sm">
                <span wire:loading.remove>{{ __('Save Preferences') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </button>
        </div>
    </div>
</div>
