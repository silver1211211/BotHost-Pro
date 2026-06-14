<x-admin-layout title="Broadcast Center" subtitle="Send platform-wide announcements to your BotHost Pro audience.">

@php $s = $stats; @endphp

{{-- PAGE HEADER --}}
<div class="relative overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A] px-5 py-5 sm:px-6 sm:py-6">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-[#8B5CF6]/8 blur-3xl"></div>
    </div>
    <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-[#27213D] bg-[#151225] px-3 py-1 text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Admin Only</span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-[#22C55E]/25 bg-[#22C55E]/8 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-[#22C55E]">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#22C55E]"></span>Safe Queue
                </span>
            </div>
            <h2 class="mt-3 text-xl font-black text-[#F8FAFC] sm:text-2xl">Broadcast Center</h2>
            <p class="mt-1 text-sm text-[#94A3B8]">Send updates to platform users, email subscribers, Telegram bot users, or platform banners.</p>
            <p class="mt-0.5 text-xs text-[#52525B]">Broadcasts run in the background after they are queued.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="location.reload()" class="inline-flex items-center gap-2 rounded-xl border border-[#27213D] bg-[#151225] px-3.5 py-2.5 text-sm font-black text-[#A1A1AA] transition hover:text-[#F8FAFC]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                Refresh
            </button>
        </div>
    </div>
</div>

{{-- SUMMARY CARDS --}}
<div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
    @foreach ([
        ['Total Broadcasts', $s['total_broadcasts'] ?? 0,      '#A855F7', 'M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5'],
        ['Sent Messages',    $s['broadcasts_sent'] ?? 0,       '#22C55E', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['Total Bot Users',  $s['total_bot_users'] ?? 0,       '#229ED9', 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z'],
        ['Platform Users',   $s['total_users'] ?? 0,           '#38BDF8', 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
    ] as [$label, $value, $color, $path])
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        <div class="flex items-start justify-between gap-2">
            <p class="text-[10px] font-black uppercase tracking-widest text-[#52525B]">{{ $label }}</p>
            <svg class="h-4 w-4 shrink-0 opacity-25" style="color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
        </div>
        <p class="mt-2 text-2xl font-black text-[#F8FAFC]">{{ number_format($value) }}</p>
    </div>
    @endforeach
</div>

{{-- MAIN COMPOSER --}}
<div class="mt-5" x-data="{
    channels: ['in_app'],
    toggleChannel(ch) { this.channels.includes(ch) ? this.channels = this.channels.filter(c => c !== ch) : this.channels.push(ch); },
    campaignType: 'announcement',
    msgType: 'text',
    targetKey: 'all_users',
    bcTitle: '',
    bcSubject: '',
    bcMsg: '',
    bcCtaText: '',
    bcCtaUrl: '',
    bcPriority: 'normal',
    tgWindow: 'all',
    tgWindowOpen: false,
    tgWindowCounts: @js(['24h' => $s['bot_users_24h'] ?? 0, '48h' => $s['bot_users_48h'] ?? 0, '72h' => $s['bot_users_72h'] ?? 0, 'all' => $s['total_bot_users'] ?? 0]),
    maxRecipients: '',
    recipientCapOpen: false,
    scheduleOpen: false,
    scheduleVal: 'now',
    batchSizeOpen: false,
    batchSizeVal: '500',
    batchDelayOpen: false,
    batchDelayVal: '5',
    get bcLen() { return this.bcMsg.length; },
    get tgWindowLabel() { return { '24h': 'Active Last 24 Hours', '48h': 'Active Last 48 Hours', '72h': 'Active Last 72 Hours', 'all': 'All Bot Users' }[this.tgWindow] || 'All Bot Users'; },
    get scheduleLabel() { return { 'now': 'Send Now — Queue immediately', 'draft': 'Save as Draft' }[this.scheduleVal] || 'Send Now — Queue immediately'; },
    get batchSizeLabel() { return { '100': '100', '500': '500', '1000': '1,000', '2000': '2,000' }[this.batchSizeVal] || '500'; },
    get batchDelayLabel() { return { '1': '1 sec', '5': '5 sec', '10': '10 sec', '30': '30 sec' }[this.batchDelayVal] || '5 sec'; }
}">
    <form method="POST" action="{{ route('admin.broadcasts.store') }}">
        @csrf
        <input type="hidden" name="campaign_type" :value="campaignType">
        <input type="hidden" name="message_type" :value="msgType">
        <input type="hidden" name="target_type" :value="targetKey">
        <input type="hidden" name="priority" :value="bcPriority">
        <input type="hidden" name="tg_window" :value="tgWindow">
        <input type="hidden" name="max_recipients" :value="maxRecipients">

        <div class="grid gap-5 lg:grid-cols-[1fr_320px]">

            {{-- ====== LEFT: Composer ====== --}}
            <div class="space-y-5">

                {{-- Campaign Setup --}}
                <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
                    <div class="border-b border-[#27213D] px-5 py-4">
                        <p class="text-sm font-black text-[#F8FAFC]">Campaign Setup</p>
                        <p class="mt-0.5 text-xs text-[#94A3B8]">Define the campaign identity, subject, and message.</p>
                    </div>
                    <div class="space-y-4 p-5">

                        {{-- Name + Priority --}}
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Campaign Name</label>
                                <input x-model="bcTitle" name="campaign_name" maxlength="120" type="text"
                                    class="mt-2 w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15 transition"
                                    placeholder="Maintenance notice, New feature alert…">
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Priority</label>
                                <div class="mt-2 grid grid-cols-4 gap-1.5">
                                    @foreach ([
                                        ['low',      'Low',      '#71717A'],
                                        ['normal',   'Normal',   '#38BDF8'],
                                        ['high',     'High',     '#F59E0B'],
                                        ['critical', 'Critical', '#EF4444'],
                                    ] as [$pv, $pl, $pc])
                                    <button type="button" @click="bcPriority = '{{ $pv }}'"
                                        :style="bcPriority === '{{ $pv }}' ? 'border-width:2px;border-color:{{ $pc }}50;background-color:{{ $pc }}12;color:{{ $pc }}' : 'border-width:1px;border-color:#27213D;background-color:#11101C;color:{{ $pc }};opacity:0.4'"
                                        class="rounded-xl px-2 py-2.5 text-[10px] font-black transition hover:opacity-100">
                                        {{ $pl }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Subject --}}
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Title / Subject</label>
                            <input x-model="bcSubject" name="title" maxlength="150" type="text"
                                class="mt-2 w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15 transition"
                                placeholder="Important update from BotHost Pro">
                        </div>

                        {{-- Message --}}
                        <div>
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Message</label>
                                <span class="text-[10px]" :class="bcLen > 3800 ? 'font-black text-[#EF4444]' : 'text-[#3D3658]'" x-text="bcLen + ' / 4096'">0 / 4096</span>
                            </div>
                            <textarea x-model="bcMsg" name="message" rows="6" maxlength="4096"
                                class="mt-2 w-full resize-y rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 font-mono text-sm leading-6 text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none focus:ring-2 focus:ring-[#8B5CF6]/15 transition"
                                placeholder="Dear BotHost Pro users,&#10;&#10;We are excited to announce…"></textarea>
                        </div>

                        {{-- CTA (optional) --}}
                        <div class="rounded-2xl border border-[#1B172B] bg-[#090713] p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-[#52525B]">CTA Button <span class="normal-case font-normal text-[#3D3658]">— optional</span></p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="text-[10px] font-bold text-[#52525B]">Button Text</label>
                                    <input x-model="bcCtaText" name="cta_text" maxlength="40" type="text"
                                        class="mt-1.5 w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none transition"
                                        placeholder="Learn More">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-[#52525B]">Button URL</label>
                                    <input x-model="bcCtaUrl" name="cta_url" maxlength="2048" type="url"
                                        class="mt-1.5 w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none transition"
                                        placeholder="https://…">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Delivery Channels --}}
                <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
                    <div class="border-b border-[#27213D] px-5 py-4">
                        <p class="text-sm font-black text-[#F8FAFC]">Delivery Channels</p>
                        <p class="mt-0.5 text-xs text-[#94A3B8]">Select one or more channels for this broadcast.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2">
                        @foreach ([
                            ['in_app',   'In-App Notification',   'Send to users inside their BotHost Pro notification center.',       '#8B5CF6', true,  'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
                            ['email',    'Email',                  "Send to users' email addresses using configured SMTP settings.",    '#38BDF8', false, 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75'],
                            ['telegram', 'Telegram Bot Users',     'Send through selected Telegram bots to tracked bot users.',         '#229ED9', false, 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z'],
                            ['platform', 'Platform Notification',  'Show an announcement banner inside the platform dashboard.',        '#71717A', false, 'M3 8.25V18a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18V8.25m-18 0V6a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 6v2.25m-18 0h18M5.25 6h.008v.008H5.25V6Z'],
                        ] as [$chVal, $chLabel, $chDesc, $chColor, $chChecked, $chPath])
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition"
                            :class="channels.includes('{{ $chVal }}') ? 'border-[#8B5CF6]/50 bg-[#8B5CF6]/6' : 'border-[#27213D] bg-[#11101C] hover:border-[#3D3658]'">
                            <input type="checkbox" name="channels[]" value="{{ $chVal }}" {{ $chChecked ? 'checked' : '' }}
                                @change="toggleChannel('{{ $chVal }}')"
                                class="mt-0.5 shrink-0 accent-[#8B5CF6]">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 shrink-0" style="color:{{ $chColor }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chPath }}"/></svg>
                                    <p class="text-sm font-black text-[#F8FAFC]">{{ $chLabel }}</p>
                                </div>
                                <p class="mt-1 text-xs leading-4 text-[#94A3B8]">{{ $chDesc }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>

                    {{-- Telegram audience window picker — shown when telegram channel is selected --}}
                    <div x-show="channels.includes('telegram')" class="border-t border-[#1B172B] px-5 pb-5 pt-4" x-cloak>
                        <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Telegram Audience Window</p>
                        <div class="mt-2 overflow-hidden rounded-2xl border border-[#229ED9]/25 bg-[#229ED9]/5">
                            <div class="flex items-center justify-between gap-3 px-4 py-3.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-[#229ED9]" x-text="tgWindowLabel"></p>
                                    <p class="mt-0.5 text-[10px] text-[#94A3B8]">
                                        <span class="font-black text-[#F8FAFC]" x-text="(tgWindowCounts[tgWindow] || 0).toLocaleString()"></span> bot users in this window
                                    </p>
                                </div>
                                <button type="button" @click="tgWindowOpen = true"
                                    class="shrink-0 rounded-xl border border-[#229ED9]/30 bg-[#229ED9]/10 px-3 py-2 text-xs font-black text-[#229ED9] transition hover:bg-[#229ED9]/18">
                                    Change
                                </button>
                            </div>
                        </div>
                        <p class="mt-2.5 text-[10px] text-[#52525B]">All bots with verified tokens are included. Blocked and deleted users are excluded automatically.</p>
                    </div>
                </div>

                {{-- Audience Targeting --}}
                <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
                    <div class="border-b border-[#27213D] px-5 py-4">
                        <p class="text-sm font-black text-[#F8FAFC]">Audience Targeting</p>
                        <p class="mt-0.5 text-xs text-[#94A3B8]">Select who receives this campaign. One segment at a time.</p>
                    </div>
                    <div class="space-y-5 p-5">

                        {{-- Platform Users --}}
                        <div>
                            <p class="mb-2 text-[10px] font-black uppercase tracking-wider text-[#52525B]">Platform Users</p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ([
                                    ['all_users',      'All Users',   $s['total_users']],
                                    ['active_users',   'Active',      $s['active_users']],
                                    ['free_users',     'Free Plan',   $s['free_users']],
                                    ['pro_users',      'Pro Plan',    $s['pro_users']],
                                    ['business_users', 'Business',    $s['business_users']],
                                    ['admin_users',    'Admins',      $s['admin_users']],
                                    ['suspended_users','Suspended',   $s['suspended_users']],
                                    ['banned_users',   'Banned',      $s['banned_users']],
                                ] as [$key, $label, $count])
                                <button type="button" @click="targetKey = '{{ $key }}'"
                                    :class="targetKey === '{{ $key }}' ? 'border-[#8B5CF6]/50 bg-[#8B5CF6]/10 text-[#A855F7]' : 'border-[#27213D] bg-[#11101C] text-[#A1A1AA] hover:text-[#F8FAFC]'"
                                    class="flex items-center justify-between rounded-xl border px-3 py-2.5 text-left transition">
                                    <span class="text-sm font-bold">{{ $label }}</span>
                                    <span class="text-xs font-black" :class="targetKey === '{{ $key }}' ? 'text-[#8B5CF6]' : 'text-[#52525B]'">{{ number_format($count) }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Activity Windows --}}
                        <div>
                            <p class="mb-2 text-[10px] font-black uppercase tracking-wider text-[#52525B]">Activity Window</p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ([
                                    ['new_today', 'Joined Today',    $s['new_today']],
                                    ['new_7d',    'Last 7 Days',     $s['new_7d']],
                                    ['new_30d',   'Last 30 Days',    $s['new_30d']],
                                    ['bot_24h',   'Bot Active 24h',  $s['bot_users_24h'] ?? 0],
                                    ['bot_48h',   'Bot Active 48h',  $s['bot_users_48h'] ?? 0],
                                    ['bot_72h',   'Bot Active 72h',  $s['bot_users_72h'] ?? 0],
                                    ['bot_7d',    'Bot Active 7d',   $s['bot_users_7d'] ?? 0],
                                ] as [$key, $label, $count])
                                <button type="button" @click="targetKey = '{{ $key }}'"
                                    :class="targetKey === '{{ $key }}' ? 'border-[#8B5CF6]/50 bg-[#8B5CF6]/10 text-[#A855F7]' : 'border-[#27213D] bg-[#11101C] text-[#A1A1AA] hover:text-[#F8FAFC]'"
                                    class="flex items-center justify-between rounded-xl border px-3 py-2.5 text-left transition">
                                    <span class="text-sm font-bold">{{ $label }}</span>
                                    <span class="text-xs font-black" :class="targetKey === '{{ $key }}' ? 'text-[#8B5CF6]' : 'text-[#52525B]'">{{ number_format($count) }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Bot Ownership --}}
                        <div>
                            <p class="mb-2 text-[10px] font-black uppercase tracking-wider text-[#52525B]">Bot Ownership</p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ([
                                    ['users_with_bots',    'Has Bots',    $s['users_with_bots']],
                                    ['users_without_bots', 'No Bots',     max(0, $s['total_users'] - $s['users_with_bots'])],
                                ] as [$key, $label, $count])
                                <button type="button" @click="targetKey = '{{ $key }}'"
                                    :class="targetKey === '{{ $key }}' ? 'border-[#8B5CF6]/50 bg-[#8B5CF6]/10 text-[#A855F7]' : 'border-[#27213D] bg-[#11101C] text-[#A1A1AA] hover:text-[#F8FAFC]'"
                                    class="flex items-center justify-between rounded-xl border px-3 py-2.5 text-left transition">
                                    <span class="text-sm font-bold">{{ $label }}</span>
                                    <span class="text-xs font-black" :class="targetKey === '{{ $key }}' ? 'text-[#8B5CF6]' : 'text-[#52525B]'">{{ number_format($count) }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Schedule & Launch --}}
                <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
                    <div class="border-b border-[#27213D] px-5 py-4">
                        <p class="text-sm font-black text-[#F8FAFC]">Schedule &amp; Launch</p>
                        <p class="mt-0.5 text-xs text-[#94A3B8]">Queue now or save as a draft for later.</p>
                    </div>
                    <div class="p-5">
                        <label class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">When to Send</label>
                        <input type="hidden" name="schedule" :value="scheduleVal">
                        <div class="relative mt-2" @click.away="scheduleOpen = false">
                            <button type="button" @click="scheduleOpen = !scheduleOpen"
                                class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-4 py-3 text-sm text-[#F8FAFC] transition focus:outline-none"
                                :class="scheduleOpen ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                                <span x-text="scheduleLabel" class="text-left"></span>
                                <svg class="ml-2 h-4 w-4 shrink-0 text-[#94A3B8] transition-transform duration-150" :class="scheduleOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <div x-show="scheduleOpen" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                @foreach ([
                                    ['now',   'Send Now',      'Queue immediately after submitting'],
                                    ['draft', 'Save as Draft', 'Save without sending — review later'],
                                ] as [$sv, $sl, $sd])
                                <button type="button" @click="scheduleVal = '{{ $sv }}'; scheduleOpen = false"
                                    class="flex w-full items-start gap-3 px-4 py-3.5 text-left transition hover:bg-[#1D1930]"
                                    :class="scheduleVal === '{{ $sv }}' ? 'bg-[#8B5CF6]/8' : ''">
                                    <svg :class="scheduleVal === '{{ $sv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="mt-0.5 h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <div>
                                        <p class="text-sm font-black" :class="scheduleVal === '{{ $sv }}' ? 'text-[#A855F7]' : 'text-[#F8FAFC]'">{{ $sl }}</p>
                                        <p class="text-[10px] text-[#52525B]">{{ $sd }}</p>
                                    </div>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="submit" name="action" value="draft"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-[#27213D] bg-[#151225] px-4 py-3 text-sm font-black text-[#A1A1AA] transition hover:text-[#F8FAFC] sm:flex-none">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                Save Draft
                            </button>
                            <button type="submit"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-3 text-sm font-black text-white transition hover:opacity-90 sm:flex-none">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                Queue Broadcast
                            </button>
                        </div>
                        <p class="mt-3 text-center text-[10px] text-[#3D3658]">All selected channels will be processed and tracked in background queues.</p>
                    </div>
                </div>

            </div>

            {{-- ====== RIGHT: Preview + Batch + Checklist ====== --}}
            <div class="space-y-5">

                {{-- Preview --}}
                <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
                    <div class="border-b border-[#27213D] px-5 py-4">
                        <p class="text-sm font-black text-[#F8FAFC]">Campaign Preview</p>
                        <p class="mt-0.5 text-xs text-[#94A3B8]">In-app notification preview</p>
                    </div>
                    <div class="p-5">
                        <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#090713]">
                            <div class="flex items-center gap-3 border-b border-[#1B172B] px-4 py-3">
                                <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-[#8B5CF6] to-[#5B21B6] text-xs font-black text-white">B</div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black text-[#A855F7]">BotHost Pro</p>
                                    <p class="text-[10px] text-[#52525B]">Platform Broadcast</p>
                                </div>
                                <span class="text-[9px] text-[#3D3658]">now</span>
                            </div>
                            <div class="px-4 py-4">
                                <p class="text-sm font-black text-[#F8FAFC]" x-text="bcSubject || 'Title / Subject'"></p>
                                <p class="mt-2 break-words text-sm leading-5 text-[#A1A1AA]" x-show="bcMsg" x-text="bcMsg.substring(0, 200) + (bcMsg.length > 200 ? '…' : '')"></p>
                                <p x-show="!bcMsg" class="mt-2 text-sm italic text-[#3D3658]">Message body will appear here…</p>
                                <div x-show="bcCtaText" x-cloak class="mt-3 rounded-xl border border-[#229ED9]/30 bg-[#229ED9]/10 px-4 py-2 text-center text-sm font-black text-[#229ED9]" x-text="bcCtaText"></div>
                            </div>
                        </div>

                        {{-- Audience summary --}}
                        @php
                            $audienceMap = [
                                'all_users'          => ['All Platform Users',    $s['total_users']],
                                'active_users'       => ['Active Users',          $s['active_users']],
                                'free_users'         => ['Free Plan Users',       $s['free_users']],
                                'pro_users'          => ['Pro Plan Users',        $s['pro_users']],
                                'business_users'     => ['Business Plan Users',   $s['business_users']],
                                'admin_users'        => ['Admin Users',           $s['admin_users']],
                                'suspended_users'    => ['Suspended Users',       $s['suspended_users']],
                                'banned_users'       => ['Banned Users',          $s['banned_users']],
                                'new_today'          => ['Joined Today',          $s['new_today']],
                                'new_7d'             => ['Joined Last 7 Days',    $s['new_7d']],
                                'new_30d'            => ['Joined Last 30 Days',   $s['new_30d']],
                                'users_with_bots'    => ['Has Bots',              $s['users_with_bots']],
                                'users_without_bots' => ['No Bots',               max(0, $s['total_users'] - $s['users_with_bots'])],
                                'bot_24h'            => ['Bot Active 24h',        $s['bot_users_24h'] ?? 0],
                                'bot_48h'            => ['Bot Active 48h',        $s['bot_users_48h'] ?? 0],
                                'bot_72h'            => ['Bot Active 72h',        $s['bot_users_72h'] ?? 0],
                                'bot_7d'             => ['Bot Active 7d',         $s['bot_users_7d'] ?? 0],
                            ];
                        @endphp
                        <div class="mt-4 rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-[#52525B]">Selected Audience</p>
                            @foreach ($audienceMap as $key => [$label, $count])
                            <div x-show="targetKey === '{{ $key }}'" class="mt-2">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-black text-[#A855F7]">{{ $label }}</p>
                                    <p class="text-xl font-black text-[#F8FAFC]">{{ number_format($count) }}</p>
                                </div>
                                <p class="mt-0.5 text-[10px] text-[#52525B]">Banned and deleted users excluded automatically.</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Batch Planning --}}
                <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
                    <div class="border-b border-[#27213D] px-5 py-4">
                        <p class="text-sm font-black text-[#F8FAFC]">Batch Planning</p>
                        <p class="mt-0.5 text-xs text-[#94A3B8]">Delivery speed and queue settings</p>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-[#52525B]">Batch Size</p>
                                <input type="hidden" name="batch_size" :value="batchSizeVal">
                                <div class="relative mt-1.5" @click.away="batchSizeOpen = false">
                                    <button type="button" @click="batchSizeOpen = !batchSizeOpen"
                                        class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-3 py-2.5 text-sm text-[#F8FAFC] transition focus:outline-none"
                                        :class="batchSizeOpen ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                                        <span x-text="batchSizeLabel"></span>
                                        <svg class="ml-1 h-3.5 w-3.5 shrink-0 text-[#94A3B8] transition-transform duration-150" :class="batchSizeOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                    </button>
                                    <div x-show="batchSizeOpen" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                        class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                        @foreach (['100' => '100', '500' => '500', '1000' => '1,000', '2000' => '2,000'] as $bsv => $bsl)
                                        <button type="button" @click="batchSizeVal = '{{ $bsv }}'; batchSizeOpen = false"
                                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]"
                                            :class="batchSizeVal === '{{ $bsv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                            <svg :class="batchSizeVal === '{{ $bsv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3 w-3 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            <span>{{ $bsl }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-[#52525B]">Batch Delay</p>
                                <input type="hidden" name="batch_delay_seconds" :value="batchDelayVal">
                                <div class="relative mt-1.5" @click.away="batchDelayOpen = false">
                                    <button type="button" @click="batchDelayOpen = !batchDelayOpen"
                                        class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-3 py-2.5 text-sm text-[#F8FAFC] transition focus:outline-none"
                                        :class="batchDelayOpen ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                                        <span x-text="batchDelayLabel"></span>
                                        <svg class="ml-1 h-3.5 w-3.5 shrink-0 text-[#94A3B8] transition-transform duration-150" :class="batchDelayOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                    </button>
                                    <div x-show="batchDelayOpen" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                        class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                        @foreach (['1' => '1 sec', '5' => '5 sec', '10' => '10 sec', '30' => '30 sec'] as $bdv => $bdl)
                                        <button type="button" @click="batchDelayVal = '{{ $bdv }}'; batchDelayOpen = false"
                                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]"
                                            :class="batchDelayVal === '{{ $bdv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                            <svg :class="batchDelayVal === '{{ $bdv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3 w-3 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            <span>{{ $bdl }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Recipient Cap --}}
                        <div class="rounded-2xl border border-[#1B172B] bg-[#090713] p-4">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-[#52525B]">Recipient Cap</p>
                                    <p class="mt-0.5 text-xs text-[#94A3B8]" x-show="!maxRecipients">Sending to all eligible recipients</p>
                                    <p class="mt-0.5 text-xs font-black text-[#A855F7]" x-show="maxRecipients" x-cloak x-text="Number(maxRecipients).toLocaleString() + ' max recipients'"></p>
                                </div>
                                <button type="button" @click="recipientCapOpen = !recipientCapOpen"
                                    class="shrink-0 rounded-xl border px-3 py-1.5 text-xs font-black transition"
                                    :class="maxRecipients ? 'border-[#8B5CF6]/40 bg-[#8B5CF6]/10 text-[#A855F7]' : 'border-[#27213D] bg-[#151225] text-[#94A3B8] hover:text-[#F8FAFC]'"
                                    x-text="recipientCapOpen ? 'Close' : (maxRecipients ? 'Edit cap' : 'Set limit')">
                                </button>
                            </div>
                            <div x-show="recipientCapOpen" x-cloak
                                x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                class="mt-3">
                                <div class="flex gap-2">
                                    <input type="number" min="1" x-model="maxRecipients" placeholder="e.g. 5000"
                                        class="flex-1 rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2.5 text-sm text-[#F8FAFC] placeholder:text-[#52525B] focus:border-[#8B5CF6]/60 focus:outline-none transition">
                                    <button type="button" @click="maxRecipients = ''; recipientCapOpen = false"
                                        class="rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2.5 text-xs font-black text-[#94A3B8] transition hover:border-[#EF4444]/40 hover:text-[#EF4444]">
                                        Clear
                                    </button>
                                </div>
                                <p class="mt-1.5 text-[10px] text-[#52525B]">Leave empty to send to all eligible recipients in the selected window.</p>
                            </div>
                        </div>

                        @foreach ([
                            ['Queue Mode',  'Safe background batch sending'],
                            ['Est. Time',   'Calculated after audience set'],
                            ['Plan Level',  'Admin — no recipient limits'],
                        ] as [$bl, $bv])
                        <div class="flex items-center justify-between border-b border-[#1B172B] pb-3 last:border-0 last:pb-0">
                            <p class="text-xs text-[#94A3B8]">{{ $bl }}</p>
                            <p class="text-xs font-black text-[#A1A1AA]">{{ $bv }}</p>
                        </div>
                        @endforeach
                        <p class="text-[10px] text-[#3D3658]">Broadcasts continue in the background after queuing.</p>
                    </div>
                </div>

                {{-- Pre-Launch Checklist --}}
                <div class="overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
                    <div class="border-b border-[#27213D] px-5 py-4">
                        <p class="text-sm font-black text-[#F8FAFC]">Pre-Launch Checklist</p>
                    </div>
                    <div class="space-y-2.5 p-5">
                        @foreach ([
                            ['Audience selected',        true],
                            ['Campaign name filled',     true],
                            ['Message body written',     false],
                            ['CTA link verified',        false],
                            ['Batch speed configured',   true],
                            ['No banned users targeted', true],
                        ] as [$item, $done])
                        <div class="flex items-center gap-2.5">
                            <div class="grid h-4 w-4 shrink-0 place-items-center rounded-full" style="{{ $done ? 'background-color:#22C55E12;border:1px solid #22C55E35' : 'background-color:#11101C;border:1px solid #27213D' }}">
                                @if ($done)
                                <svg class="h-2.5 w-2.5 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                @endif
                            </div>
                            <p class="text-xs {{ $done ? 'text-[#A1A1AA]' : 'text-[#52525B]' }}">{{ $item }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
        {{-- ===== Telegram Window Modal ===== --}}
        <div x-show="tgWindowOpen" x-cloak
            class="fixed inset-0 z-[200] flex items-end justify-center sm:items-center sm:p-4"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="tgWindowOpen = false"></div>
            <div class="relative w-full max-w-sm overflow-hidden rounded-t-3xl border border-[#27213D] bg-[#0F0D1A] shadow-[0_32px_80px_rgba(0,0,0,0.7)] sm:rounded-3xl"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95">
                <div class="flex justify-center pt-3 sm:hidden">
                    <div class="h-1 w-10 rounded-full bg-[#3D3658]"></div>
                </div>
                <div class="border-b border-[#27213D] px-5 py-4">
                    <p class="text-sm font-black text-[#F8FAFC]">Select Telegram Audience</p>
                    <p class="mt-0.5 text-xs text-[#94A3B8]">Choose which bot users receive this broadcast by activity window.</p>
                </div>
                <div class="space-y-2 p-4">
                    @foreach ([
                        ['24h', 'Active Last 24 Hours', $s['bot_users_24h']  ?? 0, 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                        ['48h', 'Active Last 48 Hours', $s['bot_users_48h']  ?? 0, 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                        ['72h', 'Active Last 72 Hours', $s['bot_users_72h']  ?? 0, 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                        ['all', 'All Bot Users',        $s['total_bot_users'] ?? 0, 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
                    ] as [$wval, $wlabel, $wcount, $wpath])
                    <button type="button" @click="tgWindow = '{{ $wval }}'; tgWindowOpen = false"
                        class="flex w-full items-center gap-3 rounded-2xl border p-4 text-left transition"
                        :class="tgWindow === '{{ $wval }}' ? 'border-[#229ED9]/40 bg-[#229ED9]/8' : 'border-[#27213D] bg-[#11101C] hover:border-[#3D3658]'">
                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border transition"
                            :class="tgWindow === '{{ $wval }}' ? 'border-[#229ED9]/30 bg-[#229ED9]/12' : 'border-[#27213D] bg-[#151225]'">
                            <svg class="h-4 w-4 text-[#229ED9]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $wpath }}"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-black transition" :class="tgWindow === '{{ $wval }}' ? 'text-[#229ED9]' : 'text-[#F8FAFC]'">{{ $wlabel }}</p>
                            <p class="mt-0.5 text-[10px] text-[#94A3B8]">
                                <span class="font-black" :class="tgWindow === '{{ $wval }}' ? 'text-[#229ED9]' : 'text-[#A1A1AA]'">{{ number_format($wcount) }}</span> bot users available
                            </p>
                        </div>
                        <svg :class="tgWindow === '{{ $wval }}' ? 'opacity-100 text-[#229ED9]' : 'opacity-0'" class="h-5 w-5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    </button>
                    @endforeach
                </div>
                <div class="border-t border-[#27213D] px-5 py-4">
                    <p class="text-[10px] text-[#52525B]">Blocked and deleted bot users are always excluded. Bots require a verified token to participate.</p>
                </div>
            </div>
        </div>

    </form>
</div>

{{-- CAMPAIGN HISTORY --}}
<div class="mt-5 overflow-hidden rounded-3xl border border-[#27213D] bg-[#0F0D1A]">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#27213D] px-5 py-4">
        <div>
            <p class="text-sm font-black text-[#F8FAFC]">Campaign History</p>
            <p class="text-xs text-[#94A3B8]">All admin broadcasts and platform campaigns</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach (['All' => '', 'Sending' => 'sending', 'Completed' => 'completed', 'Draft' => 'draft', 'Failed' => 'failed'] as $fl => $fv)
            <button type="button" class="rounded-xl border px-3 py-1.5 text-xs font-black transition {{ $fl === 'All' ? 'border-[#8B5CF6]/40 bg-[#8B5CF6]/10 text-[#A855F7]' : 'border-[#27213D] bg-[#151225] text-[#94A3B8] hover:text-[#A1A1AA]' }}">{{ $fl }}</button>
            @endforeach
        </div>
    </div>

    @if (($broadcasts ?? collect())->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="min-w-max w-full text-left text-sm">
            <thead class="border-b border-[#27213D] text-[10px] font-black uppercase tracking-widest text-[#52525B]">
                <tr>
                    <th class="whitespace-nowrap px-5 py-3">Campaign</th>
                    <th class="whitespace-nowrap px-5 py-3">Channels</th>
                    <th class="whitespace-nowrap px-5 py-3">Status</th>
                    <th class="whitespace-nowrap px-5 py-3">Recipients</th>
                    <th class="whitespace-nowrap px-5 py-3">Sent</th>
                    <th class="whitespace-nowrap px-5 py-3">Failed</th>
                    <th class="whitespace-nowrap px-5 py-3">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#1B172B]">
                @foreach ($broadcasts as $broadcast)
                @php
                    $sc = match($broadcast->status) {
                        'completed'           => ['#22C55E', '#22C55E12', '#22C55E35'],
                        'running', 'sending'  => ['#A855F7', '#A855F712', '#A855F735'],
                        'queued'              => ['#38BDF8', '#38BDF812', '#38BDF835'],
                        'failed'              => ['#EF4444', '#EF444412', '#EF444435'],
                        'cancelled'           => ['#71717A', '#71717A12', '#71717A35'],
                        'scheduled'           => ['#F59E0B', '#F59E0B12', '#F59E0B35'],
                        default               => ['#52525B', '#52525B12', '#52525B35'],
                    };
                @endphp
                <tr class="transition hover:bg-[#0A0818]/50">
                    <td class="px-5 py-3">
                        <p class="whitespace-nowrap font-black text-[#F8FAFC]">{{ $broadcast->title ?: '(No title)' }}</p>
                        <p class="text-[11px] text-[#52525B]">{{ $broadcast->campaign_name ?: 'Admin broadcast' }}</p>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3 text-xs text-[#94A3B8]">
                        {{ collect($broadcast->channels ?? [])->map(fn($ch) => ucfirst(str_replace('_', ' ', $ch)))->implode(', ') ?: '—' }}
                    </td>
                    <td class="px-5 py-3">
                        <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black"
                            style="color:{{ $sc[0] }};background-color:{{ $sc[1] }};border:1px solid {{ $sc[2] }}">
                            {{ ucfirst($broadcast->status) }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3 text-sm font-black text-[#A1A1AA]">{{ number_format($broadcast->total_recipients ?? 0) }}</td>
                    <td class="whitespace-nowrap px-5 py-3 text-sm font-black text-[#22C55E]">{{ number_format($broadcast->sent_count ?? 0) }}</td>
                    <td class="whitespace-nowrap px-5 py-3 text-sm font-black text-[#EF4444]">{{ number_format($broadcast->failed_count ?? 0) }}</td>
                    <td class="whitespace-nowrap px-5 py-3 text-xs text-[#94A3B8]">{{ $broadcast->created_at?->diffForHumans() ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="flex flex-col items-center gap-4 py-16 text-center">
        <div class="grid h-14 w-14 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
        </div>
        <div>
            <p class="text-base font-black text-[#A1A1AA]">No broadcasts yet</p>
            <p class="mt-1 max-w-xs text-sm text-[#52525B]">Create your first broadcast to reach users across selected channels.</p>
        </div>
    </div>
    @endif
</div>

{{-- SAFETY & COMPLIANCE --}}
<div class="mt-5 overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">
    <div class="flex items-center gap-2.5 border-b border-[#1B172B] px-5 py-3.5">
        <svg class="h-4 w-4 shrink-0 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
        <p class="text-xs font-black uppercase tracking-widest text-[#94A3B8]">Safety &amp; Compliance</p>
    </div>
    <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="space-y-2">
            <p class="text-[10px] font-black uppercase tracking-wider text-[#52525B]">Platform Rules</p>
            @foreach ([
                'Banned and deleted users are always excluded automatically.',
                'Admin broadcasts have platform-level reach — use carefully.',
                'Never include API keys, tokens, or passwords in messages.',
            ] as $rule)
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-3 w-3 shrink-0 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                <p class="text-xs text-[#94A3B8]">{{ $rule }}</p>
            </div>
            @endforeach
        </div>
        <div class="space-y-2">
            <p class="text-[10px] font-black uppercase tracking-wider text-[#52525B]">Delivery Safety</p>
            @foreach ([
                'Broadcasts send in safe batches with configurable delays.',
                'Batch controls prevent Telegram and email rate limiting.',
                'Always test with a small audience segment first.',
            ] as $rule)
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-3 w-3 shrink-0 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                <p class="text-xs text-[#94A3B8]">{{ $rule }}</p>
            </div>
            @endforeach
        </div>
        <div class="space-y-2">
            <p class="text-[10px] font-black uppercase tracking-wider text-[#52525B]">Plan Limits</p>
            @foreach ([
                ['Free',     'Bot broadcasts: 20,000',    '#71717A'],
                ['Pro',      'Bot broadcasts: 100,000',   '#38BDF8'],
                ['Business', 'Bot broadcasts: unlimited', '#A855F7'],
                ['Admin',    'Platform: all users',       '#EF4444'],
            ] as [$plan, $rule, $color])
            <div class="flex items-start gap-2">
                <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full" style="background-color:{{ $color }}"></span>
                <p class="text-xs text-[#94A3B8]"><span class="font-black" style="color:{{ $color }}">{{ $plan }}</span> — {{ $rule }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>

</x-admin-layout>
