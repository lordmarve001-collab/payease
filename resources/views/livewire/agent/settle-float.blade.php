<div class="px-4 py-6 md:p-8 max-w-3xl mx-auto space-y-8">
    
    <div>
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Settle Float') }}</h1>
        <p class="text-text-secondary text-sm mt-1">{{ __('Declare a bank deposit to reduce your float balance.') }}</p>
    </div>

    @if($successMessage)
        <div class="p-4 rounded-btn bg-green-50 border border-green-200 text-green-800 text-sm flex items-start gap-3" x-data x-init="$dispatch('notify-success', '{{ $successMessage }}')">
            <x-lucide-check-circle class="w-5 h-5 shrink-0 mt-0.5" />
            <span>{{ $successMessage }}</span>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 rounded-btn bg-red-50 border border-red-200 text-red-800 text-sm flex items-start gap-3" x-data x-init="$dispatch('notify-error', '{{ $errorMessage }}')">
            <x-lucide-alert-circle class="w-5 h-5 shrink-0 mt-0.5" />
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    <!-- Current Float Balance -->
    <div class="bg-surface p-6 rounded-card shadow-elevation-1 border border-border">
        <p class="text-sm text-text-secondary mb-1">{{ __('Current Float Balance') }}</p>
        <p class="text-3xl font-bold tabular-nums text-text-primary">₦{{ number_format($agent?->float_balance ?? 0, 2) }}</p>
    </div>

    <!-- Settlement Form -->
    <form wire:submit="declare" class="bg-surface p-6 rounded-card shadow-elevation-1 border border-border space-y-5">
        <h3 class="text-lg font-bold text-text-primary">{{ __('Declare Bank Deposit') }}</h3>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Amount Declared (₦)') }}</label>
            <input type="number" step="0.01" wire:model="amount" class="block w-full px-4 py-2.5 border border-border rounded-btn bg-background text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" placeholder="Enter amount deposited" />
            @error('amount') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Bank Reference') }}</label>
            <input type="text" wire:model="bankReference" class="block w-full px-4 py-2.5 border border-border rounded-btn bg-background text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" placeholder="e.g. Transaction ID, teller number" />
            @error('bankReference') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Proof of Deposit (optional)') }}</label>
            <input type="file" wire:model="proofOfDeposit" accept="image/*" class="block w-full text-sm text-text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-btn file:border-0 file:text-sm file:font-semibold file:bg-primary-light file:text-primary hover:file:bg-primary/20 transition-colors cursor-pointer" />
            @error('proofOfDeposit') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            <div wire:loading wire:target="proofOfDeposit" class="mt-2 text-sm text-text-secondary">Uploading...</div>
        </div>

        <button type="submit" class="w-full py-3 bg-secondary hover:bg-secondary-dark text-white font-semibold rounded-btn transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2" wire:loading.attr="disabled">
            <x-lucide-loader class="w-4 h-4 animate-spin" wire:loading />
            Submit Settlement
        </button>
    </form>

    <!-- Settlement History -->
    <section>
        <h3 class="text-lg font-bold text-text-primary mb-4">{{ __('Settlement History') }}</h3>
        @if(count($settlements) > 0)
            <div class="space-y-2">
                @foreach($settlements as $settlement)
                    <div class="bg-surface p-4 rounded-card border border-border flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-text-primary">₦{{ number_format($settlement->amount_declared, 2) }}</p>
                            <p class="text-xs text-text-secondary">{{ $settlement->bank_reference ? 'Ref: ' . $settlement->bank_reference : '' }} &middot; {{ $settlement->created_at->format('M j, Y g:ia') }}</p>
                        </div>
                        <div>
                            @php
                                $statusClasses = [
                                    'pending_verification' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'verified' => 'bg-green-50 text-green-700 border-green-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                ];
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusClasses[$settlement->status] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                {{ ucwords(str_replace('_', ' ', $settlement->status)) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $settlements->links() }}
            </div>
        @else
            <p class="text-center text-text-secondary py-8">{{ __('No settlements declared yet.') }}</p>
        @endif
    </section>
</div>
