<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($branding = \App\Support\Branding::assets())
    @if($branding['favicon_url'])
        <link rel="icon" href="{{ $branding['favicon_url'] }}">
    @endif
    <title>{{ $title ?? 'Error' }} — {{ config('app.name', 'BotHost Pro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#05040A] font-sans text-[#F8FAFC] antialiased">
<main class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-16 text-center">

    {{-- Background glow --}}
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_20%,rgba(139,92,246,0.18),transparent_55%),radial-gradient(circle_at_80%_80%,rgba(56,189,248,0.10),transparent_40%),linear-gradient(180deg,#05040A,#090713_50%,#05040A)]"></div>
    <div class="pointer-events-none absolute inset-0 opacity-[0.07] [background-image:linear-gradient(rgba(248,250,252,0.15)_1px,transparent_1px),linear-gradient(90deg,rgba(248,250,252,0.15)_1px,transparent_1px)] [background-size:46px_46px]"></div>

    <div class="relative w-full max-w-lg">

        {{-- Logo --}}
        <a href="{{ url('/') }}" class="mx-auto mb-10 flex w-fit items-center gap-3">
            @if($branding['platform_logo_url'])
                <img src="{{ $branding['platform_logo_url'] }}" alt="{{ $branding['platform_name'] }}" class="h-12 w-auto max-w-[230px] object-contain">
            @else
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-[#8B5CF6] via-[#A855F7] to-[#229ED9] shadow-[0_0_32px_rgba(139,92,246,0.35)]">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.75 12 14.5-7.25-3.2 14.5-4.12-4.18-3.08 3.18.45-4.55L4.75 12Z"/>
                    </svg>
                </span>
                <span class="text-lg font-black tracking-tight">BotHost <span class="bg-gradient-to-r from-[#38BDF8] to-[#A855F7] bg-clip-text text-transparent">Pro</span></span>
            @endif
        </a>

        {{-- Error code --}}
        <p class="bg-gradient-to-r from-[#8B5CF6] to-[#38BDF8] bg-clip-text text-7xl sm:text-8xl font-black tracking-tighter text-transparent">
            {{ $code ?? '?' }}
        </p>

        {{-- Title --}}
        <h1 class="mt-4 text-2xl font-black text-white">{{ $title ?? 'Something went wrong' }}</h1>

        {{-- Message --}}
        <p class="mt-3 text-sm leading-relaxed text-[#71717A]">{{ $message ?? 'An unexpected error occurred. Please try again or return to the homepage.' }}</p>

        {{-- Actions --}}
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-[#8B5CF6] px-6 py-2.5 text-sm font-black text-white transition hover:bg-[#7C3AED]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 22V12h6v10"/>
                </svg>
                Go to Homepage
            </a>
            <button onclick="history.back()"
                    class="inline-flex items-center gap-2 rounded-xl border border-[#27213D] px-6 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:border-[#3D3657] hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Go Back
            </button>
        </div>

        {{-- Footer --}}
        <p class="mt-12 text-xs text-[#3D3658]">
            {{ $branding['footer_text'] }}
        </p>

    </div>
</main>
</body>
</html>
