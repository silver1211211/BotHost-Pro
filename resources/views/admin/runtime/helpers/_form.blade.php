@php
    $sourceVersion = $version ?? ($helper->exists ? $helper->versions()->latest('version_number')->first() : null);
    $fieldClass = 'mt-1 w-full rounded-lg border border-[#3A3354] bg-[#090713] px-3 py-2 text-sm text-white outline-none transition placeholder:text-[#64748B] focus:border-[#8B5CF6] focus:ring-2 focus:ring-[#8B5CF6]/20';
    $textareaClass = $fieldClass.' font-mono text-xs leading-5 caret-[#A78BFA]';
    $labelClass = 'block text-sm font-bold text-[#F8FAFC]';
    $helpClass = 'mt-1 text-xs leading-5 text-[#A1A1AA]';
    $helperTypeValue = old('helper_type', $helper->helper_type);
    $helperTypeOptions = collect(['utility', 'validation', 'payment', 'faucetpay', 'telegram', 'data', 'admin', 'security']);

    if (filled($helperTypeValue) && ! $helperTypeOptions->contains($helperTypeValue)) {
        $helperTypeOptions->push($helperTypeValue);
    }

    $parametersSchemaValue = old('parameters_schema', ($sourceVersion?->parameters_schema ?: $helper->parameters_schema)
        ? json_encode($sourceVersion?->parameters_schema ?: $helper->parameters_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        : '');
    $returnSchemaValue = old('return_schema', ($sourceVersion?->return_schema ?: $helper->return_schema)
        ? json_encode($sourceVersion?->return_schema ?: $helper->return_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        : '');
    $allowedDomainsValue = old('allowed_domains', implode("\n", $sourceVersion?->allowed_domains ?: ($helper->allowed_domains ?? [])));
    $codeValue = old('code', $sourceVersion?->code ?? $helper->code ?? '');
@endphp

<div class="space-y-4">
    <a href="{{ route('admin.runtime.helpers.index') }}" class="text-sm text-[#A1A1AA] hover:text-white">Back to helpers</a>

    @if($errors->any())
        <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#FCA5A5]">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-4">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="category_id" class="{{ $labelClass }}">Category</label>
                    <select id="category_id" name="category_id" class="{{ $fieldClass }}">
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $helper->category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $helpClass }}">Choose the admin grouping this helper belongs to.</p>
                </div>

                <div>
                    <label for="helper_type" class="{{ $labelClass }}">Helper type</label>
                    <select id="helper_type" name="helper_type" class="{{ $fieldClass }}">
                        <option value="">Select helper type</option>
                        @foreach($helperTypeOptions as $type)
                            <option value="{{ $type }}" @selected($helperTypeValue === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $helpClass }}">Select the runtime behavior category for this helper.</p>
                </div>

                <div>
                    <label for="name" class="{{ $labelClass }}">JS helper name</label>
                    <input id="name" name="name" value="{{ old('name', $helper->name) }}" @readonly($helper->exists) placeholder="Example: isValidFaucetPayEmail" class="{{ $fieldClass }} {{ $helper->exists ? 'opacity-70' : '' }}">
                    <p class="{{ $helpClass }}">Use a JavaScript identifier. Helper names cannot be changed after creation.</p>
                </div>

                <div>
                    <label for="label" class="{{ $labelClass }}">Display label</label>
                    <input id="label" name="label" value="{{ old('label', $helper->label) }}" placeholder="Example: Validate FaucetPay Email" class="{{ $fieldClass }}">
                    <p class="{{ $helpClass }}">Human-readable name shown in the admin interface.</p>
                </div>

                <div>
                    <label for="timeout_ms" class="{{ $labelClass }}">Timeout milliseconds</label>
                    <input id="timeout_ms" name="timeout_ms" type="number" min="100" max="60000" value="{{ old('timeout_ms', $sourceVersion->timeout_ms ?? $helper->timeout_ms ?? 5000) }}" placeholder="Example: 5000" class="{{ $fieldClass }}">
                    <p class="{{ $helpClass }}">Maximum helper test/runtime time in milliseconds.</p>
                </div>

                <div>
                    <label for="permission_level" class="{{ $labelClass }}">Permission level</label>
                    <input id="permission_level" name="permission_level" type="number" min="0" max="2" value="{{ old('permission_level', $sourceVersion->permission_level ?? $helper->permission_level ?? 0) }}" placeholder="Example: 0" class="{{ $fieldClass }}">
                    <p class="{{ $helpClass }}">Use 0 for normal helpers unless this helper needs elevated admin review.</p>
                </div>
            </div>

            <div class="mt-4">
                <label for="description" class="{{ $labelClass }}">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Example: Checks whether a user email is valid." class="{{ $fieldClass }}">{{ old('description', $helper->description) }}</textarea>
                <p class="{{ $helpClass }}">Short admin note explaining what this helper does.</p>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label for="parameters_schema" class="{{ $labelClass }}">Parameters schema JSON</label>
                    <textarea id="parameters_schema" name="parameters_schema" rows="6" placeholder='{"type":"object","properties":{"email":{"type":"string"}}}' class="{{ $textareaClass }}">{{ $parametersSchemaValue }}</textarea>
                    <p class="{{ $helpClass }}">Describe the params object this helper expects. Example: {"type":"object","properties":{"email":{"type":"string"}}}</p>
                </div>

                <div>
                    <label for="return_schema" class="{{ $labelClass }}">Return schema JSON</label>
                    <textarea id="return_schema" name="return_schema" rows="6" placeholder='{"type":"object","properties":{"ok":{"type":"boolean"}}}' class="{{ $textareaClass }}">{{ $returnSchemaValue }}</textarea>
                    <p class="{{ $helpClass }}">Describe the object this helper returns.</p>
                </div>
            </div>

            <div class="mt-4">
                <label for="allowed_domains" class="{{ $labelClass }}">Allowed domains</label>
                <textarea id="allowed_domains" name="allowed_domains" rows="3" placeholder="Example: api.example.com" class="{{ $fieldClass }}">{{ $allowedDomainsValue }}</textarea>
                <p class="{{ $helpClass }}">Use [] if the helper does not call external domains. Enter one domain per line or a JSON array.</p>
            </div>

            <div class="mt-4 flex flex-wrap gap-5 text-sm text-[#E2E8F0]">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="expose_to_bot_code" value="1" @checked(old('expose_to_bot_code', $helper->exists ? $helper->expose_to_bot_code : true)) class="accent-[#8B5CF6]">
                    Expose to bot code
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="show_in_helper_list" value="1" @checked(old('show_in_helper_list', $helper->exists ? $helper->show_in_helper_list : true)) class="accent-[#8B5CF6]">
                    Show in helper list
                </label>
            </div>
        </div>

        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <label for="code" class="{{ $labelClass }}">Helper function body</label>
            <p class="{{ $helpClass }}">Paste only JavaScript function body here. Do not include async function wrapper. Do not paste labels like Description, Change summary, or Test Params.</p>
            <p class="mt-2 rounded-xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-3 py-2 text-sm font-semibold text-[#FCD34D]">Only paste JavaScript code in this box.</p>
            <textarea
                id="code"
                name="code"
                rows="24"
                placeholder="return { ok: true };"
                spellcheck="false"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                wrap="soft"
                data-helper-code-input
                class="{{ $textareaClass }} min-h-[420px] resize-y overflow-auto whitespace-pre-wrap bg-[#070612] text-[13px] leading-6 text-[#F8FAFC]"
            >{{ $codeValue }}</textarea>
            <p class="{{ $helpClass }}">Write only the helper function body. Do not include an async function wrapper.</p>

            <div class="mt-4">
                <label for="change_summary" class="{{ $labelClass }}">Change summary</label>
                <input id="change_summary" name="change_summary" value="{{ old('change_summary') }}" placeholder="Example: Initial draft" class="{{ $fieldClass }}">
                <p class="{{ $helpClass }}">Brief note for this draft version.</p>
            </div>
        </div>

        <div id="runtime-helper-test-panel" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-white">Test Helper</h2>
                    <p class="mt-1 text-xs leading-5 text-[#A1A1AA]">Dry-run tests use fake bot, user, balance, and bridge values. They do not publish helpers or touch live runtime.</p>
                </div>
                <label class="flex items-center gap-2 text-sm text-[#E2E8F0]">
                    <input id="runtime-helper-test-dry-run" type="checkbox" checked class="accent-[#8B5CF6]">
                    Dry run
                </label>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label for="runtime-helper-test-params" class="{{ $labelClass }}">Test Params JSON</label>
                    <textarea id="runtime-helper-test-params" rows="6" placeholder='{"email":"user@gmail.com"}' class="{{ $textareaClass }}"></textarea>
                    <p class="{{ $helpClass }}">Test Params JSON is passed into the helper as params.</p>
                </div>

                <div>
                    <label for="runtime-helper-test-expected" class="{{ $labelClass }}">Expected Output JSON optional</label>
                    <textarea id="runtime-helper-test-expected" rows="6" placeholder='{"ok":true}' class="{{ $textareaClass }}"></textarea>
                    <p class="{{ $helpClass }}">Optional expected result for comparing test output.</p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button id="runtime-helper-run-test" type="button" class="rounded-xl border border-[#22C55E]/40 bg-[#22C55E]/10 px-4 py-2 text-sm font-black text-[#86EFAC] transition hover:bg-[#22C55E]/15">Run Test</button>
                <p class="text-xs text-[#A1A1AA]">Real payment tests are disabled in private beta.</p>
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
    const codeInput = form.querySelector('[data-helper-code-input]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function value(name) {
        return form.querySelector('[name="' + name + '"]')?.value || '';
    }

    function syncHelperCode() {
        if (!codeInput) return '';

        // Keep this explicit even though the current editor is a native textarea.
        // If a richer editor is added later, this remains the single sync point.
        codeInput.value = codeInput.value;

        return codeInput.value;
    }

    function show(payload, ok) {
        result.className = 'mt-3 rounded-xl border px-4 py-3 text-sm ' + (ok ? 'border-[#22C55E]/30 bg-[#22C55E]/10 text-[#86EFAC]' : 'border-[#EF4444]/30 bg-[#EF4444]/10 text-[#FCA5A5]');
        result.classList.remove('hidden');
        const actual = payload.actual_output === null || payload.actual_output === undefined ? '' : JSON.stringify(payload.actual_output, null, 2);
        result.innerHTML =
            '<p class="font-bold">' + (ok ? 'Test passed' : 'Test failed') + ' &middot; ' + (payload.execution_ms ?? 0) + 'ms</p>' +
            (payload.error ? '<p class="mt-2 whitespace-pre-wrap">' + payload.error + '</p>' : '') +
            (actual ? '<pre class="mt-2 max-h-72 overflow-auto rounded-lg bg-[#090713] p-3 text-xs text-[#A1A1AA]">' + actual.replace(/[&<>]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;'}[c]; }) + '</pre>' : '');
    }

    if (codeInput) {
        codeInput.addEventListener('input', syncHelperCode);
        codeInput.addEventListener('change', syncHelperCode);
        codeInput.addEventListener('paste', function () {
            window.setTimeout(syncHelperCode, 0);
        });
    }

    form.addEventListener('submit', function () {
        syncHelperCode();
    });

    button.addEventListener('click', async function () {
        const currentCode = syncHelperCode();

        button.disabled = true;
        button.textContent = 'Running...';
        result.className = 'mt-3 rounded-xl border border-[#3A3354] px-4 py-3 text-sm text-[#A1A1AA]';
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
                    helper_type: value('helper_type'),
                    code: currentCode,
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
