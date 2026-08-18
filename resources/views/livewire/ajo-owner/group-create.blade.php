<div class="px-4 py-6 md:p-8 max-w-lg mx-auto relative overflow-hidden">

    <!-- Header/Title -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Create New Ajo') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Set up a new savings group and assign an agent.') }}</p>

        <!-- Step Indicator -->
        <div class="flex items-center gap-2 mt-6">
            @for($i = 1; $i <= 4; $i++)
                <div class="h-2 flex-1 rounded-full transition-colors {{ $step >= $i ? 'bg-purple-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
            @endfor
        </div>
        <div class="flex justify-between mt-2">
            @foreach(['Type', 'Details', 'Agent', 'Review'] as $i => $label)
                <span class="text-[10px] font-semibold transition-colors {{ $step === $i + 1 ? 'text-purple-600' : 'text-gray-400' }}">{{ $label }}</span>
            @endforeach
        </div>
    </div>

    <!-- ═══ Step 1: Choose Model Type ═══ -->
    @if($step === 1)
    <div wire:key="step-1" class="space-y-4">
        <p class="text-sm text-text-secondary mb-2">{{ __('How should this group work?') }}</p>

        <!-- Savings Pool (Recommended) -->
        <button wire:click="$set('modelType', 'savings_pool')"
                class="w-full text-left p-5 rounded-card border-2 transition-all {{ $modelType === 'savings_pool' ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20 shadow-elevation-1' : 'border-border bg-surface hover:border-purple-300' }}">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 {{ $modelType === 'savings_pool' ? 'bg-purple-600 text-white' : 'bg-purple-100 text-purple-600' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-text-primary">Savings Pool</h3>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-purple-100 text-purple-700">Flexible</span>
                    </div>
                    <p class="text-sm text-text-secondary mt-1 leading-relaxed">Members contribute different amounts. At the end of the period, the pool is split proportionally minus your management fee. No rotation — everyone gets paid back.</p>
                </div>
                @if($modelType === 'savings_pool')
                    <svg class="w-5 h-5 text-purple-600 shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                @endif
            </div>
        </button>

        <!-- Traditional Rotational -->
        <button wire:click="$set('modelType', 'rotational')"
                class="w-full text-left p-5 rounded-card border-2 transition-all {{ $modelType === 'rotational' ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20 shadow-elevation-1' : 'border-border bg-surface hover:border-purple-300' }}">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 {{ $modelType === 'rotational' ? 'bg-purple-600 text-white' : 'bg-purple-100 text-purple-600' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-text-primary">Traditional Rotation</h3>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">Classic</span>
                    </div>
                    <p class="text-sm text-text-secondary mt-1 leading-relaxed">Fixed contribution amount. Members take turns receiving the full pool each cycle. The classic Ajo model.</p>
                </div>
                @if($modelType === 'rotational')
                    <svg class="w-5 h-5 text-purple-600 shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                @endif
            </div>
        </button>

        <!-- Continuous Pool -->
        <button wire:click="$set('modelType', 'continuous_pool')"
                class="w-full text-left p-5 rounded-card border-2 transition-all {{ $modelType === 'continuous_pool' ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20 shadow-elevation-1' : 'border-border bg-surface hover:border-purple-300' }}">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 {{ $modelType === 'continuous_pool' ? 'bg-purple-600 text-white' : 'bg-purple-100 text-purple-600' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-text-primary">Continuous Pool</h3>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">Recurring</span>
                    </div>
                    <p class="text-sm text-text-secondary mt-1 leading-relaxed">Flexible contributions collected at set intervals (daily, weekly, etc.) over a longer duration. Payouts each interval from the pool proportionally.</p>
                </div>
                @if($modelType === 'continuous_pool')
                    <svg class="w-5 h-5 text-purple-600 shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                @endif
            </div>
        </button>

        <div class="pt-4">
            <button wire:click="nextStep"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1">
                {{ __('Continue') }}
            </button>
        </div>
    </div>
    @endif

    <!-- ═══ Step 2: Group Details ═══ -->
    @if($step === 2)
    <div wire:key="step-2" class="space-y-5">

        <!-- Group Name -->
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Group Name') }}</label>
            <input type="text" wire:model="name"
                   class="w-full rounded-xl border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 transition-all"
                   placeholder="{{ match($modelType) { 'savings_pool' => 'e.g. Market Women Savings', 'continuous_pool' => 'e.g. Daily Traders Pool', default => 'e.g. Market Women Daily' } }}">
            @error('name') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Description (Savings Pool / Continuous Pool) -->
        @if(in_array($modelType, ['savings_pool', 'continuous_pool']))
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Description') }} <span class="text-text-secondary font-normal">({{ __('optional') }})</span></label>
            <textarea wire:model="description" rows="2" maxlength="1000"
                      class="w-full rounded-xl border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 transition-all resize-none"
                      placeholder="What is this savings pool for?"></textarea>
            @error('description') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>
        @endif

        <!-- Fixed Contribution (Rotational only) -->
        @if($modelType === 'rotational')
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Contribution Amount') }}</label>
            <div class="flex">
                <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-border bg-gray-50 dark:bg-gray-800 text-text-secondary font-bold">₦</span>
                <input type="text" wire:model="amount"
                       class="flex-1 w-full rounded-none rounded-r-xl border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 tabular-nums transition-all"
                       placeholder="5000">
            </div>
            @error('amount') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Frequency (Rotational only) -->
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Frequency') }}</label>
            <div class="flex p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
                @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $val => $label)
                    <button type="button" wire:click="$set('frequency', '{{ $val }}')"
                            class="flex-1 py-2.5 text-sm font-medium rounded-lg transition-all {{ $frequency === $val ? 'bg-surface shadow-sm text-purple-600' : 'text-text-secondary hover:text-text-primary' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            @error('frequency') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>
        @endif

        <!-- Owner Fee % (Savings Pool / Continuous Pool) -->
        @if(in_array($modelType, ['savings_pool', 'continuous_pool']))
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Your Management Fee') }}</label>
            <div class="flex items-center gap-3">
                <div class="flex-1 flex">
                    <input type="text" wire:model="ownerFee" min="0" max="50"
                           class="w-full rounded-xl border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 tabular-nums transition-all"
                           placeholder="5">
                    <span class="inline-flex items-center px-4 rounded-r-xl border border-l-0 border-border bg-gray-50 dark:bg-gray-800 text-text-secondary font-bold">%</span>
                </div>
            </div>
            <p class="text-xs text-text-secondary mt-1.5">{{ __('Percentage of total pool taken as your management fee. E.g. 5% of ₦100,000 = ₦5,000.') }}</p>
            @error('ownerFee') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Contribution Interval (Continuous Pool only) -->
        @if($modelType === 'continuous_pool')
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Collect Every') }}</label>
            <div class="grid grid-cols-3 gap-2 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
                @foreach(['daily' => '1 Day', 'every_2_days' => '2 Days', 'every_3_days' => '3 Days', 'every_5_days' => '5 Days', 'weekly' => '1 Week', 'biweekly' => '2 Weeks', 'monthly' => '1 Month'] as $val => $label)
                    <button type="button" wire:click="$set('contributionInterval', '{{ $val }}')"
                            class="py-2.5 text-sm font-medium rounded-lg transition-all {{ $contributionInterval === $val ? 'bg-surface shadow-sm text-purple-600' : 'text-text-secondary hover:text-text-primary' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <p class="text-xs text-text-secondary mt-1.5">{{ __('How often members contribute. Collection cycles repeat at this interval.') }}</p>
            @error('contributionInterval') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>
        @endif

        <!-- Collection Period -->
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Collection Period') }}</label>
            <div class="grid grid-cols-3 gap-2 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
                @foreach([1 => '1 Day', 7 => '7 Days', 14 => '14 Days', 30 => '1 Month', 60 => '2 Months', 90 => '3 Months'] as $days => $label)
                    <button type="button" wire:click="$set('collectionPeriodDays', '{{ $days }}')"
                            class="py-2.5 text-sm font-medium rounded-lg transition-all {{ $collectionPeriodDays == $days ? 'bg-surface shadow-sm text-purple-600' : 'text-text-secondary hover:text-text-primary' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <p class="text-xs text-text-secondary mt-1.5">{{ __('How long members have to contribute before payout.') }}</p>
            @error('collectionPeriodDays') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Min/Max Contribution (Flexible models) -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Min Contribution') }} <span class="text-text-secondary font-normal">({{ __('optional') }})</span></label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-border bg-gray-50 dark:bg-gray-800 text-text-secondary text-sm font-bold">₦</span>
                    <input type="text" wire:model="minContribution"
                           class="flex-1 w-full rounded-none rounded-r-xl border border-border bg-background text-text-primary px-3 py-3 outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 tabular-nums text-sm transition-all"
                           placeholder="1000">
                </div>
                @error('minContribution') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Max Contribution') }} <span class="text-text-secondary font-normal">({{ __('optional') }})</span></label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-border bg-gray-50 dark:bg-gray-800 text-text-secondary text-sm font-bold">₦</span>
                    <input type="text" wire:model="maxContribution"
                           class="flex-1 w-full rounded-none rounded-r-xl border border-border bg-background text-text-primary px-3 py-3 outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 tabular-nums text-sm transition-all"
                           placeholder="50000">
                </div>
                @error('maxContribution') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Target Pool (Flexible models) -->
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Target Pool Amount') }} <span class="text-text-secondary font-normal">({{ __('optional') }})</span></label>
            <div class="flex">
                <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-border bg-gray-50 dark:bg-gray-800 text-text-secondary font-bold">₦</span>
                <input type="text" wire:model="targetPool"
                       class="flex-1 w-full rounded-none rounded-r-xl border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 tabular-nums transition-all"
                       placeholder="100000">
            </div>
            <p class="text-xs text-text-secondary mt-1.5">{{ __('The total amount you aim to collect. Used for progress tracking.') }}</p>
            @error('targetPool') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>
        @endif

        <!-- Members Count (Both models) -->
        <div class="{{ $modelType === 'rotational' ? 'grid grid-cols-2 gap-4' : '' }}">
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Max Members') }}</label>
                <input type="number" wire:model="membersCount" min="2" max="1000"
                       class="w-full rounded-xl border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 transition-all"
                       placeholder="{{ in_array($modelType, ['savings_pool', 'continuous_pool']) ? '50' : '10' }}">
                @error('membersCount') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Payout Order (Rotational only) -->
            @if($modelType === 'rotational')
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Payout Order') }}</label>
                <div class="flex p-1 bg-gray-100 dark:bg-gray-800 rounded-xl h-[50px]">
                    <button type="button" wire:click="$set('payoutOrder', 'fixed')"
                            class="flex-1 text-sm font-medium rounded-lg transition-all {{ $payoutOrder === 'fixed' ? 'bg-surface shadow-sm text-purple-600' : 'text-text-secondary' }}">
                        {{ __('Fixed') }}
                    </button>
                    <button type="button" wire:click="$set('payoutOrder', 'random')"
                            class="flex-1 text-sm font-medium rounded-lg transition-all {{ $payoutOrder === 'random' ? 'bg-surface shadow-sm text-purple-600' : 'text-text-secondary' }}">
                        {{ __('Random') }}
                    </button>
                </div>
            </div>
            @endif
        </div>

        <!-- Info Card (Savings Pool only) -->
        @if($modelType === 'savings_pool')
        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-4">
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-purple-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <div class="text-sm text-purple-800 dark:text-purple-300">
                    <p class="font-semibold mb-1">How Savings Pool works:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-purple-700 dark:text-purple-400">
                        <li>Members contribute any amount within the range you set</li>
                        <li>At the end of the collection period, the pool is calculated</li>
                        <li>Your management fee ({{ $ownerFee ?: '0' }}%) is deducted first</li>
                        <li>Each member gets back their share proportional to their contribution</li>
                    </ul>
                </div>
            </div>
        </div>
        @endif

        @if($modelType === 'continuous_pool')
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-blue-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-semibold mb-1">How Continuous Pool works:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-blue-700 dark:text-blue-400">
                        <li>Members contribute at the chosen interval ({{ str_replace('_', ' ', $contributionInterval) }})</li>
                        <li>Each cycle runs for the interval duration, then payouts are distributed</li>
                        <li>Total cycles = {{ (int)$collectionPeriodDays }} days / {{ app(\App\Services\AjoService::class)->getIntervalDays($contributionInterval) }} days per cycle</li>
                        <li>Your management fee ({{ $ownerFee ?: '0' }}%) is deducted from each cycle's pool</li>
                        <li>Members get back their share proportional to their contribution each cycle</li>
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <div class="flex gap-3 pt-2">
            <button type="button" wire:click="prevStep"
                    class="px-6 py-3.5 rounded-xl border-2 border-purple-600 text-purple-600 font-semibold hover:bg-purple-50 transition-all active:scale-[0.98]">
                {{ __('Back') }}
            </button>
            <button type="button" wire:click="nextStep"
                    class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1">
                {{ __('Continue') }}
            </button>
        </div>
    </div>
    @endif

    <!-- ═══ Step 3: Assign Agents ═══ -->
    @if($step === 3)
    <div wire:key="step-3" class="space-y-5">

        <div>
            <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Search Field Agents') }}</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                <input type="text" wire:model.live="searchAgent"
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-600/30 focus:border-purple-600 transition-all"
                       placeholder="Name or location...">
            </div>
        </div>

        @if(count($selectedAgentIds) > 0)
        <div class="flex flex-wrap gap-2">
            @foreach($selectedAgents as $sa)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-sm font-medium">
                    {{ $sa->user?->full_name ?? $sa->business_name }}
                    <button wire:click="toggleAgent('{{ $sa->id }}')" class="hover:text-purple-900 dark:hover:text-purple-100">
                        <x-lucide-x class="w-3.5 h-3.5" />
                    </button>
                </span>
            @endforeach
        </div>
        @endif

        <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1">
            @forelse($availableAgents as $agent)
                <div wire:click="toggleAgent('{{ $agent->id }}')"
                     class="p-4 rounded-card border cursor-pointer transition-all {{ in_array($agent->id, $selectedAgentIds) ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20' : 'border-border bg-surface hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-secondary/10 text-secondary flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-text-primary text-sm">{{ $agent->user?->full_name ?? $agent->business_name }}</h4>
                                <p class="text-xs text-text-secondary">{{ $agent->business_name }} • {{ $agent->lga }}, {{ $agent->state }}</p>
                            </div>
                        </div>
                        @if(in_array($agent->id, $selectedAgentIds))
                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-4 rounded-card border border-dashed border-border text-sm text-text-secondary text-center">
                    {{ __('No matching agents available yet.') }}
                </div>
            @endforelse
        </div>

        @if($availableAgents->hasPages())
            <div class="flex items-center justify-center gap-2 text-sm">
                @if($availableAgents->currentPage() > 1)
                    <button wire:click="gotoAgentPage({{ $availableAgents->currentPage() - 1 }})" class="px-3 py-1 rounded border border-border text-text-secondary hover:text-text-primary hover:bg-gray-50 transition-colors">{{ __('Prev') }}</button>
                @endif
                @foreach(range(1, $availableAgents->lastPage()) as $page)
                    <button wire:click="gotoAgentPage({{ $page }})" class="px-3 py-1 rounded transition-colors {{ $page === $availableAgents->currentPage() ? 'bg-purple-600 text-white' : 'border border-border text-text-secondary hover:text-text-primary' }}">{{ $page }}</button>
                @endforeach
                @if($availableAgents->currentPage() < $availableAgents->lastPage())
                    <button wire:click="gotoAgentPage({{ $availableAgents->currentPage() + 1 }})" class="px-3 py-1 rounded border border-border text-text-secondary hover:text-text-primary hover:bg-gray-50 transition-colors">{{ __('Next') }}</button>
                @endif
            </div>
        @endif

        <p class="text-xs text-text-secondary">{{ __('Select one or more agents. The first selected agent will be the primary managing agent.') }}</p>
        @error('selectedAgentIds') <p class="text-sm text-danger">{{ $message }}</p> @enderror

        <div class="flex gap-3 pt-2">
            <button type="button" wire:click="prevStep"
                    class="px-6 py-3.5 rounded-xl border-2 border-purple-600 text-purple-600 font-semibold hover:bg-purple-50 transition-all active:scale-[0.98]">
                {{ __('Back') }}
            </button>
            <button type="button" wire:click="nextStep" {{ empty($selectedAgentIds) ? 'disabled' : '' }}
                    class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 disabled:opacity-50 disabled:cursor-not-allowed">
                {{ __('Continue') }}
            </button>
        </div>
    </div>
    @endif

    <!-- ═══ Step 4: Review ═══ -->
    @if($step === 4)
    <div wire:key="step-4" class="space-y-5">

        <div class="bg-surface rounded-card p-6 shadow-elevation-1 border border-border relative overflow-hidden">
            @if($isLoading)
            <div class="absolute inset-0 bg-surface/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center">
                <svg class="w-8 h-8 text-purple-600 animate-spin mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <p class="text-sm font-medium text-text-primary animate-pulse">{{ __('Creating group...') }}</p>
            </div>
            @endif

            <h3 class="font-bold text-lg text-text-primary mb-4 border-b border-border pb-2">{{ __('Review Group Details') }}</h3>

            <div class="space-y-3.5 text-sm">
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Group Name') }}</span>
                    <span class="font-bold text-text-primary">{{ $name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Model') }}</span>
                    <span class="font-medium text-text-primary">{{ match($modelType) { 'savings_pool' => 'Savings Pool', 'continuous_pool' => 'Continuous Pool', default => 'Traditional Rotation' } }}</span>
                </div>

                @if($modelType === 'rotational')
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Contribution') }}</span>
                    <span class="font-bold text-text-primary tabular-nums">₦{{ number_format((float)$amount) }} <span class="text-xs font-normal text-text-secondary uppercase">/ {{ $frequency }}</span></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Payout Order') }}</span>
                    <span class="font-medium text-text-primary">{{ ucfirst($payoutOrder) }}</span>
                </div>
                @endif

                @if(in_array($modelType, ['savings_pool', 'continuous_pool']))
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Management Fee') }}</span>
                    <span class="font-medium text-primary">{{ $ownerFee }}%</span>
                </div>
                @if($modelType === 'continuous_pool')
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Collect Every') }}</span>
                    <span class="font-medium text-text-primary">{{ str_replace('_', ' ', ucfirst($contributionInterval)) }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Collection Period') }}</span>
                    <span class="font-medium text-text-primary">{{ match((int)$collectionPeriodDays) { 1 => '1 Day', 7 => '7 Days', 14 => '14 Days', 30 => '1 Month', 60 => '2 Months', 90 => '3 Months', default => $collectionPeriodDays . ' Days' } }}</span>
                </div>
                @if($minContribution)
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Min Contribution') }}</span>
                    <span class="font-medium text-text-primary tabular-nums">₦{{ number_format((float)$minContribution) }}</span>
                </div>
                @endif
                @if($maxContribution)
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Max Contribution') }}</span>
                    <span class="font-medium text-text-primary tabular-nums">₦{{ number_format((float)$maxContribution) }}</span>
                </div>
                @endif
                @if($targetPool)
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Target Pool') }}</span>
                    <span class="font-medium text-text-primary tabular-nums">₦{{ number_format((float)$targetPool) }}</span>
                </div>
                @endif
                @endif

                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Max Members') }}</span>
                    <span class="font-medium text-text-primary">{{ $membersCount }}</span>
                </div>
                <div class="flex justify-between border-t border-border pt-3.5">
                    <span class="text-text-secondary">{{ __('Agents') }}</span>
                    <div class="text-right">
                        @foreach($selectedAgents as $i => $sa)
                            <span class="font-medium text-text-primary flex items-center gap-1 justify-end">
                                <svg class="w-3 h-3 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                {{ $sa->user?->full_name ?? $sa->business_name }}{{ $loop->last ? '' : ',' }}
                                @if($i === 0) <span class="text-[10px] font-bold text-purple-600 uppercase">Primary</span> @endif
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            @if(in_array($modelType, ['savings_pool', 'continuous_pool']))
            <div class="mt-5 bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-100 dark:border-purple-800">
                <p class="text-sm text-purple-800 dark:text-purple-300">
                    <strong>{{ __('Management Fee:') }}</strong> {{ $ownerFee }}% of total pool
                    @if($targetPool)
                        <br><strong>{{ __('Target Pool:') }}</strong> ₦{{ number_format((float)$targetPool) }}
                    @endif
                    @if($modelType === 'continuous_pool')
                        <br><strong>{{ __('Collect Every:') }}</strong> {{ str_replace('_', ' ', $contributionInterval) }}
                        <br><strong>{{ __('Total Cycles:') }}</strong> {{ (int)$collectionPeriodDays }} days / {{ app(\App\Services\AjoService::class)->getIntervalDays($contributionInterval) }} days = {{ (int) ceil((int)$collectionPeriodDays / app(\App\Services\AjoService::class)->getIntervalDays($contributionInterval)) }} cycles
                    @endif
                    <br><strong>{{ __('Collection ends:') }}</strong> {{ now()->addDays((int)$collectionPeriodDays)->format('M j, Y') }}
                </p>
            </div>
            @else
            <div class="mt-5 bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-100 dark:border-purple-800">
                <p class="text-sm text-purple-800 dark:text-purple-300">
                    <strong>{{ __('Total Pool Value:') }}</strong> ₦{{ number_format((float)$amount * (int)$membersCount) }} {{ __('per cycle.') }}
                </p>
            </div>
            @endif
        </div>

        <div class="flex gap-3 pt-2 relative">
            <button type="button" wire:click="prevStep" wire:loading.attr="disabled"
                    class="px-6 py-3.5 rounded-xl border-2 border-purple-600 text-purple-600 font-semibold hover:bg-purple-50 transition-all active:scale-[0.98]">
                {{ __('Back') }}
            </button>
            <button type="button" wire:click="createGroup" wire:loading.attr="disabled"
                    class="flex-[2] bg-purple-600 hover:bg-purple-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="createGroup">{{ __('Create Group') }}</span>
                <span wire:loading wire:target="createGroup" class="flex items-center gap-2">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    {{ __('Creating...') }}
                </span>
            </button>
        </div>
    </div>
    @endif

    <!-- ═══ Step 5: Success ═══ -->
    @if($step === 5)
    <div wire:key="step-5" class="text-center space-y-6 pt-8">
        <div class="mx-auto w-24 h-24 rounded-full bg-primary-light flex items-center justify-center">
            <svg class="w-12 h-12 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"
                      stroke-dasharray="24" stroke-dashoffset="24"
                      x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)"
                      :class="show ? 'animate-[dash_0.5s_ease-out_forwards]' : ''" />
            </svg>
        </div>

        <style>
            @keyframes dash {
                to { stroke-dashoffset: 0; }
            }
        </style>

        <div>
            <h2 class="text-2xl font-bold text-text-primary">{{ __('Group Created!') }}</h2>
            <p class="text-text-secondary mt-2 text-sm"><strong>{{ $name }}</strong> {{ __('has been created successfully and is ready for members.') }}</p>
        </div>

        <div class="space-y-3 pt-6">
            <a href="{{ route('ajo-owner.groups.detail', $createdGroupId) }}" wire:navigate
               class="block w-full py-3.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold transition-all active:scale-[0.98] text-center shadow-elevation-1">
                {{ __('Go to Group') }}
            </a>
            <a href="{{ route('ajo-owner.dashboard') }}" wire:navigate
               class="block w-full py-3.5 bg-gray-100 text-text-primary hover:bg-gray-200 rounded-xl font-semibold transition-all text-center">
                {{ __('Back to Dashboard') }}
            </a>
        </div>
    </div>
    @endif

</div>
