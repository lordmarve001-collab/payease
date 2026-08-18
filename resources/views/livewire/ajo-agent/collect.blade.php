<div class="px-4 py-6 md:p-8 max-w-lg mx-auto relative overflow-hidden">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Collect Contribution') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Select a group and member to log their contribution.') }}</p>
    </div>

    @if(!$agent)
        <div class="text-center py-12">
            <x-lucide-alert-circle class="w-12 h-12 text-text-secondary mx-auto mb-4" />
            <p class="text-text-secondary">No agent profile found.</p>
        </div>

    @else

        {{-- ═══════════════════════════════════════════ --}}
        {{-- STEP 1: SELECT GROUP                       --}}
        {{-- ═══════════════════════════════════════════ --}}
        @if($step === 'select_group')
        <div wire:key="step-groups" class="space-y-4">
            <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide">{{ __('Select Group') }}</h2>

            @if($assignedGroups->isEmpty())
                <div class="bg-surface rounded-card border border-border p-8 text-center">
                    <x-lucide-users class="w-12 h-12 text-text-secondary mx-auto mb-3" />
                    <p class="text-text-secondary font-medium">{{ __('No active groups assigned.') }}</p>
                </div>
            @else
                @foreach($assignedGroups as $group)
                    @php
                        $progress = app(\App\Services\AjoService::class)->getCycleProgress($group);
                    @endphp
                    <button
                        wire:click="selectGroup('{{ $group->id }}')"
                        class="w-full bg-surface rounded-card border border-border p-4 text-left hover:shadow-elevation-1 hover:border-emerald-300 transition-all active:scale-[0.98]"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-text-primary">{{ $group->name }}</h3>
                                    @if($group->managing_agent_id === $agent->id)
                                        <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">Primary</span>
                                    @endif
                                </div>
                                <p class="text-sm text-text-secondary mt-0.5">
                                    {{ match($group->model_type) { 'savings_pool' => 'Savings Pool', 'continuous_pool' => 'Continuous Pool', default => 'Rotation' } }}
                                    &middot; {{ ucfirst($group->frequency) }}
                                    @if($group->model_type === 'rotational')
                                        &middot; ₦{{ number_format($group->contribution_amount, 0) }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs text-text-secondary">Cycle {{ $progress['cycle_number'] }}</p>
                                <p class="text-sm font-bold text-emerald-600">{{ $progress['percentage'] }}%</p>
                            </div>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden mt-3">
                            <div class="h-full bg-emerald-600 rounded-full" style="width: {{ $progress['percentage'] }}%"></div>
                        </div>
                        <p class="text-xs text-text-secondary mt-1.5">{{ $progress['paid_members'] }}/{{ $progress['total_members'] }} paid &middot; ₦{{ number_format($progress['amount_collected'], 0) }} collected</p>
                    </button>
                @endforeach
            @endif
        </div>
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- STEP 2: SELECT MEMBER                      --}}
        {{-- ═══════════════════════════════════════════ --}}
        @if($step === 'select_member')
        <div wire:key="step-members" class="space-y-4">
            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
                <button wire:click="goBack" class="p-1 hover:text-emerald-600 transition-colors">&larr; {{ __('Back') }}</button>
                <span>/</span>
                <span class="font-medium text-text-primary truncate">{{ $selectedGroup?->name }}</span>
            </div>

            {{-- Cycle / Collection info --}}
            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-card border border-emerald-200 dark:border-emerald-800 px-4 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wide">{{ __('Current Cycle') }}</p>
                        <p class="text-lg font-bold text-emerald-800 dark:text-emerald-200">Cycle {{ $cycleNumber }}</p>
                    </div>
                    @if($selectedGroup?->model_type === 'rotational')
                        <div class="text-right">
                            <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('Amount') }}</p>
                            <p class="text-lg font-bold text-emerald-800 dark:text-emerald-200">₦{{ number_format($selectedGroup->contribution_amount, 0) }}</p>
                        </div>
                    @endif
                    @if(in_array($selectedGroup?->model_type, ['savings_pool', 'continuous_pool']) && $selectedGroup?->collection_end_date)
                        @php
                            $endDate = \Carbon\Carbon::parse($selectedGroup->collection_end_date);
                            $now = \Carbon\Carbon::now();
                            $daysLeft = max(0, $now->diffInDays($endDate, false));
                            $isExpired = $now->isAfter($endDate);
                        @endphp
                        <div class="text-right">
                            <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ $isExpired ? __('Ended') : __('Time Left') }}</p>
                            <p class="text-lg font-bold {{ $isExpired ? 'text-red-600' : 'text-emerald-800 dark:text-emerald-200' }}">
                                {{ $isExpired ? __('Ended') : ($daysLeft == 0 ? __('Today') : $daysLeft . 'd') }}
                            </p>
                        </div>
                    @endif
                </div>
                @if(in_array($selectedGroup?->model_type, ['savings_pool', 'continuous_pool']))
                    <div class="flex items-center gap-2 mt-2 pt-2 border-t border-emerald-200/60 dark:border-emerald-800/60 text-xs text-emerald-600 dark:text-emerald-400">
                        <x-lucide-clock class="w-3 h-3" />
                        <span>{{ $selectedGroup->collection_end_date ? 'Collection ends ' . \Carbon\Carbon::parse($selectedGroup->collection_end_date)->format('M d, Y') : 'Open collection' }}</span>
                        @if($selectedGroup->min_contribution)
                            <span>&middot; Min ₦{{ number_format($selectedGroup->min_contribution, 0) }}</span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Search --}}
            <div class="relative">
                <x-lucide-search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary" />
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    class="w-full pl-10 pr-10 py-2.5 rounded-btn border border-border bg-surface text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm"
                    placeholder="{{ __('Search member name or phone...') }}"
                >
                @if($search !== '')
                    <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hover:text-text-primary">
                        <x-lucide-x class="w-4 h-4" />
                    </button>
                @endif
            </div>

            {{-- Members List --}}
            @if(in_array($selectedGroup?->model_type, ['savings_pool', 'continuous_pool']) && $selectedGroup?->collection_end_date && \Carbon\Carbon::now()->isAfter(\Carbon\Carbon::parse($selectedGroup->collection_end_date)))
                <div class="bg-red-50 dark:bg-red-900/20 rounded-card border border-red-200 dark:border-red-800 px-4 py-3 flex items-center gap-2">
                    <x-lucide-alert-circle class="w-4 h-4 text-red-600 shrink-0" />
                    <p class="text-sm font-medium text-red-700 dark:text-red-300">{{ __('Collection period has ended. Contributions cannot be accepted.') }}</p>
                </div>
            @endif

            @if(empty($members['items']) || $members['items']->isEmpty())
                <div class="bg-surface rounded-card border border-border p-8 text-center">
                    @if($search !== '')
                        <x-lucide-search-x class="w-12 h-12 text-text-secondary mx-auto mb-3" />
                        <p class="text-text-secondary font-medium">{{ __('No members match your search.') }}</p>
                    @else
                        <x-lucide-users class="w-12 h-12 text-text-secondary mx-auto mb-3" />
                        <p class="text-text-secondary font-medium">{{ __('No members in this group yet.') }}</p>
                    @endif
                </div>
            @else
                <p class="text-xs text-text-secondary font-semibold uppercase tracking-wide">
                    {{ __('All Members') }} ({{ $members['total'] }})
                </p>

                <div class="space-y-2">
                    @foreach($members['items'] as $member)
                        @php
                            $isPaid = $paidUserIds->contains($member->user_id);
                        @endphp
                        <div class="bg-surface rounded-card border {{ $isPaid ? 'border-emerald-200 dark:border-emerald-800' : 'border-border' }} p-4 flex items-center gap-3">
                            {{-- Avatar --}}
                            <div class="w-10 h-10 rounded-full {{ $isPaid ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }} flex items-center justify-center font-bold text-sm shrink-0">
                                {{ strtoupper(substr($member->user?->full_name ?? '?', 0, 1)) }}{{ strtoupper(substr(explode(' ', $member->user?->full_name ?? ' ')[1] ?? '', 0, 1)) }}
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-text-primary text-sm truncate">{{ $member->user?->full_name ?? 'Unknown' }}</p>
                                    @if($isPaid)
                                        <span class="shrink-0 text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">Paid</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-xs text-text-secondary">
                                    <span>+234 {{ substr($member->user?->phone_number ?? '', 1) }}</span>
                                    @if($member->position)
                                        <span>&middot; #{{ $member->position }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Collect Button --}}
                            @php
                                $collectionEnded = in_array($selectedGroup?->model_type, ['savings_pool', 'continuous_pool'])
                                    && $selectedGroup?->collection_end_date
                                    && \Carbon\Carbon::now()->isAfter(\Carbon\Carbon::parse($selectedGroup->collection_end_date));
                            @endphp
                            <button
                                wire:click="collectMember('{{ $member->id }}')"
                                class="shrink-0 px-3 py-1.5 text-xs font-semibold text-white {{ ($isPaid || $collectionEnded) ? 'bg-gray-400 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700 active:scale-95' }} rounded-lg transition-colors"
                                @if($isPaid || $collectionEnded) disabled @endif
                            >
                                {{ $isPaid ? __('Paid') : ($collectionEnded ? __('Closed') : __('Collect')) }}
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($members['lastPage'] > 1)
                    <div class="flex items-center justify-between pt-2">
                        <p class="text-xs text-text-secondary">
                            {{ ($members['currentPage'] - 1) * $members['perPage'] + 1 }}-{{ min($members['currentPage'] * $members['perPage'], $members['total']) }}
                            {{ __('of') }} {{ $members['total'] }}
                        </p>
                        <div class="flex gap-1">
                            @if($members['currentPage'] > 1)
                                <button
                                    wire:click="goToPage({{ $members['currentPage'] - 1 }})"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-border bg-surface text-text-primary hover:bg-background transition-colors"
                                >
                                    {{ __('Prev') }}
                                </button>
                            @endif
                            @if($members['currentPage'] < $members['lastPage'])
                                <button
                                    wire:click="goToPage({{ $members['currentPage'] + 1 }})"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-border bg-surface text-text-primary hover:bg-background transition-colors"
                                >
                                    {{ __('Next') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- STEP 3: CONFIRM                           --}}
        {{-- ═══════════════════════════════════════════ --}}
        @if($step === 'confirm')
        <div wire:key="step-confirm" class="space-y-6">
            <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
                <button wire:click="goBack" class="p-1 hover:text-emerald-600 transition-colors">&larr; {{ __('Back') }}</button>
                <span>/</span>
                <span class="font-medium text-text-primary">{{ __('Confirm') }}</span>
            </div>

            {{-- Member & Group Info --}}
            <div class="bg-surface rounded-card border border-border p-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg shrink-0">
                        {{ strtoupper(substr($selectedMember?->user?->full_name ?? '?', 0, 1)) }}{{ strtoupper(substr(explode(' ', $selectedMember?->user?->full_name ?? ' ')[1] ?? '', 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-bold text-text-primary">{{ $selectedMember?->user?->full_name }}</p>
                        <p class="text-sm text-text-secondary">{{ $selectedGroup?->name }} &middot; Cycle {{ $cycleNumber }}</p>
                    </div>
                </div>
            </div>

            {{-- Amount --}}
            @if($selectedGroup?->model_type === 'rotational')
                <div class="text-center py-4">
                    <p class="text-sm text-text-secondary mb-1">{{ __('Contribution Amount') }}</p>
                    <p class="text-4xl font-bold text-text-primary tabular-nums">₦{{ number_format($selectedGroup->contribution_amount, 2) }}</p>
                    <input type="hidden" wire:model="amount">
                </div>
            @else
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Enter Amount (₦)') }}</label>
                    <input
                        type="number"
                        wire:model="amount"
                        min="1"
                        step="100"
                        class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary text-2xl font-bold text-center outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums"
                        placeholder="0"
                    >
                    @error('amount') <p class="text-sm text-danger mt-1 text-center">{{ $message }}</p> @enderror
                    @if($selectedGroup?->min_contribution)
                        <p class="text-xs text-text-secondary text-center mt-1">Min: ₦{{ number_format($selectedGroup->min_contribution, 2) }}</p>
                    @endif
                </div>
            @endif

            @if(!$confirming)
                <button
                    wire:click="submitContribution"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1"
                >
                    {{ __('Log Contribution') }}
                </button>
            @else
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-card p-4">
                    <div class="flex items-start gap-2">
                        <x-lucide-alert-triangle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ __('Confirm this contribution?') }}</p>
                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">{{ __('This action cannot be undone. The contribution will be logged for the current cycle.') }}</p>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-4">
                        <button
                            wire:click="$set('confirming', false)"
                            class="flex-1 px-4 py-2.5 rounded-btn border border-border bg-surface text-text-primary text-sm font-medium hover:bg-gray-50 transition-colors"
                            {{ $processing ? 'disabled' : '' }}
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            wire:click="confirmContribution"
                            wire:target="confirmContribution"
                            wire:loading.attr="disabled"
                            class="flex-1 px-4 py-2.5 rounded-btn bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="confirmContribution">{{ __('Yes, Log It') }}</span>
                            <span wire:loading wire:target="confirmContribution" class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                {{ __('Processing...') }}
                            </span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- STEP 4: RESULT                            --}}
        {{-- ═══════════════════════════════════════════ --}}
        @if($step === 'result')
        <div wire:key="step-result" class="text-center space-y-6 py-4">
            @if($resultState === 'success')
                <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto">
                    <x-lucide-check class="w-10 h-10 text-emerald-600" />
                </div>
                <div>
                    <h2 class="text-xl font-bold text-text-primary">{{ __('Contribution Logged!') }}</h2>
                    <p class="text-text-secondary mt-1">{{ $resultMessage }}</p>
                </div>
                <div class="bg-surface rounded-card border border-border p-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-text-secondary">{{ __('Member') }}</span>
                        <span class="font-medium text-text-primary">{{ $resultMember }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-text-secondary">{{ __('Group') }}</span>
                        <span class="font-medium text-text-primary">{{ $resultGroup }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-text-secondary">{{ __('Cycle') }}</span>
                        <span class="font-medium text-text-primary">{{ $resultCycle }}</span>
                    </div>
                    <div class="flex justify-between text-sm border-t border-border pt-3">
                        <span class="text-text-secondary">{{ __('Amount') }}</span>
                        <span class="font-bold text-emerald-600 text-lg tabular-nums">₦{{ number_format($resultAmount, 2) }}</span>
                    </div>
                </div>
            @else
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                    <x-lucide-x class="w-10 h-10 text-red-600" />
                </div>
                <div>
                    <h2 class="text-xl font-bold text-text-primary">{{ __('Collection Failed') }}</h2>
                    <p class="text-danger mt-1 text-sm">{{ $resultMessage }}</p>
                </div>
            @endif

            <button
                wire:click="resetForm"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98]"
            >
                {{ __('Collect Another') }}
            </button>
        </div>
        @endif

    @endif
</div>
