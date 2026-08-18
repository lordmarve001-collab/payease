@php
    $settings = App\Models\SiteSetting::getSiteSettings();
    $siteName = $settings->site_name ?? 'PayEase';
    $primaryColor = $settings->primary_color ?? '#059669';
    $logoUrl = $settings->logoUrl();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Welcome to') }} {{ $siteName }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827; max-width: 600px; margin: 0 auto; padding: 20px;">
    @if($logoUrl)
        <div style="text-align: center; margin-bottom: 16px;">
            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="max-height: 48px; object-fit: contain;">
        </div>
    @endif
    <div style="background: {{ $primaryColor }}; padding: 24px; border-radius: 12px 12px 0 0; text-align: center;">
        <h1 style="color: #ffffff; margin: 0; font-size: 24px;">{{ __('Welcome to') }} {{ $siteName }}!</h1>
    </div>
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-top: 0; padding: 32px; border-radius: 0 0 12px 12px;">
        <p style="font-size: 16px;">{{ __('Hi') }} <strong>{{ $name }}</strong>,</p>
        <p style="font-size: 16px;">{{ __('Your :siteName account has been created successfully.', ['siteName' => $siteName]) }}</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600;">{{ __('Email') }}</td>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb;">{{ $email }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600;">{{ __('Phone') }}</td>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb;">+234{{ $phone }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600;">{{ __('Login Password') }}</td>
                <td style="padding: 8px 12px; border: 1px solid #e5e7eb; font-family: monospace;">{{ $password }}</td>
            </tr>
        </table>

        <p style="font-size: 14px; color: #6b7280;">{{ __('You can log in to the :siteName app using your phone number/email and this password. Your 6-digit transaction PIN was set during registration.', ['siteName' => $siteName]) }}</p>

        <p style="font-size: 14px; color: #6b7280;">{{ __('For security, please change your password after logging in.') }}</p>

        <p style="font-size: 16px; margin-top: 24px;">{{ __('Best regards') }},<br><strong>{{ __('The :siteName Team', ['siteName' => $siteName]) }}</strong></p>
    </div>
</body>
</html>
