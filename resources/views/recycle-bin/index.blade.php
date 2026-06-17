<x-dashboard-layout title="Recycle Bin">

<div class="space-y-6" x-data="{ confirmOpen: false, confirmTitle: '', confirmMessage: '', confirmForm: null, openConfirm(title, message, form) { this.confirmTitle = title; this.confirmMessage = message; this.confirmForm = form; this.confirmOpen = true; }, submitConfirm() { if (this.confirmForm) this.confirmForm.submit(); } }">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-[#F8FAFC]">Recycle Bin</h1>
            <p class="mt-1 text-sm text-[#94A3B8]">Restore deleted bots or permanently remove old items. Bots are kept for {{ $retentionDays }} days.</p>
        </div>
        <a href="{{ route('bots.index') }}" class="rounded-xl border border-[#27213D] bg-[#0F0D1A] px-4 py-2 text-xs font-black text-[#A1A1AA] transition hover:text-white">Back to Bots</a>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach([
            ['Deleted Items', $deletedCount + $deletedCommandCount,  '#EF4444', 'M20.25 7.5l-.625 10.632A2.25 2.25 0 0 1 17.378 20.25H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z'],
            ['Deleted Bots',  $deletedCount,  '#8B5CF6', 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z'],
            ['Deleted Commands', $deletedCommandCount, '#38BDF8', 'M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z'],
            ['Expiring Soon', $expiringCount, '#F59E0B', 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ] as [$label, $count, $color, $path])
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                <div class="mb-3 flex h-8 w-8 items-center justify-center rounded-xl" style="background-color:{{ $color }}18">
                    <svg class="h-4 w-4" style="color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                </div>
                <p class="text-2xl font-black text-[#F8FAFC]">{{ $count }}</p>
                <p class="mt-0.5 text-[11px] text-[#94A3B8]">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/8 px-4 py-3 text-sm text-[#EF4444]">{{ $errors->first() }}</div>
    @endif

    @if (session('status'))
        <div class="rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/10 px-4 py-3 text-sm text-[#22C55E]">{{ session('status') }}</div>
    @endif

    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
        @if ($bots->isEmpty())
            <div class="flex flex-col items-center gap-4 rounded-xl border border-[#1B172B] bg-[#0B0918] py-16 text-center">
                <div class="grid h-14 w-14 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632A2.25 2.25 0 0 1 17.378 20.25H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5"/></svg>
                </div>
                <div>
                    <p class="text-base font-black text-[#94A3B8]">Recycle Bin is empty</p>
                    <p class="mt-1 text-sm text-[#52525B]">Deleted bots will appear here for recovery before permanent removal.</p>
                </div>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($bots as $bot)
                    <div class="rounded-xl border border-[#1B172B] bg-[#0B0918] p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-[#F8FAFC]">{{ $bot->name }}</p>
                                <p class="mt-1 text-xs text-[#94A3B8]">{{ $bot->telegram_username ? '@'.$bot->telegram_username : 'No Telegram username' }}</p>
                                <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold text-[#A1A1AA]">
                                    <span class="rounded-lg border border-[#27213D] px-2.5 py-1">Deleted {{ $bot->deleted_days }} {{ Str::plural('day', $bot->deleted_days) }} ago</span>
                                    @if ($bot->days_remaining > 0)
                                        <span class="rounded-lg border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-2.5 py-1 text-[#F59E0B]">{{ $bot->days_remaining }} {{ Str::plural('day', $bot->days_remaining) }} remaining</span>
                                    @else
                                        <span class="rounded-lg border border-[#EF4444]/30 bg-[#EF4444]/10 px-2.5 py-1 text-[#EF4444]">Pending permanent deletion</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <form method="POST" action="{{ route('recycle-bin.bots.restore', $bot->id) }}">
                                    @csrf
                                    <button class="rounded-xl bg-gradient-to-r from-[#22C55E] to-[#16A34A] px-4 py-2 text-xs font-black text-white">Restore</button>
                                </form>
                                <form method="POST" action="{{ route('recycle-bin.bots.force-delete', $bot->id) }}" @submit.prevent="openConfirm('Delete bot forever?', 'This permanently deletes only {{ addslashes($bot->name) }} and cannot be undone.', $event.target)">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/8 px-4 py-2 text-xs font-black text-[#EF4444]">Delete Forever</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-5">{{ $bots->links() }}</div>
        @endif
    </div>

    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Deleted Commands</p>
                <p class="mt-1 text-sm text-[#94A3B8]">Commands in recycle bin still reserve their exact trigger until restored or permanently deleted.</p>
            </div>
            <span class="rounded-full border border-[#27213D] bg-[#151225] px-3 py-1 text-xs font-black text-[#A1A1AA]">{{ $deletedCommandCount }} command{{ $deletedCommandCount === 1 ? '' : 's' }}</span>
        </div>
        @if ($commands->isEmpty())
            <div class="rounded-xl border border-[#1B172B] bg-[#0B0918] py-10 text-center text-sm text-[#94A3B8]">No deleted commands.</div>
        @else
            <div class="space-y-3">
                @foreach ($commands as $command)
                    <div class="rounded-xl border border-[#1B172B] bg-[#0B0918] p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <p class="break-words font-mono text-sm font-black text-[#F8FAFC]">{{ $command->displayName() }}</p>
                                <p class="mt-1 text-xs text-[#94A3B8]">{{ $command->bot?->name ?? 'Bot removed' }} · {{ ucfirst(str_replace('_', ' ', $command->effectiveTriggerType())) }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <form method="POST" action="{{ route('recycle-bin.commands.restore', $command->id) }}">
                                    @csrf
                                    <button class="rounded-xl bg-gradient-to-r from-[#22C55E] to-[#16A34A] px-4 py-2 text-xs font-black text-white">Restore</button>
                                </form>
                                <form method="POST" action="{{ route('recycle-bin.commands.force-delete', $command->id) }}" @submit.prevent="openConfirm('Delete command forever?', 'This permanently deletes only this command from the recycle bin. You can recreate the same trigger afterward if no active command conflicts.', $event.target)">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/8 px-4 py-2 text-xs font-black text-[#EF4444]">Delete Forever</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-5">{{ $commands->links() }}</div>
        @endif
    </div>

    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">How the Recycle Bin Works</p>
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach([
                ['Delete a Bot',          'When you delete a bot, it moves to the Recycle Bin instead of being permanently removed.', '#8B5CF6', 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0'],
                ['Restore Before Expiry', 'Bots can be restored until the retention window ends.',                                      '#38BDF8', 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99'],
                ['Auto-Removed',          'Items older than the retention period are permanently deleted automatically.',                '#F59E0B', 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ] as [$title, $desc, $color, $path])
                <div class="rounded-xl border border-[#1B172B] bg-[#0B0918] p-4">
                    <div class="mb-2 flex h-7 w-7 items-center justify-center rounded-lg" style="background-color:{{ $color }}15">
                        <svg class="h-3.5 w-3.5" style="color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                    </div>
                    <p class="text-xs font-black text-[#A1A1AA]">{{ $title }}</p>
                    <p class="mt-1 text-[11px] leading-relaxed text-[#52525B]">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/70 p-4">
        <div class="w-full max-w-md rounded-2xl border border-[#EF4444]/30 bg-[#0F0D1A] p-5 shadow-2xl">
            <h2 class="text-lg font-black text-[#F8FAFC]" x-text="confirmTitle"></h2>
            <p class="mt-2 text-sm leading-6 text-[#FCA5A5]" x-text="confirmMessage"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="confirmOpen = false; confirmForm = null" class="rounded-xl border border-[#27213D] px-4 py-2 text-sm font-black text-[#A1A1AA]">Cancel</button>
                <button type="button" @click="submitConfirm()" class="rounded-xl bg-[#DC2626] px-4 py-2 text-sm font-black text-white">Delete Forever</button>
            </div>
        </div>
    </div>
</div>

</x-dashboard-layout>
