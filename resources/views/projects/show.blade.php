<x-dashboard-layout :title="$project->name">
    @php
        $filePayload = $files->map(fn ($file) => [
            'id'        => $file->id,
            'name'      => $file->name,
            'path'      => $file->relative_path,
            'size'      => $file->size,
            'showUrl'   => route('projects.files.show', [$project, $file]),
            'updateUrl' => route('projects.files.update', [$project, $file]),
        ])->values();
    @endphp

    <div
        x-data="projectWorkspace({
            csrf: @js(csrf_token()),
            files: @js($filePayload),
            activeFile: @js($activeFile ? $filePayload->firstWhere('id', $activeFile->id) : null),
            activeContent: @js($activeContent),
        })"
        class="overflow-hidden rounded-2xl border border-[#1E293B] bg-[#050B18]"
    >
        {{-- Workspace header --}}
        <div class="flex flex-col gap-4 border-b border-[#1E293B] bg-[#0B1220] px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl font-black text-[#F8FAFC]">{{ $project->name }}</h2>
                    <x-status-badge :status="$project->status" />
                    <span class="rounded-full border border-[#229ED9]/30 bg-[#229ED9]/10 px-3 py-1 text-xs font-bold text-[#38BDF8]">{{ $project->template?->name ?? 'Basic Telegram Bot' }}</span>
                </div>
                <p class="mt-1 text-sm text-[#64748B]">{{ $project->slug }} · {{ $project->language }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <p x-show="saving" class="text-sm text-[#94A3B8]">Saving...</p>
                <p x-show="savedAt" class="text-sm text-[#86EFAC]">Saved <span x-text="savedAt"></span></p>
                <button type="button" @click="save" class="rounded-xl bg-[#229ED9] px-4 py-2 text-sm font-bold text-white shadow-[0_0_16px_rgba(34,158,217,0.22)] transition hover:bg-[#38BDF8]">Save</button>
                <a href="{{ route('projects.edit', $project) }}" class="rounded-xl border border-[#1E293B] px-4 py-2 text-sm font-semibold text-[#94A3B8] transition hover:border-[#229ED9] hover:text-[#F8FAFC]">Project details</a>
            </div>
        </div>

        <div class="grid min-h-[720px] lg:grid-cols-[19rem_1fr]">

            {{-- Sidebar panels --}}
            <aside class="border-b border-[#1E293B] bg-[#07111F] p-4 lg:border-b-0 lg:border-r">
                <div x-data="{ panel: 'files' }" class="space-y-4">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach (['files' => 'Files', 'settings' => 'Settings', 'variables' => 'Variables', 'logs' => 'Logs'] as $key => $label)
                            <button
                                type="button"
                                @click="panel = '{{ $key }}'"
                                class="rounded-xl px-3 py-2 text-sm font-semibold transition"
                                :class="panel === '{{ $key }}'
                                    ? 'bg-[#229ED9] text-white shadow-[0_0_14px_rgba(34,158,217,0.20)]'
                                    : 'border border-[#1E293B] bg-[#0B1220] text-[#94A3B8] hover:border-[#229ED9]/40 hover:text-[#F8FAFC]'"
                            >{{ $label }}</button>
                        @endforeach
                    </div>

                    {{-- Files panel --}}
                    <section x-show="panel === 'files'" class="space-y-4">
                        <form method="POST" action="{{ route('projects.files.store', $project) }}" class="rounded-xl border border-[#1E293B] bg-[#0B1220] p-3">
                            @csrf
                            <label class="text-xs font-bold text-[#94A3B8]" for="new-file-path">New file</label>
                            <div class="mt-2 flex gap-2">
                                <input id="new-file-path" name="path" placeholder="helpers.js" class="min-w-0 flex-1 rounded-xl border border-[#1E293B] bg-[#0B1220] px-3 py-2 text-sm text-[#F8FAFC] outline-none placeholder:text-[#64748B] focus:border-[#229ED9] focus:ring-2 focus:ring-[#229ED9]/20">
                                <button class="rounded-xl bg-[#229ED9] px-3 text-sm font-bold text-white transition hover:bg-[#38BDF8]">Add</button>
                            </div>
                            @error('path') <p class="mt-2 text-xs text-[#FCA5A5]">{{ $message }}</p> @enderror
                        </form>

                        <div class="space-y-1.5">
                            <template x-for="file in files" :key="file.id">
                                <button
                                    type="button"
                                    @click="openFile(file)"
                                    class="block w-full rounded-xl px-3 py-2.5 text-left text-sm transition"
                                    :class="activeFile && activeFile.id === file.id
                                        ? 'border-l-2 border-[#229ED9] bg-[#0F172A] text-[#38BDF8]'
                                        : 'border border-[#1E293B] bg-[#0B1220] text-[#94A3B8] hover:border-[#229ED9]/30 hover:text-[#F8FAFC]'"
                                >
                                    <span class="block truncate font-semibold" x-text="file.path"></span>
                                    <span class="text-xs opacity-60" x-text="`${file.size} bytes`"></span>
                                </button>
                            </template>
                        </div>

                        <div class="space-y-2 rounded-xl border border-[#1E293B] bg-[#0B1220] p-3">
                            <h3 class="text-sm font-bold text-[#F8FAFC]">Manage file</h3>
                            @foreach ($files as $file)
                                <details class="rounded-xl bg-[#111827] p-3 text-sm text-[#94A3B8]">
                                    <summary class="cursor-pointer truncate font-semibold text-[#F8FAFC]">{{ $file->relative_path }}</summary>
                                    <form method="POST" action="{{ route('projects.files.rename', [$project, $file]) }}" class="mt-3 space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <input name="path" value="{{ $file->relative_path }}" class="block w-full rounded-xl border border-[#1E293B] bg-[#0B1220] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#229ED9]">
                                        <button class="rounded-xl border border-[#1E293B] px-3 py-1.5 text-xs font-bold text-[#94A3B8] transition hover:border-[#229ED9] hover:text-[#F8FAFC]">Rename</button>
                                    </form>
                                    <form method="POST" action="{{ route('projects.files.destroy', [$project, $file]) }}" class="mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            data-confirm
                                            data-confirm-type="danger"
                                            data-confirm-title="Delete file?"
                                            data-confirm-message="&quot;{{ addslashes($file->relative_path) }}&quot; will be permanently deleted."
                                            data-confirm-btn="Delete File"
                                            class="text-xs font-bold text-[#EF4444] transition hover:text-red-400"
                                        >Delete file</button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </section>

                    {{-- Settings panel --}}
                    <section x-show="panel === 'settings'" class="space-y-4">
                        <form method="POST" action="{{ route('projects.settings.update', $project) }}" class="space-y-4 rounded-xl border border-[#1E293B] bg-[#0B1220] p-4">
                            @csrf
                            @method('PATCH')
                            <div>
                                <h3 class="font-bold text-[#F8FAFC]">Telegram Settings</h3>
                                <input name="bot_token" type="password" autocomplete="new-password" spellcheck="false" autocapitalize="off" placeholder="{{ $setting->bot_token ? 'Bot token saved' : 'Bot token' }}" class="mt-3 block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none placeholder:text-[#64748B] focus:border-[#229ED9]">
                                <input name="admin_id" value="{{ old('admin_id', $setting->admin_id) }}" placeholder="Admin ID" class="mt-2 block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none placeholder:text-[#64748B] focus:border-[#229ED9]">
                            </div>
                            <div>
                                <h3 class="font-bold text-[#F8FAFC]">API Settings</h3>
                                <input name="oxapay_api_key" type="password" placeholder="{{ $setting->oxapay_api_key ? 'OXAPAY key saved' : 'OXAPAY API key' }}" class="mt-3 block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none placeholder:text-[#64748B] focus:border-[#229ED9]">
                                <textarea name="external_apis" rows="3" placeholder="External APIs, one per line" class="mt-2 block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none placeholder:text-[#64748B] focus:border-[#229ED9]">{{ old('external_apis', implode("\n", $setting->external_apis ?? [])) }}</textarea>
                            </div>
                            <div>
                                <h3 class="font-bold text-[#F8FAFC]">Runtime Settings</h3>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <input name="ram_limit" type="number" min="128" max="1024" value="{{ old('ram_limit', $setting->ram_limit) }}" class="rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#229ED9]">
                                    <input name="cpu_limit" type="number" min="0.1" max="2" step="0.1" value="{{ old('cpu_limit', $setting->cpu_limit) }}" class="rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#229ED9]">
                                </div>
                                <input name="timezone" value="{{ old('timezone', $setting->timezone) }}" class="mt-2 block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#229ED9]">
                                <label class="mt-3 flex items-center gap-2 text-sm text-[#94A3B8]"><input type="checkbox" name="auto_restart" value="1" @checked($setting->auto_restart) class="rounded border-[#1E293B] bg-[#0B1220] text-[#229ED9] focus:ring-[#229ED9]/30"> Auto restart</label>
                                <label class="mt-2 flex items-center gap-2 text-sm text-[#94A3B8]"><input type="checkbox" name="webhook_enabled" value="1" @checked($setting->webhook_enabled) class="rounded border-[#1E293B] bg-[#0B1220] text-[#229ED9] focus:ring-[#229ED9]/30"> Webhook enabled</label>
                            </div>
                            <button class="w-full rounded-xl bg-[#229ED9] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#38BDF8]">Save Settings</button>
                        </form>
                    </section>

                    {{-- Variables panel --}}
                    <section x-show="panel === 'variables'" class="space-y-4">
                        <form method="POST" action="{{ route('projects.variables.store', $project) }}" class="space-y-3 rounded-xl border border-[#1E293B] bg-[#0B1220] p-4">
                            @csrf
                            <input name="key" placeholder="BOT_TOKEN" class="block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none placeholder:text-[#64748B] focus:border-[#229ED9]">
                            <input name="value" placeholder="Value" class="block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none placeholder:text-[#64748B] focus:border-[#229ED9]">
                            <label class="flex items-center gap-2 text-sm text-[#94A3B8]"><input type="checkbox" name="is_secret" value="1" class="rounded border-[#1E293B] bg-[#0B1220] text-[#229ED9] focus:ring-[#229ED9]/30"> Secret variable</label>
                            <button class="w-full rounded-xl bg-[#229ED9] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#38BDF8]">Add Variable</button>
                        </form>

                        <div class="space-y-2">
                            @forelse ($variables as $variable)
                                <details class="rounded-xl border border-[#1E293B] bg-[#0B1220] p-3">
                                    <summary class="cursor-pointer text-sm font-bold text-[#F8FAFC]">{{ $variable->key }} <span class="text-xs text-[#64748B]">{{ $variable->displayValue() }}</span></summary>
                                    <form method="POST" action="{{ route('projects.variables.update', [$project, $variable]) }}" class="mt-3 space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <input name="key" value="{{ $variable->key }}" class="block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#229ED9]">
                                        <input name="value" placeholder="{{ $variable->is_secret ? 'Leave blank to keep' : 'Leave blank to keep value' }}" class="block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#229ED9]">
                                        <label class="flex items-center gap-2 text-sm text-[#94A3B8]"><input type="checkbox" name="is_secret" value="1" @checked($variable->is_secret) class="rounded border-[#1E293B] bg-[#0B1220] text-[#229ED9] focus:ring-[#229ED9]/30"> Secret</label>
                                        <button class="rounded-xl border border-[#1E293B] px-3 py-1.5 text-xs font-bold text-[#94A3B8] transition hover:border-[#229ED9] hover:text-[#F8FAFC]">Update</button>
                                    </form>
                                    <form method="POST" action="{{ route('projects.variables.destroy', [$project, $variable]) }}" class="mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            data-confirm
                                            data-confirm-type="danger"
                                            data-confirm-title="Delete variable?"
                                            data-confirm-message="&quot;{{ addslashes($variable->key) }}&quot; will be permanently deleted."
                                            data-confirm-btn="Delete Variable"
                                            class="text-xs font-bold text-[#EF4444] transition hover:text-red-400"
                                        >Delete variable</button>
                                    </form>
                                </details>
                            @empty
                                <p class="rounded-xl border border-[#1E293B] bg-[#0B1220] p-4 text-sm text-[#94A3B8]">No variables yet.</p>
                            @endforelse
                        </div>
                    </section>

                    {{-- Logs panel --}}
                    <section x-show="panel === 'logs'" class="rounded-xl border border-[#1E293B] bg-[#0B1220] p-4">
                        <h3 class="font-bold text-[#F8FAFC]">Runtime Logs</h3>
                        <p class="mt-2 text-sm text-[#94A3B8]">Runtime logs will appear after the Node.js runtime phase.</p>
                    </section>
                </div>
            </aside>

            {{-- Monaco editor --}}
            <main class="min-w-0 bg-[#050B18]">
                {{-- Tab bar --}}
                <div class="flex min-h-12 items-end gap-1 overflow-x-auto border-b border-[#1E293B] bg-[#070B14] px-3 pt-2">
                    <template x-for="tab in openTabs" :key="tab.id">
                        <div
                            class="flex max-w-56 items-center gap-2 rounded-t-xl border border-b-0 px-3 py-2 text-sm transition"
                            :class="activeFile && activeFile.id === tab.id
                                ? 'border-[#229ED9]/40 bg-[#0B1220] text-[#F8FAFC]'
                                : 'border-[#1E293B] bg-[#070B14] text-[#64748B] hover:text-[#94A3B8]'"
                        >
                            <button type="button" @click="openFile(tab)" class="min-w-0 flex-1 truncate text-left font-semibold" x-text="tab.path"></button>
                            <button type="button" @click.stop="closeTab(tab)" class="shrink-0 rounded px-1 text-xs transition hover:text-[#EF4444]">×</button>
                        </div>
                    </template>
                    <p x-show="openTabs.length === 0" class="pb-3 text-sm text-[#64748B]">Open a file from the sidebar.</p>
                </div>

                {{-- File info bar --}}
                <div class="flex items-center justify-between border-b border-[#1E293B] bg-[#070B14] px-5 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-[#F8FAFC]" x-text="activeFile ? activeFile.path : 'No file selected'"></p>
                        <p class="text-xs text-[#64748B]">JavaScript · storage/projects/{{ $project->id }}</p>
                    </div>
                    <p x-show="error" class="text-sm font-semibold text-[#FCA5A5]" x-text="error"></p>
                </div>

                {{-- Monaco editor mount --}}
                <div x-ref="editor" class="h-[650px] w-full"></div>
            </main>
        </div>
    </div>
</x-dashboard-layout>
