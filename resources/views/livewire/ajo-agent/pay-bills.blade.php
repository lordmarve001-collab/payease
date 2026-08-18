<div class="px-4 py-6 md:p-8 max-w-lg mx-auto relative overflow-hidden">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Pay Bills') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Purchase airtime, data, cable & electricity from your personal wallet.') }}</p>
        @if($wallet)
            <p class="text-sm text-emerald-600 mt-2 font-medium">{{ __('Personal Balance:') }} ₦{{ number_format($wallet->available_balance, 2) }}</p>
        @endif
    </div>

    <!-- Category Select -->
    @if($step === 'select')
    <div class="grid grid-cols-2 gap-4">
        <button wire:click="selectCategory('airtime')" class="rounded-card border border-border bg-surface p-5 text-center hover:shadow-elevation-2 transition-all active:scale-[0.98]">
            <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 mb-3">
                <x-lucide-phone class="w-6 h-6" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Airtime') }}</span>
            <p class="text-xs text-text-secondary mt-1">{{ __('All networks') }}</p>
        </button>
        <button wire:click="selectCategory('data')" class="rounded-card border border-border bg-surface p-5 text-center hover:shadow-elevation-2 transition-all active:scale-[0.98]">
            <div class="w-12 h-12 mx-auto rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 mb-3">
                <x-lucide-wifi class="w-6 h-6" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Data Bundle') }}</span>
            <p class="text-xs text-text-secondary mt-1">{{ __('MTN, Airtel, Glo, 9mobile') }}</p>
        </button>
        <button wire:click="selectCategory('cable')" class="rounded-card border border-border bg-surface p-5 text-center hover:shadow-elevation-2 transition-all active:scale-[0.98]">
            <div class="w-12 h-12 mx-auto rounded-xl bg-purple-100 flex items-center justify-center text-purple-600 mb-3">
                <x-lucide-tv class="w-6 h-6" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Cable TV') }}</span>
            <p class="text-xs text-text-secondary mt-1">{{ __('DStv, GOtv, StarTimes') }}</p>
        </button>
        <button wire:click="selectCategory('electricity')" class="rounded-card border border-border bg-surface p-5 text-center hover:shadow-elevation-2 transition-all active:scale-[0.98]">
            <div class="w-12 h-12 mx-auto rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 mb-3">
                <x-lucide-lightbulb class="w-6 h-6" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Electricity') }}</span>
            <p class="text-xs text-text-secondary mt-1">{{ __('Prepaid & Postpaid') }}</p>
        </button>
    </div>
    @endif

    <!-- Details -->
    @if($step === 'details')
    <div class="space-y-5">
        <button wire:click="goBack" class="text-sm text-text-secondary hover:text-emerald-600 transition-colors">&larr; {{ __('Back') }}</button>

        <!-- Airtime -->
        @if($category === 'airtime')
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Phone Number') }}</label>
                <div class="flex">
                    <span class="inline-flex items-center px-4 rounded-l-btn border border-r-0 border-border bg-gray-50 text-text-secondary text-sm">+234</span>
                    <input type="tel" wire:model="airtimePhone" maxlength="11" class="flex-1 rounded-none rounded-r-btn border-border focus:ring-emerald-500 focus:border-emerald-500 px-4 py-3 border outline-none" placeholder="8012345678">
                </div>
                @error('airtimePhone') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Network') }}</label>
                <div class="grid grid-cols-4 gap-2">
                    @foreach($networks as $key => $name)
                        <button type="button" wire:click="$set('airtimeNetwork', '{{ $key }}')" class="rounded-btn border px-3 py-2.5 text-xs font-medium transition-all text-center {{ $airtimeNetwork === $key ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-border bg-surface text-text-primary hover:border-emerald-300' }}">
                            {{ $name }}
                        </button>
                    @endforeach
                </div>
                @error('airtimeNetwork') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Amount (₦)') }}</label>
                <input type="number" wire:model="airtimeAmount" min="50" step="50" class="w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-lg font-bold text-center tabular-nums" placeholder="500">
                @error('airtimeAmount') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
            </div>
        @endif

        <!-- Data -->
        @if($category === 'data')
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Phone Number') }}</label>
                <div class="flex">
                    <span class="inline-flex items-center px-4 rounded-l-btn border border-r-0 border-border bg-gray-50 text-text-secondary text-sm">+234</span>
                    <input type="tel" wire:model="dataPhone" maxlength="11" class="flex-1 rounded-none rounded-r-btn border-border focus:ring-emerald-500 focus:border-emerald-500 px-4 py-3 border outline-none" placeholder="8012345678">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Network') }}</label>
                <div class="grid grid-cols-4 gap-2">
                    @foreach($networks as $key => $name)
                        <button type="button" wire:click="$set('dataNetwork', '{{ $key }}')" class="rounded-btn border px-3 py-2.5 text-xs font-medium transition-all text-center {{ $dataNetwork === $key ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-border bg-surface text-text-primary hover:border-emerald-300' }}">
                            {{ $name }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Data Bundle') }}</label>
                <input type="text" wire:model="dataBundleCode" class="w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Bundle code (e.g. MP1500)">
            </div>
        @endif

        <!-- Cable -->
        @if($category === 'cable')
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Provider') }}</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach($cableProviders as $key => $name)
                        <button type="button" wire:click="$set('cableProvider', '{{ $key }}')" class="rounded-btn border px-3 py-2.5 text-xs font-medium transition-all text-center {{ $cableProvider === $key ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-border bg-surface text-text-primary hover:border-emerald-300' }}">
                            {{ $name }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Smart Card / IUC Number') }}</label>
                <input type="text" wire:model="cableSmartCard" class="w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Enter smart card number">
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Package Code') }}</label>
                <input type="text" wire:model="cablePackageCode" class="w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Package code (e.g. DSTV-PREMIUM)">
            </div>
        @endif

        <!-- Electricity -->
        @if($category === 'electricity')
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Disco') }}</label>
                <select wire:model="electricDisco" class="w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('-- Select Disco --') }}</option>
                    @foreach($discos as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Meter Type') }}</label>
                <div class="flex gap-3">
                    <button type="button" wire:click="$set('electricMeterType', 'prepaid')" class="flex-1 rounded-btn border px-4 py-2.5 text-sm font-medium transition-all {{ $electricMeterType === 'prepaid' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-border bg-surface text-text-primary' }}">
                        {{ __('Prepaid') }}
                    </button>
                    <button type="button" wire:click="$set('electricMeterType', 'postpaid')" class="flex-1 rounded-btn border px-4 py-2.5 text-sm font-medium transition-all {{ $electricMeterType === 'postpaid' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-border bg-surface text-text-primary' }}">
                        {{ __('Postpaid') }}
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Meter Number') }}</label>
                <input type="text" wire:model="electricMeterNumber" class="w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Enter meter number">
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Amount (₦)') }}</label>
                <input type="number" wire:model="electricAmount" min="500" step="100" class="w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-lg font-bold text-center tabular-nums" placeholder="2000">
            </div>
        @endif

        <!-- PIN -->
        <div class="pt-2">
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Enter Your PIN') }}</label>
            <input type="password" wire:model="pin" inputmode="numeric" maxlength="6" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary text-center tracking-[0.3em] text-xl outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="••••••">
            @error('pin') <p class="text-sm text-danger mt-1 text-center">{{ $message }}</p> @enderror
        </div>

        <button wire:click="submitBill" wire:target="submitBill" wire:loading.attr="disabled" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98] shadow-elevation-1 disabled:opacity-50">
            <span wire:loading.remove wire:target="submitBill">{{ __('Pay Bill') }}</span>
            <span wire:loading wire:target="submitBill" class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ __('Processing...') }}
            </span>
        </button>
    </div>
    @endif

    <!-- Result -->
    @if($step === 'result')
    <div class="text-center space-y-6 py-4">
        @if($resultState === 'success')
            <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto">
                <x-lucide-check class="w-10 h-10 text-emerald-600" />
            </div>
            <div>
                <h2 class="text-xl font-bold text-text-primary">{{ __('Bill Paid!') }}</h2>
                <p class="text-text-secondary mt-1">{{ $resultMessage }}</p>
            </div>
            @if($resultReference !== 'N/A')
            <div class="bg-surface rounded-card border border-border p-4">
                <p class="text-xs text-text-secondary mb-1">{{ __('Reference') }}</p>
                <p class="font-mono text-sm font-medium text-text-primary">{{ $resultReference }}</p>
            </div>
            @endif
        @else
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                <x-lucide-x class="w-10 h-10 text-red-600" />
            </div>
            <div>
                <h2 class="text-xl font-bold text-text-primary">{{ __('Payment Failed') }}</h2>
                <p class="text-danger mt-1 text-sm">{{ $resultMessage }}</p>
            </div>
        @endif

        <button wire:click="resetForm" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-semibold transition-all active:scale-[0.98]">
            {{ __('Pay Another Bill') }}
        </button>
    </div>
    @endif
</div>
