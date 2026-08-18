<div class="px-4 py-6 md:p-8 max-w-4xl mx-auto relative overflow-hidden">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('My Ajo') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Track your Ajo groups, contributions, and savings performance.') }}</p>
    </div>

    @if($groups->isEmpty())
        <div class="text-center py-16 space-y-4">
            <div class="mx-auto w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center">
                <x-lucide-users class="w-10 h-10 text-gray-400" />
            </div>
            <h2 class="text-lg font-semibold text-text-primary">{{ __('Not in any Ajo group') }}</h2>
            <p class="text-sm text-text-secondary max-w-sm mx-auto">
                {{ __('You haven\'t joined any Ajo savings groups yet. Visit an agent to join one.') }}
            </p>
        </div>
    @else
        {{-- Global stats --}}
        @php
            $totalContributed = 0;
            $totalReceived = 0;
            $activeGroups = 0;
            foreach ($groups as $item) {
                $tc = $item['membership']->user?->id ? \App\Models\AjoContribution::where('user_id', $item['membership']->user_id)->where('group_id', $item['group']->id)->sum('amount') : 0;
                $tp = \App\Models\AjoPayout::where('user_id', $item['membership']->user_id)->where('group_id', $item['group']->id)->sum('amount');
                $totalContributed += $tc;
                $totalReceived += $tp;
                if ($item['group']->status === 'active') $activeGroups++;
            }
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
            <div class="rounded-card border border-border bg-surface p-4 shadow-soft">
                <p class="text-xs text-text-secondary uppercase tracking-wide">{{ __('Active Groups') }}</p>
                <p class="text-xl font-bold text-primary mt-1">{{ $activeGroups }}</p>
            </div>
            <div class="rounded-card border border-border bg-surface p-4 shadow-soft">
                <p class="text-xs text-text-secondary uppercase tracking-wide">{{ __('Total Contributed') }}</p>
                <p class="text-xl font-bold text-text-primary mt-1">₦{{ number_format($totalContributed, 0) }}</p>
            </div>
            <div class="rounded-card border border-border bg-surface p-4 shadow-soft">
                <p class="text-xs text-text-secondary uppercase tracking-wide">{{ __('Total Received') }}</p>
                <p class="text-xl font-bold text-success mt-1">₦{{ number_format($totalReceived, 0) }}</p>
            </div>
            <div class="rounded-card border border-border bg-surface p-4 shadow-soft">
                <p class="text-xs text-text-secondary uppercase tracking-wide">{{ __('Net Savings') }}</p>
                <p class="text-xl font-bold text-amber-600 mt-1">₦{{ number_format(max(0, $totalReceived - $totalContributed), 0) }}</p>
            </div>
        </div>

        {{-- Group cards --}}
        <div class="grid md:grid-cols-2 gap-6">
            @foreach($groups as $item)
                @php
                    $group = $item['group'];
                    $membership = $item['membership'];
                    $owner = $item['owner'];
                    $progress = $item['progress'];
                    $nextPayout = $item['nextPayout'];
                    $pct = $progress['percentage'];
                    $payoutDate = $nextPayout['scheduled_date']?->format('d M Y') ?? 'N/A';
                    $payoutStatusColor = match($nextPayout['status']) {
                        'completed' => 'text-success',
                        'overdue' => 'text-danger',
                        default => 'text-primary',
                    };
                    $myTotalContributions = \App\Models\AjoContribution::where('user_id', $membership->user_id)->where('group_id', $group->id)->sum('amount');
                    $myTotalPayouts = \App\Models\AjoPayout::where('user_id', $membership->user_id)->where('group_id', $group->id)->sum('amount');
                @endphp

                <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-4">
                    {{-- Header --}}
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-text-primary">{{ $group->name }}</h2>
                            <p class="text-xs text-text-secondary mt-1">
                                {{ __('Managed by') }} {{ $owner?->user?->full_name ?? __('Ajo Owner') }}
                                &middot; {{ ucfirst($group->frequency) }}
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $group->status === 'active' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' }}">
                            {{ ucfirst($group->status) }}
                        </span>
                    </div>

                    {{-- Mini chart --}}
                    <div class="flex items-center gap-4">
                        <div class="relative w-16 h-16 shrink-0">
                            <svg class="w-16 h-16 -rotate-90" viewBox="0 0 36 36">
                                <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                                <circle cx="18" cy="18" r="15.5" fill="none" stroke="#8b5cf6" stroke-width="3"
                                        stroke-dasharray="{{ $pct }} {{ 100 - $pct }}"
                                        stroke-linecap="round"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xs font-bold text-text-primary">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs flex-1">
                            <span class="text-text-secondary">{{ __('Position') }}</span>
                            <span class="font-medium text-text-primary text-right">{{ $membership->position ?? '-' }}</span>
                            <span class="text-text-secondary">{{ __('Contribution') }}</span>
                            <span class="font-medium text-text-primary text-right">₦{{ number_format($group->contribution_amount, 0) }}</span>
                            <span class="text-text-secondary">{{ __('My Total Paid') }}</span>
                            <span class="font-medium text-text-primary text-right">₦{{ number_format($myTotalContributions, 0) }}</span>
                            <span class="text-text-secondary">{{ __('Payouts Received') }}</span>
                            <span class="font-medium text-success text-right">₦{{ number_format($myTotalPayouts, 0) }}</span>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-text-secondary">{{ __('Cycle') }} {{ $progress['cycle_number'] }}</span>
                            <span class="font-medium text-text-primary">{{ $progress['paid_members'] }}/{{ $progress['total_members'] }}</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-gray-200">
                            <div class="h-2 rounded-full bg-primary transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>

                    {{-- Next Payout --}}
                    <div class="rounded-btn bg-background p-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-7 h-7 rounded-full bg-primary-light flex items-center justify-center text-xs font-bold text-primary shrink-0">
                                {{ $nextPayout['recipient'] ? strtoupper(substr($nextPayout['recipient']->full_name ?? '', 0, 1)) : '?' }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-text-primary truncate">{{ $nextPayout['recipient']?->full_name ?? 'TBD' }}</p>
                                <p class="text-[11px] text-text-secondary">{{ $payoutDate }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-text-primary">₦{{ number_format($nextPayout['amount'], 0) }}</p>
                            <p class="text-xs {{ $payoutStatusColor }}">{{ ucfirst($nextPayout['status']) }}</p>
                        </div>
                    </div>

                    {{-- CTA --}}
                    <a href="{{ route('customer.my-ajo-detail', $group->id) }}" wire:navigate 
                       class="inline-flex items-center justify-center rounded-btn border border-border px-5 py-2.5 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary w-full">
                        {{ __('View Dashboard') }}
                    </a>
                </section>
            @endforeach
        </div>
    @endif
</div>
