<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between mb-2">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Welcome,') }} {{ explode(' ', $user->full_name ?? '')[0] }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Manage groups, collect contributions, and access banking.') }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if($user->kyc_level > 0)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
                    <x-lucide-shield-check class="w-3.5 h-3.5" />
                    KYC Tier {{ $user->kyc_level }}
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">
                    <x-lucide-alert-circle class="w-3.5 h-3.5" />
                    KYC Pending
                </span>
            @endif
        </div>
    </div>

    @if(!$agent)
        <div class="text-center py-12">
            <x-lucide-alert-circle class="w-12 h-12 text-text-secondary mx-auto mb-4" />
            <p class="text-text-secondary">No agent profile found.</p>
        </div>
    @else

    <!-- KYC Upgrade Banner -->
    @if($user->kyc_level < 2)
    <a href="{{ url('/ajo-agent/kyc-upgrade') }}" wire:navigate class="block bg-gradient-to-r from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-900/10 border border-amber-200 dark:border-amber-800/40 rounded-card p-4 hover:shadow-elevation-1 transition-all">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-200 dark:bg-amber-800/40 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                <x-lucide-shield-alert class="w-5 h-5" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('Upgrade Your KYC') }}</p>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                    @if($user->kyc_level === 0)
                        {{ __('Complete Tier 1 verification to unlock a personal wallet and start transacting.') }}
                    @else
                        {{ __('Upgrade to Tier 2 to increase your limits and get a dedicated bank account.') }}
                    @endif
                </p>
            </div>
            <x-lucide-chevron-right class="w-5 h-5 text-amber-500 shrink-0" />
        </div>
    </a>
    @endif

    <!-- Personal Wallet Card -->
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 rounded-card p-5 text-white relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/5 rounded-full"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-1">
                <p class="text-emerald-100 text-sm font-medium">{{ __('Personal Balance') }}</p>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-white/20">{{ $wallet ? strtoupper($wallet->mmo_partner ?? 'Wallet') : 'No Wallet' }}</span>
            </div>
            <p class="text-3xl font-bold tabular-nums">₦{{ number_format($stats['wallet_balance'], 2) }}</p>

            @if($wallet && $wallet->account_number)
                <div class="mt-3 flex items-center gap-2">
                    <span class="text-emerald-200 text-xs">{{ __('Account:') }}</span>
                    <span class="font-mono font-bold text-sm tracking-wide">{{ $wallet->wallet_account_number ?: $wallet->account_number }}</span>
                    <button onclick="navigator.clipboard.writeText('{{ $wallet->wallet_account_number ?: $wallet->account_number }}')" class="text-emerald-200 hover:text-white transition-colors">
                        <x-lucide-copy class="w-3.5 h-3.5" />
                    </button>
                </div>
            @endif

            <div class="flex items-center gap-4 mt-3 text-xs text-emerald-200">
                <span>{{ __('Daily Limit:') }} ₦{{ number_format($wallet->daily_limit ?? 0, 0) }}</span>
                <span>{{ __('Per Txn:') }} ₦{{ number_format($wallet->single_txn_limit ?? 0, 0) }}</span>
            </div>
        </div>
    </div>

    <!-- Ajo Float (Read-Only) -->
    <div class="bg-surface rounded-card border border-border p-4 relative">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                <x-lucide-lock class="w-4 h-4" />
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-text-primary">{{ __('Ajo Float Balance') }}</p>
                <p class="text-[11px] text-text-secondary">{{ __('Collected from group members — managed by your Ajo Owner') }}</p>
            </div>
            <p class="text-xl font-bold text-text-primary tabular-nums">₦{{ number_format($stats['float_balance'], 2) }}</p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30 rounded-btn px-3 py-2 flex items-center gap-2 mt-2">
            <x-lucide-info class="w-3.5 h-3.5 text-amber-600 shrink-0" />
            <p class="text-[11px] text-amber-700 dark:text-amber-300">{{ __('Ajo float cannot be transferred. It is settled to your Ajo Owner periodically.') }}</p>
        </div>
    </div>

    <!-- Banking Quick Actions -->
    <div>
        <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wide mb-3">{{ __('Banking') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <a href="{{ url('/ajo-agent/send-money') }}" wire:navigate class="bg-surface rounded-card border border-border p-4 flex flex-col items-center gap-2 hover:shadow-elevation-1 hover:border-emerald-300 transition-all active:scale-[0.98] text-center">
                <div class="w-11 h-11 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <x-lucide-send class="w-5 h-5" />
                </div>
                <p class="text-xs font-bold text-text-primary">{{ __('Send Money') }}</p>
                <p class="text-[10px] text-text-secondary">Phone / Bank</p>
            </a>
            <a href="{{ url('/ajo-agent/collect') }}" wire:navigate class="bg-surface rounded-card border border-border p-4 flex flex-col items-center gap-2 hover:shadow-elevation-1 hover:border-emerald-300 transition-all active:scale-[0.98] text-center">
                <div class="w-11 h-11 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                    <x-lucide-circle-dollar-sign class="w-5 h-5" />
                </div>
                <p class="text-xs font-bold text-text-primary">{{ __('Collect') }}</p>
                <p class="text-[10px] text-text-secondary">{{ __('Ajo contributions') }}</p>
            </a>
            <a href="{{ url('/ajo-agent/create-member') }}" wire:navigate class="bg-surface rounded-card border border-border p-4 flex flex-col items-center gap-2 hover:shadow-elevation-1 hover:border-emerald-300 transition-all active:scale-[0.98] text-center">
                <div class="w-11 h-11 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                    <x-lucide-user-plus class="w-5 h-5" />
                </div>
                <p class="text-xs font-bold text-text-primary">{{ __('Add Member') }}</p>
                <p class="text-[10px] text-text-secondary">{{ __('Create & assign') }}</p>
            </a>
            <a href="{{ url('/ajo-agent/pay-bills') }}" wire:navigate class="bg-surface rounded-card border border-border p-4 flex flex-col items-center gap-2 hover:shadow-elevation-1 hover:border-emerald-300 transition-all active:scale-[0.98] text-center">
                <div class="w-11 h-11 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                    <x-lucide-receipt class="w-5 h-5" />
                </div>
                <p class="text-xs font-bold text-text-primary">{{ __('Pay Bills') }}</p>
                <p class="text-[10px] text-text-secondary">Airtime / Data / Cable</p>
            </a>
            <a href="{{ url('/ajo-agent/transactions') }}" wire:navigate class="bg-surface rounded-card border border-border p-4 flex flex-col items-center gap-2 hover:shadow-elevation-1 hover:border-emerald-300 transition-all active:scale-[0.98] text-center">
                <div class="w-11 h-11 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center">
                    <x-lucide-history class="w-5 h-5" />
                </div>
                <p class="text-xs font-bold text-text-primary">{{ __('History') }}</p>
                <p class="text-[10px] text-text-secondary">{{ __('All transactions') }}</p>
            </a>
        </div>
    </div>

    <!-- Agent Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-surface rounded-card p-4 border border-border">
            <p class="text-[11px] text-text-secondary font-medium uppercase tracking-wide">{{ __('Assigned Groups') }}</p>
            <p class="text-2xl font-bold text-text-primary mt-1">{{ $stats['total_groups'] }}</p>
        </div>
        <div class="bg-surface rounded-card p-4 border border-border">
            <p class="text-[11px] text-text-secondary font-medium uppercase tracking-wide">{{ __('Active Groups') }}</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['active_groups'] }}</p>
        </div>
        <div class="bg-surface rounded-card p-4 border border-border">
            <p class="text-[11px] text-text-secondary font-medium uppercase tracking-wide">{{ __('Total Members') }}</p>
            <p class="text-2xl font-bold text-text-primary mt-1">{{ $stats['total_members'] }}</p>
        </div>
        <div class="bg-surface rounded-card p-4 border border-border">
            <p class="text-[11px] text-text-secondary font-medium uppercase tracking-wide">{{ __('You Collected') }}</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1 tabular-nums">₦{{ number_format($stats['total_collected'], 0) }}</p>
        </div>
    </div>

    <!-- Assigned Groups -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-text-primary">{{ __('Assigned Groups') }}</h2>
            @if($assignedGroups->isNotEmpty())
                <a href="{{ url('/ajo-agent/groups') }}" wire:navigate class="text-sm font-medium text-emerald-600 hover:text-emerald-700">{{ __('View All') }}</a>
            @endif
        </div>

        @if($assignedGroups->isEmpty())
            <div class="bg-surface rounded-card border border-border p-8 text-center">
                <x-lucide-users class="w-12 h-12 text-text-secondary mx-auto mb-3" />
                <p class="text-text-secondary font-medium">{{ __('No groups assigned to you yet.') }}</p>
                <p class="text-sm text-text-secondary mt-1">{{ __('Contact your Ajo Owner to get groups assigned.') }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($assignedGroups->take(3) as $item)
                    @php($group = $item['group'])
                    @php($progress = $item['progress'])
                    <div class="bg-surface rounded-card border border-border p-4 hover:shadow-elevation-1 transition-all">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-text-primary text-sm">{{ $group->name }}</h3>
                                    @if($item['is_primary'])
                                        <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">Primary</span>
                                    @endif
                                    <x-status-badge :status="strtolower($group->status)" />
                                </div>
                                <p class="text-xs text-text-secondary mt-0.5">
                                    {{ match($group->model_type) { 'savings_pool' => 'Savings Pool', 'continuous_pool' => 'Continuous Pool', default => 'Rotation' } }}
                                    &middot; {{ ucfirst($group->frequency) }}
                                    @if($group->model_type === 'rotational')
                                        &middot; ₦{{ number_format($group->contribution_amount, 0) }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-text-secondary mb-1">
                            <span>{{ $progress['paid_members'] }}/{{ $progress['total_members'] }} paid</span>
                            <span class="font-medium">{{ $progress['percentage'] }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-600 rounded-full transition-all" style="width: {{ $progress['percentage'] }}%"></div>
                        </div>

                        @if($item['pending_members']->isNotEmpty())
                        <div class="mt-2.5">
                            <p class="text-[10px] font-semibold text-text-secondary uppercase tracking-wide mb-1">Pending (Cycle {{ $progress['cycle_number'] }})</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach($item['pending_members']->take(3) as $pending)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-[10px] font-medium text-amber-700 dark:text-amber-300">
                                        {{ Str::limit($pending->user?->full_name ?? '?', 12) }}
                                    </span>
                                @endforeach
                                @if($item['pending_members']->count() > 3)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-[10px] font-medium text-text-secondary">
                                        +{{ $item['pending_members']->count() - 3 }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @else
                        <p class="mt-2 text-[10px] font-semibold text-emerald-600">All members paid for cycle {{ $progress['cycle_number'] }}.</p>
                        @endif
                    </div>
                @endforeach

                @if($assignedGroups->count() > 3)
                    <a href="{{ url('/ajo-agent/groups') }}" wire:navigate class="block text-center py-3 text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                        {{ __('View all') }} {{ $assignedGroups->count() }} {{ __('groups') }} &rarr;
                    </a>
                @endif
            </div>
        @endif
    </div>
    @endif
</div>
