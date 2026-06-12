<x-dashboard-layout title="Dashboard">
    @php
        $dateLabel = now()->format('l, F j, Y');
        $greeting  = now()->hour < 12 ? 'Morning' : (now()->hour < 18 ? 'Afternoon' : 'Evening');

        $mainMetrics = [
            [
                'label' => 'Total Bots',
                'value' => $stats['total_bots'],
                'hint'  => 'All workspaces',
                'color' => '#8B5CF6',
                'glow'  => 'rgba(139,92,246,0.18)',
                'trend' => 'All workspaces',
                'up'    => null,
                'icon'  => 'M8 9.75h8m-7 4h6m-9.25 5.5h12.5a2 2 0 0 0 2-2V7.75a2 2 0 0 0-2-2H5.75a2 2 0 0 0-2 2v9.5a2 2 0 0 0 2 2Z',
                'spark' => '0,18 4,14 8,16 12,9 16,13 20,6 24,10 28,4 32,7',
            ],
            [
                'label' => 'Total Users',
                'value' => $stats['total_bot_users'],
                'hint'  => 'All bot users across workspaces',
                'color' => '#22C55E',
                'glow'  => 'rgba(34,197,94,0.14)',
                'trend' => 'All bot users',
                'up'    => null,
                'icon'  => 'M8.5 10.25a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7 1.5a2.75 2.75 0 1 0 0-5.5 2.75 2.75 0 0 0 0 5.5ZM3.75 20.25a4.75 4.75 0 0 1 9.5 0m1.5 0a3.75 3.75 0 0 1 5.5-3.32',
                'spark' => '0,16 4,18 8,11 12,13 16,7 20,9 24,5 28,3 32,2',
            ],
            [
                'label' => 'Commands',
                'value' => $stats['total_commands'],
                'hint'  => 'Across all bots',
                'color' => '#38BDF8',
                'glow'  => 'rgba(56,189,248,0.14)',
                'trend' => 'Across all bots',
                'up'    => null,
                'icon'  => 'M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z',
                'spark' => '0,18 4,16 8,16 12,11 16,13 20,9 24,11 28,7 32,6',
            ],
            [
                'label' => 'Running Now',
                'value' => $stats['running_bots'],
                'hint'  => 'Currently online',
                'color' => '#22C55E',
                'glow'  => 'rgba(34,197,94,0.18)',
                'trend' => 'Live bots',
                'up'    => null,
                'icon'  => 'M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z',
                'spark' => '0,14 4,12 8,14 12,9 16,11 20,7 24,9 28,5 32,4',
            ],
        ];

        $wideMetrics = [
            [
                'label' => 'Cloned Bots',
                'value' => $stats['cloned_bots'] ?? 0,
                'hint'  => 'Copied from templates or other workspaces',
                'color' => '#38BDF8',
                'glow'  => 'rgba(56,189,248,0.14)',
                'icon'  => 'M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75',
            ],
            [
                'label' => 'Transferred',
                'value' => $stats['transferred_bots'] ?? 0,
                'hint'  => 'Moved from other accounts',
                'color' => '#A855F7',
                'glow'  => 'rgba(168,85,247,0.15)',
                'icon'  => 'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
            ],
        ];
    @endphp

    {{-- ─── Header ──────────────────────────────────────────────────────── --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Dashboard</h1>
            <p class="mt-0.5 text-sm text-[#71717A]">Live stats across all your bot workspaces</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-1.5 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-4 py-2 text-sm font-semibold text-[#A1A1AA] transition hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                Refresh
            </a>
            <a href="{{ route('bots.index') }}" class="flex items-center gap-1.5 rounded-xl bg-[#8B5CF6] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#7C3AED]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9.75h8m-7 4h6m-9.25 5.5h12.5a2 2 0 0 0 2-2V7.75a2 2 0 0 0-2-2H5.75a2 2 0 0 0-2 2v9.5a2 2 0 0 0 2 2Z"/></svg>
                My Bots
            </a>
        </div>
    </div>

    {{-- ─── Stats Row 1 — 4 columns ─────────────────────────────────────── --}}
    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($mainMetrics as $m)
            <div class="rounded-2xl border bg-[#0F0D1A] p-4" style="border-color: {{ $m['color'] }}26;">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em]" style="color: {{ $m['color'] }}99;">{{ $m['label'] }}</p>
                    <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg" style="background: {{ $m['color'] }}18; color: {{ $m['color'] }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-black text-white">{{ is_numeric($m['value']) ? number_format($m['value']) : $m['value'] }}</p>
                <p class="mt-0.5 text-xs text-slate-300">{{ $m['hint'] }}</p>
            </div>
        @endforeach
    </section>

    {{-- ─── Stats Row 2 — 2 wide columns ───────────────────────────────── --}}
    <section class="mt-3 grid grid-cols-2 gap-3">
        @foreach ($wideMetrics as $m)
            <div class="rounded-2xl border bg-[#0F0D1A] p-4" style="border-color: {{ $m['color'] }}26;">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em]" style="color: {{ $m['color'] }}99;">{{ $m['label'] }}</p>
                    <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg" style="background: {{ $m['color'] }}18; color: {{ $m['color'] }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-black text-white">{{ number_format($m['value']) }}</p>
                <p class="mt-0.5 text-xs text-slate-300">{{ $m['hint'] }}</p>
            </div>
        @endforeach
    </section>

    {{-- ─── Activity Chart ─────────────────────────────────────────────── --}}
    @php
        $hasActivity  = !empty($chartData) && collect($chartData)->sum('count') > 0;
        $chartMax     = $hasActivity ? collect($chartData)->max('count') : 0;
        $yAxisMax     = $chartMax > 0 ? (int) ceil($chartMax * 1.15) : 10;

        $svgCoords = [];
        if ($hasActivity) {
            $n = count($chartData);
            foreach ($chartData as $i => $pt) {
                $x = $n > 1 ? round(($i / ($n - 1)) * 720, 1) : 360;
                $y = round(175 - ($pt['count'] / $yAxisMax * 165), 1);
                $y = max(10, min(175, $y));
                $svgCoords[] = "$x,$y";
            }
        }
        $polylineStr = implode(' ', $svgCoords);

        $firstX  = $hasActivity ? explode(',', $svgCoords[0])[0] : '0';
        $lastX   = $hasActivity ? explode(',', end($svgCoords))[0] : '720';
        $areaStr = $polylineStr . " {$lastX},180 {$firstX},180";

        $xLabels = [];
        if (!empty($chartData)) {
            foreach ($chartData as $i => $pt) {
                if ($i % 2 === 0) {
                    $xLabels[] = $pt['label'];
                }
            }
        }

        $peakCoord = null;
        if ($hasActivity && count($svgCoords) > 0) {
            $peakIdx  = array_search(collect($chartData)->max('count'), array_column($chartData, 'count'));
            $peakCoord = $svgCoords[$peakIdx] ?? null;
        }
    @endphp

    <section class="mt-5 rounded-2xl bg-[#0F0D1A] p-5" style="border: 1px solid #27213D;">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-black text-white">User Activity (24h)</h3>
                <p class="mt-0.5 text-xs text-[#71717A]">Hourly user engagement overview</p>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="block h-2 w-2 rounded-full bg-[#8B5CF6]"></span>
                <span class="text-xs text-[#71717A]">Active Users</span>
            </div>
        </div>

        @if ($hasActivity)
            <div class="overflow-x-auto rounded-xl bg-[#090713]" style="border: 1px solid #1B172B;">
                <div class="relative flex min-w-[520px] p-4">
                    <div class="mr-3 flex h-48 w-8 shrink-0 flex-col justify-between text-right">
                        <span class="text-[10px] leading-none text-[#71717A]">{{ $yAxisMax }}</span>
                        <span class="text-[10px] leading-none text-[#71717A]">{{ round($yAxisMax * 0.75) }}</span>
                        <span class="text-[10px] leading-none text-[#71717A]">{{ round($yAxisMax * 0.5) }}</span>
                        <span class="text-[10px] leading-none text-[#71717A]">{{ round($yAxisMax * 0.25) }}</span>
                        <span class="text-[10px] leading-none text-[#71717A]">0</span>
                    </div>
                    <div class="flex-1">
                        <svg class="h-48 w-full" viewBox="0 0 720 180" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="chartArea" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%"   stop-color="#8B5CF6" stop-opacity="0.32"/>
                                    <stop offset="100%" stop-color="#8B5CF6" stop-opacity="0.02"/>
                                </linearGradient>
                            </defs>
                            <line x1="0" y1="0"   x2="720" y2="0"   stroke="#1B172B" stroke-width="1"/>
                            <line x1="0" y1="45"  x2="720" y2="45"  stroke="#1B172B" stroke-width="1"/>
                            <line x1="0" y1="90"  x2="720" y2="90"  stroke="#1B172B" stroke-width="1"/>
                            <line x1="0" y1="135" x2="720" y2="135" stroke="#1B172B" stroke-width="1"/>
                            <line x1="0" y1="180" x2="720" y2="180" stroke="#1B172B" stroke-width="1"/>
                            <polygon points="{{ $areaStr }}" fill="url(#chartArea)"/>
                            <polyline points="{{ $polylineStr }}" fill="none" stroke="#8B5CF6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            @if ($peakCoord)
                                @php [$px, $py] = explode(',', $peakCoord); @endphp
                                <circle cx="{{ $px }}" cy="{{ $py }}" r="4" fill="#fff" stroke="#8B5CF6" stroke-width="2"/>
                            @endif
                        </svg>
                    </div>
                </div>
            </div>
            <div class="mt-2 flex justify-between pl-11 pr-1">
                @foreach ($xLabels as $hr)
                    <span class="text-[9px] text-[#71717A]">{{ $hr }}</span>
                @endforeach
            </div>
        @else
            <div class="flex h-48 items-center justify-center rounded-xl bg-[#090713]" style="border: 1px solid #1B172B;">
                <div class="text-center">
                    <svg class="mx-auto h-8 w-8 text-[#27213D]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                    </svg>
                    <p class="mt-3 text-sm font-semibold text-[#A1A1AA]">No activity yet</p>
                    <p class="mt-1 text-xs text-[#71717A]">Command interactions will appear here once your bots receive messages.</p>
                </div>
            </div>
        @endif

        {{-- Bottom summary stats — only when there is data --}}
        @if ($hasActivity)
        <div class="mt-5 grid grid-cols-2 pt-4 sm:grid-cols-4" style="border-top: 1px solid #27213D;">
            @foreach ([
                [$chartStats['total'],        'Commands'],
                [$chartStats['peak_hour'],     'Peak Hour'],
                [$chartStats['avg_per_hour'],  'Avg/Hour'],
                [$chartStats['active_hours'],  'Active Hours'],
            ] as [$val, $lbl])
                <div class="px-4 text-center first:pl-0 last:pr-0">
                    <p class="text-xl font-black text-white">{{ $val }}</p>
                    <p class="mt-0.5 text-xs text-[#71717A]">{{ $lbl }}</p>
                </div>
            @endforeach
        </div>
        @endif
    </section>

    {{-- ─── Top Active Bots ─────────────────────────────────────────────── --}}
    <section class="mt-5 overflow-hidden rounded-2xl bg-[#0F0D1A]" style="border: 1px solid #27213D;">
        <div class="flex items-center justify-between px-5 py-4" style="border-bottom: 1px solid #27213D;">
            <div>
                <h3 class="font-black text-white">Top Active Bots</h3>
                <p class="mt-0.5 text-xs text-[#71717A]">Recent workspace activity</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-[#71717A]">{{ $recentBots->count() }} bots</span>
                @if ($recentBots->isNotEmpty())
                    <a href="{{ route('bots.index') }}" class="text-xs font-bold text-[#8B5CF6] transition hover:text-[#A855F7]">View all →</a>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid #27213D;">
                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.16em] text-[#4B5268]">Bot</th>
                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.16em] text-[#4B5268]">Commands</th>
                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.16em] text-[#4B5268]">Status</th>
                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.16em] text-[#4B5268]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentBots->take(3) as $bot)
                        <tr class="transition hover:bg-[#151225]" style="border-top: 1px solid #27213D;">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[#151225] text-[#8B5CF6]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9.75h8m-7 4h6m-9.25 5.5h12.5a2 2 0 0 0 2-2V7.75a2 2 0 0 0-2-2H5.75a2 2 0 0 0-2 2v9.5a2 2 0 0 0 2 2Z"/></svg>
                                    </div>
                                    <span class="font-semibold text-white">{{ $bot->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-[#A1A1AA]">{{ $bot->commands_count }}</td>
                            <td class="px-5 py-3.5"><x-status-badge :status="$bot->status" /></td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('bots.show', $bot) }}" class="text-xs font-bold text-[#8B5CF6] transition hover:text-[#A855F7]">View →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-[#151225] text-[#71717A]" style="border: 1px solid #27213D;">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9.75h8m-7 4h6m-9.25 5.5h12.5a2 2 0 0 0 2-2V7.75a2 2 0 0 0-2-2H5.75a2 2 0 0 0-2 2v9.5a2 2 0 0 0 2 2Z"/></svg>
                                    </div>
                                    <p class="font-semibold text-[#A1A1AA]">No bots yet</p>
                                    <p class="text-xs text-[#71717A]">Create your first bot workspace to get started</p>
                                    <a href="{{ route('bots.create') }}" class="mt-1 flex items-center gap-1.5 rounded-xl bg-[#8B5CF6] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#7C3AED]">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                        Create Bot
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ─── Help & Support ─────────────────────────────────────────────── --}}
    @php
        $helpCards = [
            [
                'title'  => 'Telegram Community',
                'desc'   => 'Join our group for help & updates',
                'color'  => '#229ED9',
                'icon'   => 'M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z',
                'href'   => $telegramCommunityUrl ?: null,
                'target' => '_blank',
            ],
            [
                'title'  => 'Learn to Make Bots',
                'desc'   => 'Tutorials & guides to get started',
                'color'  => '#8B5CF6',
                'icon'   => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
                'href'   => $tutorialsUrl ?: null,
                'target' => '_blank',
            ],
            [
                'title'  => 'Get Support',
                'desc'   => 'Ask questions & get help',
                'color'  => '#38BDF8',
                'icon'   => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z',
                'href'   => route('support.index'),
                'target' => '_self',
            ],
        ];
    @endphp
    <section class="mt-5 grid gap-3 sm:grid-cols-3">
        @foreach ($helpCards as $card)
            <a
                href="{{ $card['href'] ?? '#' }}"
                @if($card['target'] === '_blank') target="_blank" rel="noopener noreferrer" @endif
                class="group rounded-2xl bg-[#0F0D1A] p-4 transition {{ $card['href'] ? 'hover:border-[#3D3657]' : 'cursor-default opacity-70' }}" style="border: 1px solid #27213D;"
            >
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-[#151225]" style="color: {{ $card['color'] }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
                <h4 class="mt-3 text-sm font-bold text-white">{{ $card['title'] }}</h4>
                <p class="mt-1 text-xs text-[#71717A]">{{ $card['desc'] }}</p>
            </a>
        @endforeach
    </section>
</x-dashboard-layout>
