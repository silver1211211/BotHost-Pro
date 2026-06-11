<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-xl border border-[#1E293B] bg-[#0B1220] px-5 py-2.5 text-sm font-bold text-[#F8FAFC] transition hover:border-[#229ED9] hover:text-[#38BDF8] focus:outline-none focus:ring-2 focus:ring-[#229ED9]/30']) }}>
    {{ $slot }}
</button>
