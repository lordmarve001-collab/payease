<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Business Info') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Update your Ajo business profile details.') }}</p>
        </div>
        <a href="{{ route('ajo-owner.profile') }}" wire:navigate class="text-sm text-purple-600 hover:text-purple-700 font-medium flex items-center gap-1">
            <x-lucide-arrow-left class="w-4 h-4" /> {{ __('Back to Profile') }}
        </a>
    </div>

    <div class="bg-surface rounded-card shadow-elevation-1 border border-border p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Business Name') }}</label>
            <input type="text" wire:model="businessName" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="{{ __('e.g. Market Women Ajo Services') }}">
            @error('businessName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Business Description') }}</label>
            <textarea wire:model="businessDescription" rows="3" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="{{ __('Describe your Ajo business...') }}"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Business Address') }}</label>
            <input type="text" wire:model="businessAddress" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="{{ __('e.g. 123 Main Street') }}">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('LGA') }}</label>
                <input type="text" wire:model="lga" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="{{ __('e.g. Ikeja') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('State') }}</label>
                <input type="text" wire:model="state" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="{{ __('e.g. Lagos') }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Planned Groups') }}</label>
                <input type="number" wire:model="plannedGroups" min="1" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="{{ __('e.g. 5') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Previous Experience') }}</label>
                <div class="flex p-1 bg-gray-100 dark:bg-gray-800 rounded-lg h-[50px]">
                    <button wire:click="$set('hasExperience', true)" class="flex-1 text-sm font-medium rounded-md transition-all {{ $hasExperience ? 'bg-surface shadow-sm text-purple-600' : 'text-text-secondary' }}">{{ __('Yes') }}</button>
                    <button wire:click="$set('hasExperience', false)" class="flex-1 text-sm font-medium rounded-md transition-all {{ !$hasExperience ? 'bg-surface shadow-sm text-purple-600' : 'text-text-secondary' }}">{{ __('No') }}</button>
                </div>
            </div>
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
