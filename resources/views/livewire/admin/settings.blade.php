<div class="p-4 md:p-6 space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Super Admin Settings</h1>
            <p class="text-sm text-text-secondary">Manage SMS, email, and MMO partner credentials from one place.</p>
        </div>
        @if($isLocalEnvironment)
            <div class="rounded-2xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-text-primary">
                Local mode is active. SMS will use the driver selected below. Keep it on Log if you want to avoid real Termii charges.
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-text-primary">SMS Settings</h2>
                <p class="text-sm text-text-secondary mt-1">Switch between local log delivery and live Termii credentials.</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">SMS Driver</label>
                    <select wire:model="smsDriver" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="log">Log</option>
                        <option value="termii">Termii</option>
                    </select>
                    @error('smsDriver') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Termii API Key</label>
                    <input type="password" wire:model="termiiApiKey" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Paste TERMII_API_KEY">
                    @error('termiiApiKey') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Sender ID</label>
                    <input type="text" wire:model="termiiSenderId" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="PayEase">
                    @error('termiiSenderId') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-text-primary">Email Settings</h2>
                <p class="text-sm text-text-secondary mt-1">Configure SMTP or leave it on log mailer for local testing.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-text-primary mb-2">Mailer</label>
                    <select wire:model="mailMailer" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="log">Log</option>
                        <option value="smtp">SMTP</option>
                    </select>
                    @error('mailMailer') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">SMTP Host</label>
                    <input type="text" wire:model="mailHost" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="smtp.example.com">
                    @error('mailHost') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">SMTP Port</label>
                    <input type="number" wire:model="mailPort" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="587">
                    @error('mailPort') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Encryption</label>
                    <select wire:model="mailScheme" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">None</option>
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                    </select>
                    @error('mailScheme') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Username</label>
                    <input type="text" wire:model="mailUsername" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="SMTP username">
                    @error('mailUsername') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-text-primary mb-2">Password</label>
                    <input type="password" wire:model="mailPassword" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="SMTP password">
                    @error('mailPassword') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">From Address</label>
                    <input type="email" wire:model="mailFromAddress" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="no-reply@payease.ng">
                    @error('mailFromAddress') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">From Name</label>
                    <input type="text" wire:model="mailFromName" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="PayEase">
                    @error('mailFromName') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>
    </div>

    <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-text-primary">Test Notifications</h2>
                <p class="text-sm text-text-secondary mt-1">These tests use the values currently in the form, even before you save.</p>
            </div>
            <button type="button" wire:click="saveSettings" class="inline-flex items-center justify-center rounded-btn bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                Save Settings
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-border bg-background p-4 space-y-4">
                <div>
                    <h3 class="text-base font-semibold text-text-primary">Send Test SMS</h3>
                    <p class="text-sm text-text-secondary mt-1">Useful for checking Termii credentials or confirming local log behavior.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Recipient Phone Number</label>
                    <input type="text" wire:model="testSmsPhone" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="08012345678">
                    @error('testSmsPhone') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <button type="button" wire:click="sendTestSms" class="inline-flex items-center justify-center rounded-btn border border-border px-5 py-3 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">
                    Test SMS
                </button>
            </div>

            <div class="rounded-2xl border border-border bg-background p-4 space-y-4">
                <div>
                    <h3 class="text-base font-semibold text-text-primary">Send Test Email</h3>
                    <p class="text-sm text-text-secondary mt-1">Send a short verification email through the configured mailer.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Recipient Email Address</label>
                    <input type="email" wire:model="testEmailAddress" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="ops@payease.ng">
                    @error('testEmailAddress') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <button type="button" wire:click="sendTestEmail" class="inline-flex items-center justify-center rounded-btn border border-border px-5 py-3 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">
                    Test Email
                </button>
            </div>
        </div>
    </section>

    <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-text-primary">MMO Partners</h2>
            <p class="text-sm text-text-secondary mt-1">Switch providers at runtime, test credentials, and keep secret values masked after saving.</p>
        </div>

        <div class="space-y-4">
            @foreach($mmoProviders as $providerKey => $provider)
                @php
                    $statusColor = match($provider['last_test_status']) {
                        'success' => 'bg-success',
                        'failed' => 'bg-danger',
                        default => 'bg-gray-400',
                    };
                @endphp
                <div wire:key="mmo-provider-{{ $providerKey }}" class="rounded-2xl border border-border bg-background p-4 space-y-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-primary/10 text-sm font-bold text-primary">
                                    {{ strtoupper(substr($provider['name'], 0, 2)) }}
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-text-primary">{{ $provider['name'] }}</h3>
                                    <p class="text-sm text-text-secondary">
                                        {{ $provider['provider'] === 'monnify' ? 'Primary integration for reserved accounts and webhook collections.' : 'Backup provider placeholder pending verified API documentation.' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full px-3 py-1 {{ $provider['is_active'] ? 'bg-success/10 text-success' : 'bg-gray-100 text-text-secondary' }}">
                                    {{ $provider['is_active'] ? 'Active' : 'Backup' }}
                                </span>
                                <span class="rounded-full bg-primary/10 px-3 py-1 text-primary">
                                    {{ ucfirst($provider['environment']) }}
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-surface px-3 py-1 text-text-secondary">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $statusColor }}"></span>
                                    {{ ucfirst($provider['last_test_status']) }}
                                    @if($provider['last_tested_at'])
                                        <span class="text-text-secondary/80">· Tested {{ $provider['last_tested_at']->diffForHumans() }}</span>
                                    @endif
                                </span>
                            </div>

                            @if(!empty($provider['last_test_message']))
                                <p class="text-sm {{ $provider['last_test_status'] === 'failed' ? 'text-danger' : 'text-text-secondary' }}">
                                    {{ $provider['last_test_message'] }}
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="toggleMmoProvider('{{ $providerKey }}')" class="inline-flex items-center justify-center rounded-btn border border-border px-4 py-2.5 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">
                                {{ $openMmoProvider === $providerKey ? 'Hide' : 'Configure' }}
                            </button>
                            <button type="button" wire:click="testMmoProvider('{{ $providerKey }}')" class="inline-flex items-center justify-center rounded-btn border border-border px-4 py-2.5 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">
                                Test Connection
                            </button>
                            <button type="button" wire:click="activateMmoProvider('{{ $providerKey }}')" @disabled($provider['is_active'] || $provider['last_test_status'] !== 'success') class="inline-flex items-center justify-center rounded-btn bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">
                                Set as Active Provider
                            </button>
                        </div>
                    </div>

                    @if($openMmoProvider === $providerKey)
                        <div class="grid grid-cols-1 gap-4 border-t border-border pt-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-text-primary mb-2">Environment</label>
                                <select wire:model="mmoProviders.{{ $providerKey }}.environment" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                    <option value="sandbox">Sandbox</option>
                                    <option value="live">Live</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-text-primary mb-2">Webhook URL</label>
                                <input type="text" value="{{ $provider['webhook_url'] }}" readonly class="w-full rounded-btn border border-border bg-gray-50 px-4 py-3 text-sm text-text-secondary">
                            </div>

                            @foreach($provider['fields'] as $field)
                                <div @class(['md:col-span-2' => in_array($field['key'], ['secret_key', 'private_key'], true)])>
                                    <label class="block text-sm font-medium text-text-primary mb-2">{{ $field['label'] }}</label>
                                    <input
                                        type="{{ str_contains($field['key'], 'secret') || str_contains($field['key'], 'private') ? 'password' : 'text' }}"
                                        wire:model="mmoProviders.{{ $providerKey }}.credentials.{{ $field['key'] }}"
                                        class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                        placeholder="{{ $field['label'] }}"
                                    >
                                    @if(!empty($field['hint']))
                                        <p class="text-xs text-text-secondary mt-1">{{ $field['hint'] }}</p>
                                    @endif
                                </div>
                            @endforeach

                            <div class="md:col-span-2 flex flex-wrap gap-2">
                                <button type="button" wire:click="saveMmoProvider('{{ $providerKey }}')" class="inline-flex items-center justify-center rounded-btn border border-border px-5 py-3 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">
                                    Save {{ $provider['name'] }} Settings
                                </button>
                            </div>

                            @if($providerKey === 'monnify')
                                <div class="md:col-span-2 rounded-2xl border border-warning/30 bg-warning/10 p-4 text-sm text-text-primary space-y-2">
                                    <p><strong>Reserved accounts:</strong> Monnify customer funding is driven by reserved-account webhooks, not by calling a generic outbound credit endpoint.</p>
                                    <p><strong>Live payouts:</strong> OTP is required per transfer unless Monnify has granted a waiver and your payout IPs are whitelisted.</p>
                                    <p><strong>Wallet creation note:</strong> real customer reserved accounts require BVN or NIN. Customer email is also required by Monnify, so the current app data model still needs a production-grade email source before mass provisioning live wallets.</p>
                                </div>
                            @else
                                <div class="md:col-span-2 rounded-2xl border border-border bg-surface p-4 text-sm text-text-secondary">
                                    API calls are intentionally stubbed. This provider stays in placeholder mode until the official integration details are reviewed and confirmed.
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-text-primary">Identity Verification</h2>
                <p class="text-sm text-text-secondary mt-1">Configure Youverify (NIN / BVN) and Prembly (face match) providers for automated KYC verification.</p>
            </div>
            <button type="button" wire:click="saveIdentityVerification" class="inline-flex items-center justify-center rounded-btn bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                Save Identity Settings
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-border bg-background p-4 space-y-4">
                <h3 class="text-base font-semibold text-text-primary">Youverify</h3>
                <p class="text-sm text-text-secondary">NIN and BVN data verification.</p>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">API Key</label>
                    <input type="password" wire:model="youverifyApiKey" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Youverify API Key">
                    @error('youverifyApiKey') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Environment</label>
                    <select wire:model="youverifyEnvironment" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="sandbox">Sandbox</option>
                        <option value="live">Live</option>
                    </select>
                    @error('youverifyEnvironment') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <button type="button" wire:click="testYouverifyConnection" class="inline-flex items-center justify-center rounded-btn border border-border px-4 py-2.5 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">
                    Test Youverify Connection
                </button>

                @if($testYouverifyConnectionResult)
                    <p class="text-sm text-danger">{{ $testYouverifyConnectionResult }}</p>
                @endif
            </div>

            <div class="rounded-2xl border border-border bg-background p-4 space-y-4">
                <h3 class="text-base font-semibold text-text-primary">Prembly</h3>
                <p class="text-sm text-text-secondary">Biometric face match verification.</p>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">API Key</label>
                    <input type="password" wire:model="premblyApiKey" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Prembly API Key">
                    @error('premblyApiKey') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">App ID</label>
                    <input type="text" wire:model="premblyAppId" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Prembly App ID">
                    @error('premblyAppId') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">Environment</label>
                    <select wire:model="premblyEnvironment" class="w-full rounded-btn border border-border bg-surface px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="sandbox">Sandbox</option>
                        <option value="live">Live</option>
                    </select>
                    @error('premblyEnvironment') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <button type="button" wire:click="testPremblyConnection" class="inline-flex items-center justify-center rounded-btn border border-border px-4 py-2.5 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">
                    Test Prembly Connection
                </button>

                @if($testPremblyConnectionResult)
                    <p class="text-sm text-danger">{{ $testPremblyConnectionResult }}</p>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-border bg-background p-4 space-y-4">
            <div>
                <h3 class="text-base font-semibold text-text-primary">Verification Mode</h3>
                <p class="text-sm text-text-secondary">Choose how KYC submissions are processed. Auto mode uses provider APIs; Manual mode queues submissions for admin review.</p>
            </div>
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="kycAutoVerify" wire:change="toggleKycMode" class="sr-only peer">
                    <div class="w-11 h-6 bg-text-secondary peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
                <span class="text-sm font-medium text-text-primary">{{ $kycAutoVerify ? 'Auto Verify (Provider APIs)' : 'Manual Review Only' }}</span>
            </div>
        </div>
    </section>

    {{-- Bill Payment --}}
    <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-text-primary">Bill Payment</h2>
            <p class="text-sm text-text-secondary mt-1">Configure VTPass credentials for airtime, data, cable, and electricity purchases.</p>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">API Key</label>
                <input type="password" wire:model="vtpassApiKey" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="VTPass API Key">
                @error('vtpassApiKey') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">Username</label>
                <input type="text" wire:model="vtpassUsername" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="VTPass Username">
                @error('vtpassUsername') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">Environment</label>
                <select wire:model="vtpassEnvironment" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="sandbox">Sandbox</option>
                    <option value="live">Live</option>
                </select>
                @error('vtpassEnvironment') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="button" wire:click="saveBillPaymentSettings" class="inline-flex items-center justify-center rounded-btn bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-dark">
                    Save Bill Payment Settings
                </button>

                <button type="button" wire:click="testBillPaymentConnection" class="inline-flex items-center justify-center rounded-btn border border-border px-4 py-2.5 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">
                    Test Connection
                </button>
            </div>

            @if($testBillPaymentConnectionResult)
                <p class="text-sm text-danger">{{ $testBillPaymentConnectionResult }}</p>
            @endif
        </div>
    </section>

    {{-- USSD Gateway --}}
    <section class="rounded-card border border-border bg-surface p-5 shadow-soft space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-text-primary">USSD Gateway</h2>
            <p class="text-sm text-text-secondary mt-1">Configure Africa's Talking credentials for the USSD channel.</p>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">API Key</label>
                <input type="password" wire:model="africasTalkingApiKey" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Africa's Talking API Key">
                @error('africasTalkingApiKey') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">Username</label>
                <input type="text" wire:model="africasTalkingUsername" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Africa's Talking Username">
                @error('africasTalkingUsername') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">Service Code</label>
                <input type="text" wire:model="africasTalkingServiceCode" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="*347#">
                @error('africasTalkingServiceCode') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-2">Environment</label>
                <select wire:model="africasTalkingEnvironment" class="w-full rounded-btn border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="sandbox">Sandbox</option>
                    <option value="live">Live</option>
                </select>
                @error('africasTalkingEnvironment') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="button" wire:click="saveUssdSettings" class="inline-flex items-center justify-center rounded-btn bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-dark">
                    Save USSD Settings
                </button>

                <button type="button" wire:click="testUssdConnection" class="inline-flex items-center justify-center rounded-btn border border-border px-4 py-2.5 text-sm font-semibold text-text-primary transition hover:border-primary hover:text-primary">
                    Test Connection
                </button>
            </div>

            @if($testUssdConnectionResult)
                <p class="text-sm text-danger">{{ $testUssdConnectionResult }}</p>
            @endif
        </div>
    </section>
</div>
