<div class="px-4 py-6 md:p-8 max-w-xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-text-primary">{{ __('Transaction Limits') }}</h2>
        <p class="text-text-secondary mt-1">{{ __('Your current limits based on your KYC tier.') }}</p>
    </div>

    <div class="rounded-card bg-gradient-to-br from-primary to-primary-dark p-6 text-white shadow-elevation-2">
        <p class="text-sm text-white/80">{{ __('Current Tier') }}</p>
        <p class="text-3xl font-bold mt-1">Tier {{ $currentLevel }}</p>
        <p class="text-sm text-white/80 mt-1">{{ config("tiers.tiers.{$currentLevel}.label", __('Unknown')) }}</p>
        @if($walletLimits)
        <div class="mt-4 flex gap-6">
            <div>
                <p class="text-xs text-white/70">{{ __('Daily Limit') }}</p>
                <p class="text-lg font-bold">₦{{ number_format($walletLimits['daily_limit'], 0) }}</p>
            </div>
            <div>
                <p class="text-xs text-white/70">{{ __('Per Transaction') }}</p>
                <p class="text-lg font-bold">₦{{ number_format($walletLimits['single_txn_limit'], 0) }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="space-y-3">
        @foreach($limits as $level => $tier)
        <div class="rounded-card border {{ $level == $currentLevel ? 'border-primary bg-primary-light/50' : 'border-border bg-surface' }} p-5 shadow-elevation-1">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full {{ $level == $currentLevel ? 'bg-primary text-white' : 'bg-gray-200 text-text-secondary' }} flex items-center justify-center text-xs font-bold">
                        {{ $level }}
                    </div>
                    <h3 class="font-semibold {{ $level == $currentLevel ? 'text-primary' : 'text-text-primary' }}">
                        {{ $tier['label'] ?? __('Unknown') }}
                    </h3>
                </div>
                @if($level == $currentLevel)
                <span class="text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-full">{{ __('Current') }}</span>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-text-secondary">{{ __('Daily Limit') }}</p>
                    <p class="font-semibold text-text-primary">₦{{ number_format($tier['daily_limit'] ?? 0, 0) }}</p>
                </div>
                <div>
                    <p class="text-text-secondary">{{ __('Per Transaction') }}</p>
                    <p class="font-semibold text-text-primary">₦{{ number_format($tier['single_txn_limit'] ?? 0, 0) }}</p>
                </div>
            </div>
            @if(!empty($tier['requires']))
            <div class="mt-3 pt-3 border-t border-border">
                <p class="text-xs text-text-secondary">{{ __('Requirements:') }}</p>
                <div class="flex flex-wrap gap-1.5 mt-1">
                    @foreach($tier['requires'] as $req)
                        <span class="text-xs bg-gray-100 text-text-secondary px-2 py-0.5 rounded-full">{{ str_replace('_', ' ', $req) }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    @if($currentLevel < 3)
    <div class="rounded-card bg-amber-50 border border-amber-200 p-4 flex items-start gap-2">
        <x-lucide-arrow-up class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
        <p class="text-sm text-amber-800">
            {{ __('Upgrade your KYC to Tier :level to unlock higher limits.', ['level' => $currentLevel + 1]) }}
            <a href="{{ $currentLevel === 1 ? route('customer.kyc-upgrade') : ($currentLevel === 2 ? route('customer.kyc-address') : '#') }}" wire:navigate class="font-semibold underline">{{ __('Upgrade now') }}</a>
        </p>
    </div>
    @endif
</div>
