<div class="space-y-4">
    <a href="{{ route('admin.runtime.helper-categories.index') }}" class="text-sm text-[#A1A1AA] hover:text-white">Back to categories</a>
    @if($errors->any())
        <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#FCA5A5]">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <form method="POST" action="{{ $action }}" class="space-y-4 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        @csrf
        @if($method !== 'POST') @method($method) @endif
        <div class="grid gap-3 sm:grid-cols-2">
            <input name="name" value="{{ old('name', $category->name) }}" placeholder="Name" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
            <input name="slug" value="{{ old('slug', $category->slug) }}" placeholder="slug" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
            <input name="helper_type" value="{{ old('helper_type', $category->helper_type ?: 'utility') }}" placeholder="helper_type" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
            <input name="default_timeout_ms" type="number" min="100" max="60000" value="{{ old('default_timeout_ms', $category->default_timeout_ms ?: 5000) }}" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
            <input name="permission_level" type="number" min="0" max="2" value="{{ old('permission_level', $category->permission_level ?? 0) }}" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
            <input name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
        </div>
        <textarea name="description" rows="3" placeholder="Description" class="w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">{{ old('description', $category->description) }}</textarea>
        <textarea name="allowed_domains" rows="3" placeholder="Allowed domains, comma or newline separated" class="w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">{{ old('allowed_domains', implode("\n", $category->allowed_domains ?? [])) }}</textarea>
        <label class="flex items-center gap-2 text-sm text-[#A1A1AA]"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->exists ? $category->is_active : true)) class="accent-[#8B5CF6]"> Active</label>
        <button class="rounded-xl bg-[#8B5CF6] px-5 py-2 text-sm font-black text-white">{{ $button }}</button>
    </form>
</div>
