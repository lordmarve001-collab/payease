<?php

namespace App\Services;

use App\Contracts\MmoClientInterface;
use App\Models\MmoProviderSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MmoProviderSettingService
{
    /**
     * @return array<string, array{name:string,fields:array<int, array<string, string>>,supports_test:bool}>
     */
    public function providerDefinitions(): array
    {
        return [
            'monnify' => [
                'name' => 'Monnify',
                'supports_test' => true,
                'fields' => [
                    ['key' => 'api_key', 'label' => 'API Key'],
                    ['key' => 'secret_key', 'label' => 'Secret Key'],
                    ['key' => 'contract_code', 'label' => 'Contract Code'],
                    ['key' => 'wallet_id', 'label' => 'Disbursement Wallet ID', 'hint' => 'Your Monnify disbursement wallet account number from Settings > Disbursement'],
                ],
            ],
            'opay' => [
                'name' => 'OPay',
                'supports_test' => false,
                'fields' => [
                    ['key' => 'merchant_id', 'label' => 'Merchant ID'],
                    ['key' => 'public_key', 'label' => 'Public Key'],
                    ['key' => 'secret_key', 'label' => 'Secret Key'],
                ],
            ],
            'palmpay' => [
                'name' => 'PalmPay',
                'supports_test' => false,
                'fields' => [
                    ['key' => 'app_id', 'label' => 'App ID'],
                    ['key' => 'private_key', 'label' => 'Private Key / Secret'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getProviderSettings(): array
    {
        if (!Schema::hasTable('mmo_provider_settings')) {
            return [];
        }

        $this->ensureDefaults();

        return MmoProviderSetting::query()
            ->orderByRaw("case provider when 'monnify' then 1 when 'opay' then 2 when 'palmpay' then 3 else 99 end")
            ->get()
            ->mapWithKeys(function (MmoProviderSetting $setting): array {
                $definition = $this->providerDefinitions()[$setting->provider] ?? ['name' => strtoupper($setting->provider), 'fields' => [], 'supports_test' => false];
                $rawCredentials = is_array($setting->credentials) ? $setting->credentials : [];

                return [
                    $setting->provider => [
                        'id' => $setting->id,
                        'provider' => $setting->provider,
                        'name' => $definition['name'],
                        'is_active' => $setting->is_active,
                        'environment' => $setting->environment,
                        'last_tested_at' => $setting->last_tested_at,
                        'last_test_status' => $setting->last_test_status,
                        'last_test_message' => $setting->last_test_message,
                        'credentials' => $this->maskCredentialsForProvider($setting->provider, $rawCredentials),
                        'fields' => $definition['fields'],
                        'supports_test' => $definition['supports_test'],
                        'webhook_url' => $this->webhookUrlForProvider($setting->provider),
                    ],
                ];
            })
            ->all();
    }

    public function getProviderSetting(string $provider): MmoProviderSetting
    {
        $this->ensureDefaults();

        return MmoProviderSetting::query()
            ->where('provider', $provider)
            ->firstOrFail();
    }

    public function getActiveProviderSetting(): MmoProviderSetting
    {
        $this->ensureDefaults();

        return MmoProviderSetting::query()
            ->where('is_active', true)
            ->firstOr(function (): MmoProviderSetting {
                return $this->getProviderSetting('monnify');
            });
    }

    public function resolveActiveClient(): MmoClientInterface
    {
        $setting = $this->getActiveProviderSetting();
        $credentials = is_array($setting->credentials) ? $setting->credentials : [];

        if (!$this->providerIsReady($setting->provider, $credentials, (string) $setting->last_test_status)) {
            return new MockMmoClient();
        }

        return $this->makeClient(
            $setting->provider,
            $credentials,
            (string) $setting->environment,
        );
    }

    public function saveProviderSettings(string $provider, string $environment, array $credentials, ?string $updatedBy = null): MmoProviderSetting
    {
        $setting = $this->getProviderSetting($provider);
        $mergedCredentials = $this->mergeCredentials($provider, is_array($setting->credentials) ? $setting->credentials : [], $credentials);

        $setting->update([
            'environment' => $environment,
            'credentials' => $mergedCredentials,
            'updated_by' => $updatedBy,
        ]);

        return $setting->fresh();
    }

    /**
     * @return array{status:string,message:string,access_token?:string,expires_in?:mixed}
     */
    public function testConnection(string $provider, string $environment, array $credentials): array
    {
        if ($provider !== 'monnify') {
            return [
                'status' => 'not_configured',
                'message' => 'Not yet configured.',
            ];
        }

        $result = (new MonnifyClient($credentials, $environment))->testConnection();

        return [
            'status' => 'success',
            'message' => (string) ($result['message'] ?? 'Authentication successful.'),
            'access_token' => substr((string) ($result['access_token'] ?? ''), 0, 16) . '...',
            'expires_in' => $result['expires_in'] ?? null,
        ];
    }

    public function storeTestResult(string $provider, string $environment, array $credentials, array $result, ?string $updatedBy = null): MmoProviderSetting
    {
        $setting = $this->saveProviderSettings($provider, $environment, $credentials, $updatedBy);

        $setting->update([
            'last_tested_at' => now(),
            'last_test_status' => match ($result['status'] ?? 'failed') {
                'success' => 'success',
                'not_configured' => 'untested',
                default => 'failed',
            },
            'last_test_message' => (string) ($result['message'] ?? ''),
            'updated_by' => $updatedBy,
        ]);

        return $setting->fresh();
    }

    public function activateProvider(string $provider, ?string $updatedBy = null): MmoProviderSetting
    {
        $setting = $this->getProviderSetting($provider);

        if ($setting->provider !== 'monnify' && $setting->last_test_status !== 'success') {
            throw new RuntimeException('Only providers with a successful connection test can be activated.');
        }

        if ($setting->provider === 'monnify' && $setting->last_test_status !== 'success') {
            throw new RuntimeException('Run a successful Monnify connection test before making it active.');
        }

        DB::transaction(function () use ($setting, $updatedBy): void {
            MmoProviderSetting::query()->update(['is_active' => false]);

            MmoProviderSetting::query()
                ->whereKey($setting->id)
                ->update([
                    'is_active' => true,
                    'updated_by' => $updatedBy,
                    'updated_at' => now(),
                ]);
        });

        return $this->getProviderSetting($provider);
    }

    public function webhookUrlForProvider(string $provider): ?string
    {
        return match ($provider) {
            'monnify' => url('/webhooks/monnify'),
            'opay' => url('/webhooks/opay'),
            'palmpay' => url('/webhooks/palmpay'),
            default => null,
        };
    }

    /**
     * @return array<string, string|null>
     */
    public function maskCredentialsForProvider(string $provider, array $credentials): array
    {
        $masked = [];

        foreach ($this->providerDefinitions()[$provider]['fields'] ?? [] as $field) {
            $key = $field['key'];
            $masked[$key] = $this->maskSecret((string) ($credentials[$key] ?? ''));
        }

        return $masked;
    }

    public function makeClient(string $provider, array $credentials, string $environment): MmoClientInterface
    {
        return match ($provider) {
            'monnify' => new MonnifyClient($credentials, $environment),
            'opay' => new OpayClient($credentials, $environment),
            'palmpay' => new PalmPayClient($credentials, $environment),
            default => new MockMmoClient(),
        };
    }

    protected function ensureDefaults(): void
    {
        if (!Schema::hasTable('mmo_provider_settings')) {
            return;
        }

        foreach (['monnify', 'opay', 'palmpay'] as $index => $provider) {
            MmoProviderSetting::query()->firstOrCreate(
                ['provider' => $provider],
                [
                    'is_active' => $provider === 'monnify',
                    'environment' => 'sandbox',
                    'credentials' => [],
                    'last_test_status' => 'untested',
                ],
            );
        }
    }

    protected function mergeCredentials(string $provider, array $storedCredentials, array $submittedCredentials): array
    {
        $merged = $storedCredentials;
        $maskedCurrent = $this->maskCredentialsForProvider($provider, $storedCredentials);

        foreach ($this->providerDefinitions()[$provider]['fields'] ?? [] as $field) {
            $key = $field['key'];
            $submittedValue = trim((string) ($submittedCredentials[$key] ?? ''));

            if ($submittedValue === '' || $submittedValue === (string) ($maskedCurrent[$key] ?? '')) {
                continue;
            }

            $merged[$key] = $submittedValue;
        }

        return $merged;
    }

    protected function providerIsReady(string $provider, array $credentials, string $lastTestStatus): bool
    {
        if ($lastTestStatus !== 'success') {
            return false;
        }

        return match ($provider) {
            'monnify' => collect(['api_key', 'secret_key', 'contract_code'])
                ->every(fn (string $key) => trim((string) ($credentials[$key] ?? '')) !== ''),
            'opay', 'palmpay' => false,
            default => false,
        };
    }

    protected function maskSecret(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }

        return str_repeat('*', max(strlen($value) - 4, 4)) . substr($value, -4);
    }
}
