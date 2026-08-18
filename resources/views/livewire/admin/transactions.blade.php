<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Transactions</h1>
            <p class="text-text-secondary text-sm">System-wide transaction ledger and compliance monitoring.</p>
        </div>
        <div class="inline-flex items-center px-3 py-2 rounded-btn bg-primary-light text-primary text-sm font-semibold">
            {{ $transactions->total() }} transactions
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach(['all' => 'All Statuses', 'completed' => 'Completed', 'pending' => 'Pending', 'failed' => 'Failed', 'reversed' => 'Reversed'] as $value => $label)
            <button
                wire:click="$set('statusFilter', '{{ $value }}')"
                class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors {{ $statusFilter === $value ? 'bg-primary-light text-primary border-primary/30' : 'bg-surface text-text-secondary border-border hover:bg-background' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach(['all' => 'All Types', 'transfer' => 'Transfer', 'deposit' => 'Cash In', 'withdrawal' => 'Cash Out', 'reversal' => 'Reversal'] as $value => $label)
            <button
                wire:click="$set('typeFilter', '{{ $value }}')"
                class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors {{ $typeFilter === $value ? 'bg-secondary/10 text-secondary border-secondary/30' : 'bg-surface text-text-secondary border-border hover:bg-background' }}"
            >
                {{ $label }}
            </button>
        @endforeach
        @foreach(['all' => 'All Dates', '24h' => 'Last 24h', 'today' => 'Today'] as $value => $label)
            <button
                wire:click="$set('dateFilter', '{{ $value }}')"
                class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors {{ $dateFilter === $value ? 'bg-orange-100 text-orange-700 border-orange-300' : 'bg-surface text-text-secondary border-border hover:bg-background' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <x-data-table title="Transaction Ledger" searchPlaceholder="Search reference, user, agent..." :filters="['type', 'status', 'date']" :paginator="$transactions">
        <x-slot:header>
            <th class="px-4 py-3 font-medium text-left">Reference</th>
            <th class="px-4 py-3 font-medium text-left">Type</th>
            <th class="px-4 py-3 font-medium text-left">Amount</th>
            <th class="px-4 py-3 font-medium text-left">Status</th>
            <th class="px-4 py-3 font-medium text-left">Parties</th>
            <th class="px-4 py-3 font-medium text-left">Date & Time</th>
            <th class="px-4 py-3 font-medium text-right">Actions</th>
        </x-slot:header>

        @forelse($transactions as $tx)
            @php
                $canReverse = strtolower($tx->status) === 'completed'
                    && strtolower($tx->transaction_type) !== 'reversal'
                    && (($tx->completed_at ?? $tx->created_at)->gte(now()->subDay()));
                $partyLabel = $tx->fromWallet?->user?->full_name
                    ?? $tx->toWallet?->user?->full_name
                    ?? $tx->agentUser?->full_name
                    ?? $tx->recipient_phone
                    ?? '-';
            @endphp
            <tr class="hover:bg-background hover:shadow-elevation-1 transition-all group">
                <td class="px-4 py-3 font-mono text-xs text-text-primary whitespace-nowrap">{{ $tx->reference }}</td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ ucfirst($tx->transaction_type) }}</td>
                <td class="px-4 py-3 font-bold text-text-primary tabular-nums whitespace-nowrap">₦{{ number_format($tx->amount, 2) }}</td>
                <td class="px-4 py-3">
                    <x-status-badge :status="strtolower($tx->status)" />
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary">
                    <div class="font-medium text-text-primary">{{ $partyLabel }}</div>
                    <div class="text-xs text-text-secondary">{{ $tx->agentUser?->full_name ? 'Agent: ' . $tx->agentUser->full_name : ($tx->mmo_partner ?? '-') }}</div>
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $tx->created_at->format('d M, h:i A') }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button wire:click="viewTransaction('{{ $tx->id }}')" class="p-1.5 text-text-secondary hover:text-primary transition-colors bg-surface rounded shadow-sm border border-border" title="View Details">
                            <x-lucide-eye class="w-4 h-4" />
                        </button>
                        @if($canReverse)
                            <button wire:click="openReverseModal('{{ $tx->id }}')" class="px-2 py-1 text-xs font-medium text-danger bg-red-100 hover:bg-red-200 transition-colors rounded" title="Reverse Transaction">
                                Reverse
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-text-secondary">No transactions found.</td>
            </tr>
        @endforelse
    </x-data-table>

    @if($showTransactionModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4">
            <div class="w-full max-w-2xl rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden max-h-[90vh] flex flex-col">
                <div class="p-5 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-text-primary">Transaction Detail</h3>
                        <p class="text-sm text-text-secondary">{{ $selectedTransaction->reference }}</p>
                    </div>
                    <button wire:click="closeTransactionModal" class="p-2 text-text-secondary hover:text-text-primary">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 overflow-y-auto space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-background rounded-card p-4 border border-border">
                            <p class="text-xs uppercase tracking-wider text-text-secondary mb-1">Amount</p>
                            <p class="text-2xl font-bold text-text-primary">₦{{ number_format($selectedTransaction->amount, 2) }}</p>
                        </div>
                        <div class="bg-background rounded-card p-4 border border-border">
                            <p class="text-xs uppercase tracking-wider text-text-secondary mb-1">Status</p>
                            <x-status-badge :status="strtolower($selectedTransaction->status)" />
                        </div>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-text-secondary">Type</span>
                            <span class="font-medium text-text-primary">{{ ucfirst($selectedTransaction->transaction_type) }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-text-secondary">Fee</span>
                            <span class="font-medium text-text-primary">₦{{ number_format($selectedTransaction->fee, 2) }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-text-secondary">Commission</span>
                            <span class="font-medium text-text-primary">₦{{ number_format($selectedTransaction->commission, 2) }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-text-secondary">From</span>
                            <span class="font-medium text-text-primary text-right">{{ $selectedTransaction->fromWallet?->user?->full_name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-text-secondary">To</span>
                            <span class="font-medium text-text-primary text-right">{{ $selectedTransaction->toWallet?->user?->full_name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-text-secondary">Agent</span>
                            <span class="font-medium text-text-primary text-right">{{ $selectedTransaction->agentUser?->full_name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-text-secondary">MMO Transaction ID</span>
                            <span class="font-mono text-text-primary text-right">{{ $selectedTransaction->mmo_transaction_id ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-text-secondary">Device / IP</span>
                            <span class="font-mono text-text-primary text-right">{{ $selectedTransaction->device_id ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-text-secondary">Location</span>
                            <span class="font-medium text-text-primary text-right">
                                {{ $selectedTransaction->latitude && $selectedTransaction->longitude ? $selectedTransaction->latitude . ', ' . $selectedTransaction->longitude : 'N/A' }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-background rounded-card p-4 border border-border">
                        <h4 class="text-sm font-semibold text-text-primary mb-3">Metadata</h4>
                        <pre class="text-xs text-text-secondary whitespace-pre-wrap break-words">{{ json_encode($selectedTransaction->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
                <div class="p-4 border-t border-border bg-background/40 flex justify-end gap-3">
                    <x-button variant="secondary" wire:click="closeTransactionModal" class="bg-surface">Close</x-button>
                    @php
                        $canReverseSelected = strtolower($selectedTransaction->status) === 'completed'
                            && strtolower($selectedTransaction->transaction_type) !== 'reversal'
                            && (($selectedTransaction->completed_at ?? $selectedTransaction->created_at)->gte(now()->subDay()));
                    @endphp
                    @if($canReverseSelected)
                        <x-button variant="danger" wire:click="openReverseModal('{{ $selectedTransaction->id }}')">Reverse Transaction</x-button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($showReverseModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4">
            <div class="w-full max-w-lg rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden">
                <div class="p-5 border-b border-border">
                    <h3 class="text-lg font-bold text-text-primary">Reverse Transaction</h3>
                    <p class="text-sm text-text-secondary mt-1">This will create a separate reversal record and mark the original transaction as reversed.</p>
                </div>
                <div class="p-5 space-y-4">
                    <div class="bg-background rounded-card p-4 border border-border text-sm space-y-2">
                        <div class="flex justify-between">
                            <span class="text-text-secondary">Reference</span>
                            <span class="font-mono text-text-primary">{{ $selectedTransaction->reference }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-secondary">Amount</span>
                            <span class="font-semibold text-text-primary">₦{{ number_format($selectedTransaction->amount, 2) }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Reason</label>
                        <textarea wire:model="reversalReason" rows="4" class="w-full rounded-card border border-border px-4 py-3 outline-none focus:border-primary focus:ring-primary" placeholder="Why is this transaction being reversed?"></textarea>
                        @error('reversalReason') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="p-4 border-t border-border bg-background/40 flex justify-end gap-3">
                    <x-button variant="secondary" wire:click="closeReverseModal" class="bg-surface">Cancel</x-button>
                    <x-button variant="danger" wire:click="reverseSelectedTransaction">Confirm Reversal</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
