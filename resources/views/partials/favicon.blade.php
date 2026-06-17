@php
    $faviconUrl = $branding['favicon_url'] ?? \App\Support\Branding::faviconUrl();
@endphp

@if($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
@endif
