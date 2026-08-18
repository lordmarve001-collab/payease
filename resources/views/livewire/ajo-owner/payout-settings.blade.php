<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Payout Settings') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Manage your bank account details for receiving payouts.') }}</p>
        </div>
        <a href="{{ route('ajo-owner.profile') }}" wire:navigate class="text-sm text-purple-600 hover:text-purple-700 font-medium flex items-center gap-1">
            <x-lucide-arrow-left class="w-4 h-4" /> {{ __('Back to Profile') }}
        </a>
    </div>

    <div class="bg-surface rounded-card shadow-elevation-1 border border-border p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Bank Name') }}</label>
            <input type="text" wire:model="bankName" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="{{ __('e.g. GTBank') }}">
            @error('bankName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Account Name') }}</label>
            <input type="text" wire:model="accountName" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="{{ __('e.g. John Doe') }}">
            @error('accountName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Account Number') }}</label>
            <input type="text" wire:model="accountNumber" maxlength="10" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="{{ __('e.g. 0123456789') }}">
            @error('accountNumber') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="pt-4 flex gap-3">
            <a href="{{ route('ajo-owner.profile') }}" wire:navigate class="flex-1 inline-flex items-center justify-center px-4 py-3 rounded-btn border border-border bg-surface text-text-primary font-medium hover:bg-gray-50 transition-colors text-sm">{{ __('Cancel') }}</a>
            <button wire:click="save" wire:loading.attr="disabled" class="flex-1 inline-flex items-center justify-center px-4 py-3 rounded-btn bg-purple-600 text-white font-medium hover:bg-purple-700 transition-colors text-sm">
                <span wire:loading.remove>{{ __('Save Changes') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </button>
        </div>
    </div>
</div>
