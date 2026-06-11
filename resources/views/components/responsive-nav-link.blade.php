@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[#229ED9] text-start text-base font-semibold text-[#38BDF8] bg-[#0F172A] focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-[#64748B] hover:text-[#F8FAFC] hover:bg-[#111827] hover:border-[#1E293B] focus:outline-none focus:text-[#F8FAFC] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
