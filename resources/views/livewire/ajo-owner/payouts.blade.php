<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Payouts Schedule') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Monitor and process member payouts across all groups.') }}</p>
        </div>
        <div class="flex gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-700">
                ₦{{ number_format($totalPool, 2) }} {{ __('Total Pool') }}
            </span>
        </div>
    </div>

    <!-- Filter Chips -->
    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
        <button wire:click="setFilter('upcoming')" class="{{ $filter === 'upcoming' ? 'bg-purple-600 text-white border-purple-600' : 'bg-surface text-text-secondary border-border hover:bg-gray-50' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('Upcoming') }}</button>
        <button wire:click="setFilter('completed')" class="{{ $filter === 'completed' ? 'bg-purple-600 text-white border-purple-600' : 'bg-surface text-text-secondary border-border hover:bg-gray-50' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">{{ __('Completed') }}</button>
        <button wire:click="setFilter('overdue')" class="{{ $filter === 'overdue' ? 'bg-orange-500 text-white border-orange-500' : 'bg-surface text-text-secondary border-border hover:bg-gray-50' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap flex items-center gap-1.5">
            {{ __('Overdue') }} <span class="bg-white/20 text-white text-[10px] px-1.5 rounded-full">{{ $payouts->count() }}</span>
        </button>
    </div>

    <!-- Data Table / List -->
    <div class="bg-surface rounded-card shadow-elevation-1 border border-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead>
                    <tr class="border-b border-border bg-background text-xs uppercase tracking-wider text-text-secondary">
                        <th class="px-4 py-3 font-medium">{{ __('Group Name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Recipient') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Amount') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Scheduled Date') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($payouts as $payout)
                        <tr class="{{ $payout['status'] === 'overdue' ? 'bg-orange-50 dark:bg-orange-900/10 border-l-4 border-orange-500' : '' }} hover:bg-background transition-colors group">
                            <td class="px-4 py-3 font-bold {{ $payout['status'] === 'overdue' ? 'text-orange-900 dark:text-orange-400' : 'text-text-primary' }} whitespace-nowrap">{{ $payout['group_name'] }}</td>
                            <td class="px-4 py-3 font-medium text-text-primary whitespace-nowrap">{{ $payout['recipient_name'] }}</td>
                            <td class="px-4 py-3 font-bold text-text-primary tabular-nums whitespace-nowrap">₦{{ number_format($payout['amount'], 2) }}</td>
                            <td class="px-4 py-3 text-sm {{ $payout['status'] === 'overdue' ? 'font-bold text-orange-600 dark:text-orange-400' : 'text-text-secondary' }} whitespace-nowrap">{{ $payout['scheduled_date']->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @if($payout['status'] === 'overdue')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-orange-100 text-orange-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span>
                                            {{ __('Overdue') }}
                                        </span>
                                @else
                                    <x-status-badge :status="$payout['status']" />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-xs text-text-secondary">{{ __('Cycle') }} {{ $payout['cycle_number'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-text-secondary">{{ __('No payouts found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($payouts->isEmpty())
            <div class="p-12 text-center flex flex-col items-center justify-center text-text-secondary">
                <div class="w-16 h-16 bg-background rounded-full flex items-center justify-center mb-4">
                    <x-lucide-check-circle class="w-8 h-8 text-primary" />
                </div>
                <h4 class="text-base font-bold text-text-primary mb-1">{{ __('All caught up!') }}</h4>
                <p class="text-sm">{{ __('There are no :filter payouts at this time.', ['filter' => $filter]) }}</p>
            </div>
        @endif
    </div>

</div>
