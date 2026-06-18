<x-admin-layout title="Helper Types" subtitle="Manage runtime helper type keys.">
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-black text-white">Helper Types</h1>
            <p class="mt-1 text-xs text-[#94A3B8]">Control the helper type dropdown used by runtime helpers and categories.</p>
        </div>
        <a href="{{ route('admin.runtime.helper-types.create') }}" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-sm font-black text-white hover:bg-[#7C3AED]">New Type</a>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-3">
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search types..." class="min-w-64 flex-1 rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
        <button class="rounded-xl border border-[#8B5CF6]/40 px-4 py-2 text-sm font-bold text-[#A855F7]">Filter</button>
        @if(($filters['search'] ?? '') !== '')
            <a href="{{ route('admin.runtime.helper-types.index') }}" class="rounded-xl border border-[#27213D] px-4 py-2 text-sm font-bold text-[#94A3B8]">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
        <div class="overflow-x-auto">
            <table class="min-w-max w-full text-sm">
                <thead><tr class="border-b border-[#27213D] bg-[#090713]">
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Type</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Description</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Usage</th>
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Status</th>
                    <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-[#1B172B]">
                    @forelse($types as $type)
                        <tr class="hover:bg-[#151225]">
                            <td class="px-4 py-3">
                                <p class="font-bold text-white">{{ $type->name }}</p>
                                <p class="font-mono text-xs text-[#94A3B8]">{{ $type->slug }}</p>
                            </td>
                            <td class="max-w-xl px-4 py-3 text-xs text-[#A1A1AA]">{{ $type->description ?: 'No description.' }}</td>
                            <td class="px-4 py-3 text-xs text-[#94A3B8]">{{ $type->helpers_count }} helpers · {{ $type->categories_count }} categories</td>
                            <td class="px-4 py-3"><span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $type->is_active ? 'bg-[#22C55E]/10 text-[#22C55E]' : 'bg-[#71717A]/10 text-[#A1A1AA]' }}">{{ $type->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.runtime.helper-types.edit', $type) }}" class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA]">Edit</a>
                                    <form method="POST" action="{{ route('admin.runtime.helper-types.toggle', $type) }}">@csrf @method('PATCH')<button class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA]">{{ $type->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                                    <form method="POST" action="{{ route('admin.runtime.helper-types.destroy', $type) }}">@csrf @method('DELETE')<button data-confirm data-confirm-type="danger" data-confirm-title="Delete helper type?" data-confirm-message="This type can only be deleted if no helpers or categories use it." class="rounded-lg border border-[#EF4444]/30 px-3 py-1.5 text-xs font-bold text-[#EF4444]">Delete</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-[#94A3B8]">No helper types found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $types->links() }}
</div>
</x-admin-layout>
