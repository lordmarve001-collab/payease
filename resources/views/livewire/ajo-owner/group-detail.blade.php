<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 bg-surface p-6 rounded-card shadow-elevation-1 border border-border">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h1 class="text-2xl md:text-3xl font-bold text-text-primary">{{ $group->name }}</h1>
                <x-status-badge :status="$group->status" />
            </div>
            <p class="text-text-secondary text-sm flex items-center gap-2">
                <x-lucide-users class="w-4 h-4" /> {{ $group->members_count }} {{ __('Members') }}
                <span class="text-border mx-1">|</span>
                <span class="font-bold text-text-primary">₦{{ number_format($group->contribution_amount, 2) }}</span> / {{ ucfirst($group->frequency) }}
            </p>

            <div class="mt-4 inline-flex items-center gap-2 px-3 py-1.5 bg-gray-50 dark:bg-gray-800 rounded-lg border border-border">
                <div class="w-6 h-6 rounded-full bg-secondary/10 text-secondary flex items-center justify-center text-xs">
                    <x-lucide-store class="w-3 h-3" />
                </div>
                <span class="text-sm font-medium text-text-primary">                {{ $group->managingAgent?->user?->full_name ?? $group->managingAgent?->business_name ?? __('No Agent Assigned') }}</span>
                <span class="text-xs text-text-secondary ml-1">({{ __('Agent') }})</span>
            </div>
        </div>

        <div class="flex items-center justify-center md:justify-end bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-border min-w-[280px]">
            <x-cycle-progress size="default" :total="$progress['total_members']" :completed="$progress['paid_members']" :amountCollected="$progress['amount_collected']" :amountTotal="$progress['target_amount']" />
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-surface rounded-card shadow-elevation-1 border border-border p-4">
                <h3 class="font-bold text-text-primary text-base mb-4">{{ __('Add Member') }}</h3>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="tel" wire:model.live="memberPhone" class="flex-1 rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="{{ __('Enter member phone number') }}">
                    <x-button variant="primary" wire:click="addMember" class="bg-purple-600 hover:bg-purple-700">{{ __('Add Member') }}</x-button>
                </div>
                @error('memberPhone') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
            </div>

            <div class="bg-surface rounded-card shadow-elevation-1 border border-border overflow-hidden">
                <div class="p-4 border-b border-border bg-background/50 flex justify-between items-center">
                    <h3 class="font-bold text-text-primary text-base">{{ __('Members & Payment Status') }}</h3>
                    <span class="text-xs font-medium text-text-secondary">{{ __('Cycle #') }}{{ $progress['cycle_number'] }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-max">
                        <thead>
                            <tr class="border-b border-border bg-gray-50 dark:bg-gray-800/50 text-xs uppercase tracking-wider text-text-secondary">
                                <th class="px-4 py-3 font-medium text-center w-12">{{ __('Pos') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Member') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Payment (This Cycle)') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('Phone') }}</th>
                                <th class="px-4 py-3 font-medium text-center w-20">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($members as $row)
                                <tr class="hover:bg-background transition-colors">
                                    <td class="px-4 py-3 text-center font-medium text-text-secondary">{{ $row['member']->position ?? '-' }}</td>
                                    <td class="px-4 py-3 font-medium text-text-primary">{{ $row['member']->user?->full_name }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$row['payment_status']" :label="ucfirst($row['payment_status'])" /></td>
                                    <td class="px-4 py-3 text-sm text-text-secondary">{{ $row['member']->user?->phone_number }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="confirmRemoveMember('{{ $row['member']->id }}')" class="p-1.5 text-text-secondary hover:text-danger transition-colors" title="{{ __('Remove Member') }}">
                                            <x-lucide-user-minus class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-text-secondary">{{ __('No members added to this group yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-surface rounded-card p-6 shadow-elevation-2 border-t-4 border-purple-600">
                <div class="flex items-center gap-2 text-purple-600 mb-4">
                    <x-lucide-calendar class="w-5 h-5" />
                    <h3 class="font-bold">{{ __('Next Payout Due') }}</h3>
                </div>

                <p class="text-sm text-text-secondary mb-1">{{ __('Recipient') }}</p>
                <h4 class="text-xl font-bold text-text-primary mb-4">{{ $nextPayout['recipient']?->full_name ?? __('No payout recipient yet') }}</h4>

                <div class="flex justify-between items-end mb-6">
                    <div>
                        <p class="text-sm text-text-secondary mb-1">{{ __('Pool Amount') }}</p>
                        <p class="text-2xl font-bold text-text-primary tabular-nums">₦{{ number_format($nextPayout['amount'], 2) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-text-secondary mb-1">{{ __('Date') }}</p>
                        <p class="font-bold {{ $nextPayout['status'] === 'overdue' ? 'text-danger' : 'text-text-primary' }}">{{ $nextPayout['scheduled_date']?->format('d M Y') ?? __('—') }}</p>
                    </div>
                </div>

                <div class="mb-4">
                    <x-status-badge :status="$nextPayout['status']" />
                </div>

                <x-button variant="primary" class="w-full bg-purple-600 hover:bg-purple-700" wire:click="confirmPayout">
                    {{ __('Mark as Paid') }}
                </x-button>
            </div>

            <div class="bg-surface rounded-card shadow-elevation-1 border border-border overflow-hidden">
                <div class="p-4 border-b border-border bg-background/50">
                    <h3 class="font-bold text-text-primary text-base">{{ __('Contribution History') }}</h3>
                </div>
                <div class="divide-y divide-border">
                    @forelse($contributionHistory as $cycle => $items)
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="font-bold text-text-primary text-sm">{{ __('Cycle #') }}{{ $cycle }}</p>
                                <p class="text-sm text-text-secondary">{{ $items->count() }} {{ __('payments') }}</p>
                            </div>
                            <div class="space-y-2">
                                @foreach($items as $item)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-text-primary">{{ $item->user?->full_name }}</span>
                                        <span class="font-medium text-text-secondary">₦{{ number_format($item->amount, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-text-secondary">{{ __('No contributions logged yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if($removingMemberId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-text-primary/40 backdrop-blur-sm" wire:click="cancelRemoveMember"></div>
            <div class="relative bg-surface rounded-card shadow-elevation-4 w-full max-w-sm overflow-hidden z-10">
                <div class="p-4 border-b border-border flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                    <h3 class="font-bold text-text-primary">{{ __('Remove Member') }}</h3>
                    <button wire:click="cancelRemoveMember" class="text-text-secondary hover:text-text-primary transition-colors">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-text-secondary">
                        {{ __('Are you sure you want to remove this member from the group? Their status will be marked as defaulted and they will be skipped in payout rotations.') }}
                    </p>
                    <div class="flex gap-3">
                        <x-button variant="secondary" class="flex-1" wire:click="cancelRemoveMember">{{ __('Cancel') }}</x-button>
                        <x-button variant="primary" class="flex-1 bg-danger hover:bg-red-700" wire:click="removeMember">{{ __('Remove') }}</x-button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showPayoutModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-text-primary/40 backdrop-blur-sm" wire:click="closePayoutModal"></div>
            <div class="relative bg-surface rounded-card shadow-elevation-4 w-full max-w-md overflow-hidden z-10">
                <div class="p-4 border-b border-border flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                    <h3 class="font-bold text-text-primary">{{ __('Confirm Payout') }}</h3>
                    <button wire:click="closePayoutModal" class="text-text-secondary hover:text-text-primary transition-colors">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-text-secondary">
                        {{ __('This will record cycle') }} {{ $nextPayout['cycle_number'] }} {{ __('as paid to') }} <span class="font-bold text-text-primary">{{ $nextPayout['recipient']?->full_name ?? __('the next member') }}</span>
                        {{ __('and reduce the managing agent\'s float by') }} <span class="font-bold text-text-primary">₦{{ number_format($nextPayout['amount'], 2) }}</span>.
                    </p>
                    <div class="flex gap-3">
                        <x-button variant="secondary" class="flex-1" wire:click="closePayoutModal">{{ __('Cancel') }}</x-button>
                        <x-button variant="primary" class="flex-1 bg-purple-600 hover:bg-purple-700" wire:click="processPayout">{{ __('Confirm') }}</x-button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
