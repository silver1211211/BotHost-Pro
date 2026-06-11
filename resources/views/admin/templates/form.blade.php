<x-admin-layout title="{{ $template->exists ? 'Edit Template' : 'Create Template' }}" subtitle="Command template management">
<div class="mx-auto max-w-3xl space-y-5">

    <a href="{{ route('admin.templates.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-[#A1A1AA] transition hover:text-white">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to templates
    </a>

    @if (session('status'))
        <div class="rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/10 px-4 py-3 text-sm text-[#22C55E]">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#EF4444] space-y-1">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    <form method="POST"
          action="{{ $template->exists ? route('admin.templates.update', $template) : route('admin.templates.store') }}"
          enctype="multipart/form-data"
          class="space-y-4">
        @csrf
        @if ($template->exists) @method('PATCH') @endif

        {{-- ── Section 1: Template Files ─────────────────── --}}
        <div class="overflow-hidden rounded-xl border border-[#27213D] bg-[#0F0D1A]">
            <div class="border-b border-[#27213D] px-4 py-3">
                <h2 class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">Template Files</h2>
            </div>
            <div class="grid gap-4 p-4 md:grid-cols-2">

                {{-- ZIP upload --}}
                <div class="flex flex-col gap-2">
                    <label class="flex cursor-pointer flex-col gap-2 rounded-xl border border-dashed border-[#27213D] bg-[#090713] p-4 text-sm transition hover:border-[#8B5CF6]/40">
                        <span class="font-bold text-[#A1A1AA]">
                            ZIP File
                            @if(! $template->exists)<span class="text-[#EF4444]">*</span>@endif
                        </span>
                        <input name="template_zip" type="file" accept=".zip,application/zip"
                               class="block w-full text-xs text-[#71717A] file:mr-3 file:rounded-lg file:border-0 file:bg-[#27213D] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-[#A1A1AA]">
                        <span class="text-xs text-[#71717A]">Upload the ZIP containing this template's commands. Commands are parsed automatically.</span>
                        @if($template->template_zip_path)
                            <span class="text-xs text-[#22C55E]">&#10003; ZIP already stored — upload to replace.</span>
                        @endif
                    </label>
                </div>

                {{-- Image upload --}}
                <div class="flex flex-col gap-2">
                    <label class="flex cursor-pointer flex-col gap-2 rounded-xl border border-dashed border-[#27213D] bg-[#090713] p-4 text-sm transition hover:border-[#8B5CF6]/40">
                        <span class="font-bold text-[#A1A1AA]">Thumbnail Image</span>
                        <input name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                               class="block w-full text-xs text-[#71717A] file:mr-3 file:rounded-lg file:border-0 file:bg-[#27213D] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-[#A1A1AA]">
                        <span class="text-xs text-[#71717A]">JPG, PNG or WebP · 1280 × 720 px recommended · Displayed on marketplace.</span>
                    </label>
                    @if($template->thumbnail_url)
                        <div class="relative overflow-hidden rounded-xl border border-[#27213D]" style="aspect-ratio:16/9">
                            <img src="{{ $template->thumbnail_url }}" alt="Current thumbnail"
                                 class="absolute inset-0 h-full w-full object-cover">
                        </div>
                        <p class="text-[11px] text-[#4D4868]">Current thumbnail — upload to replace.</p>
                    @endif
                </div>

            </div>
        </div>

        {{-- ── Section 2: Basic Information ──────────────── --}}
        <div class="overflow-hidden rounded-xl border border-[#27213D] bg-[#0F0D1A]">
            <div class="border-b border-[#27213D] px-4 py-3">
                <h2 class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">Basic Information</h2>
            </div>
            <div class="space-y-3 p-4">
                <input name="name" value="{{ old('name', $template->name) }}" placeholder="Template name *"
                       class="w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white placeholder:text-[#4D4868] focus:border-[#8B5CF6]/50 focus:outline-none">
                <textarea name="description" rows="3" maxlength="3000" placeholder="Description"
                          class="w-full resize-none rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white placeholder:text-[#4D4868] focus:border-[#8B5CF6]/50 focus:outline-none">{{ old('description', $template->description) }}</textarea>
                <input name="demo_url" value="{{ old('demo_url', $template->demo_url) }}" placeholder="Demo / sample bot URL"
                       class="w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white placeholder:text-[#4D4868] focus:border-[#8B5CF6]/50 focus:outline-none">
            </div>
        </div>

        {{-- ── Section 3: Marketplace Settings ──────────── --}}
        {{-- overflow-visible so the category dropdown is not clipped --}}
        <div class="rounded-xl border border-[#27213D] bg-[#0F0D1A]">
            <div class="rounded-t-xl border-b border-[#27213D] px-4 py-3">
                <h2 class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">Marketplace Settings</h2>
            </div>
            <div class="space-y-3 p-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    {{-- Searchable category dropdown --}}
                    <div class="relative"
                         x-data="{
                             open: false,
                             search: '',
                             selected: window._adminCatInit || '',
                             get displayLabel() {
                                 return (window._adminCats && window._adminCats[this.selected]) ? window._adminCats[this.selected] : (this.selected || '');
                             },
                             get filtered() {
                                 var entries = window._adminCats ? Object.entries(window._adminCats) : [];
                                 if (!this.search) return entries;
                                 var s = this.search.toLowerCase();
                                 return entries.filter(function(e) { return e[1].toLowerCase().indexOf(s) !== -1; });
                             },
                             select: function(slug) {
                                 this.selected = slug;
                                 this.open = false;
                                 this.search = '';
                             }
                         }"
                         @click.outside="open = false"
                         @keydown.escape="open = false">
                        <input type="hidden" name="category" :value="selected">
                        <button type="button"
                                @click="open = !open; if (open) $nextTick(function() { $refs.catSearch && $refs.catSearch.focus(); })"
                                class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-left text-sm focus:border-[#8B5CF6]/50 focus:outline-none"
                                :class="selected ? 'text-white' : 'text-[#4D4868]'">
                            <span x-text="displayLabel || 'Category'"></span>
                            <svg class="h-4 w-4 shrink-0 text-[#71717A] transition-transform duration-150"
                                 :class="{ 'rotate-180': open }"
                                 fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute left-0 right-0 top-full z-[200] mt-1 overflow-hidden rounded-xl border border-[#27213D] bg-[#090713] shadow-2xl">
                            <div class="p-2">
                                <input x-ref="catSearch"
                                       x-model="search"
                                       type="text"
                                       placeholder="Search categories…"
                                       @click.stop
                                       class="w-full rounded-lg border border-[#27213D] bg-[#11101C] px-3 py-2 text-sm text-white placeholder:text-[#4D4868] focus:border-[#8B5CF6]/50 focus:outline-none">
                            </div>
                            <div style="max-height:200px;overflow-y:auto;">
                                <button type="button"
                                        @click="select('')"
                                        class="block w-full px-3 py-2 text-left text-sm text-[#71717A] transition hover:bg-[#11101C]">
                                    — No category —
                                </button>
                                <template x-for="entry in filtered" :key="entry[0]">
                                    <button type="button"
                                            @click="select(entry[0])"
                                            class="block w-full px-3 py-2 text-left text-sm transition hover:bg-[#11101C]"
                                            :class="selected === entry[0] ? 'text-[#8B5CF6] font-bold' : 'text-[#A1A1AA]'"
                                            x-text="entry[1]">
                                    </button>
                                </template>
                                <p x-show="filtered.length === 0"
                                   class="px-3 py-4 text-center text-xs text-[#71717A]">
                                    No categories found
                                </p>
                            </div>
                        </div>
                    </div>
                    @php $tmLevelVal = old('level', $template->level ?: 'beginner'); @endphp
                    <div class="relative" x-data="{ open: false, val: '{{ $tmLevelVal }}', labels: @js(array_combine(\App\Models\BotTemplate::LEVELS, array_map('ucfirst', \App\Models\BotTemplate::LEVELS))), get label() { return this.labels[this.val] || '{{ ucfirst($tmLevelVal) }}' } }" @click.away="open = false">
                        <input type="hidden" name="level" :value="val">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/15' : ''">
                            <span x-text="label"></span>
                            <svg class="ml-2 h-3.5 w-3.5 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                            @foreach (\App\Models\BotTemplate::LEVELS as $lv)
                            <button type="button" @click="val = '{{ $lv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $lv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                <svg :class="val === '{{ $lv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                {{ ucfirst($lv) }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @php $tmStatusVal = old('status', $template->status ?: 'draft'); @endphp
                    <div class="relative" x-data="{ open: false, val: '{{ $tmStatusVal }}', labels: @js(array_combine(\App\Models\BotTemplate::STATUSES, array_map('ucfirst', \App\Models\BotTemplate::STATUSES))), get label() { return this.labels[this.val] || '{{ ucfirst($tmStatusVal) }}' } }" @click.away="open = false">
                        <input type="hidden" name="status" :value="val">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/15' : ''">
                            <span x-text="label"></span>
                            <svg class="ml-2 h-3.5 w-3.5 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                            @foreach (\App\Models\BotTemplate::STATUSES as $sv)
                            <button type="button" @click="val = '{{ $sv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $sv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                <svg :class="val === '{{ $sv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                {{ ucfirst($sv) }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @php $tmMktVal = old('marketplace_status', $template->marketplace_status ?: 'unlisted'); @endphp
                    <div class="relative" x-data="{ open: false, val: '{{ $tmMktVal }}', labels: { 'unlisted': 'Unlisted', 'listed': 'Listed', 'featured': 'Featured', 'archived': 'Archived' }, get label() { return this.labels[this.val] || 'Unlisted' } }" @click.away="open = false">
                        <input type="hidden" name="marketplace_status" :value="val">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/15' : ''">
                            <span x-text="label"></span>
                            <svg class="ml-2 h-3.5 w-3.5 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                            @foreach (['unlisted' => 'Unlisted', 'listed' => 'Listed', 'featured' => 'Featured', 'archived' => 'Archived'] as $mkv => $mkl)
                            <button type="button" @click="val = '{{ $mkv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $mkv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                <svg :class="val === '{{ $mkv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                {{ $mkl }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <label class="flex cursor-pointer select-none items-center gap-2 text-sm text-[#A1A1AA]">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $template->is_featured))
                           class="h-4 w-4 rounded border-[#27213D] bg-[#090713] accent-[#8B5CF6]">
                    Featured
                    <span class="text-xs text-[#71717A]">— highlighted on the marketplace</span>
                </label>
            </div>
        </div>

        {{-- ── Section 4: Pricing & Access ───────────────── --}}
        <div class="overflow-hidden rounded-xl border border-[#27213D] bg-[#0F0D1A]">
            <div class="border-b border-[#27213D] px-4 py-3">
                <h2 class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">Pricing & Access</h2>
            </div>
            <div class="space-y-3 p-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    @php $tmAccessVal = old('access_type', $template->access_type ?: 'free'); @endphp
                    <div class="relative" x-data="{ open: false, val: '{{ $tmAccessVal }}', get label() { return { 'free': 'Free', 'paid': 'Paid' }[this.val] || 'Free' } }" @click.away="open = false">
                        <input type="hidden" name="access_type" :value="val">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/15' : ''">
                            <span x-text="label"></span>
                            <svg class="ml-2 h-3.5 w-3.5 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                            @foreach (['free' => 'Free', 'paid' => 'Paid'] as $atv => $atl)
                            <button type="button" @click="val = '{{ $atv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $atv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                <svg :class="val === '{{ $atv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                {{ $atl }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @php $tmPlanVal = old('included_plan', $template->included_plan) ?? ''; @endphp
                    <div class="relative" x-data="{ open: false, val: '{{ $tmPlanVal }}', labels: { '': 'No plan inclusion', 'free': 'Included for Everyone', 'pro': 'Included for Pro &amp; Business', 'business': 'Included for Business' }, get label() { return this.labels[this.val] ?? 'No plan inclusion' } }" @click.away="open = false">
                        <input type="hidden" name="included_plan" :value="val">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/15' : ''">
                            <span x-text="label" class="truncate text-left"></span>
                            <svg class="ml-2 h-3.5 w-3.5 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                            @foreach (['' => 'No plan inclusion', 'free' => 'Included for Everyone', 'pro' => 'Included for Pro & Business', 'business' => 'Included for Business'] as $ipv => $ipl)
                            <button type="button" @click="val = '{{ $ipv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $ipv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                <svg :class="val === '{{ $ipv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                {{ $ipl }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input name="price" type="number" step="0.01" min="0"
                           value="{{ old('price', $template->price ?? 0) }}" placeholder="Price"
                           class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white placeholder:text-[#4D4868] focus:border-[#8B5CF6]/50 focus:outline-none">
                    <input name="currency" value="{{ old('currency', $template->currency ?: 'USD') }}" placeholder="USD"
                           class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white placeholder:text-[#4D4868] focus:border-[#8B5CF6]/50 focus:outline-none">
                </div>
                <p class="text-xs text-[#71717A]">Plan users can unlock this template without payment.</p>
            </div>
        </div>

        <button class="w-full sm:w-auto rounded-xl bg-[#8B5CF6] px-6 py-2.5 text-sm font-black text-white transition hover:bg-[#7C3AED]">
            {{ $template->exists ? 'Save Template' : 'Create Template' }}
        </button>

    </form>

    {{-- ── Extracted ZIP Commands (edit only) ───────────── --}}
    @if ($template->exists)
        <section class="overflow-hidden rounded-xl border border-[#27213D]">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#27213D] bg-[#0F0D1A] px-4 py-3">
                <div>
                    <h2 class="text-sm font-black">Extracted ZIP Commands</h2>
                    <p class="mt-0.5 text-xs text-[#71717A]">Commands come from the uploaded ZIP. Re-upload to refresh.</p>
                </div>
                @php($summary = $template->metadata['zip_parse'] ?? null)
                @if($summary)
                    <span class="text-xs text-[#A1A1AA]">{{ $summary['imported'] ?? 0 }} imported · {{ $summary['skipped'] ?? 0 }} skipped</span>
                @endif
            </div>
            <div class="divide-y divide-[#27213D] bg-[#0F0D1A]">
                @forelse ($template->commands as $command)
                    <div class="px-4 py-3">
                        <p class="font-mono text-sm font-bold text-[#A855F7]">{{ $command->command_name }}</p>
                        <p class="mt-0.5 text-xs text-[#71717A]">
                            {{ $command->folder ?: 'No folder' }} &middot; {{ $command->status }} &middot; {{ filled($command->code) ? 'code' : 'text' }}
                        </p>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-[#71717A]">No commands extracted yet.</p>
                @endforelse
            </div>
        </section>
    @endif

</div>

<script>
(function () {
    // Full category map: slug => label — set before Alpine initialises
    window._adminCats = @json($adminCategories);

    // Determine initial slug from whatever is stored (slug or legacy label)
    var stored = @json(old('category', $template->category ?? ''));
    if (!stored) {
        window._adminCatInit = '';
    } else if (window._adminCats[stored] !== undefined) {
        // Already a valid slug
        window._adminCatInit = stored;
    } else {
        // Legacy: stored value might be a human-readable label — find its slug
        var found = Object.entries(window._adminCats).find(function (e) { return e[1] === stored; });
        window._adminCatInit = found ? found[0] : stored;
    }
})();
</script>
</x-admin-layout>
