<div class="p-4 md:p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary">Site Settings</h1>
        <p class="text-sm text-text-secondary mt-1">Manage your brand identity, contact information, and registration preferences.</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Branding --}}
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
            <h2 class="text-lg font-semibold text-text-primary">Branding</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Site Name</label>
                    <input type="text" wire:model="siteName" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="PayEase">
                    @error('siteName') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Tagline</label>
                    <input type="text" wire:model="siteTagline" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Banking the Unbanked">
                    @error('siteTagline') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-text-primary mb-2">Site Description</label>
                    <textarea wire:model="siteDescription" rows="3" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Brief description of your platform for SEO and social sharing."></textarea>
                    @error('siteDescription') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-text-primary mb-2">Logo</label>
                    <div class="mb-2 flex items-center gap-3" wire:loading.class="opacity-50" wire:target="logo">
                        @if($logo)
                            <div class="relative">
                                <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview" class="h-10 object-contain rounded border border-border">
                                <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-primary rounded-full flex items-center justify-center">
                                    <x-lucide-check class="w-2.5 h-2.5 text-white" />
                                </span>
                            </div>
                            <button type="button" wire:click="$set('logo', null)" class="text-xs text-danger hover:underline">Change</button>
                        @elseif($settingsModel->logoUrl())
                            <img src="{{ $settingsModel->logoUrl() }}" alt="Logo" class="h-10 object-contain rounded border border-border">
                            <button type="button" wire:click="removeLogo" class="text-xs text-danger hover:underline">Remove</button>
                        @endif
                    </div>
                    <input type="file" wire:model.live="logo" accept="image/png,image/svg+xml,image/jpeg,image/webp" class="w-full text-sm file:mr-3 file:rounded-btn file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20">
                    @error('logo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    <p class="text-xs text-text-secondary">PNG, SVG, JPG or WebP. Max 2MB.</p>
                </div>

                <div class="space-y-3">
                    <label class="block text-sm font-medium text-text-primary mb-2">Site Icon (App Icon)</label>
                    <div class="mb-2 flex items-center gap-3" wire:loading.class="opacity-50" wire:target="icon">
                        @if($icon)
                            <div class="relative">
                                <img src="{{ $icon->temporaryUrl() }}" alt="Icon preview" class="h-10 w-10 rounded object-cover border border-border">
                                <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-primary rounded-full flex items-center justify-center">
                                    <x-lucide-check class="w-2.5 h-2.5 text-white" />
                                </span>
                            </div>
                            <button type="button" wire:click="$set('icon', null)" class="text-xs text-danger hover:underline">Change</button>
                        @elseif($settingsModel->iconUrl())
                            <img src="{{ $settingsModel->iconUrl() }}" alt="Icon" class="h-10 w-10 rounded object-cover border border-border">
                            <button type="button" wire:click="removeIcon" class="text-xs text-danger hover:underline">Remove</button>
                        @endif
                    </div>
                    <input type="file" wire:model.live="icon" accept="image/png,image/svg+xml,image/jpeg,image/webp" class="w-full text-sm file:mr-3 file:rounded-btn file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20">
                    @error('icon') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    <p class="text-xs text-text-secondary">PNG, SVG, JPG or WebP. Max 1MB.</p>
                </div>

                <div class="space-y-3">
                    <label class="block text-sm font-medium text-text-primary mb-2">Favicon</label>
                    <div class="mb-2 flex items-center gap-3" wire:loading.class="opacity-50" wire:target="favicon">
                        @if($favicon)
                            <div class="relative">
                                <img src="{{ $favicon->temporaryUrl() }}" alt="Favicon preview" class="h-8 w-8 rounded object-cover border border-border">
                                <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-primary rounded-full flex items-center justify-center">
                                    <x-lucide-check class="w-2.5 h-2.5 text-white" />
                                </span>
                            </div>
                            <button type="button" wire:click="$set('favicon', null)" class="text-xs text-danger hover:underline">Change</button>
                        @elseif($settingsModel->faviconUrl())
                            <img src="{{ $settingsModel->faviconUrl() }}" alt="Favicon" class="h-8 w-8 rounded object-cover border border-border">
                            <button type="button" wire:click="removeFavicon" class="text-xs text-danger hover:underline">Remove</button>
                        @endif
                    </div>
                    <input type="file" wire:model.live="favicon" accept="image/png,image/svg+xml,image/x-icon" class="w-full text-sm file:mr-3 file:rounded-btn file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20">
                    @error('favicon') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    <p class="text-xs text-text-secondary">PNG, SVG or ICO. Max 512KB.</p>
                </div>
            </div>
        </section>

        {{-- Brand Colors --}}
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
            <h2 class="text-lg font-semibold text-text-primary">Brand Colors</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Primary Color</label>
                    <div class="flex gap-2">
                        <input type="color" wire:model="primaryColor" class="h-10 w-10 rounded cursor-pointer border border-border">
                        <input type="text" wire:model="primaryColor" class="flex-1 rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary font-mono" placeholder="#00A86B">
                    </div>
                    @error('primaryColor') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Secondary Color</label>
                    <div class="flex gap-2">
                        <input type="color" wire:model="secondaryColor" class="h-10 w-10 rounded cursor-pointer border border-border">
                        <input type="text" wire:model="secondaryColor" class="flex-1 rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary font-mono" placeholder="#0A0F1A">
                    </div>
                    @error('secondaryColor') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Accent Color</label>
                    <div class="flex gap-2">
                        <input type="color" wire:model="accentColor" class="h-10 w-10 rounded cursor-pointer border border-border">
                        <input type="text" wire:model="accentColor" class="flex-1 rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary font-mono" placeholder="Optional">
                    </div>
                    @error('accentColor') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Contact Information --}}
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
            <h2 class="text-lg font-semibold text-text-primary">Contact Information</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Support Email</label>
                    <input type="email" wire:model="supportEmail" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="support@payease.ng">
                    @error('supportEmail') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Support Phone</label>
                    <input type="text" wire:model="supportPhone" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="+234 800 123 4567">
                    @error('supportPhone') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">WhatsApp Number</label>
                    <input type="text" wire:model="supportWhatsapp" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="+234 800 123 4567">
                    @error('supportWhatsapp') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Timezone</label>
                    <select wire:model="timezone" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="Africa/Lagos">Africa/Lagos (UTC+1)</option>
                        <option value="Africa/Abuja">Africa/Abuja</option>
                        <option value="Africa/Cairo">Africa/Cairo (UTC+2)</option>
                        <option value="Africa/Nairobi">Africa/Nairobi (UTC+3)</option>
                        <option value="UTC">UTC</option>
                    </select>
                    @error('timezone') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-text-primary mb-2">Address Line 1</label>
                    <input type="text" wire:model="addressLine1" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="123 Example Street">
                    @error('addressLine1') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Address Line 2</label>
                    <input type="text" wire:model="addressLine2" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Suite 200">
                    @error('addressLine2') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">City</label>
                    <input type="text" wire:model="city" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Lagos">
                    @error('city') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">State</label>
                    <input type="text" wire:model="state" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Lagos">
                    @error('state') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Country</label>
                    <input type="text" wire:model="country" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nigeria">
                    @error('country') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Locale</label>
                    <select wire:model="locale" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="en">English</option>
                        <option value="ha">Hausa</option>
                        <option value="yo">Yoruba</option>
                        <option value="ig">Igbo</option>
                        <option value="fr">French</option>
                    </select>
                    @error('locale') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Social Links --}}
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-text-primary">Social Links</h2>
                    <p class="text-sm text-text-secondary mt-1">Links to your social media profiles.</p>
                </div>
                <button type="button" wire:click="addSocialLink" class="inline-flex items-center justify-center rounded-btn border border-border px-4 py-2.5 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">
                    + Add Link
                </button>
            </div>

            <div class="space-y-3">
                @forelse($socialLinks as $index => $link)
                    <div wire:key="social-{{ $index }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-border bg-background p-3">
                        <div class="flex-1 min-w-[120px]">
                            <label class="block text-xs font-medium text-text-secondary mb-1">Platform</label>
                            <select wire:model="socialLinks.{{ $index }}.platform" class="w-full rounded-btn border border-border bg-surface px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                <option value="">Select...</option>
                                <option value="facebook">Facebook</option>
                                <option value="twitter">Twitter / X</option>
                                <option value="instagram">Instagram</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="youtube">YouTube</option>
                                <option value="tiktok">TikTok</option>
                                <option value="telegram">Telegram</option>
                            </select>
                        </div>
                        <div class="flex-[2] min-w-[200px]">
                            <label class="block text-xs font-medium text-text-secondary mb-1">URL</label>
                            <input type="url" wire:model="socialLinks.{{ $index }}.url" class="w-full rounded-btn border border-border bg-surface px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="https://facebook.com/payease">
                        </div>
                        <button type="button" wire:click="removeSocialLink({{ $index }})" class="p-2 text-text-secondary hover:text-danger transition-colors">
                            <x-lucide-trash-2 class="w-4 h-4" />
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-text-secondary italic">No social links added yet.</p>
                @endforelse
            </div>
        </section>

        {{-- Registration Settings --}}
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
            <h2 class="text-lg font-semibold text-text-primary">Registration Settings</h2>

            <div class="space-y-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="registrationEnabled" class="rounded border-border text-primary focus:ring-primary" value="1">
                    <span class="text-sm font-medium text-text-primary">Enable new user registrations</span>
                </label>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Default User Role</label>
                    <select wire:model="defaultUserRole" class="w-full max-w-xs rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="customer">Customer</option>
                        <option value="agent">Agent</option>
                    </select>
                    @error('defaultUserRole') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Required Registration Fields</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach(['full_name', 'email', 'phone', 'pin', 'bvn', 'nin', 'address', 'date_of_birth', 'gender'] as $field)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="registrationRequiredFields" value="{{ $field }}"
                                    class="rounded border-border text-primary focus:ring-primary">
                                <span class="text-sm capitalize">{{ str_replace('_', ' ', $field) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="emailVerificationRequired" class="rounded border-border text-primary focus:ring-primary" value="1">
                        <span class="text-sm font-medium text-text-primary">Require email verification</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="phoneVerificationRequired" class="rounded border-border text-primary focus:ring-primary" value="1">
                        <span class="text-sm font-medium text-text-primary">Require phone verification</span>
                    </label>
                </div>
            </div>
        </section>

        {{-- reCAPTCHA --}}
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
            <h2 class="text-lg font-semibold text-text-primary">reCAPTCHA</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Site Key</label>
                    <input type="text" wire:model="recaptchaSiteKey" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="reCAPTCHA site key">
                    @error('recaptchaSiteKey') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Secret Key</label>
                    <input type="password" wire:model="recaptchaSecretKey" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="reCAPTCHA secret key">
                    @error('recaptchaSecretKey') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Custom Code --}}
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
            <h2 class="text-lg font-semibold text-text-primary">Custom Code</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Custom Head HTML</label>
                    <textarea wire:model="customHeadHtml" rows="6" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm font-mono focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="&lt;meta name=&quot;google-site-verification&quot; content=&quot;...&quot;&gt;"></textarea>
                    @error('customHeadHtml') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Custom Footer HTML</label>
                    <textarea wire:model="customFooterHtml" rows="6" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm font-mono focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="&lt;!-- Google Analytics --&gt;"></textarea>
                    @error('customFooterHtml') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Save --}}
        <div class="sticky bottom-0 -mx-4 md:-mx-6 px-4 md:px-6 py-4 bg-surface border-t border-border">
            <button type="submit" class="inline-flex items-center justify-center rounded-btn bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                Save Site Settings
            </button>
        </div>
    </form>
</div>
