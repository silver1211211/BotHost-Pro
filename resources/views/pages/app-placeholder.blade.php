<x-dashboard-layout :title="$title">
    <div class="mx-auto max-w-6xl">
        <div class="rounded-3xl border border-[#27213D] bg-[#0F0D1A] p-8 shadow-[0_30px_90px_rgba(0,0,0,0.28)]">
            <p class="text-sm font-black uppercase tracking-[0.22em] text-[#38BDF8]">BotHost Pro</p>
            <h2 class="mt-3 text-3xl font-black text-[#F8FAFC] sm:text-5xl">{{ $title }}</h2>
            <p class="mt-4 max-w-3xl text-[#A1A1AA]">{{ $description }}</p>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @if (($kind ?? '') === 'recycle')
                <x-placeholder-card title="Deleted bots" text="Deleted bots will appear here before permanent removal." badge="Empty" />
            @elseif (($kind ?? '') === 'transfers')
                <x-placeholder-card title="Incoming transfers" text="Bots shared with your account will be listed here." badge="Coming Soon" />
                <x-placeholder-card title="Outgoing transfers" text="Transfer requests you create will be tracked here." badge="Coming Soon" />
            @elseif (($kind ?? '') === 'notifications')
                @foreach ([['Bot paused', 'Runtime status notifications will appear here.'], ['Command added', 'Command change events will be tracked here.'], ['Token updated', 'Security-sensitive updates will be listed here.'], ['Security alert', 'Future account protection alerts will appear here.']] as [$cardTitle, $text])
                    <x-placeholder-card :title="$cardTitle" :text="$text" badge="Preview" />
                @endforeach
            @elseif (($kind ?? '') === 'help')
                @foreach ([['Create a bot', 'Add your BotFather token and create a custom workspace.'], ['Add commands', 'Use exact command triggers and response text.'], ['Manage settings', 'Update tokens, names, and future runtime preferences.']] as [$cardTitle, $text])
                    <x-placeholder-card :title="$cardTitle" :text="$text" badge="Guide" />
                @endforeach
            @elseif (($kind ?? '') === 'support')
                @foreach ([['Telegram Community', 'Community CTA placeholder for creators.'], ['Contact Support', 'Direct support workflow will be added later.'], ['Documentation', 'Product docs and tutorials will live here.']] as [$cardTitle, $text])
                    <x-placeholder-card :title="$cardTitle" :text="$text" badge="Support" />
                @endforeach
            @elseif (($kind ?? '') === 'ai')
                @foreach ([['Explain errors', 'AI assistant will help explain runtime errors later.'], ['Generate command replies', 'Draft command responses from prompts.'], ['Review code', 'Review bot code and detect unsafe patterns.']] as [$cardTitle, $text])
                    <x-placeholder-card :title="$cardTitle" :text="$text" badge="AI Coming Soon" />
                @endforeach
            @endif
        </div>
    </div>
</x-dashboard-layout>
