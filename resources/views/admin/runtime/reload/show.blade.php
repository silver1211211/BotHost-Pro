@php
    $output = $log->parsedOutput();
    $type = $log->outputType();
    $counts = $log->summaryCounts();
    $statusClass = match ($log->status) {
        'success' => 'bg-[#22C55E]/10 text-[#22C55E]',
        'partial' => 'bg-[#F59E0B]/10 text-[#F59E0B]',
        'failed' => 'bg-[#EF4444]/10 text-[#EF4444]',
        'cancelled' => 'bg-[#64748B]/10 text-[#CBD5E1]',
        default => 'bg-[#F59E0B]/10 text-[#F59E0B]',
    };
    $dockerSections = [
        'ready' => 'Ready Containers',
        'would_recreate' => 'Would Recreate',
        'recreated' => 'Recreated',
        'failed' => 'Failed',
        'skipped' => 'Skipped',
        'not_running' => 'Not Running',
        'not_found' => 'Not Found',
        'unknown' => 'Unknown',
    ];
@endphp

<x-admin-layout title="Runtime Reload Log" subtitle="Reload visibility and per-bot reporting.">
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.runtime.reload.logs') }}" class="text-sm text-[#A1A1AA] hover:text-white">Back to logs</a>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.runtime.reload.logs.export-json', $log) }}" class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA] hover:text-white">Download JSON Report</a>
            <a href="{{ route('admin.runtime.reload.logs.export-text', $log) }}" class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA] hover:text-white">Download Text Report</a>
        </div>
    </div>

    @if($log->isActiveTask())
        <div class="rounded-2xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-3xl">
                    <h2 class="text-sm font-black text-[#FCD34D]">Cancel / Mark Failed</h2>
                    <p class="mt-2 text-sm text-[#FCD34D]">This marks the task cancelled in BotHost Pro. If the background process is still running, it may finish later, but the log is marked cancelled.</p>
                </div>
                <form method="POST" action="{{ route('admin.runtime.reload.logs.cancel', $log) }}">
                    @csrf
                    <button class="rounded-xl border border-[#F59E0B]/50 px-5 py-2.5 text-sm font-black text-[#FCD34D] hover:bg-[#F59E0B]/10">Cancel / Mark Failed</button>
                </form>
            </div>
        </div>
    @elseif(in_array($log->status, ['failed', 'partial', 'cancelled'], true) && in_array($log->trigger_type, ['manual_bundle_publish', 'docker_refresh_dry_run'], true))
        <div class="rounded-2xl border border-[#38BDF8]/30 bg-[#38BDF8]/10 p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-black text-[#BAE6FD]">Retry Task</h2>
                    <p class="mt-2 text-sm text-[#7DD3FC]">This creates a new pending task from the same safe operation type.</p>
                </div>
                <form method="POST" action="{{ route('admin.runtime.reload.logs.retry', $log) }}">
                    @csrf
                    <button class="rounded-xl border border-[#38BDF8]/50 px-5 py-2.5 text-sm font-black text-[#7DD3FC] hover:bg-[#38BDF8]/10">Retry</button>
                </form>
            </div>
        </div>
    @endif

    @if(in_array($log->status, ['pending', 'running'], true))
        <div class="rounded-2xl border border-[#38BDF8]/30 bg-[#38BDF8]/10 p-4" id="runtime-reload-progress" data-status-url="{{ route('admin.runtime.reload.status', $log) }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-[#BAE6FD]">Runtime Task Progress</h2>
                    <p class="mt-1 text-sm text-[#7DD3FC]" id="reload-current-step">{{ $log->current_step ?? 'Queued' }}</p>
                </div>
                <span class="rounded-lg bg-[#38BDF8]/10 px-2 py-1 text-xs font-black uppercase text-[#7DD3FC]" id="reload-status">{{ $log->status }}</span>
            </div>
            <div class="mt-4 h-3 overflow-hidden rounded-full bg-[#090713]">
                @php $initialPercent = $log->steps_total ? min(100, (int) floor(($log->steps_completed / max(1, $log->steps_total)) * 100)) : 0; @endphp
                <div class="h-full rounded-full bg-[#38BDF8]" id="reload-progress-bar" style="width: {{ $initialPercent }}%"></div>
            </div>
            <div class="mt-2 flex flex-wrap justify-between gap-2 text-xs text-[#7DD3FC]">
                <span id="reload-progress-label">{{ $log->steps_completed ?? 0 }} / {{ $log->steps_total ?? 0 }}</span>
                <span id="reload-duration">{{ $log->duration_ms ?? 0 }}ms</span>
            </div>
            <p class="mt-3 hidden text-sm text-[#BAE6FD]" id="reload-complete-message">Task completed. Refreshing details...</p>
            <p class="mt-3 hidden text-sm text-[#FCA5A5]" id="reload-error"></p>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <p class="text-xs text-[#94A3B8]">Status</p>
            <p class="mt-2"><span class="rounded-lg px-2 py-1 text-xs font-black uppercase {{ $statusClass }}">{{ $log->status }}</span></p>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4"><p class="text-xs text-[#94A3B8]">Trigger</p><p class="mt-2 font-black text-white">{{ $log->trigger_type }}</p></div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4"><p class="text-xs text-[#94A3B8]">Mode</p><p class="mt-2 font-black text-white">{{ $log->mode ?? '-' }}</p></div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4"><p class="text-xs text-[#94A3B8]">Run Type</p><p class="mt-2 font-black text-white">{{ array_key_exists('dry_run', $output) ? ($log->isDryRun() ? 'Dry run' : 'Live') : ($type ?: '-') }}</p></div>
    </div>

    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        <h2 class="text-sm font-black text-white">Details</h2>
        <dl class="mt-3 grid gap-3 text-sm md:grid-cols-3">
            <div><dt class="text-[#94A3B8]">Current step</dt><dd class="text-white">{{ $log->current_step ?? '-' }}</dd></div>
            <div><dt class="text-[#94A3B8]">Started</dt><dd class="text-white">{{ $log->started_at?->format('M j, Y H:i:s') ?? '-' }}</dd></div>
            <div><dt class="text-[#94A3B8]">Completed</dt><dd class="text-white">{{ $log->completed_at?->format('M j, Y H:i:s') ?? '-' }}</dd></div>
            <div><dt class="text-[#94A3B8]">Duration</dt><dd class="text-white">{{ $log->duration_ms ?? 0 }}ms</dd></div>
            <div><dt class="text-[#94A3B8]">Triggered by</dt><dd class="text-white">{{ $log->triggeredBy?->name ?? 'System' }}</dd></div>
            <div><dt class="text-[#94A3B8]">Helpers compiled</dt><dd class="text-white">{{ $log->helpers_compiled ?? $counts['helpers_compiled'] }}</dd></div>
            <div><dt class="text-[#94A3B8]">Helper bundle changed</dt><dd class="text-white">{{ array_key_exists('helper_bundle_changed', $output) ? (($output['helper_bundle_changed'] ?? false) ? 'Yes' : 'No') : '-' }}</dd></div>
            <div><dt class="text-[#94A3B8]">Helper bundle hash</dt><dd class="break-all font-mono text-xs text-white">{{ $output['expected_helper_bundle_hash'] ?? $output['bundle_hash'] ?? '-' }}</dd></div>
            <div><dt class="text-[#94A3B8]">Containers affected</dt><dd class="text-white">{{ $log->containers_affected ?? 0 }}</dd></div>
            <div><dt class="text-[#94A3B8]">Containers ok</dt><dd class="text-white">{{ $log->containers_ok ?? 0 }}</dd></div>
            <div><dt class="text-[#94A3B8]">Containers failed</dt><dd class="text-white">{{ $log->containers_failed ?? 0 }}</dd></div>
        </dl>
    </div>

    @if($type === 'bundle_publish')
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                <h2 class="text-sm font-black text-white">Compiled Helpers</h2>
                <div class="mt-3 overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="border-b border-[#27213D]"><th class="py-2 pr-3 text-left text-xs text-[#94A3B8]">ID</th><th class="py-2 text-left text-xs text-[#94A3B8]">Name</th></tr></thead>
                        <tbody class="divide-y divide-[#1B172B]">
                            @forelse(($output['compiled'] ?? []) as $helper)
                                <tr><td class="py-2 pr-3 text-[#A1A1AA]">{{ $helper['id'] ?? '-' }}</td><td class="py-2 text-white">{{ $helper['name'] ?? '-' }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="py-3 text-sm text-[#94A3B8]">None</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                <h2 class="text-sm font-black text-white">Skipped Helpers</h2>
                <div class="mt-3 overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="border-b border-[#27213D]"><th class="py-2 pr-3 text-left text-xs text-[#94A3B8]">Name</th><th class="py-2 text-left text-xs text-[#94A3B8]">Reason</th></tr></thead>
                        <tbody class="divide-y divide-[#1B172B]">
                            @forelse(($output['skipped'] ?? []) as $helper)
                                <tr><td class="py-2 pr-3 text-white">{{ $helper['name'] ?? '-' }}</td><td class="py-2 text-[#A1A1AA]">{{ $helper['reason'] ?? '-' }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="py-3 text-sm text-[#94A3B8]">None</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if($type === 'docker_refresh')
        <div class="grid gap-4">
            @foreach($dockerSections as $key => $title)
                @php $rows = $output[$key] ?? []; @endphp
                @if(count($rows) > 0)
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                        <h2 class="text-sm font-black text-white">{{ $title }}</h2>
                        <div class="mt-3 overflow-auto">
                            <table class="min-w-max w-full text-sm">
                                <thead><tr class="border-b border-[#27213D]">
                                    <th class="px-3 py-2 text-left text-xs text-[#94A3B8]">Bot ID</th>
                                    <th class="px-3 py-2 text-left text-xs text-[#94A3B8]">Bot Name</th>
                                    <th class="px-3 py-2 text-left text-xs text-[#94A3B8]">Container</th>
                                    <th class="px-3 py-2 text-left text-xs text-[#94A3B8]">Helper Hash</th>
                                    <th class="px-3 py-2 text-left text-xs text-[#94A3B8]">Reason</th>
                                    <th class="px-3 py-2 text-left text-xs text-[#94A3B8]">Error</th>
                                </tr></thead>
                                <tbody class="divide-y divide-[#1B172B]">
                                    @foreach($rows as $row)
                                        <tr>
                                            <td class="px-3 py-2 text-[#A1A1AA]">{{ $row['bot_id'] ?? '-' }}</td>
                                            <td class="px-3 py-2 text-white">{{ $row['bot_name'] ?? '-' }}</td>
                                            <td class="px-3 py-2 font-mono text-xs text-[#A1A1AA]">{{ $row['container_name'] ?? '-' }}</td>
                                            <td class="px-3 py-2 font-mono text-xs text-[#A1A1AA]">{{ isset($row['helper_bundle_hash_matches']) ? (($row['helper_bundle_hash_matches'] ?? false) ? 'match' : 'mismatch') : '-' }}</td>
                                            <td class="px-3 py-2 text-[#A1A1AA]">{{ $row['reason'] ?? '-' }}</td>
                                            <td class="px-3 py-2 text-[#FCA5A5]">{{ $row['error'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        <h2 class="text-sm font-black text-white">Raw JSON Output</h2>
        <pre class="mt-3 max-h-96 overflow-auto rounded-xl bg-[#090713] p-3 text-xs text-[#A1A1AA]">{{ $log->output ?: 'No output.' }}</pre>
    </div>

    @if($log->error)
        <div class="rounded-2xl border border-[#EF4444]/30 bg-[#EF4444]/10 p-4">
            <h2 class="text-sm font-black text-[#FCA5A5]">Error</h2>
            <pre class="mt-3 whitespace-pre-wrap text-xs text-[#FCA5A5]">{{ $log->error }}</pre>
        </div>
    @endif
</div>
@if(in_array($log->status, ['pending', 'running'], true))
    <script>
        (() => {
            const panel = document.getElementById('runtime-reload-progress');
            if (!panel) return;

            const statusUrl = panel.dataset.statusUrl;
            const statusEl = document.getElementById('reload-status');
            const stepEl = document.getElementById('reload-current-step');
            const barEl = document.getElementById('reload-progress-bar');
            const labelEl = document.getElementById('reload-progress-label');
            const durationEl = document.getElementById('reload-duration');
            const completeEl = document.getElementById('reload-complete-message');
            const errorEl = document.getElementById('reload-error');

            const poll = async () => {
                try {
                    const response = await fetch(statusUrl, {headers: {'Accept': 'application/json'}});
                    if (!response.ok) return;

                    const payload = await response.json();
                    const total = Math.max(1, Number(payload.steps_total || 0));
                    const completed = Number(payload.steps_completed || 0);
                    const percent = Math.min(100, Math.floor((completed / total) * 100));

                    statusEl.textContent = payload.status || 'running';
                    stepEl.textContent = payload.current_step || 'Working';
                    barEl.style.width = `${percent}%`;
                    labelEl.textContent = `${completed} / ${payload.steps_total || 0}`;
                    durationEl.textContent = `${payload.duration_ms || 0}ms`;

                    if (payload.error) {
                        errorEl.textContent = payload.error;
                        errorEl.classList.remove('hidden');
                    }

                    if (payload.completed) {
                        completeEl.classList.remove('hidden');
                        window.clearInterval(timer);
                        window.setTimeout(() => window.location.reload(), 1000);
                    }
                } catch (error) {
                    // Keep polling; transient network failures should not interrupt the operator view.
                }
            };

            const timer = window.setInterval(poll, 2000);
            poll();
        })();
    </script>
@endif
</x-admin-layout>
