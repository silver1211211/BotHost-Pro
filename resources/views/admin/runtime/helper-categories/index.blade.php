<x-admin-layout title="Helper Categories" subtitle="Manage admin runtime helper categories.">
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-black text-white">Helper Categories</h1>
            <p class="mt-1 text-xs text-[#94A3B8]">Categories organize admin-managed helper functions before runtime publishing.</p>
        </div>
        <a href="{{ route('admin.runtime.helper-categories.create') }}" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-sm font-black text-white transition hover:bg-[#7C3AED]">New Category</a>
    </div>

    <form method="GET" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-3">
        <div class="flex flex-wrap gap-2">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, slug, type..." class="min-w-[260px] flex-1 rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white placeholder:text-[#4D4868] focus:border-[#8B5CF6]/50 focus:outline-none">
            <button class="rounded-xl border border-[#8B5CF6]/40 px-4 py-2 text-sm font-bold text-[#A855F7]">Search</button>
            @if(filled($filters['search'] ?? ''))
                <a href="{{ route('admin.runtime.helper-categories.index') }}" class="rounded-xl border border-[#27213D] px-4 py-2 text-sm font-bold text-[#94A3B8]">Clear</a>
            @endif
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
        <div class="overflow-x-auto">
            <table class="min-w-max w-full text-sm">
                <thead>
                    <tr class="border-b border-[#27213D] bg-[#090713]">
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Category</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Type</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Defaults</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Helpers</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Status</th>
                        <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-[#6B6890]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1B172B]">
                    @forelse($categories as $category)
                        <tr class="hover:bg-[#151225]">
                            <td class="px-4 py-3">
                                <p class="font-bold text-white">{{ $category->name }}</p>
                                <p class="font-mono text-xs text-[#94A3B8]">{{ $category->slug }}</p>
                            </td>
                            <td class="px-4 py-3 text-[#A1A1AA]">{{ $category->helper_type }}</td>
                            <td class="px-4 py-3 text-xs text-[#94A3B8]">{{ $category->default_timeout_ms }}ms · level {{ $category->permission_level }} · sort {{ $category->sort_order }}</td>
                            <td class="px-4 py-3 text-[#A1A1AA]">{{ $category->helpers_count }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $category->is_active ? 'bg-[#22C55E]/10 text-[#22C55E]' : 'bg-[#71717A]/10 text-[#94A3B8]' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.runtime.helper-categories.edit', $category) }}" class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA] hover:text-white">Edit</a>
                                    <form method="POST" action="{{ route('admin.runtime.helper-categories.toggle', $category) }}">@csrf @method('PATCH')<button class="rounded-lg border border-[#27213D] px-3 py-1.5 text-xs font-bold text-[#A1A1AA] hover:text-white">{{ $category->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                                    <form method="POST" action="{{ route('admin.runtime.helper-categories.destroy', $category) }}">@csrf @method('DELETE')<button data-confirm data-confirm-type="danger" data-confirm-title="Delete category?" data-confirm-message="This category can only be deleted if it has no helpers." class="rounded-lg border border-[#EF4444]/30 px-3 py-1.5 text-xs font-bold text-[#EF4444]">Delete</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-[#94A3B8]">No helper categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $categories->links() }}
</div>
</x-admin-layout>
