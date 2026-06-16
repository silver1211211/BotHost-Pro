<x-dashboard-layout :title="$bot->name">
    @php
        $tabs = [
            'intro'    => ['Intro',    'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'],
            'commands' => ['Commands', 'M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z'],
            'search'   => ['Search',   'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z'],
            'errors'   => ['Errors',   'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
            'logs'     => ['Logs',     'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
            'manage'   => ['Manage',   'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z'],
            'admin'    => ['Admin',    'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
            'settings' => ['Settings', 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.107-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.764-.384.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
        ];
        $defaultTab   = array_key_exists($activeTab ?? '', $tabs) ? $activeTab : 'intro';
        $commandCount = $bot->commands->count();
        $errorLogs = $bot->logs->whereIn('type', ['error', 'runtime'])->take(50);
        $commandLogs = $bot->commandLogs->take(50);
        $logEntryCount = $commandLogs->count();
        $protectedCommandCount = $bot->commands->filter(fn ($cmd) => filled($cmd->source_template_id) || filled($cmd->source_template_purchase_id) || $cmd->license_locked || in_array($cmd->source, ['template', 'marketplace', 'pro_template', 'business_template'], true))->count();
        $exportableCommandCount = $bot->commands->count();
        $botUserAnalytics = $botUserAnalytics ?? [];
        $botUsers         = $botUsers ?? collect();
        $botUserLanguages = $botUserLanguages ?? [];
        $botUserFilters   = $botUserFilters ?? ['search' => '', 'status' => 'all'];
        $botBroadcasts    = $botBroadcasts ?? collect();
        $broadcastTargetCounts = $broadcastTargetCounts ?? [];
        $totalUsers       = $botUserAnalytics['total_users'] ?? 0;
        $adminTabs = ['analytics', 'users', 'broadcasts', 'botData', 'userData', 'permissions'];
        $defaultAdminTab = in_array(request('admin_tab'), $adminTabs, true)
            ? request('admin_tab')
            : ($adminSubTab ?? (isset($selectedBroadcast) ? 'broadcasts' : 'analytics'));
        if (! in_array($defaultAdminTab, $adminTabs, true)) {
            $defaultAdminTab = 'analytics';
        }
    @endphp

    <div class="mx-auto max-w-[1280px]" x-data="{
            tab: @js($defaultTab),
            adminTab: @js($defaultAdminTab),
            logFilter: 'all',
            broadcastOpen: false,
            cmdSearch: '',
            tokenVisible: false,
            searchQuery: '',
            codeEditorOpen: false,
            codeEditorPayload: null,
            codeEditorCache: {},
            setTab(nextTab) {
                this.tab = nextTab;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', nextTab);
                if (nextTab !== 'admin') url.searchParams.delete('admin_tab');
                window.history.replaceState(null, '', url.toString());
            },
            setAdminTab(nextAdminTab) {
                this.tab = 'admin';
                this.adminTab = nextAdminTab;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', 'admin');
                url.searchParams.set('admin_tab', nextAdminTab);
                window.history.replaceState(null, '', url.toString());
            },
            openCodeEditor(payload) {
                const hasCachedCode = Object.prototype.hasOwnProperty.call(this.codeEditorCache, payload.id);
                const cached = hasCachedCode ? this.codeEditorCache[payload.id] : '';
                this.codeEditorPayload = { ...payload, code: cached, codeLoaded: hasCachedCode };
                this.codeEditorOpen = true;
                document.documentElement.classList.add('overflow-hidden');
            },
            closeCodeEditor() {
                this.codeEditorOpen = false;
                this.codeEditorPayload = null;
                document.documentElement.classList.remove('overflow-hidden');
            }
        }"
        x-init="$nextTick(() => {
            const canPreload = !window.matchMedia('(max-width: 700px)').matches && !navigator.connection?.saveData;
            if (!canPreload) return;
            const preload = () => window.BotHostPreloadCommandEditor?.();
            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(preload, { timeout: 5000 });
            } else {
                window.setTimeout(preload, 2500);
            }
        })"
        x-on:bot-code-editor-close.window="closeCodeEditor()"
        x-on:bot-code-editor-saved.window="if ($event.detail?.id) codeEditorCache[$event.detail.id] = $event.detail.code ?? ''"
        x-on:bot-code-editor-loaded.window="if ($event.detail?.id) codeEditorCache[$event.detail.id] = $event.detail.code ?? ''"
    >
        {{-- ══ WORKSPACE HEADER ══ --}}
        <div class="mb-4 flex min-w-0 items-center justify-between gap-3 overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-3.5">
            <div class="flex min-w-0 items-center gap-3">
                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#8B5CF6] to-[#5B21B6] text-sm font-black text-white">
                    {{ strtoupper(substr($bot->name, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <h1 class="truncate text-sm font-black text-[#F8FAFC]">{{ $bot->name }}</h1>
                        <x-status-badge :status="$bot->status" />
                        @if($bot->token_verified_at)
                            <span class="inline-flex items-center gap-1 rounded-full border border-[#22C55E]/35 bg-[#22C55E]/10 px-2 py-0.5 text-[10px] font-black text-[#22C55E]">
                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Verified
                            </span>
                        @endif
                    </div>
                    @if($bot->telegram_username)
                        <p class="mt-0.5 font-mono text-[11px] text-[#52525B]">{{ '@'.$bot->telegram_username }}</p>
                    @endif
                </div>
            </div>
            <div class="hidden shrink-0 items-center divide-x divide-[#1B172B] sm:flex">
                @foreach([
                    ['COMMANDS', $commandCount],
                    ['USERS',    $totalUsers],
                    ['LANGUAGE', ucfirst($bot->language ?? 'JavaScript')],
                ] as [$statLabel, $statValue])
                    <div class="px-4 text-center">
                        <p class="text-[9px] font-black uppercase tracking-widest text-[#94A3B8]">{{ $statLabel }}</p>
                        <p class="mt-0.5 text-sm font-black text-[#F8FAFC]">{{ $statValue }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tab bar --}}
        <nav class="overflow-x-auto rounded-xl border border-[#27213D] bg-[#0B0A15] p-1" style="scrollbar-width:none">
            <div class="flex min-w-max items-center gap-1.5">
                @foreach ($tabs as $key => [$label, $icon])
                    <button
                        type="button"
                        @click="setTab('{{ $key }}')"
                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-black transition sm:gap-2 sm:px-3.5 sm:py-2"
                        :class="tab === '{{ $key }}'
                            ? 'bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] text-white shadow-[0_0_22px_rgba(139,92,246,0.32)]'
                            : 'text-[#94A3B8] hover:bg-[#151225] hover:text-[#F8FAFC]'"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        {{ $label }}
                        @if ($key === 'commands' && $commandCount)
                            <span class="rounded-full px-1.5 py-0.5 text-[10px] transition" :class="tab === 'commands' ? 'bg-white/20' : 'bg-[#27213D] text-[#A1A1AA]'">{{ $commandCount }}</span>
                        @endif
                        @if ($key === 'errors' && $errorLogs->isNotEmpty())
                            <span class="rounded-full px-1.5 py-0.5 text-[10px] transition" :class="tab === 'errors' ? 'bg-white/20' : 'bg-[#EF4444]/20 text-[#EF4444]'">{{ $errorLogs->count() }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </nav>

        <div class="mt-5">

            {{-- ══════════════════════════════════════════════════ INTRO ══ --}}
            <section x-show="tab === 'intro'" x-cloak class="space-y-4">

                {{-- Identity Card --}}
                <div class="flex flex-col gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#8B5CF6] to-[#5B21B6] text-lg font-black text-white shadow-[0_0_18px_rgba(139,92,246,0.32)]">
                            {{ strtoupper(substr($bot->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-black text-[#F8FAFC]">{{ $bot->name }}</h2>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <x-status-badge :status="$bot->status" />
                                @if ($bot->telegram_username)
                                    <span class="rounded-full border border-[#229ED9]/30 bg-[#229ED9]/10 px-2 py-0.5 font-mono text-[10px] font-black text-[#229ED9]">{{ '@'.$bot->telegram_username }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if ($bot->status === 'running')
                            <form method="POST" action="{{ route('bots.stop', $bot) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-[#EF4444]/35 bg-[#EF4444]/10 px-3.5 py-2 text-xs font-black text-[#EF4444] transition hover:bg-[#EF4444]/15">
                                    <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M6.75 5.25A1.5 1.5 0 0 0 5.25 6.75v10.5a1.5 1.5 0 0 0 1.5 1.5h10.5a1.5 1.5 0 0 0 1.5-1.5V6.75a1.5 1.5 0 0 0-1.5-1.5H6.75Z"/></svg>
                                    Stop
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('bots.activate', $bot) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-[#22C55E] px-3.5 py-2 text-xs font-black text-white transition hover:bg-[#16A34A]">
                                    <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5.14v13.72a1 1 0 0 0 1.54.84l10.6-6.86a1 1 0 0 0 0-1.68L9.54 4.3A1 1 0 0 0 8 5.14Z"/></svg>
                                    Activate
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Details grid --}}
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['Telegram Username', $bot->telegram_username ? '@'.$bot->telegram_username : 'Not verified', '#229ED9'],
                        ['Telegram Bot ID',   $bot->telegram_bot_id ?? 'Not verified',                               '#38BDF8'],
                        ['Token Status',      $bot->tokenStatusLabel(),                                               '#22C55E'],
                        ['Created',           $bot->created_at->format('M j, Y'),                                    '#71717A'],
                        ['Language',          'JavaScript · Node.js',                                                 '#8B5CF6'],
                    ] as [$label, $value, $color])
                        <div class="rounded-xl border border-[#27213D] bg-[#0F0D1A] p-3.5">
                            <p class="text-[10px] font-black uppercase tracking-[0.12em] text-[#94A3B8]">{{ $label }}</p>
                            <p class="mt-1 break-words text-sm font-black" style="color:{{ $color }}">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Overview stats (4 large cards) --}}
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                        $introStats = [
                            ['Commands',   $commandCount,                             '#8B5CF6', 'rgba(139,92,246,0.18)', 'M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z'],
                            ['Bot Users',  $totalUsers,                              '#38BDF8', 'rgba(56,189,248,0.14)', 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
                            ['Active 24h', $botUserAnalytics['active_24h'] ?? 0,    '#22C55E', 'rgba(34,197,94,0.14)',  'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                            ['Errors',     $errorLogs->count(),                      $errorLogs->isNotEmpty() ? '#EF4444' : '#22C55E', $errorLogs->isNotEmpty() ? 'rgba(239,68,68,0.14)' : 'rgba(34,197,94,0.08)', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
                        ];
                    @endphp
                    @foreach ($introStats as [$label, $value, $color, $glow, $icon])
                        <div class="rounded-xl border border-[#27213D] bg-[#0F0D1A] p-4">
                            <div class="flex items-center gap-2.5">
                                <div class="grid h-7 w-7 shrink-0 place-items-center rounded-lg border border-[#27213D] bg-[#151225]" style="color:{{ $color }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                </div>
                                <p class="text-[9px] font-black uppercase tracking-[0.12em] text-[#94A3B8]">{{ $label }}</p>
                            </div>
                            <p class="mt-2.5 text-2xl font-black" style="color:{{ $color }}">{{ number_format($value) }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Quick Actions --}}
                <div class="grid grid-cols-2 gap-3">
                    @foreach ([
                        ['logs',   'Logs',   '#38BDF8', 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z', 'View activity'],
                        ['errors', 'Errors', '#EF4444', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',          'Check errors'],
                    ] as [$tabKey, $tabLabel, $color, $icon, $sub])
                        <button
                            type="button"
                            @click="setTab('{{ $tabKey }}')"
                            class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#0F0D1A] p-3.5 text-left transition hover:border-[#27213D]/60"
                        >
                            <div class="grid h-7 w-7 shrink-0 place-items-center rounded-lg border border-[#27213D] bg-[#151225]" style="color:{{ $color }}">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-[#F8FAFC]">{{ $tabLabel }}</p>
                                <p class="text-[10px] text-[#94A3B8]">{{ $sub }}</p>
                            </div>
                        </button>
                    @endforeach
                </div>
            </section>

            {{-- ═══════════════════════════════════════════════ COMMANDS ══ --}}
            <section x-show="tab === 'commands'" x-cloak x-init="@if(old('_cmd_modal') == '1') $nextTick(() => $dispatch('open-modal', 'cmd-create')) @endif" class="space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    {{-- Search: full width on mobile --}}
                    <div class="flex min-w-0 flex-1 items-center gap-2.5 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3.5 py-2.5 focus-within:border-[#8B5CF6]/50 focus-within:ring-2 focus-within:ring-[#8B5CF6]/15 transition">
                        <svg class="h-[15px] w-[15px] shrink-0 text-[#94A3B8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        <input x-model="cmdSearch" class="w-full border-0 bg-transparent p-0 text-[13px] text-[#F8FAFC] placeholder:text-[#71717A] focus:ring-0" placeholder="Filter commands...">
                        <button x-show="cmdSearch" x-cloak @click="cmdSearch = ''" class="shrink-0 text-[#94A3B8] hover:text-[#F8FAFC]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    {{-- Buttons: side by side --}}
                    <div class="flex shrink-0 gap-2">
                        <button type="button" @click="$dispatch('open-modal', 'cmd-create')" class="flex items-center gap-1.5 whitespace-nowrap rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-3.5 py-2.5 text-[13px] font-black text-white shadow-[0_0_20px_rgba(139,92,246,0.28)] transition hover:-translate-y-0.5">
                            <svg class="h-[15px] w-[15px] shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            New Command
                        </button>
                        <a href="{{ route('bots.templates.index', $bot) }}" class="flex items-center gap-1.5 whitespace-nowrap rounded-xl border border-[#27213D] bg-[#151225] px-3.5 py-2.5 text-[13px] font-black text-[#A1A1AA] transition hover:text-[#F8FAFC]">
                            Use Template
                        </a>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A] shadow-[0_18px_70px_rgba(0,0,0,0.22)]">
                    @if ($bot->commands->isNotEmpty())
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#1B172B] px-4 py-2.5">
                            <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">{{ $commandCount }} {{ Str::plural('command', $commandCount) }} stored</p>
                            <div class="hidden items-center gap-2 text-[10px] font-black uppercase tracking-wider text-[#6B6890] md:flex">
                                <span class="w-28 text-center">State</span>
                                <span class="w-40 text-center">Actions</span>
                            </div>
                        </div>
                        <div class="divide-y divide-[#1B172B]">
                            @foreach ($bot->commands as $command)
                                @php
                                    $accentColor = match($command->status ?? 'active') {
                                        'active'   => '#8B5CF6',
                                        'inactive' => '#F59E0B',
                                        default    => '#52525B',
                                    };
                                    $triggerType = $command->effectiveTriggerType();
                                    $triggerLabel = match($triggerType) {
                                        'direct_message' => 'Direct Message',
                                        default => 'Command',
                                    };
                                @endphp
                                <div
                                    class="group relative grid gap-3 px-4 py-3 transition-all duration-200 hover:bg-[#151225]/80 lg:grid-cols-[minmax(0,1fr)_240px_210px] lg:items-center"
                                    x-show="!cmdSearch || @js(strtolower($command->displayName().' '.$command->command_name)).includes(cmdSearch.toLowerCase())"
                                >
                                    {{-- Status accent bar --}}
                                    <div class="pointer-events-none absolute left-0 top-0 h-full w-[3px] rounded-r opacity-0 transition-opacity duration-200 group-hover:opacity-100" style="background:{{ $accentColor }}"></div>
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-[#8B5CF6]/25 bg-[#8B5CF6]/12 text-[#A855F7] shadow-[inset_0_1px_0_rgba(255,255,255,0.04)] transition-colors group-hover:bg-[#8B5CF6]/20">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75 5.75 12l2.5 2.25M15.75 9.75l2.5 2.25-2.5 2.25M13.25 6.75l-2.5 10.5"/></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-black text-[#F8FAFC]">{{ $command->displayName() }}</span>
                                                <span class="rounded-full border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[10px] font-bold text-[#94A3B8]">{{ $triggerLabel }}</span>
                                            </div>
                                            <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                                @if ($triggerType !== 'direct_message')
                                                    <code class="font-mono text-[11px] text-[#A855F7]">{{ $command->command_name }}</code>
                                                    <span class="text-[11px] text-[#3D3658]">&middot;</span>
                                                @endif
                                                <span class="text-[11px] text-[#52525B]">{{ number_format($command->execution_count ?? 0) }} {{ Str::plural('run', $command->execution_count ?? 0) }}</span>
                                                <span class="text-[11px] text-[#3D3658]">&middot;</span>
                                                <span class="text-[11px] text-[#52525B]">{{ $command->last_used_at ? 'Last used '.$command->last_used_at->diffForHumans() : 'Never used' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5 lg:justify-end">
                                        <x-status-badge :status="$command->status" />
                                        <span class="rounded-full border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[10px] font-bold text-[#94A3B8]">JS</span>
                                        <span class="rounded-full border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[10px] font-bold text-[#94A3B8]">Node.js</span>
                                    </div>
                                    <div class="flex w-full flex-wrap items-center gap-2 lg:w-auto lg:justify-end">
                                        {{-- Actions --}}
                                        <button
                                            type="button"
                                            @click="openCodeEditor({
                                                id: @js($command->id),
                                                title: @js($command->displayName()),
                                                filename: @js((Str::slug($command->displayName()) ?: 'command').'.js'),
                                                language: 'javascript',
                                                codeUrl: @js(route('bots.commands.code', [$bot, $command])),
                                                action: @js(route('bots.commands.code.update', [$bot, $command])),
                                                fallbackUrl: @js(route('bots.commands.code', [$bot, $command])),
                                                method: 'PUT',
                                                csrf: @js(csrf_token()),
                                                triggerType: @js($command->effectiveTriggerType() === 'direct_message' ? 'Direct Message Handler' : 'Command'),
                                            })"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-[9px] border border-[#8B5CF6]/35 bg-[#8B5CF6]/10 px-3 text-[12px] font-black text-[#A855F7] transition hover:border-[#A855F7]/55 hover:bg-[#8B5CF6]/18"
                                        >
                                            <svg class="h-[14px] w-[14px] shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m17.25 6.75 4.5 4.5-4.5 4.5M6.75 6.75l-4.5 4.5 4.5 4.5m7.5-12-4.5 16.5"/></svg>
                                            Edit Code
                                        </button>
                                        <a href="{{ route('bots.commands.edit', [$bot, $command]) }}" class="inline-flex h-9 items-center rounded-[9px] border border-[#27213D] bg-[#151225] px-3 text-[12px] font-black text-[#A1A1AA] transition hover:border-[#38BDF8]/35 hover:text-[#38BDF8]">Edit</a>
                                        <form method="POST" action="{{ route('bots.commands.destroy', [$bot, $command]) }}">
                                            @csrf @method('DELETE')
                                            <button
                                                type="submit"
                                                data-confirm
                                                data-confirm-type="danger"
                                                data-confirm-title="Delete command?"
                                                data-confirm-message="This will permanently remove &quot;{{ addslashes($command->displayName()) }}&quot; and its code."
                                                data-confirm-btn="Delete Command"
                                                class="flex h-9 w-9 items-center justify-center rounded-[9px] border border-[#27213D] bg-[#151225] text-[#52525B] transition hover:border-[#EF4444]/35 hover:bg-[#EF4444]/8 hover:text-[#EF4444]"
                                            >
                                                <svg class="h-[14px] w-[14px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-t border-[#1B172B] px-4 py-2.5">
                            <p class="text-xs text-[#94A3B8]">Triggers fire only when the incoming message matches the command name <em>exactly</em>.</p>
                        </div>
                    @else
                        <div class="flex flex-col items-center gap-4 py-16 text-center">
                            <div class="grid h-14 w-14 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#94A3B8]">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                            </div>
                            <div>
                                <p class="font-black text-[#A1A1AA]">No commands yet</p>
                                <p class="mt-1 text-sm text-[#94A3B8]">Create triggers like <code class="font-mono text-[#8B5CF6]">/start</code> or <code class="font-mono text-[#8B5CF6]">deposit</code></p>
                            </div>
                            <button type="button" @click="$dispatch('open-modal', 'cmd-create')" class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-4 py-2.5 text-sm font-black text-white shadow-[0_0_20px_rgba(139,92,246,0.28)] transition hover:-translate-y-0.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Add First Command
                            </button>
                            <a href="{{ route('bots.templates.index', $bot) }}" class="rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5 text-sm font-black text-[#A1A1AA] transition hover:text-[#F8FAFC]">
                                Use Template
                            </a>
                        </div>
                    @endif
                </div>
            </section>

            {{-- ══════════════════════════ NEW COMMAND MODAL ══ --}}
            <x-modal name="cmd-create" maxWidth="2xl" focusable>
                {{-- Header --}}
                <div class="relative overflow-hidden border-b border-[#27213D] px-6 py-5">
                    <div class="pointer-events-none absolute inset-0">
                        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#8B5CF6]/8 blur-3xl"></div>
                    </div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-[#38BDF8]/35 bg-[#38BDF8]/10 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-[0.15em] text-[#38BDF8]">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                New Command
                            </span>
                            <h2 class="mt-3 text-xl font-black text-[#F8FAFC]">Add Command</h2>
                            <p class="mt-1 text-sm text-[#A1A1AA]">Configure a command trigger and save to continue to the code editor.</p>
                        </div>
                        <button type="button" @click="$dispatch('close-modal', 'cmd-create')" class="shrink-0 rounded-lg p-1.5 text-[#94A3B8] transition hover:bg-[#1D1930] hover:text-[#F8FAFC]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                {{-- Form --}}
                <form method="POST" action="{{ route('bots.commands.store', $bot) }}" class="p-6">
                    @csrf
                    <input type="hidden" name="_cmd_modal" value="1">
                    <div class="space-y-5">
                        @include('bots.commands.partials.form', ['command' => null])
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-[#27213D] pt-5">
                        <button type="button" @click="$dispatch('close-modal', 'cmd-create')" class="rounded-xl border border-[#27213D] bg-[#151225] px-5 py-2.5 text-sm font-semibold text-[#A1A1AA] transition hover:text-[#F8FAFC]">Cancel</button>
                        <button type="submit" class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white shadow-[0_0_20px_rgba(139,92,246,0.22)] transition hover:-translate-y-0.5">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            Save Command
                        </button>
                    </div>
                </form>
            </x-modal>

            {{-- ════════════════════════════════════════════════ SEARCH ══ --}}
            <section x-show="tab === 'search'" x-cloak class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
                <div class="space-y-3 border-b border-[#1B172B] p-5">
                    <div class="flex flex-col gap-3 xl:flex-row">
                        <div class="flex flex-1 items-center gap-3 rounded-xl border border-[#8B5CF6]/40 bg-[#151225] px-4 py-3 focus-within:border-[#8B5CF6]/70 focus-within:ring-2 focus-within:ring-[#8B5CF6]/15 transition">
                            <svg class="h-4 w-4 shrink-0 text-[#94A3B8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                            <input x-model="searchQuery" class="w-full border-0 bg-transparent p-0 font-mono text-sm text-[#F8FAFC] placeholder:text-[#71717A] focus:ring-0" placeholder="Search across command names and code...">
                            <button x-show="searchQuery" x-cloak @click="searchQuery = ''" class="shrink-0 text-xs font-black text-[#94A3B8] hover:text-[#F8FAFC]">Clear</button>
                        </div>
                        <div class="flex gap-2">
                            @foreach (['Aa', '|ab|', '.*'] as $t)
                                <button class="rounded-lg border border-[#27213D] bg-[#151225] px-3 py-2.5 font-mono text-xs font-black text-[#94A3B8] transition hover:border-[#8B5CF6]/40 hover:text-[#A855F7]">{{ $t }}</button>
                            @endforeach
                            <button class="rounded-lg border border-[#27213D] bg-[#151225] px-3 py-2.5 text-xs font-black text-[#94A3B8] transition hover:text-[#F8FAFC]">History</button>
                        </div>
                    </div>
                    <p class="text-xs text-[#94A3B8]" x-text="searchQuery ? 'Showing matching commands and code snippets.' : @js('Searching '.$commandCount.' '.Str::plural('command', $commandCount).'.')"></p>
                </div>
                <div class="min-h-64 p-6">
                    <div x-show="!searchQuery" class="flex min-h-52 flex-col items-center justify-center gap-4 text-center">
                        <div class="grid h-16 w-16 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#94A3B8]">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        </div>
                        <div>
                            <p class="font-black text-[#A1A1AA]">Search across every command</p>
                            <p class="mt-1 text-sm text-[#94A3B8]">Find any phrase, keyword, or pattern in your bot's stored responses.</p>
                        </div>
                    </div>
                    <div x-show="searchQuery" x-cloak class="space-y-3">
                        @forelse ($bot->commands as $command)
                            <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#151225]" x-show="@js(strtolower($command->displayName() . ' ' . $command->command_name . ' ' . $command->code)).includes(searchQuery.toLowerCase())">
                                <div class="border-b border-[#1B172B] px-4 py-3">
                                    <code class="font-mono text-sm font-black text-[#A855F7]">{{ $command->displayName() }}</code>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 px-4 pb-3 pt-1">
                                    <span class="rounded-full border border-[#27213D] bg-[#0F0D1A] px-2 py-0.5 text-[10px] font-black text-[#94A3B8]">{{ $command->response_type ?? 'text' }}</span>
                                    <span class="text-xs text-[#94A3B8]">{{ strlen((string) $command->code) }} chars</span>
                                    @if ($command->is_active ?? true)
                                        <span class="rounded-full border border-[#22C55E]/30 bg-[#22C55E]/10 px-2 py-0.5 text-[10px] font-black text-[#22C55E]">Active</span>
                                    @else
                                        <span class="rounded-full border border-[#71717A]/30 bg-[#71717A]/10 px-2 py-0.5 text-[10px] font-black text-[#94A3B8]">Inactive</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="py-10 text-center text-sm text-[#94A3B8]">No commands to search.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            {{-- ERRORS --}}
            <section x-show="tab === 'errors'" x-cloak class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
                <div class="flex items-center justify-between border-b border-[#1B172B] px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="grid h-8 w-8 place-items-center rounded-lg border border-[#EF4444]/30 bg-[#EF4444]/10 text-[#EF4444]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                        </div>
                        <h3 class="font-black text-[#F8FAFC]">Error Log</h3>
                        <span class="rounded-full border {{ $errorLogs->isEmpty() ? 'border-[#22C55E]/30 bg-[#22C55E]/10 text-[#22C55E]' : 'border-[#EF4444]/30 bg-[#EF4444]/10 text-[#EF4444]' }} px-2 py-0.5 text-[10px] font-black">{{ $errorLogs->count() }} {{ Str::plural('error', $errorLogs->count()) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="rounded-lg border border-[#27213D] bg-[#151225] p-2 text-[#94A3B8] transition hover:text-[#F8FAFC]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        </button>
                        <form method="POST" action="{{ route('bots.logs.errors.clear', $bot) }}">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                data-confirm
                                data-confirm-type="danger"
                                data-confirm-title="Clear errors?"
                                data-confirm-message="This will remove all recorded runtime errors for this bot. This cannot be undone."
                                data-confirm-btn="Clear Errors"
                                class="rounded-lg border border-[#27213D] bg-[#151225] px-3 py-2 text-xs font-black text-[#94A3B8] transition hover:text-[#EF4444]"
                            >Clear</button>
                        </form>
                    </div>
                </div>
                @if ($errorLogs->isEmpty())
                    <div class="flex min-h-64 flex-col items-center justify-center gap-4 p-10 text-center">
                        <div class="grid h-14 w-14 place-items-center rounded-2xl border border-[#22C55E]/20 bg-[#22C55E]/8 text-[#22C55E]">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </div>
                        <div>
                            <p class="font-black text-[#22C55E]">All Clear</p>
                            <p class="mt-1 text-sm text-[#94A3B8]">No errors found &mdash; your bot workspace has no recorded errors.</p>
                        </div>
                    </div>
                @else
                    <div class="divide-y divide-[#1B172B]">
                        @foreach ($errorLogs as $log)
                            <div class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <span class="rounded-full border border-[#EF4444]/30 bg-[#EF4444]/10 px-2.5 py-1 text-[10px] font-black text-[#EF4444]">{{ strtoupper($log->type) }}</span>
                                    <span class="text-xs text-[#94A3B8]">{{ $log->created_at?->diffForHumans() }}</span>
                                </div>
                                @if ($log->title)
                                    <p class="mt-2 text-sm font-black text-[#F8FAFC]">{{ $log->title }}</p>
                                @endif
                                <p class="mt-2 break-words font-mono text-xs leading-6 text-[#A1A1AA]">{{ $log->message }}</p>
                                @if (data_get($log->context, 'error_type'))
                                    <p class="mt-2 text-xs font-bold text-[#94A3B8]">Error type: <span class="font-mono text-[#EF4444]">{{ data_get($log->context, 'error_type') }}</span></p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ════════════════════════════════════════════════ LOGS ══ --}}
            <section x-show="tab === 'logs'" x-cloak class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
                {{-- Header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#1B172B] px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="grid h-8 w-8 place-items-center rounded-lg border border-[#38BDF8]/30 bg-[#38BDF8]/10 text-[#38BDF8]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                        </div>
                        <h3 class="font-black text-[#F8FAFC]">Runtime Logs</h3>
                        <span class="rounded-full border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[10px] font-black text-[#A1A1AA]">{{ $logEntryCount }} entries</span>
                    </div>
                    {{-- Status filter pills --}}
                    @if ($logEntryCount > 0)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ([
                                ['all',                  'All',              '#A1A1AA'],
                                ['success',              'Success',          '#22C55E'],
                                ['failed',               'Failed',           '#EF4444'],
                                ['no_match',             'No Match',         '#F59E0B'],
                                ['runtime_unavailable',  'Unavailable',      '#EF4444'],
                            ] as [$val, $filterLabel, $fc])
                                <button
                                    type="button"
                                    @click="logFilter = '{{ $val }}'"
                                    class="rounded-lg border px-2.5 py-1 text-[10px] font-black transition"
                                    :class="logFilter === '{{ $val }}'
                                        ? 'border-current bg-current/15 text-[{{ $fc }}]'
                                        : 'border-[#27213D] bg-[#151225] text-[#52525B] hover:text-[#A1A1AA]'"
                                    style="{{ "color:{$fc}" }}"
                                >{{ $filterLabel }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($logEntryCount === 0)
                    <div class="flex min-h-64 flex-col items-center justify-center gap-4 p-10 text-center">
                        <div class="grid h-14 w-14 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                        </div>
                        <div>
                            <p class="font-black text-[#A1A1AA]">No command activity yet</p>
                            <p class="mt-1 text-sm text-[#94A3B8]">Logs appear here once users interact with your bot.</p>
                        </div>
                    </div>
                @else
                    <div class="divide-y divide-[#1B172B]">
                        @foreach ($commandLogs as $log)
                            @php
                                $statusColor = match ($log->status) {
                                    'success'              => '#22C55E',
                                    'failed'               => '#EF4444',
                                    'runtime_unavailable'  => '#EF4444',
                                    'no_reply'             => '#F59E0B',
                                    'no_match'             => '#F59E0B',
                                    'fallback_response'    => '#8B5CF6',
                                    default                => '#38BDF8',
                                };
                                $telegramUser = $log->telegram_username
                                    ? '@'.$log->telegram_username
                                    : ($log->telegram_first_name ?: ($log->telegram_user_id ?: 'Unknown user'));
                            @endphp
                            <div
                                class="px-4 py-3 transition hover:bg-[#151225]/40"
                                x-show="logFilter === 'all' || '{{ $log->status }}' === logFilter"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <code class="font-mono text-sm font-black text-[#A855F7]">{{ $log->command?->displayName() ?? 'No match' }}</code>
                                            <span class="rounded-full border px-2 py-0.5 text-[10px] font-black" style="color:{{ $statusColor }};background:{{ $statusColor }}18;border-color:{{ $statusColor }}40">{{ strtoupper(str_replace('_', ' ', $log->status)) }}</span>
                                        </div>
                                        <p class="mt-1 break-words text-xs text-[#94A3B8]">
                                            <span class="font-medium text-[#A1A1AA]">{{ $telegramUser }}</span>
                                            sent <span class="font-mono text-[#94A3B8]">{{ Str::limit((string) $log->message_text, 60) }}</span>
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-xs text-[#52525B]">{{ $log->created_at?->diffForHumans() }}</span>
                                </div>
                                <div class="mt-2.5 flex flex-wrap gap-1.5">
                                    <span class="rounded-md border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[10px] font-bold text-[#94A3B8]">{{ $log->reply_count }} {{ Str::plural('reply', $log->reply_count) }}</span>
                                    <span class="rounded-md border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[10px] font-bold text-[#94A3B8]">{{ $log->execution_time_ms !== null ? $log->execution_time_ms.'ms' : 'n/a' }}</span>
                                    @if ($log->execution_id)
                                        <span class="rounded-md border border-[#27213D] bg-[#151225] px-2 py-0.5 font-mono text-[10px] text-[#52525B]">{{ $log->execution_id }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-[#1B172B] px-4 py-2.5">
                        <p class="text-xs text-[#94A3B8]">Showing latest {{ $logEntryCount }} {{ Str::plural('entry', $logEntryCount) }}. Use the filter above to narrow results.</p>
                    </div>
                @endif
            </section>

            {{-- ════════════════════════════════════════════════ MANAGE ══ --}}
            <section x-show="tab === 'manage'" x-cloak class="space-y-4">

                {{-- Section 1: Maintenance --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                    <div class="flex items-center gap-2.5">
                        <div class="grid h-8 w-8 place-items-center rounded-lg border border-[#38BDF8]/30 bg-[#38BDF8]/10 text-[#38BDF8]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.664-9.499 3.86 3.86M6.26 6.26 3.102 3.102m0 0L2.25 2.25m.852.852c-.208.224-.408.454-.595.694M10.05 10.05l3.9-3.9m.748 9.9 3.182-3.182M6.26 6.26l-.694.595"/></svg>
                        </div>
                        <p class="text-sm font-black text-[#F8FAFC]">Maintenance</p>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <button
                            type="button"
                            onclick="location.reload()"
                            class="flex items-center gap-2.5 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3 text-sm font-bold text-[#A1A1AA] transition hover:border-[#38BDF8]/30 hover:text-[#38BDF8]"
                        >
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            Refresh Data
                        </button>
                        <form method="POST" action="{{ route('bots.logs.errors.clear', $bot) }}">
                            @csrf @method('DELETE')
                            <button
                                type="submit"
                                data-confirm
                                data-confirm-type="danger"
                                data-confirm-title="Clear errors?"
                                data-confirm-message="This will remove all recorded errors for this bot. This cannot be undone."
                                data-confirm-btn="Clear Errors"
                                class="flex w-full items-center gap-2.5 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3 text-sm font-bold text-[#A1A1AA] transition hover:border-[#EF4444]/30 hover:text-[#EF4444]"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                Clear Errors
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Section 2: Bot Tools --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                    <div class="mb-3 flex items-center gap-2.5">
                        <div class="grid h-8 w-8 place-items-center rounded-lg border border-[#8B5CF6]/30 bg-[#8B5CF6]/10 text-[#8B5CF6]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                        </div>
                        <p class="text-sm font-black text-[#F8FAFC]">Bot Tools</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:items-start">

                        {{-- Export Bot --}}
                        <div class="rounded-xl border border-[#27213D] bg-[#151225] p-4">
                            <div class="flex items-center gap-2.5">
                                <div class="grid h-8 w-8 place-items-center rounded-lg border border-[#22C55E]/30 bg-[#22C55E]/10 text-[#22C55E]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                </div>
                                <p class="text-sm font-black text-[#F8FAFC]">Export Bot</p>
                            </div>
                            <p class="mt-3 text-xs text-[#94A3B8]">Download a safe backup of this bot's commands and settings. Bot token is never included.</p>
                            @if($protectedCommandCount > 0)
                                <button type="button" disabled class="mt-4 flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-[#71717A]/30 bg-[#71717A]/8 py-2.5 text-sm font-black text-[#94A3B8]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    Export Locked
                                </button>
                                <p class="mt-3 text-[10px] text-[#F59E0B]">{{ $protectedCommandCount }} marketplace/template {{ Str::plural('command', $protectedCommandCount) }} detected. This bot cannot be exported.</p>
                            @else
                                <a href="{{ route('bots.export', $bot) }}" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-[#22C55E]/40 bg-[#22C55E]/10 py-2.5 text-sm font-black text-[#22C55E] transition hover:bg-[#22C55E]/18">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    Download Export
                                </a>
                                <p class="mt-3 text-[10px] text-[#52525B]">{{ $exportableCommandCount }} self-created {{ Str::plural('command', $exportableCommandCount) }} will be included.</p>
                            @endif
                        </div>

                        {{-- Import Commands --}}
                        <div x-data="{ open: false, dragging: false, fileName: '' }" class="rounded-2xl border border-[#27213D] bg-[#151225] p-5">
                            <div class="flex items-center gap-2.5">
                                <div class="grid h-8 w-8 place-items-center rounded-lg border border-[#8B5CF6]/30 bg-[#8B5CF6]/10 text-[#A855F7]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                </div>
                                <p class="text-sm font-black text-[#F8FAFC]">Import Commands</p>
                            </div>
                            <p class="mt-3 text-xs text-[#94A3B8]">Upload a BotHost Pro export file to import commands into this bot workspace.</p>
                            <button @click="open = !open" type="button" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-[#8B5CF6]/40 bg-[#8B5CF6]/10 py-2.5 text-sm font-black text-[#A855F7] transition hover:bg-[#8B5CF6]/18">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                Import Commands
                            </button>
                            <div x-show="open" x-transition.opacity.duration.150ms x-cloak class="mt-4">
                                <form method="POST" action="{{ route('bots.import.current', $bot) }}" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#94A3B8]">Export File (.json)</label>
                                        <label
                                            class="flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed px-4 py-6 text-center transition"
                                            :class="dragging ? 'border-[#8B5CF6] bg-[#8B5CF6]/12' : 'border-[#27213D] bg-[#0F0D1A] hover:border-[#8B5CF6]/40 hover:bg-[#8B5CF6]/8'"
                                            @dragover.prevent="dragging = true"
                                            @dragleave.prevent="dragging = false"
                                            @drop.prevent="dragging = false; $refs.importFile.files = $event.dataTransfer.files; fileName = $refs.importFile.files[0]?.name || ''"
                                        >
                                            <input x-ref="importFile" name="import_file" type="file" accept=".json" required class="sr-only" @change="fileName = $event.target.files[0]?.name || ''">
                                            <svg class="h-7 w-7 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V3.75m0 0L7.5 8.25M12 3.75l4.5 4.5M3.75 16.5v2.25A1.5 1.5 0 0 0 5.25 20.25h13.5a1.5 1.5 0 0 0 1.5-1.5V16.5"/></svg>
                                            <span class="mt-2 text-sm font-black text-[#F8FAFC]" x-text="fileName || 'Drop JSON export here or tap to choose'"></span>
                                            <span class="mt-1 text-[11px] text-[#94A3B8]">Accepts the same .json file exported by BotHost Pro.</span>
                                        </label>
                                        @error('import_file') <p class="mt-1 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
                                    </div>
                                    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#38BDF8] py-2.5 text-sm font-black text-white transition hover:-translate-y-0.5">Import Commands</button>
                                </form>
                            </div>
                        </div>

                        {{-- Clone Bot --}}
                        <div x-data="{
                            open: false,
                            eName: '',
                            validate(e) {
                                this.eName = '';
                                const v = e.target.querySelector('[name=clone_name]');
                                if (!v || !v.value.trim()) {
                                    this.eName = 'Enter a name for the cloned workspace.';
                                    this.$nextTick(() => { if (v) v.focus(); });
                                    return false;
                                }
                                return true;
                            }
                        }" class="rounded-2xl border border-[#27213D] bg-[#151225] p-5">
                            <div class="flex items-center gap-2.5">
                                <div class="grid h-8 w-8 place-items-center rounded-lg border border-[#38BDF8]/30 bg-[#38BDF8]/10 text-[#38BDF8]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>
                                </div>
                                <p class="text-sm font-black text-[#F8FAFC]">Clone Bot</p>
                            </div>
                            <p class="mt-3 text-xs text-[#94A3B8]">Duplicate this workspace. Add a new token now or skip it and add one later before activation.</p>
                            <button @click="open = !open" type="button" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-[#38BDF8]/40 bg-[#38BDF8]/10 py-2.5 text-sm font-black text-[#38BDF8] transition hover:bg-[#38BDF8]/18">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>
                                Clone Bot
                            </button>
                            <div x-show="open" x-transition.opacity.duration.150ms x-cloak class="mt-4">
                                <form method="POST" action="{{ route('bots.clone', $bot) }}" novalidate @submit="if (!validate($event)) $event.preventDefault()" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#94A3B8]">New Workspace Name</label>
                                        <input
                                            name="clone_name"
                                            type="text"
                                            @input="eName = ''"
                                            class="w-full rounded-xl border bg-[#0F0D1A] px-4 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#71717A] focus:outline-none focus:ring-2 transition"
                                            :class="eName ? 'border-[#EF4444]/50 focus:border-[#EF4444]/70 focus:ring-[#EF4444]/10' : 'border-[#27213D] focus:border-[#38BDF8]/60 focus:ring-[#38BDF8]/20'"
                                            placeholder="{{ $bot->name }} (Clone)"
                                        >
                                        <p
                                            x-show="eName"
                                            x-cloak
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 -translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            class="mt-1.5 flex items-center gap-1.5 text-[11px] font-semibold text-[#F87171]"
                                        >
                                            <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                                            <span x-text="eName"></span>
                                        </p>
                                        @error('clone_name') <p class="mt-1 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#94A3B8]">Bot Token for Clone (optional)</label>
                                        <input name="clone_token" type="password" autocomplete="off" spellcheck="false" autocapitalize="off" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-4 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#71717A] focus:border-[#38BDF8]/60 focus:outline-none focus:ring-2 focus:ring-[#38BDF8]/20 transition" placeholder="Token from @BotFather, or leave blank">
                                        @error('clone_token') <p class="mt-1 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
                                    </div>
                                    <button type="submit" class="w-full rounded-xl bg-[#38BDF8] py-2.5 text-sm font-black text-[#0B0918] transition hover:-translate-y-0.5">Clone Bot</button>
                                </form>
                            </div>
                        </div>

                        {{-- Transfer Bot --}}
                        <div x-data="{ open: false }" class="rounded-2xl border border-[#27213D] bg-[#151225] p-5">
                            <div class="flex items-center gap-2.5">
                                <div class="grid h-8 w-8 place-items-center rounded-lg border border-[#8B5CF6]/30 bg-[#8B5CF6]/10 text-[#8B5CF6]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                </div>
                                <p class="text-sm font-black text-[#F8FAFC]">Transfer Bot</p>
                            </div>
                            <p class="mt-3 text-xs text-[#94A3B8]">Send this workspace to another account. Only fully self-coded bots can be transferred.</p>
                            <button @click="open = !open" type="button" @disabled($protectedCommandCount > 0) class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border py-2.5 text-sm font-black transition {{ $protectedCommandCount > 0 ? 'cursor-not-allowed border-[#71717A]/30 bg-[#71717A]/8 text-[#94A3B8]' : 'border-[#8B5CF6]/40 bg-[#8B5CF6]/10 text-[#8B5CF6] hover:bg-[#8B5CF6]/18' }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                {{ $protectedCommandCount > 0 ? 'Transfer Locked' : 'Transfer Bot' }}
                            </button>
                            <div x-show="open" x-transition.opacity.duration.150ms x-cloak class="mt-4">
                                <form method="POST" action="{{ route('bots.transfer', $bot) }}" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#94A3B8]">Receiver Email</label>
                                        <input name="receiver_email" type="email" required autocomplete="off" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-4 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#71717A] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 transition" placeholder="receiver@example.com">
                                        @error('receiver_email') <p class="mt-1 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#94A3B8]">Note (optional)</label>
                                        <textarea name="transfer_note" rows="2" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-4 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#71717A] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 transition resize-none" placeholder="Optional message to receiver..."></textarea>
                                    </div>
                                    <div class="flex items-start gap-2.5 rounded-xl border border-[#F59E0B]/20 bg-[#F59E0B]/6 px-3 py-2.5">
                                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                                        <p class="text-[11px] text-[#A1A1AA]">Your bot token is <strong class="text-[#F8FAFC]">never shared</strong>. The receiver can connect their own token during import or later.</p>
                                    </div>
                                    <button type="submit" class="w-full rounded-xl bg-[#8B5CF6] py-2.5 text-sm font-black text-white transition hover:-translate-y-0.5">Initiate Transfer</button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Section 3: Danger Zone --}}
                <div class="rounded-2xl border border-[#EF4444]/30 bg-[#EF4444]/5 p-4">
                    <div class="flex items-center gap-2.5">
                        <div class="grid h-8 w-8 place-items-center rounded-lg border border-[#EF4444]/30 bg-[#EF4444]/10 text-[#EF4444]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                        </div>
                        <p class="text-sm font-black text-[#EF4444]">Danger Zone</p>
                    </div>
                    <p class="mt-3 text-xs text-[#A1A1AA]">Permanently delete this bot and all its commands. <strong class="text-[#EF4444]">Cannot be undone.</strong></p>
                    <form method="POST" action="{{ route('bots.destroy', $bot) }}" class="mt-4">
                        @csrf @method('DELETE')
                        <button
                            type="submit"
                            data-confirm
                            data-confirm-type="danger"
                            data-confirm-title="Delete bot permanently?"
                            data-confirm-message="This will permanently delete &quot;{{ addslashes($bot->name) }}&quot; and all its commands. This cannot be undone."
                            data-confirm-btn="Delete Bot"
                            data-confirm-typed="true"
                            data-confirm-word="DELETE"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#EF4444] py-3 text-sm font-black text-white shadow-[0_0_16px_rgba(239,68,68,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_0_24px_rgba(239,68,68,0.32)]"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            Delete Bot
                        </button>
                    </form>
                </div>

            </section>

            {{-- ═════════════════════════════════════════════════ ADMIN ══ --}}
            <section x-show="tab === 'admin'" x-cloak class="space-y-5">
                <div class="flex items-center gap-3">
                    <div class="grid h-9 w-9 place-items-center rounded-xl border border-[#8B5CF6]/30 bg-[#8B5CF6]/10 text-[#8B5CF6]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    </div>
                    <h2 class="font-black text-[#F8FAFC]">Admin Panel</h2>
                </div>

                {{-- Admin sub-tabs --}}
                <div class="overflow-x-auto rounded-2xl border border-[#27213D] bg-[#0B0A15] p-1.5" style="scrollbar-width:none">
                    <div class="flex min-w-max gap-1.5">
                        @foreach (['analytics' => 'Analytics', 'users' => 'Users', 'broadcasts' => 'Broadcasts', 'botData' => 'Bot Data', 'userData' => 'User Data', 'permissions' => 'Permissions'] as $key => $label)
                            <button @click="setAdminTab('{{ $key }}')" class="rounded-xl px-2.5 py-1.5 text-xs font-black transition sm:px-4 sm:py-2 sm:text-sm" :class="adminTab === '{{ $key }}' ? 'bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] text-white shadow-[0_0_16px_rgba(139,92,246,0.28)]' : 'text-[#94A3B8] hover:bg-[#151225] hover:text-[#F8FAFC]'">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>


                {{-- Analytics --}}
                <div x-show="adminTab === 'analytics'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        @foreach ([
                            ['Total Users', $botUserAnalytics['total_users'] ?? 0, '#8B5CF6', 'rgba(139,92,246,0.18)', 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
                            ['Active 24h',  $botUserAnalytics['active_24h'] ?? 0,   '#22C55E', 'rgba(34,197,94,0.14)',   'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                            ['Active 7d',   $botUserAnalytics['active_7d'] ?? 0,   '#38BDF8', 'rgba(56,189,248,0.14)',  'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
                            ['Active 30d',  $botUserAnalytics['active_30d'] ?? 0,   '#38BDF8', 'rgba(56,189,248,0.12)',  'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
                            ['New 24h',     $botUserAnalytics['new_24h'] ?? 0,   '#22C55E', 'rgba(34,197,94,0.14)',   'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z'],
                            ['New 7d',      $botUserAnalytics['new_7d'] ?? 0,   '#F59E0B', 'rgba(245,158,11,0.14)',  'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z'],
                            ['New 30d',     $botUserAnalytics['new_30d'] ?? 0,   '#F59E0B', 'rgba(245,158,11,0.12)',  'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z'],
                            ['Blocked',     $botUserAnalytics['blocked_users'] ?? 0,   '#EF4444', 'rgba(239,68,68,0.14)',   'M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636'],
                        ] as [$label, $value, $color, $glow, $icon])
                            <div class="group relative overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 transition-all duration-300 hover:-translate-y-0.5">
                                <div class="pointer-events-none absolute inset-0 rounded-2xl opacity-0 transition-opacity duration-300 group-hover:opacity-100" style="background:radial-gradient(circle at top right,{{ $glow }},transparent 70%)"></div>
                                <div class="relative">
                                    <div class="flex items-center gap-2.5">
                                        <div class="grid h-7 w-7 shrink-0 place-items-center rounded-lg border border-[#27213D] bg-[#151225]" style="color:{{ $color }}">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                        </div>
                                        <p class="text-[9px] font-black uppercase tracking-[0.15em] text-[#94A3B8]">{{ $label }}</p>
                                    </div>
                                    <p class="mt-2.5 text-2xl font-black text-[#F8FAFC]">{{ $value }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
                        <div class="border-b border-[#1B172B] px-5 py-3.5">
                            <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Commands ({{ $commandCount }})</p>
                        </div>
                        <div class="divide-y divide-[#1B172B]">
                            @forelse ($bot->commands as $cmd)
                                <div class="flex items-center justify-between px-5 py-3.5">
                                    <code class="font-mono text-sm font-black text-[#A855F7]">{{ $cmd->displayName() }}</code>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-[#94A3B8]">{{ strlen((string) $cmd->code) }} chars</span>
                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-[#27213D]">
                                            <div class="h-1.5 rounded-full bg-gradient-to-r from-[#8B5CF6] to-[#A855F7]" style="width:{{ min(strlen((string) $cmd->code) / 5, 100) }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="px-5 py-8 text-center text-sm text-[#94A3B8]">No commands yet</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Top Languages</p>
                        <div class="mt-4 space-y-3">
                            @forelse ($botUserLanguages as $language)
                                @php
                                    $lang = $language['language'];
                                    $count = $language['count'];
                                    $pct = $language['percentage'].'%';
                                @endphp
                                <div class="grid grid-cols-[2.5rem_1fr_5rem] items-center gap-3">
                                    <span class="rounded bg-[#151225] px-1.5 py-0.5 text-center font-mono text-xs font-black text-[#A1A1AA]">{{ $lang }}</span>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-[#27213D]">
                                        <div class="h-1.5 rounded-full bg-gradient-to-r from-[#8B5CF6] to-[#38BDF8]" style="width:{{ min($language['percentage'],100) }}%"></div>
                                    </div>
                                    <span class="text-right text-xs text-[#94A3B8]">{{ $count }} <span class="text-[#4B5563]">({{ $pct }})</span></span>
                                </div>
                            @empty
                                <p class="text-sm text-[#94A3B8]">No language data yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Users --}}
                <div x-show="adminTab === 'users'" class="space-y-4">
                    <div class="flex flex-wrap gap-3">
                        <input class="min-w-[200px] flex-1 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-4 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#71717A] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 transition" placeholder="Search by name or user ID...">
                        <div class="relative" x-data="{ open: false, val: 'All', labels: {'All':'All','Active':'Active','Blocked':'Blocked'}, get label() { return this.labels[this.val] || 'All' } }" @click.away="open = false">
                            <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2.5 text-sm text-[#A1A1AA] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/20' : ''">
                                <span x-text="label"></span>
                                <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                class="absolute z-50 mt-1.5 w-36 overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                @foreach (['All', 'Active', 'Blocked'] as $usf)
                                <button type="button" @click="val = '{{ $usf }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $usf }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                    <svg :class="val === '{{ $usf }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    {{ $usf }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        <button class="rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5 text-xs font-black text-[#94A3B8] transition hover:text-[#F8FAFC]">JSON</button>
                        <button class="rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/10 px-4 py-2.5 text-xs font-black text-[#22C55E] transition hover:bg-[#22C55E]/18">CSV</button>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
                        <div class="border-b border-[#1B172B] px-5 py-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">{{ $botUsers->count() }} {{ Str::plural('user', $botUsers->count()) }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-max w-full text-left text-sm">
                                <thead><tr class="border-b border-[#1B172B]">
                                    <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-[#94A3B8]">User ID</th>
                                    <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-[#94A3B8]">Name</th>
                                    <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-[#94A3B8]">Lang</th>
                                    <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-[#94A3B8]">Last Active</th>
                                    <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-[#94A3B8]">Status</th>
                                    <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-[#94A3B8]">Messages</th>
                                    <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-[#94A3B8]">Commands</th>
                                    <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-[#94A3B8]">Action</th>
                                </tr></thead>
                                <tbody class="divide-y divide-[#1B172B]">
                                    @forelse ($botUsers as $trackedUser)
                                        @php
                                            $displayName = $trackedUser->telegram_username
                                                ? '@'.$trackedUser->telegram_username
                                                : trim(($trackedUser->telegram_first_name ?? '').' '.($trackedUser->telegram_last_name ?? ''));
                                        @endphp
                                        <tr class="transition hover:bg-[#151225]">
                                            <td class="px-5 py-3.5 font-mono text-sm font-black text-[#38BDF8]">{{ $trackedUser->telegram_user_id }}</td>
                                            <td class="px-5 py-3.5 text-[#A1A1AA]">{{ $displayName !== '' ? $displayName : '�' }}</td>
                                            <td class="px-5 py-3.5"><span class="rounded bg-[#151225] px-1.5 py-0.5 font-mono text-xs text-[#A1A1AA]">{{ $trackedUser->telegram_language_code ?: 'unknown' }}</span></td>
                                            <td class="px-5 py-3.5 text-xs text-[#94A3B8]">{{ $trackedUser->last_active_at?->diffForHumans() ?? 'Never' }}</td>
                                            <td class="px-5 py-3.5"><x-status-badge :status="$trackedUser->status" /></td>
                                            <td class="px-5 py-3.5 font-mono text-xs text-[#A1A1AA]">{{ number_format($trackedUser->message_count) }}</td>
                                            <td class="px-5 py-3.5 font-mono text-xs text-[#A1A1AA]">{{ number_format($trackedUser->command_count) }}</td>
                                            <td class="px-5 py-3.5">
                                                <div class="flex flex-wrap gap-1.5">
                                                    @if ($trackedUser->status === 'blocked')
                                                        <form method="POST" action="{{ route('bots.users.unblock', [$bot, $trackedUser]) }}">@csrf @method('PATCH')<button type="submit" class="rounded-lg border border-[#22C55E]/25 bg-[#22C55E]/8 px-2 py-1 text-[10px] font-black text-[#22C55E] transition hover:bg-[#22C55E]/15">Unblock</button></form>
                                                    @else
                                                        <form method="POST" action="{{ route('bots.users.block', [$bot, $trackedUser]) }}">@csrf @method('PATCH')<button type="submit" data-confirm data-confirm-type="warning" data-confirm-title="Block user?" data-confirm-message="This user will no longer be able to use this bot." data-confirm-btn="Block User" class="rounded-lg border border-[#EF4444]/25 bg-[#EF4444]/8 px-2 py-1 text-[10px] font-black text-[#EF4444] transition hover:bg-[#EF4444]/15">Block</button></form>
                                                    @endif
                                                    @if ($trackedUser->status === 'paused')
                                                        <form method="POST" action="{{ route('bots.users.resume', [$bot, $trackedUser]) }}">@csrf @method('PATCH')<button type="submit" class="rounded-lg border border-[#38BDF8]/25 bg-[#38BDF8]/8 px-2 py-1 text-[10px] font-black text-[#38BDF8] transition hover:bg-[#38BDF8]/15">Resume</button></form>
                                                    @else
                                                        <form method="POST" action="{{ route('bots.users.pause', [$bot, $trackedUser]) }}">@csrf @method('PATCH')<button type="submit" class="rounded-lg border border-[#F59E0B]/25 bg-[#F59E0B]/8 px-2 py-1 text-[10px] font-black text-[#F59E0B] transition hover:bg-[#F59E0B]/15">Pause</button></form>
                                                    @endif
                                                    <form method="POST" action="{{ route('bots.users.delete-status', [$bot, $trackedUser]) }}">@csrf @method('PATCH')<button type="submit" data-confirm data-confirm-type="danger" data-confirm-title="Reset user data?" data-confirm-message="This will clear this user's bot data. If they return, they will start as a new user." data-confirm-btn="Reset Data" class="rounded-lg border border-[#27213D] bg-[#151225] px-2 py-1 text-[10px] font-black text-[#94A3B8] transition hover:text-[#F8FAFC]">Reset Data</button></form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-8 text-center text-sm text-[#94A3B8]">No users yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Broadcasts --}}
                <div x-show="adminTab === 'broadcasts'" x-data="{
                    composerOpen: false,
                    bcTitle: '',
                    bcMsgType: 'text',
                    bcMsg: '',
                    bcParseMode: '',
                    parseModeOpen: false,
                    targetOpen: false,
                    bcCtaText: '',
                    bcCtaUrl: '',
                    bcDisablePreview: false,
                    bcTarget: 'all_active',
                    bcCustomLimit: '',
                    bcImageName: '',
                    bcImagePreviewUrl: '',
                    bcRecipientUploadName: '',
                    bcRecipientUploadCount: 0,
                    bcRecipientPaste: '',
                    bcEstCount: @js($broadcastTargetCounts['all_active'] ?? 0),
                    bcEligibleCount: @js($broadcastTargetCounts['all_active'] ?? 0),
                    bcEstHuman: '',
                    bcLimitApplied: false,
                    bcAppliedLimit: null,
                    bcCustomLimitApplied: false,
                    bcEstimateError: '',
                    bcEstLoading: false,
                    bcTargetLabels: @js(collect(['all_active'=>'all_active','active_24h'=>'active_24h','active_48h'=>'active_48h','active_72h'=>'active_72h','active_7d'=>'active_7d','active_30d'=>'active_30d','paused_users'=>'paused_users','blocked_users'=>'blocked_users','specific_users'=>'specific_users'])->mapWithKeys(fn($v,$k)=>[$k=>['label'=>['all_active'=>'All Active Users','active_24h'=>'Active in last 24 hours','active_48h'=>'Active in last 48 hours','active_72h'=>'Active in last 72 hours','active_7d'=>'Active in last 7 days','active_30d'=>'Active in last 30 days','paused_users'=>'Paused users','blocked_users'=>'Blocked users','specific_users'=>'Uploaded phones / IDs'][$k],'count'=>number_format($broadcastTargetCounts[$k]??0)]])->toArray()),
                    get bcParseModeLabel() {
                        return {'':'None — plain text','HTML':'HTML — use <b>, <i>, <a> tags','Markdown':'Markdown — use *bold*, _italic_'}[this.bcParseMode] ?? 'None — plain text';
                    },
                    get bcTargetLabel() {
                        const t = this.bcTargetLabels[this.bcTarget];
                        return t ? t.label + ' (' + t.count + ')' : '';
                    },
                    get bcMaxLen() { return this.bcMsgType === 'image' ? 1024 : 4096; },
                    get bcLen() { return this.bcMsg.length; },
                    get bcPreviewText() { return this.bcMsg.trim() ? this.bcMsg : ''; },
                    countRecipientText(value) {
                        const matches = (value || '').match(/@[\w_]{3,}|[+]?\d[\d\s().-]{4,}\d|\b\d{5,}\b/g) || [];
                        return [...new Set(matches.map(v => v.replace(/[^\d@A-Za-z_]+/g, '').toLowerCase()).filter(Boolean))].length;
                    },
                    updateSpecificEstimate(extraCount = null) {
                        const pastedCount = this.countRecipientText(this.bcRecipientPaste);
                        if (extraCount !== null) this.bcRecipientUploadCount = extraCount;
                        const count = Math.max(pastedCount, this.bcRecipientUploadCount || 0);
                        if (this.bcTarget === 'specific_users') {
                            this.bcEstCount = count;
                            this.bcEligibleCount = count;
                            this.bcEstHuman = count > 0 ? 'After start' : '';
                            this.bcLimitApplied = false;
                            this.bcCustomLimitApplied = false;
                        }
                    },
                    async fetchEstimate() {
                        if (this.bcTarget === 'specific_users') {
                            this.updateSpecificEstimate();
                            return;
                        }
                        this.bcEstLoading = true;
                        this.bcEstimateError = '';
                        try {
                            const params = new URLSearchParams({ target_type: this.bcTarget });
                            @if (auth()->user()?->isAdmin())
                                if (this.bcCustomLimit) params.set('custom_recipient_limit', this.bcCustomLimit);
                            @endif
                            const r = await fetch('{{ route('bots.broadcasts.target-count', $bot) }}?' + params.toString(), {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const d = await r.json();
                            if (d.ok) {
                                this.bcEstCount = d.count || 0;
                                this.bcEligibleCount = d.eligible_count || 0;
                                this.bcEstHuman = d.estimated_human || '';
                                this.bcLimitApplied = !!d.limit_applied;
                                this.bcAppliedLimit = d.applied_limit;
                                this.bcCustomLimitApplied = !!d.custom_limit_applied;
                            } else {
                                this.bcEstimateError = 'Estimate unavailable';
                            }
                        } catch(e) {
                            this.bcEstimateError = 'Estimate unavailable';
                        }
                        this.bcEstLoading = false;
                    }
                }" x-init="fetchEstimate()" class="space-y-5">

                    {{-- A. Header --}}
                    @php
                        $userPlan = auth()->user()?->isAdmin() ? 'business' : strtolower(auth()->user()?->subscription_plan ?? 'free');
                        $planLimitLabel = match($userPlan) { 'business' => 'Unlimited', 'pro' => '100,000 users', default => '20,000 users' };
                        $planLimitNote  = match($userPlan) { 'business' => 'Business plan — unlimited broadcast audience.', 'pro' => 'Pro plan — broadcasts to the most recent 100,000 active users.', default => 'Free plan — broadcasts to the most recent 20,000 active users.' };
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-black text-[#F8FAFC]">Broadcasts</h3>
                            <span class="inline-flex items-center gap-1 rounded-full border border-[#22C55E]/30 bg-[#22C55E]/10 px-2.5 py-0.5 text-[10px] font-black text-[#22C55E]">
                                <span class="h-1.5 w-1.5 rounded-full bg-[#22C55E]"></span>Active
                            </span>
                            <span class="rounded-full border border-[#27213D] bg-[#151225] px-2.5 py-0.5 text-[10px] font-bold text-[#52525B]">{{ ucfirst($userPlan) }} · {{ $planLimitLabel }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                                <button type="button" onclick="location.reload()" class="inline-flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5 text-sm font-black text-[#A1A1AA] transition hover:text-[#F8FAFC]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                    Refresh
                                </button>
                                <button type="button" @click="composerOpen = !composerOpen"
                                    :class="composerOpen ? 'from-[#EF4444] to-[#DC2626] shadow-[0_0_20px_rgba(239,68,68,0.22)]' : 'from-[#8B5CF6] to-[#A855F7] shadow-[0_0_20px_rgba(139,92,246,0.22)]'"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r px-4 py-2 text-sm font-black text-white transition hover:-translate-y-0.5">
                                    <svg x-show="!composerOpen" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    <svg x-show="composerOpen" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    <span x-text="composerOpen ? 'Cancel' : 'Broadcast'"></span>
                                </button>
                        </div>
                    </div>

                    {{-- B. Stats Cards --}}
                    @php
                        $bcDrafts    = ($botBroadcasts ?? collect())->where('status', 'draft')->count();
                        $bcSending   = ($botBroadcasts ?? collect())->whereIn('status', ['scheduled','queued','running','sending'])->count();
                        $bcCompleted = ($botBroadcasts ?? collect())->where('status', 'completed')->count();
                        $bcFailed    = ($botBroadcasts ?? collect())->where('status', 'failed')->count();
                        $bcTotal     = ($botBroadcasts ?? collect())->count();
                        $bcAllActive = $broadcastTargetCounts['all_active'] ?? 0;
                        $bcActive24h = $broadcastTargetCounts['active_24h'] ?? 0;
                    @endphp
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        @foreach ([
                            ['Broadcasts',    $bcTotal,      '#A855F7', 'M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5'],
                            ['Drafts',        $bcDrafts,     '#71717A', 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
                            ['Sending',       $bcSending,    '#F59E0B', 'M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5'],
                            ['Audience',      $bcAllActive,  '#38BDF8', 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z'],
                            ['Completed',     $bcCompleted,  '#22C55E', 'm4.5 12.75 6 6 9-13.5'],
                            ['Failed',        $bcFailed,     '#EF4444', 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z'],
                        ] as [$statLabel, $statCount, $statColor, $statPath])
                            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-[10px] font-black uppercase tracking-widest" style="color: {{ $statColor }}80">{{ $statLabel }}</p>
                                    <svg class="h-4 w-4 shrink-0" style="color: {{ $statColor }}60" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $statPath }}"/></svg>
                                </div>
                                <p class="mt-2 text-2xl font-black text-[#F8FAFC]">{{ number_format($statCount) }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- C. Composer --}}
                    <div x-show="composerOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                        <form method="POST" action="{{ route('bots.broadcasts.store', $bot) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
                                {{-- Composer header --}}
                                <div class="relative overflow-hidden border-b border-[#27213D] px-5 py-4 sm:px-6">
                                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-[#8B5CF6]/5 to-transparent"></div>
                                    <div class="relative flex flex-wrap items-center gap-3">
                                        <div>
                                            <p class="text-sm font-black uppercase tracking-widest text-[#A855F7]">Message Builder</p>
                                            <p class="mt-0.5 text-xs text-[#94A3B8]">Supports text, image, CTA buttons, and parse modes.</p>
                                        </div>
                                        <div class="ml-auto flex flex-wrap gap-2">
                                            <span class="inline-flex items-center gap-1 rounded-full border border-[#22C55E]/25 bg-[#22C55E]/8 px-2.5 py-1 text-[10px] font-black text-[#22C55E]">Text ✓</span>
                                            <span class="inline-flex items-center gap-1 rounded-full border border-[#38BDF8]/25 bg-[#38BDF8]/8 px-2.5 py-1 text-[10px] font-black text-[#38BDF8]">Image ✓</span>
                                            <span class="inline-flex items-center gap-1 rounded-full border border-[#A855F7]/25 bg-[#A855F7]/8 px-2.5 py-1 text-[10px] font-black text-[#A855F7]">CTA ✓</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-0 lg:grid-cols-[1fr_300px]">
                                    {{-- Left: form --}}
                                    <div class="space-y-5 border-b border-[#27213D] p-5 sm:p-6 lg:border-b-0 lg:border-r">

                                        {{-- Title --}}
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Broadcast Title <span class="normal-case font-normal text-[#3D3658]">optional</span></label>
                                            <input name="title" x-model="bcTitle" maxlength="100"
                                                class="mt-2 w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15 transition"
                                                placeholder="e.g. Weekend promo, New update, Game alert">
                                        </div>

                                        {{-- Message Type --}}
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Message Type</label>
                                            <div class="mt-2 flex gap-2">
                                                <button type="button" @click="bcMsgType = 'text'"
                                                    :class="bcMsgType === 'text' ? 'border-[#8B5CF6]/60 bg-[#8B5CF6]/15 text-[#A855F7]' : 'border-[#27213D] bg-[#11101C] text-[#94A3B8] hover:text-[#A1A1AA]'"
                                                    class="flex flex-1 items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-black transition">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                                                    Text Message
                                                </button>
                                                <button type="button" @click="bcMsgType = 'image'"
                                                    :class="bcMsgType === 'image' ? 'border-[#38BDF8]/60 bg-[#38BDF8]/10 text-[#38BDF8]' : 'border-[#27213D] bg-[#11101C] text-[#94A3B8] hover:text-[#A1A1AA]'"
                                                    class="flex flex-1 items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-black transition">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                                    Image Message
                                                </button>
                                            </div>
                                            <input type="hidden" name="message_type" :value="bcMsgType">
                                        </div>

                                        {{-- Image Upload (image type only) --}}
                                        <div x-show="bcMsgType === 'image'" x-cloak>
                                            <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Image File <span class="normal-case font-normal text-[#EF4444]">required for image</span></label>
                                            <label class="mt-2 flex cursor-pointer flex-col items-center gap-3 rounded-2xl border border-dashed border-[#27213D] bg-[#11101C] px-4 py-6 text-center transition hover:border-[#38BDF8]/40 hover:bg-[#0F0D1A]">
                                                <svg class="h-8 w-8 text-[#38BDF8]/60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                                <div>
                                                    <p class="text-sm font-bold text-[#A1A1AA]" x-text="bcImageName || 'Click to choose or drag image here'"></p>
                                                    <p class="mt-1 text-[10px] text-[#52525B]">JPG, PNG, WEBP · Max {{ number_format((int) config('broadcasts.image.max_size_kb', 10240) / 1024) }} MB</p>
                                                </div>
                                                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="sr-only"
                                                    @change="
                                                        const file = $event.target.files[0];
                                                        bcImageName = file?.name || '';
                                                        if (bcImagePreviewUrl) URL.revokeObjectURL(bcImagePreviewUrl);
                                                        bcImagePreviewUrl = file ? URL.createObjectURL(file) : '';
                                                    ">
                                            </label>
                                        </div>

                                        {{-- Message / Caption --}}
                                        <div>
                                            <div class="flex items-center justify-between">
                                                <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]" x-text="bcMsgType === 'image' ? 'Caption' : 'Message'">Message</label>
                                                <span class="text-[10px]" :class="bcLen > bcMaxLen - 100 ? 'font-black text-[#EF4444]' : 'text-[#3D3658]'" x-text="bcLen + ' / ' + bcMaxLen">0 / 4096</span>
                                            </div>
                                            <textarea name="message" x-model="bcMsg" rows="7" :maxlength="bcMaxLen"
                                                class="mt-2 w-full resize-y rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 font-mono text-sm leading-6 text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15 transition"
                                                :placeholder="bcMsgType === 'image' ? 'Optional caption for the image…' : 'Hello! Thanks for using our bot…'"></textarea>
                                        </div>

                                        {{-- Parse Mode --}}
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Parse Mode</label>
                                            <input type="hidden" name="parse_mode" :value="bcParseMode">
                                            <div class="relative mt-2" @click.away="parseModeOpen = false">
                                                <button type="button" @click="parseModeOpen = !parseModeOpen"
                                                    class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] transition focus:outline-none"
                                                    :class="parseModeOpen ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                                                    <span x-text="bcParseModeLabel" class="text-left"></span>
                                                    <svg class="ml-2 h-4 w-4 shrink-0 text-[#94A3B8] transition-transform duration-150" :class="parseModeOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                                                </button>
                                                <div x-show="parseModeOpen" x-cloak
                                                    x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                                    @foreach (['' => 'None — plain text', 'HTML' => 'HTML — use <b>, <i>, <a> tags', 'Markdown' => 'Markdown — use *bold*, _italic_'] as $pv => $pl)
                                                        <button type="button"
                                                            @click="bcParseMode = '{{ $pv }}'; parseModeOpen = false"
                                                            class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm transition hover:bg-[#1D1930]"
                                                            :class="bcParseMode === '{{ $pv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                                            <svg class="h-3.5 w-3.5 shrink-0 transition" :class="bcParseMode === '{{ $pv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                            <span>{{ $pl }}</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        {{-- CTA Button --}}
                                        <div class="rounded-2xl border border-[#27213D] bg-[#090713] p-4">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">CTA Button <span class="normal-case font-normal text-[#3D3658]">optional</span></p>
                                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="text-[10px] font-bold text-[#52525B]">Button Text</label>
                                                    <input name="cta_text" x-model="bcCtaText" maxlength="40"
                                                        class="mt-1.5 w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15 transition"
                                                        placeholder="Open website">
                                                </div>
                                                <div>
                                                    <label class="text-[10px] font-bold text-[#52525B]">Button URL</label>
                                                    <input name="cta_url" x-model="bcCtaUrl" maxlength="2048"
                                                        class="mt-1.5 w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15 transition"
                                                        placeholder="https://example.com">
                                                </div>
                                            </div>
                                            <p class="mt-2 text-[10px] text-[#3D3658]">Both fields must be filled together. URL must start with https://</p>
                                        </div>

                                        {{-- Disable web preview (text only) --}}
                                        <div x-show="bcMsgType === 'text'" x-cloak>
                                            <label class="flex cursor-pointer items-center gap-2.5">
                                                <input type="checkbox" name="disable_web_page_preview" value="1" x-model="bcDisablePreview"
                                                    class="h-4 w-4 rounded border-[#27213D] bg-[#11101C] text-[#8B5CF6] focus:ring-[#8B5CF6]/30">
                                                <span class="text-sm font-bold text-[#A1A1AA]">Disable link preview in message</span>
                                            </label>
                                            <p class="mt-1 pl-6.5 text-[10px] text-[#3D3658]">Prevents Telegram from showing a web page preview for URLs in the message.</p>
                                        </div>

                                        @if (auth()->user()?->isAdmin())
                                            <div>
                                                <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Maximum recipients for this broadcast</label>
                                                <input type="number" name="custom_recipient_limit" x-model="bcCustomLimit" @input.debounce.400ms="fetchEstimate()" min="1" max="10000000"
                                                    class="mt-2 w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15 transition"
                                                    placeholder="Leave empty for default limit">
                                                <p class="mt-1.5 text-[10px] text-[#3D3658]">Admin override: limit this broadcast to a specific number of most recently active recipients. If empty, default plan/config limit will be used.</p>
                                            </div>
                                        @endif

                                        {{-- Target Audience --}}
                                        <div>
                                            <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Target Audience</label>
                                            <input type="hidden" name="target_type" :value="bcTarget">
                                            <div class="relative mt-2" @click.away="targetOpen = false">
                                                <button type="button" @click="targetOpen = !targetOpen"
                                                    class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] transition focus:outline-none"
                                                    :class="targetOpen ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                                                    <span x-text="bcTargetLabel" class="text-left"></span>
                                                    <svg class="ml-2 h-4 w-4 shrink-0 text-[#94A3B8] transition-transform duration-150" :class="targetOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                                                </button>
                                                <div x-show="targetOpen" x-cloak
                                                    x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                                    @foreach ([
                                                        'all_active'    => 'All Active Users',
                                                        'active_24h'    => 'Active in last 24 hours',
                                                        'active_48h'    => 'Active in last 48 hours',
                                                        'active_72h'    => 'Active in last 72 hours',
                                                        'active_7d'     => 'Active in last 7 days',
                                                         'active_30d'    => 'Active in last 30 days',
                                                         'paused_users'  => 'Paused users',
                                                         'blocked_users' => 'Blocked users',
                                                         'specific_users' => 'Uploaded phones / IDs',
                                                     ] as $tv => $tl)
                                                        <button type="button"
                                                            @click="bcTarget = '{{ $tv }}'; targetOpen = false; fetchEstimate()"
                                                            class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm transition hover:bg-[#1D1930]"
                                                            :class="bcTarget === '{{ $tv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                                            <svg class="h-3.5 w-3.5 shrink-0" :class="bcTarget === '{{ $tv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                            <span>{{ $tl }} ({{ number_format($broadcastTargetCounts[$tv] ?? 0) }})</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div x-show="bcTarget === 'specific_users'" x-cloak class="mt-3 rounded-2xl border border-[#27213D] bg-[#090713] p-4">
                                                <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Upload Recipients</p>
                                                <label class="mt-2 flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-[#27213D] bg-[#11101C] px-4 py-5 text-center transition hover:border-[#38BDF8]/40">
                                                    <svg class="h-7 w-7 text-[#38BDF8]/60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                                    <span class="text-sm font-bold text-[#A1A1AA]" x-text="bcRecipientUploadName || 'Upload CSV or TXT'"></span>
                                                    <span class="text-[10px] text-[#52525B]">One phone number, Telegram ID, or @username per line</span>
                                                    <input type="file" name="recipient_file" accept=".csv,.txt,text/csv,text/plain" class="sr-only"
                                                        @change="
                                                            const file = $event.target.files[0];
                                                            bcRecipientUploadName = file?.name || '';
                                                            if (!file) { updateSpecificEstimate(0); return; }
                                                            file.text().then(text => updateSpecificEstimate(countRecipientText(text)));
                                                        ">
                                                </label>
                                                <textarea name="recipient_text" x-model="bcRecipientPaste" @input.debounce.250ms="updateSpecificEstimate()" rows="4"
                                                    class="mt-3 w-full resize-y rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 font-mono text-xs leading-5 text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15 transition"
                                                    placeholder="+2348012345678&#10;123456789&#10;@telegram_user"></textarea>
                                            </div>

                                            {{-- Estimate display --}}
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                <div class="flex items-center gap-2.5 rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-3">
                                                    <svg class="h-4 w-4 shrink-0 text-[#38BDF8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
                                                    <div>
                                                        <p class="text-[10px] text-[#52525B]">Recipients</p>
                                                        <p class="text-sm font-black text-[#38BDF8]" x-text="bcEstLoading ? '…' : bcEstCount.toLocaleString()">0</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2.5 rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-3">
                                                    <svg class="h-4 w-4 shrink-0 text-[#A855F7]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                                    <div>
                                                        <p class="text-[10px] text-[#52525B]">Est. Time</p>
                                                        <p class="text-sm font-black text-[#A855F7]" x-text="bcEstLoading ? '…' : (bcEstHuman || 'Instant')">—</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-2 rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2 text-[10px] text-[#52525B]">
                                                <p x-show="bcEstimateError" x-text="bcEstimateError" class="font-bold text-[#F59E0B]"></p>
                                                <p x-show="!bcEstimateError">
                                                    Eligible users:
                                                    <span class="font-black text-[#A1A1AA]" x-text="bcEligibleCount.toLocaleString()">0</span>
                                                    <template x-if="bcLimitApplied && bcAppliedLimit">
                                                        <span> · Plan limit applied: <span class="font-black text-[#F59E0B]" x-text="Number(bcAppliedLimit).toLocaleString()"></span></span>
                                                    </template>
                                                    <template x-if="bcCustomLimitApplied">
                                                        <span> · Admin custom limit applied</span>
                                                    </template>
                                                </p>
                                            </div>
                                            <p class="mt-1.5 text-[10px] text-[#3D3658]">{{ $planLimitNote }}</p>
                                        </div>

                                        {{-- Action buttons --}}
                                        <div class="flex flex-wrap items-center gap-3 border-t border-[#27213D] pt-5">
                                            <button type="submit" class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-6 py-3 text-sm font-black text-white shadow-[0_0_20px_rgba(139,92,246,0.25)] transition hover:-translate-y-0.5">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                                Save Draft
                                            </button>
                                            <button type="submit" name="send_now" value="1" class="flex items-center gap-2 rounded-xl border border-[#22C55E]/35 bg-[#22C55E]/10 px-5 py-3 text-sm font-black text-[#22C55E] transition hover:bg-[#22C55E]/18">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                                                Send Now
                                            </button>
                                            <button type="reset" @click="bcMsg='';bcTitle='';bcCtaText='';bcCtaUrl='';bcImageName='';if(bcImagePreviewUrl)URL.revokeObjectURL(bcImagePreviewUrl);bcImagePreviewUrl='';bcRecipientUploadName='';bcRecipientUploadCount=0;bcRecipientPaste='';bcParseMode='';bcDisablePreview=false;bcMsgType='text';bcCustomLimit='';fetchEstimate();"
                                                class="flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#151225] px-5 py-3 text-sm font-black text-[#94A3B8] transition hover:text-[#A1A1AA]">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                                Clear
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Right: Telegram preview + audience breakdown --}}
                                    <div class="space-y-5 bg-[#090713] p-5">
                                        {{-- Preview --}}
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-widest text-[#52525B]">Telegram Preview</p>
                                            <div class="mt-3">
                                                <div class="flex items-end gap-2">
                                                    <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-gradient-to-br from-[#8B5CF6] to-[#5B21B6] text-xs font-black text-white">{{ strtoupper(substr($bot->name, 0, 1)) }}</div>
                                                    <div class="flex-1 max-w-[220px]">
                                                        <div class="rounded-2xl rounded-bl-sm bg-[#1E1B2E] px-3.5 py-2.5 shadow-md">
                                                            <p class="text-xs font-black text-[#A855F7]">{{ $bot->name }}</p>
                                                            {{-- Image placeholder --}}
                                                            <div x-show="bcMsgType === 'image' && !bcImagePreviewUrl" class="mt-2 flex h-24 items-center justify-center rounded-xl bg-[#27213D] text-[#52525B]" x-cloak>
                                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                                            </div>
                                                            <img x-show="bcMsgType === 'image' && bcImagePreviewUrl" :src="bcImagePreviewUrl" alt="" class="mt-2 max-h-40 w-full rounded-xl object-cover" x-cloak>
                                                            <p x-show="bcPreviewText" x-text="bcPreviewText" class="mt-1 whitespace-pre-wrap break-words text-sm leading-5 text-[#F8FAFC]"></p>
                                                            <p x-show="!bcPreviewText" class="mt-1 text-sm italic text-[#3D3658]">Your message preview...</p>
                                                            <p class="mt-1.5 text-right text-[9px] text-[#3D3658]">{{ now()->format('H:i') }} ✓</p>
                                                        </div>
                                                        {{-- CTA button preview --}}
                                                        <div x-show="bcCtaText" class="mt-1 rounded-xl border border-[#229ED9]/30 bg-[#229ED9]/10 px-3 py-2 text-center text-xs font-black text-[#229ED9]" x-text="bcCtaText" x-cloak></div>
                                                    </div>
                                                </div>
                                                {{-- Parse mode badge --}}
                                                <div class="mt-3 flex flex-wrap gap-1.5">
                                                    <template x-if="bcParseMode">
                                                        <span class="rounded-full border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[9px] font-black text-[#94A3B8]" x-text="bcParseMode + ' mode'"></span>
                                                    </template>
                                                    <template x-if="bcDisablePreview">
                                                        <span class="rounded-full border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[9px] font-black text-[#94A3B8]">No preview</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Audience breakdown --}}
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-widest text-[#52525B]">Audience</p>
                                            <div class="mt-3 space-y-2">
                                                @foreach ([
                                                    ['all_active',    'All Active',  '#A855F7'],
                                                    ['active_24h',    'Active 24h',  '#22C55E'],
                                                    ['active_48h',    'Active 48h',  '#38BDF8'],
                                                    ['active_7d',     'Active 7d',   '#F59E0B'],
                                                    ['active_30d',    'Active 30d',  '#71717A'],
                                                    ['paused_users',  'Paused',      '#F59E0B'],
                                                    ['blocked_users', 'Blocked',     '#EF4444'],
                                                ] as [$tKey, $tLabel, $tColor])
                                                    @php $tCount = $broadcastTargetCounts[$tKey] ?? 0; $tMax = max(1, $broadcastTargetCounts['all_active'] ?? 1); $tPct = min(100, round($tCount / $tMax * 100)); @endphp
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-16 shrink-0 text-[10px] text-[#94A3B8]">{{ $tLabel }}</span>
                                                        <div class="h-1 flex-1 overflow-hidden rounded-full bg-[#27213D]">
                                                            <div class="h-full rounded-full" style="width: {{ $tPct }}%; background-color: {{ $tColor }}60;"></div>
                                                        </div>
                                                        <span class="w-8 shrink-0 text-right text-[10px] font-black" style="color: {{ $tColor }}">{{ number_format($tCount) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- D. Audience Targeting Cards (shown when composer closed) --}}
                    <div x-show="!composerOpen" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ([
                            ['all_active',    'All Active',     'Eligible for broadcast',        '#A855F7'],
                            ['active_24h',    'Active 24h',     'Seen in last 24 hours',         '#22C55E'],
                            ['active_48h',    'Active 48h',     'Seen in last 48 hours',         '#38BDF8'],
                            ['active_72h',    'Active 72h',     'Seen in last 72 hours',         '#38BDF8'],
                            ['active_7d',     'Active 7 days',  'Seen in last 7 days',           '#F59E0B'],
                            ['active_30d',    'Active 30 days', 'Seen in last 30 days',          '#71717A'],
                            ['paused_users',  'Paused',         'Users who paused the bot',      '#F59E0B'],
                            ['blocked_users', 'Blocked',        'Users who blocked the bot',     '#EF4444'],
                        ] as [$tKey, $tLabel, $tDesc, $tColor])
                            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[10px] font-bold text-[#94A3B8]">{{ $tLabel }}</p>
                                    <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $tColor }}"></span>
                                </div>
                                <p class="mt-2 text-2xl font-black text-[#F8FAFC]">{{ number_format($broadcastTargetCounts[$tKey] ?? 0) }}</p>
                                <p class="mt-0.5 text-[10px] text-[#3D3658]">{{ $tDesc }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- E. Status flash --}}
                    @if (session('status') && session('errors') === null)
                        @php
                            $flashMsg = (string) session('status');
                            $isBroadcastFlash = str_contains($flashMsg, 'roadcast') || str_contains($flashMsg, 'Processed') || str_contains($flashMsg, 'Test');
                        @endphp
                        @if ($isBroadcastFlash)
                            <div class="rounded-2xl border border-[#22C55E]/25 bg-[#22C55E]/8 px-4 py-3 text-sm font-bold text-[#22C55E]">{{ $flashMsg }}</div>
                        @endif
                    @endif
                    @if ($errors->has('broadcast'))
                        <div class="rounded-2xl border border-[#EF4444]/25 bg-[#EF4444]/8 px-4 py-3 text-sm font-bold text-[#EF4444]">{{ $errors->first('broadcast') }}</div>
                    @endif
                    @if ($errors->any() && ! $errors->has('broadcast'))
                        <div class="rounded-2xl border border-[#EF4444]/25 bg-[#EF4444]/8 px-4 py-3 text-sm font-bold text-[#EF4444]">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    {{-- F. Broadcast History --}}
                    <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
                        <div class="flex items-center justify-between gap-4 border-b border-[#27213D] px-5 py-4 sm:px-6">
                            <div>
                                <p class="text-sm font-black text-[#F8FAFC]">Broadcast History</p>
                                <p class="text-xs text-[#94A3B8]">{{ ($botBroadcasts ?? collect())->count() }} {{ Str::plural('broadcast', ($botBroadcasts ?? collect())->count()) }} · most recent 25 shown</p>
                            </div>
                        </div>
                        <div class="divide-y divide-[#1B172B]">
                            @forelse (($botBroadcasts ?? collect()) as $broadcast)
                                @php
                                    $bStatusColor = match($broadcast->status) {
                                        'draft'      => '#71717A',
                                        'scheduled'  => '#A855F7',
                                        'queued'     => '#38BDF8',
                                        'running'    => '#F59E0B',
                                        'sending'    => '#F59E0B',
                                        'completed'  => '#22C55E',
                                        'failed'     => '#EF4444',
                                        'cancelled'  => '#52525B',
                                        default      => '#71717A',
                                    };
                                    $bStatusLabel = match($broadcast->status) {
                                        'scheduled' => 'Scheduled',
                                        'queued'    => 'Queued',
                                        'running'   => 'Running',
                                        'sending'   => 'Sending',
                                        default   => ucfirst($broadcast->status),
                                    };
                                    $bProcessed = ($broadcast->sent_count ?? 0) + ($broadcast->failed_count ?? 0);
                                    $bPending   = $broadcast->pending_count ?? max(0, ($broadcast->target_count ?? 0) - $bProcessed);
                                    $bProgress  = ($broadcast->target_count ?? 0) > 0
                                        ? min(100, floor($bProcessed / $broadcast->target_count * 100))
                                        : 0;
                                    $bIsActive  = in_array($broadcast->status, ['scheduled', 'queued', 'running', 'sending'], true);
                                    $bIsDraft   = $broadcast->status === 'draft';
                                @endphp
                                <div class="group px-5 py-5 transition hover:bg-[#151225]/40 sm:px-6">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">

                                            {{-- Title row --}}
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-black text-[#F8FAFC]">{{ $broadcast->title ?: 'Untitled Broadcast' }}</p>
                                                {{-- Status badge --}}
                                                <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide"
                                                    style="border-color: {{ $bStatusColor }}35; background-color: {{ $bStatusColor }}12; color: {{ $bStatusColor }}">
                                                    <span class="h-1 w-1 rounded-full{{ $bIsActive ? ' animate-pulse' : '' }}" style="background-color: {{ $bStatusColor }}"></span>
                                                    {{ $bStatusLabel }}
                                                </span>
                                                {{-- Type badge --}}
                                                @if ($broadcast->message_type === 'image')
                                                    <span class="inline-flex items-center gap-1 rounded-full border border-[#38BDF8]/25 bg-[#38BDF8]/8 px-2 py-0.5 text-[10px] font-black text-[#38BDF8]">
                                                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                                        Image
                                                    </span>
                                                @endif
                                                @if ($broadcast->cta_text)
                                                    <span class="inline-flex items-center gap-1 rounded-full border border-[#229ED9]/20 bg-[#229ED9]/8 px-2 py-0.5 text-[10px] font-black text-[#229ED9]">CTA</span>
                                                @endif
                                                @if ($broadcast->parse_mode)
                                                    <span class="rounded-full border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[10px] font-black text-[#94A3B8]">{{ $broadcast->parse_mode }}</span>
                                                @endif
                                            </div>

                                            {{-- Message preview --}}
                                            @if ($broadcast->message_type === 'image')
                                                @if ($broadcast->image_url)
                                                    <img src="{{ $broadcast->image_url }}" alt="Broadcast image" class="mt-3 max-h-32 rounded-xl border border-[#27213D] object-cover">
                                                @else
                                                    <p class="mt-2 text-[10px] font-bold text-[#38BDF8]">Image uploaded</p>
                                                @endif
                                            @endif
                                            <p class="mt-2 break-words text-sm leading-5 text-[#94A3B8]">{{ Str::limit($broadcast->message ?: ($broadcast->message_type === 'image' ? '(Image with no caption)' : '(No message)'), 160) }}</p>

                                            {{-- Meta row --}}
                                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-[#52525B]">
                                                <span>{{ number_format($broadcast->target_count ?? 0) }} targets</span>
                                                <span class="text-[#3D3658]">&middot;</span>
                                                <span>{{ ucwords(str_replace('_', ' ', $broadcast->target_type)) }}</span>
                                                @if ($broadcast->estimated_seconds && $bIsDraft)
                                                    <span class="text-[#3D3658]">&middot;</span>
                                                    <span>Est. {{ $broadcast->estimated_send_time_human }}</span>
                                                @endif
                                                <span class="text-[#3D3658]">&middot;</span>
                                                <span>{{ $broadcast->created_at->diffForHumans() }}</span>
                                                @if ($broadcast->started_at && ! $bIsDraft)
                                                    <span class="text-[#3D3658]">&middot;</span>
                                                    <span>Started {{ $broadcast->started_at->diffForHumans() }}</span>
                                                @endif
                                                @if ($broadcast->completed_at)
                                                    <span class="text-[#3D3658]">&middot;</span>
                                                    <span>Finished {{ $broadcast->completed_at->diffForHumans() }}</span>
                                                @endif
                                            </div>

                                            {{-- Progress bar (non-draft) --}}
                                            @if (! $bIsDraft && ($broadcast->target_count ?? 0) > 0)
                                                <div class="mt-3">
                                                    <div class="flex items-center justify-between text-[10px] text-[#94A3B8]">
                                                        <span>
                                                            <span class="text-[#22C55E] font-bold">{{ number_format($broadcast->sent_count ?? 0) }} sent</span>
                                                            <span class="mx-1 text-[#3D3658]">·</span>
                                                            <span class="text-[#EF4444]">{{ number_format($broadcast->failed_count ?? 0) }} failed</span>
                                                            <span class="mx-1 text-[#3D3658]">·</span>
                                                            {{ number_format($bPending) }} pending
                                                        </span>
                                                        <span class="font-black" style="color: {{ $bStatusColor }}">{{ $bProgress }}%</span>
                                                    </div>
                                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[#27213D]">
                                                        <div class="h-full rounded-full transition-all duration-500" style="width: {{ $bProgress }}%; background: linear-gradient(90deg, {{ $bStatusColor }}80, {{ $bStatusColor }});"></div>
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Plan limit notice --}}
                                            @if (data_get($broadcast->metadata, 'limit_applied') === true)
                                                <p class="mt-2 text-[10px] font-bold text-[#F59E0B]">
                                                    Plan limit applied: most recent {{ number_format((int) data_get($broadcast->metadata, 'applied_limit', 0)) }} users
                                                    @if (data_get($broadcast->metadata, 'plan_at_send', 'free') === 'free') · <a href="#" class="underline">Upgrade for larger audience</a>@endif
                                                </p>
                                            @endif

                                            @if (data_get($broadcast->metadata, 'custom_recipient_limit'))
                                                <p class="mt-2 text-[10px] font-bold text-[#38BDF8]">
                                                    Admin custom limit: most recent {{ number_format((int) data_get($broadcast->metadata, 'custom_recipient_limit')) }} users
                                                </p>
                                            @endif

                                            {{-- Error message --}}
                                            @if ($broadcast->status === 'failed' && filled(data_get($broadcast->metadata, 'last_error')))
                                                <p class="mt-2 rounded-lg border border-[#EF4444]/20 bg-[#EF4444]/8 px-3 py-2 text-[10px] font-bold text-[#EF4444]">{{ Str::limit(data_get($broadcast->metadata, 'last_error'), 200) }}</p>
                                            @endif
                                        </div>

                                        {{-- Action buttons --}}
                                        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
                                            {{-- Draft actions --}}
                                            @if ($bIsDraft)
                                                <form method="POST" action="{{ route('bots.broadcasts.test-send', [$bot, $broadcast]) }}">
                                                    @csrf
                                                    <button type="submit" class="w-full rounded-xl border border-[#38BDF8]/25 bg-[#38BDF8]/8 px-3.5 py-2 text-xs font-black text-[#38BDF8] transition hover:bg-[#38BDF8]/15 sm:w-auto">Test Send</button>
                                                </form>
                                                <form method="POST" action="{{ route('bots.broadcasts.start', [$bot, $broadcast]) }}">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        data-confirm
                                                        data-confirm-type="default"
                                                        data-confirm-title="Start broadcast?"
                                                        data-confirm-message="This will begin sending messages to {{ number_format($broadcast->target_count ?? 0) }} users. You can cancel it after starting."
                                                        data-confirm-btn="Start Broadcast"
                                                        class="w-full rounded-xl border border-[#22C55E]/25 bg-[#22C55E]/10 px-3.5 py-2 text-xs font-black text-[#22C55E] transition hover:bg-[#22C55E]/20 sm:w-auto"
                                                    >
                                                        <svg class="mr-1 inline h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                                                        Start Broadcast
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Active actions --}}
                                            @if ($bIsActive)
                                                <form method="POST" action="{{ route('bots.broadcasts.process-next-batch', [$bot, $broadcast]) }}">
                                                    @csrf
                                                    <button type="submit" class="w-full rounded-xl border border-[#38BDF8]/25 bg-[#38BDF8]/8 px-3.5 py-2 text-xs font-black text-[#38BDF8] transition hover:bg-[#38BDF8]/15 sm:w-auto">Process Next Batch</button>
                                                </form>
                                                <form method="POST" action="{{ route('bots.broadcasts.cancel', [$bot, $broadcast]) }}">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        data-confirm
                                                        data-confirm-type="warning"
                                                        data-confirm-title="Cancel broadcast?"
                                                        data-confirm-message="Pending messages will not be sent. This cannot be restarted."
                                                        data-confirm-btn="Cancel Broadcast"
                                                        class="w-full rounded-xl border border-[#F59E0B]/20 bg-[#F59E0B]/8 px-3.5 py-2 text-xs font-black text-[#F59E0B] transition hover:bg-[#F59E0B]/15 sm:w-auto"
                                                    >Cancel</button>
                                                </form>
                                            @endif

                                            @if (in_array($broadcast->status, ['completed', 'failed'], true) && ($broadcast->failed_count ?? 0) > 0)
                                                <form method="POST" action="{{ route('bots.broadcasts.retry-failed', [$bot, $broadcast]) }}">
                                                    @csrf
                                                    <button type="submit" class="w-full rounded-xl border border-[#38BDF8]/25 bg-[#38BDF8]/8 px-3.5 py-2 text-xs font-black text-[#38BDF8] transition hover:bg-[#38BDF8]/15 sm:w-auto">Retry Failed</button>
                                                </form>
                                            @endif

                                            {{-- Delete (draft only, hover reveal) --}}
                                            @if ($bIsDraft)
                                                <form method="POST" action="{{ route('bots.broadcasts.destroy', [$bot, $broadcast]) }}">
                                                    @csrf @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        data-confirm
                                                        data-confirm-type="danger"
                                                        data-confirm-title="Delete broadcast draft?"
                                                        data-confirm-message="This will permanently delete the broadcast draft. This cannot be undone."
                                                        data-confirm-btn="Delete Draft"
                                                        class="w-full rounded-xl border border-[#EF4444]/20 bg-[#EF4444]/8 px-3.5 py-2 text-xs font-black text-[#EF4444] opacity-0 transition hover:bg-[#EF4444]/15 group-hover:opacity-100 sm:w-auto"
                                                    >Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center gap-4 py-16 text-center">
                                    <div class="grid h-14 w-14 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#94A3B8]">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-black text-[#A1A1AA]">No broadcasts yet</p>
                                        <p class="mt-1 text-sm text-[#52525B]">Open the Message Builder above to create your first broadcast.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- G. Broadcast Safety Card --}}
                    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
                        <div class="flex items-center gap-2.5 border-b border-[#1B172B] px-5 py-3.5">
                            <svg class="h-4 w-4 shrink-0 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                            <p class="text-xs font-black uppercase tracking-widest text-[#94A3B8]">Broadcast Safety &amp; Plan Limits</p>
                        </div>
                        <div class="grid gap-4 p-5 sm:grid-cols-2">
                            <div class="space-y-2">
                                <p class="text-[10px] font-black uppercase tracking-wider text-[#52525B]">Plan Limits</p>
                                @foreach ([
                                    ['Free plan',     'Sends to most recent 20,000 active users.',   '#71717A'],
                                    ['Pro plan',      'Sends to most recent 100,000 active users.',  '#A855F7'],
                                    ['Business plan', 'Unlimited broadcast audience.',               '#22C55E'],
                                ] as [$planName, $planDesc, $planColor])
                                    <div class="flex items-start gap-2">
                                        <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $planColor }}"></span>
                                        <div>
                                            <span class="text-xs font-black" style="color: {{ $planColor }}">{{ $planName }}</span>
                                            <span class="text-xs text-[#94A3B8]"> — {{ $planDesc }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="space-y-2">
                                <p class="text-[10px] font-black uppercase tracking-wider text-[#52525B]">Safety Guidelines</p>
                                @foreach ([
                                    'Deleted and blocked users are excluded automatically.',
                                    'Broadcasts send in safe batches and can be cancelled.',
                                    'Image broadcasts support JPG, PNG, WEBP up to 10 MB.',
                                    'Always test your broadcast before sending to a large audience.',
                                    'Telegram may rate-limit bots that send unsolicited messages.',
                                ] as $tip)
                                    <div class="flex items-start gap-2">
                                        <svg class="mt-0.5 h-3 w-3 shrink-0 text-[#52525B]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        <p class="text-xs text-[#94A3B8]">{{ $tip }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
                {{-- Bot Data --}}
                <div x-show="adminTab === 'botData'">
                    <div class="flex flex-col items-center gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] py-14 text-center">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#94A3B8]">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                        </div>
                        <p class="text-sm font-black text-[#A1A1AA]">Bot Data Storage</p>
                        <p class="text-xs text-[#94A3B8]">Coming soon — persistent key-value storage for your bot.</p>
                    </div>
                </div>

                {{-- User Data --}}
                <div x-show="adminTab === 'userData'">
                    <div class="flex flex-col items-center gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] py-14 text-center">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#94A3B8]">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                        </div>
                        <p class="text-sm font-black text-[#A1A1AA]">Per-User Data Storage</p>
                        <p class="text-xs text-[#94A3B8]">Coming soon — browse and manage data stored per Telegram user.</p>
                    </div>
                </div>

                {{-- Permissions --}}
                <div x-show="adminTab === 'permissions'">
                    <div class="flex flex-col items-center gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] py-14 text-center">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#94A3B8]">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        </div>
                        <p class="text-sm font-black text-[#A1A1AA]">Role Permissions</p>
                        <p class="text-xs text-[#94A3B8]">Coming soon — fine-grained access control for bot collaborators.</p>
                    </div>
                </div>
            </section>

            {{-- ══════════════════════════════════════════════ SETTINGS ══ --}}
            <section x-show="tab === 'settings'" x-cloak class="space-y-4">

                {{-- Bot ID --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Bot ID</p>
                    <div class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3">
                        <span class="font-mono text-sm font-black text-[#F8FAFC]">{{ $bot->id }}</span>
                        <button onclick="navigator.clipboard.writeText('{{ $bot->id }}').then(()=>{this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)})" class="shrink-0 rounded-lg border border-[#27213D] bg-[#0F0D1A] px-3 py-1.5 text-xs font-black text-[#94A3B8] transition hover:border-[#38BDF8]/40 hover:text-[#38BDF8]">Copy</button>
                    </div>
                </div>

                {{-- Bot Name --}}
                <form method="POST" action="{{ route('bots.settings.update', $bot) }}" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                    @csrf @method('PATCH')
                    <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Bot Name</p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <input name="name" value="{{ old('name', $bot->name) }}" required maxlength="100" class="flex-1 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#71717A] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 transition">
                        <button class="shrink-0 rounded-xl border border-[#8B5CF6]/40 bg-[#8B5CF6]/10 px-4 py-2.5 text-sm font-black text-[#8B5CF6] transition hover:bg-[#8B5CF6]/18">Update</button>
                    </div>
                    @error('name') <p class="mt-2 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
                </form>

                {{-- Bot Token --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Bot Token</p>
                    <div class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3">
                        <span class="min-w-0 flex-1 overflow-hidden break-all font-mono text-xs text-[#F8FAFC]" x-text="tokenVisible ? '{{ $bot->maskedToken() }}' : '••••••••••••••••••••••••••••••••••••••••'"></span>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button onclick="navigator.clipboard.writeText('{{ $bot->maskedToken() }}')" class="rounded-lg border border-[#27213D] bg-[#0F0D1A] p-1.5 text-[#94A3B8] transition hover:border-[#38BDF8]/40 hover:text-[#38BDF8]">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>
                            </button>
                            <button @click="tokenVisible = !tokenVisible" class="rounded-lg border border-[#27213D] bg-[#0F0D1A] p-1.5 text-[#94A3B8] transition hover:border-[#8B5CF6]/40 hover:text-[#8B5CF6]">
                                <svg x-show="!tokenVisible" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                <svg x-show="tokenVisible" x-cloak class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-[#94A3B8]">Encrypted at rest. Masked in the UI. Never shared with third parties.</p>
                </div>

                {{-- ════ CUSTOM WEBHOOK ════ --}}
                @php
                    $uwSetting  = $bot->setting;
                    $uwEnabled  = $uwSetting?->user_webhook_enabled;
                    $uwEndpoint = $uwSetting?->userWebhookEndpoint();
                @endphp

                @if ($hasCustomWebhook ?? false)
                    {{-- ── UNLOCKED ── --}}
                    <div
                        x-data="{
                            testing: false,
                            testResult: null,
                            testError: null,
                            async runTest() {
                                this.testing = true; this.testResult = null; this.testError = null;
                                try {
                                    const r = await fetch('{{ route('bots.user-webhook.test', $bot) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? ''
                                        }
                                    });
                                    const d = await r.json();
                                    if (d.ok) { this.testResult = d; } else { this.testError = d.error || 'Test failed.'; }
                                } catch(e) { this.testError = 'Network error. Could not complete the test.'; }
                                finally { this.testing = false; }
                            }
                        }"
                        class="space-y-4"
                    >
                        {{-- Incoming Webhook Endpoint card --}}
                        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                            {{-- Header --}}
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Custom Webhook</p>
                                    <p class="mt-1 text-xs text-[#94A3B8]">Use this endpoint to receive POST callbacks from external platforms into this bot.</p>
                                </div>
                                @if ($uwEnabled && $uwEndpoint)
                                    <span class="shrink-0 rounded-full border border-[#22C55E]/30 bg-[#22C55E]/10 px-2.5 py-0.5 text-[10px] font-black text-[#22C55E]">Active</span>
                                @else
                                    <span class="shrink-0 rounded-full border border-[#71717A]/30 bg-[#71717A]/10 px-2.5 py-0.5 text-[10px] font-black text-[#94A3B8]">Inactive</span>
                                @endif
                            </div>

                            {{-- Endpoint label + URL --}}
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Incoming Webhook Endpoint</p>
                                @if ($uwEnabled && $uwEndpoint)
                                    <div class="mt-2 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3">
                                        <div class="flex items-start gap-2">
                                            <p class="min-w-0 flex-1 break-all font-mono text-xs leading-relaxed text-[#A1A1AA]">{{ $uwEndpoint }}</p>
                                            <button
                                                onclick="navigator.clipboard.writeText('{{ $uwEndpoint }}').then(()=>{this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)})"
                                                class="mt-0.5 shrink-0 rounded-lg border border-[#27213D] bg-[#0F0D1A] px-3 py-1.5 text-xs font-black text-[#94A3B8] transition hover:border-[#38BDF8]/40 hover:text-[#38BDF8]"
                                            >Copy</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-2 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3 text-xs text-[#52525B]">
                                        No endpoint generated yet. Click <strong class="text-[#94A3B8]">Generate</strong> to create a unique URL.
                                    </div>
                                @endif
                            </div>

                            {{-- Generate / Disable buttons --}}
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('bots.user-webhook.regenerate', $bot) }}">
                                    @csrf
                                    <button class="rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2 text-sm font-black text-[#A1A1AA] transition hover:border-[#8B5CF6]/30 hover:text-[#8B5CF6]">
                                        {{ $uwEnabled && $uwEndpoint ? 'Regenerate' : 'Generate' }}
                                    </button>
                                </form>
                                @if ($uwEnabled && $uwEndpoint)
                                    <form method="POST" action="{{ route('bots.user-webhook.disable', $bot) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            data-confirm
                                            data-confirm-type="danger"
                                            data-confirm-title="Disable webhook endpoint?"
                                            data-confirm-message="This endpoint will be deleted immediately. Active integrations using this URL will stop working."
                                            data-confirm-btn="Disable Endpoint"
                                            class="rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2 text-sm font-black text-[#A1A1AA] transition hover:border-[#EF4444]/30 hover:text-[#EF4444]"
                                        >Disable</button>
                                    </form>
                                @endif
                            </div>
                            @if ($uwEnabled)
                                <p class="text-[11px] text-[#52525B]">Regenerating invalidates the old URL immediately. Update your integrations after regenerating.</p>
                            @endif

                            {{-- Docs link --}}
                            <div class="flex items-center gap-2">
                                <a href="{{ route('docs.webhooks') }}" target="_blank"
                                   class="inline-flex items-center gap-1.5 rounded-xl border border-[#27213D] bg-[#151225] px-3 py-1.5 text-xs font-black text-[#94A3B8] transition hover:border-[#8B5CF6]/40 hover:text-[#8B5CF6]">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                                    Webhook Documentation
                                </a>
                            </div>

                            {{-- Test Webhook --}}
                            <div class="rounded-xl border border-[#27213D] bg-[#151225] p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-black text-[#A1A1AA]">Test Webhook</p>
                                        <p class="mt-0.5 text-xs text-[#94A3B8]">Send a test payload to this bot's webhook endpoint.</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="runTest()"
                                        :disabled="testing"
                                        class="flex w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-[#38BDF8]/40 bg-[#38BDF8]/10 px-4 py-2 text-sm font-black text-[#38BDF8] transition hover:bg-[#38BDF8]/18 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                                    >
                                        <svg x-show="!testing" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                                        <svg x-show="testing" x-cloak class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        <span x-text="testing ? 'Sending…' : 'Test Webhook'"></span>
                                    </button>
                                </div>
                                <div x-show="testResult" x-cloak class="mt-3 flex items-center gap-2 rounded-lg border border-[#22C55E]/30 bg-[#22C55E]/8 px-3 py-2 text-xs">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span class="font-black text-[#22C55E]">Received</span>
                                    <span class="text-[#94A3B8]">— HTTP <span class="font-black text-[#A1A1AA]" x-text="testResult?.http_status"></span> · <span x-text="testResult?.duration_ms"></span>ms</span>
                                </div>
                                <div x-show="testError" x-cloak class="mt-3 flex items-start gap-2 rounded-lg border border-[#EF4444]/30 bg-[#EF4444]/8 px-3 py-2 text-xs">
                                    <svg class="mt-px h-3.5 w-3.5 shrink-0 text-[#EF4444]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                    <span class="font-black text-[#EF4444]" x-text="testError"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Delivery Logs card --}}
                        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                            @php $dlogs = $webhookDeliveryLogs ?? collect(); @endphp
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Delivery Logs</p>
                                    <p class="mt-0.5 text-[11px] text-[#52525B]">Recent incoming webhook attempts for this bot.</p>
                                </div>
                                @if ($dlogs->isNotEmpty())
                                    <span class="shrink-0 rounded-full border border-[#27213D] bg-[#151225] px-2.5 py-0.5 text-[10px] font-black text-[#94A3B8]">{{ $dlogs->count() }}</span>
                                @endif
                            </div>

                            @if ($dlogs->isEmpty())
                                <div class="mt-5 flex flex-col items-center gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] py-12 text-center">
                                    <div class="grid h-10 w-10 place-items-center rounded-xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-[#94A3B8]">No webhook deliveries yet</p>
                                        <p class="mt-0.5 text-xs text-[#52525B]">Incoming callbacks will appear here once your endpoint receives requests.</p>
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 rounded-xl border border-[#1B172B] overflow-hidden">
                                    <div style="max-height:260px;overflow-y:auto;overflow-x:auto;">
                                    <table class="w-full min-w-[520px] text-xs">
                                        <thead>
                                            <tr class="border-b border-[#1B172B] bg-[#0B0918]">
                                                <th class="px-4 py-2.5 text-left font-black text-[#52525B]">Event</th>
                                                <th class="px-4 py-2.5 text-left font-black text-[#52525B]">Status</th>
                                                <th class="px-4 py-2.5 text-left font-black text-[#52525B]">HTTPS</th>
                                                <th class="px-4 py-2.5 text-left font-black text-[#52525B]">Duration</th>
                                                <th class="px-4 py-2.5 text-left font-black text-[#52525B]">Received</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[#1B172B]">
                                            @foreach ($dlogs as $dlog)
                                                @php
                                                    $dlogStyle = match($dlog->status) {
                                                        'success'  => 'border-[#22C55E]/30 bg-[#22C55E]/10 text-[#22C55E]',
                                                        'failed'   => 'border-[#EF4444]/30 bg-[#EF4444]/10 text-[#EF4444]',
                                                        'retrying' => 'border-[#38BDF8]/30 bg-[#38BDF8]/10 text-[#38BDF8]',
                                                        default    => 'border-[#F59E0B]/30 bg-[#F59E0B]/10 text-[#F59E0B]',
                                                    };
                                                @endphp
                                                <tr class="bg-[#0F0D1A] transition hover:bg-[#151225]">
                                                    <td class="px-4 py-2.5 font-mono text-[#A1A1AA]">{{ $dlog->event }}</td>
                                                    <td class="px-4 py-2.5">
                                                        <span class="rounded-full border px-2 py-0.5 text-[10px] font-black {{ $dlogStyle }}">{{ ucfirst($dlog->status) }}</span>
                                                    </td>
                                                    <td class="px-4 py-2.5 font-mono text-[#94A3B8]">{{ $dlog->http_status ?? '—' }}</td>
                                                    <td class="px-4 py-2.5 text-[#94A3B8]">{{ $dlog->duration_ms !== null ? $dlog->duration_ms.'ms' : '—' }}</td>
                                                    <td class="px-4 py-2.5 text-[#94A3B8]">{{ $dlog->created_at->diffForHumans() }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                @else
                    {{-- ── LOCKED: Plan upgrade required ── --}}
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Custom Webhook</p>
                        <p class="mt-1.5 text-xs text-[#94A3B8]">Receive POST callbacks from external platforms into this bot.</p>
                        <div class="mt-4 flex items-start gap-3 rounded-xl border border-[#F59E0B]/20 bg-[#F59E0B]/8 px-4 py-3">
                            <svg class="h-4 w-4 shrink-0 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                            <div>
                                <p class="text-xs font-black text-[#F59E0B]">Plan upgrade required</p>
                                <p class="mt-0.5 text-xs text-[#94A3B8]">Custom webhooks are available on Pro and higher plans.</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-3 opacity-50">
                            <div class="rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3 text-xs text-[#6B6890]">
                                Incoming Webhook Endpoint — not available on your plan.
                            </div>
                            <div class="flex items-center justify-between rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5">
                                <span class="text-xs text-[#6B6890]">Test Webhook</span>
                                <button disabled class="cursor-not-allowed rounded-xl border border-[#27213D] bg-[#0B0918] px-3 py-1.5 text-xs font-black text-[#6B6890]">Test</button>
                            </div>
                        </div>
                        @if (\Illuminate\Support\Facades\Route::has('dashboard.upgrade'))
                            <a href="{{ route('dashboard.upgrade') }}" class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white shadow-[0_0_16px_rgba(139,92,246,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_0_24px_rgba(139,92,246,0.3)]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/></svg>
                                Upgrade to Pro
                            </a>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('bots.settings.update', $bot) }}" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                    @csrf @method('PATCH')
                    <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Update Token</p>
                    <p class="mt-1.5 text-xs text-[#94A3B8]">Replace the bot token stored for this workspace.</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <input name="token" type="password" autocomplete="new-password" spellcheck="false" autocapitalize="off" class="flex-1 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#71717A] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 transition" placeholder="New token from @BotFather">
                        <button class="shrink-0 rounded-xl border border-[#8B5CF6]/40 bg-[#8B5CF6]/10 px-4 py-2.5 text-sm font-black text-[#8B5CF6] transition hover:bg-[#8B5CF6]/18">Update</button>
                    </div>
                    @error('token') <p class="mt-2 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
                </form>

            </section>

        </div>

        <template x-if="codeEditorOpen && codeEditorPayload">
            <div class="fixed inset-0 z-[9998] flex bg-black/72 backdrop-blur-sm">
                <div
                    x-data="commandCodeEditor(codeEditorPayload)"
                    x-on:keydown.escape.window="editorDialogOpen ? cancelEditorDialog() : (helpersOpen ? helpersOpen = false : (searchOpen ? closeSearch() : closeEditor()))"
                    class="command-code-editor-shell m-0 flex h-dvh w-screen min-w-0 flex-col overflow-hidden overscroll-none bg-[#050509] text-[#F8FAFC] sm:m-3 sm:h-[calc(100dvh-1.5rem)] sm:rounded-lg sm:border sm:border-[#303030] sm:shadow-2xl"
                >
                    <div class="relative z-30 flex h-14 shrink-0 items-center gap-2 border-b border-[#242424] bg-[#1b1b1b] px-3">
                        <div class="flex min-w-0 flex-1 items-center gap-2">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center text-[#7db7ff]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m17.25 6.75 4.5 4.5-4.5 4.5M6.75 6.75l-4.5 4.5 4.5 4.5m7.5-12-4.5 16.5"/></svg>
                            </span>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-black sm:text-base" x-text="codeEditorPayload.title"></div>
                                <div class="truncate font-mono text-[11px] text-[#8B8B8B]" x-text="codeEditorPayload.filename"></div>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button type="button" @click="closeEditor()" class="inline-flex h-10 items-center gap-2 rounded-md bg-[#2b2b2b] px-3 text-sm font-bold text-[#E5E7EB] transition hover:bg-[#353535]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                                <span class="hidden sm:inline">Back</span>
                            </button>
                            <button type="button" @click="submitSave()" class="inline-flex h-10 items-center gap-2 rounded-md bg-[#2f6fed] px-3 text-sm font-black text-white transition hover:bg-[#3b7cff] disabled:cursor-wait disabled:opacity-70" :disabled="saving">
                                <svg x-show="!saving" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10.5 18.75 19.5 5.25"/></svg>
                                <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992M2.985 19.644v-4.992h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                                <span x-text="saving ? 'Saving...' : 'Save'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="relative z-20 flex h-12 shrink-0 items-center gap-1 overflow-x-auto border-b border-[#2d2d2d] bg-[#202020] px-3" style="scrollbar-width:none">
                        <button type="button" @click.prevent.stop="undo()" title="Undo" class="editor-tool-btn"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg></button>
                        <button type="button" @click.prevent.stop="redo()" title="Redo" class="editor-tool-btn"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 15 6-6m0 0-6-6m6 6H9a6 6 0 0 0 0 12h3"/></svg></button>
                        <span class="mx-1 h-5 w-px shrink-0 bg-[#3a3a3a]"></span>
                        <button type="button" @click.prevent.stop="copyCode()" title="Copy all code" class="editor-tool-btn" :style="copied ? 'background:#16A34A !important;color:#FFFFFF !important;box-shadow:0 0 0 2px rgba(34,197,94,0.45),0 0 26px rgba(34,197,94,0.62);' : ''"><svg x-show="!copied" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 8h10v13H8zM6 16H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg><svg x-show="copied" x-cloak class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg></button>
                        <button type="button" @click.prevent.stop="pasteReplaceCode()" title="Paste & Replace Code" class="editor-tool-btn min-w-max"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25h6M9 3.75h6A1.5 1.5 0 0 1 16.5 5.25v.75h1.125A2.625 2.625 0 0 1 20.25 8.625v9A2.625 2.625 0 0 1 17.625 20.25H6.375A2.625 2.625 0 0 1 3.75 17.625v-9A2.625 2.625 0 0 1 6.375 6H7.5v-.75A1.5 1.5 0 0 1 9 3.75Z"/></svg><span class="hidden text-xs font-bold sm:inline">Paste & Replace Code</span></button>
                        <button type="button" @click="findInEditor()" title="Search" class="editor-tool-btn"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></button>
                        <button type="button" @click="formatCode()" title="Format indentation" class="editor-tool-btn"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h16m-4-7 3 2-3 2"/></svg></button>
                        <button type="button" @click="toggleFullscreen()" title="Fullscreen" class="editor-tool-btn"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg></button>
                        <button type="button" @click="helpersOpen = true" title="Helpers" class="editor-tool-btn"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h4.5M5.25 3.75h13.5A1.5 1.5 0 0 1 20.25 5.25v13.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Z"/></svg><span class="hidden text-xs font-bold sm:inline">Helpers</span></button>
                        <a :href="codeEditorPayload.fallbackUrl" class="editor-tool-btn" title="Open full page"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18v4.5M10.5 13.5 18 6M6 6h3M6 18h12V9"/></svg></a>
                        <div class="ml-auto flex shrink-0 items-center gap-2 pl-3 text-xs">
                            <span x-show="saveStatus" x-cloak class="font-bold text-[#93C5FD]" x-text="saveStatus"></span>
                            <span x-show="saveError" x-cloak class="max-w-[260px] truncate rounded bg-[#EF4444]/12 px-2 py-1 font-bold text-[#FCA5A5]" x-text="saveError"></span>
                        </div>
                    </div>

                    <div x-show="searchOpen" x-cloak class="absolute right-4 top-[128px] z-20 flex max-w-[calc(100vw-2rem)] items-center gap-1 rounded-md border border-[#3a3a3a] bg-[#f7f7f7] p-1 text-[#222] shadow-2xl">
                        <input x-ref="searchInput" x-model="searchQuery" @input.debounce.120ms="updateSearchQuery()" @keydown.enter.prevent="findNextMatch()" @keydown.shift.enter.prevent="findPreviousMatch()" @keydown.escape.prevent="closeSearch()" class="h-8 w-44 rounded border border-[#d1d1d1] bg-white px-2 font-mono text-sm outline-none sm:w-72" type="search" placeholder="Find">
                        <span class="min-w-[72px] px-2 text-center text-xs font-bold text-[#666]" x-text="searchQuery ? (matchCount ? matchCount + ' found' : 'No match') : ''"></span>
                        <button type="button" @click="findPreviousMatch()" class="grid h-8 w-8 place-items-center rounded text-[#666] hover:bg-[#e8e8e8]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg></button>
                        <button type="button" @click="findNextMatch()" class="grid h-8 w-8 place-items-center rounded text-[#666] hover:bg-[#e8e8e8]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg></button>
                        <button type="button" @click="closeSearch()" class="grid h-8 w-8 place-items-center rounded text-[#666] hover:bg-[#e8e8e8]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
                    </div>

                    <div class="command-editor-surface relative min-h-0 flex-1 bg-[#080714]" style="background:#080714;">
                        <div x-show="!loaded" x-cloak class="absolute inset-0 z-10 flex items-center justify-center bg-black text-xs font-bold text-[#8B8B8B]">Loading editor...</div>
                        <div x-ref="editorContainer" class="h-full min-h-0 bg-[#080714]" style="background:#080714;"></div>
                    </div>

                    <div class="flex h-8 shrink-0 items-center gap-3 overflow-x-auto border-t border-[#2d2d2d] bg-[#202020] px-3 font-mono text-[12px] text-[#C7C7C7]" style="scrollbar-width:none">
                        <span x-text="'Ln ' + cursorLine + ', Col ' + cursorCol"></span>
                        <span x-text="chars.toLocaleString() + ' bytes'"></span>
                        <span class="ml-auto">JavaScript</span>
                        <span class="hidden sm:inline" x-text="codeEditorPayload.triggerType"></span>
                        <span x-show="dirty" x-cloak class="text-[#FBBF24]">Unsaved</span>
                    </div>

                    <form x-ref="saveForm" method="POST" :action="codeEditorPayload.action" class="hidden">
                        @csrf
                        <input type="hidden" name="_method" value="PUT">
                        <textarea x-ref="codeInput" name="code"></textarea>
                    </form>

                    <div x-show="editorDialogOpen" x-cloak class="fixed inset-x-0 bottom-0 top-0 z-[10000] flex items-start justify-center overflow-y-auto bg-black/70 p-3 sm:inset-0 sm:items-center sm:p-4" style="padding-top:max(1rem, env(safe-area-inset-top));">
                        <div class="max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-xl border bg-[#141414] p-5 shadow-2xl"
                             :class="editorDialogType === 'warning' ? 'border-[#F59E0B]/35' : 'border-[#303030]'">
                            <div class="flex items-start gap-3">
                                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg"
                                     :class="editorDialogType === 'warning' ? 'bg-[#F59E0B]/12 text-[#F59E0B]' : 'bg-[#2f6fed]/12 text-[#93C5FD]'">
                                    <svg x-show="editorDialogType === 'warning'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                                    <svg x-show="editorDialogType !== 'warning'" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h4.5"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-black text-white" x-text="editorDialogTitle"></h3>
                                    <p class="mt-1 text-sm leading-6 text-[#A1A1AA]" x-text="editorDialogMessage"></p>
                                </div>
                            </div>
                            <textarea
                                x-show="editorDialogPasteMode"
                                x-ref="editorDialogPaste"
                                x-model="editorDialogPasteText"
                                x-cloak
                                rows="5"
                                class="mt-4 min-h-[160px] max-h-[45dvh] w-full resize-none overflow-y-auto rounded-lg border border-[#303030] bg-black px-3 py-2 font-mono text-sm text-white outline-none focus:border-[#2f6fed]"
                                placeholder="Paste code here if clipboard permission is blocked..."
                            ></textarea>
                            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button type="button" @click="cancelEditorDialog()" class="rounded-lg border border-[#303030] bg-[#202020] px-4 py-2 text-sm font-bold text-[#C7C7C7] transition hover:bg-[#2a2a2a] hover:text-white" x-text="editorDialogCancelText"></button>
                                <button type="button" @click="acceptEditorDialog()" class="rounded-lg px-4 py-2 text-sm font-black text-white transition"
                                        :class="editorDialogType === 'warning' ? 'bg-[#F59E0B] hover:bg-[#D97706]' : 'bg-[#2f6fed] hover:bg-[#3b7cff]'"
                                        x-text="editorDialogConfirmText"></button>
                            </div>
                        </div>
                    </div>

                    <div x-show="helpersOpen" x-cloak class="fixed inset-0 z-[9999] flex justify-end bg-black/60" @click.self="helpersOpen = false">
                        <aside class="flex h-full w-full max-w-md flex-col border-l border-[#2d2d2d] bg-[#111] shadow-2xl">
                            <div class="flex h-14 shrink-0 items-center justify-between border-b border-[#2d2d2d] px-4">
                                <div>
                                    <h2 class="text-sm font-black">Helpers</h2>
                                    <p class="text-xs text-[#888]">Quick snippets only. Secrets are not shown here.</p>
                                </div>
                                <button type="button" @click="helpersOpen = false" class="grid h-9 w-9 place-items-center rounded-md text-[#aaa] hover:bg-[#222] hover:text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
                            </div>
                            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                                <div class="space-y-4">
                                    @foreach ([
                                        'Messaging' => ['await reply("Hello");', 'await replyHTML("<b>Saved</b>");', 'await sendPhoto(chat.id, url, { caption: "Photo" });'],
                                        'Command Reply Flow' => ['await askInCommand("Send your email:", "email");', 'if (commandFlow.active && commandFlow.step === "email") { ... }', 'await clearCommandFlow();'],
                                        'State' => ['await setState("step", { id: user.id });', 'const state = await getState();', 'await clearState();'],
                                        'User Data' => ['await setUserData("wallet", message.text);', 'const balance = await getBalance();', 'await addBalance(100, userId);'],
                                        'Validation' => ['if (!isEmail(message.text)) return;', 'const amount = parseAmount(message.text);', 'if (!isTelegramUserId(message.text)) return;'],
                                    ] as $title => $items)
                                        <section>
                                            <h3 class="mb-2 text-[11px] font-black uppercase tracking-wider text-[#777]">{{ $title }}</h3>
                                            <div class="space-y-2">
                                                @foreach ($items as $item)
                                                    <code class="block rounded-md border border-[#2d2d2d] bg-black px-3 py-2 font-mono text-xs font-bold text-[#7dd3fc]">{{ $item }}</code>
                                                @endforeach
                                            </div>
                                        </section>
                                    @endforeach
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-dashboard-layout>



