<x-dashboard-layout title="Use Template">
    <div class="mx-auto max-w-5xl space-y-5">
        <a href="{{ route('bots.show', ['bot' => $bot, 'tab' => 'commands']) }}" class="text-sm font-semibold text-[#A1A1AA]">Back to {{ $bot->name }}</a>

        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <h1 class="text-2xl font-black text-[#F8FAFC]">Use Template</h1>
            <p class="mt-1 text-sm text-[#71717A]">Import ready-made commands into this bot.</p>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 md:grid-cols-4">
            <input name="search" value="{{ request('search') }}" placeholder="Search templates" class="rounded-xl border border-[#27213D] bg-[#11101C] p-3 text-sm">
            <div class="relative" x-data="{ open: false, val: '{{ request('category') }}', get label() { return this.val || 'All categories' } }" @click.away="open = false">
                <input type="hidden" name="category" :value="val">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-3 text-sm text-[#A1A1AA] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/20' : ''">
                    <span x-text="label"></span>
                    <svg class="ml-2 h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    <button type="button" @click="val = ''; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        All categories
                    </button>
                    @foreach ($categories as $category)
                    <button type="button" @click="val = '{{ $category }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $category }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $category }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $category }}
                    </button>
                    @endforeach
                </div>
            </div>
            <div class="relative" x-data="{ open: false, val: '{{ request('level') }}', labels: @js(array_combine(\App\Models\BotTemplate::LEVELS, array_map('ucfirst', \App\Models\BotTemplate::LEVELS))), get label() { return this.val ? (this.labels[this.val] || this.val) : 'All levels' } }" @click.away="open = false">
                <input type="hidden" name="level" :value="val">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-3 text-sm text-[#A1A1AA] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/20' : ''">
                    <span x-text="label"></span>
                    <svg class="ml-2 h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    <button type="button" @click="val = ''; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        All levels
                    </button>
                    @foreach (\App\Models\BotTemplate::LEVELS as $level)
                    <button type="button" @click="val = '{{ $level }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $level }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $level }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ ucfirst($level) }}
                    </button>
                    @endforeach
                </div>
            </div>
            <button class="rounded-xl bg-[#8B5CF6] px-4 py-3 text-sm font-black text-white">Filter</button>
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($templates as $template)
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                    @if($template->thumbnail_url)<img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }}" class="mb-4 h-32 w-full rounded-xl object-cover">@else<div class="mb-4 flex h-32 w-full items-center justify-center rounded-xl border border-[#27213D] bg-[#11101C] text-sm text-[#71717A]">No image</div>@endif
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-black text-[#F8FAFC]">{{ $template->name }}</h2>
                            <p class="mt-1 text-sm text-[#71717A]">{{ Str::limit($template->short_description ?: $template->description, 140) }}</p>
                        </div>
                        @if ($template->is_featured)
                            <span class="rounded-full border border-[#F59E0B]/30 px-2 py-1 text-[10px] font-black text-[#F59E0B]">Featured</span>
                        @endif
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-[10px] text-[#A1A1AA]">
                        <span>{{ $template->category ?: 'General' }}</span>
                        <span>{{ ucfirst($template->level) }}</span>
                        <span>{{ $template->commands_count }} commands</span>
                        <span>{{ $template->formatted_price }}</span>
                        <span>{{ $template->import_count }} imports</span>
                        @if($template->includedPlanLabel())<span>{{ $template->includedPlanLabel() }}</span>@endif
                    </div>
                    @if ($template->tags)
                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach ($template->tags as $tag)
                                <span class="rounded border border-[#27213D] px-2 py-0.5 text-[10px] text-[#71717A]">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('bots.templates.show', [$bot, $template]) }}" class="rounded-xl border border-[#27213D] px-4 py-2 text-sm font-bold text-[#A1A1AA]">Preview</a>
                        <form method="POST" action="{{ route('bots.templates.import', [$bot, $template]) }}">
                            @csrf
                            <button class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-sm font-bold text-white">Import to This Bot</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-8 text-center text-[#71717A] md:col-span-2">No published templates found.</div>
            @endforelse
        </div>

        {{ $templates->links() }}
    </div>
</x-dashboard-layout>
