<x-dashboard-layout title="Edit Command">
    <div class="mx-auto max-w-3xl">

        {{-- Back link --}}
        <a
            href="{{ route('bots.show', ['bot' => $bot, 'tab' => 'commands']) }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-[#94A3B8] transition hover:text-[#F8FAFC]"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            Back to {{ $bot->name }}
        </a>

        <div class="mt-5 overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A] shadow-[0_30px_100px_rgba(0,0,0,0.34)]">

            {{-- Header --}}
            <div class="relative overflow-hidden border-b border-[#27213D] px-6 py-7 sm:px-8">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute -right-10 -top-10 h-44 w-44 rounded-full bg-[#38BDF8]/8 blur-3xl"></div>
                </div>
                <div class="relative">
                    <span class="inline-flex items-center gap-2 rounded-full border border-[#8B5CF6]/35 bg-[#8B5CF6]/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-[#8B5CF6]">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                        Edit Command
                    </span>
                    <h2 class="mt-4 text-2xl font-black text-[#F8FAFC] sm:text-3xl">Edit Command</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-[#A1A1AA]">Update the stored trigger and command options. JavaScript code is edited separately.</p>
                </div>

                {{-- Command name preview --}}
                <div class="relative mt-5">
                    <span class="inline-flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#151225] px-3 py-1.5">
                        <span class="text-[10px] font-black uppercase tracking-wide text-[#94A3B8]">Editing:</span>
                        <code class="font-mono text-sm font-black text-[#38BDF8]">{{ $command->displayName() }}</code>
                    </span>
                </div>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('bots.commands.update', [$bot, $command]) }}" class="space-y-6 p-6 sm:p-8">
                @csrf
                @method('PATCH')
                @include('bots.commands.partials.form', ['command' => $command])

                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-[#27213D] pt-6">
                    <a
                        href="{{ route('bots.show', ['bot' => $bot, 'tab' => 'commands']) }}"
                        class="rounded-xl border border-[#27213D] bg-[#151225] px-5 py-3 text-sm font-semibold text-[#A1A1AA] transition hover:text-[#F8FAFC]"
                    >Cancel</a>
                    <div class="flex flex-wrap items-center gap-3">
                        <a
                            href="{{ route('bots.commands.code', [$bot, $command]) }}"
                            class="flex items-center gap-2 rounded-xl border border-[#38BDF8]/40 bg-[#38BDF8]/10 px-5 py-3 text-sm font-black text-[#38BDF8] transition hover:-translate-y-0.5 hover:bg-[#38BDF8]/15"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m17.25 6.75 4.5 4.5-4.5 4.5M6.75 6.75l-4.5 4.5 4.5 4.5m7.5-12-4.5 16.5"/></svg>
                            Edit Code
                        </a>
                        <button
                            type="submit"
                            class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-6 py-3 text-sm font-black text-white shadow-[0_0_24px_rgba(139,92,246,0.28)] transition hover:-translate-y-0.5"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>

            <form method="POST" action="{{ route('bots.commands.destroy', [$bot, $command]) }}" class="border-t border-[#27213D] p-6 sm:p-8">
                @csrf
                @method('DELETE')
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#EF4444]/25 bg-[#EF4444]/5 p-4">
                    <div>
                        <p class="text-sm font-black text-[#EF4444]">Delete Command</p>
                        <p class="mt-1 text-xs text-[#A1A1AA]">Remove this trigger and its saved JavaScript code.</p>
                    </div>
                    <button
                        type="submit"
                        data-confirm
                        data-confirm-type="danger"
                        data-confirm-title="Delete command?"
                        data-confirm-message="This will permanently remove &quot;{{ addslashes($command->displayName()) }}&quot; and its JavaScript code. This cannot be undone."
                        data-confirm-btn="Delete Command"
                        class="rounded-xl bg-[#EF4444] px-5 py-3 text-sm font-black text-white transition hover:-translate-y-0.5"
                    >Delete Command</button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>
