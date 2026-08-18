<div class="px-4 py-6 md:p-8 max-w-lg mx-auto space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Create Customer') }}</h1>
            <p class="text-sm text-text-secondary">{{ __('Register a new customer') }}</p>
        </div>
        <button wire:click="resetAndStart" class="text-sm text-secondary hover:text-secondary/80 transition-colors">{{ __('Start Over') }}</button>
    </div>

    <!-- Step Indicator -->
    <div class="flex items-center gap-2">
        @foreach (['Phone', 'Name', 'OTP', 'PIN', __('Done')] as $i => $label)
            <div class="flex items-center gap-2">
                @if($i > 0)<div class="w-6 h-px bg-border {{ $step > $i ? 'bg-secondary' : '' }}"></div>@endif
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $step > $i + 1 ? 'bg-secondary text-white' : ($step === $i + 1 ? 'bg-secondary text-white ring-2 ring-secondary/30' : 'bg-surface text-text-secondary border border-border') }}">
                    {{ $i + 1 }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- Step 1: Phone -->
    @if($step === 1)
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 space-y-4">
            <h2 class="text-lg font-semibold text-text-primary">{{ __('Enter Customer Phone Number') }}</h2>
            <p class="text-sm text-text-secondary">{{ __('An OTP will be sent to this number via SMS.') }}</p>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Phone Number') }}</label>
                <input type="tel" wire:model.live="phone" placeholder="08012345678" maxlength="14"
                    class="w-full px-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <button wire:click="submitPhone" class="w-full py-2.5 bg-secondary hover:bg-secondary/90 text-white rounded-btn font-medium transition-all active:scale-[0.98]">
                {{ __('Send OTP via SMS') }}
            </button>
        </div>
    @endif

    <!-- Step 2: Full Name + Email -->
    @if($step === 2)
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 space-y-4">
            <h2 class="text-lg font-semibold text-text-primary">{{ __('Enter Customer Details') }}</h2>
            <p class="text-sm text-text-secondary">{{ __('OTP has been sent. Now enter the customer\'s details.') }}</p>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Full Name') }}</label>
                <input type="text" wire:model="fullName" placeholder="John Doe"
                    class="w-full px-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                @error('fullName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">
                    {{ __('Email Address') }} <span class="text-text-secondary font-normal">({{ __('optional') }})</span>
                </label>
                <input type="email" wire:model="email" placeholder="customer@example.com"
                    class="w-full px-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-text-secondary mt-1">{{ __('If provided, a welcome email with login details will be sent.') }}</p>
            </div>
            <button wire:click="submitName" class="w-full py-2.5 bg-secondary hover:bg-secondary/90 text-white rounded-btn font-medium transition-all active:scale-[0.98]">
                {{ __('Continue') }}
            </button>
        </div>
    @endif

    <!-- Step 3: OTP (Hand-off) -->
    @if($step === 3)
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 space-y-4 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-secondary/10 flex items-center justify-center mb-2">
                <x-lucide-smartphone class="w-8 h-8 text-secondary" />
            </div>
            <h2 class="text-lg font-semibold text-text-primary">{{ __('Customer OTP Entry') }}</h2>
            <p class="text-sm text-text-secondary">{{ __('Please hand the device to the customer to enter the OTP sent to their phone.') }}</p>
            <div>
                <input type="text" wire:model="otp" placeholder="000000" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                    class="w-full text-center text-2xl tracking-[0.5em] px-4 py-3 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                @error('otp') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
            </div>
            <button wire:click="verifyOtp" class="w-full py-2.5 bg-secondary hover:bg-secondary/90 text-white rounded-btn font-medium transition-all active:scale-[0.98]">
                {{ __('Verify OTP') }}
            </button>
            <div class="pt-2">
                @if($resendAvailableIn > 0)
                    <p class="text-xs text-text-secondary">{{ __('Resend OTP available in') }} {{ $resendAvailableIn }}s</p>
                @else
                    <button wire:click="resendOtp" class="text-sm text-secondary hover:text-secondary/80 underline transition-colors">
                        {{ __('Resend OTP') }}
                    </button>
                @endif
            </div>
        </div>
    @endif

    <!-- Step 4: PIN (Hand-off) -->
    @if($step === 4)
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 space-y-4 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-secondary/10 flex items-center justify-center mb-2">
                <x-lucide-lock class="w-8 h-8 text-secondary" />
            </div>
            <h2 class="text-lg font-semibold text-text-primary">{{ __('Customer PIN Setup') }}</h2>
            <p class="text-sm text-text-secondary">{{ __('Please hand the device to the customer to set their 6-digit transaction PIN.') }}</p>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1 text-left">{{ __('Enter PIN') }}</label>
                <input type="password" wire:model="pin" placeholder="••••••" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                    class="w-full text-center text-2xl tracking-[0.5em] px-4 py-3 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                @error('pin') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1 text-left">{{ __('Confirm PIN') }}</label>
                <input type="password" wire:model="pinConfirmation" placeholder="••••••" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                    class="w-full text-center text-2xl tracking-[0.5em] px-4 py-3 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
                @error('pinConfirmation') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <button wire:click="submitPin" class="w-full py-2.5 bg-secondary hover:bg-secondary/90 text-white rounded-btn font-medium transition-all active:scale-[0.98]">
                {{ __('Register Customer') }}
            </button>
        </div>
    @endif

    <!-- Step 5: Done -->
    @if($step === 5)
        <div class="bg-surface p-6 rounded-card shadow-elevation-1 space-y-4 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-2">
                <x-lucide-check-circle class="w-8 h-8 text-green-600" />
            </div>
            <h2 class="text-lg font-semibold text-text-primary">{{ __('Customer Registered') }}</h2>
            <p class="text-sm text-text-secondary">{{ $fullName }} {{ __('has been registered successfully.') }}
                @if($email) {{ __('A welcome email has been sent.') }} @endif
            </p>
            <button wire:click="resetAndStart" class="w-full py-2.5 bg-secondary hover:bg-secondary/90 text-white rounded-btn font-medium transition-all active:scale-[0.98]">
                {{ __('Register Another Customer') }}
            </button>
        </div>
    @endif
</div>
