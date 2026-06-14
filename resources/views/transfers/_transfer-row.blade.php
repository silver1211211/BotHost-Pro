@php
    $statusColors = [
        'pending'   => ['border-[#F59E0B]/30', 'bg-[#F59E0B]/8',  'text-[#F59E0B]', '#F59E0B'],
        'imported'  => ['border-[#22C55E]/30', 'bg-[#22C55E]/8',  'text-[#22C55E]', '#22C55E'],
        'cancelled' => ['border-[#71717A]/30', 'bg-[#71717A]/8',  'text-[#94A3B8]', '#71717A'],
        'expired'   => ['border-[#EF4444]/30', 'bg-[#EF4444]/8',  'text-[#EF4444]', '#EF4444'],
    ];
    [$borderClass, $bgClass, $textClass, $dotColor] = $statusColors[$transfer->status] ?? ['border-[#27213D]', 'bg-[#0F0D1A]', 'text-[#94A3B8]', '#71717A'];
@endphp
<div class="flex flex-wrap items-start justify-between gap-3 rounded-xl border {{ $borderClass }} {{ $bgClass }} p-4">
    <div class="flex items-start gap-3">
        <div class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-[#27213D] bg-[#151225]">
            <svg class="h-4 w-4 {{ $textClass }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                @if($role === 'sender')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0-3.75-3.75M17.25 21 21 17.25"/>
                @endif
            </svg>
        </div>
        <div>
            <p class="text-sm font-black text-[#F8FAFC]">{{ $transfer->bot_name }}</p>
            @if($role === 'sender')
                <p class="mt-0.5 text-xs text-[#94A3B8]">To <span class="text-[#A1A1AA]">{{ $transfer->receiver_email }}</span></p>
            @else
                <p class="mt-0.5 text-xs text-[#94A3B8]">From <span class="text-[#A1A1AA]">{{ $transfer->sender?->email ?? 'unknown' }}</span></p>
            @endif
            @if($transfer->note)
                <p class="mt-0.5 text-[11px] text-[#52525B]">"{{ Str::limit($transfer->note, 60) }}"</p>
            @endif
            <p class="mt-1 text-[10px] text-[#52525B]">{{ $transfer->created_at->diffForHumans() }}</p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <span class="rounded-full border {{ $borderClass }} bg-transparent px-2 py-0.5 text-[10px] font-black {{ $textClass }}">
            {{ ucfirst($transfer->status) }}
        </span>
        @if($role === 'sender' && $transfer->isPending())
            <form method="POST" action="{{ route('transfers.cancel', $transfer) }}">
                @csrf
                <button
                    type="submit"
                    data-confirm
                    data-confirm-type="danger"
                    data-confirm-title="Cancel transfer?"
                    data-confirm-message="This will cancel the transfer. The receiver will no longer be able to import this bot."
                    data-confirm-btn="Cancel Transfer"
                    class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/8 px-3 py-1.5 text-xs font-black text-[#EF4444] transition hover:bg-[#EF4444]/15"
                >Cancel</button>
            </form>
        @endif
        @if($transfer->imported_at)
            <span class="text-[10px] text-[#52525B]">Imported {{ $transfer->imported_at->format('M j, Y') }}</span>
        @endif
    </div>
</div>
