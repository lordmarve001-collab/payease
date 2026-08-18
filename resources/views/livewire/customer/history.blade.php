<div class="px-4 py-6 md:p-8 max-w-3xl mx-auto">
    
    <!-- Header & Filters -->
    <div class="sticky top-16 bg-background/95 backdrop-blur z-20 pb-4 pt-2 -mt-2">
        <h1 class="text-2xl font-bold text-text-primary mb-4">{{ __('Transaction History') }}</h1>
        
        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
            <button wire:click="$set('filter', 'all')" :class="$wire.filter === 'all' ? 'bg-primary text-white border-primary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50'" class="px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('All') }}</button>
            <button wire:click="$set('filter', 'credit')" :class="$wire.filter === 'credit' ? 'bg-primary text-white border-primary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50'" class="px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('Credit') }}</button>
            <button wire:click="$set('filter', 'debit')" :class="$wire.filter === 'debit' ? 'bg-primary text-white border-primary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50'" class="px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('Debit') }}</button>
            <button wire:click="$set('filter', 'failed')" :class="$wire.filter === 'failed' ? 'bg-primary text-white border-primary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50'" class="px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('Failed') }}</button>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="space-y-6 mt-4">
        @if($transactions->isEmpty())
            <p class="text-center text-text-secondary py-8">{{ __('No transactions yet') }}</p>
        @else
            @foreach($transactions as $date => $group)
                <div>
                    <h3 class="text-xs font-bold text-text-secondary uppercase tracking-wider mb-3 sticky top-[120px] bg-background/95 py-1 z-10">
                        {{ \Illuminate\Support\Carbon::parse($date)->toFormattedDateString() }}
                    </h3>
                    <div class="space-y-3">
                        @foreach($group as $transaction)
                            @php
                                $isCredit = $transaction->to_wallet_id === $wallet?->id;
                                $type = $transaction->status === 'failed' ? 'failed' : ($isCredit ? 'credit' : 'debit');
                                $amount = '₦' . number_format($transaction->amount, 2);
                                $title = ucwords(str_replace('_', ' ', $transaction->transaction_type));
                                $subtitle = $transaction->description ?? $transaction->recipient_phone ?? 'No description';
                                $timestamp = $transaction->created_at->format('H:i A');
                            @endphp
                            <div class="cursor-pointer active:scale-[0.98] transition-transform" 
                                 wire:key="{{ $transaction->id }}"
                                 @click='$dispatch("open-modal", @js([
                                     'ref' => $transaction->reference,
                                     'type' => $type,
                                     'title' => $title,
                                     'amount' => $amount,
                                     'date' => $transaction->created_at->toFormattedDateString() . ' ' . $timestamp,
                                     'status' => $transaction->status === 'completed' ? 'Success' : ucfirst($transaction->status),
                                     'fee' => '₦' . number_format($transaction->fee, 2),
                                     'desc' => $subtitle,
                                     'mmoTransactionId' => $transaction->mmo_transaction_id ?? 'N/A',
                                 ]))'>
                                <x-transaction-item :type="$type" :title="$title" :subtitle="$subtitle" :amount="$amount" :timestamp="$timestamp" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Modal/Bottom Sheet for Transaction Details -->
    <div x-data="{ 
            open: false, 
            data: {},
            init() {
                window.addEventListener('open-modal', (e) => {
                    this.data = e.detail;
                    this.open = true;
                });
            }
         }">
        
        <!-- Backdrop -->
        <div x-show="open" 
             x-transition.opacity 
             class="fixed inset-0 bg-text-primary/40 backdrop-blur-sm z-50"
             @click="open = false" style="display: none;"></div>
        
        <!-- Panel -->
        <div x-show="open"
             x-transition:enter="transition ease-material duration-300 transform"
             x-transition:enter-start="translate-y-full md:translate-y-10 md:opacity-0"
             x-transition:enter-end="translate-y-0 md:opacity-100"
             x-transition:leave="transition ease-material duration-200 transform"
             x-transition:leave-start="translate-y-0 md:opacity-100"
             x-transition:leave-end="translate-y-full md:translate-y-10 md:opacity-0"
             class="fixed bottom-0 left-0 right-0 md:top-1/2 md:left-1/2 md:bottom-auto md:right-auto md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-md bg-surface rounded-t-sheet md:rounded-card shadow-elevation-4 z-50 overflow-hidden" style="display: none;">
            
            <!-- Handle (Mobile only) -->
            <div class="w-full flex justify-center pt-3 pb-1 md:hidden" @click="open = false">
                <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
            </div>
            
            <div class="p-6 pt-2 md:pt-6">
                <div class="flex justify-between items-start mb-6">
                    <h3 class="text-xl font-bold text-text-primary">{{ __('Transaction Details') }}</h3>
                    <button @click="open = false" class="p-2 -mr-2 text-text-secondary hover:text-text-primary transition-colors bg-gray-100 rounded-full md:bg-transparent">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="text-center mb-8">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3"
                         :class="data.type === 'credit' ? 'bg-primary-light text-primary' : (data.type === 'debit' ? 'bg-red-100 text-danger' : 'bg-gray-100 text-text-secondary')">
                        <template x-if="data.type === 'credit'"><x-lucide-arrow-down-left class="w-8 h-8" /></template>
                        <template x-if="data.type === 'debit'"><x-lucide-arrow-up-right class="w-8 h-8" /></template>
                        <template x-if="data.type === 'failed'"><x-lucide-x class="w-8 h-8" /></template>
                    </div>
                    <h4 class="text-2xl font-bold text-text-primary tabular-nums" x-text="data.amount"></h4>
                    <p class="text-text-secondary mt-1" x-text="data.title"></p>
                </div>
                
                <div class="space-y-4 text-sm border-t border-border pt-6">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Status') }}</span>
                        <span class="font-medium px-2 py-0.5 rounded text-xs"
                              :class="data.status === 'Success' ? 'bg-primary-light text-primary' : 'bg-red-100 text-danger'" x-text="data.status"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Description') }}</span>
                        <span class="font-medium text-text-primary text-right max-w-[60%]" x-text="data.desc"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Fee') }}</span>
                        <span class="font-medium text-text-primary" x-text="data.fee"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Date & Time') }}</span>
                        <span class="font-medium text-text-primary" x-text="data.date"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Reference') }}</span>
                        <span class="font-medium text-text-primary" x-text="data.ref"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('MMO Txn ID') }}</span>
                        <span class="font-medium text-text-primary text-right max-w-[60%]" x-text="data.mmoTransactionId"></span>
                    </div>
                </div>
                
                <div class="mt-8 pt-4">
                    <x-button variant="secondary" size="large" class="w-full bg-gray-100 hover:bg-gray-200 text-text-primary opacity-60 cursor-not-allowed" disabled>
                        {{ __('Download Receipt') }} ({{ __('Coming Soon') }})
                    </x-button>
                </div>
            </div>
        </div>
    </div>

    @if($paginator->hasPages())
        <div class="mt-6">
            {{ $paginator->links() }}
        </div>
    @endif
</div>
