<x-admin-layout title="Runtime Reload Logs" subtitle="Bundle publish and runtime refresh reporting.">
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.runtime.reload.index') }}" class="text-sm text-[#A1A1AA] hover:text-white">Back to bundle</a>
    </div>
    <form method="GET" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-3">
        <div class="flex flex-wrap gap-2">
            <select name="status" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
                <option value="">All statuses</option>
                @foreach(['pending','running','success','partial','failed','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <input name="trigger_type" value="{{ $filters['trigger_type'] ?? '' }}" placeholder="Trigger type" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
            <select name="mode" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
                <option value="">All modes</option>
                @foreach(['prepare_only','docker'] as $mode)
                    <option value="{{ $mode }}" @selected(($filters['mode'] ?? '') === $mode)>{{ str_replace('_', ' ', ucfirst($mode)) }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
            <button class="rounded-xl border border-[#8B5CF6]/40 px-4 py-2 text-sm font-bold text-[#A855F7]">Filter</button>
        </div>
    </form>
    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
        <table class="min-w-max w-full text-sm">
            <thead><tr class="border-b border-[#27213D] bg-[#090713]">
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Status</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Trigger</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Run</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Helpers</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Containers</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Started</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Completed</th>
                <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Action</th>
            </tr></thead>
            <tbody class="divide-y divide-[#1B172B]">
                @forelse($logs as $log)
                    @php
                        $counts = $log->summaryCounts();
                        $output = $log->parsedOutput();
                        $statusClass = match ($log->status) {
                            'success' => 'bg-[#22C55E]/10 text-[#22C55E]',
                            'partial' => 'bg-[#F59E0B]/10 text-[#F59E0B]',
                            'failed' => 'bg-[#EF4444]/10 text-[#EF4444]',
                            'cancelled' => 'bg-[#64748B]/10 text-[#CBD5E1]',
                            default => 'bg-[#F59E0B]/10 text-[#F59E0B]',
                        };
                    @endphp
                    <tr class="hover:bg-[#151225]">
                        <td class="px-4 py-3"><span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $statusClass }}">{{ $log->status }}</span></td>
                        <td class="px-4 py-3 text-[#A1A1AA]">{{ $log->trigger_type }}</td>
                        <td class="px-4 py-3">
                            @if(array_key_exists('dry_run', $output))
                                <span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $log->isDryRun() ? 'bg-[#38BDF8]/10 text-[#7DD3FC]' : 'bg-[#EF4444]/10 text-[#FCA5A5]' }}">{{ $log->isDryRun() ? 'Dry run' : 'Live' }}</span>
                            @else
                                <span class="text-xs text-[#94A3B8]">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-[#A1A1AA]">{{ $counts['helpers_compiled'] }} compiled / {{ $counts['helpers_skipped'] }} skipped</td>
                        <td class="px-4 py-3 text-[#A1A1AA]">{{ $log->containers_ok ?? 0 }} ok / {{ $log->containers_failed ?? 0 }} failed</td>
                        <td class="px-4 py-3 text-xs text-[#94A3B8]">{{ $log->started_at?->format('M j, Y H:i:s') ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs text-[#94A3B8]">{{ $log->completed_at?->format('M j, Y H:i:s') ?? '-' }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.runtime.reload.show', $log) }}" class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA] hover:text-white">Details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div>
</x-admin-layout>
