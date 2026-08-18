<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Help & Support') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Get assistance with your Ajo Owner account.') }}</p>
        </div>
        <a href="{{ route('ajo-owner.profile') }}" wire:navigate class="text-sm text-purple-600 hover:text-purple-700 font-medium flex items-center gap-1">
            <x-lucide-arrow-left class="w-4 h-4" /> {{ __('Back to Profile') }}
        </a>
    </div>

    <div class="bg-surface rounded-card shadow-elevation-1 border border-border overflow-hidden">
        <div class="divide-y divide-border">
            <div class="p-5">
                <h3 class="font-bold text-text-primary mb-2">{{ __('Contact Support') }}</h3>
                <p class="text-sm text-text-secondary mb-4">{{ __('Our support team is available 24/7 to help you.') }}</p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                            <x-lucide-phone class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="font-medium text-text-primary">{{ __('Phone') }}</p>
                            <p class="text-text-secondary">+234 800 PAYEASE</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                            <x-lucide-mail class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="font-medium text-text-primary">{{ __('Email') }}</p>
                            <p class="text-text-secondary">ajo-owners@payease.com</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                            <x-lucide-message-circle class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="font-medium text-text-primary">{{ __('Live Chat') }}</p>
                            <p class="text-text-secondary">{{ __('Available in-app 6AM – 10PM daily') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5">
                <h3 class="font-bold text-text-primary mb-3">{{ __('Frequently Asked Questions') }}</h3>
                <div class="space-y-3" x-data="{ open: null }">
                    <div class="border border-border rounded-card overflow-hidden">
                        <button @click="open = open === 1 ? null : 1" class="w-full flex items-center justify-between p-4 text-sm font-medium text-text-primary hover:bg-gray-50 transition-colors">
                            {{ __('How do I create a new Ajo group?') }}
                            <x-lucide-chevron-down class="w-4 h-4 text-text-secondary transition-transform" :class="open === 1 ? 'rotate-180' : ''" />
                        </button>
                        <div x-show="open === 1" x-collapse class="px-4 pb-4 text-sm text-text-secondary">
                            {{ __('Navigate to Groups → Create New Group. Fill in the group details (name, contribution amount, frequency), assign a managing agent, and review before creating.') }}
                        </div>
                    </div>
                    <div class="border border-border rounded-card overflow-hidden">
                        <button @click="open = open === 2 ? null : 2" class="w-full flex items-center justify-between p-4 text-sm font-medium text-text-primary hover:bg-gray-50 transition-colors">
                            {{ __('How are payouts scheduled?') }}
                            <x-lucide-chevron-down class="w-4 h-4 text-text-secondary transition-transform" :class="open === 2 ? 'rotate-180' : ''" />
                        </button>
                        <div x-show="open === 2" x-collapse class="px-4 pb-4 text-sm text-text-secondary">
                            {{ __('Payouts follow the rotation order you selected (Fixed or Random). Each cycle completes when all members contribute, then the designated recipient receives the pool.') }}
                        </div>
                    </div>
                    <div class="border border-border rounded-card overflow-hidden">
                        <button @click="open = open === 3 ? null : 3" class="w-full flex items-center justify-between p-4 text-sm font-medium text-text-primary hover:bg-gray-50 transition-colors">
                            {{ __('What happens if a member defaults?') }}
                            <x-lucide-chevron-down class="w-4 h-4 text-text-secondary transition-transform" :class="open === 3 ? 'rotate-180' : ''" />
                        </button>
                        <div x-show="open === 3" x-collapse class="px-4 pb-4 text-sm text-text-secondary">
                            {{ __('Defaulted members are flagged and skipped in the payout rotation. You may remove defaulted members and replace them through the group detail page.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5">
                <h3 class="font-bold text-text-primary mb-2">{{ __('Report an Issue') }}</h3>
                <p class="text-sm text-text-secondary">
                    {{ __('Having trouble? Please contact our support team via phone or email above. For urgent issues, call our 24/7 hotline.') }}
                </p>
            </div>
        </div>
    </div>
</div>
