<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class SystemSettingService
{
    public const NOTIFICATION_SETTINGS_KEY = 'notification_channels';

    public function getNotificationSettings(): array
    {
        $defaults = $this->defaultNotificationSettings();

        if (!Schema::hasTable('system_settings')) {
            return $defaults;
        }

        $stored = SystemSetting::query()
            ->where('key', self::NOTIFICATION_SETTINGS_KEY)
            ->first()?->payload;

        if (!is_array($stored)) {
            return $defaults;
        }

        return array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    public function saveNotificationSettings(array $settings): SystemSetting
    {
        return SystemSetting::query()->updateOrCreate(
            ['key' => self::NOTIFICATION_SETTINGS_KEY],
            ['payload' => array_merge($this->defaultNotificationSettings(), $settings)]
        );
    }

    public function applyNotificationConfig(?array $settings = null): void
    {
        $settings ??= $this->getNotificationSettings();

        Config::set('services.sms.driver', $settings['sms_driver']);
        Config::set('services.termii.api_key', $settings['termii_api_key']);
        Config::set('services.termii.sender_id', $settings['termii_sender_id']);

        Config::set('mail.default', $settings['mail_mailer']);
        Config::set('mail.mailers.smtp.scheme', $this->normalizeMailTransportScheme((string) ($settings['mail_scheme'] ?? '')));
        Config::set('mail.mailers.smtp.host', $settings['mail_host']);
        Config::set('mail.mailers.smtp.port', (int) $settings['mail_port']);
        Config::set('mail.mailers.smtp.username', $settings['mail_username'] ?: null);
        Config::set('mail.mailers.smtp.password', $settings['mail_password'] ?: null);
        Config::set('mail.from.address', $settings['mail_from_address']);
        Config::set('mail.from.name', $settings['mail_from_name']);

        app('mail.manager')->forgetMailers();
    }

    public function maskNotificationSettings(array $settings): array
    {
        return [
            'sms_driver' => $settings['sms_driver'] ?? null,
            'termii_api_key' => $this->maskSecret($settings['termii_api_key'] ?? ''),
            'termii_sender_id' => $settings['termii_sender_id'] ?? null,
            'mail_mailer' => $settings['mail_mailer'] ?? null,
            'mail_scheme' => $settings['mail_scheme'] ?? null,
            'mail_host' => $settings['mail_host'] ?? null,
            'mail_port' => (int) ($settings['mail_port'] ?? 0),
            'mail_username' => $settings['mail_username'] ?? null,
            'mail_password' => $this->maskSecret($settings['mail_password'] ?? ''),
            'mail_from_address' => $settings['mail_from_address'] ?? null,
            'mail_from_name' => $settings['mail_from_name'] ?? null,
        ];
    }

    protected function defaultNotificationSettings(): array
    {
        return [
            'sms_driver' => (string) config('services.sms.driver', 'termii'),
            'termii_api_key' => (string) config('services.termii.api_key', ''),
            'termii_sender_id' => (string) config('services.termii.sender_id', 'PayEase'),
            'mail_mailer' => (string) config('mail.default', 'log'),
            'mail_scheme' => $this->presentMailScheme((string) (config('mail.mailers.smtp.scheme') ?? '')),
            'mail_host' => (string) config('mail.mailers.smtp.host', ''),
            'mail_port' => (string) config('mail.mailers.smtp.port', '2525'),
            'mail_username' => (string) (config('mail.mailers.smtp.username') ?? ''),
            'mail_password' => (string) (config('mail.mailers.smtp.password') ?? ''),
            'mail_from_address' => (string) config('mail.from.address', 'hello@example.com'),
            'mail_from_name' => (string) config('mail.from.name', 'PayEase'),
        ];
    }

    protected function normalizeMailTransportScheme(string $scheme): ?string
    {
        return match (strtolower(trim($scheme))) {
            'tls' => 'smtp',
            'ssl' => 'smtps',
            'smtp', 'smtps' => strtolower(trim($scheme)),
            default => null,
        };
    }

    protected function presentMailScheme(string $scheme): string
    {
        return match (strtolower(trim($scheme))) {
            'smtp' => 'tls',
            'smtps' => 'ssl',
            default => '',
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
