<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-8">

    <div>
        <h1 class="text-2xl font-bold text-text-primary">Float Management</h1>
        <p class="text-text-secondary text-sm">Review and process agent float top-up requests and settlement declarations.</p>
    </div>

    <!-- Pending Top-Up Requests -->
    <x-data-table title="Pending Top-Up Requests" :paginator="$pendingTopUps">
        <x-slot:header>
            <th class="px-4 py-3 font-medium text-left">Agent</th>
            <th class="px-4 py-3 font-medium text-left">Amount Requested</th>
            <th class="px-4 py-3 font-medium text-left">Reason</th>
            <th class="px-4 py-3 font-medium text-left">Date</th>
            <th class="px-4 py-3 font-medium text-right">Actions</th>
        </x-slot:header>

        @forelse($pendingTopUps as $request)
            <tr class="hover:bg-background hover:shadow-elevation-1 transition-all group">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-secondary/10 text-secondary flex items-center justify-center font-bold text-xs">
                            {{ strtoupper(substr($request->agent->business_name ?? $request->agent->user->full_name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-text-primary">{{ $request->agent->business_name ?? 'N/A' }}</p>
                            <p class="text-xs text-text-secondary">{{ $request->agent->user->phone_number }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 font-semibold text-text-primary">₦{{ number_format($request->amount_requested, 2) }}</td>
                <td class="px-4 py-3 text-sm text-text-secondary max-w-xs truncate">{{ $request->reason ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-text-secondary">{{ $request->created_at->format('M j, Y g:ia') }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button wire:click="confirmTopUp('{{ $request->id }}', 'approve')" class="px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-800 text-xs font-semibold rounded-btn transition-colors">
                            Approve
                        </button>
                        <button wire:click="confirmTopUp('{{ $request->id }}', 'reject')" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-800 text-xs font-semibold rounded-btn transition-colors">
                            Reject
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-12 text-center text-text-secondary">No pending top-up requests.</td></tr>
        @endforelse
    </x-data-table>

    <!-- Pending Settlements -->
    <x-data-table title="Pending Settlements" :paginator="$pendingSettlements">
        <x-slot:header>
            <th class="px-4 py-3 font-medium text-left">Agent</th>
            <th class="px-4 py-3 font-medium text-left">Amount Declared</th>
            <th class="px-4 py-3 font-medium text-left">Bank Reference</th>
            <th class="px-4 py-3 font-medium text-left">Proof</th>
            <th class="px-4 py-3 font-medium text-left">Date</th>
            <th class="px-4 py-3 font-medium text-right">Actions</th>
        </x-slot:header>

        @forelse($pendingSettlements as $settlement)
            <tr class="hover:bg-background hover:shadow-elevation-1 transition-all group">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-secondary/10 text-secondary flex items-center justify-center font-bold text-xs">
                            {{ strtoupper(substr($settlement->agent->business_name ?? $settlement->agent->user->full_name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-text-primary">{{ $settlement->agent->business_name ?? 'N/A' }}</p>
                            <p class="text-xs text-text-secondary">{{ $settlement->agent->user->phone_number }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 font-semibold text-text-primary">₦{{ number_format($settlement->amount_declared, 2) }}</td>
                <td class="px-4 py-3 text-sm text-text-secondary">{{ $settlement->bank_reference ?? '—' }}</td>
                <td class="px-4 py-3">
                    @if($settlement->proof_of_deposit_url)
                        <a href="{{ asset('storage/' . $settlement->proof_of_deposit_url) }}" target="_blank" class="text-secondary hover:text-secondary/80 text-sm font-medium underline">View Proof</a>
                    @else
                        <span class="text-xs text-text-secondary">None</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary">{{ $settlement->created_at->format('M j, Y g:ia') }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button wire:click="confirmSettlement('{{ $settlement->id }}', 'verify')" class="px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-800 text-xs font-semibold rounded-btn transition-colors">
                            Verify
                        </button>
                        <button wire:click="confirmSettlement('{{ $settlement->id }}', 'reject')" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-800 text-xs font-semibold rounded-btn transition-colors">
                            Reject
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-12 text-center text-text-secondary">No pending settlements.</td></tr>
        @endforelse
    </x-data-table>

    <!-- Approve/Reject Top-Up Modal -->
    @if($showTopUpModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-init="$dispatch('open-modal')">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeTopUpModal"></div>
            <div class="relative bg-surface rounded-card shadow-elevation-3 p-6 w-full max-w-md border border-border z-10">
                <h3 class="text-lg font-bold text-text-primary mb-4">
                    {{ $topUpAction === 'approve' ? 'Approve' : 'Reject' }} Top-Up Request
                </h3>
                <p class="text-sm text-text-secondary mb-4">
                    {{ $topUpAction === 'approve' ? 'This will increase the agent\'s float balance by the requested amount.' : 'Provide a reason for rejecting this top-up request.' }}
                </p>

                @if($topUpAction === 'reject')
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Rejection Reason</label>
                        <textarea wire:model="rejectionReason" rows="3" class="block w-full px-4 py-2.5 border border-border rounded-btn bg-background text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" placeholder="Explain why this request was rejected..."></textarea>
                        @error('rejectionReason') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3">
                    <button wire:click="closeTopUpModal" class="px-4 py-2 text-sm font-medium text-text-secondary hover:text-text-primary transition-colors">Cancel</button>
                    <button wire:click="processTopUp" class="px-4 py-2 text-sm font-semibold text-white rounded-btn transition-colors {{ $topUpAction === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}" wire:loading.attr="disabled">
                        {{ $topUpAction === 'approve' ? 'Confirm Approval' : 'Confirm Rejection' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Verify/Reject Settlement Modal -->
    @if($showSettlementModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-init="$dispatch('open-modal')">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeSettlementModal"></div>
            <div class="relative bg-surface rounded-card shadow-elevation-3 p-6 w-full max-w-md border border-border z-10">
                <h3 class="text-lg font-bold text-text-primary mb-4">
                    {{ $settlementAction === 'verify' ? 'Verify' : 'Reject' }} Settlement
                </h3>
                <p class="text-sm text-text-secondary mb-4">
                    {{ $settlementAction === 'verify' ? 'This will decrease the agent\'s float balance by the declared amount.' : 'Provide a reason for rejecting this settlement.' }}
                </p>

                @if($settlementAction === 'reject')
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Rejection Reason</label>
                        <textarea wire:model="rejectionReason" rows="3" class="block w-full px-4 py-2.5 border border-border rounded-btn bg-background text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" placeholder="Explain why this settlement was rejected..."></textarea>
                        @error('rejectionReason') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3">
                    <button wire:click="closeSettlementModal" class="px-4 py-2 text-sm font-medium text-text-secondary hover:text-text-primary transition-colors">Cancel</button>
                    <button wire:click="processSettlement" class="px-4 py-2 text-sm font-semibold text-white rounded-btn transition-colors {{ $settlementAction === 'verify' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}" wire:loading.attr="disabled">
                        {{ $settlementAction === 'verify' ? 'Confirm Verification' : 'Confirm Rejection' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
