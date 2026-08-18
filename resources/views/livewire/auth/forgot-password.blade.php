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
                <h1 class="text-xl font-bold text-text-primary">{{ __('Forgot Password') }}</h1>
                <p class="text-sm text-text-secondary">{{ __('Reset your login password') }}</p>
            </div>
        </div>

        @if($step === 1)
            <form wire:submit="sendResetOtp" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Phone Number') }}</label>
                    <input type="tel" wire:model="phoneInput"
                           class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                           placeholder="08012345678">
                    @error('phoneInput') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                        class="w-full bg-secondary hover:bg-secondary/90 text-white py-3 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1">
                    {{ __('Send Reset OTP') }}
                </button>
                <p class="text-center text-sm text-text-secondary">
                    <a href="{{ route('login') }}" class="font-semibold text-secondary hover:underline">{{ __('Back to Login') }}</a>
                </p>
            </form>
        @endif

        @if($step === 2)
            <form wire:submit="verifyOtp" class="space-y-6">
                <p class="text-sm text-text-secondary text-center">{{ __('Enter the OTP sent to your phone.') }}</p>
                <div class="flex gap-2 justify-center" x-data>
                    @for ($i = 1; $i <= 6; $i++)
                        <input type="text" wire:model="otp{{ $i }}" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                               x-ref="otp{{ $i }}" x-init="$el.addEventListener('input', () => {
                                   if ($el.value.length === 1 && $refs.otp{{ $i + 1 }}) $refs.otp{{ $i + 1 }}.focus();
                               })"
                               class="w-12 h-14 text-center text-xl font-bold rounded-xl border border-border bg-background text-text-primary focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all">
                    @endfor
                </div>
                @error('otp1') <p class="text-danger text-sm text-center">{{ $message }}</p> @enderror

                <button type="submit"
                        class="w-full bg-secondary hover:bg-secondary/90 text-white py-3 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1">
                    {{ __('Verify OTP') }}
                </button>

                <div class="text-center" wire:poll.1s="tick">
                    @if($resendAvailableIn > 0)
                        <p class="text-xs text-text-secondary">{{ __('Resend OTP in') }} {{ $resendAvailableIn }}s</p>
                    @else
                        <button type="button" wire:click="resendOtp" class="text-sm font-semibold text-secondary hover:underline">
                            {{ __('Resend OTP') }}
                        </button>
                    @endif
                </div>
            </form>
        @endif

        @if($step === 3)
            <form wire:submit="resetPassword" class="space-y-6">
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
                    {{ __('Reset Password') }}
                </button>
            </form>
        @endif
    </div>
</div>
