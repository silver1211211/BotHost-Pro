<x-dashboard-layout title="My Bots">
@php
    $runningCount   = $statusCounts['running']   ?? 0;
    $pausedCount    = $statusCounts['paused']    ?? 0;
    $stoppedCount   = $statusCounts['stopped']   ?? 0;
    $crashedCount   = $statusCounts['crashed']   ?? 0;
    $suspendedCount = $statusCounts['suspended'] ?? 0;

    $limitDisplay = match(true) {
        $botsLimit === null        => null,
        $botsLimit === 'unlimited' => 'Unlimited',
        default                    => number_format((int) $botsLimit),
    };

    $sortLabels = [
        'newest'        => 'Newest First',
        'oldest'        => 'Oldest First',
        'most_commands' => 'Most Commands',
        'most_users'    => 'Most Users',
        'status'        => 'By Status',
    ];

    $filterDefs = [
        'all'       => ['label' => 'All',       'count' => $totalBots,     'color' => '#8B5CF6'],
        'running'   => ['label' => 'Running',   'count' => $runningCount,  'color' => '#22C55E'],
        'paused'    => ['label' => 'Paused',    'count' => $pausedCount,   'color' => '#F59E0B'],
        'stopped'   => ['label' => 'Stopped',   'count' => $stoppedCount,  'color' => '#71717A'],
        'crashed'   => ['label' => 'Crashed',   'count' => $crashedCount,  'color' => '#EF4444'],
        'suspended' => ['label' => 'Suspended', 'count' => $suspendedCount,'color' => '#F97316'],
    ];

    $avatarColors = [
        'from-[#8B5CF6] to-[#229ED9]',
        'from-[#38BDF8] to-[#8B5CF6]',
        'from-[#A855F7] to-[#EF4444]',
        'from-[#22C55E] to-[#38BDF8]',
        'from-[#F59E0B] to-[#A855F7]',
    ];
@endphp

<div
    x-data="{
        view: localStorage.getItem('botView') || 'grid',
        searchTimer: null,
        setView(v)     { this.view = v; localStorage.setItem('botView', v); },
        setFilter(val) {
            document.getElementById('filter-input').value = val;
            document.getElementById('bots-form').submit();
        },
        debounceSearch() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => document.getElementById('bots-form').submit(), 600);
        }
    }"
    class="space-y-5"
