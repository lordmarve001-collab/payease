<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Request Float Top-Up') }}</h1>
        <p class="text-text-secondary text-sm mt-1">{{ __('Submit a request to increase your float balance.') }}</p>
    </div>

    @if($pendingTopUp)
        <div class="p-4 rounded-btn bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-start gap-3">
            <x-lucide-clock class="w-5 h-5 shrink-0 mt-0.5" />
            <span>{{ __('You already have a pending top-up request of') }} ₦{{ number_format($pendingTopUp->amount_requested, 2) }}. {{ __('Please wait for it to be reviewed.') }}</span>
        </div>
    @endif

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

    @if(!$pendingTopUp)
        <form wire:submit="submit" class="bg-surface p-6 rounded-card shadow-elevation-1 border border-border space-y-5">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Amount Requested (₦)') }}</label>
                <input type="number" step="0.01" wire:model="amount" class="block w-full px-4 py-2.5 border border-border rounded-btn bg-background text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" placeholder="Enter amount needed" />
                @error('amount') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Reason (optional)') }}</label>
                <textarea wire:model="reason" rows="3" class="block w-full px-4 py-2.5 border border-border rounded-btn bg-background text-text-primary placeholder-text-secondary focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" placeholder="e.g. High demand from customers, restocking float..."></textarea>
                @error('reason') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full py-3 bg-secondary hover:bg-secondary-dark text-white font-semibold rounded-btn transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2" wire:loading.attr="disabled">
                <x-lucide-loader class="w-4 h-4 animate-spin" wire:loading />
                Submit Request
            </button>
        </form>
    @endif

    <!-- Previous Requests -->
    <section>
        <h3 class="text-lg font-bold text-text-primary mb-4">{{ __('Previous Requests') }}</h3>
        @php $previousRequests = $agent?->floatTopUpRequests()->latest()->take(10)->get(); @endphp
        @if($previousRequests && count($previousRequests) > 0)
            <div class="space-y-2">
                @foreach($previousRequests as $req)
                    <div class="bg-surface p-4 rounded-card border border-border flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-text-primary">₦{{ number_format($req->amount_requested, 2) }}</p>
                            <p class="text-xs text-text-secondary">{{ $req->reason ?: 'No reason given' }} &middot; {{ $req->created_at->format('M j, Y g:ia') }}</p>
                        </div>
                        <div>
                            @php
                                $classes = [
                                    'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'approved' => 'bg-green-50 text-green-700 border-green-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                ];
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium border {{ $classes[$req->status] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-center text-text-secondary py-8">{{ __('No previous top-up requests.') }}</p>
        @endif
    </section>
</div>
