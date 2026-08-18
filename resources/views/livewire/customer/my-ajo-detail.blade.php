<div class="px-4 py-6 md:p-8 max-w-4xl mx-auto relative overflow-hidden">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('customer.my-ajo') }}" wire:navigate class="text-sm text-text-secondary hover:text-primary transition-colors">&larr; {{ __('Back to My Ajo') }}</a>
        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $group->status === 'active' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' }}">
            {{ ucfirst($group->status) }}
        </span>
    </div>

    <div class="space-y-6">
        {{-- Hero / Group Info --}}
        <section class="rounded-card bg-gradient-to-br from-primary to-primary-dark p-6 text-white shadow-elevation-2">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-white/70 text-xs uppercase tracking-wider">{{ __('Ajo Group') }}</p>
                    <h1 class="text-2xl font-bold mt-1">{{ $group->name }}</h1>
                    <p class="text-white/80 text-sm mt-1">{{ ucfirst($group->frequency) }} &middot; {{ $group->members_count }} {{ __('members') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-white/70 text-xs">{{ __('My Position') }}</p>
                    <p class="text-2xl font-bold">{{ $membership->position ?? '-' }}</p>
                </div>
            </div>
        </section>

        {{-- Live Stats Grid --}}
        <section class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="rounded-card border border-border bg-surface p-4 shadow-soft">
                <p class="text-xs text-text-secondary uppercase tracking-wide">{{ __('Contribution') }}</p>
                <p class="text-xl font-bold text-text-primary mt-1">₦{{ number_format($group->contribution_amount, 0) }}</p>
                <p class="text-xs text-text-secondary">{{ __('per cycle') }}</p>
            </div>
            <div class="rounded-card border border-border bg-surface p-4 shadow-soft">
                <p class="text-xs text-text-secondary uppercase tracking-wide">{{ __('My Total Paid') }}</p>
                <p class="text-xl font-bold text-success mt-1">₦{{ number_format($myContributions->sum('amount'), 0) }}</p>
                <p class="text-xs text-text-secondary">{{ __('across :count cycles', ['count' => $myContributions->count()]) }}</p>
            </div>
            <div class="rounded-card border border-border bg-surface p-4 shadow-soft">
                <p class="text-xs text-text-secondary uppercase tracking-wide">{{ __('Total Payouts') }}</p>
                <p class="text-xl font-bold text-primary mt-1">₦{{ number_format($myPayouts->sum('amount'), 0) }}</p>
                <p class="text-xs text-text-secondary">{{ __('received :count times', ['count' => $myPayouts->count()]) }}</p>
            </div>
            <div class="rounded-card border border-border bg-surface p-4 shadow-soft">
                <p class="text-xs text-text-secondary uppercase tracking-wide">{{ __('My Balance') }}</p>
                <p class="text-xl font-bold text-amber-600 mt-1">₦{{ number_format(max(0, $myPayouts->sum('amount') - $myContributions->sum('amount')), 0) }}</p>
                <p class="text-xs text-text-secondary">{{ __('net savings') }}</p>
            </div>
        </section>

        {{-- Main Content: Chart + Next Payout + Pay In --}}
        <div class="grid md:grid-cols-3 gap-6">
            {{-- Left/Full: Cycle Progress Chart --}}
            <div class="md:col-span-2 rounded-card border border-border bg-surface p-5 shadow-soft space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-text-primary">{{ __('Cycle #:number Progress', ['number' => $progress['cycle_number']]) }}</h2>
                    <span class="text-xs text-text-secondary">{{ $progress['paid_members'] }}/{{ $progress['total_members'] }} {{ __('paid') }}</span>
                </div>

                {{-- Donut SVG chart --}}
                <div class="flex items-center justify-center gap-8">
                    <div class="relative w-32 h-32">
                        <svg class="w-32 h-32 -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#8b5cf6" stroke-width="3"
                                    stroke-dasharray="{{ $progress['percentage'] }} {{ 100 - $progress['percentage'] }}"
                                    stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-text-primary">{{ $progress['percentage'] }}%</p>
                                <p class="text-[10px] text-text-secondary">{{ __('collected') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-primary"></div>
                            <span class="text-text-secondary">{{ __('Collected:') }} <strong class="text-text-primary">₦{{ number_format($progress['amount_collected'], 0) }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                            <span class="text-text-secondary">{{ __('Remaining:') }} <strong class="text-text-primary">₦{{ number_format(max(0, $progress['target_amount'] - $progress['amount_collected']), 0) }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                            <span class="text-text-secondary">{{ __('Target:') }} <strong class="text-text-primary">₦{{ number_format($progress['target_amount'], 0) }}</strong></span>
                        </div>
                    </div>
                </div>

                {{-- Member contribution bar chart --}}
                <div class="space-y-2 mt-4">
                    <p class="text-xs font-medium text-text-secondary uppercase tracking-wide">{{ __('Current Cycle Members') }}</p>
                    @php $maxAmount = $progress['target_amount'] > 0 ? $progress['target_amount'] : 1; @endphp
                    @foreach($group->members->sortBy('position') as $member)
                        @php
                            $memberPaid = $currentContributions->where('user_id', $member->user_id)->first();
                            $memberAmount = $memberPaid ? (float) $memberPaid->amount : 0;
                            $barWidth = $group->contribution_amount > 0 ? round(($memberAmount / $group->contribution_amount) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="w-6 text-xs font-medium text-text-secondary text-right">{{ $member->position ?? '-' }}</span>
                            <div class="flex-1 h-6 rounded-lg bg-gray-100 overflow-hidden relative">
                                <div class="h-full rounded-lg {{ $memberPaid ? 'bg-primary' : 'bg-gray-200' }}" style="width: {{ $memberPaid ? 100 : 0 }}%"></div>
                                <span class="absolute inset-0 flex items-center px-2 text-xs {{ $memberPaid ? 'text-white' : 'text-text-secondary' }}">
                                    {{ $member->user->full_name ?? 'Unknown' }}
                                </span>
                            </div>
                            @if($memberPaid)
                                <x-lucide-check class="w-4 h-4 text-success shrink-0" />
                            @else
                                <span class="w-4 shrink-0"></span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Right: Next Payout + Pay In --}}
            <div class="space-y-4">
                {{-- Next Payout Card --}}
                <div class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-3">
                    <p class="text-xs font-medium text-text-secondary uppercase tracking-wide">{{ __('Next Payout') }}</p>
                    @php $np = $nextPayout; @endphp
                    <div class="text-center">
                        <p class="text-3xl font-bold text-text-primary">₦{{ number_format($np['amount'], 0) }}</p>
                        <p class="text-sm text-text-secondary mt-1">{{ $np['scheduled_date']?->format('d M Y') ?? 'N/A' }}</p>
                    </div>
                    <div class="flex items-center gap-2 justify-center">
                        <div class="w-8 h-8 rounded-full bg-primary-light flex items-center justify-center text-sm font-bold text-primary">
                            {{ $np['recipient'] ? strtoupper(substr($np['recipient']->full_name ?? '', 0, 1)) : '?' }}
                        </div>
                        <span class="text-sm font-medium text-text-primary">{{ $np['recipient']?->full_name ?? 'TBD' }}</span>
                        @if($np['recipient'] && $np['recipient']->is(Auth::user()))
                            <span class="text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary">{{ __('You') }}</span>
                        @endif
                    </div>
                    <div class="flex justify-center">
                        <span class="text-xs px-3 py-1 rounded-full font-medium 
                            @switch($np['status']) 
                                @case('completed') bg-success/10 text-success @break 
                                @case('overdue') bg-danger/10 text-danger @break 
                                @default bg-primary/10 text-primary @endswitch">
                            {{ ucfirst($np['status']) }}
                        </span>
                    </div>
                </div>

                {{-- Pay In Card --}}
                <div class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-4">
                    <p class="text-xs font-medium text-text-secondary uppercase tracking-wide">{{ __('Pay My Contribution') }}</p>
                    
                    @php
                        $currentCycleNumber = $progress['cycle_number'];
                        $hasPaidThisCycle = $myContributions->where('cycle_number', $currentCycleNumber)->isNotEmpty();
                    @endphp

                    @if($hasPaidThisCycle)
                        <div class="bg-emerald-50 border border-emerald-200 rounded-btn p-4 text-center">
                            <x-lucide-check-circle class="w-8 h-8 text-emerald-600 mx-auto mb-2" />
                            <p class="text-sm font-medium text-emerald-800">{{ __('You\'ve paid this cycle') }}</p>
                            <p class="text-xs text-emerald-600 mt-1">₦{{ number_format($group->contribution_amount, 0) }}</p>
                        </div>
                    @else
                        <div class="text-center">
                            <p class="text-2xl font-bold text-text-primary">₦{{ number_format($group->contribution_amount, 0) }}</p>
                            <p class="text-xs text-text-secondary">{{ __('Due for cycle :number', ['number' => $currentCycleNumber]) }}</p>
                        </div>
                        <button wire:click="payContribution" wire:loading.attr="disabled"
                                class="w-full rounded-btn bg-primary text-white px-6 py-3 text-sm font-semibold hover:bg-primary-dark transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="payContribution">{{ __('Pay Now from Wallet') }}</span>
                            <span wire:loading wire:target="payContribution" class="flex items-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                {{ __('Processing...') }}
                            </span>
                        </button>
                        @if($contributionMessage)
                            <p class="text-sm text-center {{ str_contains($contributionMessage, 'successful') ? 'text-success' : 'text-danger' }}">
                                {{ $contributionMessage }}
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Activity / Timeline --}}
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-4">
            <h2 class="text-base font-semibold text-text-primary">{{ __('Recent Activity') }}</h2>

            @php
                $allActivities = collect();
                foreach ($myContributions as $c) {
                    $allActivities->push(['type' => 'contribution', 'data' => $c, 'date' => $c->created_at]);
                }
                foreach ($myPayouts as $p) {
                    $allActivities->push(['type' => 'payout', 'data' => $p, 'date' => $p->created_at]);
                }
                $allActivities = $allActivities->sortByDesc('date')->take(10);
            @endphp

            @if($allActivities->isEmpty())
                <div class="text-center py-8">
                    <x-lucide-clock class="w-10 h-10 text-gray-300 mx-auto mb-2" />
                    <p class="text-sm text-text-secondary">{{ __('No activity yet. Your transactions will appear here.') }}</p>
                </div>
            @else
                <div class="space-y-0">
                    @foreach($allActivities as $activity)
                        @php $item = $activity['data']; @endphp
                        <div class="flex items-start gap-3 py-3 border-b border-border last:border-b-0">
                            <div class="w-8 h-8 rounded-full {{ $activity['type'] === 'contribution' ? 'bg-primary-light text-primary' : 'bg-success/10 text-success' }} flex items-center justify-center shrink-0">
                                @if($activity['type'] === 'contribution')
                                    <x-lucide-arrow-down-circle class="w-4 h-4" />
                                @else
                                    <x-lucide-arrow-up-circle class="w-4 h-4" />
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-text-primary">
                                    @if($activity['type'] === 'contribution')
                                        {{ __('Cycle :number contribution', ['number' => $item->cycle_number]) }}
                                    @else
                                        {{ __('Payout received (:number)', ['number' => '#' . $item->cycle_number]) }}
                                    @endif
                                </p>
                                <p class="text-xs text-text-secondary">{{ $item->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-semibold {{ $activity['type'] === 'contribution' ? 'text-text-primary' : 'text-success' }}">
                                    {{ $activity['type'] === 'contribution' ? '-' : '+' }}₦{{ number_format($item->amount, 0) }}
                                </p>
                                @if($item->transaction && $item->transaction->reference)
                                    <p class="text-[10px] text-text-secondary">{{ $item->transaction->reference }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Group Members --}}
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-4">
            <h2 class="text-base font-semibold text-text-primary">{{ __('Group Members') }}</h2>
            <div class="grid md:grid-cols-2 gap-2">
                @foreach($group->members->sortBy('position') as $member)
                    @php $memberPaidCurrent = $currentContributions->where('user_id', $member->user_id)->isNotEmpty(); @endphp
                    <div class="flex items-center justify-between rounded-btn bg-background px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-sm font-bold text-primary">
                                {{ strtoupper(substr($member->user->full_name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-text-primary">{{ $member->user->full_name ?? 'Unknown' }}</p>
                                <p class="text-xs text-text-secondary">{{ __('Position') }} {{ $member->position ?? '-' }}</p>
                            </div>
                        </div>
                        @if($member->user_id === Auth::id())
                            <span class="text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary">{{ __('You') }}</span>
                        @elseif($memberPaidCurrent)
                            <x-lucide-check-circle class="w-4 h-4 text-success" />
                        @else
                            <span class="text-xs text-text-secondary">{{ ucfirst($member->status) }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Managing Agent --}}
        @if($group->managingAgent)
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-4" x-data="{ agentOpen: false }">
            <div class="flex items-center justify-between cursor-pointer" @click="agentOpen = !agentOpen">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center">
                        <x-lucide-briefcase class="w-5 h-5 text-secondary" />
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-text-primary">{{ __('Managing Agent') }}</h2>
                        <p class="text-sm text-text-secondary">{{ $group->managingAgent->user->full_name ?? $group->managingAgent->business_name ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="w-8 h-8 rounded-full bg-background flex items-center justify-center transition-transform" :class="agentOpen ? 'rotate-180' : ''">
                    <x-lucide-chevron-down class="w-4 h-4 text-text-secondary" />
                </div>
            </div>

            <div x-show="agentOpen" x-collapse class="space-y-3">
                <div class="bg-background rounded-btn p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-text-secondary">{{ __('Name') }}</span>
                        <span class="text-sm font-semibold text-text-primary">{{ $group->managingAgent->user->full_name ?? 'N/A' }}</span>
                    </div>
                    @if($group->managingAgent->business_name)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-text-secondary">{{ __('Business') }}</span>
                        <span class="text-sm font-semibold text-text-primary">{{ $group->managingAgent->business_name }}</span>
                    </div>
                    @endif
                    @if($group->managingAgent->user->phone_number)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-text-secondary">{{ __('Phone') }}</span>
                        <a href="tel:{{ $group->managingAgent->user->phone_number }}" class="text-sm font-semibold text-primary hover:underline tabular-nums">{{ $group->managingAgent->user->phone_number }}</a>
                    </div>
                    @endif
                    @if($group->managingAgent->user->email && $group->managingAgent->user->email !== $group->managingAgent->user->phone_number . '@payease.local')
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-text-secondary">{{ __('Email') }}</span>
                        <a href="mailto:{{ $group->managingAgent->user->email }}" class="text-sm font-semibold text-primary hover:underline">{{ $group->managingAgent->user->email }}</a>
                    </div>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- Ajo Owner --}}
        @if($group->ajoOwner)
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-4" x-data="{ ownerOpen: false }">
            <div class="flex items-center justify-between cursor-pointer" @click="ownerOpen = !ownerOpen">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <x-lucide-crown class="w-5 h-5 text-primary" />
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-text-primary">{{ __('Ajo Owner') }}</h2>
                        <p class="text-sm text-text-secondary">{{ $group->ajoOwner->user->full_name ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="w-8 h-8 rounded-full bg-background flex items-center justify-center transition-transform" :class="ownerOpen ? 'rotate-180' : ''">
                    <x-lucide-chevron-down class="w-4 h-4 text-text-secondary" />
                </div>
            </div>

            <div x-show="ownerOpen" x-collapse class="space-y-3">
                <div class="bg-background rounded-btn p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-text-secondary">{{ __('Name') }}</span>
                        <span class="text-sm font-semibold text-text-primary">{{ $group->ajoOwner->user->full_name ?? 'N/A' }}</span>
                    </div>
                    @if($group->ajoOwner->user->phone_number)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-text-secondary">{{ __('Phone') }}</span>
                        <a href="tel:{{ $group->ajoOwner->user->phone_number }}" class="text-sm font-semibold text-primary hover:underline tabular-nums">{{ $group->ajoOwner->user->phone_number }}</a>
                    </div>
                    @endif
                    @if($group->ajoOwner->user->email && !str_ends_with($group->ajoOwner->user->email, '@payease.local'))
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-text-secondary">{{ __('Email') }}</span>
                        <a href="mailto:{{ $group->ajoOwner->user->email }}" class="text-sm font-semibold text-primary hover:underline">{{ $group->ajoOwner->user->email }}</a>
                    </div>
                    @endif
                </div>
            </div>
        </section>
        @endif
    </div>
</div>
