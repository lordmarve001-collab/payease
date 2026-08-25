<div class="min-h-screen flex flex-col lg:flex-row bg-background">

    {{-- LEFT PANEL — Brand Showcase --}}
    <div class="lg:w-1/2 min-h-[300px] lg:min-h-screen relative overflow-hidden flex items-center justify-center p-8 lg:p-16"
         style="background: linear-gradient(135deg, var(--color-primary, #03381e) 0%, #01190e 100%);">
        
        {{-- Decorative circles --}}
        <div class="absolute inset-0 opacity-15">
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full" style="background: var(--color-secondary, #c39027); filter: blur(80px);"></div>
            <div class="absolute -bottom-32 -right-32 w-[500px] h-[500px] rounded-full" style="background: var(--color-primary-light, #065f34); filter: blur(100px);"></div>
            <div class="absolute top-1/3 right-1/4 w-48 h-48 rounded-full" style="background: var(--color-accent, #c39027); filter: blur(60px);"></div>
        </div>

        {{-- Pattern overlay --}}
        <div class="absolute inset-0 opacity-5" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 24px 24px;"></div>

        <div class="relative z-10 text-center max-w-md">
            {{-- Large Brand Logo --}}
            <div class="mb-10 flex justify-center">
                <div class="relative">
                    @if($siteSettings->logo_path)
                        <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name ?? 'PayEase' }}" class="h-24 w-auto object-contain drop-shadow-2xl brightness-0 invert">
                    @else
                        <div class="w-28 h-28 rounded-3xl flex items-center justify-center shadow-2xl"
                             style="background: var(--color-secondary, #c39027);">
                            <span class="text-white text-5xl font-bold">&#8358;</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Brand Name --}}
            <h1 class="text-4xl lg:text-5xl font-bold text-white leading-tight mb-4 tracking-tight font-display">
                {{ $siteSettings->site_name ?? 'PayEase' }}
            </h1>

            {{-- Tagline --}}
            <p class="text-white/70 text-base lg:text-lg leading-relaxed max-w-sm mx-auto">
                {{ $siteSettings->site_tagline ?? __('Fast, secure digital payments for traders and businesses across Nigeria.') }}
            </p>

            {{-- Trust indicators --}}
            <div class="flex items-center justify-center gap-6 mt-10">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: rgba(255,255,255,0.12);">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <span class="text-white/60 text-sm font-medium">{{ __('Secure') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: rgba(255,255,255,0.12);">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <span class="text-white/60 text-sm font-medium">{{ __('Fast') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: rgba(255,255,255,0.12);">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </div>
                    <span class="text-white/60 text-sm font-medium">{{ __('Trusted') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT PANEL — Login Form --}}
    <div class="lg:w-1/2 min-h-screen flex items-center justify-center p-6 lg:p-16 bg-surface">
        <div class="w-full max-w-md">

            {{-- Brand header --}}
            <div class="flex flex-col items-center text-center mb-10">
                @if($siteSettings->logo_path)
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name ?? 'PayEase' }}" class="h-14 w-auto object-contain mb-4">
                @else
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-elevation-2 mb-4"
                         style="background: var(--color-primary, #03381e);">
                        <span class="text-white text-3xl font-bold">&#8358;</span>
                    </div>
                @endif
                <h1 class="text-2xl font-bold text-text-primary tracking-tight">{{ $siteSettings->site_name ?? 'PayEase' }}</h1>
                <p class="text-text-secondary text-sm mt-1">{{ __('Sign in to your account') }}</p>
            </div>

            <form wire:submit="login" class="space-y-5">
                {{-- Phone Number --}}
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-2">
                        {{ __('Phone Number') }}
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-text-secondary font-medium text-sm pointer-events-none border-r border-border mr-3 pr-3">
                            +234
                        </span>
                        <input type="tel" wire:model.live="phoneNumber"
                               x-on:input="$el.value = $el.value.replace(/\D/g, '')"
                               class="w-full pl-20 pr-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 outline-none transition-all"
                               placeholder="801 234 5678"
                               autocomplete="tel"
                               inputmode="numeric">
                    </div>
                    @error('phoneNumber') <p class="text-sm text-danger mt-2 flex items-center gap-1"><svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>{{ $message }}</p> @enderror
                </div>

                {{-- Password --}}
                <div x-data="{ show: false }">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-text-primary">
                            {{ __('Password') }}
                        </label>
                        <a href="{{ route('password.forgot') }}" class="text-xs font-semibold text-secondary hover:text-secondary/80 transition-colors">
                            {{ __('Forgot Password?') }}
                        </a>
                    </div>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" wire:model="pin"
                               class="w-full px-4 py-3.5 rounded-xl border border-border bg-background text-text-primary placeholder-text-secondary/50 outline-none transition-all tracking-[0.3em] text-center"
                               placeholder="&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                               autocomplete="current-password">
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center text-text-secondary hover:text-text-primary transition-colors">
                            <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                        </button>
                    </div>
                    @error('pin') <p class="text-sm text-danger mt-2 flex items-center gap-1"><svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>{{ $message }}</p> @enderror
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full py-3.5 rounded-xl font-semibold text-white transition-all active:scale-[0.98] shadow-elevation-1 hover:shadow-elevation-2 min-h-[48px] flex items-center justify-center gap-2"
                        style="background: var(--color-secondary, #c39027);"
                        wire:loading.attr="disabled" wire:target="login">
                    <span wire:loading.remove wire:target="login">{{ __('Log In') }}</span>
                    <span wire:loading wire:target="login" class="flex items-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ __('Signing in...') }}
                    </span>
                </button>
            </form>

            {{-- Divider --}}
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-border"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-surface text-text-secondary">{{ __('New here?') }}</span>
                </div>
            </div>

            {{-- Register Link --}}
            <a href="{{ route('register') }}"
               class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] border-2"
               style="border-color: var(--color-secondary); color: var(--color-secondary);">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                {{ __('Create an account') }}
            </a>

            {{-- Ajo Owner Signup --}}
            <a href="{{ route('ajo-owner.signup') }}"
               class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-semibold text-sm transition-all active:scale-[0.98] mt-3"
               style="border: 1px solid var(--color-secondary); color: var(--color-secondary);">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ __('Register as Ajo Owner') }}
            </a>

            {{-- Need help --}}
            <p class="text-center text-xs text-text-secondary mt-6">
                {{ __('Need help?') }}
                @if($siteSettings->support_phone)
                    <a href="tel:{{ $siteSettings->support_phone }}" class="font-medium text-text-primary hover:text-secondary transition-colors">{{ $siteSettings->support_phone }}</a>
                @endif
            </p>
        </div>
    </div>

</div>
