<div class="px-4 py-6 md:p-8 max-w-lg mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Notification Settings') }}</h1>
        <p class="text-text-secondary text-sm mt-1">{{ __('Choose which alerts you\'d like to receive.') }}</p>
    </div>

    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <div class="divide-y divide-border">
            <!-- SMS Notifications -->
            <div class="flex items-center justify-between p-4">
                <div>
                    <p class="font-medium text-text-primary">{{ __('SMS Notifications') }}</p>
                    <p class="text-sm text-text-secondary">{{ __('Receive alerts via text message') }}</p>
                </div>
                <button wire:click="$toggle('notifySms')"
                    class="relative w-11 h-6 rounded-full transition-colors duration-200 {{ $notifySms ? 'bg-secondary' : 'bg-gray-300' }}">
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200 {{ $notifySms ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
            </div>

            <!-- Email Notifications -->
            <div class="flex items-center justify-between p-4">
                <div>
                    <p class="font-medium text-text-primary">{{ __('Email Notifications') }}</p>
                    <p class="text-sm text-text-secondary">{{ __('Receive alerts via email') }}</p>
                </div>
                <button wire:click="$toggle('notifyEmail')"
                    class="relative w-11 h-6 rounded-full transition-colors duration-200 {{ $notifyEmail ? 'bg-secondary' : 'bg-gray-300' }}">
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200 {{ $notifyEmail ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
            </div>

            <!-- Agent Activity Alerts -->
            <div class="flex items-center justify-between p-4">
                <div>
                    <p class="font-medium text-text-primary">{{ __('Agent Activity Alerts') }}</p>
                    <p class="text-sm text-text-secondary">{{ __('Float balance and agent transaction updates') }}</p>
                </div>
                <button wire:click="$toggle('notifyAgentActivity')"
                    class="relative w-11 h-6 rounded-full transition-colors duration-200 {{ $notifyAgentActivity ? 'bg-secondary' : 'bg-gray-300' }}">
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200 {{ $notifyAgentActivity ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
            </div>
        </div>
    </div>

    <p class="text-xs text-text-secondary text-center">{{ __('Changes are saved automatically when you toggle a setting.') }}</p>
</div>
