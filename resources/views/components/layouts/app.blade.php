<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteSettings->site_name ?? 'PayEase' }}</title>
    @if($siteSettings->favicon_path)
        <link rel="icon" type="image/png" href="{{ $siteSettings->faviconUrl() }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --color-primary: {{ $siteSettings->primary_color ?? '#D97706' }};
            --color-secondary: {{ $siteSettings->secondary_color ?? '#7C3AED' }};
            @if($siteSettings->accent_color ?? null)
                --color-accent: {{ $siteSettings->accent_color }};
            @endif
        }
    </style>
    {!! $siteSettings->custom_head_html ?? '' !!}
</head>
<body class="font-sans antialiased min-h-screen">
    {{ $slot }}
    {!! $siteSettings->custom_footer_html ?? '' !!}
</body>
</html>
