<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-xl bg-[#EF4444] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-red-400 focus:outline-none focus:ring-2 focus:ring-[#EF4444]/40']) }}>
    {{ $slot }}
</button>
