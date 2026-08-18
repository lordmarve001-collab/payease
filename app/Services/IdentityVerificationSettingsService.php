<?php

namespace App\Services;

use App\Models\SystemSetting;

class IdentityVerificationSettingsService
{
    public const SETTINGS_KEY = 'identity_verification';

    public function getSettings(): array
    {
        $defaults = $this->defaults();

        $stored = SystemSetting::where('key', self::SETTINGS_KEY)->first()?->payload;

        if (!is_array($stored)) {
            return $defaults;
        }

        return array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    public function saveSettings(array $settings): SystemSetting
    {
        return SystemSetting::updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['payload' => array_merge($this->defaults(), $settings)]
        );
    }

    public function maskSettings(array $settings): array
    {
        return [
            'youverify_api_key' => $this->maskSecret($settings['youverify_api_key'] ?? ''),
            'youverify_environment' => $settings['youverify_environment'] ?? 'sandbox',
            'prembly_api_key' => $this->maskSecret($settings['prembly_api_key'] ?? ''),
            'prembly_app_id' => $this->maskSecret($settings['prembly_app_id'] ?? ''),
            'prembly_environment' => $settings['prembly_environment'] ?? 'sandbox',
        ];
    }

    public function makeYouverifyClient(): YouverifyClient
    {
        $settings = $this->getSettings();

        return new YouverifyClient(
            apiKey: (string) ($settings['youverify_api_key'] ?? ''),
            environment: (string) ($settings['youverify_environment'] ?? 'sandbox'),
        );
    }

    public function makePremblyClient(): PremblyClient
    {
        $settings = $this->getSettings();

        return new PremblyClient(
            apiKey: (string) ($settings['prembly_api_key'] ?? ''),
            appId: (string) ($settings['prembly_app_id'] ?? ''),
            environment: (string) ($settings['prembly_environment'] ?? 'sandbox'),
        );
    }

    public function testYouverifyConnection(string $apiKey, string $environment): array
    {
        try {
            $client = new YouverifyClient($apiKey, $environment);
            $result = $client->verifyNin('00000000000', 'Test User', true);

            return [
                'status' => 'success',
                'message' => 'Youverify connection successful.',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function testPremblyConnection(string $apiKey, string $appId, string $environment): array
    {
        try {
            $client = new PremblyClient($apiKey, $appId, $environment);
            $result = $client->verifyBvn('00000000000', 'Test User', true);

            return [
                'status' => 'success',
                'message' => 'Prembly connection successful.',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function defaults(): array
    {
        return [
            'youverify_api_key' => '',
            'youverify_environment' => 'sandbox',
            'prembly_api_key' => '',
            'prembly_app_id' => '',
            'prembly_environment' => 'sandbox',
        ];
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
