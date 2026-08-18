@php
    $format = fn (float $amount): string => '₦' . number_format($amount, 2);
@endphp

<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    <div class="mb-2">
        <h1 class="text-2xl font-bold text-text-primary">Platform Liquidity</h1>
        <p class="text-text-secondary text-sm">
            Customer wallet liabilities, agent float obligations, and free platform funds.
        </p>
    </div>

    @if (! $snapshot['is_healthy'])
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 flex items-start gap-3">
            <x-icon name="exclamation-triangle" class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
            <div>
                <h3 class="font-semibold text-red-800 dark:text-red-200">Liquidity Below Safety Threshold</h3>
                <p class="text-sm text-red-700 dark:text-red-300">
                    Available funds ({{ $format($snapshot['available_platform_funds']) }}) are below the minimum threshold
                    of {{ $format($snapshot['minimum_threshold']) }}. Outbound disbursements are blocked until liquidity improves.
                </p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        <x-stat-card
            label="Customer Wallet Balances"
            :value="$format($snapshot['customer_wallet_balances'])"
        />
        <x-stat-card
            label="Agent Unsettled Obligations"
            :value="$format($snapshot['agent_unsettled_obligations'])"
        />
        <x-stat-card
            label="Platform Master Balance"
            :value="$format($snapshot['platform_master_balance'])"
        />
        <x-stat-card
            label="Available Platform Funds"
            :value="$format($snapshot['available_platform_funds'])"
            :status="$snapshot['is_healthy'] ? 'success' : 'danger'"
        />
    </div>

    <div class="bg-surface rounded-xl shadow-card p-6">
        <h2 class="text-lg font-semibold text-text-primary mb-4">Threshold Check</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-text-secondary">Minimum Required Buffer</span>
                <span class="font-medium text-text-primary">{{ $format($snapshot['minimum_threshold']) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">Current Available Funds</span>
                <span class="font-medium {{ $snapshot['is_healthy'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $format($snapshot['available_platform_funds']) }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-secondary">Status</span>
                <span class="font-medium {{ $snapshot['is_healthy'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $snapshot['is_healthy'] ? 'Healthy' : 'Critical' }}
                </span>
            </div>
        </div>
    </div>
</div>
