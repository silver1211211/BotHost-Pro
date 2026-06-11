@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-bold text-[#F8FAFC]']) }}>
    {{ $value ?? $slot }}
</label>
