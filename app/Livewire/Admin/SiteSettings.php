<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class SiteSettings extends Component
{
    use WithFileUploads;

    public SiteSetting $settings;

    public string $siteName = '';
    public string $siteTagline = '';
    public string $siteDescription = '';
    public string $primaryColor = '#00A86B';
    public string $secondaryColor = '#0A0F1A';
    public string $accentColor = '';
    public $logo;
    public $icon;
    public $favicon;
    public string $supportEmail = '';
    public string $supportPhone = '';
    public string $supportWhatsapp = '';
    public string $addressLine1 = '';
    public string $addressLine2 = '';
    public string $city = '';
    public string $state = '';
    public string $country = 'Nigeria';
    public array $socialLinks = [];
    public array $businessHours = [];
    public bool $registrationEnabled = true;
    public string $defaultUserRole = 'customer';
    public array $registrationRequiredFields = [];
    public bool $emailVerificationRequired = false;
    public bool $phoneVerificationRequired = true;
    public string $recaptchaSiteKey = '';
    public string $recaptchaSecretKey = '';
    public string $customFooterHtml = '';
    public string $customHeadHtml = '';
    public string $timezone = 'Africa/Lagos';
    public string $locale = 'en';

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super_admin'), 403);

        $this->settings = SiteSetting::getSiteSettings();

        $this->siteName = (string) ($this->settings->site_name ?? 'PayEase');
        $this->siteTagline = (string) ($this->settings->site_tagline ?? '');
        $this->siteDescription = (string) ($this->settings->site_description ?? '');
        $this->primaryColor = (string) ($this->settings->primary_color ?? '#00A86B');
        $this->secondaryColor = (string) ($this->settings->secondary_color ?? '#0A0F1A');
        $this->accentColor = (string) ($this->settings->accent_color ?? '');
        $this->supportEmail = (string) ($this->settings->support_email ?? '');
        $this->supportPhone = (string) ($this->settings->support_phone ?? '');
        $this->supportWhatsapp = (string) ($this->settings->support_whatsapp ?? '');
        $this->addressLine1 = (string) ($this->settings->address_line1 ?? '');
        $this->addressLine2 = (string) ($this->settings->address_line2 ?? '');
        $this->city = (string) ($this->settings->city ?? '');
        $this->state = (string) ($this->settings->state ?? '');
        $this->country = (string) ($this->settings->country ?? 'Nigeria');
        $this->socialLinks = $this->settings->social_links ?? [];
        $this->businessHours = $this->settings->business_hours ?? [];
        $this->registrationEnabled = (bool) ($this->settings->registration_enabled ?? true);
        $this->defaultUserRole = (string) ($this->settings->default_user_role ?? 'customer');
        $this->registrationRequiredFields = $this->settings->registration_required_fields ?? ['full_name', 'phone', 'pin'];
        $this->emailVerificationRequired = (bool) ($this->settings->email_verification_required ?? false);
        $this->phoneVerificationRequired = (bool) ($this->settings->phone_verification_required ?? true);
        $this->recaptchaSiteKey = (string) ($this->settings->recaptcha_site_key ?? '');
        $this->recaptchaSecretKey = (string) ($this->settings->recaptcha_secret_key ?? '');
        $this->customFooterHtml = (string) ($this->settings->custom_footer_html ?? '');
        $this->customHeadHtml = (string) ($this->settings->custom_head_html ?? '');
        $this->timezone = (string) ($this->settings->timezone ?? 'Africa/Lagos');
        $this->locale = (string) ($this->settings->locale ?? 'en');
    }

    public function addSocialLink(): void
    {
        $this->socialLinks[] = ['platform' => '', 'url' => ''];
    }

    public function removeSocialLink(int $index): void
    {
        unset($this->socialLinks[$index]);
        $this->socialLinks = array_values($this->socialLinks);
    }

    public function save(): void
    {
        $this->validate();

        $oldValues = $this->settings->toArray();

        $data = [
            'site_name' => $this->siteName,
            'site_tagline' => $this->siteTagline ?: null,
            'site_description' => $this->siteDescription ?: null,
            'primary_color' => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'accent_color' => $this->accentColor ?: null,
            'support_email' => $this->supportEmail ?: null,
            'support_phone' => $this->supportPhone ?: null,
            'support_whatsapp' => $this->supportWhatsapp ?: null,
            'address_line1' => $this->addressLine1 ?: null,
            'address_line2' => $this->addressLine2 ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'country' => $this->country,
            'social_links' => $this->socialLinks,
            'business_hours' => $this->businessHours,
            'registration_enabled' => $this->registrationEnabled,
            'default_user_role' => $this->defaultUserRole,
            'registration_required_fields' => $this->registrationRequiredFields,
            'email_verification_required' => $this->emailVerificationRequired,
            'phone_verification_required' => $this->phoneVerificationRequired,
            'recaptcha_site_key' => $this->recaptchaSiteKey ?: null,
            'recaptcha_secret_key' => $this->recaptchaSecretKey ?: null,
            'custom_footer_html' => $this->customFooterHtml ?: null,
            'custom_head_html' => $this->customHeadHtml ?: null,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
        ];

        if ($this->logo) {
            $data['logo_path'] = $this->logo->store('site-assets', 'public');
        }

        if ($this->icon) {
            $data['icon_path'] = $this->icon->store('site-assets', 'public');
        }

        if ($this->favicon) {
            $data['favicon_path'] = $this->favicon->store('site-assets', 'public');
        }

        $this->settings->update($data);

        $this->logo = null;
        $this->icon = null;
        $this->favicon = null;

        $this->settings->refresh();

        SiteSetting::flushCache();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'site_settings_updated',
            'entity_type' => 'site_setting',
            'entity_id' => $this->settings->id,
            'old_values' => $oldValues,
            'new_values' => $data,
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->dispatch('notify-success', message: 'Site settings saved successfully.');
    }

    public function removeLogo(): void
    {
        if ($this->settings->logo_path) {
            Storage::disk('public')->delete($this->settings->logo_path);
        }
        $this->settings->update(['logo_path' => null]);
        $this->logo = null;
        SiteSetting::flushCache();
    }

    public function removeIcon(): void
    {
        if ($this->settings->icon_path) {
            Storage::disk('public')->delete($this->settings->icon_path);
        }
        $this->settings->update(['icon_path' => null]);
        $this->icon = null;
        SiteSetting::flushCache();
    }

    public function removeFavicon(): void
    {
        if ($this->settings->favicon_path) {
            Storage::disk('public')->delete($this->settings->favicon_path);
        }
        $this->settings->update(['favicon_path' => null]);
        $this->favicon = null;
        SiteSetting::flushCache();
    }

    public function render()
    {
        return view('livewire.admin.site-settings', [
            'settingsModel' => $this->settings,
        ])->layout('components.layouts.admin');
    }

    protected function rules(): array
    {
        return [
            'siteName' => ['required', 'string', 'max:255'],
            'siteTagline' => ['nullable', 'string', 'max:255'],
            'siteDescription' => ['nullable', 'string', 'max:1000'],
            'primaryColor' => ['required', 'string', 'max:20'],
            'secondaryColor' => ['required', 'string', 'max:20'],
            'accentColor' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,webp', 'max:512'],
            'supportEmail' => ['nullable', 'email', 'max:255'],
            'supportPhone' => ['nullable', 'string', 'max:50'],
            'supportWhatsapp' => ['nullable', 'string', 'max:50'],
            'addressLine1' => ['nullable', 'string', 'max:500'],
            'addressLine2' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'socialLinks' => ['nullable', 'array'],
            'socialLinks.*.platform' => ['nullable', 'string', 'max:100'],
            'socialLinks.*.url' => ['nullable', 'url', 'max:500'],
            'businessHours' => ['nullable', 'array'],
            'registrationEnabled' => ['boolean'],
            'defaultUserRole' => ['required', Rule::in(['customer', 'agent'])],
            'registrationRequiredFields' => ['nullable', 'array'],
            'registrationRequiredFields.*' => ['string', 'max:100'],
            'emailVerificationRequired' => ['boolean'],
            'phoneVerificationRequired' => ['boolean'],
            'recaptchaSiteKey' => ['nullable', 'string', 'max:500'],
            'recaptchaSecretKey' => ['nullable', 'string', 'max:500'],
            'customFooterHtml' => ['nullable', 'string'],
            'customHeadHtml' => ['nullable', 'string'],
            'timezone' => ['required', 'string', 'max:100'],
            'locale' => ['required', 'string', 'max:10'],
        ];
    }
}
