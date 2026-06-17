@props(['title' => 'Dashboard'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#05040A">
    <meta name="color-scheme" content="dark">
    @php
        $branding = \App\Support\Branding::assets();
    @endphp
    @include('partials.favicon', ['branding' => $branding])
    @php
        $resolvedSeoPage = \App\Support\Seo::keyForRoute();
        $resolvedSeoOverrides = [];
        $routeTemplate = request()->route('template');

        if ($resolvedSeoPage === 'template_details' && $routeTemplate instanceof \App\Models\BotTemplate) {
            $templateDescription = strip_tags((string) ($routeTemplate->short_description ?: $routeTemplate->description ?: ''));
            $resolvedSeoOverrides = [
                'title' => str($routeTemplate->name)->limit(55, '').' - BotHost Pro',
                'og_title' => $routeTemplate->name.' - BotHost Pro',
                'twitter_title' => $routeTemplate->name.' - BotHost Pro',
            ];

            if (filled($templateDescription)) {
                $resolvedSeoOverrides['meta_description'] = (string) str($templateDescription)->squish()->limit(175, '');
                $resolvedSeoOverrides['og_description'] = $resolvedSeoOverrides['meta_description'];
                $resolvedSeoOverrides['twitter_description'] = $resolvedSeoOverrides['meta_description'];
            }

            if ($routeTemplate->thumbnail_url) {
                $resolvedSeoOverrides['og_image'] = $routeTemplate->thumbnail_url;
            }
        }
    @endphp
    @if(in_array($resolvedSeoPage, ['pricing', 'marketplace', 'template_details', 'support'], true))
        @include('partials.seo', ['pageKey' => $resolvedSeoPage, 'seoOverrides' => $resolvedSeoOverrides])
    @else
        <title>{{ $title }} - {{ config('app.name', 'BotHost Pro') }}</title>
    @endif
    <style>
        html,
        body {
            min-height: 100%;
            background: #05040A;
            color: #F8FAFC;
        }

        body {
            margin: 0;
        }

        .code-page-prepaint {
            background: #050509;
        }

        .command-editor-surface,
        .command-editor-surface textarea,
        .command-editor-surface .cm-editor,
        .command-editor-surface .cm-scroller,
        .command-editor-surface .cm-content,
        .command-editor-surface .cm-gutters {
            background: #080714 !important;
            color: #EEFFFF;
        }

        .command-editor-surface .cm-selectionBackground,
        .command-editor-surface .cm-focused .cm-selectionBackground {
            background: rgba(0, 0, 0, 0.75) !important;
            opacity: 1 !important;
        }

        .command-editor-surface .cm-selectionLayer .cm-selectionBackground {
            background-color: rgba(17, 24, 39, 0.65) !important;
        }

        .command-editor-surface .cm-focused .cm-selectionLayer .cm-selectionBackground {
            background-color: rgba(0, 0, 0, 0.75) !important;
        }

        .command-editor-surface .cm-activeLine {
            background: rgba(0, 0, 0, 0.35) !important;
        }

        .editor-container ::selection,
        .command-editor-surface .cm-content ::selection,
        .command-editor-surface textarea::selection,
        .command-editor-surface ::selection {
            background: rgba(0, 0, 0, 0.75);
            color: #ffffff;
        }

        .command-editor-surface .cm-copy-highlight {
            background: rgba(0, 0, 0, 0.55) !important;
            border-radius: 2px;
            box-decoration-break: clone;
            -webkit-box-decoration-break: clone;
        }

        .command-editor-surface .command-copy-flash .cm-selectionBackground,
        .command-editor-surface .command-copy-flash.cm-focused .cm-selectionBackground {
            background: rgba(0, 0, 0, 0.55) !important;
            opacity: 1 !important;
        }

        .command-editor-surface .command-copy-flash .cm-selectionLayer .cm-selectionBackground,
        .command-editor-surface .command-copy-flash.cm-focused .cm-selectionLayer .cm-selectionBackground {
            background-color: rgba(0, 0, 0, 0.55) !important;
            opacity: 1 !important;
        }

        .command-editor-surface .cm-searchMatch {
            background: rgba(250, 204, 21, 0.28) !important;
            outline: 1px solid rgba(250, 204, 21, 0.45);
        }

        .command-editor-surface .cm-searchMatch-selected {
            background: rgba(250, 204, 21, 0.40) !important;
            outline: 1px solid rgba(250, 204, 21, 0.70);
        }

        .command-editor-surface.command-copy-surface-flash::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 55;
            pointer-events: none;
            background: rgba(0, 0, 0, 0.55);
            box-shadow:
                inset 0 0 0 4px rgba(255, 255, 255, 0.18),
                inset 0 0 90px rgba(0, 0, 0, 0.36);
            animation: command-copy-surface-flash 1.45s ease-out both;
        }

        .command-editor-surface.command-copy-surface-flash::before {
            content: "Copied";
            position: absolute;
            top: 1rem;
            left: 50%;
            z-index: 56;
            transform: translateX(-50%);
            pointer-events: none;
            border-radius: 999px;
            background: #16A34A;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.24), 0 18px 40px rgba(0, 0, 0, 0.45);
            color: #FFFFFF;
            font: 900 12px/1.1 system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            letter-spacing: 0.08em;
            padding: 0.55rem 0.9rem;
            text-transform: uppercase;
            animation: command-copy-label-flash 1.45s ease-out both;
        }

        @keyframes command-copy-surface-flash {
            0% { opacity: 0; }
            12% { opacity: 1; }
            70% { opacity: 0.86; }
            100% { opacity: 0; }
        }

        @keyframes command-copy-label-flash {
            0% { opacity: 0; transform: translate(-50%, -0.45rem) scale(0.96); }
            14% { opacity: 1; transform: translate(-50%, 0) scale(1); }
            78% { opacity: 1; transform: translate(-50%, 0) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -0.25rem) scale(0.98); }
        }

        .command-editor-surface textarea {
            border: 0;
            caret-color: #8B5CF6;
            outline: 0;
        }

        .command-editor-surface textarea::selection {
            background: rgba(0, 0, 0, 0.75);
            color: #ffffff;
        }

        @media (max-width: 767px) {
            html:has(.command-code-editor-shell),
            body:has(.command-code-editor-shell) {
                height: 100%;
                overflow: hidden !important;
                overscroll-behavior: none;
            }

            .command-code-editor-shell {
                position: fixed !important;
                inset: 0 !important;
                z-index: 9998 !important;
                width: 100vw !important;
                height: var(--command-editor-visual-height, 100dvh) !important;
                margin: 0 !important;
                transform: none !important;
                overscroll-behavior: none;
            }

            .command-code-editor-shell .command-editor-surface {
                min-height: 0;
                overflow: hidden;
                overscroll-behavior: contain;
                touch-action: pan-y;
            }

            .command-code-editor-shell .cm-editor,
            .command-code-editor-shell .cm-scroller {
                height: 100%;
                max-height: 100%;
                overscroll-behavior: contain;
            }

            .command-code-editor-shell .cm-content {
                -webkit-user-select: text;
                user-select: text;
            }
        }
    </style>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        .nav-scroll::-webkit-scrollbar { width: 3px; }
        .nav-scroll::-webkit-scrollbar-track { background: transparent; }
        .nav-scroll::-webkit-scrollbar-thumb { background: #1B172B; border-radius: 4px; }
        @keyframes bell-ring {
            0%, 100% { transform: rotate(0deg); }
            10%, 30%, 50%, 70% { transform: rotate(16deg); }
            20%, 40%, 60%, 80% { transform: rotate(-16deg); }
        }
        .bell-ringing svg { animation: bell-ring 1.2s ease-in-out infinite; transform-origin: 50% 8%; }
    </style>
</head>
<body class="bg-[#05040A] font-sans text-[#F8FAFC] antialiased overflow-x-hidden">

@php
    $navGroups = [
        'Main' => [
            ['Dashboard',     'dashboard',          'dashboard',          'M3.75 13.5 12 5.25l8.25 8.25M5.25 12.75v6a1.5 1.5 0 0 0 1.5 1.5h10.5a1.5 1.5 0 0 0 1.5-1.5v-6M9 20.25v-5.25h6v5.25'],
            ['My Bots',       'bots.index',         'bots.*',             'M7.5 4.5h9l3 5.25-7.5 9.75-7.5-9.75 3-5.25ZM4.5 9.75h15M9.75 4.5 7.5 9.75 12 19.5m2.25-15 2.25 5.25L12 19.5'],
            ['Templates',     'dashboard.templates.index', 'dashboard.templates.*', 'M5.25 4.5h5.25v5.25H5.25V4.5Zm8.25 0h5.25v5.25H13.5V4.5ZM5.25 14.25h5.25v5.25H5.25v-5.25Zm8.25 0h5.25v5.25H13.5v-5.25Z'],
            ['Upgrade',       'dashboard.upgrade',  'dashboard.upgrade',  'M12 3.75 14.25 9l5.25.45-3.975 3.45 1.2 5.1L12 15.3 7.275 18l1.2-5.1L4.5 9.45 9.75 9 12 3.75Z'],
            ['Transfers',     'transfers.index',    'transfers.*',        'M7.5 7.5h12m0 0-3.75-3.75M19.5 7.5l-3.75 3.75M16.5 16.5h-12m0 0 3.75-3.75M4.5 16.5l3.75 3.75'],
            ['Recycle Bin',   'recycle-bin.index',  'recycle-bin.*',      'M6.75 7.5h10.5m-9.75 0 .6 11.25a1.5 1.5 0 0 0 1.5 1.425h4.8a1.5 1.5 0 0 0 1.5-1.425l.6-11.25M9.75 7.5V5.25a1.5 1.5 0 0 1 1.5-1.5h1.5a1.5 1.5 0 0 1 1.5 1.5V7.5M10.5 11.25v5.25m3-5.25v5.25'],
        ],
        'Tools' => [
            ['Notifications', 'notifications.index', 'notifications.*',  'M15.75 17.25h-7.5m7.5 0 1.5 1.5H6.75l1.5-1.5m7.5 0V12a3.75 3.75 0 0 0-7.5 0v5.25M12 4.5v1.125M10.5 20.25h3'],
            ['AI Help',       'ai-help.index',       'ai-help.*',        'M12 3.75 13.125 9l4.875 1.125-4.875 1.125L12 16.5l-1.125-5.25L6 10.125 10.875 9 12 3.75Zm5.25 10.5.75 3.375 3.375.75-3.375.75-.75 3.375-.75-3.375-3.375-.75 3.375-.75.75-3.375Z'],
        ],
        'Account' => [
            ['Settings',      'profile.edit',        'profile.*',        'M9.75 4.5h4.5l.75 2.25 2.25 1.125 2.25-.75 2.25 3.75-1.5 1.875v2.25l1.5 1.875-2.25 3.75-2.25-.75L15 21l-.75 2.25h-4.5L9 21l-2.25-1.125-2.25.75-2.25-3.75 1.5-1.875v-2.25L2.25 10.875 4.5 7.125l2.25.75L9 6.75 9.75 4.5ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z'],
            ['Help',          'help.index',          'help.*',           'M12 20.25a8.25 8.25 0 1 0 0-16.5 8.25 8.25 0 0 0 0 16.5ZM9.75 9.75A2.25 2.25 0 0 1 12 7.5c1.35 0 2.25.825 2.25 1.95 0 1.425-1.05 1.875-1.875 2.325-.45.225-.75.6-.75 1.225M12 16.5h.007'],
            ['Support',       'support.index',       'support.*',        'M12 20.25a8.25 8.25 0 1 0 0-16.5 8.25 8.25 0 0 0 0 16.5Zm-3-8.25 3-3 3 3m-3-3v6'],
        ],
    ];
    $user = auth()->user();
    $plan = ucfirst($user->subscription_plan ?? 'Free');
    $unreadNotifications = \App\Models\UserNotification::query()
        ->where('user_id', $user->id)
        ->where('status', 'unread')
        ->count();
    $platformAnnouncements = \App\Models\PlatformAnnouncement::query()
        ->with('broadcast')
        ->where('is_active', true)
        ->where(function ($query) {
            $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })
        ->where(function ($query) {
            $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        })
        ->latest()
        ->limit(10)
        ->get()
        ->filter(function ($announcement) use ($user) {
            $target = $announcement->broadcast?->target_type ?: 'all_users';

            return match ($target) {
                'active_users' => $user->status === 'active',
                'free_users' => in_array($user->subscription_plan, ['free', null, ''], true),
                'pro_users' => $user->subscription_plan === 'pro',
                'business_users' => $user->subscription_plan === 'business',
                'admin_users' => $user->isAdmin(),
                'new_today' => $user->created_at?->isToday() === true,
                'new_7d' => $user->created_at && $user->created_at->gte(now()->subDays(7)),
                'new_30d' => $user->created_at && $user->created_at->gte(now()->subDays(30)),
                'users_with_bots' => \App\Models\Bot::query()->where('user_id', $user->id)->exists(),
                'users_without_bots' => ! \App\Models\Bot::query()->where('user_id', $user->id)->exists(),
                default => true,
            };
        })
        ->take(3);
@endphp

<div x-data="{ open: false }">

    {{-- Mobile overlay --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden"
        @click="open = false"
        x-cloak
    ></div>

    {{-- ─── Sidebar ──────────────────────────────────────────────────────── --}}
    <aside
        class="fixed inset-y-0 left-0 z-40 flex h-screen w-[260px] flex-col bg-[#07060F] transition-transform duration-300 ease-in-out -translate-x-full lg:translate-x-0"
        :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        style="border-right: 1px solid rgba(39,33,61,0.8);"
    >
        {{-- Top glow accent --}}
        <div class="pointer-events-none absolute left-0 top-0 h-64 w-full overflow-hidden">
            <div class="absolute -left-8 -top-8 h-40 w-40 rounded-full bg-[#8B5CF6]/12 blur-3xl"></div>
        </div>

        {{-- Logo --}}
        <div class="relative flex shrink-0 items-center justify-between px-4 py-4">
            <a href="{{ route('dashboard') }}" class="group flex items-center gap-3">
                @if($branding['platform_logo_url'])
                    <img src="{{ $branding['platform_logo_url'] }}" alt="{{ $branding['platform_name'] }}" class="h-10 w-auto max-w-[160px] shrink-0 object-contain">
                @else
                    <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#8B5CF6] to-[#229ED9] shadow-[0_0_22px_rgba(139,92,246,0.5)]">
                        <svg class="h-4.5 w-4.5 text-white" style="height:18px;width:18px" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.75 12 14.5-7.25-3.2 14.5-4.12-4.18-3.08 3.18.45-4.55L4.75 12Z"/></svg>
                    </div>
                    <div class="leading-none">
                        <span class="text-[15px] font-black tracking-tight text-[#F8FAFC]">BotHost</span>
                        <span class="text-[15px] font-black bg-gradient-to-r from-[#38BDF8] to-[#8B5CF6] bg-clip-text text-transparent"> Pro</span>
                    </div>
                @endif
            </a>
            <button type="button" class="grid h-7 w-7 place-items-center rounded-lg text-[#94A3B8] transition hover:text-white lg:hidden" @click="open = false" aria-label="Close sidebar">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- User chip --}}
        <div class="relative mx-4 mb-3">
            <div class="flex items-center gap-2.5 rounded-xl border border-[#1B172B] bg-[#0F0D1A] px-3 py-2">
                <div class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-[#8B5CF6] to-[#A855F7] text-xs font-black text-white shadow-[0_0_12px_rgba(139,92,246,0.4)]">
                    {{ strtoupper(substr($user->username ?? $user->name ?? 'U', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-black text-[#F8FAFC]">{{ $user->username ?? $user->name ?? 'User' }}</p>
                    <p class="text-[10px] text-[#94A3B8]">{{ $plan }} Plan</p>
                </div>
                <span class="h-2 w-2 shrink-0 rounded-full bg-[#22C55E] shadow-[0_0_6px_rgba(34,197,94,0.7)]"></span>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="nav-scroll relative flex-1 overflow-y-auto px-3 pb-2 pt-1">
            @foreach ($navGroups as $groupLabel => $items)
                <p class="mb-2 mt-4 px-2 text-[9px] font-black uppercase tracking-[0.18em] text-[#514B6E]">{{ $groupLabel }}</p>
                @foreach ($items as [$label, $route, $active, $icon])
                    @php $isActive = request()->routeIs($active); @endphp
                    <a
                        href="{{ route($route) }}"
                        class="group relative mb-0.5 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-bold transition-all duration-200 {{ $isActive ? 'text-white' : 'text-[#8C88AD] hover:bg-[#0F0D1A]/60 hover:text-[#C4C0D8]' }}"
                        @if ($isActive) style="background:linear-gradient(90deg,rgba(139,92,246,0.16) 0%,rgba(139,92,246,0.04) 100%)" @endif
                        @click="open = false"
                    >
                        {{-- Active left bar --}}
                        @if ($isActive)
                            <span class="absolute inset-y-2 left-0 w-[3px] rounded-r-full bg-gradient-to-b from-[#8B5CF6] to-[#A855F7]"></span>
                        @endif

                        {{-- Icon --}}
                        @if ($isActive)
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#8B5CF6] to-[#6D28D9] text-white shadow-[0_0_14px_rgba(139,92,246,0.40)]">
                                <svg class="h-[17px] w-[17px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            </span>
                        @else
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center text-[#7A77A0] transition-colors group-hover:text-[#A855F7]">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            </span>
                        @endif

                        <span class="flex-1 text-[13px] font-{{ $isActive ? 'black' : 'semibold' }}">{{ $label }}</span>

                        @if ($isActive)
                            <span class="h-2 w-2 rounded-full bg-[#8B5CF6] shadow-[0_0_6px_rgba(139,92,246,0.9)]"></span>
                        @endif
                    </a>
                @endforeach
            @endforeach
        </nav>

        {{-- Bottom --}}
        <div class="relative shrink-0 px-3 pb-4 pt-2">
            <div class="h-px bg-gradient-to-r from-transparent via-[#1B172B] to-transparent mb-3"></div>

            {{-- Plan upgrade nudge --}}
            @if (strtolower($plan) === 'free')
                <div class="mb-3 overflow-hidden rounded-xl border border-[#8B5CF6]/20 bg-gradient-to-br from-[#8B5CF6]/10 to-[#0F0D1A] p-3">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="h-3.5 w-3.5 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <p class="text-[10px] font-black uppercase tracking-wide text-[#8B5CF6]">Upgrade to Pro</p>
                    </div>
                    <p class="text-[11px] leading-relaxed text-[#94A3B8]">Unlock more bots, commands & premium features.</p>
                    <a href="{{ route('dashboard.upgrade') }}" class="mt-3 block rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] py-2 text-center text-[11px] font-black text-white shadow-[0_0_18px_rgba(139,92,246,0.35)]">Upgrade Now</a>
                </div>
            @else
                <div class="mb-3 flex items-center justify-between rounded-xl border border-[#1B172B] bg-[#0F0D1A] px-3 py-2">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wide text-[#94A3B8]">Plan</p>
                        <p class="text-sm font-black text-[#F8FAFC]">{{ $plan }}</p>
                    </div>
                    <span class="rounded-full border border-[#22C55E]/30 bg-[#22C55E]/10 px-2.5 py-1 text-[10px] font-black text-[#22C55E]">Active</span>
                </div>
            @endif

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="group flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-semibold text-[#7A77A0] transition hover:bg-[#EF4444]/8 hover:text-[#EF4444]"
                >
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-[#7A77A0] transition group-hover:bg-[#EF4444]/15 group-hover:text-[#EF4444]">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </span>
                    Sign Out
                </button>
            </form>
        </div>
    </aside>

    {{-- ─── Main ─────────────────────────────────────────────────────────── --}}
    <div class="flex min-h-screen flex-col lg:ml-[260px]">

        {{-- Topbar --}}
        <header class="sticky top-0 z-30 shrink-0 border-b border-[#27213D]/60 bg-[#07060F]/90 backdrop-blur-2xl">
            <div class="flex h-[54px] items-center justify-between gap-3 px-4 sm:px-6">

                {{-- Left --}}
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-[#27213D]/60 text-[#94A3B8] transition hover:text-white lg:hidden"
                        @click="open = true"
                        aria-label="Open sidebar"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>

                    {{-- Breadcrumb-style title --}}
                    <div class="flex min-w-0 items-center gap-2">
                        <a href="{{ route('dashboard') }}" class="hidden transition hover:opacity-80 sm:block">
                            @if($branding['platform_logo_url'])
                                <img src="{{ $branding['platform_logo_url'] }}" alt="{{ $branding['platform_name'] }}" class="h-5 w-auto max-w-[90px] object-contain">
                            @else
                                <span class="text-xs text-[#6E6A90] transition hover:text-[#94A3B8]">BotHost</span>
                            @endif
                        </a>
                        <svg class="hidden h-3 w-3 shrink-0 text-[#514B6E] sm:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                        <h1 class="truncate text-sm font-black text-[#F8FAFC]">{{ $title }}</h1>
                    </div>
                </div>

                {{-- Right --}}
                <div class="flex shrink-0 items-center gap-1.5">
                    {{-- Search --}}
                    <div class="relative hidden md:block">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                            <svg class="h-3 w-3 text-[#6B6890]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"/></svg>
                        </span>
                        <input
                            type="text"
                            placeholder="Search…"
                            class="h-8 w-40 rounded-xl border border-[#1B172B] bg-[#0F0D1A] pl-8 pr-3 text-xs text-[#F8FAFC] placeholder-[#3D3657] outline-none transition focus:border-[#8B5CF6]/50 focus:ring-1 focus:ring-[#8B5CF6]/20 xl:w-52"
                        >
                    </div>

                    {{-- Notification bell --}}
                    <a href="{{ route('notifications.index') }}" data-notification-bell="{{ $unreadNotifications > 0 ? 'ringing' : 'idle' }}" class="relative grid h-8 w-8 place-items-center rounded-xl border border-[#1B172B] bg-[#0F0D1A] text-[#7E7AA0] transition hover:border-[#38BDF8]/40 hover:text-[#38BDF8] {{ $unreadNotifications > 0 ? 'bell-ringing border-[#EF4444]/35 text-[#EF4444]' : '' }}" aria-label="Notifications">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if ($unreadNotifications > 0)
                            <span class="absolute inset-0 rounded-xl bg-[#EF4444]/10 animate-ping"></span>
                            <span class="absolute -right-1 -top-1 min-w-4 rounded-full bg-[#EF4444] px-1 text-center text-[9px] font-black text-white">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                        @endif
                    </a>

                    {{-- Divider --}}
                    <div class="mx-1 h-5 w-px bg-[#1B172B]"></div>

                    {{-- Avatar + name --}}
                    <div class="flex items-center gap-2">
                        <div class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-[#8B5CF6] to-[#A855F7] text-xs font-black text-white shadow-[0_0_14px_rgba(139,92,246,0.4)]">
                            {{ strtoupper(substr($user->username ?? $user->name ?? 'U', 0, 1)) }}
                        </div>
                        <span class="hidden text-xs font-semibold text-[#A1A1AA] sm:block">{{ Str::limit($user->username ?? $user->name ?? 'User', 14) }}</span>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if (! ($noFlash ?? false) && (session('status') || session('success') || session('error')))
            <div class="px-4 pt-4 sm:px-6">
                @if (session('status'))
                    <div class="mb-2 flex items-center gap-3 rounded-2xl border border-[#38BDF8]/25 bg-[#38BDF8]/8 px-4 py-3 text-sm font-semibold text-[#38BDF8]">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                        {{ session('status') }}
                    </div>
                @endif
                @if (session('success'))
                    <div class="mb-2 flex items-center gap-3 rounded-2xl border border-[#22C55E]/25 bg-[#22C55E]/8 px-4 py-3 text-sm font-semibold text-[#22C55E]">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-2 flex items-center gap-3 rounded-2xl border border-[#EF4444]/25 bg-[#EF4444]/8 px-4 py-3 text-sm font-semibold text-[#EF4444]">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                        {{ session('error') }}
                    </div>
                @endif
            </div>
        @endif

        {{-- Page Content --}}
        <main class="flex-1 px-4 py-5 sm:px-5">
            @if ($platformAnnouncements->isNotEmpty())
                <div class="mb-5 space-y-2">
                    @foreach ($platformAnnouncements as $announcement)
                        <div class="rounded-2xl border border-[#8B5CF6]/25 bg-[#8B5CF6]/8 px-4 py-3">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-black text-[#F8FAFC]">{{ $announcement->title }}</p>
                                        <span class="rounded-full border border-[#27213D] px-2 py-0.5 text-[10px] font-bold text-[#A855F7]">{{ ucfirst($announcement->priority ?: 'normal') }}</span>
                                    </div>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-[#A1A1AA]">{{ $announcement->message }}</p>
                                </div>
                                <span class="text-[10px] font-black uppercase tracking-wider text-[#8B5CF6]">Platform</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="shrink-0 border-t border-[#1B172B]/60 px-4 py-4 sm:px-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-[11px] text-[#8D89B0]">{{ $branding['footer_text'] }}</p>
                <div class="flex items-center gap-4 text-[11px] text-[#B4AFDB]">
                    <a href="{{ route('legal.privacy') }}" class="transition-colors duration-200 hover:text-[#C4B5FD]">Privacy</a>
                    <a href="{{ route('legal.terms') }}" class="transition-colors duration-200 hover:text-[#C4B5FD]">Terms</a>
                    <a href="{{ route('support.index') }}" class="transition-colors duration-200 hover:text-[#C4B5FD]">Support</a>
                </div>
            </div>
        </footer>
    </div>
</div>

<x-confirm-modal />
</body>
</html>
