<?php

namespace App\Livewire\Admin;

use App\Contracts\SmsClientInterface;
use App\Mail\NotificationTestMail;
use App\Models\AuditLog;
use App\Services\BillPaymentSettingsService;
use App\Services\IdentityVerificationSettingsService;
use App\Services\MmoProviderSettingService;
use App\Services\SystemSettingService;
use App\Services\UssdSettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class Settings extends Component
{
    public string $smsDriver = 'termii';
    public string $termiiApiKey = '';
    public string $termiiSenderId = 'PayEase';
    public string $mailMailer = 'log';
    public string $mailScheme = '';
    public string $mailHost = '';
    public string $mailPort = '2525';
    public string $mailUsername = '';
    public string $mailPassword = '';
    public string $mailFromAddress = 'hello@example.com';
    public string $mailFromName = 'PayEase';
    public string $testSmsPhone = '';
    public string $testEmailAddress = '';
    public array $mmoProviders = [];
    public string $openMmoProvider = 'monnify';
    public string $youverifyApiKey = '';
    public string $youverifyEnvironment = 'sandbox';
    public string $premblyApiKey = '';
    public string $premblyAppId = '';
    public string $premblyEnvironment = 'sandbox';
    public string $testYouverifyConnectionResult = '';
    public string $testPremblyConnectionResult = '';
    public string $africasTalkingApiKey = '';
    public string $africasTalkingUsername = '';
    public string $africasTalkingServiceCode = '*347#';
    public string $africasTalkingEnvironment = 'sandbox';
    public string $testUssdConnectionResult = '';
    public string $vtpassApiKey = '';
    public string $vtpassUsername = '';
    public string $vtpassEnvironment = 'sandbox';
    public string $testBillPaymentConnectionResult = '';
    public bool $kycAutoVerify = false;

    public function mount(SystemSettingService $settingService, MmoProviderSettingService $mmoProviderSettingService, IdentityVerificationSettingsService $identitySettings, UssdSettingsService $ussdSettings, BillPaymentSettingsService $billPaymentSettings): void
    {
        abort_unless(Auth::user()?->hasRole('super_admin'), 403);

        $settings = $settingService->getNotificationSettings();

        $this->smsDriver = (string) $settings['sms_driver'];
        $this->termiiApiKey = (string) $settings['termii_api_key'];
        $this->termiiSenderId = (string) $settings['termii_sender_id'];
        $this->mailMailer = (string) $settings['mail_mailer'];
        $this->mailScheme = (string) $settings['mail_scheme'];
        $this->mailHost = (string) $settings['mail_host'];
        $this->mailPort = (string) $settings['mail_port'];
        $this->mailUsername = (string) $settings['mail_username'];
        $this->mailPassword = (string) $settings['mail_password'];
        $this->mailFromAddress = (string) $settings['mail_from_address'];
        $this->mailFromName = (string) $settings['mail_from_name'];

        $this->syncMmoProviders($mmoProviderSettingService);

        $identitySettings = $identitySettings->getSettings();
        $this->youverifyApiKey = (string) ($identitySettings['youverify_api_key'] ?? '');
        $this->youverifyEnvironment = (string) ($identitySettings['youverify_environment'] ?? 'sandbox');
        $this->premblyApiKey = (string) ($identitySettings['prembly_api_key'] ?? '');
        $this->premblyAppId = (string) ($identitySettings['prembly_app_id'] ?? '');
        $this->premblyEnvironment = (string) ($identitySettings['prembly_environment'] ?? 'sandbox');

        $ussd = $ussdSettings->getSettings();
        $this->africasTalkingApiKey = (string) ($ussd['africas_talking_api_key'] ?? '');
        $this->africasTalkingUsername = (string) ($ussd['africas_talking_username'] ?? '');
        $this->africasTalkingServiceCode = (string) ($ussd['africas_talking_service_code'] ?? '*347#');
        $this->africasTalkingEnvironment = (string) ($ussd['africas_talking_environment'] ?? 'sandbox');

        $this->kycAutoVerify = (bool) ($identitySettings['kyc_auto_verify'] ?? false);

        $bp = $billPaymentSettings->getSettings();
        $this->vtpassApiKey = (string) ($bp['vtpass_api_key'] ?? '');
        $this->vtpassUsername = (string) ($bp['vtpass_username'] ?? '');
        $this->vtpassEnvironment = (string) ($bp['vtpass_environment'] ?? 'sandbox');
    }

    public function saveSettings(SystemSettingService $settingService): void
    {
        $validated = $this->validate($this->rules());
        $oldSettings = $settingService->maskNotificationSettings($settingService->getNotificationSettings());
        $newSettings = $this->notificationSettingsPayload($validated);

        $settingService->saveNotificationSettings($newSettings);
        $settingService->applyNotificationConfig($newSettings);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'notification_settings_updated',
            'entity_type' => 'system_setting',
            'entity_id' => null,
            'old_values' => $oldSettings,
            'new_values' => $settingService->maskNotificationSettings($newSettings),
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->dispatch('notify-success', message: 'Notification settings saved successfully.');
    }

    public function sendTestSms(SystemSettingService $settingService): void
    {
        $this->validate(array_merge($this->smsRules(), [
            'testSmsPhone' => ['required', 'string', 'min:10', 'max:20'],
        ]));

        $settings = $this->notificationSettingsPayloadFromProperties();
        $settingService->applyNotificationConfig($settings);

        $result = app(SmsClientInterface::class)->send(
            $this->testSmsPhone,
            'PayEase test SMS from Super Admin settings. If you got this, SMS delivery is working.'
        );

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => ($result['status'] ?? 'failed') === 'sent' ? 'notification_sms_test_sent' : 'notification_sms_test_failed',
            'entity_type' => 'system_setting',
            'entity_id' => null,
            'old_values' => null,
            'new_values' => [
                'sms_driver' => $settings['sms_driver'],
                'phone_number' => $this->testSmsPhone,
                'provider_id' => $result['provider_id'] ?? null,
                'status' => $result['status'] ?? 'failed',
                'error' => $result['error'] ?? null,
            ],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        if (($result['status'] ?? 'failed') !== 'sent') {
            $message = (string) ($result['error'] ?? 'SMS test failed.');
            $this->addError('testSmsPhone', $message);
            $this->dispatch('notify-error', message: $message);
            return;
        }

        $this->dispatch('notify-success', message: 'SMS test sent successfully.');
    }

    public function sendTestEmail(SystemSettingService $settingService): void
    {
        $this->validate(array_merge($this->emailRules(), [
            'testEmailAddress' => ['required', 'email'],
        ]));

        $settings = $this->notificationSettingsPayloadFromProperties();
        $settingService->applyNotificationConfig($settings);

        try {
            Mail::to($this->testEmailAddress)->send(
                new NotificationTestMail((string) (Auth::user()?->full_name ?? 'Super Admin'))
            );
        } catch (Throwable $throwable) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'notification_email_test_failed',
                'entity_type' => 'system_setting',
                'entity_id' => null,
                'old_values' => null,
                'new_values' => [
                    'mail_mailer' => $settings['mail_mailer'],
                    'email' => $this->testEmailAddress,
                    'error' => $throwable->getMessage(),
                ],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->addError('testEmailAddress', $throwable->getMessage());
            $this->dispatch('notify-error', message: 'Email test failed.');
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'notification_email_test_sent',
            'entity_type' => 'system_setting',
            'entity_id' => null,
            'old_values' => null,
            'new_values' => [
                'mail_mailer' => $settings['mail_mailer'],
                'email' => $this->testEmailAddress,
                'status' => 'sent',
            ],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->dispatch('notify-success', message: 'Email test sent successfully.');
    }

    public function toggleMmoProvider(string $provider): void
    {
        $this->openMmoProvider = $this->openMmoProvider === $provider ? '' : $provider;
    }

    public function saveMmoProvider(string $provider): void
    {
        $mmoProviderSettingService = app(MmoProviderSettingService::class);
        $validated = $this->validateMmoProvider($provider, false);
        $current = $mmoProviderSettingService->getProviderSetting($provider);
        $oldValues = $this->maskedMmoAuditState($current->provider, (string) $current->environment, is_array($current->credentials) ? $current->credentials : [], $mmoProviderSettingService);

        $saved = $mmoProviderSettingService->saveProviderSettings(
            $provider,
            (string) $validated['environment'],
            (array) $validated['credentials'],
            (string) Auth::id(),
        );

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'mmo_provider_settings_updated',
            'entity_type' => 'mmo_provider_setting',
            'entity_id' => $saved->id,
            'old_values' => $oldValues,
            'new_values' => $this->maskedMmoAuditState($saved->provider, (string) $saved->environment, is_array($saved->credentials) ? $saved->credentials : [], $mmoProviderSettingService),
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->syncMmoProviders($mmoProviderSettingService);
        $this->dispatch('notify-success', message: ucfirst($provider) . ' settings saved.');
    }

    public function testMmoProvider(string $provider): void
    {
        $mmoProviderSettingService = app(MmoProviderSettingService::class);
        $validated = $this->validateMmoProvider($provider, $provider === 'monnify');

        try {
            $result = $mmoProviderSettingService->testConnection(
                $provider,
                (string) $validated['environment'],
                (array) $validated['credentials'],
            );
        } catch (Throwable $throwable) {
            $result = [
                'status' => 'failed',
                'message' => $throwable->getMessage(),
            ];
        }

        $saved = $mmoProviderSettingService->storeTestResult(
            $provider,
            (string) $validated['environment'],
            (array) $validated['credentials'],
            $result,
            (string) Auth::id(),
        );

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => ($result['status'] ?? 'failed') === 'success' ? 'mmo_provider_test_succeeded' : 'mmo_provider_test_failed',
            'entity_type' => 'mmo_provider_setting',
            'entity_id' => $saved->id,
            'old_values' => null,
            'new_values' => array_merge(
                $this->maskedMmoAuditState($saved->provider, (string) $saved->environment, is_array($saved->credentials) ? $saved->credentials : [], $mmoProviderSettingService),
                ['status' => $result['status'] ?? 'failed', 'message' => $result['message'] ?? null]
            ),
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->syncMmoProviders($mmoProviderSettingService);

        if (($result['status'] ?? 'failed') !== 'success') {
            $this->dispatch('notify-error', message: (string) ($result['message'] ?? 'Connection test failed.'));
            return;
        }

        $this->dispatch('notify-success', message: ucfirst($provider) . ' connection test succeeded.');
    }

    public function activateMmoProvider(string $provider): void
    {
        $mmoProviderSettingService = app(MmoProviderSettingService::class);
        $active = $mmoProviderSettingService->getActiveProviderSetting();
        $activated = $mmoProviderSettingService->activateProvider($provider, (string) Auth::id());

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'mmo_provider_activated',
            'entity_type' => 'mmo_provider_setting',
            'entity_id' => $activated->id,
            'old_values' => [
                'active_provider' => $active->provider,
            ],
            'new_values' => [
                'active_provider' => $activated->provider,
                'environment' => $activated->environment,
            ],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->syncMmoProviders($mmoProviderSettingService);
        $this->dispatch('notify-success', message: ucfirst($provider) . ' is now the active MMO provider.');
    }

    public function saveIdentityVerification(IdentityVerificationSettingsService $identitySettings): void
    {
        $validated = $this->validate($this->identityRules());
        $oldSettings = $identitySettings->maskSettings($identitySettings->getSettings());
        $newSettings = $this->identitySettingsPayload($validated);

        $identitySettings->saveSettings($newSettings);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'identity_verification_settings_updated',
            'entity_type' => 'system_setting',
            'entity_id' => null,
            'old_values' => $oldSettings,
            'new_values' => $identitySettings->maskSettings($newSettings),
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->dispatch('notify-success', message: 'Identity verification settings saved.');
    }

    public function toggleKycMode(IdentityVerificationSettingsService $identitySettings): void
    {
        $settings = $identitySettings->getSettings();
        $settings['kyc_auto_verify'] = $this->kycAutoVerify;
        $identitySettings->saveSettings($settings);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'kyc_mode_changed',
            'entity_type' => 'system_setting',
            'entity_id' => null,
            'old_values' => ['kyc_auto_verify' => !$this->kycAutoVerify],
            'new_values' => ['kyc_auto_verify' => $this->kycAutoVerify],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $mode = $this->kycAutoVerify ? 'Auto Verify' : 'Manual Review';
        $this->dispatch('notify-success', message: "KYC mode changed to {$mode}.");
    }

    public function testYouverifyConnection(IdentityVerificationSettingsService $identitySettings): void
    {
        $validated = $this->validate($this->youverifyRules());

        $result = $identitySettings->testYouverifyConnection(
            (string) $validated['youverifyApiKey'],
            (string) $validated['youverifyEnvironment'],
        );

        if (($result['status'] ?? 'failed') === 'success') {
            $this->dispatch('notify-success', message: $result['message']);
            $this->testYouverifyConnectionResult = '';
        } else {
            $this->testYouverifyConnectionResult = (string) ($result['message'] ?? 'Connection test failed.');
        }
    }

    public function testPremblyConnection(IdentityVerificationSettingsService $identitySettings): void
    {
        $validated = $this->validate($this->premblyRules());

        $result = $identitySettings->testPremblyConnection(
            (string) $validated['premblyApiKey'],
            (string) $validated['premblyAppId'],
            (string) $validated['premblyEnvironment'],
        );

        if (($result['status'] ?? 'failed') === 'success') {
            $this->dispatch('notify-success', message: $result['message']);
            $this->testPremblyConnectionResult = '';
        } else {
            $this->testPremblyConnectionResult = (string) ($result['message'] ?? 'Connection test failed.');
        }
    }

    public function saveUssdSettings(UssdSettingsService $ussdSettings): void
    {
        $validated = $this->validate($this->ussdRules());
        $oldSettings = $ussdSettings->maskSettings($ussdSettings->getSettings());
        $newSettings = $this->ussdSettingsPayload($validated);

        $ussdSettings->saveSettings($newSettings);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'ussd_gateway_settings_updated',
            'entity_type' => 'system_setting',
            'entity_id' => null,
            'old_values' => $oldSettings,
            'new_values' => $ussdSettings->maskSettings($newSettings),
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->dispatch('notify-success', message: 'USSD Gateway settings saved successfully.');
    }

    public function saveBillPaymentSettings(BillPaymentSettingsService $billPaymentSettings): void
    {
        $validated = $this->validate($this->billPaymentRules());
        $oldSettings = $billPaymentSettings->maskSettings($billPaymentSettings->getSettings());
        $newSettings = $this->billPaymentSettingsPayload($validated);

        $billPaymentSettings->saveSettings($newSettings);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'bill_payment_settings_updated',
            'entity_type' => 'system_setting',
            'entity_id' => null,
            'old_values' => $oldSettings,
            'new_values' => $billPaymentSettings->maskSettings($newSettings),
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->dispatch('notify-success', message: 'Bill Payment settings saved successfully.');
    }

    public function testBillPaymentConnection(BillPaymentSettingsService $billPaymentSettings): void
    {
        $validated = $this->validate($this->billPaymentRules());

        $billPaymentSettings->saveSettings($this->billPaymentSettingsPayload($validated));

        $result = $billPaymentSettings->testConnection();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => ($result['status'] ?? 'failed') === 'success' ? 'bill_payment_test_succeeded' : 'bill_payment_test_failed',
            'entity_type' => 'system_setting',
            'entity_id' => null,
            'old_values' => null,
            'new_values' => [
                'vtpass_environment' => $this->vtpassEnvironment,
                'result' => $result['message'] ?? 'Unknown',
            ],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        if (($result['status'] ?? 'failed') === 'success') {
            $this->dispatch('notify-success', message: $result['message']);
            $this->testBillPaymentConnectionResult = '';
        } else {
            $this->testBillPaymentConnectionResult = (string) ($result['message'] ?? 'Connection test failed.');
        }
    }

    public function testUssdConnection(UssdSettingsService $ussdSettings): void
    {
        $validated = $this->validate($this->ussdRules());

        $ussdSettings->saveSettings($this->ussdSettingsPayload($validated));

        $result = $ussdSettings->testConnection();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => ($result['status'] ?? 'failed') === 'success' ? 'ussd_gateway_test_succeeded' : 'ussd_gateway_test_failed',
            'entity_type' => 'system_setting',
            'entity_id' => null,
            'old_values' => null,
            'new_values' => [
                'africas_talking_environment' => $this->africasTalkingEnvironment,
                'result' => $result['message'] ?? 'Unknown',
            ],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        if (($result['status'] ?? 'failed') === 'success') {
            $this->dispatch('notify-success', message: $result['message']);
            $this->testUssdConnectionResult = '';
        } else {
            $this->testUssdConnectionResult = (string) ($result['message'] ?? 'Connection test failed.');
        }
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'isLocalEnvironment' => app()->environment('local'),
        ])->layout('components.layouts.admin');
    }

    protected function identityRules(): array
    {
        return array_merge($this->youverifyRules(), $this->premblyRules());
    }

    protected function youverifyRules(): array
    {
        return [
            'youverifyApiKey' => ['nullable', 'string', 'max:255'],
            'youverifyEnvironment' => ['required', Rule::in(['sandbox', 'live'])],
        ];
    }

    protected function premblyRules(): array
    {
        return [
            'premblyApiKey' => ['nullable', 'string', 'max:255'],
            'premblyAppId' => ['nullable', 'string', 'max:255'],
            'premblyEnvironment' => ['required', Rule::in(['sandbox', 'live'])],
        ];
    }

    protected function identitySettingsPayload(array $validated): array
    {
        return [
            'youverify_api_key' => trim((string) ($validated['youverifyApiKey'] ?? '')),
            'youverify_environment' => (string) $validated['youverifyEnvironment'],
            'prembly_api_key' => trim((string) ($validated['premblyApiKey'] ?? '')),
            'prembly_app_id' => trim((string) ($validated['premblyAppId'] ?? '')),
            'prembly_environment' => (string) $validated['premblyEnvironment'],
            'kyc_auto_verify' => (bool) $this->kycAutoVerify,
        ];
    }

    protected function rules(): array
    {
        return array_merge($this->smsRules(), $this->emailRules());
    }

    protected function smsRules(): array
    {
        return [
            'smsDriver' => ['required', Rule::in(['log', 'termii'])],
            'termiiApiKey' => ['nullable', 'string', 'max:255', 'required_if:smsDriver,termii'],
            'termiiSenderId' => ['required', 'string', 'max:20'],
        ];
    }

    protected function emailRules(): array
    {
        return [
            'mailMailer' => ['required', Rule::in(['log', 'smtp'])],
            'mailScheme' => ['nullable', Rule::in(['', 'tls', 'ssl'])],
            'mailHost' => ['nullable', 'string', 'max:255', 'required_if:mailMailer,smtp'],
            'mailPort' => ['required', 'integer', 'min:1', 'max:65535'],
            'mailUsername' => ['nullable', 'string', 'max:255'],
            'mailPassword' => ['nullable', 'string', 'max:255'],
            'mailFromAddress' => ['required', 'email'],
            'mailFromName' => ['required', 'string', 'max:255'],
        ];
    }

    protected function notificationSettingsPayload(array $validated): array
    {
        return [
            'sms_driver' => (string) $validated['smsDriver'],
            'termii_api_key' => trim((string) ($validated['termiiApiKey'] ?? '')),
            'termii_sender_id' => trim((string) $validated['termiiSenderId']),
            'mail_mailer' => (string) $validated['mailMailer'],
            'mail_scheme' => (string) ($validated['mailScheme'] ?? ''),
            'mail_host' => trim((string) ($validated['mailHost'] ?? '')),
            'mail_port' => (string) $validated['mailPort'],
            'mail_username' => trim((string) ($validated['mailUsername'] ?? '')),
            'mail_password' => (string) ($validated['mailPassword'] ?? ''),
            'mail_from_address' => trim((string) $validated['mailFromAddress']),
            'mail_from_name' => trim((string) $validated['mailFromName']),
        ];
    }

    protected function notificationSettingsPayloadFromProperties(): array
    {
        return [
            'sms_driver' => trim($this->smsDriver),
            'termii_api_key' => trim($this->termiiApiKey),
            'termii_sender_id' => trim($this->termiiSenderId),
            'mail_mailer' => trim($this->mailMailer),
            'mail_scheme' => trim($this->mailScheme),
            'mail_host' => trim($this->mailHost),
            'mail_port' => trim($this->mailPort),
            'mail_username' => trim($this->mailUsername),
            'mail_password' => $this->mailPassword,
            'mail_from_address' => trim($this->mailFromAddress),
            'mail_from_name' => trim($this->mailFromName),
        ];
    }

    protected function billPaymentRules(): array
    {
        return [
            'vtpassApiKey' => ['required', 'string', 'max:255'],
            'vtpassUsername' => ['required', 'string', 'max:255'],
            'vtpassEnvironment' => ['required', Rule::in(['sandbox', 'live'])],
        ];
    }

    protected function billPaymentSettingsPayload(array $validated): array
    {
        return [
            'vtpass_api_key' => trim((string) ($validated['vtpassApiKey'] ?? '')),
            'vtpass_username' => trim((string) ($validated['vtpassUsername'] ?? '')),
            'vtpass_environment' => (string) ($validated['vtpassEnvironment'] ?? 'sandbox'),
        ];
    }

    protected function ussdRules(): array
    {
        return [
            'africasTalkingApiKey' => ['required', 'string', 'max:255'],
            'africasTalkingUsername' => ['required', 'string', 'max:255'],
            'africasTalkingServiceCode' => ['required', 'string', 'max:20'],
            'africasTalkingEnvironment' => ['required', Rule::in(['sandbox', 'live'])],
        ];
    }

    protected function ussdSettingsPayload(array $validated): array
    {
        return [
            'africas_talking_api_key' => trim((string) ($validated['africasTalkingApiKey'] ?? '')),
            'africas_talking_username' => trim((string) ($validated['africasTalkingUsername'] ?? '')),
            'africas_talking_service_code' => trim((string) ($validated['africasTalkingServiceCode'] ?? '*347#')),
            'africas_talking_environment' => (string) ($validated['africasTalkingEnvironment'] ?? 'sandbox'),
        ];
    }

    protected function syncMmoProviders(MmoProviderSettingService $mmoProviderSettingService): void
    {
        $this->mmoProviders = $mmoProviderSettingService->getProviderSettings();

        if (!isset($this->mmoProviders[$this->openMmoProvider])) {
            $this->openMmoProvider = array_key_first($this->mmoProviders) ?? 'monnify';
        }
    }

    /**
     * @return array{environment:string,credentials:array<string, string>}
     */
    protected function validateMmoProvider(string $provider, bool $requireCredentials): array
    {
        $state = $this->mmoProviders[$provider] ?? [];
        $rules = [
            'environment' => ['required', Rule::in(['sandbox', 'live'])],
        ];

        foreach (($state['fields'] ?? []) as $field) {
            $rules['credentials.' . $field['key']] = $requireCredentials && $provider === 'monnify'
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return Validator::make([
            'environment' => $state['environment'] ?? 'sandbox',
            'credentials' => $state['credentials'] ?? [],
        ], $rules)->validate();
    }

    /**
     * @return array<string, mixed>
     */
    protected function maskedMmoAuditState(string $provider, string $environment, array $credentials, MmoProviderSettingService $mmoProviderSettingService): array
    {
        return [
            'provider' => $provider,
            'environment' => $environment,
            'credentials' => $mmoProviderSettingService->maskCredentialsForProvider($provider, $credentials),
            'webhook_url' => $mmoProviderSettingService->webhookUrlForProvider($provider),
        ];
    }
}
