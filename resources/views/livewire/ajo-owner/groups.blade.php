<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('My Ajo Groups') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Manage your savings circles and monitor cycle progress.') }}</p>
        </div>
        <a href="{{ route('ajo-owner.groups.create') }}" wire:navigate class="inline-flex items-center shrink-0 px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-btn font-medium transition-colors">
            <x-lucide-plus class="w-4 h-4 mr-2" /> {{ __('Create New Group') }}
        </a>
    </div>

    <div class="flex flex-wrap gap-2">
        <button wire:click="$set('statusFilter', 'all')" class="{{ $statusFilter === 'all' ? 'bg-purple-600 text-white border-purple-600' : 'bg-surface text-text-secondary border-border' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors">{{ __('All Statuses') }}</button>
        <button wire:click="$set('statusFilter', 'pending')" class="{{ $statusFilter === 'pending' ? 'bg-purple-600 text-white border-purple-600' : 'bg-surface text-text-secondary border-border' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors">{{ __('Pending') }}</button>
        <button wire:click="$set('statusFilter', 'active')" class="{{ $statusFilter === 'active' ? 'bg-purple-600 text-white border-purple-600' : 'bg-surface text-text-secondary border-border' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors">{{ __('Active') }}</button>
        <button wire:click="$set('frequencyFilter', 'all')" class="{{ $frequencyFilter === 'all' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors">{{ __('All Frequencies') }}</button>
        <button wire:click="$set('frequencyFilter', 'daily')" class="{{ $frequencyFilter === 'daily' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors">{{ __('Daily') }}</button>
        <button wire:click="$set('frequencyFilter', 'weekly')" class="{{ $frequencyFilter === 'weekly' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors">{{ __('Weekly') }}</button>
        <button wire:click="$set('frequencyFilter', 'monthly')" class="{{ $frequencyFilter === 'monthly' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors">{{ __('Monthly') }}</button>
    </div>

    <x-data-table title="Active & Completed Groups" searchPlaceholder="{{ __('Search group name...') }}" :filters="['status', 'frequency']">
        <x-slot:header>
            <th class="px-4 py-3 font-medium text-left">{{ __('Group Name') }}</th>
            <th class="px-4 py-3 font-medium text-center">{{ __('Members') }}</th>
            <th class="px-4 py-3 font-medium text-left">{{ __('Contribution') }}</th>
            <th class="px-4 py-3 font-medium text-left">{{ __('Frequency') }}</th>
            <th class="px-4 py-3 font-medium text-left">{{ __('Managing Agent') }}</th>
            <th class="px-4 py-3 font-medium text-center">{{ __('Cycle Progress') }}</th>
            <th class="px-4 py-3 font-medium text-left">{{ __('Status') }}</th>
            <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
        </x-slot:header>

        @forelse($groups as $group)
            @php($progress = $progressByGroup[$group->id] ?? null)
            <tr class="hover:bg-background hover:shadow-elevation-1 transition-all group cursor-pointer" onclick="window.location.href='{{ route('ajo-owner.groups.detail', $group->id) }}'">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-bold text-xs shrink-0">
                            <x-lucide-users class="w-4 h-4" />
                        </div>
                        <span class="font-bold text-text-primary whitespace-nowrap">{{ $group->name }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-text-secondary font-medium">
                        {{ $group->members_count }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex flex-col">
                        <span class="font-medium text-text-primary tabular-nums whitespace-nowrap">₦{{ number_format($group->contribution_amount, 2) }}<span class="text-xs font-normal text-text-secondary">/member</span></span>
                        <span class="text-xs text-emerald-600 font-medium tabular-nums">₦{{ number_format($group->total_contributed ?? 0, 2) }} {{ __('total') }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ ucfirst($group->frequency) }}</td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <x-lucide-briefcase class="w-3.5 h-3.5 text-secondary" />
                        {{ $group->managingAgent?->user?->full_name ?? $group->managingAgent?->business_name ?? __('No Agent') }}
                    </div>
                </td>
                <td class="px-4 py-3 flex justify-center">
                    <x-cycle-progress size="compact" :total="$progress['total_members'] ?? 0" :completed="$progress['paid_members'] ?? 0" />
                </td>
                <td class="px-4 py-3">
                    <x-status-badge :status="strtolower($group->status)" />
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button class="p-1.5 text-text-secondary hover:text-purple-600 transition-colors bg-surface rounded shadow-sm border border-border" title="Manage Group">
                            <x-lucide-chevron-right class="w-4 h-4" />
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-text-secondary">{{ __('No groups found.') }}</td>
            </tr>
        @endforelse
    </x-data-table>
    <div>
        {{ $groups->links() }}
    </div>
</div>
