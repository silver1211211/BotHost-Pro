@props(['status'])

@php
    $value = strtolower((string) $status);
    $classes = match ($value) {
        'running', 'active'             => 'border-[#22C55E]/35 bg-[#22C55E]/10 text-[#22C55E]',
        'paused', 'pending'             => 'border-[#F59E0B]/35 bg-[#F59E0B]/10 text-[#F59E0B]',
        'crashed', 'suspended', 'banned', 'error' => 'border-[#EF4444]/35 bg-[#EF4444]/10 text-[#EF4444]',
        'inactive'                      => 'border-[#EF4444]/35 bg-[#EF4444]/10 text-[#EF4444]',
        'template'                      => 'border-[#38BDF8]/35 bg-[#38BDF8]/10 text-[#38BDF8]',
        'custom', 'custom code'         => 'border-[#8B5CF6]/40 bg-[#8B5CF6]/10 text-[#A855F7]',
        'javascript', 'js'              => 'border-[#229ED9]/40 bg-[#229ED9]/10 text-[#38BDF8]',
        'admin'                         => 'border-[#8B5CF6]/40 bg-[#8B5CF6]/10 text-[#A855F7]',
        'stopped'                       => 'border-[#27213D] bg-[#151225] text-[#A1A1AA]',
        default                         => 'border-[#27213D] bg-[#151225] text-[#A1A1AA]',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-3 py-1 text-xs font-black ' . $classes]) }}>
    {{ ucfirst($value) }}
</span>
