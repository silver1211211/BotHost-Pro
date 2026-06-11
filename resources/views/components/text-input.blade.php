@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'rounded-xl border border-[#1E293B] bg-[#0B1220] px-4 py-3 text-[#F8FAFC] shadow-sm outline-none transition placeholder:text-[#64748B] focus:border-[#229ED9] focus:ring-2 focus:ring-[#229ED9]/20 disabled:cursor-not-allowed disabled:opacity-50']) }}
>
