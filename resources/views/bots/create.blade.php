<x-dashboard-layout title="Create New Bot">
<div class="mx-auto max-w-5xl">

    {{-- Back link --}}
    <a href="{{ route('bots.index') }}" class="inline-flex items-center gap-2 text-sm font-black text-[#94A3B8] transition hover:text-[#F8FAFC]">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
        Back to My Bots
    </a>

    {{-- ── Page header ── --}}
    <div class="mt-6 mb-6">
        <h1 class="text-2xl font-black text-[#F8FAFC]">Create New Bot</h1>
        <p class="mt-1 text-sm text-[#94A3B8]">Connect your Telegram bot and start building your workspace.</p>
    </div>

    {{-- ── Plan limit alert ── --}}
    @if($limitReached)
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-[#EF4444]/25 bg-[#EF4444]/6 p-4">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#EF4444]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
        <div class="flex-1">
            <p class="text-sm font-black text-[#F8FAFC]">Bot limit reached</p>
            <p class="mt-0.5 text-xs text-[#A1A1AA]">
                You have used {{ $botsUsed }} of {{ $botsLimit === 'unlimited' ? 'Unlimited' : $botsLimit }} bots on your current plan. Upgrade to create more bots.
            </p>
        </div>
        <a href="{{ route('billing.index') }}" class="shrink-0 rounded-xl bg-[#EF4444] px-4 py-2 text-xs font-black text-white transition hover:bg-red-400">
            Upgrade Plan
        </a>
    </div>
    @endif

    {{-- ── Main layout: form + sidebar ── --}}
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start">

        {{-- ── Form card ── --}}
        <div class="flex-1 overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">

            {{-- Card header --}}
            <div class="relative overflow-hidden border-b border-[#27213D] px-6 py-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-[#8B5CF6]/10 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-8 -left-4 h-28 w-28 rounded-full bg-[#38BDF8]/6 blur-3xl"></div>
                <div class="relative flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-[#8B5CF6] to-[#A855F7]">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-[#F8FAFC]">Bot Details</p>
                        <p class="text-xs text-[#94A3B8]">Fill in the fields below to connect your bot.</p>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <form
                method="POST"
                action="{{ route('bots.store') }}"
                novalidate
                x-data="{
                    e: { name: '', token: '' },
                    focusEl(id) {
                        this.$nextTick(() => {
                            const el = document.getElementById(id);
                            if (el) { el.focus(); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                        });
                    },
                    validate() {
                        this.e = { name: '', token: '' };
                        const n = (document.getElementById('name')?.value || '').trim();
                        const t = (document.getElementById('token')?.value || '').trim();
                        if (!n) { this.e.name = 'Enter a name for your workspace.'; this.focusEl('name'); return false; }
                        if (!t) { this.e.token = 'Paste your Telegram bot token from BotFather.'; this.focusEl('token'); return false; }
                        return true;
                    }
                }"
                @submit="if (!validate()) $event.preventDefault()"
                class="p-6 space-y-5"
            >
                @csrf

                {{-- Bot Name --}}
                <div>
                    <label for="name" class="text-xs font-black uppercase tracking-wider text-[#94A3B8]">Workspace Name</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        maxlength="100"
                        {{ $limitReached ? 'disabled' : '' }}
                        @input="e.name = ''"
                        class="mt-2 w-full rounded-xl border bg-[#0B0918] px-4 py-3 text-sm text-[#F8FAFC] outline-none transition placeholder:text-[#52525B] focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="e.name ? 'border-[#EF4444]/50 focus:border-[#EF4444]/70 focus:ring-[#EF4444]/10' : 'border-[#27213D] focus:border-[#8B5CF6]/60 focus:ring-[#8B5CF6]/20'"
                        placeholder="My Telegram Bot"
                    >
                    <p
                        x-show="e.name"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="mt-1.5 flex items-center gap-1.5 text-[11px] font-semibold text-[#F87171]"
                    >
                        <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                        <span x-text="e.name"></span>
                    </p>
                    <p x-show="!e.name" class="mt-1.5 text-[11px] text-[#52525B]">This name helps you identify the bot inside your workspace.</p>
                    @error('name')
                        <p class="mt-1.5 text-xs font-bold text-[#EF4444]">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Bot Token --}}
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <label for="token" class="text-xs font-black uppercase tracking-wider text-[#94A3B8]">Telegram Bot Token</label>
                    </div>
                    <div class="relative mt-2">
                        <input
                            id="token"
                            name="token"
                            type="password"
                            value=""
                            autocomplete="new-password"
                            spellcheck="false"
                            autocapitalize="off"
                            {{ $limitReached ? 'disabled' : '' }}
                            @input="e.token = ''"
                            class="w-full rounded-xl border bg-[#0B0918] py-3 pl-4 pr-10 text-sm text-[#F8FAFC] outline-none transition placeholder:text-[#52525B] focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="e.token ? 'border-[#EF4444]/50 focus:border-[#EF4444]/70 focus:ring-[#EF4444]/10' : 'border-[#27213D] focus:border-[#8B5CF6]/60 focus:ring-[#8B5CF6]/20'"
                            placeholder="CHANGE_ME_TELEGRAM_BOT_TOKEN"
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-3.5 flex items-center text-[#52525B]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        </span>
                    </div>
                    <p
                        x-show="e.token"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="mt-1.5 flex items-center gap-1.5 text-[11px] font-semibold text-[#F87171]"
                    >
                        <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                        <span x-text="e.token"></span>
                    </p>
                    <p x-show="!e.token" class="mt-1.5 text-[11px] text-[#52525B]">Paste the token you received from BotFather.</p>
                    @error('token')
                        <p class="mt-1.5 text-xs font-bold text-[#EF4444]">{{ $message }}</p>
                    @enderror

                    {{-- Security note --}}
                    <div class="mt-3 flex items-start gap-2 rounded-xl border border-[#27213D] bg-[#0B0918] px-3 py-2.5">
                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                        <p class="text-[11px] text-[#94A3B8]">Your token is encrypted and never shown publicly after saving.</p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col-reverse gap-3 border-t border-[#27213D] pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <a
                        href="{{ route('bots.index') }}"
                        class="flex items-center justify-center rounded-xl border border-[#27213D] bg-[#151225] px-5 py-2.5 text-sm font-black text-[#94A3B8] transition hover:text-[#A1A1AA] sm:w-auto"
                    >
                        Cancel
                    </a>

                    @if($limitReached)
                        <a
                            href="{{ route('billing.index') }}"
                            class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#EF4444] to-[#F87171] px-6 py-2.5 text-sm font-black text-white transition hover:-translate-y-0.5"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Upgrade Plan
                        </a>
                    @else
                        <button
                            type="submit"
                            class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-6 py-2.5 text-sm font-black text-white shadow-[0_0_24px_rgba(139,92,246,0.25)] transition hover:-translate-y-0.5 hover:shadow-[0_0_32px_rgba(139,92,246,0.35)]"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            Create Bot
                        </button>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── Sidebar ── --}}
        <div class="space-y-4 lg:w-72 lg:shrink-0">

            {{-- Plan usage --}}
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Bot Usage</p>
                <div class="flex items-end justify-between gap-2">
                    <p class="text-2xl font-black text-[#F8FAFC]">{{ $botsUsed }}</p>
                    <p class="mb-1 text-sm text-[#52525B]">/ {{ $botsLimit === 'unlimited' ? 'Unlimited' : $botsLimit }} bots</p>
                </div>
                @if($botsLimit !== 'unlimited')
                @php $pct = $botsLimit > 0 ? min(100, (int) round($botsUsed / $botsLimit * 100)) : 0; @endphp
                <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-[#151225]">
                    <div
                        class="h-full rounded-full transition-all"
                        style="width:{{ $pct }}%; background: {{ $pct >= 100 ? '#EF4444' : ($pct >= 75 ? '#F59E0B' : '#8B5CF6') }}"
                    ></div>
                </div>
                @endif
                <p class="mt-2 text-[11px] text-[#52525B]">
                    @if($limitReached)
                        Limit reached. <a href="{{ route('billing.index') }}" class="text-[#8B5CF6] hover:underline">Upgrade</a> to add more bots.
                    @else
                        {{ $botsLimit === 'unlimited' ? 'Unlimited bots on your plan.' : ($botsLimit - $botsUsed) . ' slot' . (($botsLimit - $botsUsed) === 1 ? '' : 's') . ' remaining.' }}
                    @endif
                </p>
            </div>

            {{-- How to get a token --}}
            <div
                x-data="{ open: false }"
                class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] overflow-hidden"
            >
                <button
                    @click="open = !open"
                    class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="grid h-7 w-7 place-items-center rounded-lg bg-[#8B5CF6]/15">
                            <svg class="h-3.5 w-3.5 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                        </div>
                        <p class="text-xs font-black text-[#A1A1AA]">How to get a bot token?</p>
                    </div>
                    <svg
                        :class="open ? 'rotate-180' : ''"
                        class="h-4 w-4 shrink-0 text-[#52525B] transition-transform duration-200"
                        fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                    ><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-cloak
                    class="border-t border-[#27213D] px-4 pb-4 pt-3"
                >
                    <ol class="space-y-2.5">
                        @foreach([
                            'Open Telegram on your phone or desktop.',
                            'Search for <strong class="text-[#A1A1AA]">@BotFather</strong> and open the chat.',
                            'Send <code class="rounded bg-[#151225] px-1 py-0.5 text-[#8B5CF6]">/newbot</code> and follow the prompts.',
                            'Choose a name and a username ending in <code class="rounded bg-[#151225] px-1 py-0.5 text-[#8B5CF6]">bot</code>.',
                            'BotFather will send you the token. Copy it and paste it in the field above.',
                        ] as $i => $step)
                        <li class="flex items-start gap-2.5">
                            <span class="mt-0.5 grid h-4 w-4 shrink-0 place-items-center rounded-full bg-[#8B5CF6]/20 text-[9px] font-black text-[#8B5CF6]">{{ $i + 1 }}</span>
                            <p class="text-[11px] leading-relaxed text-[#94A3B8]">{!! $step !!}</p>
                        </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            {{-- Tips --}}
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Good to know</p>
                <div class="space-y-3">
                    @foreach([
                        ['#22C55E', 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z', 'Token stays private — encrypted and never exposed after saving.'],
                        ['#38BDF8', 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z', 'You can import marketplace templates after creating your bot.'],
                        ['#8B5CF6', 'M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18', 'Each bot gets its own workspace with commands, logs, and settings.'],
                    ] as [$color, $icon, $text])
                    <div class="flex items-start gap-2.5">
                        <div class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-lg" style="background-color:{{ $color }}18">
                            <svg style="height:10px;width:10px;color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        </div>
                        <p class="text-[11px] leading-relaxed text-[#94A3B8]">{{ $text }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>{{-- /sidebar --}}

    </div>{{-- /layout --}}

</div>
</x-dashboard-layout>
