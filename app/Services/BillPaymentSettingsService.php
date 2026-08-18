<?php

namespace App\Services;

use App\Models\SystemSetting;

class BillPaymentSettingsService
{
    public const SETTINGS_KEY = 'bill_payment';

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
            'vtpass_api_key' => $this->maskSecret($settings['vtpass_api_key'] ?? ''),
            'vtpass_username' => $settings['vtpass_username'] ?? '',
            'vtpass_environment' => $settings['vtpass_environment'] ?? 'sandbox',
        ];
    }

    public function makeClient(): \App\Contracts\BillPaymentClientInterface
    {
        $settings = $this->getSettings();

        return new \App\Services\VTPassClient(
            apiKey: (string) ($settings['vtpass_api_key'] ?? ''),
            username: (string) ($settings['vtpass_username'] ?? ''),
            environment: (string) ($settings['vtpass_environment'] ?? 'sandbox'),
        );
    }

    public function testConnection(): array
    {
        try {
            return $this->makeClient()->testConnection();
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
            'vtpass_api_key' => '',
            'vtpass_username' => '',
            'vtpass_environment' => 'sandbox',
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
