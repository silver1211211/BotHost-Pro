@php
    $lang = strtolower($bot->language ?? 'javascript');
    $editorLang = match ($lang) {
        'node', 'nodejs' => 'javascript',
        'python' => 'python',
        'json' => 'json',
        'typescript' => 'typescript',
        default => 'javascript',
    };
    $langLabel = match ($editorLang) {
        'python' => 'Python',
        'json' => 'JSON',
        'typescript' => 'TypeScript',
        default => 'JavaScript',
    };
    $backUrl = route('bots.show', ['bot' => $bot, 'tab' => 'commands']);
    $saveUrl = route('bots.commands.code.update', [$bot, $command]);
    $fileExt = match ($editorLang) {
        'python' => 'py',
        'json' => 'json',
        'typescript' => 'ts',
        default => 'js',
    };
@endphp

<x-dashboard-layout :title="'Code · '.$command->displayName()">
<div
    x-data="commandCodeEditor({
        code: @js($command->code ?? ''),
        language: @js($editorLang),
        action: @js($saveUrl),
        method: 'PUT',
        csrf: @js(csrf_token()),
        closeUrl: @js($backUrl),
    })"
    x-on:keydown.escape.window="editorDialogOpen ? cancelEditorDialog() : (helpersOpen ? helpersOpen = false : (searchOpen ? closeSearch() : closeEditor()))"
    :class="fullscreen ? 'fixed inset-0 z-[9998] m-0 bg-[#050509]' : '-mx-4 -mt-5 sm:-mx-5'"
    :style="fullscreen ? 'height: 100dvh;' : 'height: calc(100dvh - 54px);'"
    class="command-code-editor-shell code-page-prepaint flex min-w-0 flex-col overflow-hidden overscroll-none bg-[#050509] text-[#F8FAFC]"
