@props(['title' => 'Admin', 'subtitle' => ''])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
    <title>{{ $title }} — Admin — {{ config('app.name', 'BotHost Pro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Only the main content area scrolls — everything else is locked */
        html, body { height: 100%; overflow: hidden; margin: 0; padding: 0; }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #05040A; }
        ::-webkit-scrollbar-thumb { background: #27213D; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #8B5CF6; }

        .nav-active {
            background: linear-gradient(90deg, rgba(139,92,246,0.16) 0%, rgba(139,92,246,0.04) 100%);
            border-left: 2px solid #8B5CF6;
            padding-left: calc(0.75rem - 2px);
            color: #A855F7;
        }
        .nav-inactive { color: #71717A; border-left: 2px solid transparent; }
        .nav-inactive:hover { background: #151225; color: #F8FAFC; }
        .metric-card:hover { border-color: rgba(139,92,246,0.35); transform: translateY(-1px); }
    </style>
</head>
<body class="bg-[#05040A] font-sans text-[#F8FAFC] antialiased">

<div x-data="{ sidebarOpen: false }">

    {{-- =====================
         MOBILE OVERLAY
    ===================== --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden"
        style="display:none"
    ></div>

    {{-- =====================
         SIDEBAR — fixed, never moves
    ===================== --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-50 flex w-[280px] flex-col bg-[#080711] transition-transform duration-300 ease-out -translate-x-full lg:translate-x-0"
        style="border-right: 1px solid #27213D; box-shadow: 4px 0 40px rgba(139,92,246,0.04);"
    >
        {{-- Logo --}}
        <div class="flex h-[72px] shrink-0 items-center gap-3 px-5" style="border-bottom: 1px solid #27213D;">
            <div class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl" style="background: linear-gradient(135deg, #5B21B6 0%, #8B5CF6 100%); box-shadow: 0 0 24px rgba(139,92,246,0.45);">
                @if($branding['admin_logo_url'])
                    <img src="{{ $branding['admin_logo_url'] }}" alt="Admin Logo" class="h-full w-full object-contain">
                @else
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                @endif
                <span class="absolute -right-1 -top-1 flex h-3.5 w-3.5 items-center justify-center rounded-full border-2 border-[#080711] bg-[#22C55E]"></span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-black tracking-tight text-white">BotHost Pro</p>
                <span class="inline-flex rounded px-2 py-0.5 text-[9px] font-black uppercase tracking-widest" style="background: rgba(139,92,246,0.15); color: #A855F7;">Admin Panel</span>
            </div>
            <button @click="sidebarOpen = false" class="flex h-7 w-7 items-center justify-center rounded-lg text-[#71717A] transition hover:text-white lg:hidden">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-5">
            @php
                $navGroups = [
                    'Overview' => [
                        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'dashboard'],
                    ],
                    'Management' => [
                        ['label' => 'Users',      'route' => 'admin.users.index',      'icon' => 'users'],
                        ['label' => 'Bots',       'route' => 'admin.bots.index',       'icon' => 'bots'],
                        ['label' => 'Templates',  'route' => 'admin.templates.index',  'icon' => 'templates'],
                        ['label' => 'Deposits',   'route' => 'admin.deposits.index',   'icon' => 'deposits'],
                        ['label' => 'Plans',      'route' => 'admin.plans.index',      'icon' => 'deposits'],
                        ['label' => 'Broadcasts', 'route' => 'admin.broadcasts.index', 'icon' => 'broadcasts'],
                    ],
                    'System' => [
                        ['label' => 'Logs',     'route' => 'admin.logs.index',      'icon' => 'logs'],
                        ['label' => 'Security', 'route' => 'admin.security.index',  'icon' => 'security'],
                        ['label' => 'Settings', 'route' => 'admin.settings.index',  'icon' => 'settings'],
                    ],
                ];

                $icons = [
                    'dashboard'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />',
                    'users'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
                    'bots'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />',
                    'templates'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />',
                    'deposits'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />',
                    'broadcasts' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />',
                    'logs'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h.008v.008h-.008V8.25Zm-15 3.75h.008v.008H5.625V12Zm9.375 0h.008v.008h-.008V12Z" />',
                    'security'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />',
                    'settings'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
                ];
            @endphp

            @foreach ($navGroups as $groupLabel => $items)
                <div class="mb-6">
                    <p class="mb-2 px-3 text-[10px] font-black uppercase tracking-[0.18em] text-[#514B6E]">{{ $groupLabel }}</p>
                    <div class="space-y-1">
                        @foreach ($items as $item)
                            @php
                                $routePrefix = implode('.', array_slice(explode('.', $item['route']), 0, -1));
                                $active = request()->routeIs($item['route']) ||
                                    (str_contains($routePrefix, '.') && request()->routeIs($routePrefix . '.*'));
                            @endphp
                            <a href="{{ route($item['route']) }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition-all duration-150 {{ $active ? 'nav-active' : 'nav-inactive' }}"
                               @click="sidebarOpen = false">
                                <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    {!! $icons[$item['icon']] ?? '' !!}
                                </svg>
                                <span class="flex-1">{{ $item['label'] }}</span>
                                @if ($active)
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#8B5CF6]"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- Profile card --}}
        <div class="shrink-0 p-3" style="border-top: 1px solid #27213D;">
            <div class="flex items-center gap-3 rounded-xl px-3 py-3" style="background: #0F0D1A; border: 1px solid #1B172B;">
                <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-black text-white" style="background: linear-gradient(135deg, #5B21B6 0%, #A855F7 100%);">
                    {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->username ?? 'A', 0, 1)) }}
                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-[#0F0D1A] bg-[#22C55E]"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-bold text-white">{{ auth()->user()->name ?? auth()->user()->username }}</p>
                    <div class="mt-0.5 flex items-center gap-1">
                        <span class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider" style="background: rgba(139,92,246,0.2); color: #A855F7;">Admin</span>
                        <span class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider" style="background: rgba(56,189,248,0.12); color: #38BDF8;">Business</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" title="Logout"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-[#71717A] transition hover:bg-[#EF4444]/10 hover:text-[#EF4444]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- =====================
         TOPBAR — fixed, never moves
    ===================== --}}
    <header
        class="fixed top-0 right-0 z-30 flex h-[72px] items-center gap-3 px-4 sm:px-6 lg:px-8"
        style="left: 0; background: rgba(9,7,19,0.92); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border-bottom: 1px solid #27213D; transition: left 0.3s ease-out;"
    >
        {{-- Push topbar right on desktop to avoid sidebar overlap --}}
        <div class="hidden lg:block lg:w-[280px] lg:shrink-0"></div>

        {{-- Hamburger (mobile only) --}}
        <button
            @click="sidebarOpen = true"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[#27213D] text-[#A1A1AA] transition hover:border-[#8B5CF6]/50 hover:text-white lg:hidden"
        >
            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        {{-- Page title --}}
        <div class="min-w-0 flex-1">
            <h1 class="truncate text-base font-black text-white sm:text-lg">{{ $title }}</h1>
            @if ($subtitle)
                <p class="hidden truncate text-xs text-[#71717A] sm:block">{{ $subtitle }}</p>
            @else
                <p class="hidden text-xs text-[#71717A] sm:block">BotHost Pro — Platform administration</p>
            @endif
        </div>

        {{-- Global search --}}
        <div class="hidden items-center gap-2 rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2 md:flex" style="width: 220px;">
            <svg class="h-4 w-4 shrink-0 text-[#71717A]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" placeholder="Quick search..." class="flex-1 bg-transparent text-sm text-[#A1A1AA] outline-none placeholder:text-[#71717A]" />
        </div>

        {{-- Notification --}}
        <button class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[#27213D] text-[#71717A] transition hover:bg-[#151225] hover:text-white">
            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
        </button>

        {{-- Admin pill --}}
        <div class="flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-2.5 py-1.5">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black text-white" style="background: linear-gradient(135deg, #5B21B6 0%, #A855F7 100%);">
                {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->username ?? 'A', 0, 1)) }}
            </div>
            <span class="hidden text-xs font-semibold text-[#A1A1AA] sm:block max-w-[100px] truncate">{{ auth()->user()->name ?? auth()->user()->username }}</span>
            <span class="hidden rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider sm:inline" style="background: rgba(139,92,246,0.2); color: #A855F7;">Admin</span>
        </div>
    </header>

    {{-- =====================
         MAIN CONTENT — only this scrolls
    ===================== --}}
    <main
        class="fixed bottom-0 right-0 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8"
        style="top: 72px; left: 0; background: #05040A;"
        x-bind:style="'top: 72px; left: 0; background: #05040A;'"
    >
        {{-- On desktop, offset left by sidebar width --}}
        <div class="lg:ml-[280px]">

            @if (session('success') || session('status'))
                <div class="mb-5 flex items-center gap-3 rounded-2xl border border-[#22C55E]/30 px-4 py-3 text-sm font-semibold text-[#86EFAC]" style="background: rgba(34,197,94,0.08);">
                    <svg class="h-4 w-4 shrink-0 text-[#22C55E]" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    {{ session('success') ?? session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 flex items-center gap-3 rounded-2xl border border-[#EF4444]/30 px-4 py-3 text-sm font-semibold text-[#FCA5A5]" style="background: rgba(239,68,68,0.08);">
                    <svg class="h-4 w-4 shrink-0 text-[#EF4444]" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}

            {{-- Bottom padding so content doesn't get cut off --}}
            <div class="h-8"></div>
        </div>
    </main>

</div>

<x-confirm-modal />
</body>
</html>
