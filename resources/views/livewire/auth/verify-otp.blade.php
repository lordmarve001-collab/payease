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
                <h1 class="text-xl font-bold text-text-primary">{{ __('Verify OTP') }}</h1>
                <p class="text-sm text-text-secondary">{{ __('Enter the 6-digit code') }}</p>
            </div>
        </div>

        @if($showOtp && $storedOtp)
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                <p class="text-sm text-amber-800">
                    <strong>{{ __('Development Mode:') }}</strong> {{ __('Your OTP is:') }} <span class="font-mono font-bold">{{ $storedOtp }}</span>
                </p>
            </div>
        @endif

        <form wire:submit="verify" class="space-y-6">
            <div class="flex gap-3 justify-center" x-data>
                @for ($i = 1; $i <= 6; $i++)
                    <input type="text"
                           wire:model="otp{{ $i }}"
                           x-ref="otp{{ $i }}"
                           @focus="$event.target.select()"
                           @input="
                               if ($event.target.value.length > 1) $event.target.value = $event.target.value.slice(-1);
                               if ($event.target.value && {{ $i }} < 6) $refs.otp{{ $i + 1 }}.focus();
                           "
                           @keyup.backspace="if (!$event.target.value && {{ $i }} > 1) $refs.otp{{ $i - 1 }}.focus()"
                           maxlength="1"
                           class="w-12 h-14 text-center text-2xl font-bold rounded-xl border border-border bg-background text-text-primary focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all">
                @endfor
            </div>
            @error('otp') <p class="text-center text-danger text-sm">{{ $message }}</p> @enderror

            <button type="submit"
                    class="w-full bg-secondary hover:bg-secondary/90 text-white py-3 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1"
                    wire:loading.attr="disabled" wire:target="verify">
                <span wire:loading.remove wire:target="verify">{{ __('Verify') }}</span>
                <span wire:loading wire:target="verify" class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    {{ __('Verifying...') }}
                </span>
            </button>
        </form>

        <div class="mt-5 text-center" wire:poll.1s="tick">
            @if($resendAvailableIn > 0)
                <p class="text-sm text-text-secondary">{{ __('Resend OTP in') }} {{ $resendAvailableIn }}s</p>
            @else
                <button type="button"
                        wire:click="resendOtp"
                        class="text-sm font-semibold text-secondary hover:underline">
                    {{ __('Resend OTP') }}
                </button>
            @endif
        </div>
    </div>
</div>
