<x-admin-layout title="Dashboard" subtitle="Full platform overview for BotHost Pro.">

{{-- ===========================
     KPI METRICS GRID
=========================== --}}
<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">

    @php
        $metrics = [
            [
                'label' => 'Total Users',
                'value' => number_format($stats['total_users']),
                'desc'  => 'All registered accounts',
                'color' => 'purple',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
            ],
            [
                'label' => 'Bot Audience',
                'value' => number_format($stats['active_users']),
                'desc'  => 'Across all active bots',
                'color' => 'green',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
            ],
            [
                'label' => 'Admin Users',
                'value' => number_format($stats['admin_users']),
                'desc'  => 'Role: admin',
                'color' => 'purple',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />',
            ],
            [
                'label' => 'Suspended',
                'value' => number_format($stats['suspended_users']),
                'desc'  => 'Status: suspended',
                'color' => 'amber',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />',
            ],
            [
                'label' => 'Banned',
                'value' => number_format($stats['banned_users']),
                'desc'  => 'Status: banned',
                'color' => 'red',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />',
            ],
            [
                'label' => 'Total Bots',
                'value' => number_format($stats['total_bots']),
                'desc'  => 'All created bots',
                'color' => 'blue',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />',
            ],
            [
                'label' => 'Running Bots',
                'value' => number_format($stats['running_bots']),
                'desc'  => 'Status: running',
                'color' => 'green',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />',
            ],
            [
                'label' => 'Paused Bots',
                'value' => number_format($stats['paused_bots']),
                'desc'  => 'Status: paused',
                'color' => 'amber',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />',
            ],
            [
                'label' => 'Stopped Bots',
                'value' => number_format($stats['stopped_bots']),
                'desc'  => 'Status: stopped',
                'color' => 'muted',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9Z" />',
            ],
            [
                'label' => 'Crashed Bots',
                'value' => number_format($stats['crashed_bots']),
                'desc'  => 'Status: crashed',
                'color' => 'red',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />',
            ],
            [
                'label' => 'Bot Users',
                'value' => number_format($stats['total_bot_users']),
                'desc'  => 'All Telegram users tracked',
                'color' => 'blue',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />',
            ],
            [
                'label' => 'Active 24h',
                'value' => number_format($stats['active_bot_users_24h']),
                'desc'  => 'Bot users active in 24h',
                'color' => 'green',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
            ],
            [
                'label' => 'Commands',
                'value' => number_format($stats['commands_created']),
                'desc'  => 'Total commands created',
                'color' => 'purple',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" />',
            ],
            [
                'label' => 'Executions',
                'value' => number_format($stats['command_executions']),
                'desc'  => 'Total command runs',
                'color' => 'blue',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />',
            ],
            [
                'label' => 'Runtime Errors',
                'value' => number_format($stats['runtime_errors']),
                'desc'  => 'Error & runtime logs',
                'color' => 'red',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0 1 12 12.75Zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 0 1-1.152 6.06M12 12.75c-2.883 0-5.647.508-8.208 1.44.125 2.104.52 4.136 1.153 6.06M12 12.75a2.25 2.25 0 0 0 2.248-2.354M12 12.75a2.25 2.25 0 0 1-2.248-2.354M12 8.25c.995 0 1.971-.08 2.922-.236.403-.066.74-.358.795-.762a3.778 3.778 0 0 0-.399-2.25M12 8.25c-.995 0-1.97-.08-2.922-.236-.402-.066-.74-.358-.795-.762a3.734 3.734 0 0 1 .4-2.253M12 8.25a2.25 2.25 0 0 0-2.248 2.146M12 8.25a2.25 2.25 0 0 1 2.248 2.146M8.683 5a6.032 6.032 0 0 1-1.155-1.002c.07-.63.27-1.222.574-1.747m.581 2.749A3.75 3.75 0 0 1 15.318 5m0 0c.427-.283.815-.62 1.155-1.002a6.11 6.11 0 0 0-.574-1.747m0 0a48.965 48.965 0 0 1 3.373.847" />',
            ],
            [
                'label' => 'Revenue',
                'value' => '$' . number_format($stats['revenue'], 2),
                'desc'  => $stats['revenue'] > 0
                    ? 'Subscriptions & purchases'
                    : 'No payments recorded yet',
                'color' => $stats['revenue'] > 0 ? 'green' : 'muted',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
            ],
        ];

        $colorMap = [
            'purple' => ['bg' => 'rgba(139,92,246,0.1)', 'border' => 'rgba(139,92,246,0.25)', 'text' => '#A855F7', 'icon_bg' => 'rgba(139,92,246,0.15)'],
            'blue'   => ['bg' => 'rgba(56,189,248,0.07)', 'border' => 'rgba(56,189,248,0.2)', 'text' => '#38BDF8', 'icon_bg' => 'rgba(56,189,248,0.12)'],
            'green'  => ['bg' => 'rgba(34,197,94,0.07)', 'border' => 'rgba(34,197,94,0.2)', 'text' => '#22C55E', 'icon_bg' => 'rgba(34,197,94,0.12)'],
            'amber'  => ['bg' => 'rgba(245,158,11,0.07)', 'border' => 'rgba(245,158,11,0.2)', 'text' => '#F59E0B', 'icon_bg' => 'rgba(245,158,11,0.12)'],
            'red'    => ['bg' => 'rgba(239,68,68,0.07)', 'border' => 'rgba(239,68,68,0.2)', 'text' => '#EF4444', 'icon_bg' => 'rgba(239,68,68,0.12)'],
            'muted'  => ['bg' => 'rgba(113,113,122,0.06)', 'border' => 'rgba(113,113,122,0.15)', 'text' => '#71717A', 'icon_bg' => 'rgba(113,113,122,0.1)'],
        ];
    @endphp

    @foreach ($metrics as $m)
        @php $c = $colorMap[$m['color']]; @endphp
        <div class="group metric-card relative overflow-hidden rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5"
             style="background: #0F0D1A; border: 1px solid {{ $c['border'] }};">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl" style="background: {{ $c['icon_bg'] }};">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="{{ $c['text'] }}" style="width:1.125rem;height:1.125rem;">
                        {!! $m['icon'] !!}
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-black text-white sm:text-3xl">{{ $m['value'] }}</p>
            <p class="mt-0.5 text-xs font-bold" style="color: {{ $c['text'] }};">{{ $m['label'] }}</p>
            <p class="mt-1 text-[11px] leading-tight text-[#71717A]">{{ $m['desc'] }}</p>
        </div>
    @endforeach
