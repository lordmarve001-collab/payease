<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('My Groups') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Groups assigned to you for collection and management.') }}</p>
    </div>

    @if(!$agent)
        <div class="text-center py-12">
            <x-lucide-alert-circle class="w-12 h-12 text-text-secondary mx-auto mb-4" />
            <p class="text-text-secondary">No agent profile found.</p>
        </div>
    @else
    <!-- Search -->
    <div class="relative">
        <x-lucide-search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary" />
        <input type="text" wire:model.live="search" class="w-full pl-10 pr-4 py-2.5 rounded-btn border border-border bg-surface text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm" placeholder="{{ __('Search groups...') }}">
    </div>

    @if($assignedGroups->isEmpty())
        <div class="bg-surface rounded-card border border-border p-8 text-center">
            <x-lucide-users class="w-12 h-12 text-text-secondary mx-auto mb-3" />
            <p class="text-text-secondary font-medium">{{ __('No groups assigned to you yet.') }}</p>
            <p class="text-sm text-text-secondary mt-1">{{ __('Contact your Ajo Owner to get groups assigned.') }}</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($assignedGroups as $item)
                @php($group = $item['group'])
                @php($progress = $item['progress'])
                <div class="bg-surface rounded-card border border-border p-5 hover:shadow-elevation-1 transition-all">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-text-primary">{{ $group->name }}</h3>
                                @if($item['is_primary'])
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">Primary</span>
                                @endif
                                <x-status-badge :status="strtolower($group->status)" />
                            </div>
                            <p class="text-sm text-text-secondary mt-1">
                                {{ match($group->model_type) { 'savings_pool' => 'Savings Pool', 'continuous_pool' => 'Continuous Pool', default => 'Traditional Rotation' } }}
                                &middot; {{ ucfirst($group->frequency) }}
                                @if($group->model_type === 'rotational')
                                    &middot; ₦{{ number_format($group->contribution_amount, 0) }}
                                @endif
                            </p>
                        </div>
                        @if($group->model_type === 'rotational')
                            <span class="font-bold text-text-primary tabular-nums">₦{{ number_format($group->contribution_amount, 0) }}</span>
                        @endif
                    </div>

                    <!-- Progress -->
                    <div class="mb-3">
                        <div class="flex items-center justify-between text-xs text-text-secondary mb-1.5">
                            <span>{{ $progress['paid_members'] }}/{{ $progress['total_members'] }} members paid</span>
                            <span class="font-medium">{{ $progress['percentage'] }}%</span>
                        </div>
                        <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-600 rounded-full transition-all" style="width: {{ $progress['percentage'] }}%"></div>
                        </div>
                    </div>

                    @if(in_array($group->model_type, ['savings_pool', 'continuous_pool']))
                    <div class="text-sm text-text-secondary mb-3">
                        <span class="font-medium">Collected:</span> ₦{{ number_format($progress['amount_collected'], 2) }}
                        @if($progress['target_amount'] > 0)
                            / ₦{{ number_format($progress['target_amount'], 0) }}
                        @endif
                    </div>
                    @endif

                    <!-- Next Payout -->
                    @if($item['next_payout'])
                    <div class="border-t border-border pt-3 mt-3">
                        <p class="text-xs font-semibold text-text-secondary uppercase tracking-wide mb-1">{{ __('Next Payout') }}</p>
                        <p class="text-sm text-text-primary">
                            {{ $item['next_payout']['recipient']->full_name ?? 'TBD' }}
                            @if($item['next_payout']['scheduled_date'])
                                &middot; {{ $item['next_payout']['scheduled_date']->format('M d, Y') }}
                            @endif
                        </p>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
    @endif
</div>
