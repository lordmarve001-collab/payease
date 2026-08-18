<div class="px-4 py-6 md:p-8 max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Transaction History') }}</h1>
            <p class="text-sm text-text-secondary">{{ __('View all your agent transactions.') }}</p>
        </div>
    </div>

    <!-- Search -->
    <div class="relative">
        <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-secondary" />
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by reference, name, or phone...') }}"
            class="w-full pl-10 pr-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2">
        <select wire:model.live="typeFilter" class="px-3 py-1.5 rounded-full border border-border bg-surface text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40 cursor-pointer">
            <option value="all">{{ __('All Types') }}</option>
            <option value="deposit">{{ __('Deposit') }}</option>
            <option value="withdrawal">{{ __('Withdrawal') }}</option>
            <option value="ajo_contribution">{{ __('Ajo Contribution') }}</option>
        </select>
        <select wire:model.live="statusFilter" class="px-3 py-1.5 rounded-full border border-border bg-surface text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40 cursor-pointer">
            <option value="all">{{ __('All Status') }}</option>
            <option value="completed">{{ __('Completed') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
            <option value="reversed">{{ __('Reversed') }}</option>
        </select>
        <select wire:model.live="dateFilter" class="px-3 py-1.5 rounded-full border border-border bg-surface text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40 cursor-pointer">
            <option value="all">{{ __('All Time') }}</option>
            <option value="today">{{ __('Today') }}</option>
            <option value="24h">{{ __('Last 24 Hours') }}</option>
            <option value="week">{{ __('This Week') }}</option>
            <option value="month">{{ __('This Month') }}</option>
        </select>
    </div>

    <!-- Transactions List -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <div class="divide-y divide-border">
            @forelse($transactions as $transaction)
                @php
                    $customerName = $transaction->toWallet?->user?->full_name ?? $transaction->fromWallet?->user?->full_name;
                    $title = $customerName ?: ($transaction->description ?? $transaction->recipient_phone ?? 'Transaction');
                    $subtitle = $transaction->recipient_phone
                        ? '+234 ' . ltrim($transaction->recipient_phone, '0')
                        : ucwords(str_replace('_', ' ', $transaction->transaction_type));
                @endphp
                <button wire:click="viewTransaction('{{ $transaction->id }}')" class="w-full text-left p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0
                            {{ $transaction->status === 'failed' ? 'bg-red-100 text-danger' : '' }}
                            {{ $transaction->status === 'reversed' ? 'bg-orange-100 text-orange-600' : '' }}
                            {{ $transaction->status === 'completed' ? ($transaction->transaction_type === 'deposit' || $transaction->transaction_type === 'cash_in' ? 'bg-green-100 text-green-600' : 'bg-secondary/10 text-secondary') : '' }}">
                            @if($transaction->status === 'failed')
                                <x-lucide-x class="w-5 h-5" />
                            @elseif($transaction->status === 'reversed')
                                <x-lucide-rotate-ccw class="w-5 h-5" />
                            @elseif($transaction->transaction_type === 'deposit' || $transaction->transaction_type === 'cash_in')
                                <x-lucide-arrow-down-circle class="w-5 h-5" />
                            @else
                                <x-lucide-arrow-up-circle class="w-5 h-5" />
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-semibold text-text-primary text-sm truncate">{{ $title }}</h4>
                            <div class="flex items-center gap-2 mt-0.5">
                                <p class="text-xs text-text-secondary truncate">{{ $subtitle }}</p>
                                @if($transaction->status !== 'completed')
                                    <span class="inline-flex items-center px-1.5 py-px rounded text-[10px] font-bold uppercase
                                        {{ $transaction->status === 'failed' ? 'bg-red-100 text-danger' : '' }}
                                        {{ $transaction->status === 'reversed' ? 'bg-orange-100 text-orange-600' : '' }}
                                        {{ $transaction->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="text-right shrink-0 ml-4">
                        <p class="font-bold text-text-primary text-sm tabular-nums
                            {{ $transaction->status === 'failed' || $transaction->status === 'reversed' ? 'line-through text-text-secondary' : '' }}">
                            ₦{{ number_format((float) $transaction->amount, 2) }}
                        </p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ $transaction->created_at->diffForHumans() }}</p>
                    </div>
                </button>
            @empty
                <div class="p-10 text-center text-text-secondary">
                    <x-lucide-receipt class="w-10 h-10 mx-auto mb-3 text-text-secondary/50" />
                    <p>{{ __('No transactions found.') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="flex justify-center">
        {{ $transactions->links() }}
    </div>

    <!-- Transaction Detail Modal -->
    @if($showTransactionModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeTransactionModal"></div>
            <div class="relative bg-surface rounded-card shadow-elevation-2 w-full max-w-md max-h-[90vh] overflow-y-auto p-6 space-y-4" @click.away="window.innerWidth >= 768 ? $wire.closeTransactionModal() : null">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-text-primary">{{ __('Transaction Details') }}</h3>
                    <button wire:click="closeTransactionModal" class="p-1 text-text-secondary hover:text-text-primary">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Reference') }}</span>
                        <span class="font-medium text-text-primary truncate ml-4 max-w-[200px]">{{ $selectedTransaction->reference }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Type') }}</span>
                        <span class="font-medium text-text-primary">{{ ucwords(str_replace('_', ' ', $selectedTransaction->transaction_type)) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Amount') }}</span>
                        <span class="font-bold text-text-primary">₦{{ number_format((float) $selectedTransaction->amount, 2) }}</span>
                    </div>
                    @if($selectedTransaction->fee > 0)
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Fee') }}</span>
                        <span class="font-medium text-text-primary">₦{{ number_format((float) $selectedTransaction->fee, 2) }}</span>
                    </div>
                    @endif
                    @if($selectedTransaction->commission > 0)
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Commission') }}</span>
                        <span class="font-medium text-secondary">₦{{ number_format((float) $selectedTransaction->commission, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Status') }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold
                            {{ $selectedTransaction->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $selectedTransaction->status === 'failed' ? 'bg-red-100 text-danger' : '' }}
                            {{ $selectedTransaction->status === 'reversed' ? 'bg-orange-100 text-orange-600' : '' }}
                            {{ $selectedTransaction->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}">
                            {{ ucfirst($selectedTransaction->status) }}
                        </span>
                    </div>
                    @if($selectedTransaction->recipient_phone)
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Recipient Phone') }}</span>
                        <span class="font-medium text-text-primary">+234 {{ ltrim($selectedTransaction->recipient_phone, '0') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Date & Time') }}</span>
                        <span class="font-medium text-text-primary">{{ $selectedTransaction->created_at->format('M j, Y g:i A') }}</span>
                    </div>
                </div>

                <button wire:click="closeTransactionModal" class="w-full py-2.5 bg-secondary hover:bg-secondary/90 text-white rounded-btn font-medium transition-colors mt-2">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    @endif
</div>
