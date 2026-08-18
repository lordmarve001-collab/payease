<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">KYC Queue</h1>
            <p class="text-text-secondary text-sm">Review and verify user identification documents. Submissions from Ajo Owners and customers appear here.</p>
        </div>
        <div class="flex gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-700">
                {{ $pendingCount }} Pending
            </span>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach(['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label)
            <button
                wire:click="$set('statusFilter', '{{ $value }}')"
                class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors {{ $statusFilter === $value ? 'bg-primary-light text-primary border-primary/30' : 'bg-surface text-text-secondary border-border hover:bg-background' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <x-data-table title="Verification Queue" searchPlaceholder="Search user name or phone..." :filters="['status']" :paginator="$documents">
        <x-slot:header>
            <th class="px-4 py-3 font-medium text-left">User</th>
            <th class="px-4 py-3 font-medium text-left">Document Type</th>
            <th class="px-4 py-3 font-medium text-left">Details</th>
            <th class="px-4 py-3 font-medium text-left">Current Tier</th>
            <th class="px-4 py-3 font-medium text-left">Submitted</th>
            <th class="px-4 py-3 font-medium text-left">Status</th>
            <th class="px-4 py-3 font-medium text-right min-w-[180px]">Actions</th>
        </x-slot:header>

        @forelse($documents as $kyc)
            @php
                $docValue = match($kyc->document_type) {
                    'nin' => str_starts_with($kyc->document_url ?? '', 'nin:')
                        ? substr($kyc->document_url, 4)
                        : 'NIN document',
                    'bvn' => str_starts_with($kyc->document_url ?? '', 'bvn:')
                        ? substr($kyc->document_url, 4)
                        : 'BVN document',
                    default => $kyc->document_url ? 'File uploaded' : '—',
                };
            @endphp
            <tr class="hover:bg-background hover:shadow-elevation-1 transition-all group">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary-light text-primary flex items-center justify-center font-bold text-xs">
                            {{ substr($kyc->user?->full_name ?? '?', 0, 1) }}
                        </div>
                        <div>
                            <span class="font-bold text-text-primary whitespace-nowrap">{{ $kyc->user?->full_name ?? 'N/A' }}</span>
                            <p class="text-xs text-text-secondary">{{ $kyc->user?->phone_number }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-text-primary font-medium whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <x-lucide-file-text class="w-4 h-4 text-text-secondary" />
                        {{ str_replace('_', ' ', ucfirst($kyc->document_type)) }}
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">
                    @if($kyc->document_type === 'nin' || $kyc->document_type === 'bvn')
                        <span class="font-mono text-xs">{{ $docValue }}</span>
                    @else
                        {{ $docValue }}
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-text-secondary border border-border whitespace-nowrap">
                        Tier {{ $kyc->user?->kyc_level ?? 0 }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $kyc->created_at->format('d M, h:i A') }}</td>
                <td class="px-4 py-3">
                    <x-status-badge :status="strtolower($kyc->verification_status)" />
                    @if($kyc->rejection_reason && strtolower($kyc->verification_status) === 'rejected')
                        <p class="text-xs text-red-600 mt-1 max-w-[200px] truncate" title="{{ $kyc->rejection_reason }}">{{ $kyc->rejection_reason }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button class="p-1.5 text-text-secondary hover:text-primary transition-colors bg-surface rounded shadow-sm border border-border" title="View Details" wire:click="viewVerificationDetails('{{ $kyc->id }}')">
                            <x-lucide-eye class="w-4 h-4" />
                        </button>
                        @if(in_array(strtolower($kyc->verification_status), ['pending']))
                            <button class="px-3 py-1.5 text-xs font-bold text-white bg-primary hover:bg-primary-dark transition-colors rounded shadow-sm" wire:click="confirmDocumentAction('{{ $kyc->id }}', 'approve')">
                                Approve
                            </button>
                            <button class="px-3 py-1.5 text-xs font-bold text-white bg-danger hover:bg-red-700 transition-colors rounded shadow-sm" wire:click="confirmDocumentAction('{{ $kyc->id }}', 'reject')">
                                Reject
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-text-secondary">No KYC documents found.</td>
            </tr>
        @endforelse
    </x-data-table>

    @if($showActionModal && $selectedDocument)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4">
            <div class="w-full max-w-lg rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden">
                <div class="p-5 border-b border-border">
                    <h3 class="text-lg font-bold text-text-primary">{{ $pendingAction === 'approve' ? 'Approve KYC' : 'Reject KYC' }}</h3>
                    <p class="text-sm text-text-secondary mt-1">
                        {{ $pendingAction === 'approve' ? 'This will increment the user KYC level and verification timestamp.' : 'A rejection reason is required. The user will be able to re-submit.' }}
                    </p>
                </div>
                <div class="p-5 space-y-4">
                    <div class="bg-background rounded-card p-4 border border-border text-sm space-y-2">
                        <div class="flex justify-between">
                            <span class="text-text-secondary">User</span>
                            <span class="font-medium text-text-primary">{{ $selectedDocument->user?->full_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-secondary">Phone</span>
                            <span class="font-medium text-text-primary">{{ $selectedDocument->user?->phone_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-secondary">Document</span>
                            <span class="font-medium text-text-primary">{{ str_replace('_', ' ', ucfirst($selectedDocument->document_type)) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-secondary">Current Tier</span>
                            <span class="font-medium text-text-primary">Tier {{ $selectedDocument->user?->kyc_level ?? 0 }}</span>
                        </div>
                        @php
                            $progressUser = $selectedDocument->user;
                        @endphp
                        @if($progressUser)
                        <div class="pt-2 border-t border-border mt-2">
                            <span class="text-text-secondary block mb-2">Tier 2 Progress</span>
                            <div class="flex gap-2 text-xs">
                                <span class="px-2 py-1 rounded {{ filled($progressUser->nin_verified_at) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-text-secondary' }}">NIN {{ filled($progressUser->nin_verified_at) ? '✓' : '○' }}</span>
                                <span class="px-2 py-1 rounded {{ filled($progressUser->bvn_verified_at) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-text-secondary' }}">BVN {{ filled($progressUser->bvn_verified_at) ? '✓' : '○' }}</span>
                                <span class="px-2 py-1 rounded {{ filled($progressUser->next_of_kin_submitted_at) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-text-secondary' }}">NoK {{ filled($progressUser->next_of_kin_submitted_at) ? '✓' : '○' }}</span>
                            </div>
                        </div>
                        @endif
                        @if(in_array($selectedDocument->document_type, ['nin', 'bvn']))
                        <div class="flex justify-between">
                            <span class="text-text-secondary">{{ strtoupper($selectedDocument->document_type) }}</span>
                            <span class="font-medium text-text-primary font-mono">
                                {{ str_starts_with($selectedDocument->document_url ?? '', $selectedDocument->document_type . ':')
                                    ? substr($selectedDocument->document_url, strlen($selectedDocument->document_type) + 1)
                                    : 'N/A' }}
                            </span>
                        </div>
                        @endif
                    </div>
                    @if($pendingAction === 'reject')
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea wire:model="rejectionReason" rows="3" class="w-full rounded-card border border-border px-4 py-3 outline-none focus:border-danger focus:ring-danger text-sm" placeholder="e.g. NIN does not match user name, blurry document, expired ID..."></textarea>
                            @error('rejectionReason') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>
                <div class="p-4 border-t border-border bg-background/40 flex justify-end gap-3">
                    <x-button variant="secondary" wire:click="closeActionModal" class="bg-surface">Cancel</x-button>
                    <x-button variant="{{ $pendingAction === 'approve' ? 'primary' : 'danger' }}" wire:click="runDocumentAction" wire:loading.attr="disabled">
                        {{ $pendingAction === 'approve' ? 'Approve KYC' : 'Reject KYC' }}
                    </x-button>
                </div>
            </div>
        </div>
    @endif

    @if($showDetailModal && $detailDocument)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4">
        <div class="w-full max-w-2xl rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="p-5 border-b border-border flex items-center justify-between">
                <h3 class="text-lg font-bold text-text-primary">KYC Verification Details</h3>
                <button wire:click="closeDetailModal" class="p-1 text-text-secondary hover:text-text-primary transition-colors">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>
            <div class="p-5 space-y-5">
                <!-- User Info -->
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-text-secondary block mb-0.5">Full Name</span>
                        <span class="font-medium text-text-primary">{{ $detailDocument->user?->full_name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-text-secondary block mb-0.5">Phone</span>
                        <span class="font-medium text-text-primary">{{ $detailDocument->user?->phone_number ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-text-secondary block mb-0.5">Email</span>
                        <span class="font-medium text-text-primary">{{ $detailDocument->user?->email ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-text-secondary block mb-0.5">Current KYC Tier</span>
                        <span class="font-medium text-text-primary">Tier {{ $detailDocument->user?->kyc_level ?? 0 }}</span>
                    </div>
                </div>

                <!-- Tier 2 Progress -->
                @php
                    $user = $detailDocument->user;
                @endphp
                @if($user)
                <div class="border-t border-border pt-4">
                    <h4 class="font-semibold text-text-primary mb-3">Tier 2 Progress</h4>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-lg border border-border p-3 {{ filled($user->nin_verified_at) ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-background' }}">
                            <span class="text-xs text-text-secondary block mb-1">NIN</span>
                            <span class="font-medium {{ filled($user->nin_verified_at) ? 'text-green-700 dark:text-green-300' : 'text-text-primary' }}">
                                {{ filled($user->nin_verified_at) ? '✓ Verified' : 'Pending' }}
                            </span>
                        </div>
                        <div class="rounded-lg border border-border p-3 {{ filled($user->bvn_verified_at) ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-background' }}">
                            <span class="text-xs text-text-secondary block mb-1">BVN</span>
                            <span class="font-medium {{ filled($user->bvn_verified_at) ? 'text-green-700 dark:text-green-300' : 'text-text-primary' }}">
                                {{ filled($user->bvn_verified_at) ? '✓ Verified' : 'Pending' }}
                            </span>
                        </div>
                        <div class="rounded-lg border border-border p-3 {{ filled($user->next_of_kin_submitted_at) ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-background' }}">
                            <span class="text-xs text-text-secondary block mb-1">Next of Kin</span>
                            <span class="font-medium {{ filled($user->next_of_kin_submitted_at) ? 'text-green-700 dark:text-green-300' : 'text-text-primary' }}">
                                {{ filled($user->next_of_kin_submitted_at) ? '✓ Submitted' : 'Pending' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Document Info -->
                <div class="border-t border-border pt-4">
                    <h4 class="font-semibold text-text-primary mb-3">Document Information</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-text-secondary block mb-0.5">Document Type</span>
                            <span class="font-medium text-text-primary">{{ str_replace('_', ' ', ucfirst($detailDocument->document_type)) }}</span>
                        </div>
                        <div>
                            <span class="text-text-secondary block mb-0.5">Status</span>
                            <x-status-badge :status="strtolower($detailDocument->verification_status)" />
                        </div>
                        <div>
                            <span class="text-text-secondary block mb-0.5">Submitted</span>
                            <span class="font-medium text-text-primary">{{ $detailDocument->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                        @if($detailDocument->verified_at)
                        <div>
                            <span class="text-text-secondary block mb-0.5">Verified At</span>
                            <span class="font-medium text-text-primary">{{ $detailDocument->verified_at->format('d M Y, h:i A') }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- NIN/BVN Value -->
                @if(in_array($detailDocument->document_type, ['nin', 'bvn']))
                <div class="border-t border-border pt-4">
                    <h4 class="font-semibold text-text-primary mb-3">{{ strtoupper($detailDocument->document_type) }} Value</h4>
                    <div class="bg-background rounded-card p-4 border border-border">
                        <span class="font-mono text-lg tracking-wider text-text-primary">
                            {{ str_starts_with($detailDocument->document_url ?? '', $detailDocument->document_type . ':')
                                ? substr($detailDocument->document_url, strlen($detailDocument->document_type) + 1)
                                : ($detailDocument->document_url ?? 'N/A') }}
                        </span>
                    </div>
                </div>
                @endif

                <!-- Document URL -->
                @php $docUrl = $detailDocument->getViewableDocumentUrl(); @endphp
                @if($docUrl)
                <div class="border-t border-border pt-4">
                    <h4 class="font-semibold text-text-primary mb-2">Document File</h4>
                    @if(str_ends_with($docUrl, '.pdf'))
                        <a href="{{ $docUrl }}" target="_blank" class="inline-flex items-center gap-2 text-primary hover:underline text-sm">
                            <x-lucide-file-text class="w-4 h-4" />
                            View PDF Document
                        </a>
                    @else
                        <img src="{{ $docUrl }}" alt="KYC Document" class="max-w-full h-auto rounded-lg border border-border" />
                    @endif
                </div>
                @endif

                <!-- Rejection Reason -->
                @if($detailDocument->rejection_reason)
                <div class="border-t border-border pt-4">
                    <h4 class="font-semibold text-text-primary mb-2">Rejection Reason</h4>
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-card p-4">
                        <p class="text-sm text-red-700 dark:text-red-400">{{ $detailDocument->rejection_reason }}</p>
                    </div>
                </div>
                @endif

                <!-- Provider Verification -->
                @if($detailDocument->verification_provider || $detailDocument->auto_verified)
                <div class="border-t border-border pt-4">
                    <h4 class="font-semibold text-text-primary mb-3">Provider Verification</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        @if($detailDocument->auto_verified)
                        <div>
                            <span class="text-text-secondary block mb-0.5">Auto-Verified</span>
                            <span class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                                <x-lucide-check-circle class="w-4 h-4" /> Yes
                            </span>
                        </div>
                        @endif
                        @if($detailDocument->verification_provider)
                        <div>
                            <span class="text-text-secondary block mb-0.5">Provider</span>
                            <span class="font-medium text-text-primary">{{ ucfirst($detailDocument->verification_provider) }}</span>
                        </div>
                        @endif
                        @if($detailDocument->verification_reference)
                        <div>
                            <span class="text-text-secondary block mb-0.5">Reference</span>
                            <span class="font-medium text-text-primary text-xs break-all">{{ $detailDocument->verification_reference }}</span>
                        </div>
                        @endif
                        @if($detailDocument->match_confidence !== null)
                        <div>
                            <span class="text-text-secondary block mb-0.5">Match Confidence</span>
                            <span class="font-medium text-text-primary">{{ $detailDocument->match_confidence }}%</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            <div class="p-4 border-t border-border bg-background/40 flex justify-end gap-3">
                @if(in_array(strtolower($detailDocument->verification_status), ['pending']))
                    <x-button variant="danger" wire:click="closeDetailModal; $wire.confirmDocumentAction('{{ $detailDocument->id }}', 'reject')">
                        Reject
                    </x-button>
                    <x-button variant="primary" wire:click="closeDetailModal; $wire.confirmDocumentAction('{{ $detailDocument->id }}', 'approve')">
                        Approve
                    </x-button>
                @endif
                <x-button variant="secondary" wire:click="closeDetailModal">Close</x-button>
            </div>
        </div>
    </div>
    @endif

</div>
