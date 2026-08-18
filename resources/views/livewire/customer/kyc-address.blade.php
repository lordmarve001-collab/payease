<div class="px-4 py-6 md:p-8 max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Proof of Address') }}</h1>
        <p class="text-text-secondary text-sm mt-1">{{ $upgradeMessage }}</p>
    </div>

    <form wire:submit="submit" class="space-y-6">
        <div class="bg-surface rounded-card border border-border p-6 space-y-4">
            <h3 class="font-semibold text-text-primary">{{ __('Upload Address Proof') }}</h3>
            <p class="text-sm text-text-secondary">{{ __('Upload a recent utility bill, bank statement, or government-issued document showing your name and address.') }}</p>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Document (photo/scan)') }}</label>
                <input type="file" wire:model="addressDocument" accept="image/*"
                       class="w-full">
                @error('addressDocument') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit"
                wire:loading.attr="disabled" wire:target="submit"
                class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-primary-dark transition-all active:scale-[0.98]">
            <span wire:loading.remove wire:target="submit">{{ __('Submit for Review') }}</span>
            <span wire:loading wire:target="submit" class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ __('Submitting...') }}
            </span>
        </button>
    </form>
</div>
