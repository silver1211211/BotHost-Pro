@props(['title', 'text', 'badge' => 'Coming Soon'])

<div class="rounded-3xl border border-[#27213D] bg-[#0F0D1A] p-6 shadow-[0_24px_70px_rgba(0,0,0,0.20)] transition hover:-translate-y-1 hover:border-[#8B5CF6]/55">
    <div class="flex items-center justify-between gap-3">
        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-[#8B5CF6]/25 to-[#38BDF8]/20 text-[#38BDF8]">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.75 5.75h12.5v12.5H5.75V5.75Zm3 4h6.5m-6.5 4h4"/></svg>
        </span>
        <span class="rounded-full border border-[#F59E0B]/25 bg-[#F59E0B]/10 px-3 py-1 text-xs font-black text-[#F59E0B]">{{ $badge }}</span>
    </div>
    <h3 class="mt-5 text-xl font-black text-[#F8FAFC]">{{ $title }}</h3>
    <p class="mt-3 text-sm leading-6 text-[#A1A1AA]">{{ $text }}</p>
</div>
