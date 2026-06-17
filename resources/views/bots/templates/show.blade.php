<x-dashboard-layout title="{{ $template->name }}">
    <div class="mx-auto max-w-4xl space-y-5">
        <a href="{{ route('bots.templates.index', $bot) }}" class="text-sm font-semibold text-[#A1A1AA]">Back to templates</a>

        @if ($errors->has('template'))
            <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 p-3 text-sm text-[#EF4444]">{{ $errors->first('template') }}</div>
        @endif
        @if (session('status'))
            <div class="rounded-xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 p-3 text-sm text-[#F59E0B]">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
            <div class="relative w-full overflow-hidden" style="aspect-ratio:16/7">
                @if($template->thumbnail_url)
                    <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }}" class="absolute inset-0 h-full w-full object-cover"
                         onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="absolute inset-0 items-center justify-center bg-[#11101C] text-sm text-[#94A3B8]" style="display:none">No image</div>
                @else
                    <div class="flex h-full w-full items-center justify-center bg-[#11101C] text-sm text-[#94A3B8]">No image</div>
                @endif
            </div>
            <div class="space-y-5 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h1 class="text-2xl font-black text-[#F8FAFC]">{{ $template->name }}</h1>
                        @if($template->short_description)
                            <p class="mt-2 max-w-3xl text-sm font-bold leading-6 text-[#D4D4D8]">{!! \App\Support\SafeTemplateText::inline($template->short_description) !!}</p>
                        @endif
                    </div>
                    <span class="rounded-full border border-[#22C55E]/30 bg-[#22C55E]/10 px-3 py-1 text-xs font-black text-[#22C55E]">Downloaded</span>
                </div>

                @if($template->description)
                    <div class="rounded-xl border border-[#27213D] bg-[#090713] p-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#7E7AA0]">About</p>
                        <div class="mt-3 space-y-3 text-sm leading-7 text-[#A1A1AA] [&_strong]:font-bold [&_strong]:text-white">
                            {!! \App\Support\SafeTemplateText::paragraphs($template->description) !!}
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap gap-2 text-xs font-bold text-[#94A3B8]">
                    <span class="rounded-lg border border-[#27213D] bg-[#151225] px-2.5 py-1.5">{{ $template->category ?: 'General' }}</span>
                    <span class="rounded-lg border border-[#27213D] bg-[#151225] px-2.5 py-1.5">{{ ucfirst($template->level) }}</span>
                    <span class="rounded-lg border border-[#27213D] bg-[#151225] px-2.5 py-1.5">{{ $template->commands_count }} commands</span>
                    @if($template->isPaid())<span class="rounded-lg border border-[#27213D] bg-[#151225] px-2.5 py-1.5">{{ $template->formatted_price }}</span>@endif
                    @if($template->includedPlanLabel())<span class="rounded-lg border border-[#8B5CF6]/30 bg-[#8B5CF6]/10 px-2.5 py-1.5 text-[#C4B5FD]">{{ $template->includedPlanLabel() }}</span>@endif
                </div>

                @if($template->demo_url)
                    <a href="{{ $template->demo_url }}" class="inline-flex rounded-xl border border-[#27213D] px-4 py-2 text-sm font-bold text-[#A1A1AA] transition hover:text-white" rel="noopener noreferrer" target="_blank">View Demo Bot</a>
                @endif
            </div>
        </div>

        @if($conflicts !== [])
            <div class="rounded-2xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 p-5 text-sm text-[#F59E0B]">
                This bot already has {{ count($conflicts) }} matching {{ Str::plural('command', count($conflicts)) }}.
            </div>
        @endif

        <form method="POST" action="{{ route('bots.templates.import', [$bot, $template]) }}" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            @csrf
            <label class="text-sm font-bold text-[#A1A1AA]">Conflict strategy</label>
            <div class="relative mt-2" x-data="{ open: false, val: 'skip', labels: { skip: 'Ignore duplicates', replace: 'Replace duplicates', replace_all: 'Replace all commands', cancel: 'Cancel' }, get label() { return this.labels[this.val] || 'Ignore duplicates' } }" @click.away="open = false">
                <input type="hidden" name="conflict_strategy" :value="val">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-3 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/20' : ''">
                    <span x-text="label"></span>
                    <svg class="ml-2 h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                    @foreach (['skip' => 'Ignore duplicates', 'replace' => 'Replace duplicates', 'replace_all' => 'Replace all commands', 'cancel' => 'Cancel'] as $csv => $csl)
                    <button type="button" @click="val = '{{ $csv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $csv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                        <svg :class="val === '{{ $csv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $csl }}
                    </button>
                    @endforeach
                </div>
            </div>
            <p class="mt-2 text-xs text-[#94A3B8]">Ignore duplicates imports only non-conflicting commands. Replace duplicates overwrites matching commands. <span class="text-[#EF4444]">Replace all commands deletes every existing command in this bot first, then imports fresh.</span></p>
            <button class="mt-4 rounded-xl bg-[#8B5CF6] px-5 py-3 text-sm font-black text-white">Import to This Bot</button>
        </form>

        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 text-sm text-[#A1A1AA]">
            This template includes {{ $template->commands_count }} commands. Command names and code become visible only after import into your bot workspace.
        </div>
    </div>
</x-dashboard-layout>
