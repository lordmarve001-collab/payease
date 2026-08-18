<div class="min-h-screen flex items-center justify-center bg-background p-4">
    <div class="w-full max-w-md">
        <div class="flex items-center gap-3 mb-8">
            @if($siteSettings->logo_path)
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name ?? 'PayEase' }}" class="h-10 object-contain">
            @else
                <div class="w-10 h-10 bg-primary rounded-2xl flex items-center justify-center shadow-elevation-1 shrink-0">
                    <span class="text-white text-lg font-bold">&#8358;</span>
                </div>
            @endif
            <div>
                <h1 class="text-xl font-bold text-text-primary">{{ __('Change Password') }}</h1>
                <p class="text-sm text-text-secondary">{{ __('Set a new login password') }}</p>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-sm text-amber-800">
            {{ __('You are required to change your default password before continuing.') }}
        </div>

        <form wire:submit="changePassword" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Current Password') }}</label>
                <input type="password" wire:model="currentPassword"
                       class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                       placeholder="{{ __('Default 6-digit password') }}" maxlength="6" inputmode="numeric" pattern="[0-9]*">
                @error('currentPassword') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('New Password') }}</label>
                <input type="password" wire:model="newPassword"
                       class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                       placeholder="{{ __('6-digit password') }}" maxlength="6" inputmode="numeric" pattern="[0-9]*">
                @error('newPassword') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Confirm New Password') }}</label>
                <input type="password" wire:model="newPasswordConfirmation"
                       class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                       placeholder="{{ __('Repeat 6-digit password') }}" maxlength="6" inputmode="numeric" pattern="[0-9]*">
                @error('newPasswordConfirmation') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full bg-secondary hover:bg-secondary/90 text-white py-3 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1">
                {{ __('Change Password') }}
            </button>
        </form>
    </div>
</div>
