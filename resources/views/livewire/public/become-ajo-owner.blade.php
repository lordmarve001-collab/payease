<div class="min-h-screen bg-background">
    <div class="max-w-3xl mx-auto px-4 py-8">
        <!-- Back Link / Brand -->
        <a href="/" class="inline-flex items-center gap-2 text-text-secondary hover:text-text-primary mb-6 transition-colors">
            <x-lucide-arrow-left class="w-5 h-5" />
            <span class="text-sm font-medium">Back to {{ $siteSettings->site_name ?? 'PayEase' }}</span>
        </a>

        @if($showMarketing)
            <!-- Marketing Page -->
            <div class="text-center space-y-6 pt-8">
                <div class="mx-auto w-20 h-20 rounded-full bg-primary-light flex items-center justify-center">
                    <x-lucide-users-2 class="w-10 h-10 text-primary" />
                </div>
                <h1 class="text-3xl font-bold text-text-primary">Become an Ajo Owner</h1>
                <p class="text-lg text-text-secondary max-w-xl mx-auto">
                    Run your own Ajo groups, recruit agents to manage collections, and earn from group management fees. 
                    The same trusted model Nigerians have used for generations — now digital, secure, and scalable.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left mt-8">
                    <div class="bg-surface rounded-card border border-border p-6 space-y-3">
                        <div class="w-12 h-12 rounded-full bg-primary-light flex items-center justify-center">
                            <x-lucide-hand-coins class="w-6 h-6 text-primary" />
                        </div>
                        <h3 class="font-semibold text-text-primary">Manage Groups</h3>
                        <p class="text-sm text-text-secondary">Create and manage multiple Ajo savings groups with custom cycle rules and contribution amounts.</p>
                    </div>
                    <div class="bg-surface rounded-card border border-border p-6 space-y-3">
                        <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center">
                            <x-lucide-briefcase class="w-6 h-6 text-secondary" />
                        </div>
                        <h3 class="font-semibold text-text-primary">Recruit Agents</h3>
                        <p class="text-sm text-text-secondary">Assign agents to manage daily collections in your groups — or handle collections yourself.</p>
                    </div>
                    <div class="bg-surface rounded-card border border-border p-6 space-y-3">
                        <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                            <x-lucide-trending-up class="w-6 h-6 text-amber-600" />
                        </div>
                        <h3 class="font-semibold text-text-primary">Earn Fees</h3>
                        <p class="text-sm text-text-secondary">Earn management fees from every successful group cycle. Build a sustainable community savings business.</p>
                    </div>
                </div>

                <div class="bg-surface rounded-card border border-border p-6 mt-8 text-left space-y-3">
                    <h3 class="font-semibold text-text-primary">Requirements</h3>
                    <ul class="space-y-2 text-sm text-text-secondary">
                        <li class="flex items-start gap-2">
                            <x-lucide-check class="w-4 h-4 text-primary mt-0.5 shrink-0" />
                            <span>Complete <strong>Tier 2</strong> identity verification (NIN + BVN verification)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-lucide-check class="w-4 h-4 text-primary mt-0.5 shrink-0" />
                            <span>Provide your business details and planned group structure</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-lucide-check class="w-4 h-4 text-primary mt-0.5 shrink-0" />
                            <span>Application reviewed by {{ $siteSettings->site_name ?? 'PayEase' }} admin — typically within 24 hours</span>
                        </li>
                    </ul>
                </div>

                <button wire:click="startApplication"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-8 py-4 text-base font-semibold text-white hover:bg-primary-dark transition-colors active:scale-95 min-h-[52px]">
                    Apply Now
                </button>
            </div>
        @else
            <!-- Existing application check -->
            @if(isset($existing) && $existing?->status === 'pending')
                <div class="bg-surface rounded-card border border-border p-8 text-center space-y-4 mt-8">
                    <div class="mx-auto w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
                        <x-lucide-clock class="w-8 h-8 text-amber-600" />
                    </div>
                    <h2 class="text-xl font-semibold text-text-primary">Application Under Review</h2>
                    <p class="text-text-secondary">Your Ajo Owner application is being reviewed. We'll notify you once a decision is made — typically within 24 hours.</p>
                </div>
            @elseif(isset($existing) && $existing?->status === 'rejected')
                <div class="bg-surface rounded-card border border-border p-8 space-y-4 mt-8">
                    <div class="text-center space-y-3">
                        <div class="mx-auto w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                            <x-lucide-x-circle class="w-8 h-8 text-danger" />
                        </div>
                        <h2 class="text-xl font-semibold text-text-primary">Application Not Approved</h2>
                        @if($existing->rejection_reason)
                            <p class="text-text-secondary bg-red-50 rounded-card p-4 border border-red-100">
                                <strong>Reason:</strong> {{ $existing->rejection_reason }}
                            </p>
                        @endif
                        <p class="text-text-secondary">You may submit a new application with updated information.</p>
                    </div>
                    <button wire:click="startApplication"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-primary px-8 py-4 text-base font-semibold text-white hover:bg-primary-dark transition-colors active:scale-95 min-h-[52px]">
                        Reapply
                    </button>
                </div>
            @elseif((int) ($user?->kyc_level ?? 0) < 2)
                <!-- Tier Gate -->
                <div class="bg-surface rounded-card border border-border p-8 text-center space-y-4 mt-8">
                    <div class="mx-auto w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
                        <x-lucide-shield-alert class="w-8 h-8 text-amber-600" />
                    </div>
                    <h2 class="text-xl font-semibold text-text-primary">Tier 2 Verification Required</h2>
                    <p class="text-text-secondary">You need to complete Tier 2 identity verification (NIN + BVN) before applying to become an Ajo Owner.</p>
                    <a href="{{ route('customer.kyc-upgrade') }}" wire:navigate
                       class="inline-flex items-center justify-center rounded-xl bg-primary px-8 py-4 text-base font-semibold text-white hover:bg-primary-dark transition-colors active:scale-95 min-h-[52px]">
                        Complete Tier 2 Verification
                    </a>
                </div>
            @else
                <!-- Application Form -->
                <div class="space-y-6 mt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-text-primary">Ajo Owner Application</h1>
                            <p class="text-text-secondary text-sm mt-1">Step {{ $step }} of 4</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            @for($i = 1; $i <= 4; $i++)
                                <div class="w-8 h-1.5 rounded-full transition-colors {{ $i <= $step ? 'bg-primary' : 'bg-border' }}"></div>
                            @endfor
                        </div>
                    </div>

                    @error('submit')
                        <div class="bg-red-50 border border-red-200 rounded-card p-4 text-sm text-danger">
                            {{ $message }}
                        </div>
                    @enderror

                    <!-- Step 1: Business Details -->
                    @if($step === 1)
                    <div class="bg-surface rounded-card border border-border p-6 space-y-4">
                        <h3 class="font-semibold text-text-primary">Business Details</h3>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">Business / Operation Name</label>
                            <input type="text" wire:model="businessName" maxlength="255"
                                   class="w-full rounded-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors"
                                   placeholder="e.g. Lagos Market Ajo">
                            @error('businessName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">Business Description</label>
                            <textarea wire:model="businessDescription" rows="3" maxlength="2000"
                                      class="w-full rounded-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors"
                                      placeholder="What community or market do you plan to serve? e.g. Market traders at Ile-Epo market"></textarea>
                            @error('businessDescription') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">Business Address</label>
                            <input type="text" wire:model="businessAddress" maxlength="500"
                                   class="w-full rounded-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors"
                                   placeholder="Business/operation address">
                            @error('businessAddress') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-text-primary mb-2">LGA</label>
                                <input type="text" wire:model="lga" maxlength="100"
                                       class="w-full rounded-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors"
                                       placeholder="Local Government Area">
                                @error('lga') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text-primary mb-2">State</label>
                                <input type="text" wire:model="state" maxlength="50"
                                       class="w-full rounded-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors"
                                       placeholder="State">
                                @error('state') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Step 2: Experience & Plan -->
                    @if($step === 2)
                    <div class="bg-surface rounded-card border border-border p-6 space-y-4">
                        <h3 class="font-semibold text-text-primary">Experience & Plan</h3>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-3">Do you currently run informal Ajo/Esusu groups?</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="hasExperience" :value="true"
                                           class="w-5 h-5 text-primary border-border focus:ring-primary">
                                    <span class="text-sm text-text-primary">Yes</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="hasExperience" :value="false"
                                           class="w-5 h-5 text-primary border-border focus:ring-primary">
                                    <span class="text-sm text-text-primary">No</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">How many groups do you plan to start with?</label>
                            <input type="number" wire:model="plannedGroups" min="1" max="100" inputmode="numeric"
                                   class="w-full rounded-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors"
                                   placeholder="1">
                            @error('plannedGroups') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">Approximate members per group</label>
                            <input type="number" wire:model="membersPerGroup" min="1" max="10000" inputmode="numeric"
                                   class="w-full rounded-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors"
                                   placeholder="e.g. 10">
                            @error('membersPerGroup') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">Agent Preference</label>
                            <select wire:model="agentAssignmentPreference"
                                    class="w-full rounded-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors">
                                <option value="">Select an option</option>
                                <option value="have_agents">I already have agents in mind</option>
                                <option value="needs_help">I would like {{ $siteSettings->site_name ?? 'PayEase' }} to help assign agents</option>
                                <option value="not_sure">Not sure yet</option>
                            </select>
                            @error('agentAssignmentPreference') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @endif

                    <!-- Step 3: Verification & Reference -->
                    @if($step === 3)
                    <div class="bg-surface rounded-card border border-border p-6 space-y-4">
                        <h3 class="font-semibold text-text-primary">Verification & Reference</h3>

                        <div class="bg-primary-light/50 rounded-card p-4 border border-primary/20 text-sm text-text-primary">
                            <div class="flex items-start gap-3">
                                <x-lucide-shield-check class="w-5 h-5 text-primary mt-0.5 shrink-0" />
                                <div>
                                    <p class="font-medium">Your Tier 2 KYC documents on file will be referenced for this application.</p>
                                    <p class="text-text-secondary mt-1">No need to re-upload identification documents.</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <label class="block text-sm font-medium text-text-primary mb-2">Reference Contact Name (optional)</label>
                            <input type="text" wire:model="referenceContactName" maxlength="255"
                                   class="w-full rounded-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors"
                                   placeholder="Full name">
                            @error('referenceContactName') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">Reference Contact Phone (optional)</label>
                            <div class="flex">
                                <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-border bg-gray-50 text-text-secondary text-sm font-medium">+234</span>
                                <input type="tel" wire:model="referenceContactPhone" maxlength="10" inputmode="numeric"
                                       class="flex-1 rounded-r-xl border border-border bg-background px-4 py-3 text-base outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-colors"
                                       placeholder="8012345678">
                            </div>
                            @error('referenceContactPhone') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @endif

                    <!-- Step 4: Review & Submit -->
                    @if($step === 4)
                    <div class="bg-surface rounded-card border border-border p-6 space-y-4">
                        <h3 class="font-semibold text-text-primary">Review & Submit</h3>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between py-2 border-b border-border">
                                <span class="text-text-secondary">Business Name</span>
                                <span class="font-medium text-text-primary">{{ $businessName }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-border">
                                <span class="text-text-secondary">Description</span>
                                <span class="font-medium text-text-primary text-right max-w-[60%]">{{ Str::limit($businessDescription, 80) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-border">
                                <span class="text-text-secondary">Location</span>
                                <span class="font-medium text-text-primary">{{ $lga }}, {{ $state }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-border">
                                <span class="text-text-secondary">Planned Groups</span>
                                <span class="font-medium text-text-primary">{{ $plannedGroups }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-border">
                                <span class="text-text-secondary">Members per Group</span>
                                <span class="font-medium text-text-primary">{{ $membersPerGroup }}</span>
                            </div>
                            @if($referenceContactName)
                            <div class="flex justify-between py-2 border-b border-border">
                                <span class="text-text-secondary">Reference</span>
                                <span class="font-medium text-text-primary">{{ $referenceContactName }}</span>
                            </div>
                            @endif
                        </div>

                        <label class="flex items-start gap-3 cursor-pointer pt-2">
                            <input type="checkbox" wire:model="agreeTerms"
                                   class="w-5 h-5 mt-0.5 text-primary rounded border-border focus:ring-primary">
                            <span class="text-sm text-text-primary leading-relaxed">
                                I agree to the <a href="#" class="text-primary underline">Ajo Owner Terms of Service</a> and confirm that all information provided is accurate.
                            </span>
                        </label>
                        @error('agreeTerms') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
                    </div>
                    @endif

                    <!-- Step 5: Success -->
                    @if($step === 5)
                    <div class="bg-surface rounded-card border border-border p-8 text-center space-y-4">
                        <div class="mx-auto w-20 h-20 rounded-full bg-primary-light flex items-center justify-center">
                            <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" class="animate-[draw-in_0.5s_ease-out]"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-text-primary">Application Submitted!</h2>
                        <p class="text-text-secondary">Your {{ $siteSettings->site_name ?? 'PayEase' }} Ajo Owner application has been received and is under review. We'll send you an SMS once a decision is made.</p>
                        <a href="{{ route('customer.dashboard') }}" wire:navigate
                           class="inline-flex items-center justify-center rounded-xl bg-primary px-8 py-4 text-base font-semibold text-white hover:bg-primary-dark transition-colors active:scale-95 min-h-[52px] w-full sm:w-auto">
                            Back to Dashboard
                        </a>
                    </div>
                    @endif

                    <!-- Navigation Buttons (steps 1-4) -->
                    @if($step >= 1 && $step <= 4)
                    <div class="flex gap-3">
                        @if($step > 1)
                            <button wire:click="previousStep"
                                    class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-xl border-2 border-primary px-6 py-3 text-base font-semibold text-primary hover:bg-primary-light transition-colors active:scale-95 min-h-[52px]">
                                Back
                            </button>
                        @endif
                        @if($step < 4)
                            <button wire:click="nextStep"
                                    class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 text-base font-semibold text-white hover:bg-primary-dark transition-colors active:scale-95 min-h-[52px]">
                                Continue
                            </button>
                        @elseif($step === 4)
                            <button wire:click="submit"
                                    class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 text-base font-semibold text-white hover:bg-primary-dark transition-colors active:scale-95 min-h-[52px]">
                                Submit Application
                            </button>
                        @endif
                    </div>
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>
