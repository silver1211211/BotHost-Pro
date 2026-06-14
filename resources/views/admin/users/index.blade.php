<x-admin-layout title="Users" subtitle="Manage platform users, roles, plans, and account status.">

<style>
/* Custom select arrow — removes browser default, adds clean chevron */
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
<div class="mb-5">
    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#8B5CF6]">Management</p>
    <h2 class="mt-1 text-2xl font-black text-white">All Users</h2>
    <p class="mt-0.5 text-sm text-[#94A3B8]">{{ $users->total() }} registered accounts</p>
</div>

{{-- Filter / Search Bar --}}
<form method="GET" action="{{ route('admin.users.index') }}" class="mb-5">
    <div class="rounded-2xl border border-[#27213D] p-3" style="background: #0F0D1A;">
        {{-- Search --}}
        <div class="flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2 mb-3">
            <svg class="h-4 w-4 shrink-0 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                placeholder="Search name, email, username..."
                class="flex-1 bg-transparent text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none" />
        </div>
        {{-- Filters row --}}
        <div class="flex flex-wrap gap-2">
            @php
                $uRoleVal   = $filters['role']   ?? '';
                $uStatusVal = $filters['status'] ?? '';
                $uPlanVal   = $filters['plan']   ?? '';
                $uSortVal   = $filters['sort']   ?? 'newest';
            @endphp
            {{-- Role --}}
            <div class="relative" x-data="{ open: false, val: '{{ $uRoleVal }}', labels: { '': 'All Roles', 'user': 'User', 'admin': 'Admin' }, get label() { return this.labels[this.val] ?? 'All Roles' } }" @click.away="open = false">
                <input type="hidden" name="role" :value="val">
                <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl border bg-[#11101C] px-3 py-2 text-xs text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                    <span x-text="label"></span>
                    <svg class="h-3 w-3 shrink-0 text-[#94A3B8] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 min-w-[130px] overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    @foreach (['' => 'All Roles', 'user' => 'User', 'admin' => 'Admin'] as $rv => $rl)
                    <button type="button" @click="val = '{{ $rv }}'; open = false" class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-xs transition hover:bg-[#1D1930]" :class="val === '{{ $rv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $rv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3 w-3 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $rl }}
                    </button>
                    @endforeach
                </div>
            </div>
            {{-- Status --}}
            <div class="relative" x-data="{ open: false, val: '{{ $uStatusVal }}', labels: { '': 'All Statuses', 'active': 'Active', 'suspended': 'Suspended', 'banned': 'Banned' }, get label() { return this.labels[this.val] ?? 'All Statuses' } }" @click.away="open = false">
                <input type="hidden" name="status" :value="val">
                <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl border bg-[#11101C] px-3 py-2 text-xs text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                    <span x-text="label"></span>
                    <svg class="h-3 w-3 shrink-0 text-[#94A3B8] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 min-w-[140px] overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    @foreach (['' => 'All Statuses', 'active' => 'Active', 'suspended' => 'Suspended', 'banned' => 'Banned'] as $sv => $sl)
                    <button type="button" @click="val = '{{ $sv }}'; open = false" class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-xs transition hover:bg-[#1D1930]" :class="val === '{{ $sv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $sv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3 w-3 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $sl }}
                    </button>
                    @endforeach
                </div>
            </div>
            {{-- Plan --}}
            <div class="relative" x-data="{ open: false, val: '{{ $uPlanVal }}', labels: { '': 'All Plans', 'free': 'Free', 'pro': 'Pro', 'business': 'Business' }, get label() { return this.labels[this.val] ?? 'All Plans' } }" @click.away="open = false">
                <input type="hidden" name="plan" :value="val">
                <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl border bg-[#11101C] px-3 py-2 text-xs text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                    <span x-text="label"></span>
                    <svg class="h-3 w-3 shrink-0 text-[#94A3B8] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 min-w-[130px] overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    @foreach (['' => 'All Plans', 'free' => 'Free', 'pro' => 'Pro', 'business' => 'Business'] as $pv => $pl)
                    <button type="button" @click="val = '{{ $pv }}'; open = false" class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-xs transition hover:bg-[#1D1930]" :class="val === '{{ $pv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $pv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3 w-3 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $pl }}
                    </button>
                    @endforeach
                </div>
            </div>
            {{-- Sort --}}
            <div class="relative" x-data="{ open: false, val: '{{ $uSortVal }}', labels: { 'newest': 'Newest first', 'oldest': 'Oldest first', 'most_bots': 'Most bots' }, get label() { return this.labels[this.val] ?? 'Newest first' } }" @click.away="open = false">
                <input type="hidden" name="sort" :value="val">
                <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl border bg-[#11101C] px-3 py-2 text-xs text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                    <span x-text="label"></span>
                    <svg class="h-3 w-3 shrink-0 text-[#94A3B8] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 min-w-[150px] overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    @foreach (['newest' => 'Newest first', 'oldest' => 'Oldest first', 'most_bots' => 'Most bots'] as $sortv => $sortl)
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
                <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-[#27213D] px-4 py-2 text-xs font-bold text-[#94A3B8] transition hover:text-white">
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
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">User</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Role</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Status</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Plan</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Total Spent</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Bots</th>
                    <th class="px-4 py-3.5 text-left text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Joined</th>
                    <th class="px-4 py-3.5 text-right text-[10px] font-black uppercase tracking-[0.18em] text-[#3D3658]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#1B172B]">
                @forelse ($users as $user)
                    @php
                        $roleStyle = match($user->role) {
                            'admin' => ['bg' => 'rgba(139,92,246,0.15)', 'color' => '#A855F7'],
                            default => ['bg' => 'rgba(56,189,248,0.08)', 'color' => '#38BDF8'],
                        };
                        $statusStyle = match($user->status) {
                            'active'    => ['bg' => 'rgba(34,197,94,0.1)',   'color' => '#22C55E', 'dot' => '#22C55E'],
                            'suspended' => ['bg' => 'rgba(245,158,11,0.1)',  'color' => '#F59E0B', 'dot' => '#F59E0B'],
                            'banned'    => ['bg' => 'rgba(239,68,68,0.1)',   'color' => '#EF4444', 'dot' => '#EF4444'],
                            default     => ['bg' => 'rgba(113,113,122,0.1)', 'color' => '#71717A', 'dot' => '#71717A'],
                        };
                        $planStyle = match($user->subscription_plan) {
                            'business' => ['bg' => 'rgba(56,189,248,0.1)',   'color' => '#38BDF8'],
                            'pro'      => ['bg' => 'rgba(139,92,246,0.12)',  'color' => '#A855F7'],
                            default    => ['bg' => 'rgba(113,113,122,0.08)', 'color' => '#71717A'],
                        };
                        $avatarLetter = strtoupper(substr($user->name ?? $user->username ?? 'U', 0, 1));
                    @endphp
                    <tr class="group transition-colors hover:bg-[#151225]">

                        {{-- User --}}
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-black text-white" style="background: linear-gradient(135deg, #27213D, #3D3658);">
                                    {{ $avatarLetter }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-[#94A3B8]">{{ $user->email }}</p>
                                    @if ($user->username)
                                        <p class="truncate text-[10px] text-[#3D3658]">{{ $user->username }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Role --}}
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider" style="background: {{ $roleStyle['bg'] }}; color: {{ $roleStyle['color'] }};">
                                @if ($user->role === 'admin')
                                    <svg class="mr-1 h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                @endif
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider" style="background: {{ $statusStyle['bg'] }}; color: {{ $statusStyle['color'] }};">
                                <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $statusStyle['dot'] }};"></span>
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>

                        {{-- Plan --}}
                        <td class="px-4 py-3.5">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider" style="background: {{ $planStyle['bg'] }}; color: {{ $planStyle['color'] }};">
                                {{ ucfirst($user->subscription_plan) }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-xs font-bold text-[#22C55E]">
                            ${{ number_format((float) ($user->total_spent ?? 0), 2) }}
                        </td>

                        {{-- Bots --}}
                        <td class="px-4 py-3.5">
                            <span class="text-sm font-bold text-white">{{ $user->bots_count ?? 0 }}</span>
                        </td>

                        {{-- Joined --}}
                        <td class="px-4 py-3.5">
                            <p class="text-xs text-[#A1A1AA]">{{ optional($user->created_at)->format('M d, Y') }}</p>
                            <p class="text-[10px] text-[#94A3B8]">{{ optional($user->created_at)->diffForHumans() }}</p>
                        </td>

                        {{-- Actions — compact dropdown --}}
                        <td class="px-4 py-3.5 text-right">
                            <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open"
                                    class="inline-flex items-center gap-1 rounded-lg border border-[#27213D] px-2.5 py-1.5 text-xs font-semibold text-[#A1A1AA] transition hover:border-[#8B5CF6]/50 hover:text-white">
                                    Actions
                                    <svg class="h-3 w-3 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>

                                {{-- Compact dropdown menu --}}
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute right-0 top-full z-50 mt-1 w-44 origin-top-right overflow-hidden rounded-xl border border-[#27213D]"
                                     style="display:none; background: #0F0D1A; box-shadow: 0 12px 40px rgba(0,0,0,0.55);">

                                    {{-- Role --}}
                                    @if ($user->role !== 'admin')
                                        <form method="POST" action="{{ route('admin.users.role', $user) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="role" value="admin">
                                            <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-[#A1A1AA] transition hover:bg-[#151225] hover:text-[#A855F7]">
                                                <svg class="h-3 w-3 shrink-0 text-[#8B5CF6]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                                Make Admin
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.role', $user) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="role" value="user">
                                            <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-[#A1A1AA] transition hover:bg-[#151225] hover:text-[#F59E0B]">
                                                <svg class="h-3 w-3 shrink-0 text-[#F59E0B]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                Remove Admin
                                            </button>
                                        </form>
                                    @endif

                                    <div class="my-0.5 border-t border-[#1B172B]"></div>

                                    {{-- Plans --}}
                                    @foreach (['free' => '#71717A', 'pro' => '#A855F7', 'business' => '#38BDF8'] as $plan => $col)
                                        @if ($user->subscription_plan !== $plan)
                                            <form method="POST" action="{{ route('admin.users.plan', $user) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="subscription_plan" value="{{ $plan }}">
                                                <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-[#A1A1AA] transition hover:bg-[#151225] hover:text-white">
                                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background: {{ $col }};"></span>
                                                    Set {{ ucfirst($plan) }}
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach

                                    <div class="my-0.5 border-t border-[#1B172B]"></div>

                                    {{-- Activate --}}
                                    @if ($user->status !== 'active')
                                        <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-[#A1A1AA] transition hover:bg-[#151225] hover:text-[#22C55E]">
                                                <svg class="h-3 w-3 shrink-0 text-[#22C55E]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                Activate
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Suspend form --}}
                                    @if ($user->status !== 'suspended')
                                        <div x-data="{ suspOpen: false, type: 'timed' }" class="border-t border-[#1B172B]">
                                            <button type="button" @click="suspOpen = !suspOpen"
                                                    class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-xs text-[#A1A1AA] transition hover:bg-[#151225] hover:text-[#F59E0B]">
                                                <span class="flex items-center gap-2">
                                                    <svg class="h-3 w-3 shrink-0 text-[#F59E0B]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" /></svg>
                                                    Suspend
                                                </span>
                                                <svg class="h-3 w-3 transition-transform" :class="suspOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                            </button>
                                            <div x-show="suspOpen" x-cloak class="border-t border-[#1B172B] bg-[#090713] px-3 py-3 space-y-2">
                                                <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="space-y-2">
                                                    @csrf
                                                    {{-- Type toggle --}}
                                                    <div class="flex rounded-lg border border-[#27213D] overflow-hidden text-[10px] font-bold">
                                                        <label class="flex-1 text-center cursor-pointer py-1 transition" :class="type==='timed' ? 'bg-[#27213D] text-white' : 'text-[#94A3B8]'">
                                                            <input type="radio" name="suspend_type" value="timed" x-model="type" class="sr-only"> Timed
                                                        </label>
                                                        <label class="flex-1 text-center cursor-pointer py-1 transition border-l border-[#27213D]" :class="type==='support' ? 'bg-[#27213D] text-white' : 'text-[#94A3B8]'">
                                                            <input type="radio" name="suspend_type" value="support" x-model="type" class="sr-only"> Until Support
                                                        </label>
                                                    </div>
                                                    {{-- Days (timed only) --}}
                                                    <div x-show="type === 'timed'">
                                                        <input name="days" type="number" min="1" max="3650" placeholder="Days (e.g. 7)"
                                                               class="w-full rounded border border-[#27213D] bg-[#11101C] px-2 py-1.5 text-xs text-white placeholder:text-[#4D4868]">
                                                    </div>
                                                    {{-- Message --}}
                                                    <textarea name="message" rows="2" placeholder="Message shown to user (optional)"
                                                              class="w-full resize-none rounded border border-[#27213D] bg-[#11101C] px-2 py-1.5 text-xs text-white placeholder:text-[#4D4868]"></textarea>
                                                    {{-- CTA --}}
                                                    <input name="cta_label" type="text" placeholder="CTA button label (optional)"
                                                           class="w-full rounded border border-[#27213D] bg-[#11101C] px-2 py-1.5 text-xs text-white placeholder:text-[#4D4868]">
                                                    <input name="cta_url" type="url" placeholder="CTA button URL (optional)"
                                                           class="w-full rounded border border-[#27213D] bg-[#11101C] px-2 py-1.5 text-xs text-white placeholder:text-[#4D4868]">
                                                    <button type="submit"
                                                            class="w-full rounded border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-2 py-1.5 text-xs font-bold text-[#F59E0B] transition hover:bg-[#F59E0B]/20">
                                                        Apply Suspension
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Permanent ban --}}
                                    @if ($user->status !== 'banned')
                                        <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="banned">
                                            <button
                                                type="submit"
                                                data-confirm
                                                data-confirm-type="danger"
                                                data-confirm-title="Permanently ban user?"
                                                data-confirm-message="&quot;{{ addslashes($user->name) }}&quot; will be permanently banned and will not be able to access the platform."
                                                data-confirm-btn="Ban User"
                                                class="flex w-full items-center gap-2 border-t border-[#1B172B] px-3 py-2 text-left text-xs text-[#EF4444] transition hover:bg-[#EF4444]/10"
                                            >
                                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                Permanent Ban
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl" style="background: rgba(139,92,246,0.08);">
                                    <svg class="h-6 w-6 text-[#8B5CF6]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-base font-black text-white">No users found</p>
                                <p class="mt-1 text-sm text-[#94A3B8]">
                                    @if (array_filter($filters ?? []))
                                        Try adjusting your filters.
                                    @else
                                        Users will appear here once they register.
                                    @endif
                                </p>
                                @if (array_filter($filters ?? []))
                                    <a href="{{ route('admin.users.index') }}" class="mt-3 rounded-xl border border-[#27213D] px-4 py-2 text-xs font-bold text-[#A1A1AA] transition hover:text-white">
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
@if ($users->hasPages())
    <div class="mt-4 flex items-center justify-between">
        <p class="text-xs text-[#94A3B8]">
            Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}
        </p>
        <div class="flex items-center gap-1">
            @if ($users->onFirstPage())
                <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#1B172B] text-[#3D3658]">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </span>
            @else
                <a href="{{ $users->previousPageUrl() }}" class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#27213D] text-[#A1A1AA] transition hover:border-[#8B5CF6]/50 hover:text-white">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </a>
            @endif

            @foreach ($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                <a href="{{ $url }}"
                   class="flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-bold transition {{ $page == $users->currentPage() ? 'border-[#8B5CF6] text-[#A855F7]' : 'border-[#27213D] text-[#A1A1AA] hover:text-white' }}"
                   style="{{ $page == $users->currentPage() ? 'background: rgba(139,92,246,0.12);' : '' }}">
                    {{ $page }}
                </a>
            @endforeach

            @if ($users->hasMorePages())
                <a href="{{ $users->nextPageUrl() }}" class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#27213D] text-[#A1A1AA] transition hover:border-[#8B5CF6]/50 hover:text-white">
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
