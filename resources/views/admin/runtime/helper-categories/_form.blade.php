@php
    $fieldClass = 'w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white placeholder:text-[#4D4868] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15';
    $labelClass = 'mb-1.5 block text-xs font-bold text-[#E5E7EB]';
    $helpClass = 'mt-1 text-xs leading-5 text-[#94A3B8]';
@endphp

<div class="space-y-4">
    <a href="{{ route('admin.runtime.helper-categories.index') }}" class="inline-flex items-center gap-1.5 text-sm text-[#A1A1AA] hover:text-white">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to categories
    </a>

    @if(session('status'))
        <div class="rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/10 px-4 py-3 text-sm text-[#22C55E]">{{ session('status') }}</div>
    @endif
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
                <label for="name" class="{{ $labelClass }}">Category name</label>
                <input id="name" name="name" value="{{ old('name', $category->name) }}" placeholder="Example: FaucetPay Helpers" class="{{ $fieldClass }}">
                <p class="{{ $helpClass }}">Visible admin name used to group related Runtime Helpers in the admin panel.</p>
            </div>

            <div>
                <label for="slug" class="{{ $labelClass }}">Slug / key</label>
                <input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="Example: faucetpay_helpers" class="{{ $fieldClass }}">
                <p class="{{ $helpClass }}">Stable internal key for this category. Enter it manually and keep it lowercase with underscores when possible.</p>
            </div>

            <div>
                <label for="helper_type" class="{{ $labelClass }}">Helper type</label>
                <input id="helper_type" name="helper_type" value="{{ old('helper_type', $category->helper_type ?: 'utility') }}" placeholder="Example: validation" class="{{ $fieldClass }}">
                <p class="{{ $helpClass }}">Used to group helpers by purpose, such as validation, payment, telegram, data, admin, or security.</p>
            </div>

            <div>
                <label for="default_timeout_ms" class="{{ $labelClass }}">Default timeout (ms)</label>
                <input id="default_timeout_ms" name="default_timeout_ms" type="number" min="100" max="60000" value="{{ old('default_timeout_ms', $category->default_timeout_ms ?: 5000) }}" placeholder="Example: 5000" class="{{ $fieldClass }}">
                <p class="{{ $helpClass }}">Default maximum runtime for helpers in this category.</p>
            </div>

            <div>
                <label for="permission_level" class="{{ $labelClass }}">Permission level</label>
                <input id="permission_level" name="permission_level" type="number" min="0" max="2" value="{{ old('permission_level', $category->permission_level ?? 0) }}" placeholder="Example: 0" class="{{ $fieldClass }}">
                <p class="{{ $helpClass }}">Access level for helpers in this group. Use 0 for normal helpers, 1 or 2 for more restricted admin helpers.</p>
            </div>

            <div>
                <label for="sort_order" class="{{ $labelClass }}">Sort order</label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $category->sort_order ?? 0) }}" placeholder="Example: 10" class="{{ $fieldClass }}">
                <p class="{{ $helpClass }}">Controls category order in admin lists. Lower numbers appear first.</p>
            </div>
        </div>

        <div>
            <label for="description" class="{{ $labelClass }}">Description</label>
            <textarea id="description" name="description" rows="3" placeholder="Example: Admin-only notes for FaucetPay validation helpers." class="{{ $fieldClass }}">{{ old('description', $category->description) }}</textarea>
            <p class="{{ $helpClass }}">Admin-only grouping note. This is for maintainers and is not shown to bot users.</p>
        </div>

        <div>
            <label for="allowed_domains" class="{{ $labelClass }}">Allowed domains</label>
            <textarea id="allowed_domains" name="allowed_domains" rows="3" placeholder="Example: api.faucetpay.io" class="{{ $fieldClass }}">{{ old('allowed_domains', implode("\n", $category->allowed_domains ?? [])) }}</textarea>
            <p class="{{ $helpClass }}">Optional newline or comma separated domains helpers in this category may call.</p>
        </div>

        <label class="flex items-start gap-3 rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3 text-sm text-[#E5E7EB]">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->exists ? $category->is_active : true)) class="mt-0.5 h-4 w-4 rounded border-[#27213D] bg-[#090713] accent-[#8B5CF6]">
            <span>
                <span class="block font-bold">Active category</span>
                <span class="mt-1 block text-xs leading-5 text-[#94A3B8]">Active categories can be selected on Runtime Helper create/edit forms. Turn this off to hide the category without deleting it.</span>
            </span>
        </label>

        <button class="rounded-xl bg-[#8B5CF6] px-5 py-2.5 text-sm font-black text-white transition hover:bg-[#7C3AED]">{{ $button }}</button>
    </form>
</div>
