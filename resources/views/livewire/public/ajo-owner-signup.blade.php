<div class="min-h-screen flex flex-col lg:flex-row">

    {{-- LEFT PANEL — Marketing --}}
    <div class="lg:w-[45%] min-h-[280px] lg:min-h-screen relative overflow-hidden bg-gradient-to-br from-amber-700 via-orange-600 to-yellow-500 flex items-center justify-center p-8 lg:p-16">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-yellow-300 blur-3xl"></div>
            <div class="absolute -bottom-32 -right-32 w-[500px] h-[500px] rounded-full bg-amber-300 blur-3xl"></div>
        </div>
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 24px 24px;"></div>

        <div class="relative z-10 text-center lg:text-left max-w-md">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-3 mb-8 justify-center lg:justify-start">
                <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
                    <span class="text-white text-2xl font-bold">₦</span>
                </div>
                <h1 class="text-3xl font-bold text-white">{{ $siteSettings->site_name ?? 'PayEase' }}</h1>
            </a>

            <h2 class="text-3xl lg:text-4xl font-bold text-white leading-tight mb-4">
                Build your digital<br>savings empire.
            </h2>
            <p class="text-white/80 text-lg leading-relaxed">
                The same trusted Ajo model — now digital, secure, and scalable. Create groups, recruit agents, and earn.
            </p>

            {{-- Benefits --}}
            <div class="mt-10 space-y-4 hidden lg:block">
                @foreach([
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />', 'text' => 'Create & manage multiple Ajo groups'],
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />', 'text' => 'Earn management fees from every cycle'],
                    ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />', 'text' => 'Bank-grade security & encrypted data'],
                ] as $benefit)
                    <div class="flex items-center gap-3 text-white/70">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{!! $benefit['icon'] !!}</svg>
                        </div>
                        <span class="text-sm">{{ $benefit['text'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- RIGHT PANEL — Form Wizard --}}
    <div class="lg:w-[55%] min-h-screen bg-white flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md">

            {{-- Mobile Logo --}}
            <div class="lg:hidden flex items-center gap-2.5 mb-6 justify-center">
                <div class="w-9 h-9 bg-gradient-to-br from-primary to-accent rounded-xl flex items-center justify-center">
                    <span class="text-white text-lg font-bold">₦</span>
                </div>
                <span class="text-xl font-bold font-display text-text-primary">{{ $siteSettings->site_name ?? 'PayEase' }}</span>
            </div>

            {{-- Progress --}}
            <div class="flex items-center gap-2 mb-8">
                @for($i = 1; $i <= 6; $i++)
                    <div class="h-1.5 flex-1 rounded-full transition-all duration-500 {{ $step >= $i ? 'bg-primary' : 'bg-gray-200' }}"></div>
                @endfor
            </div>

            {{-- Step Labels --}}
            <div class="flex justify-between mb-8">
                @foreach(['Phone', 'Verify', 'Profile', 'Business', 'Plan', 'Review'] as $i => $label)
                    <span class="text-[11px] font-semibold transition-colors {{ $step > $i + 1 ? 'text-primary' : ($step === $i + 1 ? 'text-primary' : 'text-gray-300') }}">
                        {{ $label }}
                    </span>
                @endforeach
            </div>

            {{-- ═══ Step 1: Phone ═══ --}}
            @if($step === 1)
                <form wire:submit="sendOtp" class="space-y-6">
                    <div class="text-center mb-6">
                        <div class="w-14 h-14 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-text-primary mb-1">What's your phone number?</h2>
                        <p class="text-text-secondary text-sm">We'll send a verification code via SMS.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">Phone Number</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-text-secondary font-medium text-sm pointer-events-none">+234</span>
                            <input type="tel" wire:model="phoneInput"
                                   class="w-full pl-16 pr-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none text-lg font-medium transition-all"
                                   placeholder="801 234 5678" maxlength="11" autocomplete="tel">
                        </div>
                        @error('phoneInput') <p class="text-sm text-danger mt-2 flex items-center gap-1"><svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="sendOtp"
                            class="w-full bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 min-h-[48px] flex items-center justify-center gap-2 cursor-pointer">
                        <span wire:loading.remove wire:target="sendOtp">Continue</span>
                        <span wire:loading wire:target="sendOtp" class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Sending...
                        </span>
                    </button>

                    <p class="text-center text-sm text-text-secondary">
                        Already have an account? <a href="{{ route('login') }}" class="font-semibold text-secondary hover:underline cursor-pointer">Log In</a>
                    </p>
                </form>
            @endif

            {{-- ═══ Step 2: OTP ═══ --}}
            @if($step === 2)
                <form wire:submit="verifyOtp" class="space-y-6">
                    <div class="text-center mb-6">
                        <div class="w-14 h-14 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-text-primary mb-1">Verify your number</h2>
                        <p class="text-text-secondary text-sm">Enter the 6-digit code sent to <span class="font-semibold text-text-primary">+234 {{ $phoneInput }}</span></p>
                    </div>

                    <div class="flex gap-3 justify-center" x-data>
                        @for ($i = 1; $i <= 6; $i++)
                            <input type="text"
                                   wire:model="otp{{ $i }}"
                                   x-ref="otp{{ $i }}"
                                   @focus="$event.target.select()"
                                   @input="if ($event.target.value.length > 1) $event.target.value = $event.target.value.slice(-1); if ($event.target.value && {{ $i }} < 6) $refs.otp{{ $i + 1 }}.focus();"
                                   @keyup.backspace="if (!$event.target.value && {{ $i }} > 1) $refs.otp{{ $i - 1 }}.focus()"
                                   maxlength="1"
                                   class="w-12 h-14 text-center text-2xl font-bold rounded-xl border-2 border-border bg-background text-text-primary focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all">
                        @endfor
                    </div>
                    @error('otp1') <p class="text-center text-danger text-sm mt-2">{{ $message }}</p> @enderror

                    <button type="submit" wire:loading.attr="disabled" wire:target="verifyOtp"
                            class="w-full bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 min-h-[48px] flex items-center justify-center gap-2 cursor-pointer">
                        <span wire:loading.remove wire:target="verifyOtp">Verify OTP</span>
                        <span wire:loading wire:target="verifyOtp" class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Verifying...
                        </span>
                    </button>

                    <div class="text-center" wire:poll.1s="tick">
                        @if($resendAvailableIn > 0)
                            <p class="text-sm text-text-secondary">Resend OTP in {{ $resendAvailableIn }}s</p>
                        @else
                            <button type="button" wire:click="resendOtp" class="text-sm font-semibold text-secondary hover:text-secondary/80 transition-colors cursor-pointer">Resend OTP</button>
                        @endif
                    </div>
                </form>
            @endif

            {{-- ═══ Step 3: PIN + Name ═══ --}}
            @if($step === 3)
                <form wire:submit="nextStep" class="space-y-5">
                    <div class="text-center mb-6">
                        <div class="w-14 h-14 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-text-primary mb-1">Set your PIN & name</h2>
                        <p class="text-text-secondary text-sm">Your PIN secures transactions. Your name appears on your profile.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">Full Name</label>
                        <input type="text" wire:model="fullName"
                               class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                               placeholder="e.g. Adaeze Okonkwo" autocomplete="name">
                        @error('fullName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">Transaction PIN</label>
                        <div class="relative" x-data="{ showPin: false }">
                            <input :type="showPin ? 'text' : 'password'" wire:model="pin"
                                   class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none tracking-[0.3em] text-center text-lg font-medium transition-all"
                                   placeholder="••••••" maxlength="6" inputmode="numeric" autocomplete="new-password">
                            <button type="button" @click="showPin = !showPin" class="absolute inset-y-0 right-0 pr-4 flex items-center text-text-secondary hover:text-text-primary transition-colors cursor-pointer">
                                <svg x-show="!showPin" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="showPin" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        @error('pin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">Confirm PIN</label>
                        <div class="relative" x-data="{ showConfirm: false }">
                            <input :type="showConfirm ? 'text' : 'password'" wire:model="pinConfirmation"
                                   class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none tracking-[0.3em] text-center text-lg font-medium transition-all"
                                   placeholder="••••••" maxlength="6" inputmode="numeric" autocomplete="new-password">
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 pr-4 flex items-center text-text-secondary hover:text-text-primary transition-colors cursor-pointer">
                                <svg x-show="!showConfirm" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="showConfirm" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        @error('pinConfirmation') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit"
                            class="w-full bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 min-h-[48px] flex items-center justify-center gap-2 cursor-pointer">
                        Continue
                    </button>
                </form>
            @endif

            {{-- ═══ Step 4: Business Details ═══ --}}
            @if($step === 4)
                <form wire:submit="nextStep" class="space-y-5">
                    <div class="text-center mb-6">
                        <div class="w-14 h-14 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-text-primary mb-1">Your business details</h2>
                        <p class="text-text-secondary text-sm">Tell us about your Ajo operation.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">Business / Operation Name</label>
                        <input type="text" wire:model="businessName" maxlength="255"
                               class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                               placeholder="e.g. Lagos Market Ajo">
                        @error('businessName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">Business Description</label>
                        <textarea wire:model="businessDescription" rows="3" maxlength="2000"
                                  class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                                  placeholder="What community or market do you plan to serve?"></textarea>
                        @error('businessDescription') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">Business Address</label>
                        <input type="text" wire:model="businessAddress" maxlength="500"
                               class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                               placeholder="Business/operation address">
                        @error('businessAddress') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-text-primary mb-2">LGA</label>
                            <input type="text" wire:model="lga" maxlength="100"
                                   class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                                   placeholder="Local Govt. Area">
                            @error('lga') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-text-primary mb-2">State</label>
                            <input type="text" wire:model="state" maxlength="50"
                                   class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                                   placeholder="State">
                            @error('state') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="previousStep"
                                class="px-6 py-3 rounded-xl border-2 border-primary text-primary font-semibold hover:bg-primary-light transition-all active:scale-[0.98] min-h-[48px] cursor-pointer">
                            Back
                        </button>
                        <button type="submit"
                                class="flex-1 bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 min-h-[48px] flex items-center justify-center gap-2 cursor-pointer">
                            Continue
                        </button>
                    </div>
                </form>
            @endif

            {{-- ═══ Step 5: Plan & Preferences ═══ --}}
            @if($step === 5)
                <form wire:submit="nextStep" class="space-y-5">
                    <div class="text-center mb-6">
                        <div class="w-14 h-14 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-text-primary mb-1">Your plan</h2>
                        <p class="text-text-secondary text-sm">How do you plan to run your Ajo groups?</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-3">Do you currently run informal Ajo/Esusu groups?</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2.5 cursor-pointer px-4 py-3 rounded-xl border-2 border-border hover:border-primary/30 transition-colors {{ $hasExperience ? 'border-primary bg-primary-light/30' : '' }}">
                                <input type="radio" wire:model="hasExperience" :value="true" class="w-5 h-5 text-secondary border-border focus:ring-secondary">
                                <span class="text-sm font-medium text-text-primary">Yes</span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer px-4 py-3 rounded-xl border-2 border-border hover:border-primary/30 transition-colors {{ !$hasExperience && $hasExperience !== null ? 'border-primary bg-primary-light/30' : '' }}">
                                <input type="radio" wire:model="hasExperience" :value="false" class="w-5 h-5 text-secondary border-border focus:ring-secondary">
                                <span class="text-sm font-medium text-text-primary">No</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">How many groups do you plan to start with?</label>
                        <input type="number" wire:model="plannedGroups" min="1" max="100" inputmode="numeric"
                               class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                               placeholder="1">
                        @error('plannedGroups') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">Approximate members per group</label>
                        <input type="number" wire:model="membersPerGroup" min="1" max="10000" inputmode="numeric"
                               class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all"
                               placeholder="e.g. 10">
                        @error('membersPerGroup') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-text-primary mb-2">Agent Preference</label>
                        <select wire:model="agentAssignmentPreference"
                                class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all">
                            <option value="">Select an option</option>
                            <option value="have_agents">I already have agents in mind</option>
                                <option value="needs_help">I would like {{ $siteSettings->site_name ?? 'PayEase' }} to help assign agents</option>
                            <option value="not_sure">Not sure yet</option>
                        </select>
                        @error('agentAssignmentPreference') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="previousStep"
                                class="px-6 py-3 rounded-xl border-2 border-primary text-primary font-semibold hover:bg-primary-light transition-all active:scale-[0.98] min-h-[48px] cursor-pointer">
                            Back
                        </button>
                        <button type="submit"
                                class="flex-1 bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 min-h-[48px] flex items-center justify-center gap-2 cursor-pointer">
                            Continue
                        </button>
                    </div>
                </form>
            @endif

            {{-- ═══ Step 6: Review & Submit ═══ --}}
            @if($step === 6)
                <form wire:submit="submit" class="space-y-5">
                    <div class="text-center mb-6">
                        <div class="w-14 h-14 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-text-primary mb-1">Review & submit</h2>
                        <p class="text-text-secondary text-sm">Double-check everything before submitting.</p>
                    </div>

                    @error('agreeTerms') <p class="text-sm text-danger bg-red-50 border border-red-200 rounded-xl p-3">{{ $message }}</p> @enderror

                    <div class="bg-background rounded-card border border-border p-5 space-y-3">
                        @foreach([
                            ['label' => 'Name', 'value' => $fullName],
                            ['label' => 'Phone', 'value' => '+234 ' . $phoneInput],
                            ['label' => 'Business', 'value' => $businessName],
                            ['label' => 'Description', 'value' => \Illuminate\Support\Str::limit($businessDescription, 60)],
                            ['label' => 'Location', 'value' => $lga . ', ' . $state],
                            ['label' => 'Groups', 'value' => $plannedGroups . ' planned, ~' . $membersPerGroup . ' members each'],
                        ] as $item)
                            <div class="flex justify-between py-2 border-b border-border last:border-0">
                                <span class="text-text-secondary text-sm">{{ $item['label'] }}</span>
                                <span class="text-text-primary text-sm font-medium text-right max-w-[60%]">{{ $item['value'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <label class="flex items-start gap-3 cursor-pointer p-4 rounded-xl bg-background border border-border hover:border-primary/30 transition-colors">
                        <input type="checkbox" wire:model="agreeTerms" class="w-5 h-5 mt-0.5 text-secondary rounded border-border focus:ring-secondary">
                        <span class="text-sm text-text-secondary leading-relaxed">
                            I agree to the <a href="#" class="text-secondary font-medium hover:underline">Ajo Owner Terms</a> and confirm all information is accurate.
                        </span>
                    </label>

                    <div class="bg-primary-light/50 border border-primary/20 rounded-xl p-4 text-sm">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-primary mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span class="text-text-secondary">Tier 2 identity verification (NIN + BVN) will be required before your application can be approved. You can complete this from your dashboard after signing up.</span>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="previousStep"
                                class="px-6 py-3 rounded-xl border-2 border-primary text-primary font-semibold hover:bg-primary-light transition-all active:scale-[0.98] min-h-[48px] cursor-pointer">
                            Back
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                                class="flex-1 bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 min-h-[48px] flex items-center justify-center gap-2 cursor-pointer">
                            <span wire:loading.remove wire:target="submit">Submit Application</span>
                            <span wire:loading wire:target="submit" class="flex items-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Submitting...
                            </span>
                        </button>
                    </div>
                </form>
            @endif

            {{-- ═══ Step 7: Success ═══ --}}
            @if($step === 7)
                <div class="text-center space-y-6">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-10 h-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-text-primary mb-2">Application Submitted!</h2>
                        <p class="text-text-secondary leading-relaxed">Your {{ $siteSettings->site_name ?? 'PayEase' }} account has been created and your Ajo Owner application is now under review. We'll send you an SMS once a decision is made — typically within 24 hours.</p>
                    </div>
                    <div class="space-y-3">
                        <a href="{{ route('customer.dashboard') }}"
                           class="block w-full bg-secondary hover:bg-secondary/90 text-white py-3.5 rounded-xl font-semibold text-center transition-all active:scale-[0.98] shadow-elevation-1 cursor-pointer">
                            Go to Dashboard
                        </a>
                        <a href="{{ route('home') }}"
                           class="block w-full border-2 border-primary text-primary py-3.5 rounded-xl font-semibold text-center hover:bg-primary-light transition-all active:scale-[0.98] cursor-pointer">
                            Back to Home
                        </a>
                    </div>
                </div>
            @endif

            {{-- Step Dots (mobile) --}}
            @if($step < 7)
                <div class="mt-8 flex justify-center gap-2">
                    @for($i = 1; $i <= 6; $i++)
                        <div class="w-2.5 h-2.5 rounded-full transition-all duration-300 {{ $i <= $step ? 'bg-primary scale-110' : 'bg-gray-300' }}"></div>
                    @endfor
                </div>
            @endif

        </div>
    </div>
</div>
