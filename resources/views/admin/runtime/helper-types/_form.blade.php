@php
    $fieldClass = 'w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white placeholder:text-[#4D4868] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15';
    $labelClass = 'mb-1.5 block text-xs font-bold text-[#E5E7EB]';
    $helpClass = 'mt-1 text-xs leading-5 text-[#94A3B8]';
@endphp

<div class="space-y-4">
    <a href="{{ route('admin.runtime.helper-types.index') }}" class="inline-flex items-center gap-1.5 text-sm text-[#A1A1AA] hover:text-white">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to helper types
    </a>

    @if($errors->any())
        <div class="space-y-1 rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#FCA5A5]">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-5 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="{{ $labelClass }}">Display label</label>
                <input id="name" name="name" value="{{ old('name', $type->name) }}" placeholder="Example: External API" class="{{ $fieldClass }}">
                <p class="{{ $helpClass }}">Human-readable name shown in admin helper forms.</p>
            </div>

            <div>
                <label for="slug" class="{{ $labelClass }}">Slug / key</label>
                <input id="slug" name="slug" value="{{ old('slug', $type->slug) }}" placeholder="Example: external_api" class="{{ $fieldClass }}">
                <p class="{{ $helpClass }}">Stable runtime key. Use lowercase letters, numbers, and underscores only.</p>
            </div>

            <div>
                <label for="sort_order" class="{{ $labelClass }}">Sort order</label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $type->sort_order ?? 0) }}" placeholder="Example: 10" class="{{ $fieldClass }}">
                <p class="{{ $helpClass }}">Lower numbers appear first in dropdowns and lists.</p>
            </div>
        </div>

        <div>
            <label for="description" class="{{ $labelClass }}">Description</label>
            <textarea id="description" name="description" rows="3" placeholder="Example: Helpers that call approved external APIs." class="{{ $fieldClass }}">{{ old('description', $type->description) }}</textarea>
            <p class="{{ $helpClass }}">Admin note for what this helper type should contain.</p>
        </div>

        <label class="flex items-start gap-3 rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3 text-sm text-[#E5E7EB]">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->exists ? $type->is_active : true)) class="mt-0.5 h-4 w-4 rounded border-[#27213D] bg-[#090713] accent-[#8B5CF6]">
            <span>
                <span class="block font-bold">Active helper type</span>
                <span class="mt-1 block text-xs leading-5 text-[#94A3B8]">Active types are selectable on helper and category forms. Deactivate to hide without breaking existing helpers.</span>
            </span>
        </label>

        <button class="rounded-xl bg-[#8B5CF6] px-5 py-2.5 text-sm font-black text-white transition hover:bg-[#7C3AED]">{{ $button }}</button>
    </form>
</div>
