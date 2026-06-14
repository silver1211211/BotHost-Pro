<x-dashboard-layout title="AI Help">

<div class="space-y-6">

    {{-- ── Hero coming-soon card ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-[#8B5CF6]/20 bg-[#0F0D1A] p-8 sm:p-10">

        {{-- Background glow --}}
        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[#8B5CF6]/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-[#38BDF8]/8 blur-3xl"></div>

        <div class="relative">
            {{-- Badge --}}
            <span class="inline-flex items-center gap-1.5 rounded-full border border-[#8B5CF6]/30 bg-[#8B5CF6]/10 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-[#8B5CF6]">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#8B5CF6]"></span>
                Coming Soon
            </span>

            {{-- Icon --}}
            <div class="mt-6 mb-5 grid h-14 w-14 place-items-center rounded-2xl border border-[#8B5CF6]/30 bg-gradient-to-br from-[#8B5CF6]/20 to-[#38BDF8]/10">
                <svg class="h-7 w-7 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636-.707.707M21 12h-1M4 12H3m3.343-5.657-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-black text-[#F8FAFC] sm:text-3xl">Smart Bot Assistant</h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-[#94A3B8]">
                We are preparing an AI assistant to help you build, debug, and improve your bot workflows — directly inside BotHost Pro.
            </p>

            <div class="mt-6 inline-flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5 text-xs font-semibold text-[#94A3B8]">
                <svg class="h-3.5 w-3.5 text-[#52525B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Stay tuned — launching soon
            </div>
        </div>
    </div>

    {{-- ── Feature preview grid ── --}}
    <div>
        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-[#52525B]">What AI Help will do</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach([
                [
                    'Explain Errors',
                    'Paste an error message and the AI will explain what went wrong and how to fix it in plain language.',
                    '#8B5CF6',
                    'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
                ],
                [
                    'Generate Responses',
                    'Describe what you want your bot to say and the AI will draft a command response for you to review and use.',
                    '#38BDF8',
                    'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z',
                ],
                [
                    'Bot Logic Help',
                    'Ask for help with building command flows, handling edge cases, or structuring your bot workspace more effectively.',
                    '#22C55E',
                    'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                ],
                [
                    'Quick Answers',
                    'Ask any question about BotHost Pro features, platform capabilities, or best practices and get a clear answer instantly.',
                    '#F59E0B',
                    'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
                ],
                [
                    'Review Bot Setup',
                    'Share your bot configuration and get AI-powered suggestions to improve responses, performance, and user experience.',
                    '#A855F7',
                    'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                ],
                [
                    'Workflow Ideas',
                    'Describe your use case and get ideas for how to structure bot commands, trigger flows, and automate tasks.',
                    '#38BDF8',
                    'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
                ],
            ] as [$title, $desc, $color, $icon])
            <div class="group rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 transition hover:border-[#27213D]/80">
                <div class="mb-3.5 h-9 w-9 rounded-xl flex items-center justify-center" style="background-color:{{ $color }}14">
                    <svg style="height:16px;width:16px;color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        @foreach(explode(' ', $icon) as $seg)
                            @if(str_starts_with($seg, 'M') || str_starts_with($seg, 'm'))
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                            @break
                            @endif
                        @endforeach
                    </svg>
                </div>
                <p class="text-sm font-black text-[#F8FAFC]">{{ $title }}</p>
                <p class="mt-2 text-xs leading-relaxed text-[#94A3B8]">{{ $desc }}</p>
                <span class="mt-3 inline-block rounded-full border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[9px] font-black uppercase tracking-wide text-[#52525B]">Coming Soon</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Stay tuned footer card ── --}}
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-6 text-center">
        <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-xl border border-[#8B5CF6]/20 bg-[#8B5CF6]/8">
            <svg class="h-5 w-5 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/></svg>
        </div>
        <p class="text-sm font-black text-[#F8FAFC]">We are working on something powerful</p>
        <p class="mt-1.5 text-xs text-[#94A3B8]">The AI assistant is being built to deeply understand BotHost Pro, your bots, and your workflows. Stay tuned for updates.</p>
    </div>

</div>

</x-dashboard-layout>
