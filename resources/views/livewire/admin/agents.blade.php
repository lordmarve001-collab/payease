<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Agents</h1>
            <p class="text-text-secondary text-sm">Manage agent network, float balances, and approvals.</p>
        </div>
        <div class="inline-flex items-center px-3 py-2 rounded-btn bg-primary-light text-primary text-sm font-semibold">
            {{ $agents->total() }} agents
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach(['all' => 'All', 'active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended'] as $value => $label)
            <button
                wire:click="$set('statusFilter', '{{ $value }}')"
                class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors {{ $statusFilter === $value ? 'bg-primary-light text-primary border-primary/30' : 'bg-surface text-text-secondary border-border hover:bg-background' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <x-data-table title="Agent Network" searchPlaceholder="Search business name, owner..." :filters="['status']" :paginator="$agents">
        <x-slot:header>
            <th class="px-4 py-3 font-medium text-left">Business Name</th>
            <th class="px-4 py-3 font-medium text-left">Owner</th>
            <th class="px-4 py-3 font-medium text-left">Location</th>
            <th class="px-4 py-3 font-medium text-left">Float Balance</th>
            <th class="px-4 py-3 font-medium text-left">Status</th>
            <th class="px-4 py-3 font-medium text-left">Approved</th>
            <th class="px-4 py-3 font-medium text-left">Last Settlement</th>
            <th class="px-4 py-3 font-medium text-right">Actions</th>
        </x-slot:header>

        @forelse($agents as $agent)
            <tr class="hover:bg-background hover:shadow-elevation-1 transition-all group">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded bg-secondary/10 text-secondary flex items-center justify-center font-bold text-xs">
                            <x-lucide-store class="w-4 h-4" />
                        </div>
                        <span class="font-medium text-text-primary whitespace-nowrap">{{ $agent->business_name }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $agent->user?->full_name ?? 'N/A' }}</td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ trim(($agent->lga ?? '') . ', ' . ($agent->state ?? ''), ', ') ?: 'N/A' }}</td>
                <td class="px-4 py-3 font-medium text-text-primary tabular-nums whitespace-nowrap">₦{{ number_format($agent->float_balance, 2) }}</td>
                <td class="px-4 py-3">
                    <x-status-badge :status="strtolower($agent->status ?? 'Active')" />
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $agent->approved_at?->format('d M Y') ?? 'Not yet' }}</td>
                <td class="px-4 py-3 text-sm whitespace-nowrap">
                    @php
                        $daysSinceSettlement = $agent->last_settlement_at ? (int) $agent->last_settlement_at->diffInDays(now()) : null;
                        $isOverdue = $daysSinceSettlement !== null && $daysSinceSettlement >= $agent->settlement_frequency_days;
                    @endphp
                    <span class="{{ $isOverdue ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-text-secondary' }}">
                        {{ $agent->last_settlement_at?->format('d M Y') ?? 'Never' }}
                        @if($isOverdue)
                            <span class="ml-1 text-xs">({{ $daysSinceSettlement }}d overdue)</span>
                        @endif
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    @if(strtolower($agent->status ?? 'Active') === 'pending')
                        <div class="flex justify-end gap-2">
                            <button class="px-2 py-1 text-xs font-medium text-primary bg-primary-light hover:bg-primary/20 transition-colors rounded" wire:click="confirmAgentAction('{{ $agent->id }}', 'approve')">
                                Approve
                            </button>
                            <button class="px-2 py-1 text-xs font-medium text-danger bg-red-100 hover:bg-red-200 transition-colors rounded" wire:click="confirmAgentAction('{{ $agent->id }}', 'suspend')">
                                Suspend
                            </button>
                        </div>
                    @else
                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="p-1.5 text-text-secondary hover:text-primary transition-colors bg-surface rounded shadow-sm border border-border" title="View Details" wire:click="viewAgent('{{ $agent->id }}')">
                                <x-lucide-eye class="w-4 h-4" />
                            </button>
                            @if(strtolower($agent->status ?? 'active') !== 'suspended')
                                <button class="px-2 py-1 text-xs font-medium text-danger bg-red-100 hover:bg-red-200 transition-colors rounded" wire:click="confirmAgentAction('{{ $agent->id }}', 'suspend')">
                                    Suspend
                                </button>
                            @endif
                        </div>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-text-secondary">No agents found.</td>
            </tr>
        @endforelse
    </x-data-table>

    @if($showActionModal && $selectedAgent)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4">
            <div class="w-full max-w-md rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden">
                <div class="p-5 border-b border-border">
                    <h3 class="text-lg font-bold text-text-primary">{{ $pendingAction === 'approve' ? 'Approve Agent' : 'Suspend Agent' }}</h3>
                    <p class="text-sm text-text-secondary mt-1">
                        {{ $pendingAction === 'approve' ? 'This will unlock Cash In and Cash Out for the agent.' : 'This will immediately block Cash In and Cash Out for the agent.' }}
                    </p>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Business</span>
                        <span class="font-medium text-text-primary">{{ $selectedAgent->business_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Owner</span>
                        <span class="font-medium text-text-primary">{{ $selectedAgent->user?->full_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Status</span>
                        <span class="font-medium text-text-primary">{{ ucfirst($selectedAgent->status) }}</span>
                    </div>
                </div>
                <div class="p-4 border-t border-border flex justify-end gap-3 bg-background/40">
                    <x-button variant="secondary" wire:click="closeActionModal" class="bg-surface">Cancel</x-button>
                    <x-button variant="{{ $pendingAction === 'approve' ? 'primary' : 'danger' }}" wire:click="runAgentAction">
                        {{ $pendingAction === 'approve' ? 'Approve Agent' : 'Suspend Agent' }}
                    </x-button>
                </div>
            </div>
        </div>
    @endif

    @if($pendingDeletions->isNotEmpty())
        <div class="bg-surface rounded-card border border-red-200 dark:border-red-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                <div class="flex items-center gap-2">
                    <x-lucide-alert-triangle class="w-5 h-5 text-red-600" />
                    <h2 class="font-bold text-red-800 dark:text-red-200">Pending Deletion Requests</h2>
                    <span class="ml-auto text-xs font-medium px-2 py-0.5 rounded-full bg-red-200 text-red-800 dark:bg-red-800 dark:text-red-200">{{ $pendingDeletions->count() }}</span>
                </div>
            </div>
            <div class="divide-y divide-border">
                @foreach($pendingDeletions as $delRequest)
                    <div class="p-5 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-semibold text-text-primary">{{ $delRequest->agent?->business_name ?? 'Unknown Agent' }}</p>
                            <p class="text-sm text-text-secondary">Requested by {{ $delRequest->requestedBy?->full_name ?? 'Unknown' }}</p>
                            <p class="text-sm text-text-secondary mt-0.5">Reason: {{ $delRequest->reason }}</p>
                            <p class="text-xs text-text-secondary mt-0.5">{{ $delRequest->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button class="px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 transition-colors rounded" wire:click="confirmDeletionRequest('{{ $delRequest->id }}', 'approve')">
                                Approve
                            </button>
                            <button class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 transition-colors rounded" wire:click="confirmDeletionRequest('{{ $delRequest->id }}', 'reject')">
                                Reject
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($showDeletionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4">
            <div class="w-full max-w-md rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden">
                <div class="p-5 border-b border-border">
                    <h3 class="text-lg font-bold text-text-primary">{{ $pendingAction === 'approve' ? 'Approve Deletion' : 'Reject Deletion' }}</h3>
                    <p class="text-sm text-text-secondary mt-1">
                        {{ $pendingAction === 'approve' ? 'This will permanently remove the agent and suspend their account.' : 'This will reject the deletion request and the agent will remain active.' }}
                    </p>
                </div>
                <div class="p-5 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Admin Notes (optional)</label>
                        <textarea wire:model="deletionAdminNotes" rows="2" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-2.5 focus:ring-purple-600 focus:border-purple-600 outline-none text-sm" placeholder="Add notes about this decision..."></textarea>
                    </div>
                </div>
                <div class="p-4 border-t border-border flex justify-end gap-3 bg-background/40">
                    <x-button variant="secondary" wire:click="closeDeletionModal">Cancel</x-button>
                    <x-button variant="{{ $pendingAction === 'approve' ? 'primary' : 'danger' }}" wire:click="processDeletionRequest" wire:target="processDeletionRequest" wire:loading.attr="disabled">
                        {{ $pendingAction === 'approve' ? 'Approve Deletion' : 'Reject Deletion' }}
                    </x-button>
                </div>
            </div>
        </div>
    @endif

    @if($showViewModal && $viewingAgent)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4" wire:click.self="closeViewModal">
            <div class="w-full max-w-lg rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden">
                <div class="p-5 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-text-primary">{{ $viewingAgent->business_name }}</h3>
                        <p class="text-sm text-text-secondary">{{ $viewingAgent->user?->full_name ?? 'N/A' }}</p>
                    </div>
                    <button wire:click="closeViewModal" class="p-1.5 text-text-secondary hover:text-text-primary transition-colors rounded-lg hover:bg-background">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4 text-sm max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-text-secondary text-xs font-medium uppercase tracking-wide">Status</span>
                            <div class="mt-1"><x-status-badge :status="strtolower($viewingAgent->status ?? 'Active')" /></div>
                        </div>
                        <div>
                            <span class="text-text-secondary text-xs font-medium uppercase tracking-wide">Approved</span>
                            <p class="mt-1 font-medium text-text-primary">{{ $viewingAgent->approved_at?->format('d M Y H:i') ?? 'Not yet' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-text-secondary text-xs font-medium uppercase tracking-wide">Phone</span>
                            <p class="mt-1 font-medium text-text-primary">{{ $viewingAgent->user?->phone_number ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-text-secondary text-xs font-medium uppercase tracking-wide">Email</span>
                            <p class="mt-1 font-medium text-text-primary">{{ $viewingAgent->user?->email ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div>
                        <span class="text-text-secondary text-xs font-medium uppercase tracking-wide">Business Address</span>
                        <p class="mt-1 font-medium text-text-primary">{{ $viewingAgent->business_address ?: 'N/A' }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-text-secondary text-xs font-medium uppercase tracking-wide">LGA / State</span>
                            <p class="mt-1 font-medium text-text-primary">{{ trim(($viewingAgent->lga ?? '') . ', ' . ($viewingAgent->state ?? ''), ', ') ?: 'N/A' }}</p>
                        </div>
                        @if($viewingAgent->ajoOwner)
                            <div>
                                <span class="text-text-secondary text-xs font-medium uppercase tracking-wide">Ajo Owner</span>
                                <p class="mt-1 font-medium text-text-primary">{{ $viewingAgent->ajoOwner->business_name ?? 'N/A' }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="border-t border-border pt-4">
                        <h4 class="text-xs font-semibold text-text-secondary uppercase tracking-wide mb-3">Financials</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <span class="text-text-secondary text-xs">Float Balance</span>
                                <p class="mt-1 font-bold text-text-primary tabular-nums">₦{{ number_format($viewingAgent->float_balance, 2) }}</p>
                            </div>
                            <div>
                                <span class="text-text-secondary text-xs">Max Float</span>
                                <p class="mt-1 font-bold text-text-primary tabular-nums">₦{{ number_format($viewingAgent->max_float, 2) }}</p>
                            </div>
                            <div>
                                <span class="text-text-secondary text-xs">Commission Rate</span>
                                <p class="mt-1 font-bold text-text-primary tabular-nums">{{ number_format($viewingAgent->commission_rate, 2) }}%</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="text-text-secondary text-xs">Total Earnings</span>
                            <p class="mt-1 font-bold text-primary tabular-nums">₦{{ number_format($viewingAgent->total_earnings, 2) }}</p>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-text-secondary text-xs">Last Settlement</span>
                                <p class="mt-1 font-medium text-text-primary tabular-nums">{{ $viewingAgent->last_settlement_at?->format('d M Y H:i') ?? 'Never' }}</p>
                            </div>
                            <div>
                                <span class="text-text-secondary text-xs">Settlement Cadence</span>
                                <p class="mt-1 font-medium text-text-primary">
                                    Every {{ $viewingAgent->settlement_frequency_days }} {{ Str::plural('day', $viewingAgent->settlement_frequency_days) }}
                                </p>
                            </div>
                        </div>
                        @php
                            $daysSinceSettlement = $viewingAgent->last_settlement_at ? (int) $viewingAgent->last_settlement_at->diffInDays(now()) : PHP_INT_MAX;
                            $isOverdue = $daysSinceSettlement >= $viewingAgent->settlement_frequency_days;
                        @endphp
                        @if($isOverdue)
                            <div class="mt-3 rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
                                <p class="text-xs text-amber-700 dark:text-amber-300">
                                    <span class="font-semibold">Settlement overdue.</span>
                                    {{ $viewingAgent->last_settlement_at ? 'Last settlement was ' . $daysSinceSettlement . ' ' . Str::plural('day', $daysSinceSettlement) . ' ago.' : 'No settlement recorded yet.' }}
                                </p>
                            </div>
                        @endif
                    </div>

                    @if($viewingAgent->assignedGroups->isNotEmpty())
                        <div class="border-t border-border pt-4">
                            <h4 class="text-xs font-semibold text-text-secondary uppercase tracking-wide mb-2">Assigned Ajo Groups ({{ $viewingAgent->assignedGroups->count() }})</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($viewingAgent->assignedGroups as $group)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-background border border-border text-xs font-medium text-text-primary">
                                        {{ $group->name }}
                                        <span class="text-text-secondary">({{ $group->pivot->role }})</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="p-4 border-t border-border flex justify-end bg-background/40">
                    <x-button variant="secondary" wire:click="closeViewModal">Close</x-button>
                </div>
            </div>
        </div>
    @endif

</div>
