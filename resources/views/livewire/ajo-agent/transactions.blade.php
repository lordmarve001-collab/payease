<div class="px-4 py-6 md:p-8 max-w-3xl mx-auto">
    <div class="sticky top-16 bg-background/95 backdrop-blur z-20 pb-4 pt-2 -mt-2">
        <h1 class="text-2xl font-bold text-text-primary mb-4">{{ __('Transaction History') }}</h1>

        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
            <button wire:click="$set('filter', 'all')" :class="$wire.filter === 'all' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-surface text-text-secondary border-border hover:bg-gray-50'" class="px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('All') }}</button>
            <button wire:click="$set('filter', 'completed')" :class="$wire.filter === 'completed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-surface text-text-secondary border-border hover:bg-gray-50'" class="px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('Completed') }}</button>
            <button wire:click="$set('filter', 'pending')" :class="$wire.filter === 'pending' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-surface text-text-secondary border-border hover:bg-gray-50'" class="px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('Pending') }}</button>
            <button wire:click="$set('filter', 'failed')" :class="$wire.filter === 'failed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-surface text-text-secondary border-border hover:bg-gray-50'" class="px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('Failed') }}</button>
        </div>
    </div>

    @if(!$agent)
        <div class="text-center py-12">
            <x-lucide-alert-circle class="w-12 h-12 text-text-secondary mx-auto mb-4" />
            <p class="text-text-secondary">No agent profile found.</p>
        </div>
    @else
    <div class="space-y-6 mt-4">
        @if($paginator && $paginator->isEmpty())
            <div class="bg-surface rounded-card border border-border p-8 text-center">
                <x-lucide-history class="w-12 h-12 text-text-secondary mx-auto mb-3" />
                <p class="text-text-secondary font-medium">{{ __('No transactions yet.') }}</p>
                <p class="text-sm text-text-secondary mt-1">{{ __('Contributions you collect will appear here.') }}</p>
            </div>
        @else
            @foreach($contributions as $date => $group)
                <div>
                    <h3 class="text-xs font-bold text-text-secondary uppercase tracking-wider mb-3">{{ \Illuminate\Support\Carbon::parse($date)->toFormattedDateString() }}</h3>
                    <div class="space-y-3">
                        @foreach($group as $contribution)
                            @php
                                $isCompleted = $contribution->status === 'completed';
                                $iconBg = $isCompleted ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600';
                                $amountColor = $isCompleted ? 'text-emerald-600' : 'text-red-500';
                            @endphp
                            <div class="bg-surface rounded-card border border-border p-4 hover:shadow-elevation-1 transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-full {{ $iconBg }} flex items-center justify-center shrink-0">
                                            <x-lucide-circle-dollar-sign class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-text-primary text-sm">{{ $contribution->user?->full_name ?? 'Unknown Member' }}</p>
                                            <p class="text-xs text-text-secondary mt-0.5">{{ $contribution->group?->name ?? 'Unknown Group' }} &middot; Cycle {{ $contribution->cycle_number }}</p>
                                            @if($contribution->transaction)
                                                <p class="text-[11px] text-text-secondary mt-0.5 font-mono">{{ $contribution->transaction->reference }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold {{ $amountColor }} tabular-nums">+₦{{ number_format($contribution->amount, 2) }}</p>
                                        <x-status-badge :status="$contribution->status" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    @if($paginator && $paginator->hasPages())
        <div class="mt-6">
            {{ $paginator->links() }}
        </div>
    @endif
    @endif
</div>
