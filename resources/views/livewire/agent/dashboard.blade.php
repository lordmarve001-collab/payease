<div class="px-4 py-6 md:p-8 max-w-5xl mx-auto space-y-8" x-data="{ lowFloat: {{ $isLowFloat ? 'true' : 'false' }} }">
    
    <!-- Top Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Earnings Card -->
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 transition-all duration-250 ease-material hover:shadow-elevation-2 border-l-4 border-primary">
                <p class="text-text-secondary text-sm font-medium mb-2">{{ __("Today's Earnings") }}</p>
                <div class="flex items-baseline justify-between">
                    <h3 class="text-3xl font-bold text-text-primary tabular-nums">₦{{ number_format($todayEarnings ?? 0, 2) }}</h3>
                    <p class="text-xs text-text-secondary">{{ __('Lifetime') }}: ₦{{ number_format($agent?->total_earnings ?? 0, 2) }}</p>
            </div>
        </div>

        <!-- Float Balance Card -->
        <div class="p-6 rounded-card shadow-elevation-1 transition-all duration-250 ease-material hover:shadow-elevation-2 relative overflow-hidden"
             :class="lowFloat ? 'bg-orange-50 border border-orange-200' : 'bg-surface border border-border'">
            
            <div class="flex justify-between items-start mb-2">
                <p class="text-sm font-medium" :class="lowFloat ? 'text-orange-800' : 'text-text-secondary'">{{ __('Float Balance') }}</p>
            </div>
            
            <div class="flex items-baseline justify-between">
                <h3 class="text-3xl font-bold tabular-nums" :class="lowFloat ? 'text-orange-900' : 'text-text-primary'">
                    ₦{{ number_format($agent?->float_balance ?? 0, 2) }}
                </h3>
                <div class="text-xs text-text-secondary">{{ __('Max') }}: ₦{{ number_format($agent?->max_float ?? 0, 2) }}</div>
            </div>
            
            @php $pendingTopUp = $agent?->pendingTopUpRequest()->first(); @endphp
            @if($pendingTopUp)
                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-btn">
                    <p class="text-sm text-amber-800 flex items-center gap-2">
                        <x-lucide-clock class="w-4 h-4 shrink-0" />
                        {{ __('Top-up of') }} ₦{{ number_format($pendingTopUp->amount_requested, 2) }} {{ __('pending approval') }}
                    </p>
                </div>
            @else
                <div x-show="lowFloat" x-transition class="mt-4" style="display: none;">
                    <a href="{{ route('agent.request-topup') }}" wire:navigate class="block w-full py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-btn text-sm font-medium transition-colors text-center flex items-center justify-center gap-2 active:scale-95">
                        <x-lucide-alert-triangle class="w-4 h-4" />
                        {{ __('Request Top-up') }}
                    </a>
                </div>
            @endif
        </div>

        <!-- Customers Card -->
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 transition-all duration-250 ease-material hover:shadow-elevation-2 border-l-4 border-secondary">
            <p class="text-text-secondary text-sm font-medium mb-2">{{ __('Registered Customers') }}</p>
            <h3 class="text-3xl font-bold text-text-primary tabular-nums">{{ number_format($registeredCustomersCount ?? 0) }}</h3>
            <p class="text-xs text-text-secondary mt-1">{{ __('lifetime') }}</p>
        </div>

        <!-- Transactions Today Card -->
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 transition-all duration-250 ease-material hover:shadow-elevation-2 border-l-4 border-emerald-500">
            <p class="text-text-secondary text-sm font-medium mb-2">{{ __("Today's Transactions") }}</p>
            <h3 class="text-3xl font-bold text-text-primary tabular-nums">{{ number_format($todayTransactionCount ?? 0) }}</h3>
            <p class="text-xs text-text-secondary mt-1">{{ __('count') }}</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <section>
        <h3 class="text-lg font-bold text-text-primary mb-4">{{ __('Quick Actions') }}</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <a href="{{ route('agent.cash-in') }}" wire:navigate class="block"><x-quick-action icon="arrow-down-circle" :label="__('Cash In')" class="py-5" /></a>
            <a href="{{ route('agent.cash-out') }}" wire:navigate class="block"><x-quick-action icon="arrow-up-circle" :label="__('Cash Out')" class="py-5" /></a>
            <a href="{{ route('agent.create-customer') }}" wire:navigate class="block"><x-quick-action icon="user-plus" :label="__('Create Customer')" class="py-5" /></a>
            <a href="{{ route('agent.upgrade-customer') }}" wire:navigate class="block"><x-quick-action icon="shield-check" :label="__('Upgrade KYC')" class="py-5" /></a>
            <div @click="$dispatch('notify-info', 'Airtime Sale coming soon!')" class="cursor-pointer"><x-quick-action icon="smartphone" :label="__('Airtime Sale')" class="py-5" /></div>
            <div @click="$dispatch('notify-info', 'Bill Payment coming soon!')" class="cursor-pointer"><x-quick-action icon="receipt" :label="__('Bill Payment')" class="py-5" /></div>
            <a href="{{ route('agent.ajo-collection') }}" wire:navigate class="block"><x-quick-action icon="users" :label="__('Ajo Collect')" class="py-5" /></a>
            <a href="{{ route('agent.settle-float') }}" wire:navigate class="block"><x-quick-action icon="dollar-sign" :label="__('Settle Float')" class="py-5" /></a>
        </div>
    </section>

    <!-- Recent Customers / Transactions -->
    <section>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-text-primary">{{ __('Recent Transactions') }}</h3>
            <a href="{{ route('agent.transactions') }}" wire:navigate class="text-sm font-medium text-secondary hover:text-secondary/80 transition-colors">{{ __('View All') }} &rarr;</a>
        </div>
        <div class="space-y-3">
            @forelse($recentTransactions as $transaction)
                @php
                    $transactionType = $transaction->transaction_type;
                    $type = $transaction->status === 'failed'
                        ? 'failed'
                        : (in_array($transactionType, ['deposit', 'cash_in'], true) ? 'credit' : 'debit');
                    $customerName = $transaction->toWallet?->user?->full_name ?? $transaction->fromWallet?->user?->full_name;
                    $title = $customerName ? $customerName : ($transaction->description ?? $transaction->recipient_phone ?? 'Transaction');
                    $subtitle = $transaction->recipient_phone
                        ? '+234 ' . ltrim($transaction->recipient_phone, '0')
                        : ucwords(str_replace('_', ' ', $transactionType));
                @endphp
                <x-transaction-item 
                    :type="$type" 
                    :title="$title" 
                    :subtitle="$subtitle" 
                    :amount="'₦' . number_format($transaction->amount, 2)" 
                    :timestamp="$transaction->created_at->diffForHumans()" 
                />
            @empty
                <p class="text-center text-text-secondary py-6">{{ __('No recent transactions') }}</p>
            @endforelse
        </div>
    </section>
</div>
