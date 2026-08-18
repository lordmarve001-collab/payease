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
# {{ __('Hi') }} {{ $agentName }},

{{ __('Your :appName float balance is low (:balance).', ['appName' => $appName, 'balance' => $floatBalance]) }}

{{ __('Please request a top-up to continue accepting withdrawals without interruption.') }}

<x-mail::button :url="$url">
{{ __('Go to') }} {{ $appName }}
</x-mail::button>

{{ __('Thanks') }},<br>
{{ $appName }}
</x-mail::message>
