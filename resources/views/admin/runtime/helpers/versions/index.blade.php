<x-admin-layout title="Helper Versions" subtitle="{{ $helper->name }}">
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.runtime.helpers.edit', $helper) }}" class="text-sm text-[#A1A1AA] hover:text-white">Back to helper</a>
            <h1 class="mt-2 text-xl font-black text-white">{{ $helper->label }} Versions</h1>
        </div>
        @if($helper->requires_runtime_reload)
            <span class="rounded-xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-3 py-2 text-xs font-bold text-[#F59E0B]">Runtime reload required</span>
        @endif
    </div>
    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
        <table class="min-w-max w-full text-sm">
            <thead><tr class="border-b border-[#27213D] bg-[#090713]">
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Version</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Checks</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Summary</th>
                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Created</th>
                <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-[#1B172B]">
                @foreach($versions as $version)
                    <tr class="align-top hover:bg-[#151225]">
                        <td class="px-4 py-3"><p class="font-bold text-white">v{{ $version->version_number }}</p><p class="text-xs text-[#94A3B8]">{{ $version->status }}</p></td>
                        <td class="px-4 py-3 text-xs text-[#94A3B8]">Safety: {{ $version->safety_scan_status }}<br>Syntax: {{ $version->syntax_check_status }}<br>Test: {{ $version->test_status ?? 'not_run' }}</td>
                        <td class="max-w-[360px] px-4 py-3 text-sm text-[#A1A1AA]">{{ $version->change_summary ?: '—' }}<details class="mt-2"><summary class="cursor-pointer text-xs text-[#8B5CF6]">View code</summary><pre class="mt-2 max-h-72 overflow-auto rounded-xl bg-[#090713] p-3 text-xs text-[#A1A1AA]">{{ $version->code }}</pre></details></td>
                        <td class="px-4 py-3 text-xs text-[#94A3B8]">{{ $version->creator?->name ?? 'System' }}<br>{{ $version->created_at?->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3 text-right"><form method="POST" action="{{ route('admin.runtime.helpers.versions.restore', [$helper, $version]) }}">@csrf<button class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA] hover:text-white">Restore as Draft</button></form></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $versions->links() }}
</div>
</x-admin-layout>
