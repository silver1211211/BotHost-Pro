<form method="POST" action="{{ $action }}" class="max-w-3xl rounded-2xl border border-[#1E293B] bg-[#0B1220] p-6 shadow-[0_20px_70px_rgba(0,0,0,0.22)]">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="name" class="text-sm font-bold text-[#F8FAFC]">Project name</label>
        <input
            id="name" name="name" value="{{ old('name', $project?->name) }}" required
            class="mt-2 block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#64748B] focus:border-[#229ED9] focus:ring-2 focus:ring-[#229ED9]/20"
        >
        @error('name') <p class="mt-2 text-sm font-semibold text-[#FCA5A5]">{{ $message }}</p> @enderror
    </div>

    <div class="mt-5">
        <label for="description" class="text-sm font-bold text-[#F8FAFC]">Description</label>
        <textarea
            id="description" name="description" rows="4"
            class="mt-2 block w-full rounded-xl border border-[#1E293B] bg-[#111827] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#64748B] focus:border-[#229ED9] focus:ring-2 focus:ring-[#229ED9]/20"
        >{{ old('description', $project?->description) }}</textarea>
        @error('description') <p class="mt-2 text-sm font-semibold text-[#FCA5A5]">{{ $message }}</p> @enderror
    </div>

    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <div>
            <label for="language" class="text-sm font-bold text-[#F8FAFC]">Language</label>
            <div class="relative mt-2" x-data="{ open: false, val: '{{ old('language', $project?->language ?? 'javascript') }}', labels: { javascript: 'JavaScript' }, get label() { return this.labels[this.val] || 'JavaScript' } }" @click.away="open = false">
                <input type="hidden" name="language" :value="val">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#1E293B] bg-[#111827] px-4 py-3 text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#229ED9] ring-2 ring-[#229ED9]/20' : ''">
                    <span x-text="label"></span>
                    <svg class="ml-2 h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#1E293B] bg-[#111827] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    <button type="button" @click="val = 'javascript'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1E293B]" :class="val === 'javascript' ? 'text-[#229ED9] bg-[#229ED9]/8' : 'text-[#94A3B8]'">
                        <svg :class="val === 'javascript' ? 'opacity-100 text-[#229ED9]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        JavaScript
                    </button>
                </div>
            </div>
        </div>
        @if ($project)
            <div>
                <label for="status" class="text-sm font-bold text-[#F8FAFC]">Status</label>
                <div class="relative mt-2" x-data="{ open: false, val: '{{ old('status', $project->status) }}', labels: { running: 'Running', paused: 'Paused', stopped: 'Stopped' }, get label() { return this.labels[this.val] || 'Running' } }" @click.away="open = false">
                    <input type="hidden" name="status" :value="val">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#1E293B] bg-[#111827] px-4 py-3 text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#229ED9] ring-2 ring-[#229ED9]/20' : ''">
                        <span x-text="label"></span>
                        <svg class="ml-2 h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#1E293B] bg-[#111827] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                        @foreach (['running', 'paused', 'stopped'] as $pjStatus)
                        <button type="button" @click="val = '{{ $pjStatus }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1E293B]" :class="val === '{{ $pjStatus }}' ? 'text-[#229ED9] bg-[#229ED9]/8' : 'text-[#94A3B8]'">
                            <svg :class="val === '{{ $pjStatus }}' ? 'opacity-100 text-[#229ED9]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            {{ ucfirst($pjStatus) }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if (! $project && $templates->isNotEmpty())
        <div class="mt-5">
            <label for="template_id" class="text-sm font-bold text-[#F8FAFC]">Starter template</label>
            <div class="relative mt-2" x-data="{ open: false, val: '{{ old('template_id') }}', labels: @js($templates->pluck('name', 'id')->map(fn($name, $id) => $name . ' - ' . $templates->find($id)?->category)->prepend('Basic Telegram Bot', '')->all()), get label() { return this.labels[this.val] !== undefined ? this.labels[this.val] : 'Basic Telegram Bot' } }" @click.away="open = false">
                <input type="hidden" name="template_id" :value="val">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#1E293B] bg-[#111827] px-4 py-3 text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#229ED9] ring-2 ring-[#229ED9]/20' : ''">
                    <span x-text="label"></span>
                    <svg class="ml-2 h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#1E293B] bg-[#111827] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    <button type="button" @click="val = ''; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1E293B]" :class="val === '' ? 'text-[#229ED9] bg-[#229ED9]/8' : 'text-[#94A3B8]'">
                        <svg :class="val === '' ? 'opacity-100 text-[#229ED9]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Basic Telegram Bot
                    </button>
                    @foreach ($templates as $template)
                    <button type="button" @click="val = '{{ $template->id }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1E293B]" :class="val === '{{ $template->id }}' ? 'text-[#229ED9] bg-[#229ED9]/8' : 'text-[#94A3B8]'">
                        <svg :class="val === '{{ $template->id }}' ? 'opacity-100 text-[#229ED9]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $template->name }} - {{ $template->category }}
                    </button>
                    @endforeach
                </div>
            </div>
            <p class="mt-2 text-xs text-[#64748B]">Templates generate starter files only. No runtime execution is enabled in this phase.</p>
        </div>
    @endif

    <div class="mt-8 flex gap-3">
        <button class="rounded-xl bg-[#229ED9] px-5 py-2.5 text-sm font-bold text-white shadow-[0_0_20px_rgba(34,158,217,0.22)] transition hover:bg-[#38BDF8]">{{ $button }}</button>
        <a href="{{ route('projects.index') }}" class="rounded-xl border border-[#1E293B] bg-[#0B1220] px-5 py-2.5 text-sm font-semibold text-[#94A3B8] transition hover:border-[#229ED9] hover:text-[#F8FAFC]">Cancel</a>
    </div>
</form>
