<div class="grid gap-3 md:grid-cols-2">
    <input name="command_name" value="{{ old('command_name', $command->command_name) }}" placeholder="/start" class="rounded border border-[#27213D] bg-[#0F0D1A] p-3">
    <input name="description" value="{{ old('description', $command->description) }}" placeholder="Description" class="rounded border border-[#27213D] bg-[#0F0D1A] p-3">
    <input name="aliases" value="{{ old('aliases', implode(', ', $command->aliases ?? [])) }}" placeholder="Aliases, comma separated" class="rounded border border-[#27213D] bg-[#0F0D1A] p-3">
    <input name="folder" value="{{ old('folder', $command->folder) }}" placeholder="Folder" class="rounded border border-[#27213D] bg-[#0F0D1A] p-3">
    <input name="runtime" value="{{ old('runtime', $command->runtime ?: 'node') }}" placeholder="node" class="rounded border border-[#27213D] bg-[#0F0D1A] p-3">
    <input name="language" value="{{ old('language', $command->language ?: 'javascript') }}" placeholder="javascript" class="rounded border border-[#27213D] bg-[#0F0D1A] p-3">
    <input type="number" name="sort_order" value="{{ old('sort_order', $command->sort_order ?? 0) }}" min="0" placeholder="Sort order" class="rounded border border-[#27213D] bg-[#0F0D1A] p-3">
    <div class="relative" x-data="{ open: false, val: '{{ old('status', $command->status ?: 'active') }}', labels: @js(array_combine(\App\Models\BotTemplateCommand::STATUSES, array_map('ucfirst', \App\Models\BotTemplateCommand::STATUSES))), get label() { return this.labels[this.val] || 'Active' } }" @click.away="open = false">
        <input type="hidden" name="status" :value="val">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-3 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/15' : ''">
            <span x-text="label"></span>
            <svg class="ml-2 h-3.5 w-3.5 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
            class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
            @foreach (\App\Models\BotTemplateCommand::STATUSES as $csval)
            <button type="button" @click="val = '{{ $csval }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $csval }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                <svg :class="val === '{{ $csval }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                {{ ucfirst($csval) }}
            </button>
            @endforeach
        </div>
    </div>
</div>
<textarea name="response_text" rows="3" placeholder="Response text" class="rounded border border-[#27213D] bg-[#0F0D1A] p-3">{{ old('response_text', $command->response_text) }}</textarea>
<textarea name="code" rows="5" placeholder="Optional JavaScript code" class="font-mono rounded border border-[#27213D] bg-[#0F0D1A] p-3">{{ old('code', $command->code) }}</textarea>
