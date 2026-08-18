<div class="px-4 py-6 md:p-8 w-full max-w-7xl mx-auto space-y-6">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('My Field Agents') }}</h1>
            <p class="text-text-secondary text-sm">{{ __('Create or link agents who collect contributions for your groups.') }}</p>
        </div>
        <div class="flex gap-2 shrink-0">
            <x-button variant="secondary" class="border-purple-600 text-purple-600 hover:bg-purple-50" wire:click="openLinkModal">
                <x-lucide-link class="w-4 h-4 mr-2" /> {{ __('Link Existing') }}
            </x-button>
            <x-button variant="primary" class="bg-purple-600 hover:bg-purple-700" wire:click="openCreateModal">
                <x-lucide-user-plus class="w-4 h-4 mr-2" /> {{ __('Create Agent') }}
            </x-button>
        </div>
    </div>

    <x-data-table :title="__('Agent Network')" :searchPlaceholder="__('Search agent name or LGA...')" :filters="['status']">
        <x-slot:header>
            <th class="px-4 py-3 font-medium text-left">{{ __('Agent Name') }}</th>
            <th class="px-4 py-3 font-medium text-left">{{ __('LGA / Location') }}</th>
            <th class="px-4 py-3 font-medium text-center">{{ __('Groups Managed') }}</th>
            <th class="px-4 py-3 font-medium text-center">{{ __('Total Members') }}</th>
            <th class="px-4 py-3 font-medium text-left">{{ __('Status') }}</th>
            <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
        </x-slot:header>

        @forelse($agents as $agent)
            <tr class="hover:bg-background hover:shadow-elevation-1 transition-all group">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded bg-secondary/10 text-secondary flex items-center justify-center font-bold text-xs">
                            <x-lucide-store class="w-4 h-4" />
                        </div>
                        <div>
                            <span class="font-bold text-text-primary whitespace-nowrap">{{ $agent->user?->full_name ?? $agent->business_name }}</span>
                            <p class="text-xs text-text-secondary">{{ $agent->user?->phone_number }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ $agent->lga }}, {{ $agent->state }}</td>
                <td class="px-4 py-3 text-sm text-center font-medium">{{ $agent->managing_ajo_groups_count }}</td>
                <td class="px-4 py-3 text-sm text-center text-text-secondary">{{ $memberCounts[$agent->id] ?? 0 }}</td>
                <td class="px-4 py-3">
                    <x-status-badge :status="strtolower($agent->status)" />
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="{{ route('ajo-owner.agents.detail', $agent->id) }}" wire:navigate
                           class="p-1.5 text-text-secondary hover:text-purple-600 transition-colors bg-surface rounded shadow-sm border border-border"
                           title="{{ __('View Activity') }}">
                            <x-lucide-eye class="w-4 h-4" />
                        </a>

                        @if(strtolower($agent->status) !== 'suspended')
                            <button wire:click="confirmSuspend('{{ $agent->id }}')"
                                    class="p-1.5 text-text-secondary hover:text-amber-600 transition-colors bg-surface rounded shadow-sm border border-border"
                                    title="{{ __('Suspend Agent') }}">
                                <x-lucide-pause-circle class="w-4 h-4" />
                            </button>
                        @endif

                        @if(!($pendingDeletions[$agent->id] ?? false))
                            <button wire:click="confirmDelete('{{ $agent->id }}')"
                                    class="p-1.5 text-text-secondary hover:text-red-600 transition-colors bg-surface rounded shadow-sm border border-border"
                                    title="{{ __('Request Deletion') }}">
                                <x-lucide-trash-2 class="w-4 h-4" />
                            </button>
                        @else
                            <span class="px-2 py-1 text-xs font-medium text-amber-700 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 rounded" title="{{ __('Deletion pending admin approval') }}">
                                {{ __('Pending') }}
                            </span>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-text-secondary">{{ __('No agents yet.') }}</td>
            </tr>
        @endforelse
    </x-data-table>

    <div>
        {{ $agents->links() }}
    </div>

    {{-- Create Agent Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
            <div class="fixed inset-0 bg-text-primary/40 backdrop-blur-sm" wire:click="closeCreateModal"></div>
            <div class="relative bg-surface rounded-card shadow-elevation-4 w-full max-w-md overflow-hidden z-10">
                <div class="p-4 border-b border-border flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                    <h3 class="font-bold text-text-primary">{{ __('Create Field Agent') }}</h3>
                    <button wire:click="closeCreateModal" class="text-text-secondary hover:text-text-primary transition-colors">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <p class="text-sm text-text-secondary">{{ __('Create a new field agent account tied directly to your network. They will receive an SMS with their login PIN and a welcome email.') }}</p>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Full Name') }}</label>
                        <input type="text" wire:model="newFullName" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-2.5 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="John Doe">
                        @error('newFullName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Phone Number') }}</label>
                        <input type="tel" wire:model="newPhone" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-2.5 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="08012345678">
                        @error('newPhone') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Email Address') }}</label>
                        <input type="email" wire:model="newEmail" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-2.5 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="agent@example.com">
                        @error('newEmail') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Business Name') }}</label>
                        <input type="text" wire:model="newBusinessName" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-2.5 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="e.g. John Ventures">
                        @error('newBusinessName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('LGA') }}</label>
                            <input type="text" wire:model="newLga" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-2.5 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="Ikeja">
                            @error('newLga') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('State') }}</label>
                            <input type="text" wire:model="newState" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-2.5 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="Lagos">
                            @error('newState') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="pt-2 flex gap-3">
                        <x-button variant="secondary" class="flex-1" wire:click="closeCreateModal">{{ __('Cancel') }}</x-button>
                        <x-button variant="primary" class="flex-1 bg-purple-600 hover:bg-purple-700" wire:click="createAgent" wire:target="createAgent" wire:loading.attr="disabled">
                            {{ __('Create Agent') }}
                        </x-button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- OTP Verification Modal --}}
    @if($showOtpModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
            <div class="fixed inset-0 bg-text-primary/40 backdrop-blur-sm"></div>
            <div class="relative bg-surface rounded-card shadow-elevation-4 w-full max-w-sm overflow-hidden z-10">
                @if($showOtpSuccess)
                    <div class="p-6 text-center space-y-4">
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto">
                            <x-lucide-check class="w-8 h-8 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-text-primary">{{ __('Agent Verified!') }}</h3>
                            <p class="text-sm text-text-secondary mt-1">{{ __('Share this PIN with your agent. It will NOT be shown again.') }}</p>
                        </div>
                        <div class="bg-background border border-border rounded-btn p-4">
                            <p class="text-xs text-text-secondary mb-1">{{ __('Agent Login PIN') }}</p>
                            <p class="text-3xl font-mono font-bold text-purple-600 tracking-[0.3em]">{{ $createdAgentPlainPin }}</p>
                        </div>
                        <x-button variant="primary" class="w-full bg-purple-600 hover:bg-purple-700" wire:click="closeOtpModal">
                            {{ __('Done') }}
                        </x-button>
                    </div>
                @else
                    <div class="p-4 border-b border-border flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                        <h3 class="font-bold text-text-primary">{{ __('Verify Agent Phone') }}</h3>
                        <button wire:click="closeOtpModal" class="text-text-secondary hover:text-text-primary transition-colors">
                            <x-lucide-x class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <p class="text-sm text-text-secondary text-center">
                            {{ __('An OTP has been sent to') }}
                            <span class="font-medium text-text-primary">{{ $pendingAgentPhone }}</span>
                            {{ __('to verify the phone number.') }}
                        </p>

                        <div class="text-center">
                            <p class="text-xs text-text-secondary mb-1">{{ __('Agent PIN (also sent via SMS)') }}</p>
                            <p class="text-2xl font-mono font-bold text-purple-600 tracking-widest">{{ $createdAgentPlainPin }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Enter 6-Digit OTP') }}</label>
                            <input type="text" wire:model="otpCode" maxlength="6"
                                   class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-3 text-center font-mono text-xl tracking-[0.5em] focus:ring-purple-600 focus:border-purple-600 outline-none"
                                   placeholder="000000">
                            @error('otpCode') <p class="text-sm text-danger mt-1 text-center">{{ $message }}</p> @enderror
                        </div>

                        <div class="pt-2 space-y-2">
                            <x-button variant="primary" class="w-full bg-purple-600 hover:bg-purple-700" wire:click="verifyOtp" wire:target="verifyOtp" wire:loading.attr="disabled">
                                {{ __('Verify & Activate') }}
                            </x-button>
                            <div class="text-center">
                                <button wire:click="resendOtp" class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                    {{ __('Resend OTP') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Link Existing Agent Modal --}}
    @if($showLinkModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
            <div class="fixed inset-0 bg-text-primary/40 backdrop-blur-sm" wire:click="closeLinkModal"></div>
            <div class="relative bg-surface rounded-card shadow-elevation-4 w-full max-w-sm overflow-hidden z-10">
                <div class="p-4 border-b border-border flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                    <h3 class="font-bold text-text-primary">{{ __('Link Existing Agent') }}</h3>
                    <button wire:click="closeLinkModal" class="text-text-secondary hover:text-text-primary transition-colors">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <p class="text-sm text-text-secondary">{{ __('Enter the phone number of an existing unaffiliated') }} {{ $siteSettings->site_name ?? 'PayEase' }} {{ __('agent to link them to your network.') }}</p>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Agent Phone Number') }}</label>
                        <input type="tel" wire:model="linkPhone" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-2.5 focus:ring-purple-600 focus:border-purple-600 outline-none" placeholder="08012345678">
                        @error('linkPhone') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-2 flex gap-3">
                        <x-button variant="secondary" class="flex-1" wire:click="closeLinkModal">{{ __('Cancel') }}</x-button>
                        <x-button variant="primary" class="flex-1 bg-purple-600 hover:bg-purple-700" wire:click="linkAgent" wire:target="linkAgent" wire:loading.attr="disabled">
                            {{ __('Link Agent') }}
                        </x-button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Suspend Confirmation Modal --}}
    @if($showSuspendModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
            <div class="fixed inset-0 bg-text-primary/40 backdrop-blur-sm" wire:click="closeSuspendModal"></div>
            <div class="relative bg-surface rounded-card shadow-elevation-4 w-full max-w-sm overflow-hidden z-10">
                <div class="p-4 border-b border-border flex justify-between items-center bg-amber-50 dark:bg-amber-900/20">
                    <h3 class="font-bold text-amber-800 dark:text-amber-200">{{ __('Suspend Agent') }}</h3>
                    <button wire:click="closeSuspendModal" class="text-text-secondary hover:text-text-primary transition-colors">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <x-lucide-alert-triangle class="w-5 h-5 text-amber-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-text-primary">{{ __('Restrict this agent?') }}</p>
                            <p class="text-sm text-text-secondary">{{ __('They will be unable to perform any activities.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-border flex justify-end gap-3 bg-background/40">
                    <x-button variant="secondary" wire:click="closeSuspendModal">{{ __('Cancel') }}</x-button>
                    <x-button variant="primary" class="bg-amber-600 hover:bg-amber-700" wire:click="executeSuspend" wire:target="executeSuspend" wire:loading.attr="disabled">
                        {{ __('Suspend Agent') }}
                    </x-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Request Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
            <div class="fixed inset-0 bg-text-primary/40 backdrop-blur-sm" wire:click="closeDeleteModal"></div>
            <div class="relative bg-surface rounded-card shadow-elevation-4 w-full max-w-sm overflow-hidden z-10">
                <div class="p-4 border-b border-border flex justify-between items-center bg-red-50 dark:bg-red-900/20">
                    <h3 class="font-bold text-red-800 dark:text-red-200">{{ __('Request Agent Deletion') }}</h3>
                    <button wire:click="closeDeleteModal" class="text-text-secondary hover:text-text-primary transition-colors">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <x-lucide-alert-triangle class="w-5 h-5 text-red-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-text-primary">{{ __('Super Admin Approval Required') }}</p>
                            <p class="text-sm text-text-secondary">{{ __('This request will be sent to a super admin for final approval.') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">{{ __('Reason for Deletion') }}</label>
                        <textarea wire:model="deleteReason" rows="3" class="block w-full rounded-btn border border-border bg-background text-text-primary px-4 py-2.5 focus:ring-red-600 focus:border-red-600 outline-none text-sm" placeholder="{{ __('Describe why this agent should be removed...') }}"></textarea>
                        @error('deleteReason') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="p-4 border-t border-border flex justify-end gap-3 bg-background/40">
                    <x-button variant="secondary" wire:click="closeDeleteModal">{{ __('Cancel') }}</x-button>
                    <x-button variant="danger" class="bg-red-600 hover:bg-red-700" wire:click="submitDeleteRequest" wire:target="submitDeleteRequest" wire:loading.attr="disabled">
                        {{ __('Submit Request') }}
                    </x-button>
                </div>
            </div>
        </div>
    @endif

</div>
