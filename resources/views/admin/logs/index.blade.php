<x-admin-layout title="Admin Logs">
    @php
        $categoryColors = [
            'security' => 'text-[#EF4444] bg-[#EF4444]/10 border-[#EF4444]/20',
            'bot'      => 'text-[#8B5CF6] bg-[#8B5CF6]/10 border-[#8B5CF6]/20',
            'payment'  => 'text-[#22C55E] bg-[#22C55E]/10 border-[#22C55E]/20',
            'template' => 'text-[#38BDF8] bg-[#38BDF8]/10 border-[#38BDF8]/20',
            'system'   => 'text-[#94A3B8] bg-[#27213D]/60 border-[#27213D]',
        ];
        $statusColors = [
            'success' => 'text-[#22C55E] bg-[#22C55E]/10 border-[#22C55E]/20',
            'failed'  => 'text-[#EF4444] bg-[#EF4444]/10 border-[#EF4444]/20',
            'warning' => 'text-[#F59E0B] bg-[#F59E0B]/10 border-[#F59E0B]/20',
            'info'    => 'text-[#38BDF8] bg-[#38BDF8]/10 border-[#38BDF8]/20',
        ];
        $tabs = [
            ''         => 'All',
            'security' => 'Security',
            'bot'      => 'Bot',
            'payment'  => 'Payment',
            'system'   => 'System',
        ];
        $activeCategory = $filters['category'] ?? '';
    @endphp

    <div
        x-data="{
            log: null,
            open: false,
            openDetail(el) { this.log = JSON.parse(el.dataset.log); this.open = true; },
            closeDetail() { this.open = false; setTimeout(() => this.log = null, 200); }
        }"
        class="space-y-6"
    >
        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-[#F8FAFC]">Audit Logs</h1>
                <p class="mt-1 text-sm text-[#94A3B8]">Platform-wide activity, security events, and system actions.</p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#8B5CF6]/10 text-[#8B5CF6]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-[#F8FAFC]">{{ number_format($summary['audit_logs_today']) }}</p>
                        <p class="text-xs text-[#94A3B8]">Logs Today</p>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-[#EF4444]/20 bg-[#0F0D1A] p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EF4444]/10 text-[#EF4444]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-[#F8FAFC]">{{ number_format($summary['failed_login_24h']) }}</p>
                        <p class="text-xs text-[#94A3B8]">Failed Logins (24h)</p>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#38BDF8]/10 text-[#38BDF8]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-[#F8FAFC]">{{ number_format($summary['admin_users']) }}</p>
                        <p class="text-xs text-[#94A3B8]">Admin Users</p>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-[#EF4444]/20 bg-[#0F0D1A] p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EF4444]/10 text-[#EF4444]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-[#F8FAFC]">{{ number_format($summary['security_events']) }}</p>
                        <p class="text-xs text-[#94A3B8]">Security Events</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search + Filter --}}
        <form method="GET" action="" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="relative lg:col-span-2">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search action, description..."
                        class="w-full rounded-xl border border-[#27213D] bg-[#151225] py-2.5 pl-9 pr-4 text-sm text-[#F8FAFC] placeholder-[#71717A] focus:border-[#8B5CF6]/50 focus:outline-none focus:ring-1 focus:ring-[#8B5CF6]/50"
                    >
                </div>
                <div class="relative" x-data="{ open: false, val: '{{ $filters['status'] ?? '' }}', labels: { '': 'All Statuses', 'success': 'Success', 'failed': 'Failed', 'warning': 'Warning', 'info': 'Info' }, get label() { return this.labels[this.val] ?? 'All Statuses' } }" @click.away="open = false">
                    <input type="hidden" name="status" :value="val">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border bg-[#151225] px-3 py-2.5 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/50' : 'border-[#27213D]'">
                        <span x-text="label"></span>
                        <svg class="ml-2 h-4 w-4 shrink-0 text-[#94A3B8] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                        @foreach (['' => 'All Statuses', 'success' => 'Success', 'failed' => 'Failed', 'warning' => 'Warning', 'info' => 'Info'] as $sv => $sl)
                        <button type="button" @click="val = '{{ $sv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $sv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                            <svg :class="val === '{{ $sv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            {{ $sl }}
                        </button>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-2">
                    <input
                        type="date"
                        name="date_from"
                        value="{{ $filters['date_from'] ?? '' }}"
                        title="From date"
                        class="w-full rounded-xl border border-[#27213D] bg-[#151225] px-3 py-2.5 text-sm text-[#F8FAFC] focus:border-[#8B5CF6]/50 focus:outline-none focus:ring-1 focus:ring-[#8B5CF6]/50"
                    >
                </div>
            </div>
            @if ($activeCategory)
                <input type="hidden" name="category" value="{{ $activeCategory }}">
            @endif
            <div class="mt-3 flex gap-2">
                <button type="submit" class="rounded-xl bg-[#8B5CF6] px-5 py-2 text-sm font-bold text-white transition hover:bg-[#7C3AED]">Apply</button>
                <a href="{{ request()->url() }}" class="rounded-xl border border-[#27213D] bg-[#151225] px-5 py-2 text-sm font-bold text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-[#F8FAFC]">Reset</a>
            </div>
        </form>

        {{-- Category Tabs --}}
        <div class="flex gap-1 overflow-x-auto rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-1">
            @foreach ($tabs as $cat => $label)
                @php
                    $isActive  = $activeCategory === $cat;
                    $tabQuery  = $cat !== '' ? array_merge($filters, ['category' => $cat]) : array_diff_key($filters, ['category' => '']);
                    $tabUrl    = request()->url() . '?' . http_build_query(array_filter($tabQuery));
                @endphp
                <a
                    href="{{ $tabUrl }}"
                    class="flex-shrink-0 rounded-xl px-4 py-2 text-sm font-semibold transition
                        {{ $isActive
                            ? 'bg-[#8B5CF6] text-white shadow-sm'
                            : 'text-[#94A3B8] hover:bg-[#151225] hover:text-[#F8FAFC]' }}"
                >{{ $label }}</a>
            @endforeach
        </div>

        {{-- Log Table (Desktop) --}}
        <div class="hidden overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A] lg:block">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="border-b border-[#27213D]">
                        <tr class="text-xs uppercase tracking-wide text-[#94A3B8]">
                            <th class="px-5 py-3.5 text-left">When</th>
                            <th class="px-5 py-3.5 text-left">Category</th>
                            <th class="px-5 py-3.5 text-left">Action</th>
                            <th class="px-5 py-3.5 text-left">Status</th>
                            <th class="px-5 py-3.5 text-left">Actor</th>
                            <th class="px-5 py-3.5 text-left">IP</th>
                            <th class="px-5 py-3.5 text-left"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1B172B]">
                        @forelse ($logs as $log)
                            @php
                                $catClass    = $categoryColors[$log->category] ?? $categoryColors['system'];
                                $statusClass = $statusColors[$log->status] ?? 'text-[#94A3B8] bg-[#27213D]/60 border-[#27213D]';
                                $safeLog = [
                                    'action'      => $log->action,
                                    'category'    => $log->category,
                                    'description' => $log->description,
                                    'status'      => $log->status,
                                    'ip_address'  => $log->ip_address,
                                    'user_agent'  => $log->user_agent,
                                    'actor'       => $log->actor?->email ?? 'System',
                                    'created_at'  => $log->created_at?->format('M j, Y H:i:s'),
                                    'target'      => $log->target_type ? class_basename($log->target_type).' #'.$log->target_id : null,
                                    'metadata'    => app(\App\Services\AuditLogService::class)->safeMetadata($log->metadata),
                                ];
                            @endphp
                            <tr class="transition hover:bg-[#151225]/60">
                                <td class="whitespace-nowrap px-5 py-3.5 text-xs text-[#94A3B8]">
                                    {{ $log->created_at?->format('M j, Y') }}<br>
                                    <span class="text-[#4B4565]">{{ $log->created_at?->format('H:i:s') }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center rounded-lg border px-2 py-0.5 text-xs font-semibold {{ $catClass }}">
                                        {{ ucfirst($log->category ?? 'system') }}
                                    </span>
                                </td>
                                <td class="max-w-[200px] px-5 py-3.5">
                                    <span class="block truncate font-mono text-xs text-[#F8FAFC]">{{ $log->action }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if ($log->status)
                                        <span class="inline-flex items-center rounded-lg border px-2 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    @else
                                        <span class="text-[#4B4565]">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-xs text-[#A1A1AA]">
                                    {{ $log->actor?->email ?? 'System' }}
                                </td>
                                <td class="px-5 py-3.5 font-mono text-xs text-[#94A3B8]">
                                    {{ $log->ip_address ?? '—' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <button
                                        type="button"
                                        data-log="{{ json_encode($safeLog) }}"
                                        @click="openDetail($el)"
                                        class="rounded-lg border border-[#27213D] bg-[#151225] px-3 py-1 text-xs text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-[#8B5CF6]"
                                    >Details</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-[#151225] text-[#4B4565]">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </div>
                                    <p class="mt-3 text-sm text-[#94A3B8]">No logs match your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="border-t border-[#27213D] px-5 py-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        {{-- Log Cards (Mobile) --}}
        <div class="space-y-3 lg:hidden">
            @forelse ($logs as $log)
                @php
                    $catClass    = $categoryColors[$log->category] ?? $categoryColors['system'];
                    $statusClass = $statusColors[$log->status] ?? 'text-[#94A3B8] bg-[#27213D]/60 border-[#27213D]';
                    $safeLog = [
                        'action'      => $log->action,
                        'category'    => $log->category,
                        'description' => $log->description,
                        'status'      => $log->status,
                        'ip_address'  => $log->ip_address,
                        'user_agent'  => $log->user_agent,
                        'actor'       => $log->actor?->email ?? 'System',
                        'created_at'  => $log->created_at?->format('M j, Y H:i:s'),
                        'target'      => $log->target_type ? class_basename($log->target_type).' #'.$log->target_id : null,
                        'metadata'    => app(\App\Services\AuditLogService::class)->safeMetadata($log->metadata),
                    ];
                @endphp
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-lg border px-2 py-0.5 text-xs font-semibold {{ $catClass }}">
                                    {{ ucfirst($log->category ?? 'system') }}
                                </span>
                                @if ($log->status)
                                    <span class="inline-flex items-center rounded-lg border px-2 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-2 truncate font-mono text-xs text-[#F8FAFC]">{{ $log->action }}</p>
                            @if ($log->description)
                                <p class="mt-1 line-clamp-2 text-xs text-[#94A3B8]">{{ $log->description }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-[#4B4565]">
                                <span>{{ $log->created_at?->diffForHumans() }}</span>
                                <span>{{ $log->actor?->email ?? 'System' }}</span>
                                @if ($log->ip_address)
                                    <span class="font-mono">{{ $log->ip_address }}</span>
                                @endif
                            </div>
                        </div>
                        <button
                            type="button"
                            data-log="{{ json_encode($safeLog) }}"
                            @click="openDetail($el)"
                            class="flex-shrink-0 rounded-lg border border-[#27213D] bg-[#151225] px-3 py-1.5 text-xs text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-[#8B5CF6]"
                        >Details</button>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] px-5 py-14 text-center">
                    <p class="text-sm text-[#94A3B8]">No logs match your filters.</p>
                </div>
            @endforelse

            @if ($logs->hasPages())
                <div class="pt-2">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        {{-- Platform Status Bar --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="flex items-center gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl {{ $summary['maintenance_mode'] ? 'bg-[#F59E0B]/10 text-[#F59E0B]' : 'bg-[#22C55E]/10 text-[#22C55E]' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-[#94A3B8]">Platform Mode</p>
                    <p class="text-sm font-bold {{ $summary['maintenance_mode'] ? 'text-[#F59E0B]' : 'text-[#22C55E]' }}">
                        {{ $summary['maintenance_mode'] ? 'Maintenance' : 'Live' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl {{ $summary['registration_enabled'] ? 'bg-[#22C55E]/10 text-[#22C55E]' : 'bg-[#EF4444]/10 text-[#EF4444]' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-[#94A3B8]">Registrations</p>
                    <p class="text-sm font-bold {{ $summary['registration_enabled'] ? 'text-[#22C55E]' : 'text-[#EF4444]' }}">
                        {{ $summary['registration_enabled'] ? 'Open' : 'Closed' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-[#38BDF8]/10 text-[#38BDF8]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-[#94A3B8]">Last Webhook Reset</p>
                    <p class="text-sm font-bold text-[#A1A1AA]">
                        {{ $summary['last_webhook_reset']?->created_at?->diffForHumans() ?? 'Never' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Log Detail Modal --}}
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 backdrop-blur-sm sm:items-center sm:p-4"
            @click.self="closeDetail()"
            @keydown.escape.window="closeDetail()"
        >
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="w-full max-w-lg rounded-t-2xl border border-[#27213D] bg-[#0F0D1A] sm:rounded-2xl"
            >
                <div class="flex items-center justify-between border-b border-[#27213D] px-5 py-4">
                    <h3 class="text-sm font-bold text-[#F8FAFC]">Log Detail</h3>
                    <button @click="closeDetail()" class="rounded-lg p-1 text-[#94A3B8] transition hover:bg-[#151225] hover:text-[#F8FAFC]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-3 p-5" x-show="log">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-[#151225] p-3">
                            <p class="text-xs text-[#94A3B8]">Category</p>
                            <p class="mt-1 text-sm font-semibold text-[#F8FAFC]" x-text="log?.category ? log.category.charAt(0).toUpperCase() + log.category.slice(1) : '—'"></p>
                        </div>
                        <div class="rounded-xl bg-[#151225] p-3">
                            <p class="text-xs text-[#94A3B8]">Status</p>
                            <p class="mt-1 text-sm font-semibold text-[#F8FAFC]" x-text="log?.status ? log.status.charAt(0).toUpperCase() + log.status.slice(1) : '—'"></p>
                        </div>
                    </div>
                    <div class="rounded-xl bg-[#151225] p-3">
                        <p class="text-xs text-[#94A3B8]">Action</p>
                        <p class="mt-1 break-all font-mono text-xs text-[#F8FAFC]" x-text="log?.action ?? '—'"></p>
                    </div>
                    <div class="rounded-xl bg-[#151225] p-3">
                        <p class="text-xs text-[#94A3B8]">Description</p>
                        <p class="mt-1 text-sm text-[#A1A1AA]" x-text="log?.description || '—'"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-[#151225] p-3">
                            <p class="text-xs text-[#94A3B8]">Actor</p>
                            <p class="mt-1 truncate text-sm text-[#F8FAFC]" x-text="log?.actor ?? '—'"></p>
                        </div>
                        <div class="rounded-xl bg-[#151225] p-3">
                            <p class="text-xs text-[#94A3B8]">IP Address</p>
                            <p class="mt-1 font-mono text-sm text-[#F8FAFC]" x-text="log?.ip_address || '—'"></p>
                        </div>
                    </div>
                    <div class="rounded-xl bg-[#151225] p-3">
                        <p class="text-xs text-[#94A3B8]">Timestamp</p>
                        <p class="mt-1 text-sm text-[#F8FAFC]" x-text="log?.created_at ?? '—'"></p>
                    </div>
                    <div class="rounded-xl bg-[#151225] p-3">
                        <p class="text-xs text-[#94A3B8]">User Agent</p>
                        <p class="mt-1 break-all text-xs text-[#94A3B8]" x-text="log?.user_agent || '—'"></p>
                    </div>
                </div>
                <div class="border-t border-[#27213D] px-5 py-4">
                    <button @click="closeDetail()" class="w-full rounded-xl border border-[#27213D] bg-[#151225] py-2.5 text-sm font-semibold text-[#A1A1AA] transition hover:bg-[#1B172B] hover:text-[#F8FAFC]">Close</button>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
