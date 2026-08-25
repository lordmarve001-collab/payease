<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'site_name',
        'site_tagline',
        'site_description',
        'logo_path',
        'icon_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'support_email',
        'support_phone',
        'support_whatsapp',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'social_links',
        'business_hours',
        'registration_enabled',
        'default_user_role',
        'registration_required_fields',
        'email_verification_required',
        'phone_verification_required',
        'recaptcha_site_key',
        'recaptcha_secret_key',
        'custom_footer_html',
        'custom_head_html',
        'timezone',
        'locale',
    ];

    protected $casts = [
        'social_links' => 'array',
        'business_hours' => 'array',
        'registration_required_fields' => 'array',
        'registration_enabled' => 'boolean',
        'email_verification_required' => 'boolean',
        'phone_verification_required' => 'boolean',
    ];

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function iconUrl(): ?string
    {
        return $this->icon_path ? Storage::disk('public')->url($this->icon_path) : null;
    }

    public function faviconUrl(): ?string
    {
        return $this->favicon_path ? Storage::disk('public')->url($this->favicon_path) : null;
    }

    public static function getSiteSettings(): self
    {
        return Cache::rememberForever('site_settings', function () {
            return self::first() ?? self::create([]);
        });
    }

    public static function defaultSettings(): self
    {
        $instance = new self();
        $instance->site_name = 'PayEase';
        $instance->primary_color = '#F59E0B';
        $instance->secondary_color = '#8B5CF6';
        $instance->accent_color = null;

        return $instance;
    }

    public static function flushCache(): void
    {
        Cache::forget('site_settings');
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
    }
}
