<x-admin-layout title="Runtime Helpers" subtitle="Manage admin-defined runtime helpers.">
<div class="space-y-4">
    @if(\App\Models\RuntimeHelper::query()->where('requires_runtime_reload', true)->exists())
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-4 py-3 text-sm text-[#F59E0B]">
            <span>Helper bundle publish required. Some activated helpers are not compiled into the generated bundle yet.</span>
            <a href="{{ route('admin.runtime.reload.index') }}" class="rounded-lg border border-[#F59E0B]/30 px-3 py-1.5 text-xs font-black text-[#F59E0B]">Publish Helper Bundle</a>
        </div>
    @endif
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-black text-white">Runtime Helpers</h1>
            <p class="mt-1 text-xs text-[#94A3B8]">Database activation only. Runtime reload is not connected yet.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('admin.runtime.health.force-apply-helpers') }}" class="flex flex-wrap items-center gap-2">
                @csrf
                <input name="confirm_force_apply" autocomplete="off" class="w-64 rounded-xl border border-[#22C55E]/40 bg-[#090713] px-3 py-2 font-mono text-xs text-white placeholder:text-[#166534]" placeholder="FORCE_APPLY_RUNTIME_HELPERS">
                <button class="rounded-xl border border-[#22C55E]/40 px-4 py-2 text-sm font-black text-[#22C55E] hover:bg-[#22C55E]/10">Force Apply Runtime Helpers</button>
            </form>
            <a href="{{ route('admin.runtime.health.index') }}" class="rounded-xl border border-[#38BDF8]/40 px-4 py-2 text-sm font-black text-[#7DD3FC] hover:bg-[#38BDF8]/10">Health Center</a>
            <a href="{{ route('admin.runtime.helper-types.index') }}" class="rounded-xl border border-[#27213D] px-4 py-2 text-sm font-black text-[#A1A1AA] hover:text-white">Helper Types</a>
            <a href="{{ route('admin.runtime.helpers.create') }}" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-sm font-black text-white hover:bg-[#7C3AED]">New Helper</a>
        </div>
    </div>
    <form method="GET" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-3">
        <div class="grid gap-2 md:grid-cols-6">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search helpers..." class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white md:col-span-2">
            <select name="category_id" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>@endforeach</select>
            <select name="helper_type" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white"><option value="">All types</option>@foreach($helperTypes as $type)<option value="{{ $type->slug }}" @selected(($filters['helper_type'] ?? '') === $type->slug)>{{ $type->name }}</option>@endforeach</select>
            <select name="status" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white"><option value="">All statuses</option>@foreach(['draft','active','disabled','deprecated'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
            <button class="rounded-xl border border-[#8B5CF6]/40 px-4 py-2 text-sm font-bold text-[#A855F7]">Filter</button>
        </div>
    </form>
    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
        <div class="overflow-x-auto">
            <table class="min-w-max w-full text-sm">
                <thead><tr class="border-b border-[#27213D] bg-[#090713]">
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Helper</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Category</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Status</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Version</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Reload</th>
                    <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-[#1B172B]">
                    @forelse($helpers as $helper)
                        <tr class="hover:bg-[#151225]">
                            <td class="px-4 py-3"><p class="font-bold text-white">{{ $helper->label }}</p><p class="font-mono text-xs text-[#94A3B8]">{{ $helper->name }} · {{ $helper->type?->name ?? $helper->helper_type }} ({{ $helper->helper_type }})</p></td>
                            <td class="px-4 py-3 text-[#A1A1AA]">{{ $helper->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3"><span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $helper->status === 'active' ? 'bg-[#22C55E]/10 text-[#22C55E]' : ($helper->status === 'disabled' ? 'bg-[#EF4444]/10 text-[#EF4444]' : 'bg-[#F59E0B]/10 text-[#F59E0B]') }}">{{ $helper->status }}</span></td>
                            <td class="px-4 py-3 text-xs text-[#94A3B8]">Active: {{ $helper->activeVersion?->version_number ?? 'none' }} · Latest: {{ $helper->versions_max_version_number ?? 'none' }}</td>
                            <td class="px-4 py-3 text-xs {{ $helper->requires_runtime_reload ? 'text-[#F59E0B]' : 'text-[#6B6890]' }}">{{ $helper->requires_runtime_reload ? 'Required' : 'No' }}</td>
                            <td class="px-4 py-3"><div class="flex justify-end gap-2">
                                <a href="{{ route('admin.runtime.helpers.edit', $helper) }}" class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA]">Edit</a>
                                <a href="{{ route('admin.runtime.helpers.versions.index', $helper) }}" class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA]">Versions</a>
                                @if($helper->status !== 'active')<form method="POST" action="{{ route('admin.runtime.helpers.activate', $helper) }}">@csrf<button class="rounded-lg border border-[#22C55E]/30 px-3 py-1.5 text-xs font-bold text-[#22C55E]">Activate</button></form>@endif
                                @if($helper->status !== 'disabled')<form method="POST" action="{{ route('admin.runtime.helpers.deactivate', $helper) }}">@csrf<button class="rounded-lg border border-[#F59E0B]/30 px-3 py-1.5 text-xs font-bold text-[#F59E0B]">Disable</button></form>@endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-[#94A3B8]">No runtime helpers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $helpers->links() }}
</div>
</x-admin-layout>
