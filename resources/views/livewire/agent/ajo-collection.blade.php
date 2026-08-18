<div class="px-4 py-6 md:p-8 max-w-lg mx-auto relative overflow-hidden">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Ajo Collection') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Log member contributions for the groups you manage.') }}</p>
    </div>

    @if($step === 1)
        <div class="space-y-4">
            <h3 class="font-bold text-text-primary">{{ __('Step 1 - Select Group') }}</h3>
            @forelse($groups as $group)
                @php($progress = $groupProgress[$group->id] ?? ['paid_members' => 0, 'total_members' => 0, 'cycle_number' => 1])
                <button wire:click="selectGroup('{{ $group->id }}')" class="w-full text-left p-4 rounded-card border border-border bg-surface hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h4 class="font-bold text-text-primary">{{ $group->name }}</h4>
                            <p class="text-sm text-text-secondary">Cycle {{ $progress['cycle_number'] }} • ₦{{ number_format($group->contribution_amount, 2) }} / {{ ucfirst($group->frequency) }}</p>
                        </div>
                        <x-cycle-progress size="compact" :total="$progress['total_members']" :completed="$progress['paid_members']" />
                    </div>
                </button>
            @empty
                <div class="p-6 rounded-card border border-dashed border-border text-center text-text-secondary">
                    {{ __('No Ajo groups are assigned to you yet.') }}
                </div>
            @endforelse
        </div>
    @endif

    @if($step === 2)
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-text-primary">{{ __('Step 2 - Select Member') }}</h3>
                <button wire:click="goBack" class="text-sm font-medium text-secondary">{{ __('Change Group') }}</button>
            </div>

            <div class="rounded-card border border-border bg-surface p-4">
                <p class="font-bold text-text-primary">{{ $selectedGroup?->name }}</p>
                <p class="text-sm text-text-secondary">{{ $progress['paid_members'] }} {{ __('of') }} {{ $progress['total_members'] }} {{ __('paid in cycle') }} {{ $progress['cycle_number'] }}</p>
            </div>

            @forelse($members as $member)
                <button wire:click="selectMember('{{ $member->id }}')" class="w-full text-left p-4 rounded-card border border-border bg-surface hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-text-primary">{{ $member->user?->full_name }}</h4>
                            <p class="text-sm text-text-secondary">{{ $member->user?->phone_number }}</p>
                        </div>
                        <span class="text-xs font-medium text-text-secondary">Position {{ $member->position ?? '-' }}</span>
                    </div>
                </button>
            @empty
                <div class="p-6 rounded-card border border-dashed border-border text-center text-text-secondary">
                    {{ __('Everyone in this cycle has already been logged.') }}
                </div>
            @endforelse
        </div>
    @endif

    @if($step === 3)
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-text-primary">{{ __('Step 3 - Confirm & PIN') }}</h3>
                <button wire:click="goBack" class="text-sm font-medium text-secondary">{{ __('Back') }}</button>
            </div>

            <div class="bg-surface rounded-card p-6 shadow-elevation-1 border border-border space-y-4">
                <div class="flex justify-between text-sm">
                    <span class="text-text-secondary">{{ __('Group') }}</span>
                    <span class="font-bold text-text-primary">{{ $selectedGroup?->name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-text-secondary">{{ __('Member') }}</span>
                    <span class="font-bold text-text-primary">{{ $selectedMember?->user?->full_name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-text-secondary">{{ __('Contribution Amount') }}</span>
                    <span class="font-bold text-text-primary">₦{{ number_format($amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-text-secondary">{{ __('Ajo Commission') }}</span>
                    <span class="font-bold text-text-primary">₦{{ number_format($commission, 2) }}</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Agent PIN') }}</label>
                <input type="password" inputmode="numeric" maxlength="6" wire:model.live="agentPin" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 tracking-[0.4em] text-center focus:ring-secondary focus:border-secondary outline-none" placeholder="------">
                @error('agentPin') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
            </div>

            <x-button variant="primary" size="large" wire:click="confirmContribution" wire:loading.attr="disabled" class="w-full bg-secondary hover:bg-secondary/90">
                <span wire:loading.remove wire:target="confirmContribution">{{ __('Log Contribution') }}</span>
                <span wire:loading wire:target="confirmContribution" class="flex items-center justify-center gap-2">
                    <x-lucide-loader-2 class="w-5 h-5 animate-spin" />
                    {{ __('Processing...') }}
                </span>
            </x-button>
        </div>
    @endif

    @if($step === 4)
        <div class="text-center space-y-6 pt-8">
            @if($resultState === 'success')
                <div class="mx-auto w-24 h-24 rounded-full bg-primary-light flex items-center justify-center">
                    <svg class="w-12 h-12 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" stroke-dasharray="24" stroke-dashoffset="24" x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)" :class="show ? 'animate-[dash_0.5s_ease-out_forwards]' : ''" />
                    </svg>
                </div>
                <style>
                    @keyframes dash { to { stroke-dashoffset: 0; } }
                </style>
                <div>
                    <h2 class="text-2xl font-bold text-text-primary">{{ __('Contribution Logged') }}</h2>
                    <p class="text-text-secondary mt-2 text-sm">{{ $progress['paid_members'] }} {{ __('of') }} {{ $progress['total_members'] }} {{ __('paid this cycle.') }}</p>
                </div>
                <div class="bg-surface rounded-card p-6 shadow-elevation-1 border border-border space-y-3 text-left">
                    <div class="flex justify-between text-sm">
                        <span class="text-text-secondary">{{ __('Reference') }}</span>
                        <span class="font-medium text-text-primary">{{ $reference }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-text-secondary">{{ __('Commission Earned') }}</span>
                        <span class="font-medium text-text-primary">₦{{ number_format($commission, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-text-secondary">{{ __('Logged At') }}</span>
                        <span class="font-medium text-text-primary">{{ $date }}</span>
                    </div>
                </div>
                <div class="space-y-3">
                    <x-button variant="primary" size="large" wire:click="logAnother" class="w-full bg-secondary hover:bg-secondary/90">{{ __('Log Another') }}</x-button>
                    <x-button variant="secondary" size="large" wire:click="chooseAnotherGroup" class="w-full">{{ __('Choose Another Group') }}</x-button>
                </div>
            @else
                <div class="mx-auto w-24 h-24 rounded-full bg-red-100 flex items-center justify-center">
                    <x-lucide-x-circle class="w-12 h-12 text-danger" />
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-text-primary">{{ __('Unable to Log Contribution') }}</h2>
                    <p class="text-danger mt-2 text-sm">{{ $resultMessage }}</p>
                </div>
                <div class="space-y-3">
                    <x-button variant="primary" size="large" wire:click="goBack" class="w-full bg-secondary hover:bg-secondary/90">{{ __('Try Again') }}</x-button>
                    <x-button variant="secondary" size="large" wire:click="chooseAnotherGroup" class="w-full">{{ __('Back to Groups') }}</x-button>
                </div>
            @endif
        </div>
    @endif
</div>
