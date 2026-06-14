<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $branding = \App\Support\Branding::assets();
    @endphp
    @if($branding['favicon_url'])
        <link rel="icon" href="{{ $branding['favicon_url'] }}">
    @endif
    <title>{{ config('app.name', 'BotHost Pro') }} — Secure Access</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        .auth-input-icon { @apply pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#52525B]; }
        @keyframes auth-glow-pulse {
            0%, 100% { box-shadow: 0 0 28px rgba(139,92,246,0.22), 0 0 0 1px rgba(139,92,246,0.06); }
            50%       { box-shadow: 0 0 44px rgba(139,92,246,0.34), 0 0 0 1px rgba(139,92,246,0.12); }
        }
        .auth-card { animation: auth-glow-pulse 6s ease-in-out infinite; }
    </style>
</head>
<body class="bg-[#05040A] font-sans text-[#F8FAFC] antialiased">

    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12">

        {{-- Ambient background --}}
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,rgba(139,92,246,0.18),transparent),radial-gradient(circle_at_80%_30%,rgba(56,189,248,0.10),transparent_40%),linear-gradient(180deg,#05040A,#090713_60%,#05040A)]"></div>
            <div class="absolute inset-0 opacity-[0.07] [background-image:linear-gradient(rgba(248,250,252,0.15)_1px,transparent_1px),linear-gradient(90deg,rgba(248,250,252,0.15)_1px,transparent_1px)] [background-size:48px_48px]"></div>
            {{-- Floating orbs --}}
            <div class="absolute -left-32 top-1/4 h-96 w-96 rounded-full bg-[#8B5CF6]/6 blur-3xl"></div>
            <div class="absolute -right-32 bottom-1/4 h-80 w-80 rounded-full bg-[#38BDF8]/6 blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-[480px]">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="mx-auto mb-8 flex w-fit items-center gap-3 transition-opacity hover:opacity-80">
                @if($branding['platform_logo_url'])
                    <img src="{{ $branding['platform_logo_url'] }}" alt="{{ $branding['platform_name'] }}" class="h-14 w-auto max-w-[240px] object-contain">
                @else
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-[#8B5CF6] via-[#A855F7] to-[#229ED9] shadow-[0_0_40px_rgba(139,92,246,0.40)]">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.75 12 14.5-7.25-3.2 14.5-4.12-4.18-3.08 3.18.45-4.55L4.75 12Z"/>
                        </svg>
                    </span>
                    <span class="text-xl font-black tracking-tight text-[#F8FAFC]">
                        BotHost <span class="bg-gradient-to-r from-[#38BDF8] to-[#A855F7] bg-clip-text text-transparent">Pro</span>
                    </span>
                @endif
            </a>

            {{-- Card --}}
            <section class="auth-card rounded-3xl border border-[#27213D]/80 bg-[#0D0C18]/98 p-6 sm:p-8 shadow-[0_40px_120px_rgba(0,0,0,0.70),inset_0_1px_0_rgba(255,255,255,0.04)] backdrop-blur-2xl">
                {{ $slot }}
            </section>

            {{-- Policy footer --}}
            <footer class="mt-8 space-y-3 text-center">
                <nav class="flex flex-wrap items-center justify-center gap-y-2 text-[11px] text-[#3D3759]">
                    <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer" class="px-2 transition hover:text-[#94A3B8]">Privacy Policy</a>
                    <span class="text-[#27213D]">·</span>
                    <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer" class="px-2 transition hover:text-[#94A3B8]">Terms of Service</a>
                    <span class="text-[#27213D]">·</span>
                    <a href="{{ route('legal.cookies') }}" target="_blank" rel="noopener noreferrer" class="px-2 transition hover:text-[#94A3B8]">Cookie Policy</a>
                    <span class="text-[#27213D]">·</span>
                    <a href="{{ route('legal.refunds') }}" target="_blank" rel="noopener noreferrer" class="px-2 transition hover:text-[#94A3B8]">Refund Policy</a>
                    <span class="text-[#27213D]">·</span>
                    <a href="{{ route('legal.acceptable-use') }}" target="_blank" rel="noopener noreferrer" class="px-2 transition hover:text-[#94A3B8]">Acceptable Use</a>
                </nav>
                <p class="text-[10px] text-[#2A2640]">{{ $branding['footer_text'] }}</p>
            </footer>

        </div>
    </main>
</body>
</html>