</div>

{{-- ===========================
     SECOND ROW: Platform Health + Bot Status Distribution
=========================== --}}
<div class="mt-5 grid gap-4 lg:grid-cols-3">

    {{-- Platform Health --}}
    <div class="rounded-2xl border border-[#27213D] lg:col-span-1" style="background: #0F0D1A;">
        <div class="flex items-center gap-3 border-b border-[#27213D] px-5 py-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg" style="background: rgba(139,92,246,0.12);">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#8B5CF6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 10.5h.375c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H21M4.5 10.5H18V15H4.5v-4.5ZM3.75 18h15A2.25 2.25 0 0 0 21 15.75v-1.5A2.25 2.25 0 0 0 18.75 12h-15a2.25 2.25 0 0 0-2.25 2.25v1.5A2.25 2.25 0 0 0 3.75 18Z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-black text-white">Platform Health</h2>
                <p class="text-xs text-[#71717A]">System status overview</p>
            </div>
        </div>
        <div class="divide-y divide-[#1B172B] px-5">
            @php
                $healthItems = [
                    ['label' => 'Laravel Application', 'status' => 'online', 'value' => 'Online'],
                    ['label' => 'Webhook System', 'status' => 'online', 'value' => $stats['running_bots'].' active'],
                    ['label' => 'Node.js Runtime', 'status' => 'info', 'value' => 'Webhook-based'],
                    ['label' => 'Runtime Errors', 'status' => $stats['runtime_errors'] > 0 ? 'error' : 'online', 'value' => number_format($stats['runtime_errors']).' errors'],
                    ['label' => 'New Users Today', 'status' => 'info', 'value' => number_format($stats['new_users_today']).' joined'],
                    ['label' => 'Bots Created Today', 'status' => 'info', 'value' => number_format($stats['bots_created_today']).' new'],
                    ['label' => 'Payment System', 'status' => 'pending', 'value' => 'Not connected'],
                ];
                $healthColors = [
                    'online'  => ['dot' => '#22C55E', 'text' => '#22C55E'],
                    'error'   => ['dot' => '#EF4444', 'text' => '#EF4444'],
                    'pending' => ['dot' => '#F59E0B', 'text' => '#F59E0B'],
                    'info'    => ['dot' => '#38BDF8', 'text' => '#38BDF8'],
                ];
            @endphp
            @foreach ($healthItems as $hi)
                @php $hc = $healthColors[$hi['status']]; @endphp
                <div class="flex items-center justify-between py-3.5">
                    <div class="flex items-center gap-2.5">
                        <span class="h-2 w-2 rounded-full" style="background: {{ $hc['dot'] }}; box-shadow: 0 0 6px {{ $hc['dot'] }};"></span>
                        <span class="text-sm text-[#A1A1AA]">{{ $hi['label'] }}</span>
                    </div>
                    <span class="text-xs font-bold" style="color: {{ $hc['text'] }};">{{ $hi['value'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Bot Status Distribution --}}
    <div class="rounded-2xl border border-[#27213D] lg:col-span-2" style="background: #0F0D1A;">
        <div class="flex items-center gap-3 border-b border-[#27213D] px-5 py-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg" style="background: rgba(56,189,248,0.1);">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#38BDF8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-black text-white">Bot Status Overview</h2>
                <p class="text-xs text-[#71717A]">{{ $stats['total_bots'] }} bots across the platform</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-5">
            @php
                $botStatuses = [
                    ['key' => 'running',   'label' => 'Running',   'color' => '#22C55E', 'bg' => 'rgba(34,197,94,0.1)'],
                    ['key' => 'paused',    'label' => 'Paused',    'color' => '#F59E0B', 'bg' => 'rgba(245,158,11,0.1)'],
                    ['key' => 'stopped',   'label' => 'Stopped',   'color' => '#71717A', 'bg' => 'rgba(113,113,122,0.1)'],
                    ['key' => 'crashed',   'label' => 'Crashed',   'color' => '#EF4444', 'bg' => 'rgba(239,68,68,0.1)'],
                    ['key' => 'suspended', 'label' => 'Suspended', 'color' => '#A855F7', 'bg' => 'rgba(168,85,247,0.1)'],
                ];
                $totalBots = max($stats['total_bots'], 1);
            @endphp
            @foreach ($botStatuses as $bs)
                @php
                    $count = $botStatusDist[$bs['key']] ?? 0;
                    $pct   = round(($count / $totalBots) * 100);
                @endphp
                <div class="rounded-xl p-4 text-center" style="background: {{ $bs['bg'] }}; border: 1px solid {{ $bs['color'] }}22;">
                    <p class="text-3xl font-black text-white">{{ number_format($count) }}</p>
                    <p class="mt-1 text-xs font-bold" style="color: {{ $bs['color'] }};">{{ $bs['label'] }}</p>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full" style="background: rgba(255,255,255,0.06);">
                        <div class="h-full rounded-full transition-all duration-500" style="width: {{ $pct }}%; background: {{ $bs['color'] }};"></div>
                    </div>
                    <p class="mt-1.5 text-[10px] text-[#71717A]">{{ $pct }}%</p>
                </div>
            @endforeach
        </div>

        {{-- Quick Admin Actions --}}
        <div class="border-t border-[#1B172B] px-5 py-4">
            <p class="mb-3 text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Quick Actions</p>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <a href="{{ route('admin.users.index') }}" class="flex items-center justify-center gap-1.5 rounded-xl border border-[#8B5CF6]/30 px-3.5 py-2 text-xs font-bold text-[#8B5CF6] transition hover:bg-[#8B5CF6]/10">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    View Users
                </a>
                <a href="{{ route('admin.bots.index') }}" class="flex items-center justify-center gap-1.5 rounded-xl border border-[#38BDF8]/30 px-3.5 py-2 text-xs font-bold text-[#38BDF8] transition hover:bg-[#38BDF8]/10">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                    View Bots
                </a>
                <a href="{{ route('admin.logs.index') }}" class="flex items-center justify-center gap-1.5 rounded-xl border border-[#EF4444]/30 px-3.5 py-2 text-xs font-bold text-[#EF4444] transition hover:bg-[#EF4444]/10">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    Review Errors
                </a>
                <a href="{{ route('admin.broadcasts.index') }}" class="flex items-center justify-center gap-1.5 rounded-xl border border-[#A855F7]/30 px-3.5 py-2 text-xs font-bold text-[#A855F7] transition hover:bg-[#A855F7]/10">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 1 8.835-2.535m0 0A23.74 23.74 0 0 1 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m-14.456 0a23.847 23.847 0 0 0-1.014-5.395m0 0A23.74 23.74 0 0 0 5.205 3m-.38 1.125a23.91 23.91 0 0 0-1.014 5.395m1.014 8.855c.118.38.245.754.38 1.125" /></svg>
                    Broadcasts
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ===========================
     THIRD ROW: Recent Users + Recent Bots
=========================== --}}
<div class="mt-6 grid gap-5 xl:grid-cols-2">

    {{-- Recent Users --}}
    <div class="overflow-hidden rounded-2xl border border-[#27213D]" style="background: #0F0D1A;">
        <div class="flex items-center justify-between border-b border-[#27213D] px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg" style="background: rgba(139,92,246,0.12);">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#8B5CF6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-black text-white">Recent Users</h2>
                    <p class="text-xs text-[#71717A]">Latest registrations</p>
                </div>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-[#8B5CF6] transition hover:text-[#A855F7]">View all →</a>
        </div>
        <div class="divide-y divide-[#1B172B]">
            @forelse ($recentUsers as $user)
                @php
                    $roleColors = ['admin' => ['bg' => 'rgba(139,92,246,0.15)', 'text' => '#A855F7'], 'user' => ['bg' => 'rgba(56,189,248,0.1)', 'text' => '#38BDF8']];
                    $statusColors = ['active' => ['bg' => 'rgba(34,197,94,0.1)', 'text' => '#22C55E'], 'suspended' => ['bg' => 'rgba(245,158,11,0.1)', 'text' => '#F59E0B'], 'banned' => ['bg' => 'rgba(239,68,68,0.1)', 'text' => '#EF4444']];
                    $rc = $roleColors[$user->role] ?? $roleColors['user'];
                    $sc = $statusColors[$user->status] ?? $statusColors['active'];
                @endphp
                <div class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-[#151225]">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-black text-white" style="background: linear-gradient(135deg, #27213D, #3D3658);">
                        {{ strtoupper(substr($user->name ?? $user->username ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                        <p class="truncate text-xs text-[#71717A]">{{ $user->email }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <span class="rounded px-2 py-0.5 text-[10px] font-black uppercase tracking-wider" style="background: {{ $rc['bg'] }}; color: {{ $rc['text'] }};">{{ $user->role }}</span>
                        <span class="rounded px-2 py-0.5 text-[10px] font-black uppercase tracking-wider" style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }};">{{ $user->status }}</span>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl" style="background: rgba(139,92,246,0.08);">
                        <svg class="h-5 w-5 text-[#8B5CF6]" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-[#A1A1AA]">No users yet</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Bots --}}
    <div class="overflow-hidden rounded-2xl border border-[#27213D]" style="background: #0F0D1A;">
        <div class="flex items-center justify-between border-b border-[#27213D] px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg" style="background: rgba(56,189,248,0.1);">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#38BDF8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-black text-white">Recent Bots</h2>
                    <p class="text-xs text-[#71717A]">Latest bot creations</p>
                </div>
            </div>
            <a href="{{ route('admin.bots.index') }}" class="text-xs font-bold text-[#38BDF8] transition hover:text-white">View all →</a>
        </div>
        <div class="divide-y divide-[#1B172B]">
            @forelse ($recentBots as $bot)
                @php
                    $botStatusColors = [
                        'running'   => ['bg' => 'rgba(34,197,94,0.1)',   'text' => '#22C55E'],
                        'paused'    => ['bg' => 'rgba(245,158,11,0.1)',  'text' => '#F59E0B'],
                        'stopped'   => ['bg' => 'rgba(113,113,122,0.1)', 'text' => '#71717A'],
                        'crashed'   => ['bg' => 'rgba(239,68,68,0.1)',   'text' => '#EF4444'],
                        'suspended' => ['bg' => 'rgba(168,85,247,0.1)',  'text' => '#A855F7'],
                    ];
                    $bsc = $botStatusColors[$bot->status] ?? $botStatusColors['stopped'];
                @endphp
                <div class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-[#151225]">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-black" style="background: rgba(34,158,217,0.12); color: #229ED9;">
                        {{ strtoupper(substr($bot->name ?? 'B', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white">{{ $bot->name }}</p>
                        <p class="truncate text-xs text-[#71717A]">{{ $bot->telegram_username ? '@'.$bot->telegram_username : 'Not verified' }} · {{ $bot->user?->name ?? 'Deleted user' }}</p>
                    </div>
                    <span class="shrink-0 rounded px-2 py-0.5 text-[10px] font-black uppercase tracking-wider" style="background: {{ $bsc['bg'] }}; color: {{ $bsc['text'] }};">{{ $bot->status }}</span>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl" style="background: rgba(56,189,248,0.08);">
                        <svg class="h-5 w-5 text-[#38BDF8]" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-[#A1A1AA]">No bots yet</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ===========================
     FOURTH ROW: Recent Errors + Recent Activity
=========================== --}}
<div class="mt-6 grid gap-5 xl:grid-cols-2">

    {{-- Recent Runtime Errors --}}
    <div class="overflow-hidden rounded-2xl border border-[#27213D]" style="background: #0F0D1A;">
        <div class="flex items-center justify-between border-b border-[#27213D] px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg" style="background: rgba(239,68,68,0.1);">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#EF4444">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-black text-white">Recent Errors</h2>
                    <p class="text-xs text-[#71717A]">Runtime & error logs</p>
                </div>
            </div>
            <a href="{{ route('admin.logs.index') }}" class="text-xs font-bold text-[#EF4444] transition hover:text-white">View logs →</a>
        </div>
        <div class="divide-y divide-[#1B172B]">
            @forelse ($recentErrors as $err)
                <div class="px-5 py-3.5 transition hover:bg-[#151225]">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md" style="background: rgba(239,68,68,0.12);">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#EF4444]"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-white">{{ $err->title ?? 'Error' }}</p>
                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider" style="background: rgba(239,68,68,0.12); color: #EF4444;">{{ $err->type }}</span>
                            </div>
                            <p class="mt-0.5 truncate text-xs text-[#71717A]">{{ Str::limit($err->message, 80) }}</p>
                            <p class="mt-1 text-[10px] text-[#3D3658]">{{ $err->bot?->name ?? 'Unknown bot' }} · {{ optional($err->created_at)->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl" style="background: rgba(34,197,94,0.08);">
                        <svg class="h-5 w-5 text-[#22C55E]" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-[#22C55E]">No errors</p>
                    <p class="mt-1 text-xs text-[#71717A]">Platform is running clean</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="overflow-hidden rounded-2xl border border-[#27213D]" style="background: #0F0D1A;">
        <div class="flex items-center justify-between border-b border-[#27213D] px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg" style="background: rgba(56,189,248,0.1);">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="#38BDF8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-black text-white">Recent Activity</h2>
                    <p class="text-xs text-[#71717A]">Admin actions & events</p>
                </div>
            </div>
        </div>
        <div class="divide-y divide-[#1B172B]">
            @forelse ($recentActivity as $activity)
                <div class="flex items-start gap-3 px-5 py-3.5 transition hover:bg-[#151225]">
                    <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#8B5CF6]" style="box-shadow: 0 0 6px rgba(139,92,246,0.5);"></div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-[#A1A1AA]">{{ $activity->description ?: str_replace('_', ' ', $activity->action) }}</p>
                        <p class="mt-0.5 text-xs text-[#71717A]">
                            {{ $activity->user?->username ?? 'System' }}
                            <span class="mx-1 text-[#3D3658]">·</span>
                            {{ optional($activity->created_at)->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <p class="text-sm text-[#71717A]">No recent activity</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

</x-admin-layout>