>
    <div class="relative z-30 flex h-14 shrink-0 items-center gap-2 border-b border-[#242424] bg-[#1b1b1b] px-3">
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center text-[#7db7ff]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m17.25 6.75 4.5 4.5-4.5 4.5M6.75 6.75l-4.5 4.5 4.5 4.5m7.5-12-4.5 16.5"/></svg>
            </span>
            <div class="min-w-0">
                <div class="truncate text-sm font-black sm:text-base">{{ $command->displayName() }}</div>
                <div class="truncate font-mono text-[11px] text-[#8B8B8B]">{{ Str::slug($command->displayName()) ?: 'command' }}.{{ $fileExt }}</div>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <a href="{{ $backUrl }}"
               @click.prevent="confirmLeave(@js($backUrl))"
               class="inline-flex h-10 items-center gap-2 rounded-md bg-[#2b2b2b] px-3 text-sm font-bold text-[#E5E7EB] transition hover:bg-[#353535]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                <span class="hidden sm:inline">Back</span>
            </a>
            <button type="button"
                    @click="submitSave()"
                    class="inline-flex h-10 items-center gap-2 rounded-md bg-[#2f6fed] px-3 text-sm font-black text-white transition hover:bg-[#3b7cff] disabled:cursor-wait disabled:opacity-70"
                    :disabled="saving">
                <svg x-show="!saving" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10.5 18.75 19.5 5.25"/></svg>
                <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992M2.985 19.644v-4.992h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                <span x-text="saving ? 'Saving...' : 'Save'"></span>
            </button>
        </div>
    </div>

    <div class="relative z-20 flex h-12 shrink-0 items-center gap-1 overflow-x-auto border-b border-[#2d2d2d] bg-[#202020] px-3" style="scrollbar-width:none">
        <button type="button" @click.prevent.stop="undo()" title="Undo" class="editor-tool-btn">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
        </button>
        <button type="button" @click.prevent.stop="redo()" title="Redo" class="editor-tool-btn">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 15 6-6m0 0-6-6m6 6H9a6 6 0 0 0 0 12h3"/></svg>
        </button>
        <span class="mx-1 h-5 w-px shrink-0 bg-[#3a3a3a]"></span>
        <button type="button" @click.prevent.stop="copyCode()" title="Copy all code" class="editor-tool-btn" :style="copied ? 'background:#16A34A !important;color:#FFFFFF !important;box-shadow:0 0 0 2px rgba(34,197,94,0.45),0 0 26px rgba(34,197,94,0.62);' : ''">
            <svg x-show="!copied" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 8h10v13H8zM6 16H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            <svg x-show="copied" x-cloak class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        </button>
        <button type="button" @click.prevent.stop="pasteReplaceCode()" title="Paste & Replace Code" class="editor-tool-btn min-w-max">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25h6M9 3.75h6A1.5 1.5 0 0 1 16.5 5.25v.75h1.125A2.625 2.625 0 0 1 20.25 8.625v9A2.625 2.625 0 0 1 17.625 20.25H6.375A2.625 2.625 0 0 1 3.75 17.625v-9A2.625 2.625 0 0 1 6.375 6H7.5v-.75A1.5 1.5 0 0 1 9 3.75Z"/></svg>
            <span class="hidden text-xs font-bold sm:inline">Paste & Replace Code</span>
        </button>
        <button type="button" @click="findInEditor()" title="Search" class="editor-tool-btn">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        </button>
        <button type="button" @click="formatCode()" title="Format indentation" class="editor-tool-btn">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h16m-4-7 3 2-3 2"/></svg>
        </button>
        <button type="button" @click="toggleFullscreen()" title="Fullscreen" class="editor-tool-btn">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
        </button>
        <button type="button" @click="helpersOpen = true" title="Helpers" class="editor-tool-btn">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h4.5M5.25 3.75h13.5A1.5 1.5 0 0 1 20.25 5.25v13.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
            <span class="hidden text-xs font-bold sm:inline">Helpers</span>
        </button>

        <div class="ml-auto flex shrink-0 items-center gap-2 pl-3 text-xs">
            <span x-show="saveStatus" x-cloak class="font-bold text-[#93C5FD]" x-text="saveStatus"></span>
            <span x-show="saveError" x-cloak class="max-w-[260px] truncate rounded bg-[#EF4444]/12 px-2 py-1 font-bold text-[#FCA5A5]" x-text="saveError"></span>
        </div>
    </div>

    <div x-show="searchOpen" x-cloak class="absolute right-3 top-[122px] z-20 flex max-w-[calc(100vw-1.5rem)] items-center gap-1 rounded-md border border-[#3a3a3a] bg-[#f7f7f7] p-1 text-[#222] shadow-2xl">
        <input x-ref="searchInput" x-model="searchQuery" @input.debounce.120ms="updateSearchQuery()" @keydown.enter.prevent="findNextMatch()" @keydown.shift.enter.prevent="findPreviousMatch()" @keydown.escape.prevent="closeSearch()" class="h-8 w-44 rounded border border-[#d1d1d1] bg-white px-2 font-mono text-sm outline-none sm:w-72" type="search" placeholder="Find">
        <span class="min-w-[72px] px-2 text-center text-xs font-bold text-[#666]" x-text="searchQuery ? (matchCount ? matchCount + ' found' : 'No match') : ''"></span>
        <button type="button" @click="findPreviousMatch()" class="grid h-8 w-8 place-items-center rounded text-[#666] hover:bg-[#e8e8e8]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg></button>
        <button type="button" @click="findNextMatch()" class="grid h-8 w-8 place-items-center rounded text-[#666] hover:bg-[#e8e8e8]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg></button>
        <button type="button" @click="closeSearch()" class="grid h-8 w-8 place-items-center rounded text-[#666] hover:bg-[#e8e8e8]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
    </div>

    <div class="command-editor-surface relative min-h-0 flex-1 bg-[#080714]" style="background:#080714;">
        <div x-show="!loaded" x-cloak class="absolute inset-0 z-10 flex items-center justify-center bg-black text-xs font-bold text-[#8B8B8B]">Loading editor...</div>
        <div x-ref="editorContainer" class="h-full min-h-0 bg-[#080714]" style="background:#080714;"></div>
    </div>

    <div class="flex h-8 shrink-0 items-center gap-3 overflow-x-auto border-t border-[#2d2d2d] bg-[#202020] px-3 font-mono text-[12px] text-[#C7C7C7]" style="scrollbar-width:none">
        <span x-text="'Ln ' + cursorLine + ', Col ' + cursorCol"></span>
        <span x-text="chars.toLocaleString() + ' bytes'"></span>
        <span class="ml-auto">{{ $langLabel }}</span>
        <span class="hidden sm:inline">{{ $command->effectiveTriggerType() === 'direct_message' ? 'Direct Message Handler' : 'Command' }}</span>
        <span x-show="dirty" x-cloak class="text-[#FBBF24]">Unsaved</span>
    </div>

    <form x-ref="saveForm" method="POST" action="{{ $saveUrl }}" class="hidden">
        @csrf
        @method('PUT')
        <textarea x-ref="codeInput" name="code"></textarea>
    </form>

    <div x-show="editorDialogOpen" x-cloak class="fixed inset-x-0 bottom-0 top-0 z-[10000] flex items-start justify-center overflow-y-auto bg-black/70 p-3 sm:inset-0 sm:items-center sm:p-4" style="padding-top:max(1rem, env(safe-area-inset-top));">
        <div class="max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-xl border bg-[#141414] p-5 shadow-2xl"
             :class="editorDialogType === 'warning' ? 'border-[#F59E0B]/35' : 'border-[#303030]'">
            <div class="flex items-start gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg"
                     :class="editorDialogType === 'warning' ? 'bg-[#F59E0B]/12 text-[#F59E0B]' : 'bg-[#2f6fed]/12 text-[#93C5FD]'">
                    <svg x-show="editorDialogType === 'warning'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                    <svg x-show="editorDialogType !== 'warning'" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h4.5"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-black text-white" x-text="editorDialogTitle"></h3>
                    <p class="mt-1 text-sm leading-6 text-[#A1A1AA]" x-text="editorDialogMessage"></p>
                </div>
            </div>
            <textarea
                x-show="editorDialogPasteMode"
                x-ref="editorDialogPaste"
                x-model="editorDialogPasteText"
                x-cloak
                rows="5"
                class="mt-4 min-h-[160px] max-h-[45dvh] w-full resize-none overflow-y-auto rounded-lg border border-[#303030] bg-black px-3 py-2 font-mono text-sm text-white outline-none focus:border-[#2f6fed]"
                placeholder="Paste code here if clipboard permission is blocked..."
            ></textarea>
            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" @click="cancelEditorDialog()" class="rounded-lg border border-[#303030] bg-[#202020] px-4 py-2 text-sm font-bold text-[#C7C7C7] transition hover:bg-[#2a2a2a] hover:text-white" x-text="editorDialogCancelText"></button>
                <button type="button" @click="acceptEditorDialog()" class="rounded-lg px-4 py-2 text-sm font-black text-white transition"
                        :class="editorDialogType === 'warning' ? 'bg-[#F59E0B] hover:bg-[#D97706]' : 'bg-[#2f6fed] hover:bg-[#3b7cff]'"
                        x-text="editorDialogConfirmText"></button>
            </div>
        </div>
    </div>

    <div x-show="helpersOpen" x-cloak class="fixed inset-0 z-[9999] flex justify-end bg-black/60" @click.self="helpersOpen = false">
        <aside class="flex h-full w-full max-w-md flex-col border-l border-[#2d2d2d] bg-[#111] shadow-2xl">
            <div class="flex h-14 shrink-0 items-center justify-between border-b border-[#2d2d2d] px-4">
                <div>
                    <h2 class="text-sm font-black">Helpers</h2>
                    <p class="text-xs text-[#888]">Quick snippets only. Secrets are not shown here.</p>
                </div>
                <button type="button" @click="helpersOpen = false" class="grid h-9 w-9 place-items-center rounded-md text-[#aaa] hover:bg-[#222] hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div class="space-y-4">
                    @foreach ([
                        'Messaging' => ['await reply("Hello");', 'await replyHTML("<b>Saved</b>");', 'await sendPhoto(chat.id, url, { caption: "Photo" });'],
                        'Command Reply Flow' => ['await askInCommand("Send your email:", "email");', 'if (commandFlow.active && commandFlow.step === "email") { ... }', 'await clearCommandFlow();'],
                        'State' => ['await setState("step", { id: user.id });', 'const state = await getState();', 'await clearState();'],
                        'User Data' => ['await setUserData("wallet", message.text);', 'const balance = await getBalance();', 'await addBalance(100, userId);'],
                        'Validation' => ['if (!isEmail(message.text)) return;', 'const amount = parseAmount(message.text);', 'if (!isTelegramUserId(message.text)) return;'],
                    ] as $title => $items)
                        <section>
                            <h3 class="mb-2 text-[11px] font-black uppercase tracking-wider text-[#777]">{{ $title }}</h3>
                            <div class="space-y-2">
                                @foreach ($items as $item)
                                    <code class="block rounded-md border border-[#2d2d2d] bg-black px-3 py-2 font-mono text-xs font-bold text-[#7dd3fc]">{{ $item }}</code>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
    .editor-tool-btn {
        display: inline-flex;
        height: 2.25rem;
        min-width: 2.25rem;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        border-radius: 0.375rem;
        padding-left: 0.625rem;
        padding-right: 0.625rem;
        color: #b8b8b8;
        transition: background-color 150ms ease, color 150ms ease;
    }

    .editor-tool-btn:hover {
        background: #303030;
        color: #fff;
    }
</style>
</x-dashboard-layout>
