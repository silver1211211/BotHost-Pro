<x-admin-layout title="Security" subtitle="Platform security overview and access controls">
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-[#F8FAFC]">Security Overview</h1>
            <p class="mt-0.5 text-xs text-[#94A3B8]">Platform-wide security status and access controls.</p>
        </div>
        <a href="{{ route('admin.settings.index', ['tab' => 'security']) }}" class="inline-flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5 text-sm font-black text-[#A1A1AA] transition hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            Security Settings
        </a>
    </div>

    {{-- Stat Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['Admin Users',        $summary['admin_users'],        '#8B5CF6', 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
            ['Failed Logins 24h',  $summary['failed_login_24h'],   '#EF4444', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
            ['Audit Logs Today',   $summary['audit_logs_today'],   '#38BDF8', 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z'],
            ['Security Events',    $summary['security_events'],    '#F59E0B', 'M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88'],
        ] as [$label, $value, $color, $path])
        <div class="group rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 transition hover:border-[{{ $color }}]/25">
            <div class="mb-3 flex items-start justify-between gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl" style="background: {{ $color }}18;">
                    <svg class="h-4.5 w-4.5" style="width:1.125rem;height:1.125rem;color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-black text-[#F8FAFC] sm:text-3xl">{{ $value }}</p>
            <p class="mt-0.5 text-xs font-bold" style="color: {{ $color }};">{{ $label }}</p>
        </div>
        @endforeach
    </div>

    {{-- Platform State --}}
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
        <h3 class="mb-4 text-xs font-black uppercase tracking-widest text-[#94A3B8]">Platform State</h3>
        <div class="grid gap-3 sm:grid-cols-3">

            {{-- Maintenance Mode --}}
            <div class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#11101C] p-4">
                <div class="h-2.5 w-2.5 shrink-0 rounded-full {{ $summary['maintenance_mode'] ? 'bg-[#F59E0B] shadow-[0_0_6px_rgba(245,158,11,0.6)]' : 'bg-[#22C55E] shadow-[0_0_6px_rgba(34,197,94,0.5)]' }}"></div>
                <div class="min-w-0">
                    <p class="text-xs font-black text-[#A1A1AA]">Maintenance Mode</p>
                    <p class="mt-0.5 text-[11px] font-semibold {{ $summary['maintenance_mode'] ? 'text-[#F59E0B]' : 'text-[#22C55E]' }}">
                        {{ $summary['maintenance_mode'] ? 'Active — users see maintenance page' : 'Off — platform is live' }}
                    </p>
                </div>
            </div>

            {{-- Registration --}}
            <div class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#11101C] p-4">
                <div class="h-2.5 w-2.5 shrink-0 rounded-full {{ $summary['registration_enabled'] ? 'bg-[#22C55E] shadow-[0_0_6px_rgba(34,197,94,0.5)]' : 'bg-[#EF4444] shadow-[0_0_6px_rgba(239,68,68,0.5)]' }}"></div>
                <div class="min-w-0">
                    <p class="text-xs font-black text-[#A1A1AA]">Registration</p>
                    <p class="mt-0.5 text-[11px] font-semibold {{ $summary['registration_enabled'] ? 'text-[#22C55E]' : 'text-[#EF4444]' }}">
                        {{ $summary['registration_enabled'] ? 'Open — new signups allowed' : 'Closed — no new signups' }}
                    </p>
                </div>
            </div>

            {{-- Last Webhook Reset --}}
            <div class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#11101C] p-4">
                <div class="h-2.5 w-2.5 shrink-0 rounded-full bg-[#38BDF8] shadow-[0_0_6px_rgba(56,189,248,0.4)]"></div>
                <div class="min-w-0">
                    <p class="text-xs font-black text-[#A1A1AA]">Last Webhook Reset</p>
                    <p class="mt-0.5 text-[11px] font-semibold text-[#94A3B8]">
                        {{ $summary['last_webhook_reset']?->created_at?->format('M j, Y H:i') ?? 'Never' }}
                    </p>
                </div>
            </div>

        </div>
    </div>

    {{-- Quick Links --}}
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
        <h3 class="mb-4 text-xs font-black uppercase tracking-widest text-[#94A3B8]">Security Controls</h3>
        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('admin.settings.index', ['tab' => 'security']) }}"
               class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#11101C] p-4 transition hover:border-[#8B5CF6]/30 hover:bg-[#151225]">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#8B5CF6]/12 text-[#8B5CF6]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-black text-[#F8FAFC]">Auth & Access</p>
                    <p class="text-[11px] text-[#94A3B8]">Email verification, login attempts, registration toggle</p>
                </div>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'maintenance']) }}"
               class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#11101C] p-4 transition hover:border-[#F59E0B]/30 hover:bg-[#151225]">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#F59E0B]/10 text-[#F59E0B]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-black text-[#F8FAFC]">Maintenance Mode</p>
                    <p class="text-[11px] text-[#94A3B8]">Toggle platform access, IP allowlist, webhook reset</p>
                </div>
            </a>
            <a href="{{ route('admin.users.index') }}"
               class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#11101C] p-4 transition hover:border-[#38BDF8]/30 hover:bg-[#151225]">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#38BDF8]/10 text-[#38BDF8]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-black text-[#F8FAFC]">User Management</p>
                    <p class="text-[11px] text-[#94A3B8]">Suspend, ban, and manage user accounts</p>
                </div>
            </a>
            <a href="{{ route('admin.logs.index') }}"
               class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#11101C] p-4 transition hover:border-[#EF4444]/30 hover:bg-[#151225]">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#EF4444]/10 text-[#EF4444]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-black text-[#F8FAFC]">Audit Logs</p>
                    <p class="text-[11px] text-[#94A3B8]">View all bot errors, runtime events, and admin actions</p>
                </div>
            </a>
        </div>
    </div>

</div>
</x-admin-layout>
