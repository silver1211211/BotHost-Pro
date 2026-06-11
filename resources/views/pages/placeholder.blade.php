<x-dashboard-layout :title="$title">
    <div class="rounded-3xl border border-[#27213D] bg-[#0F0D1A] p-8 shadow-[0_30px_90px_rgba(0,0,0,0.28)]">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
            <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-[#8B5CF6]/25 to-[#38BDF8]/20 text-[#38BDF8]">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-black uppercase tracking-[0.22em] text-[#38BDF8]">Coming Soon</p>
                <h2 class="mt-2 text-3xl font-black text-[#F8FAFC] sm:text-5xl">{{ $title }}</h2>
                <p class="mt-4 max-w-2xl text-[#A1A1AA]">{{ $description }}</p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-5 md:grid-cols-3">
        @foreach (['Premium UI is ready', 'Backend comes later', 'No runtime claims yet'] as $item)
            <div class="rounded-3xl border border-[#27213D] bg-[#0F0D1A] p-6">
                <span class="rounded-full border border-[#F59E0B]/25 bg-[#F59E0B]/10 px-3 py-1 text-xs font-black text-[#F59E0B]">Planned</span>
                <p class="mt-5 font-black text-[#F8FAFC]">{{ $item }}</p>
            </div>
        @endforeach
    </div>
</x-dashboard-layout>
