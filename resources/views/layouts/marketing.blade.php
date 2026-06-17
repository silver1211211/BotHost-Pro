<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $branding = \App\Support\Branding::assets();
    @endphp
    @include('partials.favicon', ['branding' => $branding])
    @include('partials.seo', ['pageKey' => \App\Support\Seo::keyForRoute('home')])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#050B18] font-sans text-[#F8FAFC] antialiased">
    <div class="min-h-screen overflow-hidden bg-[linear-gradient(180deg,#050B18_0%,#070B14_46%,#050B18_100%)]">

        {{-- Sticky Navbar --}}
        <nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-[#1E293B]/80 bg-[rgba(7,17,31,0.85)] backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="group flex items-center gap-3">
                    @if($branding['platform_logo_url'])
                        <img src="{{ $branding['platform_logo_url'] }}" alt="{{ $branding['platform_name'] }}" class="h-11 w-auto max-w-[190px] object-contain">
                    @else
                        <span class="relative grid h-11 w-11 place-items-center rounded-xl border border-[#229ED9]/40 bg-[#0B1220] text-lg font-black text-[#38BDF8] shadow-[0_0_24px_rgba(34,158,217,0.18)]">
                            B
                            <span class="absolute -right-1 -top-1 h-3 w-3 rounded-full bg-[#229ED9]"></span>
                        </span>
                        <span class="text-lg font-extrabold tracking-tight text-[#F8FAFC]">BotHost <span class="text-[#38BDF8]">Pro</span></span>
                    @endif
                </a>

                {{-- Desktop nav links --}}
                <div class="hidden items-center gap-8 text-sm font-medium text-[#94A3B8] lg:flex">
                    <a href="#home" class="transition hover:text-[#F8FAFC]">Home</a>
                    <a href="#features" class="transition hover:text-[#F8FAFC]">Features</a>
                    <a href="#templates" class="transition hover:text-[#F8FAFC]">Templates</a>
                    <a href="#pricing" class="transition hover:text-[#F8FAFC]">Pricing</a>
                    <a href="#faq" class="transition hover:text-[#F8FAFC]">FAQ</a>
                </div>

                {{-- Desktop auth links --}}
                <div class="hidden items-center gap-3 lg:flex">
                    <a href="{{ route('login') }}" class="rounded-xl border border-[#1E293B] bg-[#0B1220] px-4 py-2 text-sm font-semibold text-[#F8FAFC] transition hover:border-[#229ED9] hover:text-[#38BDF8]">Login</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-[#229ED9] px-5 py-2.5 text-sm font-bold text-white shadow-[0_0_24px_rgba(34,158,217,0.30)] transition hover:bg-[#38BDF8]">Get Started</a>
                </div>

                {{-- Mobile menu button --}}
                <button type="button" @click="open = ! open" class="rounded-xl border border-[#1E293B] px-3 py-2 text-sm font-semibold text-[#F8FAFC] transition hover:border-[#229ED9] lg:hidden">Menu</button>
            </div>

            {{-- Mobile menu --}}
            <div x-show="open" x-transition class="border-t border-[#1E293B] bg-[#070B14] px-4 py-4 lg:hidden">
                <div class="flex flex-col gap-3 text-sm font-medium text-[#94A3B8]">
                    <a href="#home" @click="open = false" class="rounded-xl px-3 py-2 transition hover:bg-[#0B1220] hover:text-[#F8FAFC]">Home</a>
                    <a href="#features" @click="open = false" class="rounded-xl px-3 py-2 transition hover:bg-[#0B1220] hover:text-[#F8FAFC]">Features</a>
                    <a href="#templates" @click="open = false" class="rounded-xl px-3 py-2 transition hover:bg-[#0B1220] hover:text-[#F8FAFC]">Templates</a>
                    <a href="#pricing" @click="open = false" class="rounded-xl px-3 py-2 transition hover:bg-[#0B1220] hover:text-[#F8FAFC]">Pricing</a>
                    <a href="#faq" @click="open = false" class="rounded-xl px-3 py-2 transition hover:bg-[#0B1220] hover:text-[#F8FAFC]">FAQ</a>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <a href="{{ route('login') }}" class="rounded-xl border border-[#1E293B] px-4 py-2.5 text-center font-semibold text-[#F8FAFC] transition hover:border-[#229ED9]">Login</a>
                        <a href="{{ route('register') }}" class="rounded-xl bg-[#229ED9] px-4 py-2.5 text-center font-bold text-white transition hover:bg-[#38BDF8]">Get Started</a>
                    </div>
                </div>
            </div>
        </nav>

        {{ $slot }}
    </div>
</body>
</html>
