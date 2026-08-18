<div class="px-4 py-6 md:p-8 max-w-2xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('KYC Verification') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Verify your identity to unlock higher limits.') }}</p>
        </div>
        <a href="{{ route('ajo-owner.dashboard') }}" wire:navigate class="text-sm text-purple-600 hover:text-purple-700 font-medium flex items-center gap-1">
            <x-lucide-arrow-left class="w-4 h-4" /> {{ __('Dashboard') }}
        </a>
    </div>

    <!-- Tier Progress -->
    <div class="bg-surface rounded-card border border-border shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            @if($kycLevel >= 1)
                <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <x-lucide-check-circle class="w-6 h-6 text-emerald-600" />
                </div>
            @else
                <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <x-lucide-alert-circle class="w-6 h-6 text-red-600" />
                </div>
            @endif
            <div>
                <p class="font-semibold text-text-primary">Tier {{ $kycLevel }} — {{ __('Current Level') }}</p>
                <p class="text-text-secondary text-sm">
                    {{ match($kycLevel) {
                        0 => __('Unverified — ₦:limit/day limit', ['limit' => number_format(config('tiers.tiers.0.daily_limit'))]),
                        1 => __('Basic — ₦:limit/day limit', ['limit' => number_format(config('tiers.tiers.1.daily_limit'))]),
                        2 => __('Verified — ₦:limit/day limit', ['limit' => number_format(config('tiers.tiers.2.daily_limit'))]),
                        3 => __('Premium — ₦:limit/day limit', ['limit' => number_format(config('tiers.tiers.3.daily_limit'))]),
                        default => __('Unknown tier'),
                    } }}
                </p>
            </div>
        </div>

        <div class="flex gap-1.5">
            @for($i = 1; $i <= 3; $i++)
                <div class="flex-1 h-2 rounded-full {{ $kycLevel >= $i ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
            @endfor
        </div>
    </div>

    <!-- Pending Review Banner -->
    @php
        $pendingDocs = $documents->where('verification_status', 'pending');
        $latestRejectedNin = $documents->where('document_type', 'nin')->where('verification_status', 'rejected')->first();
        $latestRejectedBvn = $documents->where('document_type', 'bvn')->where('verification_status', 'rejected')->first();
        $latestRejectedDoc = $documents->whereNotIn('document_type', ['nin', 'bvn'])->where('verification_status', 'rejected')->first();
    @endphp

    @if($pendingDocs->isNotEmpty())
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center flex-shrink-0">
                <x-lucide-clock class="w-5 h-5 text-amber-600" />
            </div>
            <div>
                <p class="font-semibold text-amber-800 dark:text-amber-200 text-sm">{{ __('Under Review') }}</p>
                <p class="text-amber-700 dark:text-amber-300 text-sm mt-0.5">
                    {{ __('You have :count submission(s) under review by our team. This will later be verified automatically via NIN and BVN API.', ['count' => $pendingDocs->count()]) }}
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Tier Details -->
    <div class="bg-surface rounded-card border border-border shadow-sm p-6 space-y-4">
        <h2 class="font-bold text-text-primary">{{ __('Verification Tiers') }}</h2>

        <!-- Tier 1 -->
        @php
            $ninApproved = $documents->where('document_type', 'nin')->where('verification_status', 'verified')->first();
            $ninPending = $documents->where('document_type', 'nin')->where('verification_status', 'pending')->first();
        @endphp
        <div class="flex items-start gap-4 p-4 rounded-xl border {{ $kycLevel >= 1 ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-900/10' : ($ninPending ? 'border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10' : 'border-border') }}">
            <div class="w-10 h-10 rounded-full {{ $kycLevel >= 1 ? 'bg-emerald-100 text-emerald-600' : ($ninPending ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-400 dark:bg-gray-800') }} flex items-center justify-center flex-shrink-0">
                @if($kycLevel >= 1)
                    <x-lucide-check class="w-5 h-5" />
                @elseif($ninPending)
                    <x-lucide-clock class="w-5 h-5" />
                @else
                    <span class="font-bold text-sm">1</span>
                @endif
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <p class="font-semibold text-text-primary text-sm">{{ __('Basic — NIN Verification') }}</p>
                    @if($kycLevel >= 1)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Approved') }}</span>
                    @elseif($ninPending)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('Under Review') }}</span>
                    @endif
                </div>
                <p class="text-text-secondary text-xs mt-0.5">{{ __('₦5,000/day limit') }}</p>
                <p class="text-text-secondary text-xs">{{ __('Submit your National Identification Number (NIN) for basic verification') }}</p>
                @if($latestRejectedNin)
                    <div class="mt-2 p-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                        <p class="text-xs font-medium text-red-700 dark:text-red-400">{{ __('Rejection Reason:') }} {{ $latestRejectedNin->rejection_reason ?? __('No reason provided') }}</p>
                    </div>
                @endif
            </div>
            @if($kycLevel < 1 && !$ninPending)
                <button wire:click="openUploadModal('nin')" class="text-xs font-medium text-purple-600 hover:text-purple-700 whitespace-nowrap">
                    {{ $latestRejectedNin ? __('Re-submit') : __('Start') }}
                </button>
            @endif
        </div>

        <!-- Tier 2 -->
        @php
            $bvnApproved = $documents->where('document_type', 'bvn')->where('verification_status', 'verified')->first();
            $bvnPending = $documents->where('document_type', 'bvn')->where('verification_status', 'pending')->first();
        @endphp
        <div class="flex items-start gap-4 p-4 rounded-xl border {{ $kycLevel >= 2 ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-900/10' : ($bvnPending ? 'border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10' : 'border-border') }}">
            <div class="w-10 h-10 rounded-full {{ $kycLevel >= 2 ? 'bg-emerald-100 text-emerald-600' : ($bvnPending ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-400 dark:bg-gray-800') }} flex items-center justify-center flex-shrink-0">
                @if($kycLevel >= 2)
                    <x-lucide-check class="w-5 h-5" />
                @elseif($bvnPending)
                    <x-lucide-clock class="w-5 h-5" />
                @else
                    <span class="font-bold text-sm">2</span>
                @endif
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <p class="font-semibold text-text-primary text-sm">{{ __('Verified — BVN Verification') }}</p>
                    @if($kycLevel >= 2)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Approved') }}</span>
                    @elseif($bvnPending)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('Under Review') }}</span>
                    @endif
                </div>
                <p class="text-text-secondary text-xs mt-0.5">{{ __('₦50,000/day limit') }}</p>
                <p class="text-text-secondary text-xs">{{ __('Submit your Bank Verification Number (BVN) for higher limits') }}</p>
                @if($latestRejectedBvn)
                    <div class="mt-2 p-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                        <p class="text-xs font-medium text-red-700 dark:text-red-400">{{ __('Rejection Reason:') }} {{ $latestRejectedBvn->rejection_reason ?? __('No reason provided') }}</p>
                    </div>
                @endif
            </div>
            @if($kycLevel < 2 && $kycLevel >= 1 && !$bvnPending)
                <button wire:click="openUploadModal('bvn')" class="text-xs font-medium text-purple-600 hover:text-purple-700 whitespace-nowrap">
                    {{ $latestRejectedBvn ? __('Re-submit') : __('Start') }}
                </button>
            @endif
        </div>

        <!-- Tier 3 -->
        @php
            $addrApproved = $documents->whereIn('document_type', ['utility_bill', 'proof_of_address'])->where('verification_status', 'verified')->first();
            $addrPending = $documents->whereIn('document_type', ['utility_bill', 'proof_of_address'])->where('verification_status', 'pending')->first();
        @endphp
        <div class="flex items-start gap-4 p-4 rounded-xl border {{ $kycLevel >= 3 ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-900/10' : ($addrPending ? 'border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10' : 'border-border') }}">
            <div class="w-10 h-10 rounded-full {{ $kycLevel >= 3 ? 'bg-emerald-100 text-emerald-600' : ($addrPending ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-400 dark:bg-gray-800') }} flex items-center justify-center flex-shrink-0">
                @if($kycLevel >= 3)
                    <x-lucide-check class="w-5 h-5" />
                @elseif($addrPending)
                    <x-lucide-clock class="w-5 h-5" />
                @else
                    <span class="font-bold text-sm">3</span>
                @endif
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <p class="font-semibold text-text-primary text-sm">{{ __('Premium — Address Proof') }}</p>
                    @if($kycLevel >= 3)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Approved') }}</span>
                    @elseif($addrPending)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('Under Review') }}</span>
                    @endif
                </div>
                <p class="text-text-secondary text-xs mt-0.5">{{ __('₦200,000/day limit') }}</p>
                <p class="text-text-secondary text-xs">{{ __('Submit utility bill or proof of address for full access') }}</p>
                @if($latestRejectedDoc)
                    <div class="mt-2 p-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                        <p class="text-xs font-medium text-red-700 dark:text-red-400">{{ __('Rejection Reason:') }} {{ $latestRejectedDoc->rejection_reason ?? __('No reason provided') }}</p>
                    </div>
                @endif
            </div>
            @if($kycLevel < 3 && $kycLevel >= 2 && !$addrPending)
                <button wire:click="openUploadModal('address')" class="text-xs font-medium text-purple-600 hover:text-purple-700 whitespace-nowrap">
                    {{ $latestRejectedDoc ? __('Re-submit') : __('Upload') }}
                </button>
            @endif
        </div>
    </div>

    <!-- Submission History -->
    @if($documents->isNotEmpty())
    <div class="bg-surface rounded-card border border-border shadow-sm p-6 space-y-3">
        <h2 class="font-bold text-text-primary">{{ __('Submission History') }}</h2>
        @foreach($documents as $doc)
            <div class="flex items-center justify-between p-3 rounded-lg border border-border">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <x-lucide-file-text class="w-4 h-4 text-text-secondary" />
                    </div>
                    <div>
                        <p class="font-medium text-text-primary text-sm">{{ str_replace('_', ' ', ucfirst($doc->document_type)) }}</p>
                        <p class="text-text-secondary text-xs">{{ $doc->created_at->diffForHumans() }}</p>
                        @if($doc->rejection_reason && $doc->verification_status === 'rejected')
                            <p class="text-red-600 dark:text-red-400 text-xs mt-0.5">{{ __('Reason:') }} {{ $doc->rejection_reason }}</p>
                        @endif
                    </div>
                </div>
                @if($doc->verification_status === 'verified')
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Approved') }}</span>
                @elseif($doc->verification_status === 'rejected')
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Rejected') }}</span>
                @else
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('Under Review') }}</span>
                @endif
            </div>
        @endforeach
    </div>
    @endif

    <!-- Upload Modal -->
    @if($showUploadModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
        <div class="absolute inset-0 bg-black/50" wire:click="closeUploadModal"></div>
        <div class="relative bg-surface rounded-2xl shadow-xl w-full max-w-md p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-text-primary">
                    {{ $uploadDocType === 'bvn' ? __('Enter BVN') : ($uploadDocType === 'nin' ? __('Enter NIN') : __('Upload Document')) }}
                </h3>
                <button wire:click="closeUploadModal" class="text-text-secondary hover:text-text-primary">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>

            @if($uploadDocType === 'bvn')
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Bank Verification Number') }}</label>
                    <input type="text" wire:model="bvn" maxlength="11" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 font-mono text-lg tracking-wider focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="11 digits">
                    @error('bvn') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <button wire:click="saveBvn" wire:loading.attr="disabled" class="w-full py-3 bg-purple-600 text-white font-medium rounded-btn hover:bg-purple-700 transition-colors disabled:opacity-50">
                    <span wire:loading.remove>{{ __('Submit BVN') }}</span>
                    <span wire:loading>{{ __('Submitting...') }}</span>
                </button>

            @elseif($uploadDocType === 'nin')
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">{{ __('National Identification Number') }}</label>
                    <input type="text" wire:model="nin" maxlength="11" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 font-mono text-lg tracking-wider focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="11 digits">
                    @error('nin') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <button wire:click="saveNin" wire:loading.attr="disabled" class="w-full py-3 bg-purple-600 text-white font-medium rounded-btn hover:bg-purple-700 transition-colors disabled:opacity-50">
                    <span wire:loading.remove>{{ __('Submit NIN') }}</span>
                    <span wire:loading>{{ __('Submitting...') }}</span>
                </button>

            @else
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Document Type') }}</label>
                    <select wire:model="documentType" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 focus:ring-purple-600 focus:border-purple-600 outline-none">
                        <option value="">{{ __('Select document type') }}</option>
                        <option value="nin_slip">{{ __('NIN Slip') }}</option>
                        <option value="bvn_slip">{{ __('BVN Slip') }}</option>
                        <option value="government_id">{{ __('Government ID') }}</option>
                        <option value="utility_bill">{{ __('Utility Bill') }}</option>
                        <option value="passport_photograph">{{ __('Passport Photograph') }}</option>
                    </select>
                    @error('documentType') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">{{ __('Upload File') }} <span class="text-text-secondary font-normal">(JPG, PNG, PDF — max 5MB)</span></label>
                    <input type="file" wire:model="documentFile" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-sm text-text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-100 file:text-purple-700 hover:file:bg-purple-200 dark:file:bg-purple-900/30 dark:file:text-purple-400">
                    @error('documentFile') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <button wire:click="uploadDocument" wire:loading.attr="disabled" class="w-full py-3 bg-purple-600 text-white font-medium rounded-btn hover:bg-purple-700 transition-colors disabled:opacity-50">
                    <span wire:loading.remove>{{ __('Submit for Review') }}</span>
                    <span wire:loading>{{ __('Submitting...') }}</span>
                </button>
            @endif
        </div>
    </div>
    @endif

</div>
