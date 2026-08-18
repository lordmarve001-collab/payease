<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Ajo Groups</h1>
            <p class="text-text-secondary text-sm">Monitor savings groups and managing agents.</p>
        </div>
        <div class="inline-flex items-center px-3 py-2 rounded-btn bg-primary-light text-primary text-sm font-semibold">
            {{ $groups->total() }} groups
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

    <x-data-table title="All Groups" searchPlaceholder="Search group name, agent..." :filters="['status']" :paginator="$groups">
        <x-slot:header>
            <th class="px-4 py-3 font-medium text-left">Group Name</th>
            <th class="px-4 py-3 font-medium text-left">Contribution</th>
            <th class="px-4 py-3 font-medium text-left">Frequency</th>
            <th class="px-4 py-3 font-medium text-center">Members</th>
            <th class="px-4 py-3 font-medium text-left">Managing Agent</th>
            <th class="px-4 py-3 font-medium text-left">Status</th>
            <th class="px-4 py-3 font-medium text-right">Actions</th>
        </x-slot:header>

        @forelse($groups as $group)
            <tr class="hover:bg-background hover:shadow-elevation-1 transition-all group">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-bold text-xs">
                            <x-lucide-users class="w-4 h-4" />
                        </div>
                        <span class="font-bold text-text-primary whitespace-nowrap">{{ $group->name }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 font-medium text-text-primary tabular-nums whitespace-nowrap">₦{{ number_format($group->contribution_amount, 2) }}</td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ ucfirst($group->frequency) }}</td>
                <td class="px-4 py-3 text-sm text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-text-secondary font-medium">{{ $group->members_count }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $group->managingAgent?->business_name ?? 'N/A' }}</td>
                <td class="px-4 py-3"><x-status-badge :status="strtolower($group->status)" /></td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button wire:click="viewGroup('{{ $group->id }}')" class="p-1.5 text-text-secondary hover:text-primary transition-colors bg-surface rounded shadow-sm border border-border" title="View Group">
                            <x-lucide-eye class="w-4 h-4" />
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-text-secondary">No Ajo groups found.</td>
            </tr>
        @endforelse
    </x-data-table>

    {{-- ================================================
         VIEW GROUP MODAL
         ================================================ --}}
    @if($showModal && $selectedGroup)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4" wire:click.self="closeModal">
        <div class="w-full max-w-2xl bg-surface rounded-card shadow-elevation-4 border border-border flex flex-col overflow-hidden max-h-[90vh]" @click.stop>

            {{-- Header --}}
            <div class="p-5 border-b border-border flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-text-primary">{{ $selectedGroup->name }}</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <x-status-badge :status="strtolower($selectedGroup->status)" />
                        <span class="text-xs text-text-secondary">{{ match($selectedGroup->model_type) { 'savings_pool' => 'Savings Pool', 'continuous_pool' => 'Continuous Pool', default => 'Rotational' } }}</span>
                        <span class="text-xs text-text-secondary">&middot; {{ ucfirst($selectedGroup->frequency) }}</span>
                    </div>
                </div>
                <button wire:click="closeModal" class="p-2 text-text-secondary hover:text-text-primary hover:bg-background rounded-lg transition-colors">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5 space-y-5 overflow-y-auto flex-1 min-h-0">

                {{-- Details --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-text-secondary block mb-0.5">Contribution</span>
                        <span class="font-semibold text-text-primary">₦{{ number_format($selectedGroup->contribution_amount, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-text-secondary block mb-0.5">Target Pool</span>
                        <span class="font-semibold text-text-primary">₦{{ number_format($selectedGroup->target_pool_amount ?? 0, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-text-secondary block mb-0.5">Members</span>
                        <span class="font-semibold text-text-primary">{{ $selectedGroup->members_count }}</span>
                    </div>
                    <div>
                        <span class="text-text-secondary block mb-0.5">Start Date</span>
                        <span class="font-semibold text-text-primary">{{ $selectedGroup->start_date?->format('d M Y') ?? 'Not set' }}</span>
                    </div>
                    <div>
                        <span class="text-text-secondary block mb-0.5">Collection End</span>
                        <span class="font-semibold text-text-primary">{{ $selectedGroup->collection_end_date?->format('d M Y') ?? 'Not set' }}</span>
                    </div>
                    <div>
                        <span class="text-text-secondary block mb-0.5">Created</span>
                        <span class="font-semibold text-text-primary">{{ $selectedGroup->created_at->format('d M Y') }}</span>
                    </div>
                </div>

                @if($selectedGroup->description)
                <div class="bg-background rounded-card p-3 border border-border text-sm text-text-secondary">{{ $selectedGroup->description }}</div>
                @endif

                {{-- Managing Agent --}}
                <div class="border-t border-border pt-4">
                    <h4 class="font-semibold text-text-primary mb-2 text-sm">Managing Agent</h4>
                    @if($selectedGroup->managingAgent)
                        <div class="bg-background rounded-card p-3 border border-border flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary-light text-primary flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr($selectedGroup->managingAgent->user?->full_name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-text-primary text-sm">{{ $selectedGroup->managingAgent->user?->full_name ?? 'N/A' }}</p>
                                <p class="text-xs text-text-secondary">{{ $selectedGroup->managingAgent->business_name ?? '' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-text-secondary">No managing agent assigned.</p>
                    @endif
                </div>

                {{-- Assigned Agents --}}
                @if($selectedGroup->agents->count() > 0)
                <div class="border-t border-border pt-4">
                    <h4 class="font-semibold text-text-primary mb-2 text-sm">Assigned Agents ({{ $selectedGroup->agents->count() }})</h4>
                    <div class="space-y-2">
                        @foreach($selectedGroup->agents as $agent)
                            <div class="bg-background rounded-card p-3 border border-border flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                    {{ strtoupper(substr($agent->user?->full_name ?? '?', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-text-primary text-sm">{{ $agent->user?->full_name ?? 'N/A' }}</p>
                                    <p class="text-xs text-text-secondary">{{ $agent->business_name ?? '' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Members --}}
                <div class="border-t border-border pt-4">
                    <h4 class="font-semibold text-text-primary mb-2 text-sm">Members ({{ $selectedGroup->members->count() }})</h4>
                    @if($selectedGroup->members->count() > 0)
                        <div class="max-h-48 overflow-y-auto rounded-card border border-border divide-y divide-border">
                            @foreach($selectedGroup->members->sortBy('position') as $member)
                                <div class="px-3 py-2.5 flex items-center gap-3 bg-background">
                                    <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[10px]">
                                        {{ strtoupper(substr($member->user?->full_name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-text-primary">{{ $member->user?->full_name ?? 'N/A' }}</p>
                                        <p class="text-[10px] text-text-secondary font-mono">{{ $member->user?->phone_number ?? '' }}</p>
                                    </div>
                                    <span class="text-[10px] text-text-secondary">#{{ $member->position ?? '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-text-secondary">No members yet.</p>
                    @endif
                </div>

                {{-- Recent Contributions --}}
                @if($selectedGroup->contributions->count() > 0)
                <div class="border-t border-border pt-4">
                    <h4 class="font-semibold text-text-primary mb-2 text-sm">Recent Contributions</h4>
                    <div class="max-h-40 overflow-y-auto rounded-card border border-border divide-y divide-border">
                        @foreach($selectedGroup->contributions->sortByDesc('created_at')->take(10) as $c)
                            <div class="px-3 py-2 flex items-center justify-between bg-background text-sm">
                                <span class="text-text-primary">{{ $c->user?->full_name ?? 'Member' }}</span>
                                <span class="font-medium text-text-primary tabular-nums">₦{{ number_format($c->amount, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Footer Actions --}}
            <div class="p-4 border-t border-border bg-background/40 shrink-0">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <div class="text-sm text-text-secondary">
                        @if($selectedGroup->status === 'pending') Awaiting approval. @endif
                        @if($selectedGroup->status === 'active') Group is active. @endif
                        @if($selectedGroup->status === 'suspended') Group is suspended. @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($selectedGroup->status === 'pending')
                            <button type="button" wire:click="promptConfirm('approve')" class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-btn transition-colors shadow-sm cursor-pointer">
                                Approve
                            </button>
                        @endif
                        @if($selectedGroup->status === 'suspended')
                            <button type="button" wire:click="promptConfirm('activate')" class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-btn transition-colors shadow-sm cursor-pointer">
                                Activate
                            </button>
                        @endif
                        @if($selectedGroup->status === 'active')
                            <button type="button" wire:click="promptConfirm('suspend')" class="px-4 py-2 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-btn transition-colors shadow-sm cursor-pointer">
                                Suspend
                            </button>
                        @endif
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-text-secondary bg-surface border border-border hover:bg-background rounded-btn transition-colors cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================
         CONFIRM ACTION MODAL
         ================================================ --}}
    @if($showConfirm)
    <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
        <div class="w-full max-w-sm bg-surface rounded-card shadow-elevation-4 border border-border overflow-hidden">
            <div class="p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ in_array($confirmType, ['approve', 'activate']) ? 'bg-emerald-100' : 'bg-amber-100' }}">
                        @if(in_array($confirmType, ['approve', 'activate']))
                            <x-lucide-check class="w-5 h-5 text-emerald-600" />
                        @else
                            <x-lucide-alert-triangle class="w-5 h-5 text-amber-600" />
                        @endif
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-text-primary">
                            {{ match($confirmType) { 'approve' => 'Approve', 'activate' => 'Activate', 'suspend' => 'Suspend', default => ucfirst($confirmType) } }} Group
                        </h3>
                    </div>
                </div>
                <p class="text-sm text-text-secondary">
                    Are you sure you want to
                    <strong class="text-text-primary">{{ strtolower(match($confirmType) { 'approve' => 'Approve', 'activate' => 'Activate', 'suspend' => 'Suspend', default => $confirmType }) }}</strong>
                    the group <strong class="text-text-primary">{{ $selectedGroup?->name ?? 'this group' }}</strong>?
                </p>
            </div>
            <div class="p-4 border-t border-border bg-background/40 flex justify-end gap-3">
                <button type="button" wire:click="cancelConfirm" class="px-4 py-2 text-sm font-medium text-text-secondary bg-surface border border-border hover:bg-background rounded-btn transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="button" wire:click="executeConfirm" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-semibold text-white rounded-btn transition-colors shadow-sm disabled:opacity-50 cursor-pointer
                    {{ in_array($confirmType, ['approve', 'activate']) ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-amber-600 hover:bg-amber-700' }}">
                    <span wire:loading.remove wire:target="executeConfirm">
                        {{ match($confirmType) { 'approve' => 'Approve', 'activate' => 'Activate', 'suspend' => 'Suspend', default => ucfirst($confirmType) } }}
                    </span>
                    <span wire:loading wire:target="executeConfirm" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Working...
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
