@php
    $sourceVersion = $version ?? $helper->versions()->latest('version_number')->first();
    $schema = fn ($value) => old($value[0], isset($value[1]) && $value[1] ? json_encode($value[1], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '');
@endphp
<div class="space-y-4">
    <a href="{{ route('admin.runtime.helpers.index') }}" class="text-sm text-[#A1A1AA] hover:text-white">Back to helpers</a>
    @if($errors->any())
        <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#FCA5A5]">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <form method="POST" action="{{ $action }}" class="space-y-4">
        @csrf
        @if($method !== 'POST') @method($method) @endif
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <div class="grid gap-3 md:grid-cols-2">
                <select name="category_id" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white"><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $helper->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select>
                <input name="helper_type" value="{{ old('helper_type', $helper->helper_type ?: 'utility') }}" placeholder="helper_type" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
                <input name="name" value="{{ old('name', $helper->name) }}" @readonly($helper->exists) placeholder="JS helper name" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white {{ $helper->exists ? 'opacity-70' : '' }}">
                <input name="label" value="{{ old('label', $helper->label) }}" placeholder="Display label" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
                <input name="timeout_ms" type="number" min="100" max="60000" value="{{ old('timeout_ms', $sourceVersion->timeout_ms ?? $helper->timeout_ms ?? 5000) }}" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
                <input name="permission_level" type="number" min="0" max="2" value="{{ old('permission_level', $sourceVersion->permission_level ?? $helper->permission_level ?? 0) }}" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
            </div>
            <textarea name="description" rows="3" placeholder="Description" class="mt-3 w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">{{ old('description', $helper->description) }}</textarea>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <textarea name="parameters_schema" rows="5" placeholder="Parameters schema JSON" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 font-mono text-xs text-white">{{ old('parameters_schema', ($sourceVersion?->parameters_schema ?: $helper->parameters_schema) ? json_encode($sourceVersion?->parameters_schema ?: $helper->parameters_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                <textarea name="return_schema" rows="5" placeholder="Return schema JSON" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 font-mono text-xs text-white">{{ old('return_schema', ($sourceVersion?->return_schema ?: $helper->return_schema) ? json_encode($sourceVersion?->return_schema ?: $helper->return_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
            </div>
            <textarea name="allowed_domains" rows="3" placeholder="Allowed domains" class="mt-3 w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">{{ old('allowed_domains', implode("\n", $sourceVersion?->allowed_domains ?: ($helper->allowed_domains ?? []))) }}</textarea>
            <div class="mt-3 flex flex-wrap gap-4 text-sm text-[#A1A1AA]">
                <label class="flex items-center gap-2"><input type="checkbox" name="expose_to_bot_code" value="1" @checked(old('expose_to_bot_code', $helper->exists ? $helper->expose_to_bot_code : true)) class="accent-[#8B5CF6]"> Expose to bot code</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="show_in_helper_list" value="1" @checked(old('show_in_helper_list', $helper->exists ? $helper->show_in_helper_list : true)) class="accent-[#8B5CF6]"> Show in helper list</label>
            </div>
        </div>
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <p class="mb-2 rounded-xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-3 py-2 text-sm font-semibold text-[#F59E0B]">Write only the helper function body. Do not include async function wrapper. Example: return { ok: true };</p>
            <textarea name="code" rows="18" class="w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 font-mono text-xs text-white">{{ old('code', $sourceVersion->code ?? $helper->code) }}</textarea>
            <input name="change_summary" value="{{ old('change_summary') }}" placeholder="Change summary" class="mt-3 w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 text-sm text-white">
        </div>
        <div id="runtime-helper-test-panel" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-white">Test Helper</h2>
                    <p class="mt-1 text-xs text-[#94A3B8]">Dry-run tests use fake bot, user, balance, and bridge values. They do not publish helpers or touch live runtime.</p>
                </div>
                <label class="flex items-center gap-2 text-sm text-[#A1A1AA]">
                    <input id="runtime-helper-test-dry-run" type="checkbox" checked class="accent-[#8B5CF6]">
                    Dry run
                </label>
            </div>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <textarea id="runtime-helper-test-params" rows="5" placeholder='Test Params JSON, e.g. {"name":"Ada"}' class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 font-mono text-xs text-white"></textarea>
                <textarea id="runtime-helper-test-expected" rows="5" placeholder="Expected Output JSON (optional)" class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2 font-mono text-xs text-white"></textarea>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <button id="runtime-helper-run-test" type="button" class="rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/10 px-4 py-2 text-sm font-black text-[#22C55E] transition hover:bg-[#22C55E]/15">Run Test</button>
                <p class="text-xs text-[#94A3B8]">Real payment tests are disabled in private beta.</p>
            </div>
            <div id="runtime-helper-test-result" class="mt-3 hidden rounded-xl border px-4 py-3 text-sm"></div>
        </div>
        <button class="rounded-xl bg-[#8B5CF6] px-5 py-2 text-sm font-black text-white">{{ $button }}</button>
    </form>
</div>
<script>
(function () {
    const button = document.getElementById('runtime-helper-run-test');
    const result = document.getElementById('runtime-helper-test-result');
    if (!button || !result) return;

    const form = button.closest('form');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function value(name) {
        return form.querySelector('[name="' + name + '"]')?.value || '';
    }

    function show(payload, ok) {
        result.className = 'mt-3 rounded-xl border px-4 py-3 text-sm ' + (ok ? 'border-[#22C55E]/30 bg-[#22C55E]/10 text-[#86EFAC]' : 'border-[#EF4444]/30 bg-[#EF4444]/10 text-[#FCA5A5]');
        result.classList.remove('hidden');
        const actual = payload.actual_output === null || payload.actual_output === undefined ? '' : JSON.stringify(payload.actual_output, null, 2);
        result.innerHTML =
            '<p class="font-bold">' + (ok ? 'Test passed' : 'Test failed') + ' · ' + (payload.execution_ms ?? 0) + 'ms</p>' +
            (payload.error ? '<p class="mt-2 whitespace-pre-wrap">' + payload.error + '</p>' : '') +
            (actual ? '<pre class="mt-2 max-h-72 overflow-auto rounded-lg bg-[#090713] p-3 text-xs text-[#A1A1AA]">' + actual.replace(/[&<>]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;'}[c]; }) + '</pre>' : '');
    }

    button.addEventListener('click', async function () {
        button.disabled = true;
        button.textContent = 'Running...';
        result.className = 'mt-3 rounded-xl border border-[#27213D] px-4 py-3 text-sm text-[#A1A1AA]';
        result.textContent = 'Running helper test...';
        result.classList.remove('hidden');

        try {
            const response = await fetch(@json(route('admin.runtime.helpers.test')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    helper_id: @json($helper->exists ? $helper->id : null),
                    version_id: @json($sourceVersion?->id),
                    name: value('name'),
                    helper_type: value('helper_type') || 'utility',
                    code: value('code'),
                    allowed_domains: value('allowed_domains'),
                    params: document.getElementById('runtime-helper-test-params').value,
                    expected_output: document.getElementById('runtime-helper-test-expected').value,
                    dry_run: document.getElementById('runtime-helper-test-dry-run').checked,
                }),
            });
            const payload = await response.json();
            if (!response.ok && payload.errors) {
                show({ error: Object.values(payload.errors).flat().join("\n"), execution_ms: 0 }, false);
            } else {
                show(payload, !!payload.ok);
            }
        } catch (error) {
            show({ error: String(error && error.message ? error.message : error), execution_ms: 0 }, false);
        } finally {
            button.disabled = false;
            button.textContent = 'Run Test';
        }
    });
})();
</script>
