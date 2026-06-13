@props(['title', 'updated' => 'May 27, 2026', 'sections' => []])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    @php($branding = \App\Support\Branding::assets())
    @if($branding['favicon_url'])
        <link rel="icon" href="{{ $branding['favicon_url'] }}">
    @endif
    <title>{{ $title }} — BotHost Pro</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#05040A] font-sans text-[#F8FAFC] antialiased">

    {{-- Ambient glow --}}
    <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_70%_35%_at_50%_0%,rgba(139,92,246,0.12),transparent),radial-gradient(circle_at_85%_15%,rgba(56,189,248,0.07),transparent_40%)]"></div>
    </div>

    {{-- Sticky header --}}
    <header class="sticky top-0 z-50 border-b border-[#1C1830] bg-[#05040A]/94 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 transition-opacity hover:opacity-80">
                @if($branding['platform_logo_url'])
                    <img src="{{ $branding['platform_logo_url'] }}" alt="{{ $branding['platform_name'] }}" class="h-9 w-auto max-w-[160px] object-contain">
                @else
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-[#8B5CF6] via-[#A855F7] to-[#229ED9] shadow-[0_0_18px_rgba(139,92,246,0.38)]">
                        <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.75 12 14.5-7.25-3.2 14.5-4.12-4.18-3.08 3.18.45-4.55L4.75 12Z"/>
                        </svg>
                    </span>
                    <span class="text-[15px] font-black tracking-tight text-[#F8FAFC]">
                        BotHost <span class="bg-gradient-to-r from-[#38BDF8] to-[#A855F7] bg-clip-text text-transparent">Pro</span>
                    </span>
                @endif
            </a>

        </div>
    </header>

    {{-- Hero --}}
    <section class="relative z-10 border-b border-[#1C1830] bg-gradient-to-b from-[#080614] to-[#05040A]">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14">

            <div class="inline-flex items-center gap-1.5 rounded-lg border border-[#38BDF8]/20 bg-[#38BDF8]/8 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-[#38BDF8]">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
                Legal Document
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-[#F8FAFC] sm:text-[42px] sm:leading-tight">{{ $title }}</h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-[#71717A]">
                Please read this document carefully. It governs your use of BotHost Pro services.
            </p>

            <div class="mt-5 inline-flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#0D0C18] px-3.5 py-2">
                <svg class="h-3.5 w-3.5 text-[#8B5CF6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
                <span class="text-xs text-[#71717A]">Last updated: <span class="font-semibold text-[#A1A1AA]">{{ $updated }}</span></span>
            </div>

        </div>
    </section>

    {{-- Body --}}
    <div
        class="relative z-10 mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:py-14"
        x-data="{ active: '{{ str(array_key_first($sections ?? ['Overview' => '']))->slug() }}', mobileOpen: false }"
    >

        {{-- Mobile TOC dropdown --}}
        <div class="mb-6 lg:hidden">
            <button
                @click="mobileOpen = !mobileOpen"
                class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#0D0C18] px-4 py-3 text-sm font-semibold text-[#A1A1AA] transition hover:border-[#8B5CF6]/30"
            >
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-[#8B5CF6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                    </svg>
                    Jump to section
                </span>
                <svg class="h-4 w-4 transition-transform duration-200" :class="mobileOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>
            <div
                x-show="mobileOpen"
                x-cloak
                @click.outside="mobileOpen = false"
                class="mt-1 overflow-hidden rounded-xl border border-[#27213D] bg-[#0D0C18] p-2 shadow-[0_20px_50px_rgba(0,0,0,0.50)]"
            >
                @foreach ($sections as $heading => $content)
                    <a
                        href="#{{ str($heading)->slug() }}"
                        @click="active = '{{ str($heading)->slug() }}'; mobileOpen = false"
                        :class="active === '{{ str($heading)->slug() }}' ? 'bg-[#8B5CF6]/10 text-[#A855F7] font-semibold' : 'text-[#71717A] hover:text-[#A1A1AA] hover:bg-[#151225]'"
                        class="block rounded-lg px-3 py-2.5 text-sm transition"
                    >{{ $heading }}</a>
                @endforeach
            </div>
        </div>

        <div class="grid gap-8 lg:grid-cols-[256px_1fr] xl:grid-cols-[280px_1fr]">

            {{-- Desktop sidebar --}}
            <aside class="hidden lg:block">
                <div class="sticky top-[72px] rounded-2xl border border-[#27213D] bg-[#0D0C18] p-5 shadow-[0_24px_60px_rgba(0,0,0,0.28)]">
                    <p class="mb-4 flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-[#3D3759]">
                        <svg class="h-3 w-3 text-[#8B5CF6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                        On this page
                    </p>
                    <nav class="space-y-0.5">
                        @foreach ($sections as $heading => $content)
                            <a
                                href="#{{ str($heading)->slug() }}"
                                @click="active = '{{ str($heading)->slug() }}'"
                                :class="active === '{{ str($heading)->slug() }}'
                                    ? 'border-[#8B5CF6] bg-[#8B5CF6]/10 text-[#A855F7] font-semibold'
                                    : 'border-transparent text-[#71717A] hover:text-[#A1A1AA] hover:bg-[#0F0D1A]'"
                                class="block rounded-r-xl border-l-[3px] px-3 py-2 text-[13px] leading-snug transition-all"
                            >{{ $heading }}</a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            {{-- Content article --}}
            <article class="min-w-0">
                <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0D0C18] shadow-[0_24px_80px_rgba(0,0,0,0.32)]">
                    <div class="divide-y divide-[#161328]">
                        @foreach ($sections as $heading => $content)
                            <section
                                id="{{ str($heading)->slug() }}"
                                class="scroll-mt-24 px-6 py-8 sm:px-8 sm:py-9"
                            >
                                {{-- Section heading --}}
                                <div class="mb-5 flex items-start gap-3">
                                    <span class="mt-1.5 h-5 w-0.5 shrink-0 rounded-full bg-gradient-to-b from-[#8B5CF6] to-[#38BDF8]"></span>
                                    <h2 class="text-[18px] font-black leading-tight text-[#F8FAFC]">{{ $heading }}</h2>
                                </div>

                                {{-- Section body --}}
                                @if (is_array($content))
                                    <ul class="ml-3.5 space-y-3">
                                        @foreach ($content as $item)
                                            <li class="flex items-start gap-3 text-sm leading-7 text-[#A1A1AA]">
                                                <span class="mt-[10px] h-1.5 w-1.5 shrink-0 rounded-full bg-[#8B5CF6]/60"></span>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="ml-3.5 text-sm leading-7 text-[#A1A1AA]">{{ $content }}</p>
                                @endif
                            </section>
                        @endforeach
                    </div>
                </div>
            </article>

        </div>
    </div>

    {{-- Footer --}}
    <footer class="relative z-10 mt-4 border-t border-[#1C1830] bg-[#03020A]">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <div class="flex flex-col items-center gap-5 sm:flex-row sm:justify-between">

                {{-- Brand --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2 transition-opacity hover:opacity-80">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-[#8B5CF6] to-[#229ED9]">
                        <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.75 12 14.5-7.25-3.2 14.5-4.12-4.18-3.08 3.18.45-4.55L4.75 12Z"/>
                        </svg>
                    </span>
                    <span class="text-sm font-bold text-[#4B4565]">BotHost Pro</span>
                </a>

                {{-- Legal nav --}}
                <nav class="flex flex-wrap items-center justify-center gap-y-2 text-[11px] text-[#3D3759]">
                    <a href="{{ route('legal.privacy') }}" class="px-2 transition hover:text-[#71717A]">Privacy Policy</a>
                    <span class="text-[#1C1830]">·</span>
                    <a href="{{ route('legal.terms') }}" class="px-2 transition hover:text-[#71717A]">Terms of Service</a>
                    <span class="text-[#1C1830]">·</span>
                    <a href="{{ route('legal.cookies') }}" class="px-2 transition hover:text-[#71717A]">Cookie Policy</a>
                    <span class="text-[#1C1830]">·</span>
                    <a href="{{ route('legal.refunds') }}" class="px-2 transition hover:text-[#71717A]">Refund Policy</a>
                    <span class="text-[#1C1830]">·</span>
                    <a href="{{ route('legal.acceptable-use') }}" class="px-2 transition hover:text-[#71717A]">Acceptable Use</a>
                </nav>

                {{-- Copyright --}}
                <p class="text-[11px] text-[#2A2640]">{{ $branding['footer_text'] }}</p>

            </div>
        </div>
    </footer>

</body>
</html>
