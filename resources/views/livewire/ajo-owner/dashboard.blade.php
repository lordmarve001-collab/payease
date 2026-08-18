<div class="space-y-6 pb-6">

    <!-- Welcome Header -->
    <div class="flex items-center justify-between">
        <div>
            <p class="text-text-secondary text-sm font-medium">{{ __('Welcome back 👋') }}</p>
            <h1 class="text-2xl font-bold text-text-primary">{{ $user->full_name }}</h1>
        </div>
        <div class="w-12 h-12 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
            {{ strtoupper(substr($user->full_name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->full_name)[1] ?? '', 0, 1)) }}
        </div>
    </div>

    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 rounded-2xl p-5 text-white shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <span class="text-purple-200 text-sm font-medium">{{ __('Total Wallet Balance') }}</span>
            <div class="flex items-center gap-2">
                <button wire:click="syncBalance" wire:loading.attr="disabled" :disabled="$isSyncing"
                        class="flex items-center gap-1.5 text-purple-200 hover:text-white text-xs transition-colors bg-white/10 hover:bg-white/20 px-2 py-1 rounded-lg">
                    <x-lucide-refresh-cw class="w-3 h-3 {{ $isSyncing ? 'animate-spin' : '' }}" />
                    <span class="{{ $isSyncing ? 'hidden' : '' }}">{{ __('Sync') }}</span>
                </button>
                <span class="text-purple-200 text-xs uppercase tracking-wider">{{ $ajoOwner?->business_name ?? 'Ajo Business' }}</span>
            </div>
        </div>
        <p class="text-3xl font-bold mb-1">₦{{ number_format($balance, 2) }}</p>

        @if($accountDisplay)
        <div class="mt-4 pt-4 border-t border-white/10 space-y-3">
            <div>
                <p class="text-purple-200 text-xs mb-1">{{ $siteSettings->site_name ?? 'PayEase' }} {{ __('Account') }}</p>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-mono text-lg font-bold tracking-wider">{{ $user->phone_number }}</p>
                        <p class="text-purple-200 text-xs">{{ __('Phone number — send & receive on') }} {{ $siteSettings->site_name ?? 'PayEase' }}</p>
                    </div>
                    <button onclick="navigator.clipboard.writeText('{{ $user->phone_number }}'); $dispatch('notify-success', {message: 'Phone number copied!'})"
                            class="text-purple-200 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <x-lucide-copy class="w-4 h-4" />
                    </button>
                </div>
            </div>
            @if(!$accountDisplay['is_pending'])
            <div class="pt-3 border-t border-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-200 text-xs mb-0.5">{{ __('Bank Account') }}</p>
                        <p class="font-mono text-lg font-bold tracking-wider">
                            {{ $accountDisplay['formatted_account_number'] }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-purple-200 text-xs mb-0.5">{{ __('Bank') }}</p>
                        <p class="font-medium">{{ $accountDisplay['partner'] }}</p>
                    </div>
                </div>
            </div>
            @endif
            @if($accountDisplay['is_pending'])
            <div class="flex items-center gap-1.5 text-yellow-300 text-xs">
                <x-lucide-alert-circle class="w-3.5 h-3.5" />
                <span>{{ __('Bank account pending — complete verification') }}</span>
            </div>
            @endif
        </div>
        @endif
    </div>

    <!-- KYC Banner -->
    @if($currentTier < 3 && $kycMessage)
    <a href="{{ route('ajo-owner.kyc') }}" wire:navigate class="block bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 hover:shadow-sm transition-shadow">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/40 flex items-center justify-center flex-shrink-0">
                <x-lucide-shield class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-yellow-800 dark:text-yellow-200 text-sm">{{ __('Verification Required') }}</p>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $currentTier === 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                        {{ __('Tier') }} {{ $currentTier }}
                    </span>
                </div>
                <p class="text-yellow-700 dark:text-yellow-300 text-sm mt-1">{{ $kycMessage }}</p>
            </div>
            <x-lucide-chevron-right class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-1" />
        </div>
    </a>
    @endif

    <!-- Quick Actions Grid -->
    <div class="grid grid-cols-4 gap-3">
        <a href="{{ route('ajo-owner.add-fund') }}" wire:navigate class="flex flex-col items-center gap-2 p-3 rounded-xl bg-surface border border-border shadow-sm hover:shadow-md transition-shadow active:scale-95">
            <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <x-lucide-plus-circle class="w-5 h-5 text-emerald-600" />
            </div>
            <span class="text-xs font-medium text-text-primary text-center">{{ __('Add Fund') }}</span>
        </a>
        <a href="{{ route('ajo-owner.send-fund') }}" wire:navigate class="flex flex-col items-center gap-2 p-3 rounded-xl bg-surface border border-border shadow-sm hover:shadow-md transition-shadow active:scale-95">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <x-lucide-send class="w-5 h-5 text-blue-600" />
            </div>
            <span class="text-xs font-medium text-text-primary text-center">{{ __('Send Fund') }}</span>
        </a>
        <a href="{{ route('ajo-owner.groups.create') }}" wire:navigate class="flex flex-col items-center gap-2 p-3 rounded-xl bg-surface border border-border shadow-sm hover:shadow-md transition-shadow active:scale-95">
            <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <x-lucide-plus class="w-5 h-5 text-purple-600" />
            </div>
            <span class="text-xs font-medium text-text-primary text-center">{{ __('New Group') }}</span>
        </a>
        <a href="{{ route('ajo-owner.pay-bills') }}" wire:navigate class="flex flex-col items-center gap-2 p-3 rounded-xl bg-surface border border-border shadow-sm hover:shadow-md transition-shadow active:scale-95">
            <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                <x-lucide-receipt class="w-5 h-5 text-yellow-600" />
            </div>
            <span class="text-xs font-medium text-text-primary text-center">{{ __('Pay Bills') }}</span>
        </a>
    </div>

    <!-- Secondary Quick Actions -->
    <div class="grid grid-cols-3 gap-3">
        <a href="{{ route('ajo-owner.agents') }}" wire:navigate class="flex flex-col items-center gap-2 p-3 rounded-xl bg-surface border border-border shadow-sm hover:shadow-md transition-shadow active:scale-95">
            <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                <x-lucide-users class="w-5 h-5 text-indigo-600" />
            </div>
            <span class="text-xs font-medium text-text-primary text-center">{{ __('Agents') }}</span>
        </a>
        <a href="{{ route('ajo-owner.payouts') }}" wire:navigate class="flex flex-col items-center gap-2 p-3 rounded-xl bg-surface border border-border shadow-sm hover:shadow-md transition-shadow active:scale-95">
            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <x-lucide-banknote class="w-5 h-5 text-amber-600" />
            </div>
            <span class="text-xs font-medium text-text-primary text-center">{{ __('Payouts') }}</span>
        </a>
        <a href="{{ route('ajo-owner.kyc') }}" wire:navigate class="flex flex-col items-center gap-2 p-3 rounded-xl bg-surface border border-border shadow-sm hover:shadow-md transition-shadow active:scale-95">
            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <x-lucide-shield-check class="w-5 h-5 text-red-600" />
            </div>
            <span class="text-xs font-medium text-text-primary text-center">{{ __('Verify') }}</span>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-surface rounded-xl p-4 border border-border shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-text-secondary text-xs font-medium">{{ __('Total Groups') }}</span>
                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <x-lucide-folder class="w-4 h-4 text-purple-600" />
                </div>
            </div>
            <p class="text-2xl font-bold text-text-primary">{{ number_format($totalGroups) }}</p>
        </div>

        <div class="bg-surface rounded-xl p-4 border border-border shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-text-secondary text-xs font-medium">{{ __('Total Members') }}</span>
                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <x-lucide-users class="w-4 h-4 text-blue-600" />
                </div>
            </div>
            <p class="text-2xl font-bold text-text-primary">{{ number_format($totalMembers) }}</p>
        </div>

        <div class="bg-surface rounded-xl p-4 border border-border shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-text-secondary text-xs font-medium">{{ __('Pool Value') }}</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <x-lucide-wallet class="w-4 h-4 text-emerald-600" />
                </div>
            </div>
            <p class="text-2xl font-bold text-emerald-600">₦{{ number_format($totalPoolValue, 0) }}</p>
        </div>

        <div class="bg-surface rounded-xl p-4 border border-border shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-text-secondary text-xs font-medium">{{ __('This Month') }}</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <x-lucide-calendar class="w-4 h-4 text-indigo-600" />
                </div>
            </div>
            <p class="text-2xl font-bold text-indigo-600">₦{{ number_format($thisMonthsCollections, 0) }}</p>
        </div>
    </div>

    <!-- Attention Items -->
    @if($attentionGroups->isNotEmpty())
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold text-text-primary">{{ __('Needs Attention') }}</h2>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                {{ $attentionGroups->count() }} {{ __('items') }}
            </span>
        </div>

        <div class="space-y-3">
            @foreach($attentionGroups as $item)
            @php
                $group = $item['group'];
                $nextPayout = $item['next_payout'];
                $isOverdue = ($nextPayout['scheduled_date'] ?? null) && $nextPayout['scheduled_date']->lt(now());
            @endphp
            <a href="{{ route('ajo-owner.groups.detail', $group->id) }}" wire:navigate
               class="block bg-surface rounded-xl border {{ $isOverdue ? 'border-red-200 dark:border-red-800' : 'border-amber-200 dark:border-amber-800' }} p-4 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-text-primary">{{ $group->name }}</h3>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $isOverdue ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $isOverdue ? __('Overdue') : __('Due Soon') }}
                    </span>
                </div>
                @if($nextPayout)
                <div class="flex items-center gap-4 text-sm text-text-secondary">
                    @if($nextPayout['amount'])
                    <span>₦{{ number_format($nextPayout['amount'], 2) }}</span>
                    @endif
                    @if($nextPayout['scheduled_date'])
                    <span>{{ $nextPayout['scheduled_date']->format('d M Y') }}</span>
                    @endif
                    @if($nextPayout['scheduled_date'])
                    @php
                        $daysUntil = (int) now()->diffInDays($nextPayout['scheduled_date'], false);
                    @endphp
                    <span class="{{ $daysUntil < 0 ? 'text-red-600 font-medium' : 'text-amber-600' }}">
                        {{ $daysUntil <= 0 ? __('Overdue') : $daysUntil . 'd' }}
                    </span>
                    @endif
                </div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Recent Groups -->
    @if($totalGroups > 0)
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold text-text-primary">{{ __('Your Groups') }}</h2>
            <a href="{{ route('ajo-owner.groups') }}" wire:navigate class="text-sm text-purple-600 hover:text-purple-700 font-medium">{{ __('View all') }}</a>
        </div>

        <div class="space-y-3">
            @foreach($groups->take(5) as $group)
            @php
                $progress = (new \App\Services\AjoService)->getCycleProgress($group);
            @endphp
            <a href="{{ route('ajo-owner.groups.detail', $group->id) }}" wire:navigate
               class="block bg-surface rounded-xl border border-border p-4 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-semibold text-text-primary">{{ $group->name }}</h3>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ $group->status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                           ($group->status === 'completed' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400') }}">
                        {{ ucfirst($group->status) }}
                    </span>
                </div>
                <div class="flex items-center gap-4 text-sm text-text-secondary">
                    <span>₦{{ number_format($group->contribution_amount, 0) }}/{{ $group->frequency }}</span>
                    <span>{{ $group->members_count ?? 0 }}/{{ $group->max_members }} {{ __('members') }}</span>
                </div>
                <div class="mt-3">
                    <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-600 rounded-full" style="width: {{ $progress['percentage'] }}%"></div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>
