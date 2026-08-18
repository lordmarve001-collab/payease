<div class="px-4 py-6 md:p-8 max-w-xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-text-primary">{{ __('Help & Support') }}</h2>
        <p class="text-text-secondary mt-1">{{ __('We\'re here to help you.') }}</p>
    </div>

    <div class="rounded-card bg-gradient-to-br from-primary to-primary-dark p-6 text-white shadow-elevation-2">
        <x-lucide-headset class="w-8 h-8 mb-3 text-white/80" />
        <h3 class="text-lg font-bold">{{ __('Contact Support') }}</h3>
        <p class="text-white/80 text-sm mt-1">{{ __('Reach us anytime, we\'re available 24/7.') }}</p>
        <div class="mt-4 space-y-2 text-sm">
            <div class="flex items-center gap-2">
                <x-lucide-phone class="w-4 h-4 text-white/70" />
                <span>{{ __('+234 800 PAYEASE (7293273)') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <x-lucide-mail class="w-4 h-4 text-white/70" />
                <span>{{ __('support@payease.ng') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <x-lucide-message-circle class="w-4 h-4 text-white/70" />
                <span>{{ __('Live chat: payease.ng/chat') }}</span>
            </div>
        </div>
    </div>

    <div class="rounded-card border border-border bg-surface shadow-elevation-1 overflow-hidden">
        <div class="divide-y divide-border">
            <details class="group p-5 cursor-pointer">
                <summary class="flex items-center justify-between text-sm font-semibold text-text-primary list-none">
                    {{ __('How do I create an account?') }}
                    <x-lucide-chevron-down class="w-4 h-4 text-text-secondary transition-transform group-open:rotate-180" />
                </summary>
                <p class="text-sm text-text-secondary mt-3 leading-relaxed">{{ __('Download the') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('app or visit any') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('agent. Provide your phone number, verify your identity with your PIN, and you\'re set! You can also sign up online.') }}</p>
            </details>

            <details class="group p-5 cursor-pointer">
                <summary class="flex items-center justify-between text-sm font-semibold text-text-primary list-none">
                    {{ __('How do I add money to my wallet?') }}
                    <x-lucide-chevron-down class="w-4 h-4 text-text-secondary transition-transform group-open:rotate-180" />
                </summary>
                <p class="text-sm text-text-secondary mt-3 leading-relaxed">{{ __('You can add money by transferring to your dedicated wallet account number from any bank, or by visiting any') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('agent to deposit cash. Card funding is coming soon.') }}</p>
            </details>

            <details class="group p-5 cursor-pointer">
                <summary class="flex items-center justify-between text-sm font-semibold text-text-primary list-none">
                    {{ __('What is Ajo savings?') }}
                    <x-lucide-chevron-down class="w-4 h-4 text-text-secondary transition-transform group-open:rotate-180" />
                </summary>
                <p class="text-sm text-text-secondary mt-3 leading-relaxed">{{ __('Ajo is a community savings system where groups of people contribute money together on a regular schedule.') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('digitizes this by automating collections, tracking contributions, and handling payouts transparently.') }}</p>
            </details>

            <details class="group p-5 cursor-pointer">
                <summary class="flex items-center justify-between text-sm font-semibold text-text-primary list-none">
                    {{ __('How do I upgrade my KYC?') }}
                    <x-lucide-chevron-down class="w-4 h-4 text-text-secondary transition-transform group-open:rotate-180" />
                </summary>
                <p class="text-sm text-text-secondary mt-3 leading-relaxed">{{ __('Go to Profile → KYC Upgrade. For Tier 2, you\'ll need your NIN, BVN, and next of kin details. For Tier 3, you\'ll need a proof of address document. Higher tiers unlock higher transaction limits.') }}</p>
            </details>

            <details class="group p-5 cursor-pointer">
                <summary class="flex items-center justify-between text-sm font-semibold text-text-primary list-none">
                    {{ __('What are the transaction limits?') }}
                    <x-lucide-chevron-down class="w-4 h-4 text-text-secondary transition-transform group-open:rotate-180" />
                </summary>
                <p class="text-sm text-text-secondary mt-3 leading-relaxed">{{ __('Tier 1: ₦2,000 per transaction, ₦5,000 daily. Tier 2: ₦20,000 per transaction, ₦50,000 daily. Tier 3: ₦100,000 per transaction, ₦200,000 daily. See Profile → Transaction Limits for details.') }}</p>
            </details>

            <details class="group p-5 cursor-pointer">
                <summary class="flex items-center justify-between text-sm font-semibold text-text-primary list-none">
                    {{ __('Is my money safe?') }}
                    <x-lucide-chevron-down class="w-4 h-4 text-text-secondary transition-transform group-open:rotate-180" />
                </summary>
                <p class="text-sm text-text-secondary mt-3 leading-relaxed">{{ __('Yes!') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('uses 256-bit encryption and PIN & OTP verification to protect your account. Customer funds are held with our CBN-licensed banking partner and are NDIC-insured. Our systems are continuously monitored for suspicious activity.') }}</p>
            </details>
        </div>
    </div>
</div>
