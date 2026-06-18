<x-admin-layout title="Runtime Health Center" subtitle="Inspect and repair runtime helper delivery.">
@php
    $summary = $report['summary'] ?? [];
    $helperBundle = $report['helper_bundle'] ?? [];
    $runtimeSource = $report['runtime_source'] ?? [];
    $botRows = $report['bots'] ?? [];
    $queue = $report['queue'] ?? [];
    $cache = $report['cache'] ?? [];
    $bridge = $report['bridge'] ?? [];
    $badge = function ($ok, string $yes = 'OK', string $no = 'Issue') {
        return [
            'label' => $ok ? $yes : $no,
            'class' => $ok ? 'bg-[#22C55E]/10 text-[#22C55E]' : 'bg-[#EF4444]/10 text-[#EF4444]',
        ];
    };
    $actionBadge = function (?string $action) {
        return match ($action) {
            'none' => 'bg-[#22C55E]/10 text-[#22C55E]',
            'recreate' => 'bg-[#EF4444]/10 text-[#EF4444]',
            'not running' => 'bg-[#F59E0B]/10 text-[#F59E0B]',
            default => 'bg-[#64748B]/10 text-[#CBD5E1]',
        };
    };
@endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-white">Runtime Health Center</h1>
            <p class="mt-1 text-xs text-[#94A3B8]">Checks helper bundle state, Docker runtime support, cache state, and queue restart signals without exposing tokens.</p>
        </div>
        <form method="POST" action="{{ route('admin.runtime.health.check') }}">
            @csrf
            <button class="rounded-xl border border-[#38BDF8]/40 px-4 py-2 text-sm font-black text-[#7DD3FC] hover:bg-[#38BDF8]/10">Run Health Check</button>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <p class="text-xs text-[#94A3B8]">Runtime Status</p>
            <p class="mt-2 text-lg font-black {{ ($summary['runtime_status'] ?? '') === 'ready' ? 'text-[#22C55E]' : 'text-[#F59E0B]' }}">{{ ucfirst(str_replace('_', ' ', $summary['runtime_status'] ?? 'unknown')) }}</p>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <p class="text-xs text-[#94A3B8]">Helper Bundle</p>
            <p class="mt-2 text-lg font-black {{ ($helperBundle['exists'] ?? false) ? 'text-[#22C55E]' : 'text-[#EF4444]' }}">{{ ($helperBundle['exists'] ?? false) ? 'Present' : 'Missing' }}</p>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <p class="text-xs text-[#94A3B8]">Containers Checked</p>
            <p class="mt-2 text-2xl font-black text-white">{{ $summary['containers_checked'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <p class="text-xs text-[#94A3B8]">Issues Found</p>
            <p class="mt-2 text-2xl font-black {{ ($summary['issues_found'] ?? 0) > 0 ? 'text-[#EF4444]' : 'text-[#22C55E]' }}">{{ $summary['issues_found'] ?? 0 }}</p>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-[#22C55E]/30 bg-[#0F0D1A] p-4">
            <h2 class="text-sm font-black text-[#BBF7D0]">Force Apply Runtime Helpers</h2>
            <p class="mt-2 text-sm text-[#A7F3D0]">Publishes the helper bundle and recreates Docker bot runtimes that need updated helper support.</p>
            <form method="POST" action="{{ route('admin.runtime.health.force-apply-helpers') }}" class="mt-4 space-y-3">
                @csrf
                <label class="block text-xs font-bold text-[#BBF7D0]" for="confirm_force_apply">Type FORCE_APPLY_RUNTIME_HELPERS to confirm</label>
                <input id="confirm_force_apply" name="confirm_force_apply" autocomplete="off" class="w-full rounded-xl border border-[#22C55E]/40 bg-[#090713] px-3 py-2 font-mono text-sm text-white placeholder:text-[#166534]" placeholder="FORCE_APPLY_RUNTIME_HELPERS">
                <button class="rounded-xl bg-[#16A34A] px-5 py-2.5 text-sm font-black text-white hover:bg-[#15803D]">Force Apply Runtime Helpers</button>
            </form>
        </div>

        <div class="rounded-2xl border border-[#F59E0B]/30 bg-[#0F0D1A] p-4">
            <h2 class="text-sm font-black text-[#FDE68A]">Force Runtime Refresh</h2>
            <p class="mt-2 text-sm text-[#FCD34D]">Rechecks live Docker runtime support and recreates containers that are stale or missing helper mounts.</p>
            <form method="POST" action="{{ route('admin.runtime.health.force-runtime-refresh') }}" class="mt-4 space-y-3">
                @csrf
                <label class="block text-xs font-bold text-[#FDE68A]" for="confirm_runtime_refresh">Type FORCE_RUNTIME_REFRESH to confirm</label>
                <input id="confirm_runtime_refresh" name="confirm_runtime_refresh" autocomplete="off" class="w-full rounded-xl border border-[#F59E0B]/40 bg-[#090713] px-3 py-2 font-mono text-sm text-white placeholder:text-[#78350F]" placeholder="FORCE_RUNTIME_REFRESH">
                <button class="rounded-xl bg-[#D97706] px-5 py-2.5 text-sm font-black text-white hover:bg-[#B45309]">Force Runtime Refresh</button>
            </form>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <h2 class="text-sm font-black text-white">Force Recreate Selected Bot Runtime</h2>
            <form method="POST" action="{{ route('admin.runtime.health.recreate-bot') }}" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
                @csrf
                <select name="bot_id" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
                    <option value="">Select bot</option>
                    @foreach($bots as $bot)
                        <option value="{{ $bot->id }}">#{{ $bot->id }} {{ $bot->name }} ({{ $bot->runtime_mode ?? 'local' }})</option>
                    @endforeach
                </select>
                <input name="confirm_recreate_bot" autocomplete="off" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 font-mono text-sm text-white placeholder:text-[#475569]" placeholder="RECREATE_SELECTED_BOT_RUNTIME">
                <button class="rounded-xl border border-[#EF4444]/40 px-4 py-2 text-sm font-black text-[#FCA5A5] hover:bg-[#EF4444]/10">Recreate Bot</button>
            </form>
        </div>

        <div class="rounded-2xl border border-[#EF4444]/30 bg-[#0F0D1A] p-4">
            <h2 class="text-sm font-black text-[#FCA5A5]">Force Recreate All Bot Runtimes</h2>
            <form method="POST" action="{{ route('admin.runtime.health.recreate-all') }}" class="mt-4 flex flex-wrap gap-3">
                @csrf
                <input name="confirm_recreate_all" autocomplete="off" class="min-w-0 flex-1 rounded-xl border border-[#EF4444]/40 bg-[#090713] px-3 py-2 font-mono text-sm text-white placeholder:text-[#7F1D1D]" placeholder="RECREATE_ALL_BOT_RUNTIMES">
                <button class="rounded-xl bg-[#DC2626] px-4 py-2 text-sm font-black text-white hover:bg-[#B91C1C]">Recreate All</button>
            </form>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <h2 class="text-sm font-black text-white">Helper Bundle</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-[#94A3B8]">Hash</dt><dd class="break-all font-mono text-xs text-white">{{ $helperBundle['current_hash'] ?? '-' }}</dd></div>
                <div><dt class="text-[#94A3B8]">Last publish</dt><dd class="text-white">{{ $helperBundle['last_publish_at'] ?? 'None' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <h2 class="text-sm font-black text-white">Queue</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-[#94A3B8]">Connection</dt><dd class="text-white">{{ $queue['connection'] ?? '-' }}</dd></div>
                <div><dt class="text-[#94A3B8]">Restart signal</dt><dd class="text-white">{{ $queue['restart_timestamp'] ?? 'None' }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('admin.runtime.health.restart-queue') }}" class="mt-4 flex gap-2">
                @csrf
                <input name="confirm_queue_restart" autocomplete="off" class="min-w-0 flex-1 rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 font-mono text-xs text-white placeholder:text-[#475569]" placeholder="RESTART_QUEUE_WORKERS">
                <button class="rounded-xl border border-[#38BDF8]/40 px-3 py-2 text-xs font-black text-[#7DD3FC]">Restart</button>
            </form>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <h2 class="text-sm font-black text-white">Cache</h2>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-[#94A3B8]">Config</dt><dd class="text-white">{{ ($cache['config_cached'] ?? false) ? 'Cached' : 'Open' }}</dd></div>
                <div><dt class="text-[#94A3B8]">Routes</dt><dd class="text-white">{{ ($cache['routes_cached'] ?? false) ? 'Cached' : 'Open' }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('admin.runtime.health.clear-cache') }}" class="mt-4 flex gap-2">
                @csrf
                <input name="confirm_clear_cache" autocomplete="off" class="min-w-0 flex-1 rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 font-mono text-xs text-white placeholder:text-[#475569]" placeholder="CLEAR_LARAVEL_CACHE">
                <button class="rounded-xl border border-[#8B5CF6]/40 px-3 py-2 text-xs font-black text-[#A855F7]">Clear</button>
            </form>
        </div>
    </div>

    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-black text-white">Bot Runtime Readiness</h2>
                <p class="mt-1 text-xs text-[#94A3B8]">Expected hashes are shown as fingerprints only. Tokens and host paths are omitted.</p>
            </div>
            <span class="rounded-lg px-2 py-1 text-xs font-black uppercase {{ ($bridge['status'] ?? '') === 'ok' ? 'bg-[#22C55E]/10 text-[#22C55E]' : 'bg-[#64748B]/10 text-[#CBD5E1]' }}">{{ $bridge['status'] ?? 'not checked' }}</span>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-max w-full text-sm">
                <thead><tr class="border-b border-[#27213D] bg-[#090713]">
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Bot</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Runtime</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Container</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Hashes</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Mount</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Action</th>
                </tr></thead>
                <tbody class="divide-y divide-[#1B172B]">
                    @forelse($botRows as $row)
                        @php
                            $helper = $badge(($row['helper_bundle_hash_matches'] ?? null) === true, 'Helper OK', 'Helper mismatch');
                            $runtime = $badge(($row['runtime_hash_matches'] ?? null) === true, 'Runtime OK', 'Runtime mismatch');
                        @endphp
                        <tr class="hover:bg-[#151225]">
                            <td class="px-4 py-3"><p class="font-bold text-white">#{{ $row['bot_id'] ?? '-' }} {{ $row['bot_name'] ?? 'Unnamed bot' }}</p><p class="text-xs text-[#94A3B8]">Port {{ $row['runtime_http_port'] ?? '-' }}</p></td>
                            <td class="px-4 py-3 text-[#A1A1AA]">{{ $row['runtime_mode'] ?? '-' }}<p class="text-xs text-[#6B6890]">{{ $row['container_status'] ?? '-' }}</p></td>
                            <td class="px-4 py-3 font-mono text-xs text-[#A1A1AA]">{{ $row['container_name'] ?? '-' }}</td>
                            <td class="px-4 py-3"><div class="flex flex-wrap gap-2"><span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $helper['class'] }}">{{ $helper['label'] }}</span><span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $runtime['class'] }}">{{ $runtime['label'] }}</span></div></td>
                            <td class="px-4 py-3 text-xs text-[#A1A1AA]">Mounted: {{ ($row['mounted'] ?? false) ? 'Yes' : 'No' }}<br>Read-only: {{ ($row['read_only'] ?? false) ? 'Yes' : 'No' }}<br>Localhost: {{ ($row['localhost_only'] ?? false) ? 'Yes' : 'No' }}</td>
                            <td class="px-4 py-3"><span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $actionBadge($row['action_needed'] ?? null) }}">{{ $row['action_needed'] ?? 'unknown' }}</span><p class="mt-2 max-w-xs text-xs text-[#94A3B8]">{{ $row['reason'] ?? '-' }}</p></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No bots found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        <h2 class="text-sm font-black text-white">Recent Runtime Admin Actions</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-max w-full text-sm">
                <thead><tr class="border-b border-[#27213D] bg-[#090713]">
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Status</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Action</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Summary</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Admin</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Finished</th>
                </tr></thead>
                <tbody class="divide-y divide-[#1B172B]">
                    @forelse($logs as $log)
                        <tr class="hover:bg-[#151225]">
                            <td class="px-4 py-3"><span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $log->status === 'success' ? 'bg-[#22C55E]/10 text-[#22C55E]' : ($log->status === 'failed' ? 'bg-[#EF4444]/10 text-[#EF4444]' : 'bg-[#F59E0B]/10 text-[#F59E0B]') }}">{{ $log->status }}</span></td>
                            <td class="px-4 py-3 font-mono text-xs text-[#A1A1AA]">{{ $log->action }}</td>
                            <td class="px-4 py-3 text-[#A1A1AA]">{{ $log->summary ?? '-' }}</td>
                            <td class="px-4 py-3 text-[#94A3B8]">{{ $log->admin?->email ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-xs text-[#94A3B8]">{{ $log->finished_at?->format('M j, Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No admin runtime actions logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-admin-layout>
