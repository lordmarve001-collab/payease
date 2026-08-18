<div class="px-4 py-6 md:p-8 max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Verify Your Identity') }}</h1>
        <p class="text-text-secondary text-sm mt-1">{{ $upgradeMessage }}</p>
    </div>

    @if($blockedReason)
    <div class="bg-amber-50 border border-amber-200 rounded-card p-6 text-center space-y-3">
        <div class="mx-auto w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center">
            <x-lucide-alert-triangle class="w-7 h-7 text-amber-600" />
        </div>
        <h3 class="font-semibold text-amber-800">{{ __('KYC Upgrade Not Available') }}</h3>
        <p class="text-sm text-amber-700">{{ $blockedReason }}</p>
        <a href="{{ url('/ajo-agent/profile') }}" wire:navigate class="inline-block mt-2 text-sm font-semibold text-amber-700 hover:text-amber-800 underline">{{ __('Go to Profile') }}</a>
    </div>
    @elseif($flowStep === 'form')
    <form wire:submit="submit" class="space-y-6">
        <div class="bg-surface rounded-card border border-border p-6 space-y-4">
            <h3 class="font-semibold text-text-primary">{{ __('Government Identifiers') }}</h3>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('NIN (National Identification Number)') }}</label>
                <input type="text" wire:model="nin" maxlength="11" inputmode="numeric"
                       class="w-full rounded-card border border-border px-4 py-3 outline-none focus:border-primary focus:ring-primary"
                       placeholder="11-digit NIN">
                @error('nin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('BVN (Bank Verification Number)') }}</label>
                <input type="text" wire:model="bvn" maxlength="11" inputmode="numeric"
                       class="w-full rounded-card border border-border px-4 py-3 outline-none focus:border-primary focus:ring-primary"
                       placeholder="11-digit BVN">
                @error('bvn') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-surface rounded-card border border-border p-6 space-y-4">
            <h3 class="font-semibold text-text-primary">{{ __('Next of Kin') }}</h3>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Full Name') }}</label>
                <input type="text" wire:model="nextOfKinName"
                       class="w-full rounded-card border border-border px-4 py-3 outline-none focus:border-primary focus:ring-primary"
                       placeholder="Next of kin full name">
                @error('nextOfKinName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Relationship') }}</label>
                <select wire:model="nextOfKinRelationship"
                        class="w-full rounded-card border border-border px-4 py-3 outline-none focus:border-primary focus:ring-primary">
                    <option value="">{{ __('Select relationship') }}</option>
                    <option value="spouse">{{ __('Spouse') }}</option>
                    <option value="parent">{{ __('Parent') }}</option>
                    <option value="sibling">{{ __('Sibling') }}</option>
                    <option value="child">{{ __('Child') }}</option>
                    <option value="friend">{{ __('Friend') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </select>
                @error('nextOfKinRelationship') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Phone Number') }}</label>
                <input type="tel" wire:model="nextOfKinPhone"
                       class="w-full rounded-card border border-border px-4 py-3 outline-none focus:border-primary focus:ring-primary"
                       placeholder="08012345678">
                @error('nextOfKinPhone') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-surface rounded-card border border-border p-6 space-y-4">
            <h3 class="font-semibold text-text-primary">{{ __('Document Uploads') }}</h3>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('NIN Slip (photo/scan)') }}</label>
                <input type="file" wire:model="ninDocument" accept="image/*"
                       class="w-full">
                @error('ninDocument') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('BVN Slip (photo/scan)') }}</label>
                <input type="file" wire:model="bvnDocument" accept="image/*"
                       class="w-full">
                @error('bvnDocument') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" wire:model="useLiveness" id="useLiveness"
                       class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                <label for="useLiveness" class="text-sm text-text-primary">
                    Include liveness capture (optional — speeds up verification with face match)
                </label>
            </div>

            @if($useLiveness)
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Liveness Selfie') }}</label>
                <input type="file" wire:model="livenessCapture" accept="image/*"
                       class="w-full">
                @error('livenessCapture') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            @endif
        </div>

        <div class="bg-surface rounded-card border border-border p-6">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" wire:model="consent"
                       class="w-5 h-5 mt-0.5 text-primary rounded border-gray-300 focus:ring-primary">
                <span class="text-sm text-text-primary leading-relaxed">
                    I consent to {{ $siteSettings->site_name ?? 'PayEase' }} verifying my NIN and BVN details with licensed identity verification providers.
                </span>
            </label>
            @error('consent') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                wire:loading.attr="disabled" wire:target="submit"
                class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-primary-dark transition-all active:scale-[0.98]">
            <span wire:loading.remove wire:target="submit">{{ __('Submit for Verification') }}</span>
            <span wire:loading wire:target="submit" class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ __('Submitting...') }}
            </span>
        </button>
    </form>
    @endif

    @if($flowStep === 'submitting')
    <div class="bg-surface rounded-card border border-border p-8 text-center space-y-4">
        <div class="mx-auto w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
            <svg class="w-8 h-8 text-primary animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
        <h2 class="text-lg font-semibold text-text-primary">{{ __('Submitting your details...') }}</h2>
        <p class="text-sm text-text-secondary">{{ __('Please wait while we process your information.') }}</p>
    </div>
    @endif

    @if($flowStep === 'verifying')
    <div class="bg-surface rounded-card border border-border p-8 text-center space-y-4">
        <div class="mx-auto w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
            <span class="relative flex h-8 w-8">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-40"></span>
                <span class="relative inline-flex rounded-full h-8 w-8 bg-primary/20"></span>
            </span>
        </div>
        <h2 class="text-lg font-semibold text-text-primary">{{ __('Verifying your details...') }}</h2>
        <p class="text-sm text-text-secondary">{{ __('Checking your identity with verification providers. This takes just a moment.') }}</p>
    </div>
    @endif

    @if($flowStep === 'verified')
    <div class="bg-surface rounded-card border border-border p-8 text-center space-y-4">
        <div class="mx-auto w-20 h-20 rounded-full bg-success/10 flex items-center justify-center">
            <svg class="w-10 h-10 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-text-primary">{{ __('Verified!') }}</h2>
        <p class="text-sm text-text-secondary">{{ $statusMessage }}</p>
        <div class="bg-success/5 border border-success/20 rounded-card p-4 text-sm text-text-primary">
            <p>{{ __('Your account is now Tier 2. You can send up to ₦50,000 daily and a Monnify reserved account is being set up for deposits.') }}</p>
        </div>
        <a href="{{ url('/ajo-agent/dashboard') }}" wire:navigate
           class="inline-block w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-primary-dark transition-colors text-center">
            {{ __('Go to Dashboard') }}
        </a>
    </div>
    @endif

    @if($flowStep === 'under_review')
    <div class="bg-surface rounded-card border border-border p-8 text-center space-y-4">
        <div class="mx-auto w-16 h-16 rounded-full bg-secondary/10 flex items-center justify-center">
            <x-lucide-clock class="w-8 h-8 text-secondary" />
        </div>
        <h2 class="text-lg font-semibold text-text-primary">{{ __('Under Review') }}</h2>
        <div class="bg-secondary/5 border border-secondary/20 rounded-card p-4 text-sm text-text-primary">
            <p>{{ __("We're taking a closer look at your details — this usually takes less than 24 hours. We'll notify you.") }}</p>
        </div>
        <p class="text-sm text-text-secondary">{{ $statusMessage }}</p>
        <a href="{{ url('/ajo-agent/dashboard') }}" wire:navigate
           class="inline-block w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-primary-dark transition-colors text-center">
            {{ __('Go to Dashboard') }}
        </a>
    </div>
    @endif

    @if($flowStep === 'provider_error')
    <div class="bg-surface rounded-card border border-border p-8 text-center space-y-4">
        <div class="mx-auto w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
            <x-lucide-alert-triangle class="w-8 h-8 text-amber-600" />
        </div>
        <h2 class="text-lg font-semibold text-text-primary">{{ __('Verification Paused') }}</h2>
        <div class="bg-amber-50 border border-amber-200 rounded-card p-4 text-sm text-amber-800">
            <p>{{ __("We couldn't complete the automated verification right now. Your submission has been saved and will be reviewed manually.") }}</p>
        </div>
        <p class="text-sm text-text-secondary">{{ $statusMessage }}</p>
        <a href="{{ url('/ajo-agent/dashboard') }}" wire:navigate
           class="inline-block w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-primary-dark transition-colors text-center">
            {{ __('Go to Dashboard') }}
        </a>
    </div>
    @endif
</div>
