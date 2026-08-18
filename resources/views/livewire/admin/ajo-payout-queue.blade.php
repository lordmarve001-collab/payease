<div class="p-4 md:p-6 space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Ajo Payout Queue</h1>
            <p class="text-sm text-text-secondary">Convert cash-handover payouts to electronic wallet credits.</p>
        </div>
        <div class="rounded-2xl border border-border bg-background px-4 py-3 text-sm text-text-primary max-w-lg">
            <strong>Pending:</strong> {{ $pendingCount }} payout{{ $pendingCount !== 1 ? 's' : '' }} awaiting electronic credit.
        </div>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-2">
        <button wire:click="setFilter('pending')" class="{{ $filter === 'pending' ? 'bg-primary text-white border-primary' : 'bg-surface text-text-secondary border-border' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">
            Pending
            @if($pendingCount > 0)<span class="ml-1.5 bg-white/20 text-white text-[10px] px-1.5 rounded-full">{{ $pendingCount }}</span>@endif
        </button>
        <button wire:click="setFilter('completed')" class="{{ $filter === 'completed' ? 'bg-primary text-white border-primary' : 'bg-surface text-text-secondary border-border' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">Completed</button>
        <button wire:click="setFilter('failed')" class="{{ $filter === 'failed' ? 'bg-danger text-white border-danger' : 'bg-surface text-text-secondary border-border' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">Failed</button>
    </div>

    <section class="rounded-card border border-border bg-surface shadow-soft overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-background">
                        <th class="px-4 py-3 text-left font-semibold text-text-secondary">Group</th>
                        <th class="px-4 py-3 text-left font-semibold text-text-secondary">Recipient</th>
                        <th class="px-4 py-3 text-left font-semibold text-text-secondary">Agent</th>
                        <th class="px-4 py-3 text-right font-semibold text-text-secondary">Amount</th>
                        <th class="px-4 py-3 text-center font-semibold text-text-secondary">Cycle</th>
                        <th class="px-4 py-3 text-left font-semibold text-text-secondary">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-text-secondary">Date</th>
                        <th class="px-4 py-3 text-right font-semibold text-text-secondary">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $isPending = $item->status === 'pending';
                        @endphp
                        <tr class="border-b border-border last:border-0 hover:bg-background/50 transition-colors">
                            <td class="px-4 py-3 font-medium text-text-primary">{{ $item->group?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <div class="text-text-primary">{{ $item->memberUser?->full_name }}</div>
                                <div class="text-xs text-text-secondary">{{ $item->memberUser?->phone_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-text-secondary">{{ $item->agent?->user?->full_name ?? $item->agent?->business_name }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-text-primary">₦{{ number_format($item->amount, 2) }}</td>
                            <td class="px-4 py-3 text-center text-text-secondary">#{{ $item->cycle_number }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider
                                    {{ $isPending ? 'bg-amber-100 text-amber-700' : ($item->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $isPending ? 'bg-amber-500 animate-pulse' : ($item->status === 'completed' ? 'bg-green-500' : 'bg-red-500') }}"></span>
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-text-secondary">{{ $item->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($isPending)
                                    <div class="flex gap-2 justify-end">
                                        <button wire:click="confirmProcess('{{ $item->id }}')" class="inline-flex items-center justify-center rounded-btn bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90">
                                            Credit Wallet
                                        </button>
                                        <button wire:click="confirmReject('{{ $item->id }}')" class="inline-flex items-center justify-center rounded-btn border border-border px-3 py-1.5 text-xs font-semibold text-text-secondary transition hover:text-danger hover:border-danger">
                                            Reject
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-text-secondary">{{ $item->processed_at?->format('d M Y H:i') }}</span>
                                @endif
                            </td>
                        </tr>
                        @if($item->note)
                            <tr class="bg-background/30">
                                <td colspan="8" class="px-4 py-2 text-xs text-text-secondary italic">{{ $item->note }}</td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-text-secondary">
                                <div class="flex flex-col items-center">
                                    <x-lucide-check-circle class="w-12 h-12 text-success mb-3" />
                                    <h3 class="text-lg font-semibold text-text-primary">All clear</h3>
                                    <p class="text-sm mt-1">No {{ $filter }} payout queue items.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="border-t border-border px-4 py-3">
                {{ $items->links() }}
            </div>
        @endif
    </section>

    @if($confirmingItemId && $selectedItem)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="cancelProcess">
            <div class="bg-surface rounded-card shadow-elevation-3 w-full max-w-md mx-4 p-6 space-y-5" @click.stop>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-text-primary">Credit Wallet</h3>
                    <button wire:click="cancelProcess" class="text-text-secondary hover:text-text-primary transition-colors">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <div class="rounded-2xl border border-border bg-background p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Recipient</span>
                        <span class="font-semibold text-text-primary">{{ $selectedItem->memberUser?->full_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Group</span>
                        <span class="font-medium text-text-primary">{{ $selectedItem->group?->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Amount</span>
                        <span class="font-bold text-text-primary tabular-nums">₦{{ number_format($selectedItem->amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Cycle</span>
                        <span class="font-medium text-text-primary">#{{ $selectedItem->cycle_number }}</span>
                    </div>
                </div>

                <p class="text-sm text-text-secondary">This will credit the member's {{ $siteSettings->site_name ?? 'PayEase' }} wallet with the payout amount. The agent float has already been reduced during the cash handover.</p>

                <div class="flex gap-3">
                    <button wire:click="cancelProcess" class="flex-1 inline-flex items-center justify-center rounded-btn border border-border px-4 py-3 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">Cancel</button>
                    <button wire:click="processItem" wire:loading.attr="disabled" class="flex-1 inline-flex items-center justify-center rounded-btn bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50">
                        <span wire:loading.remove wire:target="processItem">Credit Wallet</span>
                        <span wire:loading wire:target="processItem">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($rejectingItemId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="cancelReject">
            <div class="bg-surface rounded-card shadow-elevation-3 w-full max-w-md mx-4 p-6 space-y-5" @click.stop>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-text-primary">Reject Payout Credit</h3>
                    <button wire:click="cancelReject" class="text-text-secondary hover:text-text-primary transition-colors">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Reason (optional)</label>
                    <textarea wire:model="rejectNote" rows="3" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Why is this being rejected?"></textarea>
                </div>

                <div class="flex gap-3">
                    <button wire:click="cancelReject" class="flex-1 inline-flex items-center justify-center rounded-btn border border-border px-4 py-3 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">Cancel</button>
                    <button wire:click="rejectItem" wire:loading.attr="disabled" class="flex-1 inline-flex items-center justify-center rounded-btn bg-danger px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50">
                        <span wire:loading.remove wire:target="rejectItem">Reject</span>
                        <span wire:loading wire:target="rejectItem">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
