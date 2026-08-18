<div class="px-4 py-6 md:p-8 max-w-5xl mx-auto space-y-8">
    @if($kycUpgradeMessage)
    <div class="rounded-card border border-primary/20 bg-gradient-to-r from-primary-light/50 to-accent-light/30 p-5 shadow-elevation-1">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex items-center gap-3 flex-1">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0">
                    <x-lucide-shield-check class="w-6 h-6 text-primary" />
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <h3 class="font-bold text-text-primary">KYC Tier {{ $currentTier }}</h3>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-primary/10 text-primary">
                            {{ $tierConfig[$currentTier]['label'] ?? 'Unknown' }}
                        </span>
                    </div>
                    <p class="text-sm text-text-secondary leading-snug">{{ $kycUpgradeMessage }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3 sm:flex-shrink-0">
                @if($nextTier)
                    <div class="hidden sm:block text-right mr-2">
                        <p class="text-[11px] text-text-secondary uppercase font-semibold tracking-wider">Next Tier</p>
                        <p class="text-sm font-bold text-primary">
                            Tier {{ $nextTier }} — {{ $tierConfig[$nextTier]['label'] ?? 'Premium' }}
                        </p>
                        <p class="text-xs text-text-secondary">₦{{ number_format($tierConfig[$nextTier]['daily_limit'] ?? 0, 0) }}/day</p>
                    </div>
                    @if($currentTier === 0)
                        <a href="{{ route('customer.profile') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl font-semibold text-sm hover:bg-primary-dark transition-all active:scale-[0.98] shadow-elevation-1 whitespace-nowrap">
                            <x-lucide-user class="w-4 h-4" />
                            {{ __('Complete Setup') }}
                        </a>
                    @elseif($currentTier === 1)
                        <a href="{{ route('customer.kyc-upgrade') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl font-semibold text-sm hover:bg-primary-dark transition-all active:scale-[0.98] shadow-elevation-1 whitespace-nowrap">
                            <x-lucide-arrow-up-circle class="w-4 h-4" />
                            {{ __('Upgrade Now') }}
                        </a>
                    @elseif($currentTier === 2)
                        <a href="{{ route('customer.kyc-address') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl font-semibold text-sm hover:bg-primary-dark transition-all active:scale-[0.98] shadow-elevation-1 whitespace-nowrap">
                            <x-lucide-map-pin class="w-4 h-4" />
                            {{ __('Upgrade Now') }}
                        </a>
                    @endif
                @else
                    <div class="flex items-center gap-2 text-success">
                        <x-lucide-check-circle class="w-5 h-5" />
                        <span class="text-sm font-semibold">Fully Verified</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Balance Card -->
    @if($wallet)
    <div x-data="{ 
            balance: 0, 
            target: {{ $balance }},
            init() {
                let duration = 1000; // 1s
                let steps = 60;
                let stepTime = Math.abs(Math.floor(duration / steps));
                let increment = this.target / steps;
                let timer = setInterval(() => {
                    this.balance += increment;
                    if (this.balance >= this.target) {
                        this.balance = this.target;
                        clearInterval(timer);
                    }
                }, stepTime);
            },
            formatBalance(value) {
                return '₦' + value.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
         }">
        <div class="p-6 rounded-card shadow-elevation-2 bg-gradient-to-br from-primary to-primary-dark text-white transition-all duration-250 ease-material hover:shadow-elevation-3">
            <p class="text-primary-light text-sm font-medium mb-1">{{ __('Available Balance') }}</p>
            <h2 class="text-4xl font-bold tabular-nums mb-6" x-text="formatBalance(balance)">₦0.00</h2>

            <!-- PayEase Phone Account -->
            <div class="flex items-center justify-between border-t border-white/20 pt-4 mb-3" x-data="{ phoneCopied: false }">
                <div>
                    <p class="text-xs text-primary-light mb-0.5">{{ $siteSettings->site_name ?? 'PayEase' }} {{ __('Account') }}</p>
                    <p class="text-sm font-semibold tracking-wider tabular-nums">{{ $user->phone_number }}</p>
                    <p class="text-[10px] text-primary-light/70">{{ __('Phone number — send & receive on') }} {{ $siteSettings->site_name ?? 'PayEase' }}</p>
                </div>
                <button
                    @click="navigator.clipboard.writeText('{{ $user->phone_number }}'); phoneCopied = true; setTimeout(() => phoneCopied = false, 2000)"
                    class="p-2 bg-white/10 hover:bg-white/20 rounded-full transition-colors active:scale-95 flex items-center justify-center"
                    title="{{ __('Copy Phone Number') }}"
                >
                    <x-lucide-copy class="w-4 h-4" x-show="!phoneCopied" />
                    <x-lucide-check class="w-4 h-4 text-white" x-show="phoneCopied" style="display: none;" />
                </button>
            </div>

            <!-- Bank Account -->
            <div class="flex items-center justify-between border-t border-white/20 pt-4" x-data="{ copied: false }">
                <div>
                    <p class="text-xs text-primary-light mb-0.5">{{ $accountDisplay['headline'] }}</p>
                    @if($accountDisplay['is_pending'])
                        <p class="text-sm font-semibold">{{ $accountDisplay['message'] }}</p>
                    @else
                        <p class="text-sm font-semibold tracking-wider tabular-nums">{{ $accountDisplay['formatted_account_number'] }}</p>
                    @endif
                </div>

                @if($accountDisplay['is_copyable'])
                    <button
                        @click="navigator.clipboard.writeText('{{ $accountDisplay['account_number'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="p-2 bg-white/10 hover:bg-white/20 rounded-full transition-colors active:scale-95 flex items-center justify-center"
                        title="{{ __('Copy Account Number') }}"
                    >
                        <x-lucide-copy class="w-4 h-4" x-show="!copied" />
                        <x-lucide-check class="w-4 h-4 text-white" x-show="copied" style="display: none;" />
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endif

    @unless($wallet)
    <div class="p-6 rounded-card shadow-elevation-1 bg-surface border border-border">
        <h2 class="text-lg font-bold text-text-primary mb-2">{{ __('Wallet unavailable') }}</h2>
        <p class="text-sm text-text-secondary">{{ __("We couldn't load your wallet details yet. Please try again in a moment.") }}</p>
    </div>
    @endunless

    <!-- Quick Actions -->
    <section>
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-4">
            <a href="{{ route('customer.send-money') }}" wire:navigate class="block"><x-quick-action icon="send" :label="__('Send Money')" /></a>
            <a href="{{ route('customer.add-money') }}" wire:navigate class="block"><x-quick-action icon="plus-circle" :label="__('Add Money')" /></a>
            <a href="{{ route('customer.buy-airtime') }}" wire:navigate class="block"><x-quick-action icon="smartphone" :label="__('Buy Airtime')" /></a>
            <a href="{{ route('customer.pay-bills') }}" wire:navigate class="block"><x-quick-action icon="receipt" :label="__('Pay Bills')" /></a>
            <a href="{{ route('customer.my-ajo') }}" wire:navigate class="block"><x-quick-action icon="users" :label="__('My Ajo')" /></a>
            <a href="{{ route('customer.get-loan') }}" wire:navigate class="block"><x-quick-action icon="banknote" :label="__('Get Loan')" /></a>
        </div>
    </section>

    <!-- Recent Transactions -->
    <section>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-text-primary">{{ __('Recent Transactions') }}</h3>
            <a href="{{ route('customer.history') }}" wire:navigate class="text-sm font-medium text-primary hover:text-primary-dark transition-colors">{{ __('View All →') }}</a>
        </div>
        <div class="space-y-3">
            @forelse($recentTransactions as $transaction)
                @php
                    $isCredit = $transaction->to_wallet_id === $wallet?->id;
                    $type = $transaction->status === 'failed' ? 'failed' : ($isCredit ? 'credit' : 'debit');
                    $amount = '₦' . number_format($transaction->amount, 2);
                    $title = ucwords(str_replace('_', ' ', $transaction->transaction_type));
                    $subtitle = $transaction->description ?? $transaction->recipient_phone ?? 'No description';
                    $timestamp = $transaction->created_at->diffForHumans();
                @endphp
                <x-transaction-item :type="$type" :title="$title" :subtitle="$subtitle" :amount="$amount" :timestamp="$timestamp" />
            @empty
                <div class="bg-surface rounded-card border border-border p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-primary-light text-primary flex items-center justify-center mx-auto mb-3">
                        <x-lucide-receipt class="w-6 h-6" />
                    </div>
                    <h4 class="text-base font-semibold text-text-primary">{{ __('No transactions yet') }}</h4>
                    <p class="text-sm text-text-secondary mt-1">{{ __("Your recent transfers will appear here as soon as you start using your wallet.") }}</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
