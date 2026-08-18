<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

class UssdSettingsService
{
    public const SETTINGS_KEY = 'ussd_gateway';

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
            'africas_talking_api_key' => $this->maskSecret($settings['africas_talking_api_key'] ?? ''),
            'africas_talking_username' => $settings['africas_talking_username'] ?? '',
            'africas_talking_service_code' => $settings['africas_talking_service_code'] ?? '',
            'africas_talking_environment' => $settings['africas_talking_environment'] ?? 'sandbox',
        ];
    }

    public function testConnection(): array
    {
        $settings = $this->getSettings();
        $apiKey = (string) ($settings['africas_talking_api_key'] ?? '');
        $username = (string) ($settings['africas_talking_username'] ?? '');

        if ($apiKey === '' || $username === '') {
            return [
                'status' => 'failed',
                'message' => 'API Key and Username are required to test the connection.',
            ];
        }

        try {
            $environment = (string) ($settings['africas_talking_environment'] ?? 'sandbox');
            $baseUrl = $environment === 'live'
                ? 'https://api.africastalking.com'
                : 'https://api.sandbox.africastalking.com';

            $response = Http::withHeaders([
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
            ])->get($baseUrl . '/version1/user');

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'message' => 'Africa\'s Talking connection successful.',
                ];
            }

            return [
                'status' => 'failed',
                'message' => 'Connection failed: ' . ($response->body() ?: 'Unknown error'),
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
            'africas_talking_api_key' => '',
            'africas_talking_username' => '',
            'africas_talking_service_code' => '*347#',
            'africas_talking_environment' => 'sandbox',
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
