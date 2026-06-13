<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php $branding = $branding ?? \App\Support\Branding::assets(); @endphp
    @if($branding['favicon_url'])
        <link rel="icon" href="{{ $branding['favicon_url'] }}">
    @endif
    <title>BotHost Pro — Telegram Bot Workspace Platform</title>
    <meta name="description" content="Create bots, manage commands, import templates, track users, send broadcasts, and connect webhooks — all from one clean workspace.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        .hero-glow{background:radial-gradient(ellipse 80% 50% at 50% -10%,rgba(139,92,246,0.26) 0%,transparent 68%)}
        .grid-lines{background-image:linear-gradient(rgba(248,250,252,0.032) 1px,transparent 1px),linear-gradient(90deg,rgba(248,250,252,0.032) 1px,transparent 1px);background-size:56px 56px}
        .section-glow{background:radial-gradient(ellipse 60% 40% at 50% 100%,rgba(139,92,246,0.10) 0%,transparent 70%)}
    </style>
</head>
<body class="bg-[#05040A] font-sans text-[#F8FAFC] antialiased overflow-x-hidden">

@php
$features = [
    ['Bot Workspaces',       'Create and organize multiple Telegram bot workspaces from one clean dashboard.',                                 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18',      '#8B5CF6'],
    ['Command Builder',      'Add commands, responses, and bot logic with exact-match triggers — no wildcards, no drift.',                     'M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25Z', '#38BDF8'],
    ['Template Marketplace', 'Import ready-made bot templates and speed up your workspace setup significantly.',                               'M5.5 4.75h5.75v5.75H5.5V4.75Zm7.25 0h5.75v5.75h-5.75V4.75ZM5.5 12.75h5.75v5.75H5.5v-5.75Zm7.25 0h5.75v5.75h-5.75v-5.75Z', '#A855F7'],
    ['Broadcast Tools',      'Send targeted updates to tracked bot users based on your plan limits.',                                         'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783M10.34 6.66a49.422 49.422 0 012.041-.163m-2.041.163L9 7.5m1.34-.84 1.34.84M9 7.5l1.34-.84M9 7.5l-1.34-.84M9 7.5l1.34.84', '#22C55E'],
    ['Bot User Tracking',    'Track users interacting with your bots and understand your audience better.',                                   'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0Zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0Z', '#F59E0B'],
    ['Custom Webhook',       'Receive POST callbacks from external platforms into your bot workspace via a dedicated endpoint.',               'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244', '#38BDF8'],
    ['Transfers',            'Send self-coded bot workspaces to another account securely. The receiver can connect a token later.',           'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',                              '#8B5CF6'],
    ['Import / Export',      'Export self-created commands and import workspace files safely across accounts.',                               'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3', '#A855F7'],
    ['Logs & Errors',        'Review command logs, webhook delivery records, and error traces to improve your bot.',                          'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z', '#EF4444'],
    ['Plan Access Control',  'Free, Pro, and Business tiers with organized feature and resource limits per plan.',                            'M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z', '#22C55E'],
];

$steps = [
    ['01', '#8B5CF6', 'Create your account',       'Sign up free in seconds. No credit card required to get started.'],
    ['02', '#38BDF8', 'Connect your bot token',    'Add your Telegram bot token to link your bot to the workspace.'],
    ['03', '#A855F7', 'Add commands or import',    'Build commands from scratch or import a ready-made template.'],
    ['04', '#22C55E', 'Launch and manage',         'Your bot is live. Monitor users, send broadcasts, and track performance.'],
];

$templateTiers = [
    ['Free Templates',     'Get started with community-built templates at no cost.',                '#22C55E', 'Free'],
    ['Pro Templates',      'Access expanded template library with Pro plan access.',                '#8B5CF6', 'Pro'],
    ['Business Templates', 'Priority templates and advanced configurations for Business plans.',    '#38BDF8', 'Business'],
    ['Paid Templates',     'Purchase premium marketplace templates with one-time payments.',        '#F59E0B', 'Paid'],
];

$workspaceItems = [
    ['Commands',       '#8B5CF6', 'M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25Z'],
    ['Users',          '#38BDF8', 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0Zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0Z'],
    ['Broadcast',      '#A855F7', 'M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5'],
    ['Logs',           '#F59E0B', 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
    ['Webhook',        '#38BDF8', 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244'],
    ['Settings',       '#71717A', 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ['Transfers',      '#8B5CF6', 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
    ['Import/Export',  '#A855F7', 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3'],
];

$transferCards = [
    ['Export Commands',       'Export your self-created commands as a file for backup or reuse.',       '#8B5CF6', 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3'],
    ['Import Commands',       'Import command files into a new or existing workspace safely.',           '#38BDF8', 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5'],
    ['Transfer Workspace',    'Send a self-coded bot workspace to another user by email. They can add a token later.', '#A855F7', 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
    ['Clone with New Token',  'Duplicate a workspace configuration and attach a new bot token.',        '#22C55E', 'M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75'],
];

$securityPoints = [
    ['Bot tokens hidden after saving',      '#22C55E'],
    ['Duplicate bot tokens blocked',        '#22C55E'],
    ['Sensitive keys masked in UI',         '#22C55E'],
    ['Marketplace commands protected',      '#22C55E'],
    ['Admin security monitoring',           '#38BDF8'],
    ['Maintenance mode protection',         '#38BDF8'],
];

$plans = [
    ['Free',     'Start free',  'Basic bot creation, limited commands, and community access.',        ['Basic bot workspace', 'Limited commands', 'Template access', 'Community support'], false, 'Get Started Free', 'register'],
    ['Pro',      'Go further',  'More bots, more commands, advanced templates, and priority features.', ['Expanded bot limit', 'More commands per bot', 'Priority templates', 'Broadcasts & tracking'], true, 'Upgrade to Pro', 'register'],
    ['Business', 'Scale up',   'Higher limits, priority support, and advanced workspace tools.',       ['Highest limits', 'All Pro features', 'Advanced tools', 'Priority support'], false, 'Get Business', 'register'],
];

$faqs = [
    ['Do I need coding skills?',      'You can start with the command builder and templates without writing any code. Advanced users can work with more flexible bot logic and webhook integrations.'],
    ['Can I import templates?',       'Yes. Templates can be imported directly into bot workspaces. Free, Pro, and paid templates are available in the marketplace.'],
    ['Can I export my bot?',          'You can export self-created commands as a file. Marketplace and template commands are protected and cannot be exported.'],
    ['Can I transfer a bot workspace?','Yes. You can transfer a fully self-coded workspace to another user by email. The receiver can import it first and connect their own token later.'],
    ['What is Custom Webhook?',       'Custom Webhook is an incoming endpoint for external platforms to send POST callbacks into your bot workspace. Each bot has its own unique endpoint.'],
    ['Is my bot token safe?',         'Bot tokens are protected and should never be shared publicly. After saving, tokens are masked in the interface and stored securely.'],
];
@endphp

{{-- ─── NAVBAR ──────────────────────────────────────────────────────────── --}}
<nav x-data="{ open: false }" class="fixed left-0 right-0 top-0 z-50 border-b border-[#27213D]/50 bg-[#05040A]/82 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">

        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            @if($branding['platform_logo_url'])
                <img src="{{ $branding['platform_logo_url'] }}" alt="{{ $branding['platform_name'] }}" class="h-10 w-auto max-w-[170px] object-contain">
            @else
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#8B5CF6] to-[#229ED9] shadow-[0_0_20px_rgba(139,92,246,0.48)]">
                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.75 12 14.5-7.25-3.2 14.5-4.12-4.18-3.08 3.18.45-4.55L4.75 12Z"/></svg>
                </div>
                <span class="text-base font-black text-[#F8FAFC]">BotHost<span class="bg-gradient-to-r from-[#38BDF8] to-[#8B5CF6] bg-clip-text text-transparent"> Pro</span></span>
            @endif
        </a>

        <div class="hidden items-center gap-6 text-sm font-semibold text-[#71717A] lg:flex">
            @foreach ([['Features','#features'],['How It Works','#how'],['Templates','#templates'],['Pricing','#pricing'],['FAQ','#faq']] as [$l,$h])
                <a href="{{ $h }}" class="transition hover:text-[#F8FAFC]">{{ $l }}</a>
            @endforeach
        </div>

        <div class="hidden items-center gap-2 lg:flex">
            <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-sm font-bold text-[#A1A1AA] transition hover:text-[#F8FAFC]">Log in</a>
            <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white shadow-[0_0_24px_rgba(139,92,246,0.36)] transition hover:-translate-y-0.5 hover:shadow-[0_0_36px_rgba(139,92,246,0.50)]">Get Started</a>
        </div>

        <button type="button" @click="open = !open" class="grid h-9 w-9 place-items-center rounded-xl border border-[#27213D]/60 text-[#F8FAFC] lg:hidden">
            <svg x-show="!open" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="open" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <div x-show="open" x-transition x-cloak class="border-t border-[#27213D]/50 bg-[#07060F]/98 px-4 pb-5 pt-3 lg:hidden">
        <div class="space-y-0.5 text-sm font-semibold text-[#A1A1AA]">
            @foreach ([['Features','#features'],['How It Works','#how'],['Templates','#templates'],['Pricing','#pricing'],['FAQ','#faq']] as [$l,$h])
                <a href="{{ $h }}" @click="open=false" class="block rounded-xl px-4 py-3 transition hover:bg-[#111020] hover:text-[#F8FAFC]">{{ $l }}</a>
            @endforeach
        </div>
        <div class="mt-4 grid grid-cols-2 gap-2">
            <a href="{{ route('login') }}" class="rounded-xl border border-[#27213D] py-3 text-center text-sm font-bold text-[#F8FAFC] transition hover:bg-[#111020]">Log in</a>
            <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] py-3 text-center text-sm font-black text-white">Get Started</a>
        </div>
    </div>
</nav>

<main class="relative overflow-x-hidden pt-[60px]">

    {{-- ─── HERO ────────────────────────────────────────────────────────── --}}
    <section id="home" class="relative min-h-[90vh] flex items-center px-4 pb-16 pt-16 sm:px-6 sm:pt-20 lg:px-8 lg:pt-24">
        <div class="pointer-events-none absolute inset-0">
            <div class="hero-glow absolute inset-0"></div>
            <div class="grid-lines absolute inset-0"></div>
            <div class="absolute left-1/2 top-24 h-[500px] w-[500px] -translate-x-1/2 rounded-full bg-[#8B5CF6]/8 blur-[120px]"></div>
            <div class="absolute right-1/4 top-1/3 h-72 w-72 rounded-full bg-[#38BDF8]/6 blur-[90px]"></div>
        </div>

        <div class="relative mx-auto w-full max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">

                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-[#8B5CF6]/30 bg-[#8B5CF6]/10 px-4 py-2 text-xs font-black uppercase tracking-widest text-[#8B5CF6]">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#8B5CF6] shadow-[0_0_6px_rgba(139,92,246,1)]"></span>
                    Telegram Bot Workspace Platform
                </div>

                <h1 class="text-balance text-4xl font-black leading-[1.06] tracking-tight text-[#F8FAFC] sm:text-5xl lg:text-[62px]">
                    Build and manage<br>
                    <span class="bg-gradient-to-r from-[#38BDF8] via-[#8B5CF6] to-[#A855F7] bg-clip-text text-transparent">Telegram bots</span><br>
                    without starting from scratch
                </h1>

                <p class="mx-auto mt-6 max-w-xl text-base leading-7 text-[#71717A] sm:text-lg sm:leading-8">
                    Create bots, manage commands, import templates, track users, send broadcasts, and connect external callbacks — all from one clean workspace.
                </p>

                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] via-[#A855F7] to-[#38BDF8] px-8 py-3.5 text-sm font-black text-white shadow-[0_0_40px_rgba(139,92,246,0.42)] transition hover:-translate-y-0.5 hover:shadow-[0_0_56px_rgba(139,92,246,0.56)]">
                        Create Free Account
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#27213D] bg-[#0F0D1A]/70 px-8 py-3.5 text-sm font-bold text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-[#F8FAFC]">
                        Log in to Dashboard
                    </a>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-4 text-xs font-semibold text-[#3D3657]">
                    @foreach (['Free to start', 'No credit card required', 'Bot tokens encrypted', 'Templates included'] as $b)
                        <span class="flex items-center gap-1.5">
                            <svg class="h-3 w-3 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                            {{ $b }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- Dashboard mockup --}}
            <div class="relative mx-auto mt-14 max-w-5xl">
                <div class="absolute inset-x-16 -top-8 h-28 rounded-full bg-gradient-to-r from-[#8B5CF6]/18 via-[#38BDF8]/12 to-[#A855F7]/18 blur-3xl"></div>
                <div class="relative overflow-hidden rounded-3xl border border-[#27213D] bg-[#0B0918] shadow-[0_40px_130px_rgba(0,0,0,0.80),0_0_80px_rgba(139,92,246,0.10)]">
                    <div class="flex items-center justify-between border-b border-[#27213D] bg-[#07060F] px-5 py-3.5">
                        <div class="flex gap-1.5">
                            <div class="h-2.5 w-2.5 rounded-full bg-[#EF4444]"></div>
                            <div class="h-2.5 w-2.5 rounded-full bg-[#F59E0B]"></div>
                            <div class="h-2.5 w-2.5 rounded-full bg-[#22C55E]"></div>
                        </div>
                        <span class="rounded-full border border-[#27213D] px-3 py-1 text-[10px] font-black text-[#71717A]">bothost.pro — workspace</span>
                        <div class="w-14"></div>
                    </div>
                    <div class="grid lg:grid-cols-[200px_1fr]">
                        <div class="hidden border-r border-[#27213D] bg-[#07060F] p-4 lg:block">
                            <div class="mb-5 flex items-center gap-2.5">
                                <div class="h-7 w-7 rounded-lg bg-gradient-to-br from-[#8B5CF6] to-[#229ED9]"></div>
                                <div class="h-3 w-20 rounded bg-[#1B172B]"></div>
                            </div>
                            @foreach ([['Commands',true,'#8B5CF6'],['Users',false,null],['Broadcast',false,null],['Logs',false,null],['Settings',false,null],['Webhook',false,null]] as [$label,$active,$clr])
                                <div class="mb-0.5 flex items-center gap-2.5 rounded-xl px-2.5 py-2 {{ $active ? 'bg-[#8B5CF6]/15' : '' }}">
                                    <div class="h-4 w-4 rounded {{ $active ? 'bg-[#8B5CF6]' : 'bg-[#1B172B]' }}"></div>
                                    <span class="text-[11px] {{ $active ? 'font-black text-[#8B5CF6]' : 'text-[#3D3657]' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="p-5 sm:p-6">
                            <div class="mb-5 flex items-center justify-between">
                                <div>
                                    <div class="h-4 w-36 rounded bg-[#F8FAFC]/10"></div>
                                    <div class="mt-1.5 h-2.5 w-24 rounded bg-[#27213D]"></div>
                                </div>
                                <div class="h-7 w-24 rounded-lg bg-gradient-to-r from-[#8B5CF6] to-[#A855F7]"></div>
                            </div>
                            <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach ([['#8B5CF6','Bots'],['#38BDF8','Commands'],['#22C55E','Users'],['#F59E0B','Logs']] as [$c,$l])
                                    <div class="rounded-2xl border border-[#27213D] bg-[#151225] p-4">
                                        <div class="mb-1 h-2 w-6 rounded" style="background:{{ $c }}"></div>
                                        <div class="text-[10px] font-black" style="color:{{ $c }}">{{ $l }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="space-y-2">
                                @foreach ([['#8B5CF6','/start','Send welcome message'],['#38BDF8','/help','Show help menu'],['#A855F7','deposit','Show deposit info'],['#22C55E','balance','Return wallet balance']] as [$c,$cmd,$desc])
                                    <div class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3">
                                        <span class="shrink-0 rounded-lg px-2 py-0.5 text-[10px] font-black" style="background:{{ $c }}20;color:{{ $c }}">{{ $cmd }}</span>
                                        <span class="truncate text-xs text-[#71717A]">{{ $desc }}</span>
                                        <div class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $c }}"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── PLATFORM HIGHLIGHTS ─────────────────────────────────────────── --}}
    <section class="border-y border-[#27213D]/50 bg-[#07060F]/70 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                @foreach ([
                    ['No-code friendly',     'Command management without writing code',    '#8B5CF6', 'M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25Z'],
                    ['Plan-based limits',    'Organized feature access per plan tier',     '#38BDF8', 'M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z'],
                    ['Secure token storage', 'Bot tokens protected and hidden after saving', '#22C55E', 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z'],
                    ['Transfers supported', 'Move workspaces between accounts safely',     '#A855F7', 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
                ] as [$title,$desc,$color,$icon])
                    <div class="flex items-start gap-3 rounded-2xl border border-[#27213D]/60 bg-[#0B0918]/50 p-4 sm:p-5">
                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" style="background:{{ $color }}15;color:{{ $color }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-black text-[#F8FAFC]">{{ $title }}</p>
                            <p class="mt-0.5 text-xs leading-5 text-[#71717A]">{{ $desc }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─── FEATURES ───────────────────────────────────────────────────── --}}
    <section id="features" class="px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto mb-14 max-w-2xl text-center">
                <p class="text-xs font-black uppercase tracking-widest text-[#38BDF8]">Features</p>
                <h2 class="mt-3 text-3xl font-black text-[#F8FAFC] sm:text-4xl">Everything your bot workspace needs</h2>
                <p class="mt-3 text-sm leading-7 text-[#71717A]">From workspace creation to commands, templates, broadcasts, webhooks, and transfers — all in one place.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($features as [$title,$desc,$icon,$color])
                    <div class="group relative overflow-hidden rounded-2xl border border-[#27213D] bg-[#0B0918] p-6 transition duration-300 hover:-translate-y-1 hover:border-[#27213D]/80">
                        <div class="pointer-events-none absolute inset-0 opacity-0 transition duration-300 group-hover:opacity-100" style="background:radial-gradient(circle at 50% 0%,{{ $color }}10 0%,transparent 65%)"></div>
                        <div class="relative">
                            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl border transition duration-300" style="border-color:{{ $color }}28;background:{{ $color }}10;color:{{ $color }}">
                                <svg class="h-4.5 w-4.5 h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            </div>
                            <h3 class="text-sm font-black text-[#F8FAFC]">{{ $title }}</h3>
                            <p class="mt-1.5 text-xs leading-5 text-[#71717A]">{{ $desc }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─── HOW IT WORKS ──────────────────────────────────────────────── --}}
    <section id="how" class="border-y border-[#27213D]/50 bg-[#07060F]/60 px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto mb-14 max-w-2xl text-center">
                <p class="text-xs font-black uppercase tracking-widest text-[#A855F7]">How It Works</p>
                <h2 class="mt-3 text-3xl font-black text-[#F8FAFC] sm:text-4xl">Up and running in minutes</h2>
                <p class="mt-3 text-sm leading-7 text-[#71717A]">Four simple steps from sign-up to a live Telegram bot workspace.</p>
            </div>
            <div class="relative grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="absolute left-1/2 top-9 hidden h-px w-3/4 -translate-x-1/2 bg-gradient-to-r from-[#8B5CF6]/20 via-[#38BDF8]/30 to-[#22C55E]/20 lg:block"></div>
                @foreach ($steps as [$step,$color,$title,$desc])
                    <div class="relative rounded-2xl border border-[#27213D] bg-[#0B0918] p-7">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl text-lg font-black text-white" style="background:linear-gradient(135deg,{{ $color }}cc,{{ $color }}70)">{{ $step }}</div>
                        <h3 class="text-base font-black text-[#F8FAFC]">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-[#71717A]">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─── WORKSPACE PREVIEW ──────────────────────────────────────────── --}}
    <section class="px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0B0918]">
                <div class="grid lg:grid-cols-2">
                    <div class="p-8 sm:p-12 flex flex-col justify-center">
                        <p class="text-xs font-black uppercase tracking-widest text-[#38BDF8]">Workspace</p>
                        <h2 class="mt-3 text-3xl font-black text-[#F8FAFC] sm:text-4xl">One workspace.<br>Everything included.</h2>
                        <p class="mt-4 text-sm leading-7 text-[#71717A]">Each bot gets its own dedicated workspace with all tools in one place — no jumping between platforms.</p>
                        <div class="mt-8 grid grid-cols-2 gap-3">
                            @foreach ($workspaceItems as [$label,$color,$icon])
                                <div class="flex items-center gap-2.5 rounded-xl border border-[#27213D]/60 bg-[#0F0D1A] px-3.5 py-3 text-sm font-semibold text-[#A1A1AA] transition hover:border-[#27213D] hover:text-[#F8FAFC]">
                                    <svg class="h-4 w-4 shrink-0" style="color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                    {{ $label }}
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-8">
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-6 py-3 text-sm font-black text-white shadow-[0_0_24px_rgba(139,92,246,0.30)] transition hover:-translate-y-0.5">
                                Open your workspace
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                            </a>
                        </div>
                    </div>
                    <div class="border-t border-[#27213D] bg-[#07060F] p-6 sm:p-8 lg:border-l lg:border-t-0">
                        <div class="rounded-2xl border border-[#27213D] bg-[#0B0918] overflow-hidden">
                            <div class="flex items-center gap-2 border-b border-[#27213D] bg-[#07060F] px-4 py-3">
                                @foreach (['Commands','Users','Logs'] as $tab)
                                    <span class="rounded-lg px-3 py-1.5 text-xs font-black {{ $tab === 'Commands' ? 'bg-[#8B5CF6]/15 text-[#8B5CF6]' : 'text-[#3D3657]' }}">{{ $tab }}</span>
                                @endforeach
                            </div>
                            <div class="p-4 space-y-2">
                                @foreach ([['#8B5CF6','/start','Welcome message',true],['#38BDF8','/help','Help menu',true],['#A855F7','deposit','Deposit prompt',true],['#22C55E','balance','Wallet balance',false],['#F59E0B','withdraw','Withdrawal flow',false]] as [$c,$cmd,$desc,$active])
                                    <div class="flex items-center gap-3 rounded-xl border {{ $active ? 'border-[#27213D] bg-[#151225]' : 'border-[#1A1730] bg-[#0F0D1A] opacity-60' }} px-3.5 py-3">
                                        <span class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-black" style="background:{{ $c }}20;color:{{ $c }}">{{ $cmd }}</span>
                                        <span class="flex-1 truncate text-xs text-[#71717A]">{{ $desc }}</span>
                                        <span class="shrink-0 h-1.5 w-1.5 rounded-full" style="background:{{ $active ? $c : '#3D3657' }}"></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── TEMPLATES ──────────────────────────────────────────────────── --}}
    <section id="templates" class="border-y border-[#27213D]/50 bg-[#07060F]/60 px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-[#A855F7]">Marketplace</p>
                    <h2 class="mt-3 text-3xl font-black text-[#F8FAFC] sm:text-4xl">Start faster with templates</h2>
                    <p class="mt-4 text-sm leading-7 text-[#71717A]">Browse ready-made templates, import them into your bot workspace, and customize your setup. Skip the blank-slate setup and launch faster.</p>
                    <div class="mt-6 rounded-xl border border-[#F59E0B]/20 bg-[#F59E0B]/6 px-4 py-3">
                        <p class="text-xs leading-5 text-[#F59E0B]">
                            <span class="font-black">Note:</span> Marketplace templates are protected and cannot be exported like self-created commands.
                        </p>
                    </div>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-6 py-3 text-sm font-black text-white shadow-[0_0_24px_rgba(139,92,246,0.28)] transition hover:-translate-y-0.5">
                            Browse Templates
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl border border-[#27213D] px-6 py-3 text-sm font-bold text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-[#F8FAFC]">
                            Log in
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($templateTiers as [$title,$desc,$color,$badge])
                        <div class="rounded-2xl border border-[#27213D] bg-[#0B0918] p-5 transition hover:-translate-y-0.5">
                            <div class="mb-3 flex items-center justify-between">
                                <div class="h-9 w-9 rounded-xl flex items-center justify-center" style="background:{{ $color }}15;color:{{ $color }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.5 4.75h5.75v5.75H5.5V4.75Zm7.25 0h5.75v5.75h-5.75V4.75ZM5.5 12.75h5.75v5.75H5.5v-5.75Zm7.25 0h5.75v5.75h-5.75v-5.75Z"/></svg>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-black" style="background:{{ $color }}18;color:{{ $color }}">{{ $badge }}</span>
                            </div>
                            <h3 class="text-sm font-black text-[#F8FAFC]">{{ $title }}</h3>
                            <p class="mt-1.5 text-xs leading-5 text-[#71717A]">{{ $desc }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ─── TRANSFER / IMPORT / EXPORT ────────────────────────────────── --}}
    <section class="px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto mb-14 max-w-2xl text-center">
                <p class="text-xs font-black uppercase tracking-widest text-[#38BDF8]">Transfers</p>
                <h2 class="mt-3 text-3xl font-black text-[#F8FAFC] sm:text-4xl">Move bot workspaces safely</h2>
                <p class="mt-4 text-sm leading-7 text-[#71717A]">
                    Transfer workspaces between accounts, export your own commands for reuse, or import command files into a fresh workspace. Marketplace templates stay protected throughout.
                </p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($transferCards as [$title,$desc,$color,$icon])
                    <div class="group rounded-2xl border border-[#27213D] bg-[#0B0918] p-6 transition hover:-translate-y-1">
                        <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl border transition" style="border-color:{{ $color }}28;background:{{ $color }}10;color:{{ $color }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        </div>
                        <h3 class="text-sm font-black text-[#F8FAFC]">{{ $title }}</h3>
                        <p class="mt-2 text-xs leading-5 text-[#71717A]">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-8 rounded-2xl border border-[#27213D]/60 bg-[#07060F] p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-black text-[#F8FAFC]">Workspace transfers are protected</p>
                        <p class="mt-1 text-xs leading-5 text-[#71717A]">When a workspace is transferred, the receiving user must connect their own Telegram bot token. Marketplace and template commands remain protected and cannot be exported.</p>
                    </div>
                    <a href="{{ route('register') }}" class="shrink-0 inline-flex items-center gap-2 rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-[#F8FAFC]">
                        Get started
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── CUSTOM WEBHOOK ─────────────────────────────────────────────── --}}
    <section class="border-y border-[#27213D]/50 bg-[#07060F]/60 px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div class="order-2 lg:order-1">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0B0918] overflow-hidden">
                        <div class="flex items-center gap-2 border-b border-[#27213D] bg-[#07060F] px-4 py-3">
                            <div class="h-2 w-2 rounded-full bg-[#38BDF8]"></div>
                            <span class="text-[10px] font-black text-[#71717A]">Incoming Webhook Endpoint</span>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-wider text-[#71717A] mb-1">Endpoint URL</p>
                                <p class="text-xs font-mono text-[#38BDF8] truncate">POST /webhooks/custom/{'{'}botId{'}'}/{'{'}secret{'}'}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach ([['Last delivery','Just now','#22C55E'],['Status','200 OK','#22C55E'],['Method','POST','#38BDF8'],['Auth','Secret key','#8B5CF6']] as [$l,$v,$c])
                                    <div class="rounded-xl border border-[#27213D] bg-[#151225] px-3.5 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-[#3D3657]">{{ $l }}</p>
                                        <p class="mt-1 text-xs font-black" style="color:{{ $c }}">{{ $v }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <div class="rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-wider text-[#71717A] mb-2">Delivery Logs</p>
                                @foreach ([['2s ago','200 OK','External payment callback'],['14s ago','200 OK','Order notification received'],['2m ago','200 OK','User action processed']] as [$t,$s,$msg])
                                    <div class="flex items-center gap-2.5 py-1">
                                        <span class="text-[9px] text-[#3D3657] shrink-0">{{ $t }}</span>
                                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black text-[#22C55E] bg-[#22C55E]/10">{{ $s }}</span>
                                        <span class="truncate text-[10px] text-[#71717A]">{{ $msg }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    <p class="text-xs font-black uppercase tracking-widest text-[#38BDF8]">Custom Webhook</p>
                    <h2 class="mt-3 text-3xl font-black text-[#F8FAFC] sm:text-4xl">Receive external callbacks</h2>
                    <p class="mt-4 text-sm leading-7 text-[#71717A]">Each bot workspace can have a dedicated incoming webhook endpoint. External platforms can send POST callbacks directly into your bot workspace.</p>
                    <div class="mt-6 space-y-3">
                        @foreach ([['Unique endpoint per bot workspace','#8B5CF6'],['Accepts POST requests from any external platform','#38BDF8'],['Delivery log for every incoming request','#22C55E'],['Secret key authentication for security','#A855F7']] as [$point,$c])
                            <div class="flex items-center gap-2.5 text-sm text-[#A1A1AA]">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-lg" style="background:{{ $c }}12;color:{{ $c }}">
                                    <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                </span>
                                {{ $point }}
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-8">
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#38BDF8]/80 to-[#8B5CF6] px-6 py-3 text-sm font-black text-white shadow-[0_0_24px_rgba(56,189,248,0.20)] transition hover:-translate-y-0.5">
                            Set up your webhook
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── SECURITY ────────────────────────────────────────────────────── --}}
    <section class="px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0B0918]">
                <div class="grid lg:grid-cols-2">
                    <div class="p-8 sm:p-12">
                        <p class="text-xs font-black uppercase tracking-widest text-[#22C55E]">Security</p>
                        <h2 class="mt-3 text-3xl font-black text-[#F8FAFC] sm:text-4xl">Built with security in mind</h2>
                        <p class="mt-4 text-sm leading-7 text-[#71717A]">Your bot tokens, workspace data, and user information are handled with security as the baseline. Sensitive credentials are protected throughout the platform.</p>
                        <div class="mt-8 grid gap-3 sm:grid-cols-2">
                            @foreach ($securityPoints as [$point,$color])
                                <div class="flex items-center gap-2.5 text-sm text-[#A1A1AA]">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-lg" style="background:{{ $color }}12;color:{{ $color }}">
                                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                    </span>
                                    {{ $point }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center justify-center border-t border-[#27213D] bg-[#07060F] p-8 lg:border-l lg:border-t-0">
                        <div class="w-full max-w-xs space-y-2.5">
                            @foreach ([['Bot Token','Protected','#22C55E'],['Duplicate Tokens','Blocked','#EF4444'],['Sensitive Keys','Masked','#38BDF8'],['Marketplace Commands','Protected','#8B5CF6'],['Admin Monitoring','Active','#22C55E'],['Maintenance Mode','Available','#F59E0B']] as [$l,$v,$c])
                                <div class="flex items-center justify-between rounded-xl border border-[#27213D] bg-[#0B0918] px-4 py-3">
                                    <span class="text-xs font-semibold text-[#71717A]">{{ $l }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-black" style="background:{{ $c }}15;color:{{ $c }}">{{ $v }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── PLANS ───────────────────────────────────────────────────────── --}}
    <section id="pricing" class="border-y border-[#27213D]/50 bg-[#07060F]/60 px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto mb-14 max-w-2xl text-center">
                <p class="text-xs font-black uppercase tracking-widest text-[#38BDF8]">Plans</p>
                <h2 class="mt-3 text-3xl font-black text-[#F8FAFC] sm:text-4xl">Start free, scale when ready</h2>
                <p class="mt-3 text-sm leading-7 text-[#71717A]">All plans include core workspace features. Upgrade for more bots, commands, templates, and advanced tools.</p>
            </div>
            <div class="grid gap-5 lg:grid-cols-3">
                @foreach ($plans as [$plan,$tagline,$desc,$items,$popular,$cta,$route])
                    <div class="relative flex flex-col rounded-3xl border bg-[#0B0918] p-7 transition hover:-translate-y-0.5 {{ $popular ? 'border-[#8B5CF6]/55 shadow-[0_0_60px_rgba(139,92,246,0.16)]' : 'border-[#27213D]' }}">
                        @if ($popular)
                            <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-4 py-1 text-[10px] font-black text-white shadow-[0_0_18px_rgba(139,92,246,0.48)]">Most Popular</span>
                        @endif
                        <div class="mb-5">
                            <p class="text-xs font-black uppercase tracking-wide text-[#71717A]">{{ $plan }}</p>
                            <p class="mt-1.5 text-xl font-black text-[#F8FAFC]">{{ $tagline }}</p>
                            <p class="mt-2 text-sm leading-6 text-[#71717A]">{{ $desc }}</p>
                        </div>
                        <ul class="mb-8 flex-1 space-y-3">
                            @foreach ($items as $item)
                                <li class="flex items-center gap-2.5 text-sm text-[#A1A1AA]">
                                    <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-[#22C55E]/14 text-[#22C55E]">
                                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                    </span>
                                    {{ $item }}
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route($route) }}" class="block rounded-xl py-3 text-center text-sm font-black transition {{ $popular ? 'bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] text-white shadow-[0_0_24px_rgba(139,92,246,0.30)] hover:shadow-[0_0_36px_rgba(139,92,246,0.46)]' : 'border border-[#27213D] text-[#F8FAFC] hover:border-[#8B5CF6]/45 hover:text-[#8B5CF6]' }}">{{ $cta }}</a>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-center text-xs text-[#3D3657]">Plan limits and pricing details are shown during account upgrade. No credit card required to start.</p>
        </div>
    </section>

    {{-- ─── FAQ ─────────────────────────────────────────────────────────── --}}
    <section id="faq" class="px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
            <div class="mb-14 text-center">
                <p class="text-xs font-black uppercase tracking-widest text-[#A855F7]">FAQ</p>
                <h2 class="mt-3 text-3xl font-black text-[#F8FAFC] sm:text-4xl">Common questions</h2>
                <p class="mt-3 text-sm leading-7 text-[#71717A]">Quick answers about BotHost Pro features and policies.</p>
            </div>
            <div class="space-y-2" x-data="{ open: null }">
                @foreach ($faqs as $i => [$q, $a])
                    <div class="overflow-hidden rounded-2xl border border-[#27213D] transition hover:border-[#8B5CF6]/35">
                        <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-sm font-black text-[#F8FAFC]">
                            <span>{{ $q }}</span>
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-[#27213D] bg-[#0F0D1A] text-lg font-thin text-[#71717A] transition-transform duration-200" :class="open === {{ $i }} ? 'rotate-45 border-[#8B5CF6]/45 text-[#8B5CF6]' : ''">+</span>
                        </button>
                        <div x-show="open === {{ $i }}" x-collapse x-cloak class="border-t border-[#27213D]/50 px-5 pb-5 pt-4 text-sm leading-7 text-[#71717A]">{{ $a }}</div>
                    </div>
                @endforeach
            </div>
            <div class="mt-8 text-center">
                @if (Route::has('help.index'))
                    <a href="{{ route('help.index') }}" class="text-sm font-semibold text-[#38BDF8] transition hover:text-[#7DD3FC]">Visit Help Center →</a>
                @endif
            </div>
        </div>
    </section>

    {{-- ─── FINAL CTA ──────────────────────────────────────────────────── --}}
    <section class="border-t border-[#27213D]/50 bg-[#07060F]/60 px-4 pb-20 pt-20 sm:px-6 lg:px-8">
        <div class="relative mx-auto max-w-4xl overflow-hidden rounded-3xl border border-[#8B5CF6]/22 bg-[#0B0918] p-10 text-center shadow-[0_0_80px_rgba(139,92,246,0.12)] sm:p-16">
            <div class="pointer-events-none absolute inset-0" style="background:radial-gradient(ellipse at 50% 0%,rgba(139,92,246,0.10),transparent 60%)"></div>
            <div class="relative">
                <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-[#8B5CF6]/28 bg-[#8B5CF6]/8 px-4 py-2 text-xs font-black uppercase tracking-widest text-[#8B5CF6]">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#8B5CF6]"></span>
                    Ready to build
                </div>
                <h2 class="text-3xl font-black text-[#F8FAFC] sm:text-4xl">Ready to build your Telegram bot workspace?</h2>
                <p class="mx-auto mt-4 max-w-lg text-sm leading-7 text-[#71717A]">Create your account, connect your bot, and start building commands in minutes. No credit card required to get started.</p>
                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] via-[#A855F7] to-[#38BDF8] px-8 py-3.5 text-sm font-black text-white shadow-[0_0_40px_rgba(139,92,246,0.40)] transition hover:-translate-y-0.5 hover:shadow-[0_0_56px_rgba(139,92,246,0.54)]">
                        Create Free Account
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-[#27213D] px-8 py-3.5 text-sm font-bold text-[#A1A1AA] transition hover:border-[#27213D]/80 hover:text-[#F8FAFC]">Log in</a>
                </div>
            </div>
        </div>
    </section>

</main>

{{-- ─── FOOTER ──────────────────────────────────────────────────────────── --}}
<footer class="border-t border-[#27213D]/50 bg-[#03020A] px-4 py-14 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.6fr_repeat(3,1fr)]">
            <div>
                <div class="flex items-center gap-2.5">
                    @if($branding['platform_logo_url'])
                        <img src="{{ $branding['platform_logo_url'] }}" alt="{{ $branding['platform_name'] }}" class="h-10 w-auto max-w-[180px] object-contain">
                    @else
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#8B5CF6] to-[#229ED9] shadow-[0_0_16px_rgba(139,92,246,0.36)]">
                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.75 12 14.5-7.25-3.2 14.5-4.12-4.18-3.08 3.18.45-4.55L4.75 12Z"/></svg>
                        </div>
                        <span class="text-base font-black text-[#F8FAFC]">BotHost<span class="bg-gradient-to-r from-[#38BDF8] to-[#8B5CF6] bg-clip-text text-transparent"> Pro</span></span>
                    @endif
                </div>
                <p class="mt-4 max-w-xs text-sm leading-6 text-[#4D4868]">Create, manage, and grow Telegram bot workspaces with commands, templates, broadcasts, transfers, and webhooks.</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <a href="{{ route('register') }}" class="rounded-xl bg-[#8B5CF6]/12 px-4 py-2 text-xs font-black text-[#8B5CF6] transition hover:bg-[#8B5CF6]/20">Get Started Free</a>
                    <a href="{{ route('login') }}" class="rounded-xl border border-[#27213D] px-4 py-2 text-xs font-bold text-[#4D4868] transition hover:text-[#A1A1AA]">Log in</a>
                </div>
                <p class="mt-6 text-xs text-[#2A2540]">{{ $branding['footer_text'] }}</p>
            </div>

            @php
                $footerLinks = [
                    'Platform' => [
                        ['Features',        '#features'],
                        ['Templates',       '#templates'],
                        ['Plans',           '#pricing'],
                        ['How It Works',    '#how'],
                    ],
                    'Account' => [
                        ['Create Account',  route('register')],
                        ['Log in',          route('login')],
                        ['Dashboard',       Route::has('dashboard') ? route('dashboard') : route('login')],
                        ['Help Center',     Route::has('help.index') ? route('help.index') : route('login')],
                    ],
                    'Legal' => [
                        ['Privacy Policy',  route('legal.privacy')],
                        ['Terms of Service',route('legal.terms')],
                        ['Cookie Policy',   route('legal.cookies')],
                        ['Refund Policy',   route('legal.refunds')],
                        ['Acceptable Use',  route('legal.acceptable-use')],
                    ],
                ];
            @endphp
            @foreach ($footerLinks as $heading => $links)
                <div>
                    <p class="mb-5 text-[10px] font-black uppercase tracking-widest text-[#3D3657]">{{ $heading }}</p>
                    <div class="space-y-3 text-sm text-[#4D4868]">
                        @foreach ($links as [$label, $url])
                            <a href="{{ $url }}" class="block transition hover:text-[#A1A1AA]">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</footer>

</body>
</html>
