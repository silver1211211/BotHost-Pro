<x-dashboard-layout title="Broadcasts">
    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-black text-[#F8FAFC]">Broadcasts</h1>
                <p class="mt-1 text-sm text-[#71717A]">Create and monitor Telegram bot broadcasts from eligible bots.</p>
            </div>
        </div>

        @if ($eligibleBots->isEmpty())
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-6">
                <p class="font-black text-[#A1A1AA]">No eligible bots</p>
                <p class="mt-1 text-sm text-[#71717A]">A bot needs a verified token before it can send broadcasts.</p>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($eligibleBots as $bot)
                    <a href="{{ route('bots.broadcasts.index', $bot) }}" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 transition hover:border-[#8B5CF6]/40">
                        <p class="truncate text-sm font-black text-[#F8FAFC]">{{ $bot->name }}</p>
                        <p class="mt-1 text-xs text-[#71717A]">
                            {{ $bot->telegram_username ? '@'.$bot->telegram_username : 'Verified token available' }}
                            @if ($bot->trashed()) <span class="text-[#F59E0B]">(recycled)</span> @endif
                        </p>
                        <p class="mt-3 text-xs font-black text-[#38BDF8]">Open broadcast builder</p>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
            <div class="border-b border-[#27213D] px-5 py-4">
                <p class="text-sm font-black text-[#F8FAFC]">Broadcast History</p>
            </div>
            <div class="divide-y divide-[#1B172B]">
                @forelse ($broadcasts as $broadcast)
                    <div class="px-5 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-[#F8FAFC]">{{ $broadcast->title ?: 'Untitled Broadcast' }}</p>
                                <p class="mt-1 text-xs text-[#71717A]">
                                    {{ $broadcast->bot?->name ?? 'Deleted bot' }}
                                    @if ($broadcast->bot?->trashed()) <span class="text-[#F59E0B]">(recycled)</span> @endif
                                    · {{ $broadcast->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2 text-[11px] font-black">
                                <span class="rounded-lg border border-[#27213D] px-2.5 py-1 text-[#A1A1AA]">{{ ucfirst($broadcast->status) }}</span>
                                <span class="rounded-lg border border-[#22C55E]/25 bg-[#22C55E]/8 px-2.5 py-1 text-[#22C55E]">{{ number_format($broadcast->sent_count) }} sent</span>
                                <span class="rounded-lg border border-[#EF4444]/25 bg-[#EF4444]/8 px-2.5 py-1 text-[#EF4444]">{{ number_format($broadcast->failed_count) }} failed</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center text-sm text-[#71717A]">No broadcasts yet.</div>
                @endforelse
            </div>
            <div class="px-5 py-4">{{ $broadcasts->links() }}</div>
        </div>
    </div>
</x-dashboard-layout>
