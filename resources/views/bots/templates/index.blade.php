<x-dashboard-layout title="My Unlocked Templates">
    <div class="mx-auto max-w-5xl space-y-5">
        <a href="{{ route('bots.show', ['bot' => $bot, 'tab' => 'commands']) }}" class="text-sm font-semibold text-[#A1A1AA]">Back to {{ $bot->name }}</a>

        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <h1 class="text-2xl font-black text-[#F8FAFC]">My Unlocked Templates</h1>
            <p class="mt-1 text-sm text-[#94A3B8]">Choose a template you already own and import it into {{ $bot->name }}.</p>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 md:grid-cols-4">
            <input name="search" value="{{ request('search') }}" placeholder="Search unlocked templates" class="rounded-xl border border-[#27213D] bg-[#11101C] p-3 text-sm text-white placeholder:text-[#4D4868]">
            <select name="category" class="rounded-xl border border-[#27213D] bg-[#11101C] p-3 text-sm text-[#A1A1AA]">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <select name="level" class="rounded-xl border border-[#27213D] bg-[#11101C] p-3 text-sm text-[#A1A1AA]">
                <option value="">All levels</option>
                @foreach (\App\Models\BotTemplate::LEVELS as $level)
                    <option value="{{ $level }}" @selected(request('level') === $level)>{{ ucfirst($level) }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-[#8B5CF6] px-4 py-3 text-sm font-black text-white">Filter</button>
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($templates as $template)
                <div class="flex h-full flex-col rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                    @if($template->thumbnail_url)
                        <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }}" class="mb-4 h-32 w-full rounded-xl object-cover">
                    @else
                        <div class="mb-4 flex h-32 w-full items-center justify-center rounded-xl border border-[#27213D] bg-[#11101C] text-sm text-[#94A3B8]">No image</div>
                    @endif

                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-black text-[#F8FAFC]">{{ $template->name }}</h2>
                            <p class="mt-1 line-clamp-2 text-sm text-[#94A3B8]">{!! \App\Support\SafeTemplateText::inline(\Illuminate\Support\Str::limit($template->short_description ?: $template->description, 140)) !!}</p>
                        </div>
                        <span class="shrink-0 rounded-full border border-[#22C55E]/30 bg-[#22C55E]/10 px-2 py-1 text-[10px] font-black text-[#22C55E]">Unlocked</span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2 text-[10px] text-[#A1A1AA]">
                        <span>{{ $template->category ?: 'General' }}</span>
                        <span>{{ ucfirst($template->level) }}</span>
                        <span>{{ $template->commands_count }} commands</span>
                        @if($template->isPaid())<span>{{ $template->formatted_price }}</span>@endif
                        @if($template->includedPlanLabel())<span>{{ $template->includedPlanLabel() }}</span>@endif
                    </div>

                    @if ($template->tags)
                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach ($template->tags as $tag)
                                <span class="rounded border border-[#27213D] px-2 py-0.5 text-[10px] text-[#94A3B8]">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-auto flex flex-wrap gap-2 pt-4">
                        <a href="{{ route('bots.templates.show', [$bot, $template]) }}" class="rounded-xl border border-[#27213D] px-4 py-2 text-sm font-bold text-[#A1A1AA] transition hover:text-white">Preview</a>
                        <form method="POST" action="{{ route('bots.templates.import', [$bot, $template]) }}">
                            @csrf
                            <button class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#7C3AED]">Import into this bot</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-8 text-center md:col-span-2">
                    <p class="text-lg font-black text-[#F8FAFC]">No unlocked templates yet</p>
                    <p class="mt-2 text-sm text-[#94A3B8]">Visit Marketplace to unlock templates first.</p>
                    <a href="{{ route('dashboard.templates.index') }}" class="mt-4 inline-flex rounded-xl bg-[#8B5CF6] px-5 py-3 text-sm font-black text-white transition hover:bg-[#7C3AED]">Visit Marketplace</a>
                </div>
            @endforelse
        </div>

        {{ $templates->links() }}
    </div>
</x-dashboard-layout>
