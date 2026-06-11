<x-dashboard-layout title="Notifications">

<div
    x-data="{ filter: 'all' }"
    class="space-y-6"
>
@php
    $notifications = $notifications ?? \App\Models\UserNotification::query()
        ->where('user_id', auth()->id())
        ->latest()
        ->paginate(20);
@endphp

    {{-- ── Header ── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-[#F8FAFC]">Notifications</h1>
            <p class="mt-1 text-sm text-[#71717A]">Platform alerts, account updates, and important messages.</p>
        </div>
        <div class="flex items-center gap-2">
            <button
                class="shrink-0 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-4 py-2 text-xs font-black text-[#71717A] transition hover:border-[#8B5CF6]/30 hover:text-[#A1A1AA] disabled:cursor-not-allowed disabled:opacity-40"
                disabled
                title="Notifications are marked read when this page opens"
            >
                All caught up
            </button>
        </div>
    </div>

    {{-- ── Filter tabs ── --}}
    <div class="flex gap-1.5 overflow-x-auto pb-1" style="scrollbar-width:none">
        @foreach([
            ['all',     'All'],
            ['unread',  'Unread'],
            ['system',  'System'],
            ['account', 'Account'],
        ] as [$id, $label])
        <button
            @click="filter = '{{ $id }}'"
            :class="filter === '{{ $id }}'
                ? 'border-[#8B5CF6] bg-[#8B5CF6]/12 text-white'
                : 'border-[#27213D] bg-[#0F0D1A] text-[#71717A] hover:border-[#8B5CF6]/30 hover:text-[#A1A1AA]'"
            class="shrink-0 rounded-xl border px-4 py-2 text-xs font-black transition"
        >{{ $label }}</button>
        @endforeach
    </div>

    {{-- ── Notification list / empty state ── --}}
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">

        @forelse ($notifications as $notification)
            <div
                x-show="filter === 'all' || (filter === 'unread' && '{{ $notification->status }}' === 'unread') || filter === '{{ $notification->type ?: 'system' }}'"
                class="mb-3 rounded-xl border {{ $notification->status === 'unread' ? 'border-[#8B5CF6]/35 bg-[#8B5CF6]/8' : 'border-[#1B172B] bg-[#0B0918]' }} px-4 py-3"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-black text-[#F8FAFC]">{{ $notification->title }}</p>
                            <span class="rounded-full border border-[#27213D] px-2 py-0.5 text-[10px] font-bold text-[#71717A]">{{ ucfirst($notification->priority ?: 'normal') }}</span>
                            @if ($notification->status === 'unread')
                                <span class="rounded-full border border-[#8B5CF6]/30 bg-[#8B5CF6]/10 px-2 py-0.5 text-[10px] font-bold text-[#A855F7]">Unread</span>
                            @endif
                        </div>
                        <p class="mt-1.5 whitespace-pre-line text-sm leading-6 text-[#A1A1AA]">{{ $notification->message }}</p>
                    </div>
                    <p class="shrink-0 text-[10px] text-[#52525B]">{{ $notification->created_at?->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center gap-4 rounded-xl border border-[#1B172B] bg-[#0B0918] py-16 text-center">
                <div class="grid h-14 w-14 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                </div>
                <div>
                    <p class="text-base font-black text-[#71717A]">No notifications yet</p>
                    <p class="mt-1 text-sm text-[#52525B]">Important platform updates and account alerts will appear here.</p>
                </div>
            </div>
        @endforelse

        @if ($notifications->hasPages())
            <div class="mt-4">{{ $notifications->links() }}</div>
        @endif

    </div>

    {{-- ── What you'll receive info ── --}}
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-[#71717A]">Types of Notifications</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['Bot Alerts',     'Runtime pauses, token errors, and status changes for your bots.',    '#8B5CF6', 'M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728M12 9v3l2 2'],
                ['Account',        'Login activity, password changes, and plan updates.',                 '#38BDF8', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['Plan & Billing', 'Subscription renewals, upgrades, and payment confirmations.',        '#F59E0B', 'M13 10V3L4 14h7v7l9-11h-7z'],
                ['Platform',       'New features, maintenance windows, and important announcements.',     '#22C55E', 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6'],
            ] as [$title, $desc, $color, $icon])
            <div class="rounded-xl border border-[#1B172B] bg-[#0B0918] p-4">
                <div class="mb-2.5 h-8 w-8 rounded-xl flex items-center justify-center" style="background-color:{{ $color }}15">
                    <svg style="height:14px;width:14px;color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </div>
                <p class="text-xs font-black text-[#A1A1AA]">{{ $title }}</p>
                <p class="mt-1 text-[11px] leading-relaxed text-[#52525B]">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>

</div>

</x-dashboard-layout>
