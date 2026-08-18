<div class="px-4 py-6 md:p-8 max-w-xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-text-primary">{{ __('Get a Loan') }}</h2>
        <p class="text-text-secondary mt-1">{{ __('Access quick and affordable loans right from your wallet.') }}</p>
    </div>

    <div class="rounded-card bg-gradient-to-br from-primary to-primary-dark p-6 text-white shadow-elevation-2">
        <x-lucide-sparkles class="w-8 h-8 mb-3 text-white/80" />
        <h3 class="text-xl font-bold">{{ __('Loan Products Coming Soon') }}</h3>
        <p class="text-white/80 text-sm mt-2 leading-relaxed">{{ __('We\'re working on bringing you flexible loan options with competitive rates. You\'ll be able to apply, get approved, and receive funds directly to your wallet.') }}</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-card border border-border bg-surface p-4 text-center">
            <div class="w-10 h-10 mx-auto rounded-full bg-primary-light flex items-center justify-center text-primary mb-2">
                <x-lucide-gauge class="w-5 h-5" />
            </div>
            <h4 class="text-sm font-semibold text-text-primary">{{ __('Quick Approval') }}</h4>
            <p class="text-xs text-text-secondary mt-1">{{ __('Get decisions in minutes') }}</p>
        </div>
        <div class="rounded-card border border-border bg-surface p-4 text-center">
            <div class="w-10 h-10 mx-auto rounded-full bg-secondary/10 flex items-center justify-center text-secondary mb-2">
                <x-lucide-percent class="w-5 h-5" />
            </div>
            <h4 class="text-sm font-semibold text-text-primary">{{ __('Low Rates') }}</h4>
            <p class="text-xs text-text-secondary mt-1">{{ __('Competitive interest rates') }}</p>
        </div>
        <div class="rounded-card border border-border bg-surface p-4 text-center">
            <div class="w-10 h-10 mx-auto rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mb-2">
                <x-lucide-zap class="w-5 h-5" />
            </div>
            <h4 class="text-sm font-semibold text-text-primary">{{ __('Instant Deposit') }}</h4>
            <p class="text-xs text-text-secondary mt-1">{{ __('Funds sent to your wallet') }}</p>
        </div>
    </div>

    <div class="rounded-card border border-border bg-surface p-5 shadow-elevation-1">
        <h3 class="font-semibold text-text-primary mb-2">{{ __('Eligibility') }}</h3>
        <ul class="space-y-2 text-sm text-text-secondary">
            <li class="flex items-center gap-2">
                <x-lucide-check class="w-4 h-4 text-primary shrink-0" />
                {{ __('Active') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('wallet with transaction history') }}
            </li>
            <li class="flex items-center gap-2">
                <x-lucide-check class="w-4 h-4 text-primary shrink-0" />
                {{ __('Minimum Tier 2 KYC verification') }}
            </li>
            <li class="flex items-center gap-2">
                <x-lucide-check class="w-4 h-4 text-primary shrink-0" />
                {{ __('Regular savings and Ajo contributions') }}
            </li>
        </ul>
    </div>
</div>
