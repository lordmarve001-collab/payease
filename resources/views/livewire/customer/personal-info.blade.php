<div class="px-4 py-6 md:p-8 max-w-xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-text-primary">{{ __('Personal Info') }}</h2>
            <p class="text-text-secondary mt-1">{{ __('Manage your personal details') }}</p>
        </div>
        @if(!$isEditing)
            <button wire:click="toggleEdit" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:text-primary-dark transition-colors">
                <x-lucide-pencil class="w-4 h-4" />
                {{ __('Edit') }}
            </button>
        @endif
    </div>

    <div class="rounded-card border border-border bg-surface shadow-elevation-1 overflow-hidden">
        <div class="p-6 space-y-5">
            @if(!$isEditing)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('Full Name') }}</p>
                        <p class="text-sm font-semibold text-text-primary mt-1">{{ $fullName }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('Phone Number') }}</p>
                        <p class="text-sm font-semibold text-text-primary mt-1">+234 {{ substr($user->phone_number, 1) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('Email') }}</p>
                        <p class="text-sm font-semibold text-text-primary mt-1">{{ $email ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('Date of Birth') }}</p>
                        <p class="text-sm font-semibold text-text-primary mt-1">{{ $dateOfBirth ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('Gender') }}</p>
                        <p class="text-sm font-semibold text-text-primary mt-1">{{ $gender ? ucfirst($gender) : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('KYC Level') }}</p>
                        <p class="text-sm font-semibold text-text-primary mt-1">Tier {{ $user->kyc_level }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('LGA') }}</p>
                        <p class="text-sm font-semibold text-text-primary mt-1">{{ $lga ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('State') }}</p>
                        <p class="text-sm font-semibold text-text-primary mt-1">{{ $state ?: '—' }}</p>
                    </div>
                </div>

                <hr class="border-border">

                <div>
                    <p class="text-xs font-semibold text-text-primary uppercase tracking-wider mb-3">{{ __('Next of Kin') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('Name') }}</p>
                            <p class="text-sm font-semibold text-text-primary mt-1">{{ $nextOfKinName ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('Relationship') }}</p>
                            <p class="text-sm font-semibold text-text-primary mt-1">{{ $nextOfKinRelationship ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-text-secondary uppercase tracking-wider font-medium">{{ __('Phone') }}</p>
                            <p class="text-sm font-semibold text-text-primary mt-1">{{ $nextOfKinPhone ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            @else
                <form wire:submit="save" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Full Name') }}</label>
                        <input type="text" wire:model="fullName" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('fullName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Email') }}</label>
                        <input type="email" wire:model="email" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('email') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Date of Birth') }}</label>
                            <input type="date" wire:model="dateOfBirth" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            @error('dateOfBirth') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Gender') }}</label>
                            <select wire:model="gender" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">{{ __('Select') }}</option>
                                <option value="male">{{ __('Male') }}</option>
                                <option value="female">{{ __('Female') }}</option>
                                <option value="other">{{ __('Other') }}</option>
                            </select>
                            @error('gender') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('LGA') }}</label>
                            <input type="text" wire:model="lga" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            @error('lga') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('State') }}</label>
                            <input type="text" wire:model="state" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            @error('state') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <hr class="border-border">

                    <p class="text-xs font-semibold text-text-primary uppercase tracking-wider">{{ __('Next of Kin') }}</p>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Name') }}</label>
                        <input type="text" wire:model="nextOfKinName" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('nextOfKinName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Relationship') }}</label>
                            <input type="text" wire:model="nextOfKinRelationship" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            @error('nextOfKinRelationship') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">{{ __('Phone') }}</label>
                            <input type="text" wire:model="nextOfKinPhone" class="w-full px-4 py-3 rounded-btn border border-border bg-background text-text-primary outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            @error('nextOfKinPhone') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <x-button type="button" variant="secondary" class="flex-1 bg-gray-100 text-text-primary hover:bg-gray-200" wire:click="toggleEdit">{{ __('Cancel') }}</x-button>
                        <x-button type="submit" variant="primary" class="flex-1">{{ __('Save Changes') }}</x-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
