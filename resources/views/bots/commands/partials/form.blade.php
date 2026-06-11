@php
    $storedTriggerType = old('trigger_type', $command?->effectiveTriggerType() ?? 'slash');
    $triggerType = $storedTriggerType === 'direct_message' ? 'direct_message' : 'slash';
    $commandName = old('command_name', $triggerType === 'direct_message' ? $command?->displayName() : $command?->command_name);
    $responseText = old('response_text', $command?->response_text);
    $status = old('status', $command?->status ?? 'active');
    $aliases = old('aliases', $command?->aliases ? implode(', ', $command->aliases) : '');
    $folder = old('folder', $command?->folder);
@endphp

<div x-data="{ triggerType: @js($triggerType), triggerTypeOpen: false }" class="space-y-6">
<div>
    <label for="command_name" class="text-sm font-black text-[#F8FAFC]">Command Name</label>
    <p class="mt-1 text-xs leading-relaxed text-[#71717A]">
        <span x-show="triggerType !== 'direct_message'">The command users send to your bot.</span>
        <span x-show="triggerType === 'direct_message'" x-cloak>A workspace name for this direct message handler.</span>
    </p>
    <div class="relative mt-3">
        <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center">
            <svg class="h-4 w-4 text-[#71717A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
        </span>
        <input id="command_name" name="command_name" type="text" value="{{ $commandName }}" required maxlength="100" class="w-full rounded-xl border border-[#27213D] bg-[#11101C] py-3 pl-10 pr-4 font-mono text-sm text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6]/60 focus:ring-2 focus:ring-[#8B5CF6]/20" placeholder="/start">
    </div>
    @error('command_name') <p class="mt-2 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
</div>

<div>
    <label for="trigger_type" class="text-sm font-black text-[#F8FAFC]">Trigger Type</label>
    <p class="mt-1 text-xs leading-relaxed text-[#71717A]">
        Choose how Telegram updates should match this command.
    </p>
    <div class="relative mt-3" @click.away="triggerTypeOpen = false">
        <input type="hidden" name="trigger_type" :value="triggerType">
        <button type="button" @click="triggerTypeOpen = !triggerTypeOpen" class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] transition focus:outline-none" :class="triggerTypeOpen ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/20' : 'border-[#27213D]'">
            <span x-text="triggerType === 'direct_message' ? 'Direct Message Handler' : 'Command'"></span>
            <svg class="ml-2 h-4 w-4 shrink-0 text-[#71717A] transition-transform" :class="triggerTypeOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>
        <div x-show="triggerTypeOpen" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
            class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
            <button type="button" @click="triggerType = 'slash'; triggerTypeOpen = false" class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm transition hover:bg-[#1D1930]" :class="triggerType === 'slash' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                <svg :class="triggerType === 'slash' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                Command
            </button>
            <button type="button" @click="triggerType = 'direct_message'; triggerTypeOpen = false" class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm transition hover:bg-[#1D1930]" :class="triggerType === 'direct_message' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                <svg :class="triggerType === 'direct_message' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                Direct Message Handler
            </button>
        </div>
    </div>
    <p class="mt-2 text-xs leading-relaxed text-[#71717A]" x-show="triggerType !== 'direct_message'">
        Runs when the user sends a command such as /start, /help, /menu, or /admin.
    </p>
    <p class="mt-2 text-xs leading-relaxed text-[#71717A]" x-show="triggerType === 'direct_message'" x-cloak>
        Runs when the user sends normal text and no command matches. Use it for captcha answers, wallet input, amount input, and admin step-by-step replies.
    </p>
    @error('trigger_type') <p class="mt-2 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="aliases" class="text-sm font-black text-[#F8FAFC]">Command Aliases</label>
        <p class="mt-1 text-xs text-[#71717A]">Optional. Separate up to 10 aliases with commas or new lines.</p>
        <textarea id="aliases" name="aliases" rows="3" class="mt-3 w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6]/60 focus:ring-2 focus:ring-[#8B5CF6]/20" placeholder="/hello, hi">{{ $aliases }}</textarea>
        @error('aliases') <p class="mt-2 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="folder" class="text-sm font-black text-[#F8FAFC]">Folder</label>
        <p class="mt-1 text-xs text-[#71717A]">Optional placeholder for future folder organization.</p>
        <input id="folder" name="folder" value="{{ $folder }}" class="mt-3 w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6]/60 focus:ring-2 focus:ring-[#8B5CF6]/20" placeholder="General">
        @error('folder') <p class="mt-2 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label for="response_text" class="text-sm font-black text-[#F8FAFC]">Response Text</label>
    <p class="mt-1 text-xs text-[#71717A]">Optional display fallback for simple text responses.</p>
    <textarea id="response_text" name="response_text" rows="4" class="mt-3 w-full resize-y rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6]/60 focus:ring-2 focus:ring-[#8B5CF6]/20" placeholder="Welcome! Use /help to see all available commands.">{{ $responseText }}</textarea>
    @error('response_text') <p class="mt-2 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
</div>

<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="text-sm font-black text-[#F8FAFC]">Status</label>
        <div class="relative mt-3" x-data="{ open: false, val: '{{ $status }}', get label() { return { 'active': 'Active', 'inactive': 'Inactive' }[this.val] || 'Active' } }" @click.away="open = false">
            <input type="hidden" name="status" :value="val">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/20' : 'border-[#27213D]'">
                <span x-text="label"></span>
                <svg class="ml-2 h-4 w-4 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </button>
            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $stv => $stl)
                <button type="button" @click="val = '{{ $stv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $stv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                    <svg :class="val === '{{ $stv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    {{ $stl }}
                </button>
                @endforeach
            </div>
        </div>
        @error('status') <p class="mt-2 text-xs font-bold text-[#EF4444]">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3 sm:mt-8">
        <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $command?->is_pinned)) class="rounded border-[#27213D] bg-[#11101C] text-[#8B5CF6] focus:ring-[#8B5CF6]/30">
        <span class="text-sm font-black text-[#F8FAFC]">Pin command</span>
    </label>

    <label class="flex items-center gap-3 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3 sm:mt-8">
        <input type="checkbox" name="admin_only" value="1" @checked(old('admin_only', $command?->admin_only)) class="rounded border-[#27213D] bg-[#11101C] text-[#8B5CF6] focus:ring-[#8B5CF6]/30">
        <span class="text-sm font-black text-[#F8FAFC]">Admin-only</span>
    </label>
</div>
</div>
