<x-admin-layout title="Platform Settings" subtitle="Global platform configuration and administration">
<style>[x-cloak]{display:none!important}</style>

@php
    $currentTab = $tab ?? 'general';

    $tabs = [
        ['id' => 'general',       'label' => 'General',          'danger' => false,
         'icon' => 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z'],
        ['id' => 'branding',      'label' => 'Branding',         'danger' => false,
         'icon' => 'M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42'],
        ['id' => 'links',         'label' => 'Links',            'danger' => false,
         'icon' => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244'],
        ['id' => 'payments',      'label' => 'Payments',         'danger' => false,
         'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
        ['id' => 'webhooks',      'label' => 'Webhooks',         'danger' => false,
         'icon' => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244'],
        ['id' => 'triggers',      'label' => 'Trigger Webhooks', 'danger' => false,
         'icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z'],
        ['id' => 'storage',       'label' => 'Storage',          'danger' => false,
         'icon' => 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125'],
        ['id' => 'security',      'label' => 'Security',         'danger' => false,
         'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
        ['id' => 'notifications', 'label' => 'Notifications',    'danger' => false,
         'icon' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
        ['id' => 'automations',   'label' => 'Automations',      'danger' => false,
         'icon' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99'],
        ['id' => 'maintenance',   'label' => 'Maintenance',      'danger' => false,
         'icon' => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z'],
        ['id' => 'danger',        'label' => 'Danger Zone',      'danger' => true,
         'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
    ];

    $isMaintenance     = ($platformMode ?? 'live') === 'maintenance';
    $regEnabled        = $allowRegistration ?? true;
@endphp

<div x-data="{ tab: '{{ $currentTab }}' }" x-on:switch-tab.window="tab = $event.detail" class="flex flex-col gap-5 lg:flex-row lg:items-start">

    {{-- ═══════════════════════════════════════════
         LEFT SIDEBAR NAVIGATION
    ═══════════════════════════════════════════ --}}
    <div class="lg:sticky lg:top-0 lg:w-52 lg:shrink-0">

        {{-- Mobile: horizontal scroll --}}
        <div class="flex gap-1 overflow-x-auto pb-2 lg:hidden" style="-webkit-overflow-scrolling:touch;scrollbar-width:none;">
            @foreach($tabs as $t)
            <button
                @click="tab = '{{ $t['id'] }}'; history.replaceState(null, '', '?tab={{ $t['id'] }}')"
                :class="tab === '{{ $t['id'] }}'
                    ? '{{ $t['danger'] ? 'border-[#EF4444]/40 bg-[#EF4444]/10 text-[#EF4444]' : 'border-[#8B5CF6] bg-[#8B5CF6]/12 text-white' }}'
                    : '{{ $t['danger'] ? 'border-[#27213D] text-[#EF4444]/50' : 'border-[#27213D] text-[#71717A]' }}'"
                class="shrink-0 rounded-xl border px-3 py-2 text-[11px] font-semibold transition-all whitespace-nowrap"
            >{{ $t['label'] }}</button>
            @endforeach
        </div>

        {{-- Desktop: vertical nav --}}
        <nav class="hidden max-h-[calc(100vh-8rem)] overflow-y-auto rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-1.5 lg:flex lg:flex-col">
            @foreach($tabs as $t)
            <button
                @click="tab = '{{ $t['id'] }}'; history.replaceState(null, '', '?tab={{ $t['id'] }}')"
                :class="tab === '{{ $t['id'] }}'
                    ? '{{ $t['danger'] ? 'bg-[#EF4444]/8 text-[#EF4444]' : 'bg-[#8B5CF6]/12 text-[#F8FAFC]' }}'
                    : '{{ $t['danger'] ? 'text-[#EF4444]/60 hover:bg-[#EF4444]/5 hover:text-[#EF4444]' : 'text-[#71717A] hover:bg-[#151225] hover:text-[#F8FAFC]' }}'"
                class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all duration-150 {{ $loop->last ? 'mt-1 border-t border-[#27213D]/50 pt-2.5' : '' }}"
            >
                <span
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg transition-all"
                    :class="tab === '{{ $t['id'] }}'
                        ? '{{ $t['danger'] ? 'bg-[#EF4444]/15 text-[#EF4444]' : 'bg-[#8B5CF6] text-white shadow-[0_0_10px_rgba(139,92,246,0.5)]' }}'
                        : '{{ $t['danger'] ? 'text-[#EF4444]/40' : 'text-[#4D4868]' }}'"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $t['icon'] }}"/>
                    </svg>
                </span>
                <span class="flex-1 text-left">{{ $t['label'] }}</span>
                <span x-show="tab === '{{ $t['id'] }}'" x-cloak class="h-1.5 w-1.5 rounded-full {{ $t['danger'] ? 'bg-[#EF4444]' : 'bg-[#8B5CF6]' }}"></span>
            </button>
            @endforeach
        </nav>
    </div>

    {{-- ═══════════════════════════════════════════
         RIGHT CONTENT AREA
    ═══════════════════════════════════════════ --}}
    <div class="min-w-0 flex-1 space-y-4 pb-6">

        {{-- ───────────────────────────────
             GENERAL
        ─────────────────────────────── --}}
        <div x-show="tab === 'general'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.general.save') }}" class="space-y-4">
                @csrf
                @if($errors->any() && $currentTab === 'general')
                    <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/8 px-4 py-3 text-sm text-[#EF4444]">{{ $errors->first() }}</div>
                @endif

                {{-- Header --}}
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-[#F8FAFC]">General Settings</h2>
                        <p class="text-xs text-[#71717A] mt-0.5">Core platform identity and behavior.</p>
                    </div>
                    <button type="submit" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition shrink-0">Save Changes</button>
                </div>

                {{-- Platform Identity --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">Platform Identity</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Platform Name</label>
                            <input type="text" name="platform_name" value="{{ old('platform_name', $platformName) }}"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                            <p class="mt-1 text-[11px] text-[#71717A]">Shown in page titles and emails.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Support Email</label>
                            <input type="email" name="support_email" value="{{ old('support_email', $supportEmail) }}" placeholder="support@yourdomain.com"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Default Currency</label>
                            <div class="relative" x-data="{ open: false, val: '{{ old('default_currency', $defaultCurrency) }}', labels: { 'USD': 'USD — US Dollar', 'EUR': 'EUR — Euro', 'GBP': 'GBP — British Pound', 'CAD': 'CAD — Canadian Dollar', 'AUD': 'AUD — Australian Dollar' }, get label() { return this.labels[this.val] || 'USD — US Dollar' } }" @click.away="open = false">
                                <input type="hidden" name="default_currency" :value="val">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                                    <span x-text="label"></span>
                                    <svg class="ml-2 h-4 w-4 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                    @foreach(['USD' => 'USD — US Dollar', 'EUR' => 'EUR — Euro', 'GBP' => 'GBP — British Pound', 'CAD' => 'CAD — Canadian Dollar', 'AUD' => 'AUD — Australian Dollar'] as $cv => $cl)
                                    <button type="button" @click="val = '{{ $cv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $cv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                        <svg :class="val === '{{ $cv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        {{ $cl }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Platform Mode</label>
                            <div class="relative" x-data="{ open: false, val: '{{ old('platform_mode', $platformMode) }}', get label() { return { 'live': 'Live — Open to all users', 'maintenance': 'Maintenance — Admin only' }[this.val] || 'Live — Open to all users' } }" @click.away="open = false">
                                <input type="hidden" name="platform_mode" :value="val">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                                    <span x-text="label"></span>
                                    <svg class="ml-2 h-4 w-4 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                    @foreach(['live' => 'Live — Open to all users', 'maintenance' => 'Maintenance — Admin only'] as $pmv => $pml)
                                    <button type="button" @click="val = '{{ $pmv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $pmv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                        <svg :class="val === '{{ $pmv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        {{ $pml }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- URL Info --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">URL Configuration</h3>
                    <p class="text-[11px] text-[#71717A]">APP_URL comes from your environment. The public callback URL can be updated in the Webhooks section.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-xl border border-[#27213D] bg-[#11101C] p-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A] mb-1">Local App URL <span class="text-[#4D4868]">(APP_URL)</span></p>
                            <p class="font-mono text-xs text-[#A1A1AA] break-all">{{ $appUrl ?: '(not set)' }}</p>
                            <p class="mt-1 text-[10px] text-[#71717A]">Browser navigation, return redirects.</p>
                        </div>
                        <div class="rounded-xl border border-[#27213D] bg-[#11101C] p-3">
                            <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A] mb-1">Public Callback URL <span class="text-[#4D4868]">(APP_PUBLIC_URL)</span></p>
                            <p class="font-mono text-xs text-[#A1A1AA] break-all">{{ $appPublicUrl ?: '(not set)' }}</p>
                            <p class="mt-1 text-[10px] text-[#71717A]">External webhooks, Telegram, OxaPay callbacks.</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        {{-- ───────────────────────────────
             BRANDING
        ─────────────────────────────── --}}
        <div x-show="tab === 'branding'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.branding.save') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                {{-- ── Page header ────────────────────────────────── --}}
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-[#F8FAFC]">Branding Settings</h2>
                        <p class="text-xs text-[#71717A] mt-0.5">Customize the platform's visual identity — logos, colors, and page text.</p>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition shrink-0">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Save Changes
                    </button>
                </div>

                {{-- ── Platform Logo ──────────────────────────────── --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] overflow-hidden">
                    <div class="px-5 pt-5 pb-4 border-b border-[#1B172B]">
                        <p class="text-xs font-black uppercase tracking-widest text-[#71717A]">Platform Logo</p>
                        <p class="mt-0.5 text-[11px] text-[#52525B]">Displayed on the marketing site, login page, and user dashboard sidebar.</p>
                    </div>
                    <div class="flex flex-col lg:flex-row">
                        {{-- Wide transparent-grid preview area --}}
                        <div class="flex-1 flex flex-col items-center justify-center p-10 min-h-[148px]"
                             style="background-color:#07060F;background-image:linear-gradient(45deg,#0F0C1C 25%,transparent 25%),linear-gradient(-45deg,#0F0C1C 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#0F0C1C 75%),linear-gradient(-45deg,transparent 75%,#0F0C1C 75%);background-size:18px 18px;background-position:0 0,0 9px,9px -9px,-9px 0">
                            @if($platformLogoUrl)
                                <img src="{{ $platformLogoUrl }}" alt="Platform Logo" class="h-12 w-auto max-w-[240px] object-contain">
                                <span class="mt-3 text-[10px] font-semibold uppercase tracking-wide text-[#3D3657]">Current logo</span>
                            @else
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-7 w-7 text-[#27213D]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    <p class="text-[11px] text-[#3D3657]">No platform logo uploaded</p>
                                </div>
                            @endif
                        </div>
                        {{-- Upload panel --}}
                        <div class="shrink-0 border-t lg:border-t-0 lg:border-l border-[#1B172B] p-5 space-y-3 lg:w-60">
                            <label class="flex w-full cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-[#27213D] bg-[#11101C] p-4 text-center transition hover:border-[#8B5CF6]/50 hover:bg-[#151225]">
                                <svg class="h-5 w-5 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                <div>
                                    <p class="text-[11px] font-bold text-[#A1A1AA]">Upload new logo</p>
                                    <p class="mt-0.5 text-[10px] text-[#52525B]">PNG, JPG, WEBP, SVG</p>
                                </div>
                                <input type="file" name="platform_logo" class="sr-only" accept=".png,.jpg,.jpeg,.webp,.svg">
                            </label>
                            <p class="text-[10px] leading-relaxed text-[#3D3657]">Use a wide transparent PNG or SVG for best results. Replaces the existing logo on save.</p>
                        </div>
                    </div>
                </div>

                {{-- ── Admin Logo ──────────────────────────────────── --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] overflow-hidden">
                    <div class="px-5 pt-5 pb-4 border-b border-[#1B172B]">
                        <p class="text-xs font-black uppercase tracking-widest text-[#71717A]">Admin Logo</p>
                        <p class="mt-0.5 text-[11px] text-[#52525B]">Shown in the admin panel sidebar. Falls back to the Platform Logo if not set.</p>
                    </div>
                    <div class="flex flex-col lg:flex-row">
                        {{-- Wide transparent-grid preview area --}}
                        <div class="flex-1 flex flex-col items-center justify-center p-10 min-h-[148px]"
                             style="background-color:#07060F;background-image:linear-gradient(45deg,#0F0C1C 25%,transparent 25%),linear-gradient(-45deg,#0F0C1C 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#0F0C1C 75%),linear-gradient(-45deg,transparent 75%,#0F0C1C 75%);background-size:18px 18px;background-position:0 0,0 9px,9px -9px,-9px 0">
                            @if($adminLogoUrl)
                                <img src="{{ $adminLogoUrl }}" alt="Admin Logo" class="h-12 w-auto max-w-[240px] object-contain">
                                <span class="mt-3 text-[10px] font-semibold uppercase tracking-wide text-[#3D3657]">
                                    {{ ($adminLogoUrl === $platformLogoUrl && $platformLogoUrl) ? 'Inheriting Platform Logo' : 'Current admin logo' }}
                                </span>
                            @else
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-7 w-7 text-[#27213D]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    <p class="text-[11px] text-[#3D3657]">No admin logo — using default icon</p>
                                </div>
                            @endif
                        </div>
                        {{-- Upload panel --}}
                        <div class="shrink-0 border-t lg:border-t-0 lg:border-l border-[#1B172B] p-5 space-y-3 lg:w-60">
                            <label class="flex w-full cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-[#27213D] bg-[#11101C] p-4 text-center transition hover:border-[#8B5CF6]/50 hover:bg-[#151225]">
                                <svg class="h-5 w-5 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                <div>
                                    <p class="text-[11px] font-bold text-[#A1A1AA]">Upload admin logo</p>
                                    <p class="mt-0.5 text-[10px] text-[#52525B]">PNG, JPG, WEBP, SVG</p>
                                </div>
                                <input type="file" name="admin_logo" class="sr-only" accept=".png,.jpg,.jpeg,.webp,.svg">
                            </label>
                            <p class="text-[10px] leading-relaxed text-[#3D3657]">Leave blank to inherit the Platform Logo. Replaces the existing admin logo on save.</p>
                        </div>
                    </div>
                </div>

                {{-- ── Favicon ─────────────────────────────────────── --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                        <div class="flex items-start gap-4 flex-1 min-w-0">
                            <div class="h-14 w-14 shrink-0 rounded-xl border border-[#1B172B] bg-[#060510] flex items-center justify-center overflow-hidden">
                                @if($faviconUrl)
                                    <img src="{{ $faviconUrl }}" alt="Favicon" class="h-10 w-10 object-contain">
                                @else
                                    <svg class="h-5 w-5 text-[#27213D]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                @endif
                            </div>
                            <div class="min-w-0 pt-0.5">
                                <p class="text-xs font-black uppercase tracking-widest text-[#71717A]">Favicon</p>
                                <p class="mt-0.5 text-[11px] text-[#52525B] leading-relaxed">Shown in browser tabs and bookmarks. Use a square ICO, PNG, or SVG — 32×32 or 64×64 px recommended.</p>
                            </div>
                        </div>
                        <label class="block shrink-0 sm:w-52 cursor-pointer rounded-xl border border-dashed border-[#27213D] bg-[#11101C] p-3 text-center transition hover:border-[#8B5CF6]/50 hover:bg-[#151225]">
                            <svg class="mx-auto mb-1 h-4 w-4 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                            <p class="text-[11px] font-bold text-[#A1A1AA]">Upload favicon</p>
                            <p class="text-[10px] text-[#52525B]">ICO, PNG, SVG</p>
                            <input type="file" name="favicon" class="sr-only" accept=".png,.jpg,.jpeg,.webp,.svg,.ico">
                        </label>
                    </div>
                </div>

                {{-- ── Brand Colors ─────────────────────────────────── --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-[#71717A]">Brand Colors</p>
                        <p class="mt-0.5 text-[11px] text-[#52525B]">Primary and accent colors used across the platform UI.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Primary Color</label>
                            <div class="flex gap-2">
                                <input type="color" name="platform_primary_color" value="{{ old('platform_primary_color', $primaryColor) }}"
                                    class="h-10 w-16 rounded-xl border border-[#27213D] bg-[#11101C] cursor-pointer">
                                <input type="text" value="{{ old('platform_primary_color', $primaryColor) }}"
                                    oninput="this.previousElementSibling.value=this.value;this.closest('div').querySelector('[name=platform_primary_color]').value=this.value"
                                    class="flex-1 rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] font-mono outline-none transition focus:border-[#8B5CF6]/60">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Accent Color</label>
                            <div class="flex gap-2">
                                <input type="color" name="platform_accent_color" value="{{ old('platform_accent_color', $accentColor) }}"
                                    class="h-10 w-16 rounded-xl border border-[#27213D] bg-[#11101C] cursor-pointer">
                                <input type="text" value="{{ old('platform_accent_color', $accentColor) }}"
                                    oninput="this.previousElementSibling.value=this.value;this.closest('div').querySelector('[name=platform_accent_color]').value=this.value"
                                    class="flex-1 rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] font-mono outline-none transition focus:border-[#8B5CF6]/60">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Login Page & Site Text ───────────────────────── --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-[#71717A]">Login Page & Site Text</p>
                        <p class="mt-0.5 text-[11px] text-[#52525B]">Text displayed on the login screen and across public page footers.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Login Title</label>
                        <input type="text" name="login_page_title" value="{{ old('login_page_title', $loginTitle) }}" placeholder="Sign in to BotHost Pro"
                            class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Login Subtitle</label>
                        <input type="text" name="login_page_subtitle" value="{{ old('login_page_subtitle', $loginSubtitle) }}" placeholder="Build powerful Telegram bots"
                            class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Footer Text</label>
                        <input type="text" name="footer_text" value="{{ old('footer_text', $footerText) }}" placeholder="© 2026 BotHost Pro. All rights reserved."
                            class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                        <p class="mt-1 text-[11px] text-[#52525B]">Shown at the bottom of all public and auth pages.</p>
                    </div>
                </div>

                {{-- ── Save footer bar ──────────────────────────────── --}}
                <div class="flex items-center justify-between gap-3 rounded-2xl border border-[#1B172B] bg-[#0B0A14] px-5 py-4">
                    <p class="text-[11px] text-[#3D3657]">Uploading a new file replaces the existing one. All changes apply after saving.</p>
                    <button type="submit" class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-[#8B5CF6] px-5 py-2.5 text-xs font-black text-white hover:bg-[#7C3AED] transition shadow-[0_0_18px_rgba(139,92,246,0.25)]">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Save Branding
                    </button>
                </div>
            </form>
        </div>


        {{-- ───────────────────────────────
             LINKS
        ─────────────────────────────── --}}
        <div x-show="tab === 'links'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.links.save') }}" class="space-y-4">
                @csrf
                @if($errors->any() && $currentTab === 'links')
                    <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/8 px-4 py-3 text-sm text-[#EF4444]">{{ $errors->first() }}</div>
                @endif

                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-[#F8FAFC]">Link Settings</h2>
                        <p class="text-xs text-[#71717A] mt-0.5">Configure external URLs shown on the Dashboard, Help, and Support pages.</p>
                    </div>
                    <button type="submit" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition shrink-0">Save Changes</button>
                </div>

                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-5">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">Dashboard Help Cards</h3>
                    <p class="text-[11px] text-[#71717A] -mt-2">These URLs power the two community cards at the bottom of the Dashboard. Leave blank to disable linking.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Telegram Community URL</label>
                            <input type="url" name="telegram_community_url"
                                value="{{ old('telegram_community_url', $telegramCommunityUrl) }}"
                                placeholder="https://t.me/your_group"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                            <p class="mt-1 text-[11px] text-[#71717A]">Shown as the "Telegram Community" card on the Dashboard.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Tutorials / Learn URL</label>
                            <input type="url" name="tutorials_url"
                                value="{{ old('tutorials_url', $tutorialsUrl) }}"
                                placeholder="https://docs.yourdomain.com/tutorials"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                            <p class="mt-1 text-[11px] text-[#71717A]">Shown as the "Learn to Make Bots" card on the Dashboard.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">Contact Support URL</h3>
                    <p class="text-[11px] text-[#71717A] -mt-2">Shown as a "Contact Support" button on the Help and Support pages. Accepts any URL — e.g. a Telegram group, Discord invite, or help desk link.</p>

                    <div>
                        <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Support URL</label>
                        <input type="text" name="support_url"
                            value="{{ old('support_url', $supportUrl) }}"
                            placeholder="https://t.me/your_support_bot"
                            class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                        <p class="mt-1 text-[11px] text-[#71717A]">Renders as a "Contact Support" button on the Help and Support pages.</p>
                    </div>
                </div>
            </form>
        </div>


        {{-- ───────────────────────────────
             PAYMENTS
        ─────────────────────────────── --}}
        <div x-show="tab === 'payments'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.payments.save') }}" class="space-y-4">
                @csrf

                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-[#F8FAFC]">Payment Settings</h2>
                        <p class="text-xs text-[#71717A] mt-0.5">OxaPay White Label cryptocurrency payment configuration.</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="rounded-full border px-2.5 py-1 text-[10px] font-black {{ $providerConfigured ? 'border-[#22C55E]/30 text-[#22C55E]' : 'border-[#F59E0B]/30 text-[#F59E0B]' }}">
                            {{ $providerConfigured ? 'Configured' : 'Not Configured' }}
                        </span>
                        <button type="submit" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition">Save Changes</button>
                    </div>
                </div>

                {{-- API Config --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">OxaPay Merchant</h3>

                    <div>
                        <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Merchant API Key</label>
                        <input type="password" name="merchant_api_key" placeholder="{{ $maskedApiKey ? 'Leave blank to keep current key' : 'Enter OxaPay merchant API key' }}"
                            class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60">
                        @if($maskedApiKey)
                            <p class="mt-1 text-[11px] text-[#71717A]">Saved key: <span class="font-mono text-[#A1A1AA]">{{ $maskedApiKey }}</span></p>
                        @endif
                        <p class="mt-1 text-[11px] text-[#71717A]">Stored encrypted. Never displayed in full.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Base URL</label>
                            <input type="url" name="base_url" value="{{ old('base_url', $oxaPayBaseUrl) }}"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] font-mono outline-none transition focus:border-[#8B5CF6]/60">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Invoice Lifetime (min)</label>
                            <input type="number" name="invoice_lifetime" min="15" max="2880" value="{{ old('invoice_lifetime', $invoiceLifetime) }}"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] outline-none transition focus:border-[#8B5CF6]/60">
                            <p class="mt-1 text-[11px] text-[#71717A]">15–2880 (default 60)</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Underpaid Coverage (%)</label>
                        <input type="number" name="under_paid_coverage" min="0" max="100" step="0.01" value="{{ old('under_paid_coverage', $underPaidCoverage) }}" placeholder="Leave empty for OxaPay default"
                            class="w-full sm:w-72 rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60">
                        <p class="mt-1 text-[11px] text-[#71717A]">Percentage underpayment OxaPay will accept.</p>
                    </div>
                </div>

                {{-- Toggles --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A] mb-4">Options</h3>

                    @php
                        $payToggles = [
                            ['key' => 'oxapay_enabled',    'val' => $oxaPayEnabled,  'label' => 'Enable OxaPay Payments',    'desc' => 'Allow crypto payment processing via OxaPay.'],
                            ['key' => 'fee_paid_by_user',  'val' => $feePaidByUser,  'label' => 'User Pays Network Fee',     'desc' => 'Pass the blockchain network fee to the payer.'],
                            ['key' => 'sandbox',           'val' => $oxaPaySandbox,  'label' => 'Sandbox / Test Mode',       'desc' => 'Use OxaPay test environment. Disable for production.'],
                        ];
                    @endphp

                    @foreach($payToggles as $tog)
                    <div x-data="{ val: {{ $tog['val'] ? 'true' : 'false' }} }" class="flex items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3">
                        <div class="flex-1 min-w-0 mr-4">
                            <p class="text-sm font-semibold text-[#F8FAFC]">{{ $tog['label'] }}</p>
                            <p class="text-[11px] text-[#71717A] mt-0.5">{{ $tog['desc'] }}</p>
                        </div>
                        <input type="hidden" name="{{ $tog['key'] }}" :value="val ? '1' : '0'">
                        <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'"
                            class="relative h-6 w-11 rounded-full transition-colors duration-200 shrink-0 focus:outline-none overflow-hidden">
                            <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'" class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                        </button>
                    </div>
                    @endforeach

                    @if($oxaPaySandbox)
                    <div class="rounded-xl border border-[#F59E0B]/25 bg-[#F59E0B]/8 px-4 py-3 text-xs text-[#F59E0B] font-semibold">
                        Sandbox mode is active. Invoices use OxaPay test environment. Disable before going live.
                    </div>
                    @endif
                </div>

                {{-- Webhook URLs --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">Webhook Callback URLs</h3>
                    <p class="text-[11px] text-[#71717A]">Copy the URL below and enter it in your OxaPay merchant dashboard as the callback URL.</p>

                    @foreach([
                        ['label' => 'OxaPay Webhook URL', 'url' => $oxaPayWebhookUrl, 'note' => 'Use this single URL for template purchases and plan upgrades.'],
                    ] as $wh)
                    <div>
                        <p class="text-xs font-bold text-[#A1A1AA] mb-1">{{ $wh['label'] }}</p>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2 font-mono text-[11px] text-[#A1A1AA] break-all">{{ $wh['url'] }}</code>
                            <button type="button"
                                onclick="navigator.clipboard.writeText('{{ $wh['url'] }}').then(()=>{this.textContent='Copied!';this.classList.add('text-[#22C55E]');setTimeout(()=>{this.textContent='Copy';this.classList.remove('text-[#22C55E]')},2000)})"
                                class="shrink-0 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-xs font-bold text-[#A1A1AA] hover:text-white transition">Copy</button>
                        </div>
                        <p class="mt-1 text-[10px] text-[#71717A]">{{ $wh['note'] }}</p>
                    </div>
                    @endforeach
                </div>
            </form>
        </div>


        {{-- ───────────────────────────────
             WEBHOOKS
        ─────────────────────────────── --}}
        <div x-show="tab === 'webhooks'" x-cloak class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-black text-[#F8FAFC]">Webhook Settings</h2>
                    <p class="text-xs text-[#71717A] mt-0.5">Platform webhook endpoints and Telegram webhook administration.</p>
                </div>
            </div>

            {{-- Public URL --}}
            <form method="POST" action="{{ route('admin.settings.webhooks.public-url.save') }}" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                @csrf
                <div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">Public Callback URL</h3>
                    <p class="mt-1 text-xs text-[#71717A]">Used for Telegram, OxaPay, and other external webhook callbacks.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Public URL</label>
                    <input type="url" name="app_public_url" value="{{ old('app_public_url', $appPublicUrl) }}" placeholder="https://your-tunnel.example.com"
                        class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 font-mono text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60">
                    @error('app_public_url')
                        <p class="mt-1 text-[11px] font-semibold text-[#EF4444]">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-[11px] text-[#71717A]">Must be a public HTTPS URL, for example a trycloudflare.com tunnel.</p>
                    @enderror
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition">Save Public URL</button>
                </div>
            </form>

            {{-- URL Reference --}}
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">Endpoint Reference</h3>

                @php
                    $publicBase = rtrim((string) $appPublicUrl, '/');
                    $localBase  = rtrim((string) config('app.url'), '/');
                    $webhookUrls = [
                        ['label' => 'Public Callback Base URL',    'url' => $publicBase,                              'note' => 'Used for all external callbacks (OxaPay, Telegram).'],
                        ['label' => 'OxaPay Webhook',              'url' => $publicBase.'/webhooks/oxapay',           'note' => 'Primary OxaPay callback URL.'],
                        ['label' => 'Telegram Webhook Base',       'url' => $publicBase.'/telegram/webhook/{bot}/{secret}', 'note' => 'Per-bot Telegram webhook. {bot} = bot ID, {secret} = webhook_secret.'],
                    ];
                @endphp

                @foreach($webhookUrls as $whu)
                <div>
                    <p class="text-xs font-bold text-[#A1A1AA] mb-1">{{ $whu['label'] }}</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2 font-mono text-[11px] text-[#A1A1AA] break-all">{{ $whu['url'] }}</code>
                        @if(! str_contains($whu['url'], '{'))
                        <button type="button"
                            onclick="navigator.clipboard.writeText('{{ $whu['url'] }}').then(()=>{this.textContent='Copied!';this.classList.add('text-[#22C55E]');setTimeout(()=>{this.textContent='Copy';this.classList.remove('text-[#22C55E]')},2000)})"
                            class="shrink-0 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-xs font-bold text-[#A1A1AA] hover:text-white transition">Copy</button>
                        @endif
                    </div>
                    <p class="mt-1 text-[10px] text-[#71717A]">{{ $whu['note'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- Telegram Webhook Reset Tool --}}
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
                <div class="flex items-start gap-4">
                    <div class="h-10 w-10 rounded-xl bg-[#8B5CF6]/12 border border-[#8B5CF6]/20 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-[#8B5CF6]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-black text-[#F8FAFC]">Reset Telegram Webhooks</h3>
                        <p class="text-xs text-[#71717A] mt-1 leading-relaxed">Set the public callback URL, then refresh every verified bot webhook immediately. Successful bots are put back into running state.</p>
                        @if($errors->has('webhook'))
                            <p class="mt-2 text-xs font-semibold text-[#EF4444]">{{ $errors->first('webhook') }}</p>
                        @endif
                        <div x-data="{}">
                        <form x-ref="maintResetWebhookForm" method="POST" action="{{ route('admin.settings.webhooks.reset-telegram') }}" class="mt-3 space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Public Callback URL</label>
                                <input type="url" name="app_public_url" value="{{ old('app_public_url', $appPublicUrl) }}" placeholder="https://your-tunnel.example.com"
                                    class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 font-mono text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60">
                                <p class="mt-1 text-[11px] text-[#71717A]">Example: https://circus-mineral-ancient-yield.trycloudflare.com</p>
                            </div>
                            <button
                                type="button"
                                @click="$store.confirm.show({
                                    type: 'warning',
                                    title: 'Reset webhooks for all bots?',
                                    message: 'This will save the public callback URL, update Telegram webhooks for all verified non-suspended bots, and start them.',
                                    confirmText: 'Reset All Webhooks',
                                    onAccept: () => $refs.maintResetWebhookForm.submit()
                                })"
                                class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition"
                            >Reset Webhooks for All Bots</button>
                        </form>
                        </div>
                    </div>
                </div>
                @if(! filled($appPublicUrl))
                    <div class="mt-4 rounded-xl border border-[#F59E0B]/25 bg-[#F59E0B]/8 px-4 py-3 text-xs text-[#F59E0B] font-semibold">
                        Public callback URL is not set. Enter a public HTTPS URL before using this tool.
                    </div>
                @endif
            </div>
        </div>


        {{-- ───────────────────────────────
             TRIGGER WEBHOOKS
        ─────────────────────────────── --}}
        <div x-show="tab === 'triggers'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.triggers.save') }}" class="space-y-4">
                @csrf

                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-[#F8FAFC]">Trigger Webhooks</h2>
                        <p class="text-xs text-[#71717A] mt-0.5">Control platform event triggers for user-configured custom webhook endpoints.</p>
                    </div>
                    <button type="submit" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition shrink-0">Save Changes</button>
                </div>

                {{-- Master Switch --}}
                <div x-data="{ val: {{ $triggerWebhooksEnabled ? 'true' : 'false' }} }"
                     class="rounded-2xl border bg-[#0F0D1A] p-5"
                     :class="val ? 'border-[#8B5CF6]/25' : 'border-[#27213D]'">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-black text-[#F8FAFC]">Trigger Webhooks (Global)</p>
                            <p class="text-xs text-[#71717A] mt-0.5">Master switch. When off, no trigger webhook events will fire for any user.</p>
                        </div>
                        <input type="hidden" name="trigger_webhooks_enabled" :value="val ? '1' : '0'">
                        <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'"
                            class="relative h-6 w-11 rounded-full transition-colors duration-200 shrink-0 focus:outline-none overflow-hidden">
                            <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'" class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                        </button>
                    </div>
                    <p x-show="val" class="mt-3 text-[11px] text-[#22C55E] font-semibold">Active — platform event triggers are enabled for users with custom webhooks configured.</p>
                </div>

                {{-- Per-Event Toggles --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A] mb-4">Event Triggers</h3>

                    @php
                        $triggerEvents = [
                            ['key' => 'trigger_webhook_payment_success',   'val' => $triggerPaymentSuccess,   'label' => 'Payment Success',      'desc' => 'Fires when a payment is confirmed (plan upgrade, template purchase).'],
                            ['key' => 'trigger_webhook_template_purchase', 'val' => $triggerTemplatePurchase, 'label' => 'Template Purchased',   'desc' => 'Fires when a user purchases or unlocks a template.'],
                            ['key' => 'trigger_webhook_plan_upgrade',      'val' => $triggerPlanUpgrade,      'label' => 'Plan Upgraded',        'desc' => 'Fires when a user upgrades their subscription plan.'],
                            ['key' => 'trigger_webhook_bot_created',       'val' => $triggerBotCreated,       'label' => 'Bot Created',          'desc' => 'Fires when a user creates a new bot.'],
                            ['key' => 'trigger_webhook_command_error',     'val' => $triggerCommandError,     'label' => 'Command Runtime Error', 'desc' => 'Fires when a bot command throws a runtime error (high volume, use cautiously).'],
                        ];
                    @endphp

                    @foreach($triggerEvents as $evt)
                    <div x-data="{ val: {{ $evt['val'] ? 'true' : 'false' }} }" class="flex items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3">
                        <div class="flex-1 min-w-0 mr-4">
                            <p class="text-sm font-semibold text-[#F8FAFC]">{{ $evt['label'] }}</p>
                            <p class="text-[11px] text-[#71717A] mt-0.5">{{ $evt['desc'] }}</p>
                        </div>
                        <input type="hidden" name="{{ $evt['key'] }}" :value="val ? '1' : '0'">
                        <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'"
                            class="relative h-6 w-11 rounded-full transition-colors duration-200 shrink-0 focus:outline-none overflow-hidden">
                            <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'" class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                        </button>
                    </div>
                    @endforeach
                </div>

                <div class="rounded-xl border border-[#38BDF8]/20 bg-[#38BDF8]/5 px-4 py-3 text-[11px] text-[#38BDF8]">
                    <strong class="font-black">Note:</strong> User-level custom webhook URLs will be configurable in user account settings (coming soon). The platform infrastructure is ready.
                </div>
            </form>
        </div>


        {{-- ───────────────────────────────
             STORAGE
        ─────────────────────────────── --}}
        <div x-show="tab === 'storage'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.storage.save') }}" class="space-y-4">
                @csrf

                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-[#F8FAFC]">Storage Settings</h2>
                        <p class="text-xs text-[#71717A] mt-0.5">Control how files and bot storage are managed.</p>
                    </div>
                    <button type="submit" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition shrink-0">Save Changes</button>
                </div>

                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">Storage Configuration</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Default Storage Disk</label>
                            <div class="relative" x-data="{ open: false, val: '{{ old('storage_default_disk', $storageDisk) }}', get label() { return { 'local': 'local', 'public': 'public', 's3': 's3' }[this.val] || 'local' } }" @click.away="open = false">
                                <input type="hidden" name="storage_default_disk" :value="val">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                                    <span x-text="label"></span>
                                    <svg class="ml-2 h-4 w-4 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                    @foreach(['local' => 'local', 'public' => 'public', 's3' => 's3'] as $dkv => $dkl)
                                    <button type="button" @click="val = '{{ $dkv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $dkv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                        <svg :class="val === '{{ $dkv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        {{ $dkl }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Warning Threshold (%)</label>
                            <input type="number" name="storage_warning_threshold_percent" min="50" max="99" value="{{ old('storage_warning_threshold_percent', $storageWarningThreshold) }}"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] outline-none transition focus:border-[#8B5CF6]/60">
                            <p class="mt-1 text-[11px] text-[#71717A]">Show warning at this usage %.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Critical Threshold (%)</label>
                            <input type="number" name="storage_critical_threshold_percent" min="51" max="100" value="{{ old('storage_critical_threshold_percent', $storageCriticalThreshold) }}"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] outline-none transition focus:border-[#8B5CF6]/60">
                            <p class="mt-1 text-[11px] text-[#71717A]">Deny uploads at this usage %.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A] mb-4">Storage Behavior</h3>

                    @php
                        $storeToggles = [
                            ['key' => 'storage_tracking_enabled',  'val' => $storageTrackingEnabled,  'label' => 'Storage Usage Tracking',      'desc' => 'Track and display per-user storage consumption.'],
                            ['key' => 'clear_bot_storage_on_delete','val' => $clearBotStorageOnDelete, 'label' => 'Clear Bot Storage on Delete',  'desc' => 'Automatically delete broadcast files when a bot is deleted. Frees storage.'],
                        ];
                    @endphp

                    @foreach($storeToggles as $tog)
                    <div x-data="{ val: {{ $tog['val'] ? 'true' : 'false' }} }" class="flex items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3">
                        <div class="flex-1 min-w-0 mr-4">
                            <p class="text-sm font-semibold text-[#F8FAFC]">{{ $tog['label'] }}</p>
                            <p class="text-[11px] text-[#71717A] mt-0.5">{{ $tog['desc'] }}</p>
                        </div>
                        <input type="hidden" name="{{ $tog['key'] }}" :value="val ? '1' : '0'">
                        <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'"
                            class="relative h-6 w-11 rounded-full transition-colors duration-200 shrink-0 focus:outline-none overflow-hidden">
                            <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'" class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                        </button>
                    </div>
                    @endforeach
                </div>
            </form>
        </div>


        {{-- ───────────────────────────────
             SECURITY
        ─────────────────────────────── --}}
        <div x-show="tab === 'security'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.security.save') }}" class="space-y-5">
                @csrf

                @php $mailConfigured = filled($mailFromAddress ?? '') && filled($mailHost ?? ''); @endphp

                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-[#F8FAFC]">Security Settings</h2>
                        <p class="text-xs text-[#71717A] mt-0.5">Authentication, registration, and access controls.</p>
                    </div>
                    <button type="submit" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition shrink-0">Save Changes</button>
                </div>

                {{-- ── Email Verification ── --}}
                <div
                    x-data="{ val: {{ $requireEmailVerification ? 'true' : 'false' }} }"
                    class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-[#F8FAFC]">Require Email Verification</p>
                            <p class="text-[11px] text-[#71717A] mt-0.5">New users must verify their email before accessing the dashboard.</p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 shrink-0">
                            <input type="hidden" name="require_email_verification" :value="val ? '1' : '0'">
                            <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'"
                                class="relative h-6 w-11 rounded-full transition-colors duration-200 focus:outline-none overflow-hidden">
                                <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'"
                                    class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                            </button>
                            <span x-show="val" x-cloak class="text-[10px] font-semibold text-[#8B5CF6]">Enabled</span>
                            <span x-show="!val" class="text-[10px] font-semibold text-[#71717A]">Disabled</span>
                        </div>
                    </div>

                    <div x-show="val" x-cloak class="space-y-2.5">
                        @if ($mailConfigured)
                            <div class="flex items-center gap-2.5 rounded-xl border border-[#22C55E]/25 bg-[#22C55E]/8 px-3.5 py-2.5">
                                <svg class="h-4 w-4 shrink-0 text-[#22C55E]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-[11px] text-[#22C55E]">Email sending is configured. Verification emails will be sent using your configured mail settings.</p>
                            </div>
                        @else
                            <div class="flex items-start gap-2.5 rounded-xl border border-[#F59E0B]/25 bg-[#F59E0B]/8 px-3.5 py-2.5">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#F59E0B]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                                <div>
                                    <p class="text-[11px] font-semibold text-[#F59E0B]">Email sending is not fully configured.</p>
                                    <p class="mt-0.5 text-[11px] text-[#A1A1AA]">Configure mail settings under <button type="button" @click="$dispatch('switch-tab', 'notifications')" class="underline text-[#38BDF8] hover:text-[#7DD3FC] transition">Notifications</button> before requiring verification.</p>
                                </div>
                            </div>
                        @endif
                        <p class="text-[11px] text-[#4B4565]">Verification emails use the sender address configured in Notifications → Email Sending Settings.</p>
                    </div>
                </div>

                {{-- ── Allow Registrations ── --}}
                <div
                    x-data="{ val: {{ $allowRegistration ? 'true' : 'false' }} }"
                    class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-bold text-[#F8FAFC]">Allow New Registrations</p>
                                <span :class="val ? 'bg-[#22C55E]/10 text-[#22C55E] border-[#22C55E]/25' : 'bg-[#EF4444]/10 text-[#EF4444] border-[#EF4444]/25'"
                                    class="inline-flex items-center rounded-lg border px-2 py-0.5 text-[10px] font-semibold transition-colors">
                                    <span x-text="val ? 'Registration Open' : 'Registration Closed'"></span>
                                </span>
                            </div>
                            <p class="text-[11px] text-[#71717A] mt-1">When disabled, visitors cannot create new accounts. Existing users can still log in.</p>
                        </div>
                        <div class="shrink-0">
                            <input type="hidden" name="allow_registration" :value="val ? '1' : '0'">
                            <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'"
                                class="relative h-6 w-11 rounded-full transition-colors duration-200 focus:outline-none overflow-hidden">
                                <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'"
                                    class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ── Admin Access During Maintenance ── --}}
                <div
                    x-data="{ val: {{ $adminMaintenanceAccess ? 'true' : 'false' }}, showIps: false }"
                    class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-[#F8FAFC]">Admin Access During Maintenance</p>
                            <p class="text-[11px] text-[#71717A] mt-0.5">Allow admins to access the platform while normal users see the maintenance page.</p>
                            <p class="text-[11px] text-[#4B4565] mt-1">Admins bypass maintenance mode after logging in.</p>
                        </div>
                        <div class="shrink-0">
                            <input type="hidden" name="admin_maintenance_access" :value="val ? '1' : '0'">
                            <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'"
                                class="relative h-6 w-11 rounded-full transition-colors duration-200 focus:outline-none overflow-hidden">
                                <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'"
                                    class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Allowed IPs --}}
                    <div class="border-t border-[#27213D] pt-4">
                        <button type="button" @click="showIps = !showIps"
                            class="flex items-center gap-2 text-[11px] font-semibold text-[#71717A] hover:text-[#A1A1AA] transition">
                            <svg class="h-3.5 w-3.5 transition-transform duration-150" :class="showIps ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            Allowed Admin IPs <span class="text-[#4B4565] font-normal">(optional)</span>
                        </button>
                        <div x-show="showIps" x-cloak x-transition.opacity.duration.150ms class="mt-3 space-y-2">
                            <textarea
                                name="maintenance_allowed_ips"
                                rows="3"
                                placeholder="127.0.0.1, 192.168.1.10"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 resize-none font-mono text-xs"
                            >{{ old('maintenance_allowed_ips', $maintenanceAllowedIps ?? '') }}</textarea>
                            <p class="text-[11px] text-[#4B4565]">Optional. Comma-separated IPs allowed to access the platform during maintenance. Admin account login always takes priority.</p>
                            @isset($currentAdminIp)
                                <p class="text-[11px] text-[#71717A]">Your current IP: <span class="font-mono text-[#A1A1AA]">{{ $currentAdminIp }}</span></p>
                            @endisset
                        </div>
                    </div>
                </div>

                {{-- ── Limits ── --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">Rate Limiting</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Session Timeout <span class="font-normal text-[#52525B]">(minutes)</span></label>
                            <input type="number" name="session_timeout_minutes" min="5" max="10080"
                                value="{{ old('session_timeout_minutes', $sessionTimeoutMinutes) }}"
                                placeholder="Default (Laravel config)"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60">
                            <p class="mt-1 text-[11px] text-[#71717A]">Leave empty to use Laravel's default.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#A1A1AA] mb-1.5">Max Login Attempts</label>
                            <input type="number" name="max_login_attempts" min="3" max="20"
                                value="{{ old('max_login_attempts', $maxLoginAttempts) }}"
                                placeholder="5"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60">
                            <p class="mt-1 text-[11px] text-[#71717A]">Lockout after this many failed attempts (3–20).</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        {{-- ───────────────────────────────
             NOTIFICATIONS
        ─────────────────────────────── --}}
        <div x-show="tab === 'notifications'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.notifications.save') }}" class="space-y-5">
                @csrf

                @php
                    $mailPasswordSaved = filled($mailPassword ?? '');
                @endphp

                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-[#F8FAFC]">Notification Settings</h2>
                        <p class="text-xs text-[#71717A] mt-0.5">Email sending, admin alerts, and platform event notifications.</p>
                    </div>
                    <button type="submit" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition shrink-0">Save Changes</button>
                </div>

                {{-- ══════════════════════════════════════
                     CARD 1 — Email Sending Settings
                ══════════════════════════════════════ --}}
                <div
                    x-data="{ mailEnabled: {{ ($mailEnabled ?? false) ? 'true' : 'false' }}, driver: '{{ old('mail_mailer', $mailMailer ?? 'smtp') }}', showGuide: false }"
                    class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-5"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-black text-[#F8FAFC]">Email Sending Settings</h3>
                            <p class="text-[11px] text-[#71717A] mt-0.5">SMTP / Gmail configuration for platform-sent emails.</p>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span x-show="mailEnabled" x-cloak class="text-[10px] font-semibold text-[#22C55E]">Enabled</span>
                            <span x-show="!mailEnabled" class="text-[10px] font-semibold text-[#71717A]">Disabled</span>
                            <input type="hidden" name="mail_enabled" :value="mailEnabled ? '1' : '0'">
                            <button type="button" @click="mailEnabled = !mailEnabled" :class="mailEnabled ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'"
                                class="relative h-6 w-11 rounded-full transition-colors duration-200 focus:outline-none overflow-hidden shrink-0">
                                <span :class="mailEnabled ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'"
                                    class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">From Email</label>
                            <input type="email" name="mail_from_address"
                                value="{{ old('mail_from_address', $mailFromAddress ?? '') }}"
                                placeholder="yourname@gmail.com"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60">
                            <p class="mt-1 text-[11px] text-[#4B4565]">Address users see as the sender.</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">From Name</label>
                            <input type="text" name="mail_from_name"
                                value="{{ old('mail_from_name', $mailFromName ?? '') }}"
                                placeholder="BotHost Pro"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">Mail Driver</label>
                            <div class="relative" x-data="{ open: false, get label() { return { 'smtp': 'SMTP', 'log': 'Log only / Testing' }[driver] || 'SMTP' } }" @click.away="open = false">
                                <input type="hidden" name="mail_mailer" :value="driver">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                                    <span x-text="label"></span>
                                    <svg class="ml-2 h-4 w-4 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                    @foreach(['smtp' => 'SMTP', 'log' => 'Log only / Testing'] as $mdv => $mdl)
                                    <button type="button" @click="driver = '{{ $mdv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="driver === '{{ $mdv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                        <svg :class="driver === '{{ $mdv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        {{ $mdl }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">SMTP Host</label>
                            <input type="text" name="mail_host"
                                value="{{ old('mail_host', $mailHost ?? '') }}"
                                placeholder="smtp.gmail.com"
                                :disabled="driver !== 'smtp'"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 disabled:opacity-40">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">SMTP Port</label>
                            <input type="number" name="mail_port"
                                value="{{ old('mail_port', $mailPort ?? '587') }}"
                                placeholder="587"
                                :disabled="driver !== 'smtp'"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 disabled:opacity-40">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">SMTP Username</label>
                            <input type="text" name="mail_username"
                                value="{{ old('mail_username', $mailUsername ?? '') }}"
                                placeholder="yourname@gmail.com"
                                :disabled="driver !== 'smtp'"
                                autocomplete="off" spellcheck="false" autocapitalize="off"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 disabled:opacity-40">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">SMTP Password / App Password</label>
                            <input type="password" name="mail_password"
                                placeholder="{{ $mailPasswordSaved ? '••••••••••••' : 'Enter password or app password' }}"
                                :disabled="driver !== 'smtp'"
                                autocomplete="new-password" spellcheck="false" autocapitalize="off"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 disabled:opacity-40">
                            @if ($mailPasswordSaved)
                                <p class="mt-1 text-[11px] text-[#F59E0B]">Password saved. Leave blank to keep current.</p>
                            @else
                                <p class="mt-1 text-[11px] text-[#4B4565]">For Gmail: use an App Password, not your account password.</p>
                            @endif
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">Encryption</label>
                            <div class="relative" :class="driver !== 'smtp' ? 'opacity-40 pointer-events-none' : ''"
                                x-data="{ open: false, val: '{{ old('mail_encryption', $mailEncryption ?? 'tls') }}', get label() { return { 'tls': 'TLS', 'ssl': 'SSL', '': 'None' }[this.val] ?? 'TLS' } }" @click.away="open = false">
                                <input type="hidden" name="mail_encryption" :value="val">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/15' : 'border-[#27213D]'">
                                    <span x-text="label"></span>
                                    <svg class="ml-2 h-4 w-4 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                                    class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                                    @foreach(['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'] as $encv => $encl)
                                    <button type="button" @click="val = '{{ $encv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $encv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                        <svg :class="val === '{{ $encv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        {{ $encl }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Gmail accordion guide --}}
                    <div x-data="{ open: false }" class="rounded-xl border border-[#27213D] bg-[#0D0C18]">
                        <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between px-4 py-3 text-left">
                            <div class="flex items-center gap-2.5">
                                <svg class="h-4 w-4 text-[#38BDF8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-[11px] font-semibold text-[#A1A1AA]">Gmail setup guide</span>
                            </div>
                            <svg class="h-4 w-4 text-[#71717A] transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="border-t border-[#27213D] px-4 pb-4 pt-3 space-y-1.5">
                            <p class="text-[10px] font-black uppercase tracking-wider text-[#71717A] mb-2">How to use Gmail with SMTP</p>
                            @foreach([
                                ['1', 'Enable 2-Step Verification in your Google Account security settings.'],
                                ['2', 'Go to Google Account → Security → App passwords.'],
                                ['3', 'Create an App Password for "Mail" and copy it.'],
                                ['4', 'Paste the App Password in the field above (not your Gmail password).'],
                                ['5', 'Use smtp.gmail.com, port 587, encryption TLS.'],
                                ['6', 'Save settings and send a test email.'],
                            ] as [$n, $step])
                            <div class="flex items-start gap-2.5">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-[#8B5CF6]/15 text-[9px] font-black text-[#8B5CF6] mt-0.5">{{ $n }}</span>
                                <p class="text-[11px] text-[#71717A]">{{ $step }}</p>
                            </div>
                            @endforeach
                            <div class="mt-2 rounded-lg border border-[#F59E0B]/20 bg-[#F59E0B]/6 px-3 py-2">
                                <p class="text-[11px] text-[#A1A1AA]">Your SMTP password is stored securely and never shown in plain text after saving.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════
                     CARD 2 — Admin Notification Recipient
                ══════════════════════════════════════ --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    <div>
                        <h3 class="text-sm font-black text-[#F8FAFC]">Admin Notification Recipient</h3>
                        <p class="text-[11px] text-[#71717A] mt-0.5">Where platform alerts and admin notifications are delivered.</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#71717A]">Admin Notification Email</label>
                        <input type="email" name="admin_notification_email"
                            value="{{ old('admin_notification_email', $adminNotificationEmail ?? $adminAlertEmail) }}"
                            placeholder="admin@example.com"
                            class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 sm:max-w-sm">
                        <p class="mt-1 text-[11px] text-[#4B4565]">Receives all admin alerts. If empty, platform support email is used as fallback.</p>
                    </div>
                </div>

                {{-- ══════════════════════════════════════
                     CARD 3 — Event Notifications
                ══════════════════════════════════════ --}}
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-3">
                    <div class="mb-1">
                        <h3 class="text-sm font-black text-[#F8FAFC]">Event Notifications</h3>
                        <p class="text-[11px] text-[#71717A] mt-0.5">Choose which platform events trigger an admin email alert.</p>
                    </div>

                    @php
                        $notifToggles = [
                            ['key' => 'notify_new_user_signup',  'val' => $notifyNewUserSignup  ?? false, 'label' => 'New User Signups',     'desc' => 'Notify admin when a new user registers on the platform.'],
                            ['key' => 'notify_payment_events',   'val' => $notifyPaymentEvents,           'label' => 'Payment Events',        'desc' => 'Notify admin on completed or failed payments.'],
                            ['key' => 'notify_template_events',  'val' => $notifyTemplateEvents,          'label' => 'Template Purchases',    'desc' => 'Notify admin when templates are purchased or unlocked.'],
                            ['key' => 'notify_plan_events',      'val' => $notifyPlanEvents,              'label' => 'Plan Upgrades',         'desc' => 'Notify admin on subscription plan changes.'],
                            ['key' => 'notify_bot_errors',       'val' => $notifyBotErrors,               'label' => 'Bot Runtime Errors',    'desc' => 'Notify admin on bot command runtime errors (may be high volume).'],
                            ['key' => 'notify_storage_warnings', 'val' => $notifyStorageWarnings,         'label' => 'Storage Warnings',      'desc' => 'Notify admin when users reach storage warning/critical thresholds.'],
                        ];
                    @endphp

                    @foreach($notifToggles as $tog)
                    <div x-data="{ val: {{ $tog['val'] ? 'true' : 'false' }} }"
                        class="flex items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-[#F8FAFC]">{{ $tog['label'] }}</p>
                            <p class="text-[11px] text-[#71717A] mt-0.5">{{ $tog['desc'] }}</p>
                        </div>
                        <div class="shrink-0">
                            <input type="hidden" name="{{ $tog['key'] }}" :value="val ? '1' : '0'">
                            <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'"
                                class="relative h-6 w-11 rounded-full transition-colors duration-200 focus:outline-none overflow-hidden">
                                <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'"
                                    class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- ══════════════════════════════════════
                     CARD 4 — Test Email
                ══════════════════════════════════════ --}}
                <div x-data="{ emailErr: '' }" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-4">
                    <div>
                        <h3 class="text-sm font-black text-[#F8FAFC]">Test Email</h3>
                        <p class="text-[11px] text-[#71717A] mt-0.5">Verify your email configuration is working correctly.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 items-start">
                        <div class="flex-1 sm:max-w-xs">
                            <input type="email" name="mail_test_recipient"
                                value="{{ old('mail_test_recipient', $mailTestRecipient ?? '') }}"
                                placeholder="admin@example.com"
                                @invalid.prevent="emailErr = $el.validationMessage"
                                @input="emailErr = ''"
                                :style="emailErr ? 'border-color:rgba(239,68,68,.5);box-shadow:0 0 0 2px rgba(239,68,68,.1)' : ''"
                                class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-[#F8FAFC] placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60">
                            <p
                                x-show="emailErr"
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="mt-1.5 flex items-center gap-1.5 text-[11px] font-semibold text-[#F87171]"
                            >
                                <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                </svg>
                                <span x-text="emailErr"></span>
                            </p>
                        </div>
                        <button type="submit" formaction="{{ route('admin.settings.test-email') }}" formmethod="POST"
                            class="flex items-center justify-center gap-2 rounded-xl border border-[#38BDF8]/30 bg-[#38BDF8]/10 px-4 py-2.5 text-sm font-semibold text-[#38BDF8] transition hover:bg-[#38BDF8]/15">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Send Test Email
                        </button>
                    </div>
                    <p class="text-[11px] text-[#52525B]">Save your settings first, then use this to verify delivery.</p>
                </div>
            </form>
        </div>


        {{-- ───────────────────────────────
             AUTOMATIONS
        ─────────────────────────────── --}}
        <div x-show="tab === 'automations'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.automations.save') }}" class="space-y-4">
                @csrf

                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-[#F8FAFC]">Automation Settings</h2>
                        <p class="text-xs text-[#71717A] mt-0.5">Background tasks and scheduled automations.</p>
                    </div>
                    <button type="submit" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white hover:bg-[#7C3AED] transition shrink-0">Save Changes</button>
                </div>

                <div class="rounded-2xl border border-[#F59E0B]/20 bg-[#F59E0B]/5 px-4 py-3 text-[11px] text-[#F59E0B]">
                    <strong class="font-black">Scheduler required.</strong> These automations run only when Laravel's scheduler is active.
                    <code class="mt-1.5 block overflow-x-auto font-mono text-[#F8FAFC]">* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1</code>
                </div>

                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A] mb-4">Scheduled Automations</h3>

                    @php
                        $autoToggles = [
                            ['key' => 'automation_process_broadcasts_enabled',       'val' => $automationProcessBroadcasts, 'label' => 'Broadcast Queue',              'desc' => 'Send queued, scheduled, and running bot broadcasts every minute.'],
                            ['key' => 'automation_reconnect_webhooks_enabled',       'val' => $automationReconnectWebhooks, 'label' => 'Telegram Reconnect',          'desc' => 'Check running bots and reconnect Telegram webhooks when the public URL changes or drops.'],
                            ['key' => 'automation_prune_logs_enabled',               'val' => $automationPruneLogs,         'label' => 'Prune Bot Logs',              'desc' => 'Delete bot logs older than each user\'s plan retention period.'],
                        ];
                    @endphp

                    @foreach($autoToggles as $tog)
                    <div x-data="{ val: {{ $tog['val'] ? 'true' : 'false' }} }" class="flex items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3">
                        <div class="flex-1 min-w-0 mr-4">
                            <p class="text-sm font-semibold text-[#F8FAFC]">{{ $tog['label'] }}</p>
                            <p class="text-[11px] text-[#71717A] mt-0.5">{{ $tog['desc'] }}</p>
                        </div>
                        <input type="hidden" name="{{ $tog['key'] }}" :value="val ? '1' : '0'">
                        <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'"
                            class="relative h-6 w-11 rounded-full transition-colors duration-200 shrink-0 focus:outline-none overflow-hidden">
                            <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'" class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                        </button>
                    </div>
                    @endforeach
                </div>
            </form>
        </div>


        {{-- ───────────────────────────────
             MAINTENANCE
        ─────────────────────────────── --}}
        <div x-show="tab === 'maintenance'" x-cloak class="space-y-3">

            {{-- ── Header ── --}}
            <div>
                <h2 class="text-base font-black text-[#F8FAFC]">Maintenance Tools</h2>
                <p class="text-xs text-[#71717A] mt-0.5">Cache management, asset links, and system diagnostics.</p>
            </div>

            {{-- ── Platform Maintenance Mode ── --}}
            <div class="rounded-2xl border {{ $platformMode === 'maintenance' ? 'border-[#F59E0B]/30 bg-[#F59E0B]/4' : 'border-[#27213D] bg-[#0F0D1A]' }} p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $platformMode === 'maintenance' ? 'border border-[#F59E0B]/30 bg-[#F59E0B]/10 text-[#F59E0B]' : 'border border-[#27213D] bg-[#151225] text-[#71717A]' }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.664-9.499 3.86 3.86M6.26 6.26 3.102 3.102m0 0L2.25 2.25m.852.852c-.208.224-.408.454-.595.694M10.05 10.05l3.9-3.9m.748 9.9 3.182-3.182M6.26 6.26l-.694.595"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-black text-[#F8FAFC]">Platform Maintenance Mode</p>
                            <p class="mt-0.5 text-xs text-[#71717A]">Temporarily disable user access while admins continue managing the platform.</p>
                            <div class="mt-1.5 flex items-center gap-1.5">
                                @if($platformMode === 'maintenance')
                                    <span class="h-2 w-2 animate-pulse rounded-full bg-[#F59E0B]"></span>
                                    <span class="text-[11px] font-black text-[#F59E0B]">Active — users see maintenance page</span>
                                @else
                                    <span class="h-2 w-2 rounded-full bg-[#22C55E]"></span>
                                    <span class="text-[11px] font-black text-[#22C55E]">Platform is Live</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div x-data="{}" class="shrink-0">
                        @if($platformMode === 'maintenance')
                            <form x-ref="maintTabDisableForm" method="POST" action="{{ route('admin.settings.maintenance-mode') }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="platform_mode" value="live">
                                <button
                                    type="submit"
                                    data-confirm
                                    data-confirm-type="default"
                                    data-confirm-title="Disable maintenance mode?"
                                    data-confirm-message="Users will regain access to the platform immediately."
                                    data-confirm-btn="Restore Live Mode"
                                    class="rounded-xl bg-[#22C55E] px-3 py-2 text-xs font-black text-white transition hover:bg-green-400"
                                >Restore Live</button>
                            </form>
                        @else
                            <form x-ref="maintTabEnableForm" method="POST" action="{{ route('admin.settings.maintenance-mode') }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="platform_mode" value="maintenance">
                                <button
                                    type="submit"
                                    data-confirm
                                    data-confirm-type="warning"
                                    data-confirm-title="Enable maintenance mode?"
                                    data-confirm-message="Normal users will be temporarily blocked from accessing the platform. Admin access and callback routes remain available."
                                    data-confirm-btn="Enable Maintenance"
                                    class="rounded-xl border border-[#F59E0B]/40 bg-[#F59E0B]/10 px-3 py-2 text-xs font-black text-[#F59E0B] transition hover:bg-[#F59E0B]/18"
                                >Enable Maint.</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Runtime / Performance --}}
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                {{-- Section header + quick-action buttons --}}
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A]">Runtime Performance</h3>
                        <p class="mt-0.5 text-[11px] text-[#52525B]">Redis, queue, JavaScript runtime, and command logging controls.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5 sm:flex sm:flex-wrap sm:gap-1.5">
                        @foreach([
                            ['route' => 'admin.settings.test-redis', 'label' => 'Test Redis'],
                            ['route' => 'admin.settings.test-docker', 'label' => 'Test Docker'],
                            ['route' => 'admin.settings.check-runtime-image', 'label' => 'Check Image'],
                            ['route' => 'admin.settings.build-runtime-image', 'label' => 'Build Image'],
                        ] as $action)
                            <form method="POST" action="{{ route($action['route']) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-lg border border-[#27213D] bg-[#11101C] px-3 py-1.5 text-[11px] font-black text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-white sm:w-auto">{{ $action['label'] }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>

                @if($errors->has('redis') || $errors->has('runtime'))
                    <div class="mb-4 rounded-xl border border-[#EF4444]/25 bg-[#EF4444]/8 px-4 py-3 text-xs font-semibold text-[#FCA5A5]">
                        {{ $errors->first('redis') ?: $errors->first('runtime') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.runtime-performance.save') }}" class="space-y-5">
                    @csrf
                    @php
                        $runtimeStatusLabel = match($runtimeStatusPersisted ?? 'unknown') {
                            'online'  => 'Online',
                            'offline' => 'Offline',
                            default   => 'Unknown',
                        };
                        $runtimeStatusClass = match($runtimeStatusPersisted ?? 'unknown') {
                            'online'  => 'text-[#22C55E]',
                            'offline' => 'text-[#EF4444]',
                            default   => 'text-[#F59E0B]',
                        };
                        $lastRuntimeError = filled($runtimeStatusLastError ?? '')
                            ? \Illuminate\Support\Str::limit((string) $runtimeStatusLastError, 180)
                            : 'None reported';
                        $lastCheckedAt = filled($runtimeStatusCheckedAt ?? '')
                            ? \Illuminate\Support\Carbon::parse($runtimeStatusCheckedAt)->diffForHumans()
                            : null;
                        $runtimeBaseHost = strtolower((string) parse_url((string) ($runtimeSettings['runtime_base_url'] ?? ''), PHP_URL_HOST));
                        $runtimeHealthHost = strtolower((string) parse_url((string) ($runtimeSettings['runtime_health_url'] ?? ''), PHP_URL_HOST));
                        $_appHost    = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
                        $_publicHost = strtolower((string) parse_url((string) (\App\Support\PublicCallbackUrl::base() ?? ''), PHP_URL_HOST));
                        $runtimeLooksPublic = str_contains($runtimeBaseHost, 'trycloudflare.com')
                            || str_contains($runtimeBaseHost, 'ngrok')
                            || ($runtimeBaseHost !== '' && in_array($runtimeBaseHost, array_filter([$_appHost, $_publicHost]), true));
                        $runtimeHealthLooksPublic = $runtimeHealthHost !== '' && in_array($runtimeHealthHost, array_filter([$_appHost, $_publicHost]), true);
                    @endphp
                    <div class="rounded-xl border border-[#27213D] bg-[#11101C] p-4">
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-widest text-[#71717A]">Runtime Connection</p>
                                <p class="mt-1 text-xs text-[#71717A]">How Laravel reaches the local Node.js runtime.</p>
                            </div>
                            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                                <button type="submit" formmethod="POST" formaction="{{ route('admin.settings.test-runtime') }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-4 py-2 text-xs font-black text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-white sm:w-auto">Test Local Runtime</button>
                                <button type="submit" formmethod="POST" formaction="{{ route('admin.settings.runtime-health-check') }}" class="w-full rounded-xl border border-[#8B5CF6]/40 bg-[#8B5CF6]/10 px-4 py-2 text-xs font-black text-[#C4B5FD] transition hover:bg-[#8B5CF6]/18 sm:w-auto">Check Runtime Health</button>
                                <button type="submit" formmethod="POST" formaction="{{ route('admin.settings.runtime-urls.reset') }}" class="w-full rounded-xl border border-[#F59E0B]/30 bg-[#F59E0B]/8 px-4 py-2 text-xs font-black text-[#FCD34D] transition hover:bg-[#F59E0B]/14 sm:w-auto">Reset URLs</button>
                            </div>
                        </div>

                        <div class="mb-3 grid grid-cols-2 gap-2 text-[11px] lg:grid-cols-4">
                            <div class="rounded-lg border border-[#27213D] bg-[#0F0D1A] px-3 py-2">
                                <p class="font-black uppercase tracking-wide text-[#71717A]">Runtime Status</p>
                                <p class="mt-1 font-black {{ $runtimeStatusClass }}">{{ $runtimeStatusLabel }}</p>
                                @if($lastCheckedAt)<p class="mt-0.5 text-[10px] text-[#52525B]">Checked {{ $lastCheckedAt }}</p>@endif
                            </div>
                            <div class="rounded-lg border border-[#27213D] bg-[#0F0D1A] px-3 py-2">
                                <p class="font-black uppercase tracking-wide text-[#71717A]">Runtime Mode</p>
                                <p class="mt-1 font-black text-[#A1A1AA]">{{ ucfirst($runtimeSettings['runtime_mode'] ?? 'local') }}</p>
                            </div>
                            <div class="rounded-lg border border-[#27213D] bg-[#0F0D1A] px-3 py-2">
                                <p class="font-black uppercase tracking-wide text-[#71717A]">Docker</p>
                                <p class="mt-1 font-black {{ ($runtimeSettings['runtime_docker_enabled'] ?? false) ? 'text-[#22C55E]' : 'text-[#A1A1AA]' }}">{{ ($runtimeSettings['runtime_docker_enabled'] ?? false) ? 'Enabled' : 'Disabled' }}</p>
                            </div>
                            <div class="rounded-lg border border-[#27213D] bg-[#0F0D1A] px-3 py-2">
                                <p class="font-black uppercase tracking-wide text-[#71717A]">Last Error</p>
                                <p class="mt-1 break-words font-semibold {{ $lastRuntimeError === 'None reported' ? 'text-[#A1A1AA]' : 'text-[#FCA5A5]' }}">{{ $lastRuntimeError }}</p>
                            </div>
                        </div>

                        <div class="mb-4 rounded-xl border border-[#8B5CF6]/25 bg-[#8B5CF6]/8 px-4 py-3 text-xs font-semibold leading-relaxed text-[#C4B5FD]">
                            If Local runtime is selected, Laravel must be able to reach the Node.js runtime URL. If the runtime is unavailable, commands may timeout unless fallback runner is enabled.
                            <span class="mt-1 block text-[#A1A1AA]">Redis and Docker are optional for basic commands. Local runtime/fallback should still allow simple commands to work.</span>
                        </div>
                        @if($runtimeLooksPublic)
                            <div class="mb-4 rounded-xl border border-[#F59E0B]/25 bg-[#F59E0B]/8 px-4 py-3 text-xs font-semibold leading-relaxed text-[#FCD34D]">
                                Runtime Base URL points to your public app domain, not the Node.js runtime server.
                                <span class="mt-1 block text-[#A1A1AA]">Set Runtime Base URL to the internal address, e.g. <code class="font-mono">http://127.0.0.1:8787</code>. Click <strong>Reset URLs</strong> to restore defaults.</span>
                            </div>
                        @endif
                        @if($runtimeHealthLooksPublic)
                            <div class="mb-4 rounded-xl border border-[#EF4444]/25 bg-[#EF4444]/8 px-4 py-3 text-xs font-semibold leading-relaxed text-[#FCA5A5]">
                                Runtime Health URL points to your public app domain — health checks will never reach the Node.js runtime.
                                <span class="mt-1 block text-[#A1A1AA]">Set Runtime Health URL to <code class="font-mono">http://127.0.0.1:8787/health</code>, or click <strong>Reset URLs</strong> to restore defaults.</span>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <label>
                                <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Runtime Host</span>
                                <input name="runtime_host" value="{{ old('runtime_host', $runtimeSettings['runtime_host'] ?? '127.0.0.1') }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                <p class="mt-1 text-[11px] text-[#71717A]">Local Node runtime host. Use 127.0.0.1 for local server.</p>
                            </label>
                            <label>
                                <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Runtime Port</span>
                                <input name="runtime_port" type="number" min="1" max="65535" value="{{ old('runtime_port', $runtimeSettings['runtime_port'] ?? 8787) }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                <p class="mt-1 text-[11px] text-[#71717A]">Port where the local Node.js runtime server listens.</p>
                            </label>
                            <label>
                                <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Runtime HTTP Port Start</span>
                                <input name="runtime_http_port_start" type="number" min="1024" max="65500" value="{{ old('runtime_http_port_start', $runtimeSettings['runtime_http_port_start'] ?? 8800) }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                <p class="mt-1 text-[11px] text-[#71717A]">Starting port for Docker/per-bot runtime containers.</p>
                            </label>
                            <label>
                                <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Runtime Base URL</span>
                                <input name="runtime_base_url" type="url" value="{{ old('runtime_base_url', $runtimeSettings['runtime_base_url'] ?? 'http://127.0.0.1:8787') }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 font-mono text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                <p class="mt-1 text-[11px] text-[#71717A]">Full URL Laravel uses to call the Node.js runtime.</p>
                            </label>
                            <label>
                                <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Runtime Health URL</span>
                                <input name="runtime_health_url" type="url" value="{{ old('runtime_health_url', $runtimeSettings['runtime_health_url'] ?? 'http://127.0.0.1:8787/health') }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 font-mono text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                <p class="mt-1 text-[11px] text-[#71717A]">Used to check if runtime is online.</p>
                            </label>
                            <label>
                                <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Runtime Execute URL</span>
                                <input name="runtime_execute_url" type="url" value="{{ old('runtime_execute_url', $runtimeSettings['runtime_execute_url'] ?? 'http://127.0.0.1:8787/execute') }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 font-mono text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                <p class="mt-1 text-[11px] text-[#71717A]">Used to send command code to runtime.</p>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-xl border border-[#27213D] bg-[#11101C] p-4">
                            <p class="mb-3 text-xs font-black uppercase tracking-widest text-[#71717A]">Redis / Cache</p>
                            <div class="space-y-3">
                                <div x-data="{ val: {{ old('redis_enabled', $runtimeSettings['redis_enabled'] ?? false) ? 'true' : 'false' }} }" class="flex items-center justify-between gap-4 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2.5">
                                    <div>
                                        <p class="text-xs font-black text-[#F8FAFC]">Redis Enabled</p>
                                        <p class="text-[11px] text-[#71717A]">Allow cache and queue settings to use Redis.</p>
                                    </div>
                                    <input type="hidden" name="redis_enabled" :value="val ? '1' : '0'">
                                    <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'" class="relative h-6 w-11 rounded-full transition-colors duration-200 shrink-0 focus:outline-none overflow-hidden">
                                        <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'" class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <label class="sm:col-span-2">
                                        <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Redis Host</span>
                                        <input name="redis_host" value="{{ old('redis_host', $runtimeSettings['redis_host'] ?? '127.0.0.1') }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                    </label>
                                    <label>
                                        <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Port</span>
                                        <input name="redis_port" type="number" min="1" max="65535" value="{{ old('redis_port', $runtimeSettings['redis_port'] ?? 6379) }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <label class="sm:col-span-2">
                                        <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Redis Password</span>
                                        <input name="redis_password" type="password" placeholder="{{ filled($redisPasswordMasked ?? null) ? '&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;' : 'Optional' }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                        <p class="mt-1 text-[11px] text-[#71717A]">Leave blank to keep current password.</p>
                                    </label>
                                    <label>
                                        <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Database</span>
                                        <input name="redis_db" type="number" min="0" max="255" value="{{ old('redis_db', $runtimeSettings['redis_db'] ?? 0) }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label>
                                        <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Cache Store</span>
                                        <select name="cache_store" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                            @foreach(['file' => 'File', 'database' => 'Database', 'redis' => 'Redis'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('cache_store', $runtimeSettings['cache_store'] ?? 'database') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Queue Connection</span>
                                        <select name="queue_connection" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                            @foreach(['sync' => 'Sync', 'database' => 'Database', 'redis' => 'Redis'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('queue_connection', $runtimeSettings['queue_connection'] ?? 'database') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-[#27213D] bg-[#11101C] p-4">
                            <p class="mb-3 text-xs font-black uppercase tracking-widest text-[#71717A]">Command Runtime</p>
                            @if(! ($runtimeDockerStatus['docker_available'] ?? false))
                                <div class="mb-4 rounded-xl border border-[#F59E0B]/25 bg-[#F59E0B]/8 px-4 py-3 text-xs font-semibold text-[#FCD34D]">
                                    Docker is not available on this machine. Local runtime mode will be used.
                                </div>
                            @endif
                            @if(filled($runtimeDockerStatus['last_error'] ?? null))
                                <div class="mb-4 rounded-xl border border-[#EF4444]/25 bg-[#EF4444]/8 px-4 py-3 text-xs font-semibold text-[#FCA5A5]">
                                    Last Docker error: {{ \Illuminate\Support\Str::limit($runtimeDockerStatus['last_error'], 180) }}
                                </div>
                            @endif
                            <div class="mb-3 grid grid-cols-2 gap-2 text-[11px] sm:grid-cols-4">
                                @foreach([
                                    ['label' => 'Docker', 'value' => ($runtimeDockerStatus['docker_available'] ?? false) ? 'Available' : 'Unavailable', 'ok' => ($runtimeDockerStatus['docker_available'] ?? false)],
                                    ['label' => 'Image', 'value' => ($runtimeDockerStatus['image_exists'] ?? false) ? 'Ready' : 'Missing', 'ok' => ($runtimeDockerStatus['image_exists'] ?? false)],
                                    ['label' => 'Containers', 'value' => (string) ($runtimeDockerStatus['active_containers'] ?? 0), 'ok' => true],
                                    ['label' => 'Unhealthy', 'value' => (string) ($runtimeDockerStatus['unhealthy_containers'] ?? 0), 'ok' => (int) ($runtimeDockerStatus['unhealthy_containers'] ?? 0) === 0],
                                    ['label' => 'Mode', 'value' => ucfirst($runtimeSettings['runtime_mode'] ?? 'local'), 'ok' => true],
                                    ['label' => 'Timeout', 'value' => ($runtimeSettings['command_timeout_ms'] ?? 5000).'ms', 'ok' => true],
                                    ['label' => 'Memory', 'value' => (string) ($runtimeSettings['runtime_memory_limit'] ?? '128m'), 'ok' => true],
                                    ['label' => 'CPU', 'value' => (string) ($runtimeSettings['runtime_cpu_limit'] ?? '0.25'), 'ok' => true],
                                ] as $item)
                                    <div class="rounded-lg border border-[#27213D] bg-[#0F0D1A] px-3 py-2">
                                        <p class="font-black uppercase tracking-wide text-[#71717A]">{{ $item['label'] }}</p>
                                        <p class="mt-1 font-black {{ $item['ok'] ? 'text-[#22C55E]' : 'text-[#F59E0B]' }}">{{ $item['value'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <label>
                                    <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Command Timeout (ms)</span>
                                    <input name="command_timeout_ms" type="number" min="1000" max="30000" value="{{ old('command_timeout_ms', $runtimeSettings['command_timeout_ms'] ?? 15000) }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                </label>
                                <label>
                                    <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Max Delay (ms)</span>
                                    <input name="max_delay_ms" type="number" min="0" max="30000" value="{{ old('max_delay_ms', $runtimeSettings['max_delay_ms'] ?? 10000) }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                </label>
                                <label>
                                    <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Slow Threshold (ms)</span>
                                    <input name="slow_command_threshold_ms" type="number" min="100" max="30000" value="{{ old('slow_command_threshold_ms', $runtimeSettings['slow_command_threshold_ms'] ?? 1000) }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                </label>
                                <label>
                                    <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Runtime Mode</span>
                                    <select name="runtime_mode" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                        @foreach(['local' => 'Local', 'docker' => 'Docker (Phase 2)'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('runtime_mode', $runtimeSettings['runtime_mode'] ?? 'local') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <div x-data="{ val: {{ old('runtime_docker_enabled', $runtimeSettings['runtime_docker_enabled'] ?? false) ? 'true' : 'false' }} }" class="sm:col-span-2 lg:col-span-1 flex items-center justify-between gap-3 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2.5">
                                    <div>
                                        <p class="text-xs font-black text-[#F8FAFC]">Docker Enabled</p>
                                        <p class="text-[11px] text-[#71717A]">Allow isolated Docker runtime containers.</p>
                                    </div>
                                    <input type="hidden" name="runtime_docker_enabled" :value="val ? '1' : '0'">
                                    <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'" class="relative h-6 w-11 rounded-full transition-colors duration-200 shrink-0 focus:outline-none overflow-hidden">
                                        <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'" class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                                    </button>
                                </div>
                                <label>
                                    <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Docker Image</span>
                                    <input name="runtime_docker_image" value="{{ old('runtime_docker_image', $runtimeSettings['runtime_docker_image'] ?? 'bothost-node-runtime') }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                </label>
                                <label>
                                    <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Container Prefix</span>
                                    <input name="runtime_container_prefix" value="{{ old('runtime_container_prefix', $runtimeSettings['runtime_container_prefix'] ?? 'bothost-bot') }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                </label>
                                <label>
                                    <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">Memory Limit</span>
                                    <input name="runtime_memory_limit" value="{{ old('runtime_memory_limit', $runtimeSettings['runtime_memory_limit'] ?? '128m') }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                </label>
                                <label>
                                    <span class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#71717A]">CPU Limit</span>
                                    <input name="runtime_cpu_limit" type="number" step="0.05" min="0.05" max="4" value="{{ old('runtime_cpu_limit', $runtimeSettings['runtime_cpu_limit'] ?? '0.25') }}" class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-sm text-[#F8FAFC] outline-none focus:border-[#8B5CF6]">
                                </label>
                            </div>

                        </div>
                    </div>

                    <div class="rounded-xl border border-[#27213D] bg-[#11101C] p-4">
                        <p class="mb-3 text-xs font-black uppercase tracking-widest text-[#71717A]">Runtime Behavior</p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach([
                                ['key' => 'runtime_warm_enabled', 'label' => 'Warm Runtime', 'desc' => 'Prefer the already running local Node bridge.', 'default' => true],
                                ['key' => 'queue_simple_commands', 'label' => 'Queue Simple Commands', 'desc' => 'Keep off for fastest replies.', 'default' => false],
                                ['key' => 'log_slow_commands', 'label' => 'Log Slow Commands', 'desc' => 'Only logs commands above the threshold.', 'default' => false],
                                ['key' => 'runtime_auto_restart', 'label' => 'Auto Restart Docker', 'desc' => 'Restart missing or unhealthy bot containers.', 'default' => true],
                                ['key' => 'runtime_keep_paused_warm', 'label' => 'Keep Paused Warm', 'desc' => 'Keep paused Docker containers running.', 'default' => false],
                                ['key' => 'show_user_code_errors_to_owners', 'label' => 'Show User Code Logs', 'desc' => 'Expose command debug logs to bot owners.', 'default' => false],
                                ['key' => 'log_user_code_errors', 'label' => 'Log User Code Errors', 'desc' => 'Store JavaScript command failures.', 'default' => false],
                                ['key' => 'log_backend_runtime_errors', 'label' => 'Log Backend Runtime Errors', 'desc' => 'Store serious runtime failures.', 'default' => true],
                                ['key' => 'log_webhook_errors', 'label' => 'Log Webhook Errors', 'desc' => 'Store webhook route failures.', 'default' => true],
                                ['key' => 'log_telegram_api_errors', 'label' => 'Log Telegram API Errors', 'desc' => 'Store Telegram send failures.', 'default' => true],
                                ['key' => 'log_redis_errors', 'label' => 'Log Redis Errors', 'desc' => 'Store Redis/cache failures.', 'default' => true],
                                ['key' => 'log_docker_errors', 'label' => 'Log Docker Errors', 'desc' => 'Store Docker runtime backend failures.', 'default' => true],
                            ] as $toggle)
                                <div x-data="{ val: {{ old($toggle['key'], $runtimeSettings[$toggle['key']] ?? $toggle['default']) ? 'true' : 'false' }} }" class="flex items-center justify-between gap-3 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2.5">
                                    <div>
                                        <p class="text-xs font-black text-[#F8FAFC]">{{ $toggle['label'] }}</p>
                                        <p class="text-[11px] text-[#71717A]">{{ $toggle['desc'] }}</p>
                                    </div>
                                    <input type="hidden" name="{{ $toggle['key'] }}" :value="val ? '1' : '0'">
                                    <button type="button" @click="val = !val" :class="val ? 'bg-[#8B5CF6]' : 'bg-[#3A3553]'" class="relative h-6 w-11 rounded-full transition-colors duration-200 shrink-0 focus:outline-none overflow-hidden">
                                        <span :class="val ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-[#C4C4D4]'" class="absolute left-0 top-0.5 h-5 w-5 rounded-full shadow transition-all duration-200"></span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-xl bg-[#8B5CF6] px-4 py-2 text-xs font-black text-white transition hover:bg-[#7C3AED]">Save Runtime Settings</button>
                    </div>
                </form>
            </div>

            {{-- Cache Actions --}}
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A] mb-2.5">Cache Management</h3>
                <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-4 gap-2">
                    @foreach([
                        ['action' => 'clear-cache',  'label' => 'Clear Cache',       'desc' => 'Application + config cache', 'route' => 'admin.settings.maintenance.clear-cache',  'color' => 'blue'],
                        ['action' => 'clear-views',  'label' => 'Clear View Cache',  'desc' => 'Compiled Blade templates',   'route' => 'admin.settings.maintenance.clear-views',  'color' => 'blue'],
                        ['action' => 'clear-routes', 'label' => 'Clear Route Cache', 'desc' => 'Route lookup cache',         'route' => 'admin.settings.maintenance.clear-routes', 'color' => 'blue'],
                        ['action' => 'storage-link', 'label' => 'Storage Link',      'desc' => 'Create public symlink',      'route' => 'admin.settings.maintenance.storage-link', 'color' => 'purple'],
                    ] as $tool)
                    <form method="POST" action="{{ route($tool['route']) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-[#27213D] bg-[#11101C] p-3 text-left transition hover:border-[#8B5CF6]/40 hover:bg-[#151225] group">
                            <div class="flex items-center gap-1.5 mb-1">
                                <svg class="h-3.5 w-3.5 shrink-0 text-[#4D4868] group-hover:text-[#8B5CF6] transition" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
                                <span class="text-xs font-black text-[#F8FAFC]">{{ $tool['label'] }}</span>
                            </div>
                            <p class="text-[11px] text-[#52525B]">{{ $tool['desc'] }}</p>
                        </button>
                    </form>
                    @endforeach
                </div>
            </div>

            {{-- System Status + Environment Info (inline row on larger screens) --}}
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">

                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A] mb-2.5">System Status</h3>
                    @php $storageLinkExists = is_link(public_path('storage')); @endphp
                    <div class="space-y-2">
                        <div class="rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2.5 flex items-start gap-2">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $storageLinkExists ? 'bg-[#22C55E]' : 'bg-[#EF4444]' }}"></span>
                            <div class="min-w-0">
                                <p class="text-xs font-black text-[#A1A1AA]">Storage Symlink</p>
                                <p class="text-[11px] text-[#52525B]">{{ $storageLinkExists ? 'public/storage → storage/app/public' : 'Not linked — use Storage Link above.' }}</p>
                            </div>
                        </div>
                        <div class="rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2.5 flex items-start gap-2">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#F59E0B]"></span>
                            <div class="min-w-0">
                                <p class="text-xs font-black text-[#A1A1AA]">Queue Status</p>
                                <p class="text-[11px] text-[#52525B]">Monitor via queue dashboard or supervisor logs.</p>
                            </div>
                        </div>
                        <div class="rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2.5 flex items-start gap-2">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#F59E0B]"></span>
                            <div class="min-w-0">
                                <p class="text-xs font-black text-[#A1A1AA]">Scheduler</p>
                                <p class="text-[11px] text-[#52525B]">Verify cron entry runs <code class="font-mono">schedule:run</code> every minute.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#71717A] mb-2.5">Environment Info</h3>
                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                        @foreach([
                            ['label' => 'Laravel',     'value' => app()->version()],
                            ['label' => 'PHP',         'value' => PHP_VERSION],
                            ['label' => 'Environment', 'value' => app()->environment()],
                            ['label' => 'Debug Mode',  'value' => config('app.debug') ? 'ON' : 'off'],
                        ] as $info)
                        <div class="rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2.5">
                            <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A]">{{ $info['label'] }}</p>
                            <p class="font-mono text-[#A1A1AA] mt-0.5">{{ $info['value'] }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>


        {{-- ───────────────────────────────
             DANGER ZONE
        ─────────────────────────────── --}}
        <div x-show="tab === 'danger'" x-cloak x-data="{}" class="space-y-4">

            <div>
                <h2 class="text-base font-black text-[#F8FAFC]">Danger Zone</h2>
                <p class="text-xs text-[#71717A] mt-0.5">These actions affect all users and platform state. Proceed with extreme caution.</p>
            </div>

            <div class="rounded-xl border border-[#EF4444]/20 bg-[#EF4444]/5 px-4 py-3 flex items-start gap-3">
                <svg class="h-4 w-4 text-[#EF4444] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                <p class="text-xs text-[#EF4444]/80 font-semibold">All actions below are immediate. Some cannot be undone without manual intervention.</p>
            </div>

            {{-- No irreversible platform actions are currently exposed here. --}}
            {{-- Maintenance Mode → Maintenance tab | Registrations → Security tab | Webhooks → Webhooks tab --}}
            <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-6 text-center">
                <svg class="mx-auto h-8 w-8 text-[#4D4868] mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                <p class="text-sm font-black text-[#A1A1AA]">No irreversible platform actions configured</p>
                <p class="mt-1 text-xs text-[#71717A]">Platform controls (maintenance, registrations, webhooks) are managed in their respective tabs.</p>
            </div>

        </div>

    </div>
    {{-- end right content --}}

</div>

</x-admin-layout>
