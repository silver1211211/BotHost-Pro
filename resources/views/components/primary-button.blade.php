<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-xl bg-[#229ED9] px-5 py-2.5 text-sm font-bold text-white shadow-[0_0_20px_rgba(34,158,217,0.22)] transition hover:bg-[#38BDF8] focus:outline-none focus:ring-2 focus:ring-[#229ED9]/40']) }}>
    {{ $slot }}
</button>
