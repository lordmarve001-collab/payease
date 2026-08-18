<div class="px-4 py-6 md:p-8 max-w-lg mx-auto relative overflow-hidden">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-text-primary">{{ __('Pay Bills') }}</h1>
        <p class="text-text-secondary text-sm">{{ __('Select a bill category to get started.') }}</p>
        @if($wallet)
            <p class="text-sm text-primary mt-2 font-medium">Available Balance: ₦{{ number_format($wallet->available_balance, 2) }}</p>
        @endif
    </div>

    @if($step === 'select')
    <div class="grid grid-cols-2 gap-4">
        <button wire:click="selectCategory('data')" class="rounded-card border border-border bg-surface p-5 text-center hover:shadow-elevation-2 transition-all duration-200 active:scale-[0.98]">
            <div class="w-12 h-12 mx-auto rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 mb-3">
                <x-lucide-wifi class="w-6 h-6" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Data Bundle') }}</span>
            <p class="text-xs text-text-secondary mt-1">{{ __('MTN, Airtel, Glo, 9mobile') }}</p>
        </button>

        <button wire:click="selectCategory('cable')" class="rounded-card border border-border bg-surface p-5 text-center hover:shadow-elevation-2 transition-all duration-200 active:scale-[0.98]">
            <div class="w-12 h-12 mx-auto rounded-xl bg-purple-100 flex items-center justify-center text-purple-600 mb-3">
                <x-lucide-tv class="w-6 h-6" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Cable TV') }}</span>
            <p class="text-xs text-text-secondary mt-1">{{ __('DSTV, GOtv, StarTimes') }}</p>
        </button>

        <button wire:click="selectCategory('electricity')" class="rounded-card border border-border bg-surface p-5 text-center hover:shadow-elevation-2 transition-all duration-200 active:scale-[0.98]">
            <div class="w-12 h-12 mx-auto rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 mb-3">
                <x-lucide-lightbulb class="w-6 h-6" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Electricity') }}</span>
            <p class="text-xs text-text-secondary mt-1">{{ __('Prepaid & Postpaid') }}</p>
        </button>

        <button wire:click="selectCategory('education')" class="rounded-card border border-border bg-surface p-5 text-center hover:shadow-elevation-2 transition-all duration-200 active:scale-[0.98]">
            <div class="w-12 h-12 mx-auto rounded-xl bg-green-100 flex items-center justify-center text-green-600 mb-3">
                <x-lucide-graduation-cap class="w-6 h-6" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Education') }}</span>
            <p class="text-xs text-text-secondary mt-1">{{ __('Exams & Fees') }}</p>
        </button>
    </div>
    @endif

    @if($step === 'details' && $category === 'education')
    <div class="space-y-6">
        <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
            <button wire:click="goBack" class="p-1 hover:text-primary">&larr; {{ __('Back') }}</button>
            <span>/</span>
            <span class="font-medium text-text-primary">{{ __('Education Payment') }}</span>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-3">{{ __('Exam Type') }}</label>
            <div class="grid grid-cols-2 gap-2">
                <button wire:click="set('educationExamType', 'WAEC')" class="rounded-btn border px-4 py-3 text-sm font-medium transition-all text-center {{ $educationExamType === 'WAEC' ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                    WAEC
                </button>
                <button wire:click="set('educationExamType', 'JAMB')" class="rounded-btn border px-4 py-3 text-sm font-medium transition-all text-center {{ $educationExamType === 'JAMB' ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                    JAMB
                </button>
                <button wire:click="set('educationExamType', 'NECO')" class="rounded-btn border px-4 py-3 text-sm font-medium transition-all text-center {{ $educationExamType === 'NECO' ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                    NECO
                </button>
                <button wire:click="set('educationExamType', 'SCHOOLFEES')" class="rounded-btn border px-4 py-3 text-sm font-medium transition-all text-center {{ $educationExamType === 'SCHOOLFEES' ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                     {{ __('School Fees') }}
                </button>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Student ID / Registration No') }}</label>
            <input type="text" wire:model="educationStudentId" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="{{ __('Enter registration number') }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Amount (₦)') }}</label>
            <input type="number" wire:model="educationAmount" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="1000" min="0" step="100">
        </div>

        @if($validationMessage !== '')
            <div class="rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">
                {{ $validationMessage }}
            </div>
        @endif

        <x-button variant="primary" size="large" wire:click="goToPin" class="w-full">
            {{ __('Continue') }}
        </x-button>
    </div>
    @endif

    @if($step === 'details' && $category === 'data')
    <div class="space-y-6">
        <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
            <button wire:click="goBack" class="p-1 hover:text-primary">&larr; {{ __('Back') }}</button>
            <span>/</span>
            <span class="font-medium text-text-primary">{{ __('Data Bundle') }}</span>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-3">{{ __('Network') }}</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach($networks as $network)
                    <button wire:click="selectDataNetwork('{{ $network }}')" class="rounded-btn border px-4 py-3 text-sm font-medium transition-all text-center {{ $dataNetwork === $network ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                        {{ $network }}
                    </button>
                @endforeach
            </div>
        </div>

        @if($dataNetwork && count($dataBundles) > 0)
        <div>
            <label class="block text-sm font-medium text-text-primary mb-3">{{ __('Data Bundle') }}</label>
            <div class="space-y-2">
                @foreach($dataBundles as $bundle)
                    <button wire:click="selectDataBundle('{{ $bundle['code'] }}', '{{ $bundle['name'] }}', {{ $bundle['price'] }})" class="w-full rounded-btn border px-4 py-3 text-left transition-all flex items-center justify-between {{ $dataBundleCode === $bundle['code'] ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                        <div>
                            <span class="font-medium">{{ $bundle['name'] }}</span>
                            <span class="text-xs text-text-secondary block">{{ $bundle['validity'] }}</span>
                        </div>
                        <span class="font-semibold">₦{{ number_format($bundle['price']) }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Phone Number') }}</label>
            <input type="tel" wire:model="dataPhone" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="08012345678" maxlength="11">
        </div>

        @if($validationMessage !== '')
            <div class="rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">
                {{ $validationMessage }}
            </div>
        @endif

        <x-button variant="primary" size="large" wire:click="goToPin" class="w-full">
            {{ __('Continue') }}
        </x-button>
    </div>
    @endif

    @if($step === 'details' && $category === 'cable')
    <div class="space-y-6">
        <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
            <button wire:click="goBack" class="p-1 hover:text-primary">&larr; {{ __('Back') }}</button>
            <span>/</span>
            <span class="font-medium text-text-primary">{{ __('Cable TV') }}</span>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-3">{{ __('Provider') }}</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach($cableProviders as $provider)
                    <button wire:click="selectCableProvider('{{ $provider }}')" class="rounded-btn border px-4 py-3 text-sm font-medium transition-all text-center {{ $cableProvider === $provider ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                        {{ $provider }}
                    </button>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Smart Card Number') }}</label>
            <input type="text" wire:model="cableSmartCard" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="{{ __('Enter smart card number') }}">
        </div>

        @if($cableProvider && isset($cablePackages[$cableProvider]))
        <div>
            <label class="block text-sm font-medium text-text-primary mb-3">{{ __('Package') }}</label>
            <div class="space-y-2">
                @foreach($cablePackages[$cableProvider] as $pkg)
                    <button wire:click="selectCablePackage('{{ $pkg['code'] }}', '{{ $pkg['name'] }}', {{ $pkg['price'] }})" class="w-full rounded-btn border px-4 py-3 text-left transition-all flex items-center justify-between {{ $cablePackageCode === $pkg['code'] ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                        <span class="font-medium">{{ $pkg['name'] }}</span>
                        <span class="font-semibold">₦{{ number_format($pkg['price']) }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        @if($validationMessage !== '')
            <div class="rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">
                {{ $validationMessage }}
            </div>
        @endif

        <x-button variant="primary" size="large" wire:click="goToPin" class="w-full">
            {{ __('Continue') }}
        </x-button>
    </div>
    @endif

    @if($step === 'details' && $category === 'electricity')
    <div class="space-y-6">
        <div class="flex items-center gap-2 text-sm text-text-secondary mb-2">
            <button wire:click="goBack" class="p-1 hover:text-primary">&larr; {{ __('Back') }}</button>
            <span>/</span>
            <span class="font-medium text-text-primary">{{ __('Electricity') }}</span>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-3">{{ __('Electricity Distributor') }}</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach($discos as $code => $name)
                    <button wire:click="set('electricDisco', '{{ $code }}')" class="rounded-btn border px-4 py-3 text-sm font-medium transition-all text-center {{ $electricDisco === $code ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                        {{ $name }}
                    </button>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-3">{{ __('Meter Type') }}</label>
            <div class="flex gap-2">
                <button wire:click="setElectricMeterType('prepaid')" class="flex-1 rounded-btn border px-4 py-3 text-sm font-medium transition-all text-center {{ $electricMeterType === 'prepaid' ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                    {{ __('Prepaid') }}
                </button>
                <button wire:click="setElectricMeterType('postpaid')" class="flex-1 rounded-btn border px-4 py-3 text-sm font-medium transition-all text-center {{ $electricMeterType === 'postpaid' ? 'border-primary bg-primary-light text-primary' : 'border-border bg-surface text-text-primary hover:border-primary' }}">
                    {{ __('Postpaid') }}
                </button>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Meter Number') }}</label>
            <input type="text" wire:model="electricMeterNumber" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="{{ __('Enter meter number') }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Amount (₦)') }}</label>
            <input type="number" wire:model="electricAmount" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="1000" min="0" step="100">
        </div>

        @if($validationMessage !== '')
            <div class="rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">
                {{ $validationMessage }}
            </div>
        @endif

        <x-button variant="primary" size="large" wire:click="goToPin" class="w-full">
            {{ __('Continue') }}
        </x-button>
    </div>
    @endif

    @if($step === 'confirm')
    <div class="space-y-6">
        <div class="bg-surface rounded-card p-6 shadow-elevation-1 space-y-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
                    @if($category === 'data')
                        <x-lucide-wifi class="w-8 h-8" />
                    @elseif($category === 'cable')
                        <x-lucide-tv class="w-8 h-8" />
                    @elseif($category === 'electricity')
                        <x-lucide-lightbulb class="w-8 h-8" />
                    @elseif($category === 'education')
                        <x-lucide-graduation-cap class="w-8 h-8" />
                    @endif
                </div>
                <h3 class="text-xl font-bold text-text-primary capitalize">{{ $category }} {{ __('Payment') }}</h3>
            </div>

            <div class="border-t border-b border-border py-4 space-y-3 text-sm">
                @if($category === 'data')
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Network') }}</span>
                        <span class="font-medium text-text-primary">{{ $dataNetwork }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Bundle') }}</span>
                        <span class="font-medium text-text-primary">{{ $dataBundleName }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Phone') }}</span>
                        <span class="font-medium text-text-primary">{{ $dataPhone }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Amount') }}</span>
                        <span class="font-bold text-primary">₦{{ number_format($dataPrice, 2) }}</span>
                    </div>
                @elseif($category === 'cable')
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Provider') }}</span>
                        <span class="font-medium text-text-primary">{{ $cableProvider }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Package') }}</span>
                        <span class="font-medium text-text-primary">{{ $cablePackageName }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Smart Card') }}</span>
                        <span class="font-medium text-text-primary">{{ $cableSmartCard }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Amount') }}</span>
                        <span class="font-bold text-primary">₦{{ number_format($cablePrice, 2) }}</span>
                    </div>
                @elseif($category === 'electricity')
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Disco') }}</span>
                        <span class="font-medium text-text-primary">{{ $discos[$electricDisco] ?? $electricDisco }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Meter Type') }}</span>
                        <span class="font-medium text-text-primary capitalize">{{ $electricMeterType }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Meter Number') }}</span>
                        <span class="font-medium text-text-primary">{{ $electricMeterNumber }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Amount') }}</span>
                        <span class="font-bold text-primary">₦{{ number_format((float)$electricAmount, 2) }}</span>
                    </div>
                @elseif($category === 'education')
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Exam Type') }}</span>
                        <span class="font-medium text-text-primary">{{ $educationExamType }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Student ID / Reg No') }}</span>
                        <span class="font-medium text-text-primary">{{ $educationStudentId }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-secondary">{{ __('Amount') }}</span>
                        <span class="font-bold text-primary">₦{{ number_format((float)$educationAmount, 2) }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-3 pt-4">
            <x-button variant="primary" size="large" wire:click="goToPin" :disabled="$isProcessing" class="w-full">
                {{ __('Confirm & Pay') }}
            </x-button>
            <x-button variant="secondary" size="large" wire:click="goBack" :disabled="$isProcessing" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Back') }}
            </x-button>
        </div>
    </div>
    @endif

    @if($step === 'pin')
    <div class="space-y-6">
        <div class="text-center">
            <div class="w-16 h-16 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
                <x-lucide-lock class="w-8 h-8" />
            </div>
            <h3 class="text-2xl font-bold text-text-primary">{{ __('Enter Transfer PIN') }}</h3>
            <p class="text-text-secondary text-sm mt-2">{{ __('Confirm your 6-digit PIN to authorize this payment.') }}</p>
        </div>

        <div class="flex gap-3 justify-center" x-data x-on:focus-bill-pin.window="$nextTick(() => $refs.pin1?.focus())">
            @foreach(['pin1','pin2','pin3','pin4','pin5','pin6'] as $i => $pin)
            <input type="password"
                   wire:model.live="{{ $pin }}"
                   x-ref="pin{{ $i + 1 }}"
                   maxlength="1"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   @input="if ($event.target.value.length > 1) $event.target.value = $event.target.value.slice(-1); if ($event.target.value && {{ $i + 1 }} < 6) $refs.pin{{ $i + 2 }}?.focus();"
                   @keyup.backspace="if (!$event.target.value && {{ $i + 1 }} > 1) $refs.pin{{ $i }}?.focus();"
                   class="w-12 h-14 text-center text-2xl font-bold rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            @endforeach
        </div>

        @if($pinError)
        <div class="rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger text-center">
            {{ $pinError }}
        </div>
        @endif

        <div class="space-y-3 pt-4">
            <x-button variant="primary" size="large" wire:click="confirmPin" wire:loading.attr="disabled" :disabled="$isProcessing" class="w-full">
                <span wire:loading.remove wire:target="confirmPin">{{ __('Authorize Payment') }}</span>
                <span wire:loading wire:target="confirmPin">{{ __('Processing...') }}</span>
            </x-button>
            <x-button variant="secondary" size="large" wire:click="goBack" :disabled="$isProcessing" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Back') }}
            </x-button>
        </div>
    </div>
    @endif

    @if($step === 'result')
    <div class="text-center space-y-6 pt-8">
        <div class="mx-auto w-24 h-24 rounded-full {{ $resultState === 'failed' ? 'bg-red-100' : 'bg-primary-light' }} flex items-center justify-center">
            @if($resultState === 'failed')
                <x-lucide-x class="w-12 h-12 text-danger" />
            @else
                <svg class="w-12 h-12 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            @endif
        </div>

        <div>
            <h2 class="text-2xl font-bold text-text-primary">{{ $resultState === 'failed' ? __('Purchase Failed') : __('Purchase Successful!') }}</h2>
            <p class="text-text-secondary mt-2">{{ $resultMessage }}</p>
        </div>

        <div class="bg-gray-50 rounded-card p-4 space-y-3 text-sm text-left">
            @if($category === 'data')
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Network') }}</span>
                    <span class="font-medium text-text-primary">{{ $dataNetwork }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Bundle') }}</span>
                    <span class="font-medium text-text-primary">{{ $dataBundleName }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Phone') }}</span>
                    <span class="font-medium text-text-primary">{{ $dataPhone }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Amount') }}</span>
                    <span class="font-bold text-primary">₦{{ number_format($dataPrice, 2) }}</span>
                </div>
            @elseif($category === 'cable')
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Provider') }}</span>
                    <span class="font-medium text-text-primary">{{ $cableProvider }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Package') }}</span>
                    <span class="font-medium text-text-primary">{{ $cablePackageName }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Amount') }}</span>
                    <span class="font-bold text-primary">₦{{ number_format($cablePrice, 2) }}</span>
                </div>
            @elseif($category === 'electricity')
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Disco') }}</span>
                    <span class="font-medium text-text-primary">{{ $discos[$electricDisco] ?? $electricDisco }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Meter Number') }}</span>
                    <span class="font-medium text-text-primary">{{ $electricMeterNumber }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Amount') }}</span>
                    <span class="font-bold text-primary">₦{{ number_format((float)$electricAmount, 2) }}</span>
                </div>
            @elseif($category === 'education')
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Exam Type') }}</span>
                    <span class="font-medium text-text-primary">{{ $educationExamType }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Student ID') }}</span>
                    <span class="font-medium text-text-primary">{{ $educationStudentId }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Amount') }}</span>
                    <span class="font-bold text-primary">₦{{ number_format((float)$educationAmount, 2) }}</span>
                </div>
            @endif
            @if($resultReference)
                <div class="flex justify-between">
                    <span class="text-text-secondary">{{ __('Reference') }}</span>
                    <span class="font-medium text-xs text-text-primary font-mono">{{ $resultReference }}</span>
                </div>
            @endif
        </div>

        <div class="space-y-3 pt-6">
            @if($resultState === 'failed')
                <x-button variant="primary" size="large" wire:click="goBack" class="w-full">
                    {{ __('Try Again') }}
                </x-button>
            @else
                <a href="{{ route('customer.dashboard') }}" wire:navigate class="inline-flex items-center justify-center w-full rounded-btn bg-primary text-white px-6 py-3.5 text-sm font-semibold transition-all hover:bg-primary-dark active:scale-[0.98]">
                    {{ __('Done') }}
                </a>
            @endif
            <x-button variant="secondary" size="large" wire:click="goBack" class="w-full bg-gray-100 text-text-primary hover:bg-gray-200">
                {{ __('Pay Another Bill') }}
            </x-button>
        </div>
    </div>
    @endif
</div>
