<div class="space-y-6 pb-6">
    <div class="flex items-center gap-3">
        @if(in_array($step, ['details', 'confirm']))
            <button wire:click="goBack" class="p-2 rounded-lg hover:bg-background text-text-secondary hover:text-text-primary transition-colors">
                <x-lucide-arrow-left class="w-5 h-5" />
            </button>
        @else
            <a href="{{ route('ajo-owner.dashboard') }}" wire:navigate class="p-2 rounded-lg hover:bg-background text-text-secondary hover:text-text-primary transition-colors">
                <x-lucide-arrow-left class="w-5 h-5" />
            </a>
        @endif
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('Pay Bills') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Airtime, Data, Cable, Electricity & more') }}</p>
        </div>
    </div>

    <!-- Balance -->
    <div class="bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 rounded-2xl p-4 text-white shadow-lg">
        <p class="text-purple-200 text-xs font-medium">{{ __('Available Balance') }}</p>
        <p class="text-2xl font-bold mt-1">₦{{ number_format($wallet?->available_balance ?? 0, 2) }}</p>
    </div>

    @if($step === 'select')
    <!-- Category Grid -->
    <div class="grid grid-cols-2 gap-3">
        <button wire:click="selectCategory('airtime')" class="flex flex-col items-center gap-2 p-5 bg-surface rounded-xl border border-border shadow-sm hover:shadow-md transition-all active:scale-[0.98]">
            <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <x-lucide-smartphone class="w-6 h-6 text-emerald-600" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Airtime') }}</span>
        </button>
        <button wire:click="selectCategory('data')" class="flex flex-col items-center gap-2 p-5 bg-surface rounded-xl border border-border shadow-sm hover:shadow-md transition-all active:scale-[0.98]">
            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <x-lucide-wifi class="w-6 h-6 text-blue-600" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Data') }}</span>
        </button>
        <button wire:click="selectCategory('cable')" class="flex flex-col items-center gap-2 p-5 bg-surface rounded-xl border border-border shadow-sm hover:shadow-md transition-all active:scale-[0.98]">
            <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                <x-lucide-monitor class="w-6 h-6 text-indigo-600" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Cable TV') }}</span>
        </button>
        <button wire:click="selectCategory('electricity')" class="flex flex-col items-center gap-2 p-5 bg-surface rounded-xl border border-border shadow-sm hover:shadow-md transition-all active:scale-[0.98]">
            <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                <x-lucide-zap class="w-6 h-6 text-yellow-600" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Electricity') }}</span>
        </button>
        <button wire:click="selectCategory('education')" class="flex flex-col items-center gap-2 p-5 bg-surface rounded-xl border border-border shadow-sm hover:shadow-md transition-all active:scale-[0.98]">
            <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <x-lucide-graduation-cap class="w-6 h-6 text-purple-600" />
            </div>
            <span class="text-sm font-semibold text-text-primary">{{ __('Education') }}</span>
        </button>
    </div>
    @endif

    @if($step === 'details')
    <!-- Category Details -->
    @if($category === 'airtime')
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Phone Number') }}</label>
            <input type="tel" wire:model.live="airtimePhone" placeholder="08012345678"
                   class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Network') }}</label>
            <div class="grid grid-cols-4 gap-2">
                @foreach($networks as $network)
                <button wire:click="$set('airtimeNetwork', '{{ $network }}')"
                        class="py-3 rounded-xl text-sm font-semibold border-2 transition-all {{ $airtimeNetwork === $network ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20 text-purple-600' : 'border-border bg-surface text-text-secondary hover:border-purple-300' }}">
                    {{ $network }}
                </button>
                @endforeach
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Amount') }}</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-text-secondary font-medium">₦</span>
                <input type="number" wire:model.live="airtimeAmount" placeholder="500" min="50"
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
            </div>
        </div>
    </div>

    @elseif($category === 'data')
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Phone Number') }}</label>
            <input type="tel" wire:model.live="dataPhone" placeholder="08012345678"
                   class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Network') }}</label>
            <div class="grid grid-cols-4 gap-2">
                @foreach($networks as $network)
                <button wire:click="selectDataNetwork('{{ $network }}')"
                        class="py-3 rounded-xl text-sm font-semibold border-2 transition-all {{ $dataNetwork === $network ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20 text-purple-600' : 'border-border bg-surface text-text-secondary hover:border-purple-300' }}">
                    {{ $network }}
                </button>
                @endforeach
            </div>
        </div>
        @if($dataNetwork && count($dataBundles) > 0)
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Select Bundle') }}</label>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach($dataBundles as $bundle)
                <button wire:click="selectDataBundle('{{ $bundle['code'] }}', '{{ $bundle['name'] }}', {{ $bundle['price'] }})"
                        class="w-full flex items-center justify-between p-3 rounded-xl border-2 transition-all {{ $dataBundleCode === ($bundle['code'] ?? '') ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20' : 'border-border bg-surface hover:border-purple-300' }}">
                    <span class="text-sm font-medium text-text-primary">{{ $bundle['name'] }}</span>
                    <span class="text-sm font-bold text-purple-600">₦{{ number_format($bundle['price'], 0) }}</span>
                </button>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    @elseif($category === 'cable')
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Provider') }}</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach($cableProviders as $provider)
                <button wire:click="selectCableProvider('{{ $provider }}')"
                        class="py-3 rounded-xl text-sm font-semibold border-2 transition-all {{ $cableProvider === $provider ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20 text-purple-600' : 'border-border bg-surface text-text-secondary hover:border-purple-300' }}">
                    {{ $provider }}
                </button>
                @endforeach
            </div>
        </div>
        @if($cableProvider)
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Smart Card Number') }}</label>
            <input type="text" wire:model.live="cableSmartCard" placeholder="1234567890"
                   class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Package') }}</label>
            <div class="space-y-2">
                @foreach(($cablePackages[$cableProvider] ?? []) as $pkg)
                <button wire:click="selectCablePackage('{{ $pkg['code'] }}', '{{ $pkg['name'] }}', {{ $pkg['price'] }})"
                        class="w-full flex items-center justify-between p-3 rounded-xl border-2 transition-all {{ $cablePackageCode === $pkg['code'] ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20' : 'border-border bg-surface hover:border-purple-300' }}">
                    <span class="text-sm font-medium text-text-primary">{{ $pkg['name'] }}</span>
                    <span class="text-sm font-bold text-purple-600">₦{{ number_format($pkg['price'], 0) }}</span>
                </button>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    @elseif($category === 'electricity')
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Distributor') }}</label>
            <select wire:model.live="electricDisco"
                    class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500">
                <option value="">-- {{ __('Select Disco') }} --</option>
                @foreach($discos as $code => $name)
                    <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Meter Type') }}</label>
            <div class="flex gap-3">
                <button wire:click="$set('electricMeterType', 'prepaid')"
                        class="flex-1 py-3 rounded-xl text-sm font-semibold border-2 transition-all {{ $electricMeterType === 'prepaid' ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20 text-purple-600' : 'border-border bg-surface text-text-secondary' }}">
                    {{ __('Prepaid') }}
                </button>
                <button wire:click="$set('electricMeterType', 'postpaid')"
                        class="flex-1 py-3 rounded-xl text-sm font-semibold border-2 transition-all {{ $electricMeterType === 'postpaid' ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20 text-purple-600' : 'border-border bg-surface text-text-secondary' }}">
                    {{ __('Postpaid') }}
                </button>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Meter Number') }}</label>
            <input type="text" wire:model.live="electricMeterNumber" placeholder="1234567890"
                   class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Amount') }}</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-text-secondary font-medium">₦</span>
                <input type="number" wire:model.live="electricAmount" placeholder="5000" min="500"
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
            </div>
        </div>
    </div>

    @elseif($category === 'education')
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Exam Type') }}</label>
            <select wire:model.live="educationExamType"
                    class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500">
                <option value="">-- {{ __('Select Exam') }} --</option>
                <option value="waec">WAEC</option>
                <option value="jamb">JAMB</option>
                <option value="nabteb">NABTEB</option>
                <option value="neco">NECO</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Student ID / Registration No.') }}</label>
            <input type="text" wire:model.live="educationStudentId" placeholder="Enter ID"
                   class="w-full px-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500" />
        </div>
        <div>
            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Amount') }}</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-text-secondary font-medium">₦</span>
                <input type="number" wire:model.live="educationAmount" placeholder="5000" min="100"
                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 tabular-nums" />
            </div>
        </div>
    </div>
    @endif

    @if($validationMessage)
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-3 text-sm text-red-700 dark:text-red-400">
        {{ $validationMessage }}
    </div>
    @endif

    <button wire:click="goToConfirm"
            class="w-full py-3.5 bg-purple-600 text-white rounded-xl font-semibold text-sm hover:bg-purple-700 transition-all active:scale-[0.98] shadow-lg">
        {{ __('Continue') }}
    </button>
    @endif

    @if($step === 'confirm')
    <!-- Confirm -->
    <div class="bg-surface rounded-xl border border-border p-5 space-y-4">
        <h3 class="font-semibold text-text-primary text-center">{{ __('Confirm Purchase') }}</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Service') }}</span>
                <span class="text-sm font-semibold text-text-primary capitalize">{{ $category }}</span>
            </div>
            @if($category === 'airtime')
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Phone') }}</span>
                <span class="text-sm font-semibold text-text-primary tabular-nums">{{ $airtimePhone }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Network') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ $airtimeNetwork }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Amount') }}</span>
                <span class="text-sm font-bold text-purple-600">₦{{ number_format($airtimeAmount, 2) }}</span>
            </div>
            @elseif($category === 'data')
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Phone') }}</span>
                <span class="text-sm font-semibold text-text-primary tabular-nums">{{ $dataPhone }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Bundle') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ $dataBundleName }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Amount') }}</span>
                <span class="text-sm font-bold text-purple-600">₦{{ number_format($dataPrice, 2) }}</span>
            </div>
            @elseif($category === 'cable')
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Provider') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ $cableProvider }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Smart Card') }}</span>
                <span class="text-sm font-semibold text-text-primary tabular-nums">{{ $cableSmartCard }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Package') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ $cablePackageName }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Amount') }}</span>
                <span class="text-sm font-bold text-purple-600">₦{{ number_format($cablePrice, 2) }}</span>
            </div>
            @elseif($category === 'electricity')
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Disco') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ $discos[$electricDisco] ?? $electricDisco }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Meter') }}</span>
                <span class="text-sm font-semibold text-text-primary tabular-nums">{{ $electricMeterNumber }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Amount') }}</span>
                <span class="text-sm font-bold text-purple-600">₦{{ number_format((float) $electricAmount, 2) }}</span>
            </div>
            @elseif($category === 'education')
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Exam') }}</span>
                <span class="text-sm font-semibold text-text-primary uppercase">{{ $educationExamType }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Student ID') }}</span>
                <span class="text-sm font-semibold text-text-primary">{{ $educationStudentId }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-text-secondary">{{ __('Amount') }}</span>
                <span class="text-sm font-bold text-purple-600">₦{{ number_format((float) $educationAmount, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    <button wire:click="purchase" wire:loading.attr="disabled" :disabled="$isProcessing"
            class="w-full py-3.5 bg-purple-600 text-white rounded-xl font-semibold text-sm hover:bg-purple-700 transition-all active:scale-[0.98] shadow-lg flex items-center justify-center gap-2">
        <span wire:loading.remove>{{ __('Confirm & Pay') }}</span>
        <span wire:loading class="flex items-center gap-2">
            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            {{ __('Processing...') }}
        </span>
    </button>
    @endif

    @if($step === 'result')
    <!-- Result -->
    <div class="bg-surface rounded-xl border border-border p-6 text-center space-y-4">
        @if($resultState === 'success')
        <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mx-auto">
            <x-lucide-check-circle class="w-9 h-9 text-emerald-600" />
        </div>
        <h3 class="text-xl font-bold text-text-primary">{{ __('Purchase Successful') }}</h3>
        @else
        <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto">
            <x-lucide-x-circle class="w-9 h-9 text-red-600" />
        </div>
        <h3 class="text-xl font-bold text-text-primary">{{ __('Purchase Failed') }}</h3>
        @endif

        <p class="text-sm text-text-secondary">{{ $resultMessage }}</p>

        @if($resultReference)
        <div class="bg-background rounded-xl p-3">
            <span class="text-xs text-text-secondary">Reference</span>
            <p class="font-mono text-sm font-semibold text-text-primary">{{ $resultReference }}</p>
        </div>
        @endif

        <button wire:click="goBack"
                class="w-full py-3.5 bg-purple-600 text-white rounded-xl font-semibold text-sm hover:bg-purple-700 transition-all active:scale-[0.98] shadow-lg">
            {{ $resultState === 'success' ? __('New Purchase') : __('Try Again') }}
        </button>

        <a href="{{ route('ajo-owner.dashboard') }}" wire:navigate
           class="block text-sm font-medium text-purple-600 hover:text-purple-700">{{ __('Back to Dashboard') }}</a>
    </div>
    @endif
</div>
