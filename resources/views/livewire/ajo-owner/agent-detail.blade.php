<div class="px-4 py-6 md:p-8 max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('ajo-owner.agents') }}" wire:navigate class="text-sm text-purple-600 hover:text-purple-700 font-medium flex items-center gap-1 mb-1">
                <x-lucide-arrow-left class="w-4 h-4" /> {{ __('Back to Agents') }}
            </a>
            <h1 class="text-2xl font-bold text-text-primary">{{ $agent->user?->full_name ?? $agent->business_name }}</h1>
            <p class="text-text-secondary text-sm">{{ $agent->business_name }} &mdash; {{ $agent->lga }}, {{ $agent->state }}</p>
        </div>
        <x-status-badge :status="strtolower($agent->status)" />
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-surface rounded-xl p-4 border border-border shadow-sm">
            <p class="text-text-secondary text-xs font-medium">{{ __('Groups Managed') }}</p>
            <p class="text-2xl font-bold text-text-primary mt-1">{{ $agent->managing_ajo_groups_count }}</p>
        </div>
        <div class="bg-surface rounded-xl p-4 border border-border shadow-sm">
            <p class="text-text-secondary text-xs font-medium">{{ __('Total Collected') }}</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">₦{{ number_format($totalCollected, 0) }}</p>
        </div>
        <div class="bg-surface rounded-xl p-4 border border-border shadow-sm">
            <p class="text-text-secondary text-xs font-medium">{{ __('This Month') }}</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">₦{{ number_format($thisMonthCollected, 0) }}</p>
        </div>
        <div class="bg-surface rounded-xl p-4 border border-border shadow-sm">
            <p class="text-text-secondary text-xs font-medium">{{ __('Float Balance') }}</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">₦{{ number_format($agent->float_balance, 0) }}</p>
        </div>
    </div>

    <!-- Agent Info Card -->
    <div class="bg-surface rounded-card border border-border shadow-sm p-6 space-y-4">
        <h2 class="font-bold text-text-primary">{{ __('Agent Information') }}</h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-text-secondary">{{ __('Full Name') }}</p>
                <p class="font-medium text-text-primary">{{ $agent->user?->full_name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-text-secondary">{{ __('Phone Number') }}</p>
                <p class="font-medium text-text-primary">+234 {{ substr($agent->user?->phone_number ?? '', 1) }}</p>
            </div>
            <div>
                <p class="text-text-secondary">{{ __('Email') }}</p>
                <p class="font-medium text-text-primary">{{ $agent->user?->email ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-text-secondary">{{ __('Business Name') }}</p>
                <p class="font-medium text-text-primary">{{ $agent->business_name }}</p>
            </div>
            <div>
                <p class="text-text-secondary">{{ __('Location') }}</p>
                <p class="font-medium text-text-primary">{{ $agent->lga }}, {{ $agent->state }}</p>
            </div>
            <div>
                <p class="text-text-secondary">{{ __('Commission Rate') }}</p>
                <p class="font-medium text-text-primary">{{ $agent->commission_rate }}%</p>
            </div>
            <div>
                <p class="text-text-secondary">{{ __('Max Float') }}</p>
                <p class="font-medium text-text-primary">₦{{ number_format($agent->max_float, 2) }}</p>
            </div>
            <div>
                <p class="text-text-secondary">{{ __('Total Earnings') }}</p>
                <p class="font-medium text-text-primary">₦{{ number_format($agent->total_earnings, 2) }}</p>
            </div>
            <div>
                <p class="text-text-secondary">{{ __('Approved') }}</p>
                <p class="font-medium text-text-primary">{{ $agent->approved_at?->format('d M Y') ?? 'Not yet' }}</p>
            </div>
        </div>
    </div>

    <!-- Assigned Groups -->
    @if($agent->managingAjoGroups->isNotEmpty() || $agent->assignedGroups->isNotEmpty())
    <div class="bg-surface rounded-card border border-border shadow-sm p-6 space-y-3">
        <h2 class="font-bold text-text-primary">{{ __('Assigned Groups') }}</h2>
        @foreach($agent->managingAjoGroups as $group)
            <div class="flex items-center justify-between p-3 rounded-lg border border-border">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <x-lucide-shield class="w-4 h-4 text-purple-600" />
                    </div>
                    <div>
                        <p class="font-medium text-text-primary text-sm">{{ $group->name }}</p>
                        <p class="text-text-secondary text-xs">{{ __('Managing Agent') }}</p>
                    </div>
                </div>
                <x-status-badge :status="strtolower($group->status)" />
            </div>
        @endforeach
        @foreach($agent->assignedGroups as $group)
            <div class="flex items-center justify-between p-3 rounded-lg border border-border">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <x-lucide-users class="w-4 h-4 text-blue-600" />
                    </div>
                    <div>
                        <p class="font-medium text-text-primary text-sm">{{ $group->name }}</p>
                        <p class="text-text-secondary text-xs">{{ $group->pivot->role === 'managing_agent' ? __('Managing Agent') : __('Field Agent') }}</p>
                    </div>
                </div>
                <x-status-badge :status="strtolower($group->status)" />
            </div>
        @endforeach
    </div>
    @endif

    <!-- Recent Transactions -->
    @if($recentTransactions->isNotEmpty())
    <div class="bg-surface rounded-card border border-border shadow-sm p-6 space-y-3">
        <h2 class="font-bold text-text-primary">{{ __('Recent Transactions') }}</h2>
        @foreach($recentTransactions as $txn)
            <div class="flex items-center justify-between p-3 rounded-lg border border-border">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg {{ $txn->type === 'credit' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' }} flex items-center justify-center">
                        @if($txn->type === 'credit')
                            <x-lucide-arrow-down class="w-4 h-4" />
                        @else
                            <x-lucide-arrow-up class="w-4 h-4" />
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-text-primary text-sm">{{ $txn->description ?? ucfirst($txn->type) }}</p>
                        <p class="text-text-secondary text-xs">{{ $txn->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                <span class="font-medium text-sm {{ $txn->type === 'credit' ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $txn->type === 'credit' ? '+' : '-' }}₦{{ number_format($txn->amount, 2) }}
                </span>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Audit Logs -->
    @if($recentAuditLogs->isNotEmpty())
    <div class="bg-surface rounded-card border border-border shadow-sm p-6 space-y-3">
        <h2 class="font-bold text-text-primary">{{ __('Activity Log') }}</h2>
        @foreach($recentAuditLogs as $log)
            <div class="flex items-start gap-3 p-3 rounded-lg border border-border">
                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0">
                    <x-lucide-clock class="w-4 h-4 text-text-secondary" />
                </div>
                <div>
                    <p class="text-sm text-text-primary">{{ str_replace('_', ' ', ucfirst($log->action)) }}</p>
                    <p class="text-xs text-text-secondary">{{ $log->created_at->diffForHumans() }}</p>
                    @if($log->new_values)
                        <p class="text-xs text-text-secondary mt-0.5">
                            @foreach($log->new_values as $key => $val)
                                {{ str_replace('_', ' ', ucfirst($key)) }}: {{ is_array($val) ? json_encode($val) : $val }}{{ !$loop->last ? ', ' : '' }}
                            @endforeach
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @endif

</div>
