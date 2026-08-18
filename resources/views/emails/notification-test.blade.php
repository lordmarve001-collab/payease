@php
    $settings = App\Models\SiteSetting::getSiteSettings();
    $appName = $settings->site_name ?? 'PayEase';
    $logoUrl = $settings->logoUrl();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} {{ __('Notification Test') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827; max-width: 600px; margin: 0 auto; padding: 20px;">
    @if($logoUrl)
        <div style="text-align: center; margin-bottom: 16px;">
            <img src="{{ $logoUrl }}" alt="{{ $appName }}" style="max-height: 48px; object-fit: contain;">
        </div>
    @endif
    <h1 style="margin-bottom: 16px;">{{ $appName }} {{ __('Notification Test') }}</h1>
    <p>{{ __('This is a test email from the :appName Super Admin settings page.', ['appName' => $appName]) }}</p>
    <p><strong>{{ __('Requested by:') }}</strong> {{ $requestedBy }}</p>
    <p><strong>{{ __('Sent at:') }}</strong> {{ $sentAt->format('Y-m-d H:i:s') }}</p>
    <p>{{ __('If this arrived correctly, your email notification configuration is working.') }}</p>
</body>
</html>
