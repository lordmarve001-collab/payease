<div class="min-h-screen flex flex-col lg:flex-row">

    {{-- LEFT PANEL — Market Woman Illustration --}}
    <div class="lg:w-1/2 min-h-[260px] lg:min-h-screen relative overflow-hidden bg-gradient-to-br from-amber-700 via-orange-600 to-yellow-500 flex items-center justify-center p-8 lg:p-16">
        
        <div class="absolute inset-0 opacity-20">
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-yellow-300 blur-3xl"></div>
            <div class="absolute -bottom-32 -right-32 w-[500px] h-[500px] rounded-full bg-amber-300 blur-3xl"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 rounded-full bg-orange-200 blur-2xl"></div>
        </div>

        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 24px 24px;"></div>

        <div class="relative z-10 text-center lg:text-left max-w-md">
            {{-- Logo --}}
            <div class="flex items-center gap-3 mb-8 justify-center lg:justify-start">
                @if($siteSettings->logo_path)
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name ?? 'PayEase' }}" class="h-12 object-contain">
                @else
                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">₦</span>
                    </div>
                @endif
                <h1 class="text-3xl font-bold text-white">{{ $siteSettings->site_name ?? 'PayEase' }}</h1>
            </div>

            {{-- Headline --}}
            <h2 class="text-3xl lg:text-4xl font-bold text-white leading-tight mb-4">
                {{ __('Start your journey') }}<br>{{ __('today.') }}
            </h2>
            <p class="text-white/80 text-lg leading-relaxed">
                {{ __('Fast, secure payments built for Nigerian communities.') }}
            </p>

            {{-- Steps overview --}}
            <div class="mt-10 space-y-3 hidden lg:block">
                <div class="flex items-center gap-3 text-white/70">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold shrink-0">1</div>
                    <span>{{ __('Enter your phone number') }}</span>
                </div>
                <div class="flex items-center gap-3 text-white/70">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold shrink-0">2</div>
                    <span>{{ __('Verify with OTP') }}</span>
                </div>
                <div class="flex items-center gap-3 text-white/70">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold shrink-0">3</div>
                    <span>{{ __('Set your transaction PIN') }}</span>
                </div>
                <div class="flex items-center gap-3 text-white/70">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold shrink-0">4</div>
                    <span>{{ __('Complete your profile') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT PANEL — Registration Wizard --}}
    <div class="lg:w-1/2 min-h-screen bg-white flex items-center justify-center p-6 lg:p-16">
        <div class="w-full max-w-md">

            {{-- Progress Bar --}}
            <div class="flex items-center gap-2 mb-10">
                @for($i = 1; $i <= 4; $i++)
                    <div class="h-1.5 flex-1 rounded-full transition-all duration-300 {{ $step >= $i ? 'bg-primary' : 'bg-gray-200' }}"></div>
                @endfor
            </div>

            {{-- Step Labels --}}
            <div class="flex justify-between mb-8">
                @foreach (['Phone', 'Verify', 'PIN', 'Profile'] as $i => $label)
                    <span class="text-xs font-semibold transition-colors {{ $step > $i + 1 ? 'text-primary' : ($step === $i + 1 ? 'text-primary' : 'text-gray-300') }}">
                        {{ __($label) }}
                    </span>
                @endforeach
            </div>

            @if(session()->has('otp_user_id'))
                <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <p class="text-sm text-amber-800">
                        {{ __('You already have an OTP pending.') }}
                        <a href="{{ route('verify-otp') }}" class="font-semibold underline hover:no-underline">{{ __('Continue verification') }}</a>
                    </p>
                </div>
            @endif

            {{-- Step 1: Phone --}}
            @if($step === 1)
                <form wire:submit="sendOtp" class="space-y-6">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-text-primary mb-2">{{ __("What's your phone number?") }}</h2>
                        <p class="text-text-secondary text-sm">{{ __("We'll send a verification code via SMS.") }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Phone Number') }}</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-text-secondary font-medium text-sm pointer-events-none">+234</span>
                            <input type="tel" wire:model="phoneInput"
                                   class="w-full pl-16 pr-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none text-lg font-medium transition-all"
                                   placeholder="801 234 5678"
                                   maxlength="11"
                                   autocomplete="tel">
                        </div>
                        @error('phoneInput') <p class="text-sm text-danger mt-2 flex items-center gap-1"><svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="sendOtp"
                            class="w-full bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 min-h-[48px] flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="sendOtp">{{ __('Send OTP via SMS') }}</span>
                        <span wire:loading wire:target="sendOtp" class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('Sending...') }}
                        </span>
                    </button>

                    <p class="text-center text-sm text-text-secondary">
                        {{ __('Already have an account?') }}
                        <a href="{{ route('login') }}" class="font-semibold text-secondary hover:underline">{{ __('Log In') }}</a>
                    </p>
                </form>
            @endif

            {{-- Step 2: OTP --}}
            @if($step === 2)
                <form wire:submit="verifyOtp" class="space-y-6">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-text-primary mb-2">{{ __('Enter verification code') }}</h2>
                        <p class="text-text-secondary text-sm">
                            {{ __('We sent a 6-digit code to') }}<br>
                            <span class="font-semibold text-text-primary">+234 {{ $phoneInput }}</span>
                        </p>
                    </div>

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
                                   class="w-12 h-14 text-center text-2xl font-bold rounded-xl border-2 border-border bg-background text-text-primary focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all">
                        @endfor
                    </div>
                    @error('otp1') <p class="text-center text-danger text-sm mt-2">{{ $message }}</p> @enderror

                    <button type="submit" wire:loading.attr="disabled" wire:target="verifyOtp"
                            class="w-full bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 min-h-[48px] flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="verifyOtp">{{ __('Verify OTP') }}</span>
                        <span wire:loading wire:target="verifyOtp" class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('Verifying...') }}
                        </span>
                    </button>

                    <div class="text-center" wire:poll.1s="tick">
                        @if($resendAvailableIn > 0)
                            <p class="text-sm text-text-secondary">{{ __('Resend OTP in') }} {{ $resendAvailableIn }}s</p>
                        @else
                            <button type="button" wire:click="resendOtp"
                                    class="text-sm font-semibold text-secondary hover:text-secondary/80 transition-colors">
                                {{ __('Resend OTP') }}
                            </button>
                        @endif
                    </div>
                </form>
            @endif

            {{-- Step 3: PIN --}}
            @if($step === 3)
                <form wire:submit="setPin" x-data="{ showPin: false, showConfirm: false }" class="space-y-6">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-text-primary mb-2">{{ __('Set your transaction PIN') }}</h2>
                        <p class="text-text-secondary text-sm">{{ __('You will use this PIN to authorize transactions.') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Transaction PIN') }}</label>
                        <div class="relative">
                            <input :type="showPin ? 'text' : 'password'" wire:model="pin"
                                   class="w-full px-4 py-3.5 rounded-xl border-2 border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none tracking-[0.3em] text-center text-lg font-medium transition-all"
                                   placeholder="••••••"
                                   maxlength="6" inputmode="numeric" autocomplete="new-password">
                            <button type="button" @click="showPin = !showPin" class="absolute inset-y-0 right-0 pr-4 flex items-center text-text-secondary hover:text-text-primary transition-colors">
                                <svg x-show="!showPin" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="showPin" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        @error('pin') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Confirm PIN') }}</label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" wire:model="pinConfirmation"
                                   class="w-full px-4 py-3.5 rounded-xl border-2 border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none tracking-[0.3em] text-center text-lg font-medium transition-all"
                                   placeholder="••••••"
                                   maxlength="6" inputmode="numeric" autocomplete="new-password">
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 pr-4 flex items-center text-text-secondary hover:text-text-primary transition-colors">
                                <svg x-show="!showConfirm" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="showConfirm" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        @error('pinConfirmation') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="setPin"
                            class="w-full bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 min-h-[48px] flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="setPin">{{ __('Continue') }}</span>
                        <span wire:loading wire:target="setPin" class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('Processing...') }}
                        </span>
                    </button>
                </form>
            @endif

            {{-- Step 4: Profile --}}
            @if($step === 4)
                <form wire:submit="completeRegistration" class="space-y-5">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-text-primary mb-2">{{ __('Complete your profile') }}</h2>
                        <p class="text-text-secondary text-sm">{{ __('Just a few more details and you are set.') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('Full Name') }}</label>
                        <input type="text" wire:model="fullName"
                               class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                               placeholder="{{ __('Enter your full name') }}"
                               autocomplete="name">
                        @error('fullName') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">
                            {{ __('Email Address') }} <span class="text-text-secondary font-normal text-xs">({{ __('optional') }})</span>
                        </label>
                        <input type="email" wire:model="emailInput"
                               class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                               placeholder="you@example.com"
                               autocomplete="email">
                        @error('emailInput') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
                        <p class="text-xs text-text-secondary mt-1">{{ __('If provided, a welcome email with your login details will be sent.') }}</p>
                    </div>

                    <label class="flex items-start gap-3 p-4 rounded-xl bg-background border border-border cursor-pointer hover:border-primary/30 transition-colors">
                        <input type="checkbox" wire:model="terms" id="terms"
                               class="mt-0.5 w-4 h-4 text-secondary rounded border-border focus:ring-secondary/30">
                        <span class="text-sm text-text-secondary">
                            {{ __('I agree to the') }} <a href="#" class="text-secondary font-medium hover:underline">{{ __('Terms of Service') }}</a> {{ __('and') }} <a href="#" class="text-secondary font-medium hover:underline">{{ __('Privacy Policy') }}</a>
                        </span>
                    </label>
                    @error('terms') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror

                    <button type="submit" wire:loading.attr="disabled" wire:target="completeRegistration"
                            class="w-full bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 min-h-[48px] flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="completeRegistration">{{ __('Create Account') }}</span>
                        <span wire:loading wire:target="completeRegistration" class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('Creating account...') }}
                        </span>
                    </button>
                </form>
            @endif

            {{-- Step Dots (mobile) --}}
            <div class="mt-8 flex justify-center gap-2">
                @for($i = 1; $i <= 4; $i++)
                    <div class="w-2.5 h-2.5 rounded-full transition-all duration-300 {{ $i <= $step ? 'bg-primary scale-110' : 'bg-gray-300' }}"></div>
                @endfor
            </div>

        </div>
    </div>

</div>