>

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black sm:text-3xl text-[#F8FAFC]">My Bots</h1>
            <p class="mt-1 text-sm text-[#71717A]">Manage your Telegram bot workspaces.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            {{-- Plan usage badge --}}
            @if ($limitDisplay !== null)
                <div class="flex items-center gap-1.5 rounded-xl border {{ $limitReached ? 'border-[#EF4444]/35 bg-[#EF4444]/8' : 'border-[#27213D] bg-[#0F0D1A]' }} px-3.5 py-2">
                    <svg class="h-3.5 w-3.5 {{ $limitReached ? 'text-[#EF4444]' : 'text-[#71717A]' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                    </svg>
                    <span class="text-xs">
                        <span class="{{ $limitReached ? 'font-black text-[#EF4444]' : 'font-black text-[#F8FAFC]' }}">{{ $totalBots }}</span>
                        <span class="text-[#4D4868]"> / {{ $limitDisplay }}</span>
                        <span class="ml-1 text-[#71717A]">bots</span>
                    </span>
                </div>
            @endif

            {{-- New Bot / Upgrade --}}
            @if ($limitReached)
                @if (\Illuminate\Support\Facades\Route::has('dashboard.upgrade'))
                    <a href="{{ route('dashboard.upgrade') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-[#EF4444]/40 bg-[#EF4444]/10 px-5 py-2.5 text-sm font-black text-[#EF4444] transition hover:bg-[#EF4444]/18">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/></svg>
                        Upgrade Plan
                    </a>
                @else
                    <span title="Bot limit reached — upgrade your plan"
                          class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl border border-[#27213D] bg-[#0B0918] px-5 py-2.5 text-sm font-black text-[#4D4868] opacity-70">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        New Bot
                    </span>
                @endif
            @else
                <a href="{{ route('bots.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white shadow-[0_0_20px_rgba(139,92,246,0.35)] transition hover:shadow-[0_0_28px_rgba(139,92,246,0.5)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New Bot
                </a>
            @endif
        </div>
    </div>

    {{-- ─── Summary Cards ───────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-2">
        @foreach ([
            ['Total Bots', $totalBots,     '#8B5CF6', 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18'],
            ['Running',    $runningCount,  '#22C55E', 'M5 3l14 9-14 9V3z'],
        ] as [$label, $count, $color, $icon])
            <div class="flex items-center gap-2.5 rounded-xl border border-[#1B172B] bg-[#0B0918] px-3 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg" style="background:{{ $color }}18;color:{{ $color }}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-base font-black text-[#F8FAFC]">{{ number_format($count) }}</p>
                    <p class="truncate text-[10px] font-semibold text-[#71717A]">{{ $label }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ─── Toolbar ─────────────────────────────────────────────────────────── --}}
    <form id="bots-form" method="GET" action="{{ route('bots.index') }}" class="space-y-3">
        <input type="hidden" id="filter-input" name="filter" value="{{ $filter }}">

        {{-- Search + Sort + View toggle --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Search --}}
            <div class="relative min-w-0 flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-[#3D3657]">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0Z"/></svg>
                </span>
                <input
                    name="search"
                    value="{{ $search }}"
                    type="text"
                    placeholder="Search by name, username, or ID…"
                    @input="debounceSearch()"
                    class="h-9 w-full rounded-xl border border-[#1B172B] bg-[#0B0918] pl-9 pr-4 text-sm text-[#F8FAFC] placeholder-[#3D3657] outline-none transition focus:border-[#8B5CF6]/50 focus:ring-1 focus:ring-[#8B5CF6]/20"
                >
            </div>

            {{-- Sort --}}
            <div class="relative" x-data="{ open: false, val: '{{ $sort }}', labels: @js($sortLabels), get label() { return this.labels[this.val] || 'Newest First' } }" @click.away="open = false">
                <input type="hidden" name="sort" :value="val">
                <button type="button" @click="open = !open" class="flex h-9 items-center gap-2 rounded-xl border border-[#1B172B] bg-[#0B0918] px-3 text-sm text-[#A1A1AA] transition hover:border-[#27213D] focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/20' : ''">
                    <span x-text="label" class="whitespace-nowrap"></span>
                    <svg class="h-3 w-3 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute right-0 z-50 mt-1.5 w-44 overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    @foreach ($sortLabels as $sv => $sl)
                    <button type="button" @click="val = '{{ $sv }}'; open = false; $nextTick(() => document.getElementById('bots-form').submit())" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $sv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $sv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $sl }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- View toggle --}}
            <div class="flex items-center gap-1 rounded-xl border border-[#1B172B] bg-[#0B0918] p-1">
                <button type="button" @click="setView('grid')"
                    :class="view === 'grid' ? 'bg-[#151225] text-[#F8FAFC]' : 'text-[#4D4868] hover:text-[#71717A]'"
                    class="rounded-lg p-1.5 transition" title="Grid view">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 16 16">
                        <rect x="1" y="1" width="6" height="6" rx="1"/>
                        <rect x="9" y="1" width="6" height="6" rx="1"/>
                        <rect x="1" y="9" width="6" height="6" rx="1"/>
                        <rect x="9" y="9" width="6" height="6" rx="1"/>
                    </svg>
                </button>
                <button type="button" @click="setView('list')"
                    :class="view === 'list' ? 'bg-[#151225] text-[#F8FAFC]' : 'text-[#4D4868] hover:text-[#71717A]'"
                    class="rounded-lg p-1.5 transition" title="List view">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        {{-- Filter pills --}}
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach ($filterDefs as $val => $info)
                @if ($val === 'all' || $info['count'] > 0 || $filter === $val)
                    <button
                        type="button"
                        @click="setFilter('{{ $val }}')"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold transition"
                        style="{{ $filter === $val
                            ? 'background-color:' . $info['color'] . '22;border-color:' . $info['color'] . '60;color:' . $info['color']
                            : 'border-color:#1B172B;color:#4D4868' }}"
                        onmouseover="{{ $filter !== $val ? "this.style.borderColor='#27213D';this.style.color='#A1A1AA'" : '' }}"
                        onmouseout="{{ $filter !== $val ? "this.style.borderColor='#1B172B';this.style.color='#4D4868'" : '' }}"
                    >
                        {{ $info['label'] }}
                        <span class="rounded-full px-1.5 py-0.5 text-[9px]"
                            style="{{ $filter === $val ? 'background:rgba(255,255,255,0.2)' : 'background:#1B172B' }}">{{ $info['count'] }}</span>
                    </button>
                @endif
            @endforeach

            @if ($search !== '')
                @php
                    $clearParams = array_filter([
                        'filter' => $filter !== 'all' ? $filter : null,
                        'sort'   => $sort !== 'newest' ? $sort : null,
                    ]);
                @endphp
                <a href="{{ route('bots.index', $clearParams) }}"
                   class="inline-flex items-center gap-1 rounded-full border border-[#27213D] px-3 py-1 text-xs text-[#71717A] transition hover:border-[#EF4444]/40 hover:text-[#EF4444]">
                    Clear search
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </a>
            @endif
        </div>
    </form>

    {{-- ─── Content ─────────────────────────────────────────────────────────── --}}
    @if ($bots->count())

        {{-- Grid View ─────────────────────────────────────────────────────── --}}
        <div x-show="view === 'grid'" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($bots as $bot)
                @php
                    $statusColor = match($bot->status) {
                        'running'   => ['#22C55E', '#22C55E18', 'border-[#22C55E]/25'],
                        'paused'    => ['#F59E0B', '#F59E0B18', 'border-[#F59E0B]/25'],
                        'crashed'   => ['#EF4444', '#EF444418', 'border-[#EF4444]/25'],
                        'suspended' => ['#F97316', '#F9731618', 'border-[#F97316]/25'],
                        default     => ['#71717A', '#71717A18', 'border-[#27213D]'],
                    };
                    $grad = $avatarColors[$bot->id % count($avatarColors)];
                @endphp
                <article class="group flex flex-col overflow-hidden rounded-2xl border border-[#1B172B] bg-[#0B0918] transition-all duration-200 hover:border-[#27213D]">

                    {{-- Status colour bar --}}
                    <div class="h-[3px]" style="background:linear-gradient(90deg,{{ $statusColor[0] }}80 0%,{{ $statusColor[0] }}20 100%)"></div>

                    <div class="flex flex-1 flex-col p-4">
                        {{-- Identity --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="relative h-9 w-9 shrink-0">
                                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br {{ $grad }} text-sm font-black text-white">
                                        {{ strtoupper(substr($bot->name, 0, 1)) }}
                                    </div>
                                    <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-[#0B0918]"
                                          style="background:{{ $statusColor[0] }};box-shadow:0 0 6px {{ $statusColor[0] }}88"></span>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-black leading-tight text-[#F8FAFC]">{{ $bot->name }}</h3>
                                    <p class="truncate text-xs text-[#4D4868]">{{ $bot->telegramUsernameLabel() }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5">
                                <span class="rounded-full border px-2.5 py-1 text-[10px] font-black {{ $statusColor[2] }}"
                                      style="color:{{ $statusColor[0] }};background:{{ $statusColor[1] }}">{{ ucfirst($bot->status) }}</span>
                                @if ($bot->token_verified_at)
                                    <span class="rounded-full border border-[#22C55E]/30 bg-[#22C55E]/10 px-2.5 py-1 text-[10px] font-black text-[#22C55E]">Verified</span>
                                @endif
                            </div>
                        </div>

                        <div class="my-3 h-px bg-[#1B172B]"></div>

                        {{-- Quick stats --}}
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div>
                                <p class="text-base font-black text-[#F8FAFC]">{{ number_format($bot->commands_count) }}</p>
                                <p class="text-xs text-[#4D4868]">Commands</p>
                            </div>
                            <div>
                                <p class="text-base font-black text-[#F8FAFC]">{{ number_format($bot->users_count) }}</p>
                                <p class="text-xs text-[#4D4868]">Users</p>
                            </div>
                            <div>
                                <p class="text-base font-black text-[#F8FAFC]">{{ $bot->setup_type === 'template' ? 'TPL' : 'CST' }}</p>
                                <p class="text-xs text-[#4D4868]">Type</p>
                            </div>
                        </div>

                        <div class="mt-2 text-xs text-[#3D3657]">
                            Created {{ $bot->created_at->diffForHumans() }}
                            @if ($bot->last_active_at)
                                · Active {{ $bot->last_active_at->diffForHumans() }}
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2 border-t border-[#1B172B] p-2.5">
                        <a href="{{ route('bots.show', $bot) }}"
                           class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] py-2 text-xs font-black text-white transition hover:shadow-[0_0_16px_rgba(139,92,246,0.4)]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                            Workspace
                        </a>
                        <a href="{{ route('bots.settings.show', $bot) }}"
                           class="flex items-center justify-center rounded-xl border border-[#1B172B] bg-[#151225] px-2.5 py-2 text-[#71717A] transition hover:border-[#27213D] hover:text-[#F8FAFC]"
                           title="Settings">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>
                        <form method="POST" action="{{ route('bots.destroy', $bot) }}">
                            @csrf @method('DELETE')
                            <button
                                type="submit"
                                data-confirm
                                data-confirm-type="warning"
                                data-confirm-title="Move bot to recycle bin?"
                                data-confirm-message="&quot;{{ addslashes($bot->name) }}&quot; will be moved to the recycle bin. You can restore it later."
                                data-confirm-btn="Move to Recycle Bin"
                                class="flex items-center justify-center rounded-xl border border-[#1B172B] bg-[#151225] px-2.5 py-2 text-[#71717A] transition hover:border-[#EF4444]/30 hover:bg-[#EF4444]/8 hover:text-[#EF4444]"
                                title="Delete"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- List View ──────────────────────────────────────────────────────── --}}
        <div x-show="view === 'list'" x-cloak class="overflow-x-auto rounded-2xl border border-[#1B172B]">
            <table class="w-full min-w-[600px] text-sm">
                <thead>
                    <tr class="border-b border-[#1B172B] bg-[#07060F]">
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#3D3657]">Bot</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#3D3657]">Commands</th>
                        <th class="hidden px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#3D3657] sm:table-cell">Users</th>
                        <th class="hidden px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#3D3657] md:table-cell">Created</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#3D3657]">Status</th>
                        <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-[#3D3657]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1B172B] bg-[#0B0918]">
                    @foreach ($bots as $bot)
                        @php
                            $sc   = match($bot->status) {
                                'running'   => ['#22C55E', '#22C55E18'],
                                'paused'    => ['#F59E0B', '#F59E0B18'],
                                'crashed'   => ['#EF4444', '#EF444418'],
                                'suspended' => ['#F97316', '#F9731618'],
                                default     => ['#71717A', '#71717A18'],
                            };
                            $grad = $avatarColors[$bot->id % count($avatarColors)];
                        @endphp
                        <tr class="group transition hover:bg-[#0F0D1A]">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br {{ $grad }} text-sm font-black text-white">
                                        {{ strtoupper(substr($bot->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-black text-[#F8FAFC]">{{ $bot->name }}</p>
                                        <p class="truncate text-xs text-[#4D4868]">{{ $bot->telegramUsernameLabel() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-black text-[#F8FAFC]">{{ number_format($bot->commands_count) }}</span>
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                <span class="font-black text-[#F8FAFC]">{{ number_format($bot->users_count) }}</span>
                            </td>
                            <td class="hidden px-4 py-3 text-xs text-[#4D4868] md:table-cell">
                                {{ $bot->created_at->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-nowrap items-center gap-1.5">
                                    <span class="whitespace-nowrap rounded-full border px-2.5 py-1 text-[10px] font-black"
                                          style="color:{{ $sc[0] }};background:{{ $sc[1] }};border-color:{{ $sc[0] }}40">
                                        {{ ucfirst($bot->status) }}
                                    </span>
                                    @if ($bot->token_verified_at)
                                        <span class="whitespace-nowrap rounded-full border border-[#22C55E]/30 bg-[#22C55E]/10 px-2.5 py-1 text-[10px] font-black text-[#22C55E]">Verified</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('bots.show', $bot) }}"
                                       class="rounded-lg bg-[#8B5CF6]/15 px-3 py-1.5 text-xs font-black text-[#8B5CF6] transition hover:bg-[#8B5CF6]/25">Open</a>
                                    <a href="{{ route('bots.settings.show', $bot) }}"
                                       class="rounded-lg border border-[#1B172B] px-3 py-1.5 text-xs font-semibold text-[#71717A] transition hover:text-[#F8FAFC]">Settings</a>
                                    <form method="POST" action="{{ route('bots.destroy', $bot) }}">
                                        @csrf @method('DELETE')
                                        <button
                                            type="submit"
                                            data-confirm
                                            data-confirm-type="warning"
                                            data-confirm-title="Move bot to recycle bin?"
                                            data-confirm-message="&quot;{{ addslashes($bot->name) }}&quot; will be moved to the recycle bin. You can restore it later."
                                            data-confirm-btn="Move to Recycle Bin"
                                            class="rounded-lg border border-[#1B172B] px-3 py-1.5 text-xs font-semibold text-[#71717A] transition hover:border-[#EF4444]/30 hover:text-[#EF4444]"
                                        >Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($bots->hasPages())
            <div class="flex justify-center">
                {{ $bots->links() }}
            </div>
        @endif

    @elseif ($search !== '' || $filter !== 'all')

        {{-- Search / filter produced no results --}}
        <div class="flex min-h-[200px] items-center justify-center rounded-2xl border border-[#1B172B] bg-[#0B0918] text-center">
            <div>
                <svg class="mx-auto h-8 w-8 text-[#27213D]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0Z"/>
                </svg>
                <p class="mt-3 text-sm font-semibold text-[#A1A1AA]">No bots match your search</p>
                <p class="mt-1 text-xs text-[#71717A]">Try a different name, username, or ID.</p>
                <a href="{{ route('bots.index') }}"
                   class="mt-4 inline-block rounded-xl border border-[#27213D] px-4 py-1.5 text-xs font-semibold text-[#71717A] transition hover:text-[#F8FAFC]">
                    Clear all filters
                </a>
            </div>
        </div>

    @else

        {{-- No bots at all --}}
        <div class="relative overflow-hidden rounded-2xl border border-[#1B172B] bg-[#0B0918] py-16 text-center">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute left-1/2 top-0 h-48 w-48 -translate-x-1/2 rounded-full bg-[#8B5CF6]/8 blur-3xl"></div>
            </div>
            <div class="relative">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-[#8B5CF6]/20 to-[#38BDF8]/15">
                    <svg class="h-8 w-8 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                    </svg>
                </div>
                <h3 class="mt-5 text-xl font-black text-[#F8FAFC]">No bots yet</h3>
                <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-[#71717A]">
                    Create a bot workspace, add your Telegram token, and start building commands.
                </p>
                @if (! $limitReached)
                    <div class="mt-6">
                        <a href="{{ route('bots.create') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-6 py-3 text-sm font-black text-white shadow-[0_0_22px_rgba(139,92,246,0.30)] transition hover:-translate-y-0.5">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Create First Bot
                        </a>
                    </div>
                @endif
                @if ($limitDisplay !== null)
                    <p class="mt-4 text-xs text-[#3D3657]">
                        {{ ucfirst(auth()->user()->subscription_plan ?? 'Free') }} plan ·
                        {{ $limitDisplay === 'Unlimited' ? 'Unlimited bots' : "Up to {$limitDisplay} bots" }}
                    </p>
                @endif
            </div>
        </div>

    @endif

</div>
</x-dashboard-layout>
