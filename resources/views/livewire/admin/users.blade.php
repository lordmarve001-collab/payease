<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Users</h1>
            <p class="text-text-secondary text-sm">Manage customer accounts and their KYC status.</p>
        </div>
        <div class="inline-flex items-center px-3 py-2 rounded-btn bg-primary-light text-primary text-sm font-semibold">
            {{ $users->total() }} users
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach(['all' => 'All', 'active' => 'Active', 'suspended' => 'Suspended'] as $value => $label)
            <button
                wire:click="$set('statusFilter', '{{ $value }}')"
                class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors {{ $statusFilter === $value ? 'bg-primary-light text-primary border-primary/30' : 'bg-surface text-text-secondary border-border hover:bg-background' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <x-data-table title="All Users" searchPlaceholder="Search by name or phone..." :filters="['status']" :paginator="$users">
        <x-slot:header>
            <th class="px-4 py-3 font-medium text-left">Name</th>
            <th class="px-4 py-3 font-medium text-left">Phone</th>
            <th class="px-4 py-3 font-medium text-left">KYC Tier</th>
            <th class="px-4 py-3 font-medium text-left">Tier 2 Progress</th>
            <th class="px-4 py-3 font-medium text-left">Status</th>
            <th class="px-4 py-3 font-medium text-left">Registered By Agent</th>
            <th class="px-4 py-3 font-medium text-left">Registered Date</th>
            <th class="px-4 py-3 font-medium text-right">Actions</th>
        </x-slot:header>

        @forelse($users as $user)
            <tr class="hover:bg-background hover:shadow-elevation-1 transition-all group">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary-light text-primary flex items-center justify-center font-bold text-xs">
                            {{ substr($user->full_name, 0, 1) }}
                        </div>
                        <span class="font-medium text-text-primary whitespace-nowrap">{{ $user->full_name }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $user->phone_number }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-text-secondary border border-border whitespace-nowrap">
                        Tier {{ $user->kyc_level }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm whitespace-nowrap">
                    @if((int) $user->kyc_level >= 2)
                        <span class="text-green-600 dark:text-green-400 font-medium">✓ Complete</span>
                    @else
                        <div class="flex gap-1 text-xs">
                            <span class="px-1.5 py-0.5 rounded {{ filled($user->nin_verified_at) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-text-secondary' }}" title="NIN">N</span>
                            <span class="px-1.5 py-0.5 rounded {{ filled($user->bvn_verified_at) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-text-secondary' }}" title="BVN">B</span>
                            <span class="px-1.5 py-0.5 rounded {{ filled($user->next_of_kin_submitted_at) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-text-secondary' }}" title="Next of Kin">K</span>
                        </div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-status-badge :status="strtolower($user->status ?? 'Active')" />
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">
                    @if($user->registeredByAgent)
                        {{ $user->registeredByAgent->user?->full_name ?? 'Agent #' . substr($user->registered_by_agent_id, 0, 8) }}
                    @else
                        <span class="text-text-secondary/50">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $user->created_at->format('d M Y') }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button class="p-1.5 text-text-secondary hover:text-primary transition-colors bg-surface rounded shadow-sm border border-border" title="View Details">
                            <x-lucide-eye class="w-4 h-4" />
                        </button>
                        <button
                            wire:click="confirmUnlock('{{ $user->id }}')"
                            class="px-2 py-1 text-xs font-medium text-secondary bg-secondary-light/20 hover:bg-secondary-light/30 rounded"
                            title="Unlock Account (clear PIN & login locks)"
                        >
                            <x-lucide-unlock class="w-3 h-3 inline" /> Unlock
                        </button>
                        <button
                            wire:click="confirmToggleStatus('{{ $user->id }}')"
                            class="px-2 py-1 text-xs font-medium {{ $user->status === 'suspended' ? 'text-primary bg-primary-light' : 'text-danger bg-red-100' }} rounded"
                            title="{{ $user->status === 'suspended' ? 'Activate User' : 'Suspend User' }}"
                        >
                            {{ $user->status === 'suspended' ? 'Activate' : 'Suspend' }}
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-text-secondary">No users found.</td>
            </tr>
        @endforelse
    </x-data-table>

    @if($showStatusModal && $selectedUser)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4">
            <div class="w-full max-w-md rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden">
                <div class="p-5 border-b border-border">
                    <h3 class="text-lg font-bold text-text-primary">{{ $selectedUser->status === 'suspended' ? 'Activate User' : 'Suspend User' }}</h3>
                    <p class="text-sm text-text-secondary mt-1">
                        {{ $selectedUser->status === 'suspended' ? 'Restore access for this account.' : 'This will immediately block the user from accessing ' . ($siteSettings->site_name ?? 'PayEase') . '.' }}
                    </p>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Name</span>
                        <span class="font-medium text-text-primary">{{ $selectedUser->full_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Phone</span>
                        <span class="font-medium text-text-primary">{{ $selectedUser->phone_number }}</span>
                    </div>
                </div>
                <div class="p-4 border-t border-border flex justify-end gap-3 bg-background/40">
                    <x-button variant="secondary" wire:click="closeStatusModal" class="bg-surface">Cancel</x-button>
                    <x-button variant="{{ $selectedUser->status === 'suspended' ? 'primary' : 'danger' }}" wire:click="toggleStatus">
                        {{ $selectedUser->status === 'suspended' ? 'Activate User' : 'Suspend User' }}
                    </x-button>
                </div>
            </div>
        </div>
    @endif
    @if($showUnlockModal && $selectedUser)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-text-primary/40 backdrop-blur-sm px-4">
            <div class="w-full max-w-md rounded-card bg-surface shadow-elevation-4 border border-border overflow-hidden">
                <div class="p-5 border-b border-border">
                    <h3 class="text-lg font-bold text-text-primary">Unlock Account</h3>
                    <p class="text-sm text-text-secondary mt-1">
                        Clear all PIN and login lockouts for this account.
                    </p>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Name</span>
                        <span class="font-medium text-text-primary">{{ $selectedUser->full_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Phone</span>
                        <span class="font-medium text-text-primary">{{ $selectedUser->phone_number }}</span>
                    </div>
                </div>
                <div class="p-4 border-t border-border flex justify-end gap-3 bg-background/40">
                    <x-button variant="secondary" wire:click="closeUnlockModal" class="bg-surface">Cancel</x-button>
                    <x-button variant="primary" wire:click="unlockAccount">
                        Unlock Account
                    </x-button>
                </div>
            </div>
        </div>
    @endif
</div>
