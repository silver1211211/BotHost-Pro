@php
    $faviconUrl = $branding['favicon_url'] ?? \App\Support\Branding::faviconUrl();
    $faviconUrl = $faviconUrl ?: asset('favicon.svg');
    $faviconType = str_ends_with(parse_url($faviconUrl, PHP_URL_PATH) ?: '', '.svg') ? 'image/svg+xml' : null;
@endphp

<link rel="icon" href="{{ $faviconUrl }}" @if($faviconType) type="{{ $faviconType }}" @endif sizes="any">
<link rel="shortcut icon" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
