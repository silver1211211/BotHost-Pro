<x-dashboard-layout title="Projects">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-extrabold uppercase tracking-[0.22em] text-[#229ED9]">Workspace</p>
            <h2 class="mt-2 text-3xl font-black text-[#F8FAFC]">Projects</h2>
            <p class="mt-1 text-sm text-[#94A3B8]">Create and manage JavaScript bot projects.</p>
        </div>
        <a href="{{ route('projects.create') }}" class="rounded-xl bg-[#229ED9] px-5 py-3 text-center text-sm font-bold text-white shadow-[0_0_20px_rgba(34,158,217,0.22)] transition hover:bg-[#38BDF8]">New Project</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-[#1E293B] bg-[#0B1220] shadow-[0_20px_70px_rgba(0,0,0,0.22)]">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#1E293B] text-sm">
                <thead>
                    <tr class="bg-[#111827]">
                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.16em] text-[#64748B]">Name</th>
                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.16em] text-[#64748B]">Status</th>
                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.16em] text-[#64748B]">Language</th>
                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.16em] text-[#64748B]">Created</th>
                        <th class="px-5 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1E293B]">
                    @forelse ($projects as $project)
                        <tr class="transition hover:bg-[#111827]">
                            <td class="px-5 py-4">
                                <a href="{{ route('projects.show', $project) }}" class="font-bold text-[#F8FAFC] transition hover:text-[#38BDF8]">{{ $project->name }}</a>
                                <p class="text-xs text-[#64748B]">{{ $project->slug }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <x-status-badge :status="$project->status" />
                            </td>
                            <td class="px-5 py-4 text-[#94A3B8]">{{ $project->language }}</td>
                            <td class="px-5 py-4 text-[#94A3B8]">{{ $project->created_at->format('M d, Y') }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('projects.edit', $project) }}" class="text-sm font-bold text-[#229ED9] transition hover:text-[#38BDF8]">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-[#94A3B8]">No projects created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $projects->links() }}</div>
</x-dashboard-layout>
