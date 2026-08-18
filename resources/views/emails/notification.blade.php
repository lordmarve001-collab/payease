@php
    $settings = App\Models\SiteSetting::getSiteSettings();
    $appName = $settings->site_name ?? 'PayEase';
    $url = config('app.url', '#');
    $logoUrl = $settings->logoUrl();
@endphp
<x-mail::message>
@if($logoUrl)
![{{ $appName }}]({{ $logoUrl }})

@endif
# {{ __('Hello') }}, {{ $recipientName }}!

{{ $message }}

<x-mail::button :url="$url">
{{ __('Go to') }} {{ $appName }}
</x-mail::button>

{{ __('Thanks') }},<br>
{{ $appName }}
</x-mail::message>
