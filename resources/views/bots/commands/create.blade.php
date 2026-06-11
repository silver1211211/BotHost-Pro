<x-dashboard-layout title="Add Command">
    <div class="mx-auto max-w-3xl">

        {{-- Back link --}}
        <a
            href="{{ route('bots.show', ['bot' => $bot, 'tab' => 'commands']) }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-[#71717A] transition hover:text-[#F8FAFC]"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            Back to {{ $bot->name }}
        </a>

        <div class="mt-5 overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A] shadow-[0_30px_100px_rgba(0,0,0,0.34)]">

            {{-- Header --}}
            <div class="relative overflow-hidden border-b border-[#27213D] px-6 py-7 sm:px-8">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute -right-10 -top-10 h-44 w-44 rounded-full bg-[#8B5CF6]/8 blur-3xl"></div>
                </div>
                <div class="relative">
                    <span class="inline-flex items-center gap-2 rounded-full border border-[#38BDF8]/35 bg-[#38BDF8]/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-[#38BDF8]">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        New Command
                    </span>
                    <h2 class="mt-4 text-2xl font-black text-[#F8FAFC] sm:text-3xl">Add Command</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-[#A1A1AA]">Store an exact command trigger and JavaScript code. Execution connects when the runtime is enabled.</p>
                </div>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('bots.commands.store', $bot) }}" class="space-y-6 p-6 sm:p-8">
                @csrf
                @include('bots.commands.partials.form', ['command' => null])

                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-[#27213D] pt-6">
                    <a
                        href="{{ route('bots.show', ['bot' => $bot, 'tab' => 'commands']) }}"
                        class="rounded-xl border border-[#27213D] bg-[#151225] px-5 py-3 text-sm font-semibold text-[#A1A1AA] transition hover:text-[#F8FAFC]"
                    >Cancel</a>
                    <button
                        type="submit"
                        class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-6 py-3 text-sm font-black text-white shadow-[0_0_24px_rgba(139,92,246,0.28)] transition hover:-translate-y-0.5 hover:shadow-[0_0_32px_rgba(139,92,246,0.38)]"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Save Command
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>
