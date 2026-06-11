<x-admin-layout title="Templates" subtitle="Manage command templates">
    <div class="space-y-4">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-black text-[#F8FAFC]">Templates</h1>
                <p class="mt-0.5 text-xs text-[#71717A]">Manage command templates available to users.</p>
            </div>
            <a href="{{ route('admin.templates.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-4 py-2.5 text-sm font-black text-white shadow-[0_0_20px_rgba(139,92,246,0.3)] transition hover:shadow-[0_0_28px_rgba(139,92,246,0.45)]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New Template
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/10 px-4 py-3 text-sm font-semibold text-[#22C55E]">{{ session('status') }}</div>
        @endif

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-[#27213D]" style="background: #0F0D1A;">
            <div class="overflow-x-auto">
                <table class="min-w-max w-full text-sm">
                    <thead>
                        <tr style="background: #090713; border-bottom: 1px solid #27213D;">
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Name</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Category</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Level</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Status</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Access</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Price</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Cmds</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Imports</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Sales</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Revenue</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Marketplace</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Featured</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Created</th>
                            <th class="px-4 py-3.5 text-right text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1B172B]">
                        @forelse ($templates as $template)
                            @php
                                $statusStyle = match($template->status) {
                                    'published' => 'bg-[#22C55E]/10 text-[#22C55E]',
                                    'archived'  => 'bg-[#71717A]/10 text-[#71717A]',
                                    default     => 'bg-[#F59E0B]/10 text-[#F59E0B]',
                                };
                                $accessStyle = match($template->access_type ?? 'free') {
                                    'paid'    => 'bg-[#A855F7]/10 text-[#A855F7]',
                                    'plan'    => 'bg-[#38BDF8]/10 text-[#38BDF8]',
                                    default   => 'bg-[#71717A]/10 text-[#71717A]',
                                };
                                $marketStyle = match($template->marketplace_status ?? 'listed') {
                                    'listed'   => 'bg-[#22C55E]/10 text-[#22C55E]',
                                    'unlisted' => 'bg-[#71717A]/10 text-[#71717A]',
                                    default    => 'bg-[#71717A]/10 text-[#71717A]',
                                };
                            @endphp
                            <tr class="group transition-colors hover:bg-[#151225]">
                                <td class="px-4 py-3.5 font-semibold text-[#F8FAFC] max-w-[180px] truncate">{{ $template->name }}</td>
                                <td class="px-4 py-3.5 text-[#71717A]">{{ $template->category ?: '—' }}</td>
                                <td class="px-4 py-3.5 text-[#A1A1AA]">{{ ucfirst($template->level) }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-block rounded-lg px-2 py-0.5 text-[10px] font-black uppercase tracking-wide {{ $statusStyle }}">{{ ucfirst($template->status) }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-block rounded-lg px-2 py-0.5 text-[10px] font-black uppercase tracking-wide {{ $accessStyle }}">{{ ucfirst($template->access_type ?? 'free') }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-[#A1A1AA] font-mono text-xs">{{ $template->currency ?? 'USD' }} {{ number_format((float) $template->price, 2) }}</td>
                                <td class="px-4 py-3.5 text-[#A1A1AA]">{{ $template->commands_count }}</td>
                                <td class="px-4 py-3.5 text-[#A1A1AA]">{{ $template->import_count }}</td>
                                <td class="px-4 py-3.5 text-[#A1A1AA]">{{ $template->sales_count }}</td>
                                <td class="px-4 py-3.5 text-[#A1A1AA] font-mono text-xs">{{ $template->currency ?? 'USD' }} {{ number_format((float) $template->revenue_total, 2) }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-block rounded-lg px-2 py-0.5 text-[10px] font-black uppercase tracking-wide {{ $marketStyle }}">{{ ucfirst($template->marketplace_status ?? 'listed') }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($template->is_featured)
                                        <span class="inline-block rounded-lg bg-[#F59E0B]/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-[#F59E0B]">Yes</span>
                                    @else
                                        <span class="text-[#3D3658] text-xs">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs text-[#71717A] whitespace-nowrap">{{ $template->created_at->format('M j, Y') }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.templates.edit', $template) }}" class="rounded-lg border border-[#27213D] bg-[#151225] px-2.5 py-1 text-[11px] font-semibold text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-white">Edit</a>
                                        @if ($template->status !== 'published')
                                            <form method="POST" action="{{ route('admin.templates.publish', $template) }}">@csrf<button class="rounded-lg border border-[#22C55E]/30 bg-[#22C55E]/8 px-2.5 py-1 text-[11px] font-semibold text-[#22C55E] transition hover:bg-[#22C55E]/15">Publish</button></form>
                                        @endif
                                        @if ($template->status !== 'archived')
                                            <form method="POST" action="{{ route('admin.templates.archive', $template) }}">@csrf<button class="rounded-lg border border-[#F59E0B]/30 bg-[#F59E0B]/8 px-2.5 py-1 text-[11px] font-semibold text-[#F59E0B] transition hover:bg-[#F59E0B]/15">Archive</button></form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.templates.destroy', $template) }}">@csrf @method('DELETE')<button type="submit" data-confirm data-confirm-type="danger" data-confirm-title="Delete template?" data-confirm-message="This will permanently delete the template and all its commands. This cannot be undone." data-confirm-btn="Delete Template" class="rounded-lg border border-[#EF4444]/30 bg-[#EF4444]/8 px-2.5 py-1 text-[11px] font-semibold text-[#EF4444] transition hover:bg-[#EF4444]/15">Delete</button></form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl" style="background: rgba(139,92,246,0.08);">
                                            <svg class="h-5 w-5 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>
                                        </div>
                                        <p class="text-sm font-semibold text-[#A1A1AA]">No templates yet</p>
                                        <a href="{{ route('admin.templates.create') }}" class="text-xs text-[#8B5CF6] transition hover:text-[#A855F7]">Create your first template →</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $templates->links() }}
    </div>
</x-admin-layout>
