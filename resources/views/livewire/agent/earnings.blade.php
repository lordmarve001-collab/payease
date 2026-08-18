<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">
    
    <!-- Hero Stat -->
    <div class="bg-surface rounded-card p-6 shadow-elevation-1 border-t-4 border-secondary text-center">
        <p class="text-text-secondary text-sm font-medium mb-2">{{ __('Available Earnings') }}</p>
        <h2 class="text-4xl md:text-5xl font-bold text-text-primary tabular-nums mb-6">₦{{ number_format($availableEarnings ?? 0, 2) }}</h2>
        <x-button variant="primary" size="large" class="w-full sm:w-auto px-12 bg-secondary hover:bg-secondary/90" wire:click="withdrawEarnings" wire:loading.attr="disabled" x-bind:disabled="@js(($availableEarnings ?? 0) <= 0)">
            {{ __('Withdraw Earnings') }}
        </x-button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-surface rounded-card border border-border p-4">
            <p class="text-xs uppercase tracking-wider text-text-secondary mb-1">{{ __('Selected Period') }}</p>
            <p class="text-xl font-bold text-text-primary">₦{{ number_format($periodTotal ?? 0, 2) }}</p>
        </div>
        <div class="bg-surface rounded-card border border-border p-4">
            <p class="text-xs uppercase tracking-wider text-text-secondary mb-1">{{ __('Cash In') }}</p>
            <p class="text-xl font-bold text-primary">₦{{ number_format($earningsBreakdown['deposit'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-surface rounded-card border border-border p-4">
            <p class="text-xs uppercase tracking-wider text-text-secondary mb-1">{{ __('Cash Out') }}</p>
            <p class="text-xl font-bold text-secondary">₦{{ number_format($earningsBreakdown['withdrawal'] ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Filter Chips -->
    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide mt-8">
        <button wire:click="setFilter('today')" class="{{ $filter === 'today' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('Today') }}</button>
        <button wire:click="setFilter('week')" class="{{ $filter === 'week' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('This Week') }}</button>
        <button wire:click="setFilter('month')" class="{{ $filter === 'month' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('This Month') }}</button>
    </div>

    <!-- Breakdown List -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden mt-4">
        <div class="p-4 border-b border-border bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-text-primary">{{ __('Transactions') }}</h3>
            <span class="text-xs text-text-secondary font-medium">
                {{ $filter === 'today' ? now()->toFormattedDateString() : ($filter === 'week' ? now()->startOfWeek()->toFormattedDateString() . ' - ' . now()->endOfWeek()->toFormattedDateString() : now()->format('F Y')) }}
            </span>
        </div>
        
        <div class="divide-y divide-border">
            @forelse($transactions as $transaction)
                <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-primary-light text-primary flex items-center justify-center shrink-0">
                            @if($transaction->transaction_type === 'deposit')
                                <x-lucide-arrow-down-circle class="w-6 h-6" />
                            @elseif($transaction->transaction_type === 'withdrawal')
                                <x-lucide-arrow-up-circle class="w-6 h-6" />
                            @else
                                <x-lucide-receipt class="w-6 h-6" />
                            @endif
                        </div>
                        <div>
                            <h4 class="font-semibold text-text-primary">{{ ucwords(str_replace('_', ' ', $transaction->transaction_type)) }}</h4>
                            <p class="text-sm text-text-secondary">{{ $transaction->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-text-primary tabular-nums">₦{{ number_format($transaction->commission > 0 ? $transaction->commission : $transaction->amount, 2) }}</p>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-text-secondary">
                    {{ __('No transactions for this period') }}
                </div>
            @endforelse
        </div>
    </div>

</div>
