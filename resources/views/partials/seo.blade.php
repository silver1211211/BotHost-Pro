@php
    $seoPageKey = $pageKey ?? \App\Support\Seo::keyForRoute('home');
    $seoData = $seo ?? \App\Support\Seo::page($seoPageKey, $seoOverrides ?? []);
    $canonicalUrl = filled($seoData['canonical_url'] ?? null) ? $seoData['canonical_url'] : url()->current();
    $seoUrl = filled($seoData['url'] ?? null) ? $seoData['url'] : $canonicalUrl;
@endphp

<title>{{ $seoData['title'] }}</title>
<meta name="description" content="{{ $seoData['meta_description'] }}">
<meta name="keywords" content="{{ $seoData['meta_keywords'] }}">
<meta name="robots" content="{{ $seoData['robots'] }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $seoData['og_title'] }}">
<meta property="og:description" content="{{ $seoData['og_description'] }}">
<meta property="og:type" content="{{ $seoData['og_type'] }}">
<meta property="og:url" content="{{ $seoUrl }}">
@if(! empty($seoData['og_image']))
<meta property="og:image" content="{{ $seoData['og_image'] }}">
@endif
<meta name="twitter:card" content="{{ ! empty($seoData['og_image']) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seoData['twitter_title'] }}">
<meta name="twitter:description" content="{{ $seoData['twitter_description'] }}">
@if(! empty($seoData['og_image']))
<meta name="twitter:image" content="{{ $seoData['og_image'] }}">
@endif
