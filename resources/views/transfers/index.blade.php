<x-dashboard-layout title="Transfers">

<div
    x-data="{ tab: '{{ request()->query('tab', 'all') }}' }"
    class="space-y-6"
>

    {{-- ── Header ── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-[#F8FAFC]">Transfers</h1>
            <p class="mt-1 text-sm text-[#71717A]">Send and receive bot workspaces securely between accounts.</p>
        </div>
    </div>

    @if(session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/8 px-4 py-3">
            <svg class="h-4 w-4 shrink-0 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            <p class="text-sm font-bold text-[#22C55E]">{{ session('status') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/6 px-4 py-3">
            @foreach($errors->all() as $error)
                <p class="text-xs font-bold text-[#EF4444]">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ── Summary cards ── --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach([
            ['All',       $counts['all'],       '#A855F7', 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5'],
            ['Sent',      $counts['sent'],      '#8B5CF6', 'M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5'],
            ['Received',  $counts['received'],  '#38BDF8', 'M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0-3.75-3.75M17.25 21 21 17.25'],
            ['Pending',   $counts['pending'],   '#F59E0B', 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ['Completed', $counts['completed'], '#22C55E', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ['Cancelled', $counts['cancelled'], '#71717A', 'M6 18 18 6M6 6l12 12'],
        ] as [$label, $count, $color, $icon])
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <div class="mb-3 h-8 w-8 rounded-xl flex items-center justify-center" style="background-color:{{ $color }}18">
                <svg style="height:14px;width:14px;color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
            </div>
            <p class="text-2xl font-black text-[#F8FAFC]">{{ $count }}</p>
            <p class="mt-0.5 text-[11px] text-[#71717A]">{{ $label }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── Tabs ── --}}
    <div class="flex gap-1.5 overflow-x-auto pb-1" style="scrollbar-width:none">
        @foreach([
            ['all',       'All'],
            ['received',  'Received'],
            ['sent',      'Sent'],
            ['pending',   'Pending'],
            ['completed', 'Completed'],
            ['cancelled', 'Cancelled'],
        ] as [$id, $label])
        <button
            @click="tab = '{{ $id }}'"
            :class="tab === '{{ $id }}'
                ? 'border-[#8B5CF6] bg-[#8B5CF6]/12 text-white'
                : 'border-[#27213D] bg-[#0F0D1A] text-[#71717A] hover:border-[#8B5CF6]/30 hover:text-[#A1A1AA]'"
            class="shrink-0 rounded-xl border px-4 py-2 text-xs font-black transition"
        >{{ $label }}</button>
        @endforeach
    </div>

    {{-- All --}}
    <div x-show="tab === 'all'" x-transition.opacity.duration.150ms>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <div class="mb-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">All Transfers</p>
                <p class="mt-0.5 text-[11px] text-[#52525B]">Every transfer you sent or received.</p>
            </div>
            @if($all->isEmpty())
                <div class="flex flex-col items-center gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] py-14 text-center">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-[#71717A]">No transfers yet</p>
                        <p class="mt-0.5 text-xs text-[#52525B]">Sent and received transfers will appear here.</p>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($all as $transfer)
                        @include('transfers._transfer-row', ['transfer' => $transfer, 'role' => $transfer->sender_id === auth()->id() ? 'sender' : 'receiver'])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ── Received ── --}}
    <div x-show="tab === 'received'" x-transition.opacity.duration.150ms>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">Received Transfers</p>
                    <p class="mt-0.5 text-[11px] text-[#52525B]">Bots that other users have sent to your account.</p>
                </div>
            </div>
            @if($received->isEmpty())
                <div class="flex flex-col items-center gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] py-14 text-center">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0-3.75-3.75M17.25 21 21 17.25"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-[#71717A]">No received transfers</p>
                        <p class="mt-0.5 text-xs text-[#52525B]">When someone transfers a bot to your account, it will appear here.</p>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($received as $transfer)
                        @include('transfers._transfer-row', ['transfer' => $transfer, 'role' => 'receiver'])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ── Sent ── --}}
    <div x-show="tab === 'sent'" x-transition.opacity.duration.150ms x-cloak>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <div class="mb-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">Sent Transfers</p>
                <p class="mt-0.5 text-[11px] text-[#52525B]">Bot workspaces you have sent to other accounts.</p>
            </div>
            @if($sent->isEmpty())
                <div class="flex flex-col items-center gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] py-14 text-center">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-[#71717A]">No sent transfers</p>
                        <p class="mt-0.5 text-xs text-[#52525B]">Transfers you initiate from your bot settings will appear here.</p>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($sent as $transfer)
                        @include('transfers._transfer-row', ['transfer' => $transfer, 'role' => 'sender'])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ── Pending ── --}}
    <div x-show="tab === 'pending'" x-transition.opacity.duration.150ms x-cloak>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <div class="mb-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">Pending</p>
                <p class="mt-0.5 text-[11px] text-[#52525B]">Transfers awaiting your acceptance.</p>
            </div>
            @if($pending->isEmpty())
                <div class="flex flex-col items-center gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] py-14 text-center">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-[#71717A]">No pending transfers</p>
                        <p class="mt-0.5 text-xs text-[#52525B]">Transfers waiting for your action will appear here.</p>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($pending as $transfer)
                        @if($transfer->sender_id === auth()->id())
                            @include('transfers._transfer-row', ['transfer' => $transfer, 'role' => 'sender'])
                            @continue
                        @endif
                        {{-- Pending import form --}}
                        <div x-data="{ open: false }" class="rounded-xl border border-[#F59E0B]/30 bg-[#F59E0B]/5 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-[#F8FAFC]">{{ $transfer->bot_name }}</p>
                                    <p class="mt-0.5 text-xs text-[#71717A]">From <span class="text-[#A1A1AA]">{{ $transfer->sender?->email ?? 'unknown sender' }}</span></p>
                                    @if($transfer->note)
                                        <p class="mt-1 text-xs text-[#71717A]">"{{ $transfer->note }}"</p>
                                    @endif
                                    <p class="mt-1 text-[10px] text-[#52525B]">Expires {{ $transfer->expires_at?->diffForHumans() ?? 'never' }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-2 py-0.5 text-[10px] font-black text-[#F59E0B]">Pending</span>
                                    <button @click="open = !open" type="button" class="rounded-xl border border-[#22C55E]/40 bg-[#22C55E]/10 px-3 py-1.5 text-xs font-black text-[#22C55E] transition hover:bg-[#22C55E]/18">
                                        Import
                                    </button>
                                </div>
                            </div>
                            <div x-show="open" x-transition.opacity.duration.150ms x-cloak class="mt-4">
                                <form method="POST" action="{{ route('transfers.import', $transfer) }}" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">Workspace Name</label>
                                        <input name="import_name" type="text" required value="{{ $transfer->bot_name }}" class="w-full rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#71717A] focus:border-[#22C55E]/60 focus:outline-none focus:ring-2 focus:ring-[#22C55E]/20 transition">
                                        @error('import_name') <p class="mt-1 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">Bot Token (optional)</label>
                                    <input name="import_token" type="password" autocomplete="new-password" spellcheck="false" autocapitalize="off" class="w-full rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#71717A] focus:border-[#22C55E]/60 focus:outline-none focus:ring-2 focus:ring-[#22C55E]/20 transition" placeholder="Add a token now, or leave blank and connect it later">
                                        <p class="mt-1 text-[10px] text-[#52525B]">Without a token, the imported bot stays stopped until you add and verify one.</p>
                                        @error('import_token') <p class="mt-1 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
                                    </div>
                                    <button type="submit" class="w-full rounded-xl bg-[#22C55E] py-2.5 text-sm font-black text-[#0B0918] transition hover:-translate-y-0.5">Accept & Import Transfer</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ── Completed ── --}}
    <div x-show="tab === 'completed'" x-transition.opacity.duration.150ms x-cloak>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <div class="mb-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">Completed</p>
                <p class="mt-0.5 text-[11px] text-[#52525B]">Successfully imported bot transfers.</p>
            </div>
            @if($completed->isEmpty())
                <div class="flex flex-col items-center gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] py-14 text-center">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-[#71717A]">No completed transfers</p>
                        <p class="mt-0.5 text-xs text-[#52525B]">Transfers that have been accepted will appear here.</p>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($completed as $transfer)
                        @include('transfers._transfer-row', ['transfer' => $transfer, 'role' => 'receiver'])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Cancelled --}}
    <div x-show="tab === 'cancelled'" x-transition.opacity.duration.150ms x-cloak>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <div class="mb-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">Cancelled</p>
                <p class="mt-0.5 text-[11px] text-[#52525B]">Transfers that can no longer be imported.</p>
            </div>
            @if($cancelled->isEmpty())
                <div class="flex flex-col items-center gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] py-14 text-center">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-[#71717A]">No cancelled transfers</p>
                        <p class="mt-0.5 text-xs text-[#52525B]">Cancelled transfers will appear here.</p>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($cancelled as $transfer)
                        @include('transfers._transfer-row', ['transfer' => $transfer, 'role' => $transfer->sender_id === auth()->id() ? 'sender' : 'receiver'])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ── How it works ── --}}
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-[#71717A]">How Bot Transfers Work</p>
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach([
                ['1', 'Initiate Transfer', 'Go to your bot settings and enter the email address of the account you want to transfer to.', '#8B5CF6'],
                ['2', 'Receiver Imports', 'The receiver sees the transfer in their Pending tab and can import immediately, with or without a bot token.', '#38BDF8'],
                ['3', 'Transfer Complete', 'The bot workspace is created under the receiver\'s account. The sender\'s original bot is unaffected.', '#22C55E'],
            ] as [$step, $title, $desc, $color])
            <div class="flex items-start gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] p-4">
                <span class="mt-0.5 shrink-0 grid h-5 w-5 place-items-center rounded-full text-[10px] font-black text-white" style="background-color:{{ $color }}">{{ $step }}</span>
                <div>
                    <p class="text-xs font-black text-[#A1A1AA]">{{ $title }}</p>
                    <p class="mt-1 text-[11px] leading-relaxed text-[#52525B]">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4 flex items-start gap-3 rounded-xl border border-[#F59E0B]/20 bg-[#F59E0B]/6 px-4 py-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
            <p class="text-xs text-[#A1A1AA]">The sender's bot token is never shared or transferred. Receivers can add their own token during import or connect one later.</p>
        </div>
    </div>

</div>

</x-dashboard-layout>
