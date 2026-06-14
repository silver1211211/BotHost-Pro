<x-admin-layout title="Bots" subtitle="Monitor all Telegram bots created across the platform.">

<style>
.admin-select {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='%2371717A'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19.5 8.25-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.6rem center;
    background-size: 14px 14px;
    padding-right: 2rem;
}
.admin-select:focus { border-color: #8B5CF6; outline: none; }
</style>

{{-- Page header --}}
<div class="mb-5 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#38BDF8]">Management</p>
        <h2 class="mt-1 text-2xl font-black text-white">All Bots</h2>
        <p class="mt-0.5 text-sm text-[#94A3B8]">{{ $bots->total() }} bots across the platform</p>
    </div>
    <form method="POST" action="{{ route('admin.bots.set-all-webhooks') }}">
        @csrf
        @if(session('status'))
            <p class="mb-2 text-xs font-semibold text-[#22C55E]">{{ session('status') }}</p>
        @endif
        @error('webhook')
            <p class="mb-2 text-xs font-semibold text-[#EF4444]">{{ $message }}</p>
        @enderror
        <button
            type="submit"
            data-confirm
            data-confirm-type="warning"
            data-confirm-title="Reset webhooks for all bots?"
            data-confirm-message="This will call the Telegram API for every verified bot using the current public callback URL and start successful bots."
            data-confirm-btn="Reset All Webhooks"
            class="inline-flex items-center gap-2 rounded-xl border border-[#8B5CF6]/40 bg-[#8B5CF6]/10 px-4 py-2.5 text-sm font-black text-[#8B5CF6] transition hover:bg-[#8B5CF6]/20"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z"/>
            </svg>
            Set All Webhooks
        </button>
    </form>
</div>

{{-- Filter / Search Bar --}}
<form method="GET" action="{{ route('admin.bots.index') }}" class="mb-5">
    <div class="rounded-2xl border border-[#27213D] p-3" style="background: #0F0D1A;">
        {{-- Search --}}
        <div class="flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2 mb-3">
            <svg class="h-4 w-4 shrink-0 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                placeholder="Search bot name, @username, owner..."
                class="flex-1 bg-transparent text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none" />
        </div>
        {{-- Filters --}}
        @php
            $abStatusVal = $filters['status']     ?? '';
            $abTypeVal   = $filters['setup_type'] ?? '';
            $abVerifVal  = $filters['verified']   ?? '';
            $abSortVal   = $filters['sort']       ?? 'newest';
        @endphp
        <div class="flex flex-wrap gap-2">
            {{-- Status --}}
            <div class="relative" x-data="{ open: false, val: '{{ $abStatusVal }}', labels: { '': 'All Statuses', 'running': 'Running', 'paused': 'Paused', 'stopped': 'Stopped', 'crashed': 'Crashed', 'suspended': 'Suspended' }, get label() { return this.labels[this.val] ?? 'All Statuses' } }" @click.away="open = false">
                <input type="hidden" name="status" :value="val">
                <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl border bg-[#11101C] px-3 py-2 text-xs text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                    <span x-text="label"></span>
                    <svg class="h-3 w-3 shrink-0 text-[#94A3B8] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 min-w-[140px] overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    @foreach (['' => 'All Statuses', 'running' => 'Running', 'paused' => 'Paused', 'stopped' => 'Stopped', 'crashed' => 'Crashed', 'suspended' => 'Suspended'] as $sv => $sl)
                    <button type="button" @click="val = '{{ $sv }}'; open = false" class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-xs transition hover:bg-[#1D1930]" :class="val === '{{ $sv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $sv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3 w-3 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $sl }}
                    </button>
                    @endforeach
                </div>
            </div>
            {{-- Type --}}
            <div class="relative" x-data="{ open: false, val: '{{ $abTypeVal }}', labels: { '': 'All Types', 'custom': 'Custom', 'template': 'Template' }, get label() { return this.labels[this.val] ?? 'All Types' } }" @click.away="open = false">
                <input type="hidden" name="setup_type" :value="val">
                <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl border bg-[#11101C] px-3 py-2 text-xs text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                    <span x-text="label"></span>
                    <svg class="h-3 w-3 shrink-0 text-[#94A3B8] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 min-w-[130px] overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    @foreach (['' => 'All Types', 'custom' => 'Custom', 'template' => 'Template'] as $tv => $tl)
                    <button type="button" @click="val = '{{ $tv }}'; open = false" class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-xs transition hover:bg-[#1D1930]" :class="val === '{{ $tv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $tv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3 w-3 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $tl }}
                    </button>
                    @endforeach
                </div>
            </div>
            {{-- Verified --}}
            <div class="relative" x-data="{ open: false, val: '{{ $abVerifVal }}', labels: { '': 'All Verified', 'yes': 'Verified', 'no': 'Not Verified' }, get label() { return this.labels[this.val] ?? 'All Verified' } }" @click.away="open = false">
                <input type="hidden" name="verified" :value="val">
                <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl border bg-[#11101C] px-3 py-2 text-xs text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                    <span x-text="label"></span>
                    <svg class="h-3 w-3 shrink-0 text-[#94A3B8] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 min-w-[140px] overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    @foreach (['' => 'All Verified', 'yes' => 'Verified', 'no' => 'Not Verified'] as $vv => $vl)
                    <button type="button" @click="val = '{{ $vv }}'; open = false" class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-xs transition hover:bg-[#1D1930]" :class="val === '{{ $vv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $vv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3 w-3 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $vl }}
                    </button>
                    @endforeach
                </div>
            </div>
            {{-- Sort --}}
            <div class="relative" x-data="{ open: false, val: '{{ $abSortVal }}', labels: { 'newest': 'Newest first', 'oldest': 'Oldest first', 'most_commands': 'Most commands', 'most_users': 'Most bot users', 'most_errors': 'Most errors' }, get label() { return this.labels[this.val] ?? 'Newest first' } }" @click.away="open = false">
                <input type="hidden" name="sort" :value="val">
                <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl border bg-[#11101C] px-3 py-2 text-xs text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                    <span x-text="label"></span>
                    <svg class="h-3 w-3 shrink-0 text-[#94A3B8] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 min-w-[160px] overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    @foreach (['newest' => 'Newest first', 'oldest' => 'Oldest first', 'most_commands' => 'Most commands', 'most_users' => 'Most bot users', 'most_errors' => 'Most errors'] as $sortv => $sortl)
                    <button type="button" @click="val = '{{ $sortv }}'; open = false" class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-xs transition hover:bg-[#1D1930]" :class="val === '{{ $sortv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $sortv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3 w-3 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $sortl }}
                    </button>
                    @endforeach
                </div>
            </div>
            <button type="submit" class="rounded-xl px-4 py-2 text-xs font-bold text-white transition hover:opacity-90" style="background: linear-gradient(135deg, #5B21B6, #8B5CF6);">
                Filter
            </button>
            @if (array_filter($filters ?? []))
                <a href="{{ route('admin.bots.index') }}" class="rounded-xl border border-[#27213D] px-4 py-2 text-xs font-bold text-[#94A3B8] transition hover:text-white">
                    Clear
                </a>
            @endif
        </div>
    </div>
</form>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-[#27213D]" style="background: #0F0D1A;">
    <div class="overflow-x-auto">
        <table class="min-w-max w-full text-sm">
            <thead>
                <tr style="background: #090713; border-bottom: 1px solid #27213D;">
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Bot</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Owner</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Status</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Verified</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Cmds</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Users</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Errors</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Created</th>
                    <th class="px-4 py-3.5 text-right text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#1B172B]">
                @forelse ($bots as $bot)
                    @php
                        $statusStyle = match($bot->status) {
                            'running'   => ['bg' => 'rgba(34,197,94,0.1)',   'color' => '#22C55E', 'dot' => '#22C55E'],
                            'paused'    => ['bg' => 'rgba(245,158,11,0.1)',  'color' => '#F59E0B', 'dot' => '#F59E0B'],
                            'stopped'   => ['bg' => 'rgba(113,113,122,0.1)','color' => '#71717A', 'dot' => '#71717A'],
                            'crashed'   => ['bg' => 'rgba(239,68,68,0.1)',   'color' => '#EF4444', 'dot' => '#EF4444'],
                            'suspended' => ['bg' => 'rgba(168,85,247,0.1)',  'color' => '#A855F7', 'dot' => '#A855F7'],
                            default     => ['bg' => 'rgba(113,113,122,0.1)','color' => '#71717A', 'dot' => '#71717A'],
                        };
                        $botLetter  = strtoupper(substr($bot->name ?? $bot->telegram_username ?? 'B', 0, 1));
                        $hasErrors  = ($bot->error_count ?? 0) > 0;
                        $isVerified = !is_null($bot->token_verified_at);
                    @endphp
                    <tr class="group transition-colors hover:bg-[#151225]">

                        {{-- Bot --}}
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-black" style="background: rgba(34,158,217,0.12); color: #229ED9; border: 1px solid rgba(34,158,217,0.2);">
                                    {{ $botLetter }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-white">{{ $bot->name }}</p>
                                    @if ($bot->telegram_username)
                                        <p class="truncate text-xs text-[#229ED9]">{{ '@'.$bot->telegram_username }}</p>
                                    @else
                                        <p class="truncate text-xs text-[#94A3B8]">Not verified</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Owner --}}
                        <td class="px-4 py-3.5">
                            @if ($bot->user)
                                <p class="text-xs font-medium text-white">{{ $bot->user->name }}</p>
                                <p class="text-[10px] text-[#94A3B8]">{{ $bot->user->email }}</p>
                            @else
                                <span class="text-xs text-[#EF4444]">Deleted</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider" style="background: {{ $statusStyle['bg'] }}; color: {{ $statusStyle['color'] }};">
                                <span class="h-1.5 w-1.5 rounded-full {{ $bot->status === 'running' ? 'animate-pulse' : '' }}" style="background: {{ $statusStyle['dot'] }};"></span>
                                {{ ucfirst($bot->status) }}
                            </span>
                        </td>

                        {{-- Verified --}}
                        <td class="px-4 py-3.5">
                            @if ($isVerified)
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider" style="background: rgba(34,197,94,0.1); color: #22C55E;">
                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    Yes
                                </span>
                            @else
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider" style="background: rgba(113,113,122,0.1); color: #71717A;">No</span>
                            @endif
                        </td>

                        {{-- Commands --}}
                        <td class="px-4 py-3.5 text-sm font-bold text-white">{{ number_format($bot->commands_count ?? 0) }}</td>

                        {{-- Bot Users --}}
                        <td class="px-4 py-3.5 text-sm font-bold text-white">{{ number_format($bot->bot_users_count ?? 0) }}</td>

                        {{-- Errors --}}
                        <td class="px-4 py-3.5">
                            @if ($hasErrors)
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-black" style="background: rgba(239,68,68,0.1); color: #EF4444;">
                                    {{ number_format($bot->error_count) }}
                                </span>
                            @else
                                <span class="text-sm text-[#94A3B8]">0</span>
                            @endif
                        </td>

                        {{-- Created --}}
                        <td class="px-4 py-3.5">
                            <p class="text-xs text-[#A1A1AA]">{{ optional($bot->created_at)->format('M d, Y') }}</p>
                            <p class="text-[10px] text-[#94A3B8]">{{ optional($bot->created_at)->diffForHumans() }}</p>
                        </td>

                        {{-- Actions — compact dropdown --}}
                        <td class="px-4 py-3.5 text-right">
                            <div class="relative inline-block text-left" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false"
                                    class="inline-flex items-center gap-1 rounded-lg border border-[#27213D] px-2.5 py-1.5 text-xs font-semibold text-[#A1A1AA] transition hover:border-[#38BDF8]/50 hover:text-white">
                                    Actions
                                    <svg class="h-3 w-3 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute right-0 top-full z-50 mt-1 w-40 origin-top-right overflow-hidden rounded-xl border border-[#27213D]"
                                     style="display:none; background: #0F0D1A; box-shadow: 0 12px 40px rgba(0,0,0,0.55);">

                                    @if ($bot->status !== 'running')
                                        <form method="POST" action="{{ route('admin.bots.status', $bot) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="running">
                                            <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-[#A1A1AA] transition hover:bg-[#151225] hover:text-[#22C55E]">
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#22C55E]"></span> Set Running
                                            </button>
                                        </form>
                                    @endif

                                    @if ($bot->status !== 'paused')
                                        <form method="POST" action="{{ route('admin.bots.status', $bot) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="paused">
                                            <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-[#A1A1AA] transition hover:bg-[#151225] hover:text-[#F59E0B]">
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#F59E0B]"></span> Pause
                                            </button>
                                        </form>
                                    @endif

                                    @if ($bot->status !== 'stopped')
                                        <form method="POST" action="{{ route('admin.bots.status', $bot) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="stopped">
                                            <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-[#A1A1AA] transition hover:bg-[#151225] hover:text-[#94A3B8]">
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#71717A]"></span> Stop
                                            </button>
                                        </form>
                                    @endif

                                    @if ($bot->status !== 'suspended')
                                        <form method="POST" action="{{ route('admin.bots.status', $bot) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="suspended">
                                            <button
                                                type="submit"
                                                data-confirm
                                                data-confirm-type="warning"
                                                data-confirm-title="Suspend bot?"
                                                data-confirm-message="&quot;{{ addslashes($bot->name) }}&quot; will be suspended. The bot will stop responding to users."
                                                data-confirm-btn="Suspend Bot"
                                                class="flex w-full items-center gap-2 border-t border-[#1B172B] px-3 py-2 text-left text-xs text-[#A855F7] transition hover:bg-[#A855F7]/10"
                                            >
                                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                Suspend
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl" style="background: rgba(56,189,248,0.08);">
                                    <svg class="h-6 w-6 text-[#38BDF8]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-base font-black text-white">No bots found</p>
                                <p class="mt-1 text-sm text-[#94A3B8]">
                                    @if (array_filter($filters ?? []))
                                        Try adjusting your filters.
                                    @else
                                        Bots will appear here once users create them.
                                    @endif
                                </p>
                                @if (array_filter($filters ?? []))
                                    <a href="{{ route('admin.bots.index') }}" class="mt-3 rounded-xl border border-[#27213D] px-4 py-2 text-xs font-bold text-[#A1A1AA] transition hover:text-white">
                                        Clear filters
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
@if ($bots->hasPages())
    <div class="mt-4 flex items-center justify-between">
        <p class="text-xs text-[#94A3B8]">
            Showing {{ $bots->firstItem() }}–{{ $bots->lastItem() }} of {{ $bots->total() }}
        </p>
        <div class="flex items-center gap-1">
            @if ($bots->onFirstPage())
                <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#1B172B] text-[#3D3658]">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </span>
            @else
                <a href="{{ $bots->previousPageUrl() }}" class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#27213D] text-[#A1A1AA] transition hover:border-[#38BDF8]/50 hover:text-white">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </a>
            @endif

            @foreach ($bots->getUrlRange(max(1, $bots->currentPage() - 2), min($bots->lastPage(), $bots->currentPage() + 2)) as $page => $url)
                <a href="{{ $url }}"
                   class="flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-bold transition {{ $page == $bots->currentPage() ? 'border-[#38BDF8] text-[#38BDF8]' : 'border-[#27213D] text-[#A1A1AA] hover:text-white' }}"
                   style="{{ $page == $bots->currentPage() ? 'background: rgba(56,189,248,0.1);' : '' }}">
                    {{ $page }}
                </a>
            @endforeach

            @if ($bots->hasMorePages())
                <a href="{{ $bots->nextPageUrl() }}" class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#27213D] text-[#A1A1AA] transition hover:border-[#38BDF8]/50 hover:text-white">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </a>
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#1B172B] text-[#3D3658]">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </span>
            @endif
        </div>
    </div>
@endif

</x-admin-layout>
