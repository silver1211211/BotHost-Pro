<x-admin-layout title="Runtime Helper Bundle" subtitle="Controlled helper bundle publishing.">
<div class="space-y-5">
    <div class="rounded-2xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-4 py-3 text-sm text-[#F59E0B]">
        Publish compiles active helpers into the generated bundle. Apply Runtime To Bots recreates Docker bot containers when their helper mount, helper bundle hash, or runtime image support is outdated.
    </div>

    @if(session('runtime_reload_log_id'))
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#38BDF8]/30 bg-[#38BDF8]/10 px-4 py-3 text-sm text-[#BAE6FD]">
            <span>Latest publish log is ready for review.</span>
            <a href="{{ route('admin.runtime.reload.show', session('runtime_reload_log_id')) }}" class="rounded-lg border border-[#38BDF8]/40 px-3 py-1.5 text-xs font-black text-[#BAE6FD] hover:bg-[#38BDF8]/10">View latest reload log</a>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <p class="text-xs text-[#94A3B8]">Publish Required</p>
            <p class="mt-2 text-2xl font-black text-white">{{ $reloadRequiredCount }}</p>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <p class="text-xs text-[#94A3B8]">Active Helpers</p>
            <p class="mt-2 text-2xl font-black text-white">{{ $activeHelperCount }}</p>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <p class="text-xs text-[#94A3B8]">Live Bundle Exists</p>
            <p class="mt-2 text-2xl font-black {{ $liveBundleExists ? 'text-[#22C55E]' : 'text-[#EF4444]' }}">{{ $liveBundleExists ? 'Yes' : 'No' }}</p>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <p class="text-xs text-[#94A3B8]">Last Publish</p>
            <p class="mt-2 text-sm font-bold text-white">{{ $lastLog?->status ? ucfirst($lastLog->status) : 'None' }}</p>
            <p class="mt-1 text-xs text-[#94A3B8]">{{ $lastLog?->created_at?->diffForHumans() }}</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-white">Last Bundle Publish Result</h2>
                    <p class="mt-1 text-xs text-[#94A3B8]">{{ $lastBundleLog?->created_at?->diffForHumans() ?? 'No publish yet' }}</p>
                </div>
                @if($lastBundleLog)
                    <a href="{{ route('admin.runtime.reload.show', $lastBundleLog) }}" class="text-xs font-bold text-[#8B5CF6] hover:text-[#A855F7]">Details</a>
                @endif
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 text-sm">
                <div><p class="text-xs text-[#94A3B8]">Compiled</p><p class="mt-1 font-black text-white">{{ $lastBundleSummary['helpers_compiled'] ?? 0 }}</p></div>
                <div><p class="text-xs text-[#94A3B8]">Skipped</p><p class="mt-1 font-black text-white">{{ $lastBundleSummary['helpers_skipped'] ?? 0 }}</p></div>
                <div><p class="text-xs text-[#94A3B8]">Status</p><p class="mt-1 font-black text-white">{{ $lastBundleLog?->status ? ucfirst($lastBundleLog->status) : '-' }}</p></div>
            </div>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-white">Last Docker Dry-Run Result</h2>
                    <p class="mt-1 text-xs text-[#94A3B8]">{{ $lastDryRunLog?->created_at?->diffForHumans() ?? 'No dry run yet' }}</p>
                </div>
                @if($lastDryRunLog)
                    <a href="{{ route('admin.runtime.reload.show', $lastDryRunLog) }}" class="text-xs font-bold text-[#8B5CF6] hover:text-[#A855F7]">Details</a>
                @endif
            </div>
            <div class="mt-4 grid grid-cols-4 gap-2 text-sm">
                <div><p class="text-xs text-[#94A3B8]">Checked</p><p class="mt-1 font-black text-white">{{ $lastDryRunSummary['bots_checked'] ?? 0 }}</p></div>
                <div><p class="text-xs text-[#94A3B8]">Ready</p><p class="mt-1 font-black text-white">{{ $lastDryRunSummary['ready'] ?? 0 }}</p></div>
                <div><p class="text-xs text-[#94A3B8]">Recreate</p><p class="mt-1 font-black text-white">{{ $lastDryRunSummary['would_recreate'] ?? 0 }}</p></div>
                <div><p class="text-xs text-[#94A3B8]">Unknown</p><p class="mt-1 font-black text-white">{{ $lastDryRunSummary['unknown'] ?? 0 }}</p></div>
            </div>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-white">Last Runtime Apply Result</h2>
                    <p class="mt-1 text-xs text-[#94A3B8]">{{ $lastLiveLog?->created_at?->diffForHumans() ?? 'No live refresh yet' }}</p>
                </div>
                @if($lastLiveLog)
                    <a href="{{ route('admin.runtime.reload.show', $lastLiveLog) }}" class="text-xs font-bold text-[#8B5CF6] hover:text-[#A855F7]">Details</a>
                @endif
            </div>
            <div class="mt-4 grid grid-cols-4 gap-2 text-sm">
                <div><p class="text-xs text-[#94A3B8]">Recreated</p><p class="mt-1 font-black text-white">{{ $lastLiveSummary['recreated'] ?? 0 }}</p></div>
                <div><p class="text-xs text-[#94A3B8]">Failed</p><p class="mt-1 font-black text-white">{{ $lastLiveSummary['failed'] ?? 0 }}</p></div>
                <div><p class="text-xs text-[#94A3B8]">Skipped</p><p class="mt-1 font-black text-white">{{ $lastLiveSummary['skipped'] ?? 0 }}</p></div>
                <div><p class="text-xs text-[#94A3B8]">Status</p><p class="mt-1 font-black text-white">{{ $lastLiveLog?->status ? ucfirst($lastLiveLog->status) : '-' }}</p></div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-black text-white">Async Process Diagnostics</h2>
                <p class="mt-1 text-xs text-[#94A3B8]">Background runtime reload process readiness.</p>
            </div>
            <span class="rounded-lg px-2 py-1 text-xs font-black uppercase {{ ($processDiagnostics['ok'] ?? false) ? 'bg-[#22C55E]/10 text-[#22C55E]' : 'bg-[#EF4444]/10 text-[#EF4444]' }}">{{ ($processDiagnostics['ok'] ?? false) ? 'OK' : 'Error' }}</span>
        </div>
        <dl class="mt-4 grid gap-3 text-sm md:grid-cols-4">
            <div><dt class="text-[#94A3B8]">PHP binary</dt><dd class="break-all font-mono text-xs text-white">{{ $processDiagnostics['php_binary'] ?? '-' }}</dd></div>
            <div><dt class="text-[#94A3B8]">artisan exists</dt><dd class="text-white">{{ ($processDiagnostics['artisan_exists'] ?? false) ? 'Yes' : 'No' }}</dd></div>
            <div><dt class="text-[#94A3B8]">storage logs writable</dt><dd class="text-white">{{ ($processDiagnostics['logs_writable'] ?? false) ? 'Yes' : 'No' }}</dd></div>
            <div><dt class="text-[#94A3B8]">proc_open available</dt><dd class="text-white">{{ ($processDiagnostics['proc_open_available'] ?? false) ? 'Yes' : 'No' }}</dd></div>
        </dl>
        @if(! empty($processDiagnostics['errors']))
            <ul class="mt-3 space-y-1 text-sm text-[#FCA5A5]">
                @foreach($processDiagnostics['errors'] as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-black text-white">Generated Bundle</h2>
                <p class="mt-1 font-mono text-xs text-[#94A3B8]">{{ $liveBundlePath }}</p>
                <p class="mt-1 text-xs text-[#94A3B8]">Last modified: {{ $liveBundleModifiedAt ?? 'Not generated yet' }}</p>
            </div>
            <form method="POST" action="{{ route('admin.runtime.reload.publish-bundle') }}">
                @csrf
                <button class="rounded-xl bg-[#8B5CF6] px-5 py-2.5 text-sm font-black text-white hover:bg-[#7C3AED]">Publish Helper Bundle</button>
            </form>
        </div>
    </div>

    <div class="rounded-2xl border border-[#22C55E]/30 bg-[#22C55E]/10 p-4">
        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <h2 class="text-sm font-black text-[#BBF7D0]">Publish & Apply Helpers</h2>
                <p class="mt-2 text-sm text-[#86EFAC]">Use this after activating a helper. It publishes the bundle, detects helper bundle hash changes, then recreates only Docker bot containers that need the new helper list.</p>
                <a href="{{ route('admin.runtime.health.index') }}" class="mt-3 inline-flex rounded-lg border border-[#22C55E]/40 px-3 py-1.5 text-xs font-black text-[#BBF7D0] hover:bg-[#22C55E]/10">Open Runtime Health Center</a>
            </div>
            <form method="POST" action="{{ route('admin.runtime.reload.publish-and-apply') }}" class="space-y-3">
                @csrf
                <label class="block text-xs font-bold text-[#BBF7D0]" for="confirm_publish_apply">Type PUBLISH_AND_APPLY_HELPERS to confirm</label>
                <input id="confirm_publish_apply" name="confirm_publish_apply" autocomplete="off" class="w-full rounded-xl border border-[#22C55E]/40 bg-[#090713] px-3 py-2 font-mono text-sm text-white placeholder:text-[#166534]" placeholder="PUBLISH_AND_APPLY_HELPERS">
                <button class="rounded-xl bg-[#16A34A] px-5 py-2.5 text-sm font-black text-white hover:bg-[#15803D]">Publish & Apply Helpers</button>
            </form>
        </div>
    </div>

    <div class="rounded-2xl border border-[#38BDF8]/30 bg-[#38BDF8]/10 p-4">
        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <h2 class="text-sm font-black text-[#BAE6FD]">Force Apply Runtime Helpers</h2>
                <p class="mt-2 text-sm text-[#7DD3FC]">Use this when runtime containers are marked current but live helper delivery still needs a direct publish and Docker refresh.</p>
            </div>
            <form method="POST" action="{{ route('admin.runtime.health.force-apply-helpers') }}" class="space-y-3">
                @csrf
                <label class="block text-xs font-bold text-[#BAE6FD]" for="reload_confirm_force_apply">Type FORCE_APPLY_RUNTIME_HELPERS to confirm</label>
                <input id="reload_confirm_force_apply" name="confirm_force_apply" autocomplete="off" class="w-full rounded-xl border border-[#38BDF8]/40 bg-[#090713] px-3 py-2 font-mono text-sm text-white placeholder:text-[#075985]" placeholder="FORCE_APPLY_RUNTIME_HELPERS">
                <button class="rounded-xl border border-[#38BDF8]/40 px-5 py-2.5 text-sm font-black text-[#BAE6FD] hover:bg-[#38BDF8]/10">Force Apply Runtime Helpers</button>
            </form>
        </div>
    </div>

    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-3xl">
                <h2 class="text-sm font-black text-white">Runtime Container Refresh Planning</h2>
                <p class="mt-2 text-sm text-[#A1A1AA]">Dry-run checks bundle mount, helper bundle hash, helper-loader support, and runtime source hash. It does not stop, remove, or restart any container.</p>
                @if($lastDryRunLog)
                    <div class="mt-4 rounded-xl border border-[#27213D] bg-[#090713] p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs font-bold text-[#94A3B8]">Latest dry run: <span class="text-white">{{ ucfirst($lastDryRunLog->status) }}</span> - {{ $lastDryRunLog->created_at?->diffForHumans() }}</p>
                            <a href="{{ route('admin.runtime.reload.show', $lastDryRunLog) }}" class="text-xs font-bold text-[#8B5CF6] hover:text-[#A855F7]">View log</a>
                        </div>
                        <pre class="mt-3 max-h-56 overflow-auto whitespace-pre-wrap text-xs text-[#A1A1AA]">{{ $lastDryRunLog->output ?: 'No dry-run output yet.' }}</pre>
                    </div>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.runtime.reload.docker-refresh-plan') }}">
                @csrf
                <button class="rounded-xl border border-[#38BDF8]/40 px-5 py-2.5 text-sm font-black text-[#7DD3FC] hover:bg-[#38BDF8]/10">Check Runtime Apply Plan</button>
            </form>
        </div>
    </div>

    @if($lastDryRunWouldRecreateCount > 0)
        <div class="rounded-2xl border border-[#EF4444]/40 bg-[#EF4444]/10 p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-3xl">
                    <h2 class="text-sm font-black text-[#FCA5A5]">Apply Runtime To Bots</h2>
                    <p class="mt-2 text-sm text-[#FCA5A5]">This recreates Docker runtime containers that are missing the helper bundle mount, missing helper-loader support, running an old helper bundle hash, or running an old runtime source hash. It may cause short downtime for those bots. Run dry-run first.</p>
                    <p class="mt-2 text-xs font-bold text-[#FECACA]">Latest dry run found {{ $lastDryRunWouldRecreateCount }} container{{ $lastDryRunWouldRecreateCount === 1 ? '' : 's' }} that would be recreated.</p>
                </div>
                <form method="POST" action="{{ route('admin.runtime.reload.docker-refresh-live') }}" class="w-full max-w-xl space-y-3">
                    @csrf
                    <label class="block text-xs font-bold text-[#FECACA]" for="confirm_live_refresh">Type YES_RECREATE_DOCKER_CONTAINERS to confirm</label>
                    <input id="confirm_live_refresh" name="confirm_live_refresh" autocomplete="off" class="w-full rounded-xl border border-[#EF4444]/50 bg-[#090713] px-3 py-2 font-mono text-sm text-white placeholder:text-[#7F1D1D]" placeholder="YES_RECREATE_DOCKER_CONTAINERS">
                    <button class="rounded-xl bg-[#DC2626] px-5 py-2.5 text-sm font-black text-white hover:bg-[#B91C1C]">Apply Runtime To Bots</button>
                </form>
            </div>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-black text-white">Latest Logs</h2>
        <a href="{{ route('admin.runtime.reload.logs') }}" class="text-xs font-bold text-[#8B5CF6] hover:text-[#A855F7]">View all logs</a>
    </div>
    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
        <table class="min-w-max w-full text-sm">
            <thead><tr class="border-b border-[#27213D] bg-[#090713]">
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Status</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Trigger</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Compiled</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Created</th>
                <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Action</th>
            </tr></thead>
            <tbody class="divide-y divide-[#1B172B]">
                @forelse($logs as $log)
                    <tr class="hover:bg-[#151225]">
                        <td class="px-4 py-3"><span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $log->status === 'success' ? 'bg-[#22C55E]/10 text-[#22C55E]' : ($log->status === 'failed' ? 'bg-[#EF4444]/10 text-[#EF4444]' : 'bg-[#F59E0B]/10 text-[#F59E0B]') }}">{{ $log->status }}</span></td>
                        <td class="px-4 py-3 text-[#A1A1AA]">{{ $log->trigger_type }}</td>
                        <td class="px-4 py-3 text-[#A1A1AA]">{{ $log->helpers_compiled ?? 0 }}</td>
                        <td class="px-4 py-3 text-xs text-[#94A3B8]">{{ $log->created_at?->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.runtime.reload.show', $log) }}" class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA] hover:text-white">Details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No publish logs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-admin-layout>
