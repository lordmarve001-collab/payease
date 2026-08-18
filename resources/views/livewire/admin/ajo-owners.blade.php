<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Ajo Owners</h1>
            <p class="text-text-secondary text-sm">Manage Ajo Owner applications and active owners.</p>
        </div>
        <div class="inline-flex items-center px-3 py-2 rounded-btn bg-primary-light text-primary text-sm font-semibold">
            {{ $owners->total() }} owners
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'active' => 'Active', 'rejected' => 'Rejected', 'suspended' => 'Suspended'] as $value => $label)
            <button
                wire:click="$set('statusFilter', '{{ $value }}')"
                class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors {{ $statusFilter === $value ? 'bg-primary-light text-primary border-primary/30' : 'bg-surface text-text-secondary border-border hover:bg-background' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <x-data-table title="Ajo Owners" searchPlaceholder="Search business name, owner..." :filters="['status']" :paginator="$owners">
        <x-slot:header>
            <th class="px-4 py-3 font-medium text-left">Business Name</th>
            <th class="px-4 py-3 font-medium text-left">Owner</th>
            <th class="px-4 py-3 font-medium text-left">Location</th>
            <th class="px-4 py-3 font-medium text-left">Groups Planned</th>
            <th class="px-4 py-3 font-medium text-left">Status</th>
            <th class="px-4 py-3 font-medium text-left">Date Applied</th>
            <th class="px-4 py-3 font-medium text-right">Actions</th>
        </x-slot:header>

        @forelse($owners as $owner)
            <tr class="hover:bg-background hover:shadow-elevation-1 transition-all group">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded bg-secondary/10 text-secondary flex items-center justify-center font-bold text-xs">
                            <x-lucide-users-2 class="w-4 h-4" />
                        </div>
                        <span class="font-medium text-text-primary whitespace-nowrap">{{ $owner->business_name }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $owner->user?->full_name ?? 'N/A' }}</td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ trim(($owner->lga ?? '') . ', ' . ($owner->state ?? ''), ', ') ?: 'N/A' }}</td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $owner->planned_groups }} groups</td>
                <td class="px-4 py-3">
                    <x-status-badge :status="strtolower($owner->status ?? 'pending')" />
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $owner->created_at->format('d M Y') }}</td>
                <td class="px-4 py-3 text-right">
                    @if(strtolower($owner->status ?? 'pending') === 'pending')
                        <div class="flex justify-end gap-2">
                            <button class="p-1.5 text-text-secondary hover:text-primary transition-colors bg-surface rounded shadow-sm border border-border" title="View Details" wire:click="viewDetail('{{ $owner->id }}')">
                                <x-lucide-eye class="w-4 h-4" />
                            </button>
                            <button class="px-2 py-1 text-xs font-medium text-primary bg-primary-light hover:bg-primary/20 transition-colors rounded" wire:click="confirmOwnerAction('{{ $owner->id }}', 'approve')">
                                Approve
                            </button>
                            <button class="px-2 py-1 text-xs font-medium text-danger bg-red-100 hover:bg-red-200 transition-colors rounded" wire:click="confirmOwnerAction('{{ $owner->id }}', 'reject')">
                                Reject
                            </button>
                        </div>
                    @else
                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="p-1.5 text-text-secondary hover:text-primary transition-colors bg-surface rounded shadow-sm border border-border" title="View Details" wire:click="viewDetail('{{ $owner->id }}')">
                                <x-lucide-eye class="w-4 h-4" />
                            </button>
                            @if(strtolower($owner->status ?? 'active') === 'active')
                                <button class="px-2 py-1 text-xs font-medium text-danger bg-red-100 hover:bg-red-200 transition-colors rounded" wire:click="confirmOwnerAction('{{ $owner->id }}', 'suspend')">
                                    Suspend
                                </button>
                            @endif
                        </div>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-text-secondary">No Ajo Owners found.</td>
            </tr>
        @endforelse
    </x-data-table>

    <!-- Action Modal -->
    @if($showActionModal && $selectedOwner)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4">
            <div class="w-full max-w-md rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden">
                <div class="p-5 border-b border-border">
                    <h3 class="text-lg font-bold text-text-primary">
                        @switch($pendingAction)
                            @case('approve') Approve Ajo Owner @break
                            @case('reject') Reject Application @break
                            @case('suspend') Suspend Ajo Owner @break
                        @endswitch
                    </h3>
                    <p class="text-sm text-text-secondary mt-1">
                        @switch($pendingAction)
                            @case('approve') This will activate the Ajo Owner and grant dashboard access. @break
                            @case('reject') A rejection reason is required and the applicant will be notified. @break
                            @case('suspend') This will immediately block all Ajo Owner operations. @break
                        @endswitch
                    </p>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Business</span>
                        <span class="font-medium text-text-primary">{{ $selectedOwner->business_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Owner</span>
                        <span class="font-medium text-text-primary">{{ $selectedOwner->user?->full_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Status</span>
                        <span class="font-medium text-text-primary">{{ ucfirst($selectedOwner->status) }}</span>
                    </div>
                    @if($pendingAction === 'reject')
                        <div class="pt-2">
                            <label class="block text-sm font-medium text-text-primary mb-2">Rejection Reason</label>
                            <textarea wire:model="rejectionReason" rows="3" class="w-full rounded-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors" placeholder="Why is this application being rejected?"></textarea>
                            @error('rejectionReason') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>
                <div class="p-4 border-t border-border flex justify-end gap-3 bg-background/40">
                    <x-button variant="secondary" wire:click="closeActionModal" class="bg-surface">Cancel</x-button>
                    <x-button variant="{{ $pendingAction === 'approve' ? 'primary' : 'danger' }}" wire:click="runOwnerAction">
                        @switch($pendingAction)
                            @case('approve') Approve @break
                            @case('reject') Reject @break
                            @case('suspend') Suspend @break
                        @endswitch
                    </x-button>
                </div>
            </div>
        </div>
    @endif

    <!-- Detail Modal -->
    @if($showDetailModal && $detailOwner)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4">
            <div class="w-full max-w-2xl rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="p-5 border-b border-border flex items-center justify-between">
                    <h3 class="text-lg font-bold text-text-primary">Application Details</h3>
                    <button wire:click="closeDetailModal" class="p-1 text-text-secondary hover:text-text-primary transition-colors">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-text-secondary block">Business Name</span>
                            <span class="font-medium text-text-primary">{{ $detailOwner->business_name }}</span>
                        </div>
                        <div>
                            <span class="text-text-secondary block">Status</span>
                            <x-status-badge :status="strtolower($detailOwner->status ?? 'pending')" />
                        </div>
                        <div class="col-span-2">
                            <span class="text-text-secondary block">Business Description</span>
                            <p class="font-medium text-text-primary mt-1">{{ $detailOwner->business_description ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-text-secondary block">Address</span>
                            <span class="font-medium text-text-primary">{{ $detailOwner->business_address ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-text-secondary block">LGA / State</span>
                            <span class="font-medium text-text-primary">{{ trim(($detailOwner->lga ?? '') . ', ' . ($detailOwner->state ?? ''), ', ') ?: 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-text-secondary block">Experience</span>
                            <span class="font-medium text-text-primary">{{ $detailOwner->has_experience ? 'Yes, runs informal groups' : 'No prior experience' }}</span>
                        </div>
                        <div>
                            <span class="text-text-secondary block">Groups Planned</span>
                            <span class="font-medium text-text-primary">{{ $detailOwner->planned_groups }} groups, ~{{ $detailOwner->members_per_group }} members each</span>
                        </div>
                        <div>
                            <span class="text-text-secondary block">Agent Preference</span>
                            <span class="font-medium text-text-primary">
                                @switch($detailOwner->agent_assignment_preference)
                                    @case('have_agents') Has agents in mind @break
                                    @case('needs_help') Wants {{ $siteSettings->site_name ?? 'PayEase' }} to assign @break
                                    @case('not_sure') Not sure yet @break
                                    @default N/A
                                @endswitch
                            </span>
                        </div>
                        <div>
                            <span class="text-text-secondary block">Reference Contact</span>
                            <span class="font-medium text-text-primary">{{ $detailOwner->reference_contact_name ?? 'N/A' }} {{ $detailOwner->reference_contact_phone ? '(' . $detailOwner->reference_contact_phone . ')' : '' }}</span>
                        </div>
                        <div>
                            <span class="text-text-secondary block">Submitted By</span>
                            <span class="font-medium text-text-primary">{{ $detailOwner->user?->full_name ?? 'N/A' }} ({{ $detailOwner->user?->phone_number ?? '' }})</span>
                        </div>
                        <div>
                            <span class="text-text-secondary block">Submitted At</span>
                            <span class="font-medium text-text-primary">{{ $detailOwner->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                        @if($detailOwner->approved_at)
                        <div>
                            <span class="text-text-secondary block">Approved At</span>
                            <span class="font-medium text-text-primary">{{ $detailOwner->approved_at->format('d M Y, h:i A') }}</span>
                        </div>
                        @endif
                        @if($detailOwner->rejection_reason)
                        <div class="col-span-2">
                            <span class="text-text-secondary block">Rejection Reason</span>
                            <p class="text-danger mt-1">{{ $detailOwner->rejection_reason }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="border-t border-border pt-4">
                        <h4 class="font-semibold text-text-primary mb-3">Tier 2 KYC Documents</h4>
                        <p class="text-sm text-text-secondary">Applicant's identity verification was completed during Tier 2 KYC. <a href="{{ route('admin.kyc-queue') }}?search={{ urlencode($detailOwner->user?->full_name ?? '') }}" wire:navigate class="text-primary hover:underline">View in KYC Queue</a></p>
                    </div>
                </div>
                <div class="p-4 border-t border-border bg-background/40 flex justify-end">
                    <x-button variant="secondary" wire:click="closeDetailModal">Close</x-button>
                </div>
            </div>
        </div>
    @endif

</div>
