<x-dashboard-layout title="Account Settings" :no-flash="true">

@php
    $initialTab = 'profile';
    if (session('status') === 'password-updated' || $errors->updatePassword->isNotEmpty()) {
        $initialTab = 'security';
    } elseif ($errors->userDeletion->isNotEmpty()) {
        $initialTab = 'danger';
    }

    $planSlug = strtolower((string) ($user->subscription_plan ?? 'free'));
    $planName = $subscriptionPlan?->name ?? ucfirst($planSlug);

    $planColor = match ($planSlug) {
        'pro'      => '#38BDF8',
        'business' => '#F59E0B',
        default    => '#71717A',
    };

    $initials = strtoupper(substr($user->name ?? $user->username ?? 'U', 0, 1));
    if ($user->name && str_contains(trim($user->name), ' ')) {
        $parts    = explode(' ', trim($user->name));
        $initials = strtoupper(substr($parts[0], 0, 1).substr(end($parts), 0, 1));
    }

    $usedMb           = $storageUsedMb ?? 0;
    $limitMb          = $storageLimitMb ?? 'unlimited';
    $isUnlimitedStore = $limitMb === 'unlimited';
    $storagePct       = $isUnlimitedStore ? 0 : ($limitMb > 0 ? min(100, round(($usedMb / $limitMb) * 100, 1)) : 0);
    $storeBarColor    = $storagePct >= 90 ? '#EF4444' : ($storagePct >= 70 ? '#F59E0B' : '#8B5CF6');
    $storeUsedDisplay  = $usedMb >= 1024 ? round($usedMb / 1024, 2).' GB' : $usedMb.' MB';
    $storeLimitDisplay = $isUnlimitedStore
        ? 'Unlimited'
        : ($limitMb >= 1024 ? round($limitMb / 1024, 2).' GB' : $limitMb.' MB');

    $fmtLimit = function (string $key, array $data): string {
        if ($data['is_unlimited'] || $data['value'] === null) {
            return 'Unlimited';
        }
        $v = $data['value'];
        return match ($key) {
            'storage_mb'                    => ((int) $v >= 1024 ? round((int) $v / 1024, 1).' GB' : $v.' MB'),
            'broadcast_recipients_per_send' => number_format((int) $v),
            'logs_retention_days'           => $v.' days',
            default                         => (string) $v,
        };
    };

    $featureLabels = [
        'bot_creation'        => ['label' => 'Bot Creation',          'icon' => 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18'],
        'command_editor'      => ['label' => 'Command Editor',        'icon' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'node_runtime'        => ['label' => 'Node.js Runtime',       'icon' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z'],
        'template_marketplace'=> ['label' => 'Template Marketplace',  'icon' => 'M19 11H7m12 0l-4-4m4 4l-4 4M3 12a9 9 0 1118 0 9 9 0 01-18 0z'],
        'paid_templates'      => ['label' => 'Paid Templates',        'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'broadcasts'          => ['label' => 'Broadcasts',            'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
        'advanced_broadcasts' => ['label' => 'Advanced Broadcasts',   'icon' => 'M7 4V2m10 2V2M5 8h14M5 8a2 2 0 00-2 2v8a2 2 0 002 2h14a2 2 0 002-2v-8a2 2 0 00-2-2M5 8V6a2 2 0 012-2h10a2 2 0 012 2v2'],
        'bot_user_tracking'   => ['label' => 'User Tracking',         'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        'analytics'           => ['label' => 'Analytics',             'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        'error_logs'          => ['label' => 'Logs & Errors',         'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
        'custom_webhooks'     => ['label' => 'Custom Webhooks',       'icon' => 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14'],
        'priority_support'    => ['label' => 'Priority Support',      'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'],
        'team_access'         => ['label' => 'Team Access',           'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
        'api_access'          => ['label' => 'API Access',            'icon' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
    ];

    $limitCards = [
        'bots_allowed'                      => ['label' => 'Bots Allowed',        'icon' => 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18'],
        'commands_per_bot'                  => ['label' => 'Commands / Bot',      'icon' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'broadcasts_per_month'              => ['label' => 'Broadcasts / Month',  'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
        'broadcast_recipients_per_send'     => ['label' => 'Recipients / Send',   'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
        'storage_mb'                        => ['label' => 'Storage',             'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
        'bot_users_tracked'                 => ['label' => 'Bot Users Tracked',   'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        'free_templates_unlocked_per_month' => ['label' => 'Free Unlocks / Month','icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192A48.424 48.424 0 0 0 17.15 3.836M13.5 2.25H15c1.012 0 1.867.668 2.15 1.586M13.5 2.25c-.376.023-.75.05-1.124.08C11.245 2.424 10.4 3.387 10.4 4.522V6.75'],
        'logs_retention_days'               => ['label' => 'Log Retention',       'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];

    $navItems = [
        ['id' => 'profile',  'label' => 'Profile',           'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        ['id' => 'security', 'label' => 'Security',          'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
        ['id' => 'plan',     'label' => 'Plan & Membership', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
        ['id' => 'bots',     'label' => 'My Bots',           'icon' => 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18'],
        ['id' => 'activity', 'label' => 'Activity Log',      'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
        ['id' => 'danger',   'label' => 'Danger Zone',       'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
    ];
@endphp

{{-- Success flash --}}
@if(session('status') === 'profile-updated' || session('status') === 'password-updated')
    <div
        x-data="{ show: true }"
        x-show="show"
        x-transition
        x-init="setTimeout(() => show = false, 3500)"
        class="mb-5 flex items-center gap-3 rounded-2xl border border-[#22C55E]/25 bg-[#22C55E]/8 px-4 py-3 text-sm font-semibold text-[#22C55E]"
    >
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        {{ session('status') === 'profile-updated' ? 'Profile information saved.' : 'Password updated successfully.' }}
    </div>
@endif

<div x-data="{ tab: '{{ $initialTab }}' }">

    {{-- ════════════════════════════════
         ACCOUNT HERO BANNER
    ════════════════════════════════ --}}
    <div class="relative mb-6 overflow-hidden rounded-3xl border border-[#1E293B] bg-[#0F0D1A]">
        {{-- Background glows --}}
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#8B5CF6]/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-16 bottom-0 h-48 w-48 rounded-full bg-[#38BDF8]/5 blur-3xl"></div>
        {{-- Purple top bar --}}
        <div class="h-1 w-full bg-gradient-to-r from-[#8B5CF6] via-[#A855F7] to-[#38BDF8]"></div>
        <div class="relative px-5 py-5 sm:px-7 sm:py-6">
            <div class="flex flex-wrap items-center justify-between gap-5">
                {{-- Left: avatar + info --}}
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <div class="grid h-14 w-14 sm:h-16 sm:w-16 place-items-center rounded-2xl bg-gradient-to-br from-[#8B5CF6] to-[#A855F7] text-xl font-black text-white shadow-[0_0_32px_rgba(139,92,246,0.45)] select-none">
                            {{ $initials }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-[#0F0D1A] {{ $user->status === 'active' ? 'bg-[#22C55E]' : 'bg-[#F59E0B]' }}"></span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-lg font-black text-[#F8FAFC] sm:text-xl">{{ $user->name ?? $user->username ?? 'Account' }}</h1>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide"
                                style="border-color:{{ $planColor }}30; color:{{ $planColor }}; background-color:{{ $planColor }}12"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" style="background-color:{{ $planColor }}; box-shadow:0 0 5px {{ $planColor }}"></span>
                                {{ $planName }}
                            </span>
                            @if($user->isAdmin())
                                <span class="inline-flex items-center gap-1 rounded-full border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide text-[#F59E0B]">Admin</span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-sm text-[#94A3B8]">{{ $user->email }}</p>
                    </div>
                </div>
                {{-- Right: quick stats --}}
                <div class="flex items-center gap-1 rounded-2xl border border-[#1E293B] bg-[#07060F] px-1 py-1 sm:gap-0">
                    @foreach([
                        ['Total Bots',  $botStats['total'],    '#8B5CF6'],
                        ['Running',     $botStats['active'],   '#22C55E'],
                        ['Commands',    $botStats['commands'], '#38BDF8'],
                        ['Bot Users',   $botStats['users'],    '#F59E0B'],
                    ] as [$label, $value, $color])
                    <div class="px-4 py-2 text-center {{ !$loop->last ? 'border-r border-[#1E293B]' : '' }}">
                        <p class="text-lg font-black sm:text-xl" style="color:{{ $color }}">{{ number_format($value) }}</p>
                        <p class="text-[9px] font-black uppercase tracking-wide text-[#94A3B8]">{{ $label }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════
         MAIN GRID
    ════════════════════════════════ --}}
    <div class="grid gap-5 lg:grid-cols-[300px_1fr]">

        {{-- ── LEFT COLUMN ── --}}
        <div class="space-y-4 lg:sticky lg:top-[72px] self-start">

            {{-- Profile Card --}}
            <div class="overflow-hidden rounded-2xl border border-[#1E293B] bg-[#0F0D1A]">
                {{-- Card header --}}
                <div class="bg-gradient-to-br from-[#8B5CF6]/12 to-[#0F0D1A] px-5 pt-5 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#8B5CF6] to-[#A855F7] text-sm font-black text-white shadow-[0_0_18px_rgba(139,92,246,0.4)] select-none">
                            {{ $initials }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-[#F8FAFC]">{{ $user->name ?? $user->username }}</p>
                            <p class="truncate text-[11px] text-[#94A3B8]">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>
                {{-- Meta rows --}}
                <div class="divide-y divide-[#131220] px-5 py-1">
                    @foreach([
                        ['Account Status', $user->status === 'active' ? 'Active' : ucfirst($user->status ?? 'active'), $user->status === 'active' ? '#22C55E' : '#F59E0B'],
                        ['Member Since',   $user->created_at?->format('M Y') ?? '—',     '#A1A1AA'],
                    ] as [$key, $val, $col])
                    <div class="flex items-center justify-between py-2.5">
                        <span class="text-[11px] text-[#94A3B8]">{{ $key }}</span>
                        <span class="text-[11px] font-semibold" style="color:{{ $col }}">{{ $val }}</span>
                    </div>
                    @endforeach
                    @if($user->subscription_expires_at)
                    <div class="flex items-center justify-between py-2.5">
                        <span class="text-[11px] text-[#94A3B8]">{{ $user->subscription_expires_at->isPast() ? 'Plan Expired' : 'Plan Renews' }}</span>
                        <span class="text-[11px] font-semibold {{ $user->subscription_expires_at->isPast() ? 'text-[#EF4444]' : 'text-[#A1A1AA]' }}">{{ $user->subscription_expires_at->format('M d, Y') }}</span>
                    </div>
                    @endif
                    @if($user->wallet_balance > 0)
                    <div class="flex items-center justify-between py-2.5">
                        <span class="text-[11px] text-[#94A3B8]">Wallet</span>
                        <span class="text-[11px] font-semibold text-[#22C55E]">${{ number_format($user->wallet_balance, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="overflow-hidden rounded-2xl border border-[#1E293B] bg-[#0F0D1A] p-1.5">
                @foreach($navItems as $item)
                <button
                    @click="tab = '{{ $item['id'] }}'"
                    :class="tab === '{{ $item['id'] }}'
                        ? '{{ $item['id'] === 'danger' ? 'bg-[#EF4444]/8 text-[#EF4444]' : 'bg-[#8B5CF6]/10 text-[#F8FAFC]' }}'
                        : '{{ $item['id'] === 'danger' ? 'text-[#EF4444]/50 hover:bg-[#EF4444]/5 hover:text-[#EF4444]/70' : 'text-[#6B6785] hover:bg-[#111020] hover:text-[#C4C0D8]' }}'"
                    class="relative w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all duration-150 {{ $item['id'] === 'danger' ? 'mt-1' : '' }}"
                >
                    {{-- Active left bar --}}
                    <span
                        x-show="tab === '{{ $item['id'] }}'"
                        x-cloak
                        class="absolute left-0 inset-y-1.5 w-[3px] rounded-r-full {{ $item['id'] === 'danger' ? 'bg-[#EF4444]' : 'bg-gradient-to-b from-[#8B5CF6] to-[#A855F7]' }}"
                    ></span>
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-all"
                        :class="tab === '{{ $item['id'] }}'
                            ? '{{ $item['id'] === 'danger' ? 'bg-[#EF4444]/15 text-[#EF4444]' : 'bg-[#8B5CF6] text-white shadow-[0_0_12px_rgba(139,92,246,0.5)]' }}'
                            : '{{ $item['id'] === 'danger' ? 'text-[#EF4444]/40' : 'text-[#7E7AA0]' }}'"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                    </span>
                    <span class="flex-1 text-left">{{ $item['label'] }}</span>
                    <span
                        x-show="tab === '{{ $item['id'] }}'"
                        x-cloak
                        class="h-1.5 w-1.5 shrink-0 rounded-full {{ $item['id'] === 'danger' ? 'bg-[#EF4444]' : 'bg-[#8B5CF6]' }}"
                    ></span>
                </button>
                @endforeach
            </nav>

            {{-- Storage Mini Card --}}
            <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A] p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="grid h-7 w-7 place-items-center rounded-lg bg-[#8B5CF6]/10 text-[#8B5CF6]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        </div>
                        <p class="text-[11px] font-black text-[#A1A1AA]">Storage</p>
                    </div>
                    @if(!$isUnlimitedStore)
                        <span class="text-[11px] font-black" style="color:{{ $storeBarColor }}">{{ $storagePct }}%</span>
                    @else
                        <span class="text-[10px] font-black text-[#22C55E]">Unlimited</span>
                    @endif
                </div>
                <div class="mb-2 h-1.5 w-full overflow-hidden rounded-full bg-[#1E293B]">
                    @if(!$isUnlimitedStore && $storagePct > 0)
                        <div class="h-full rounded-full transition-all" style="width:{{ $storagePct }}%; background-color:{{ $storeBarColor }}"></div>
                    @elseif($isUnlimitedStore)
                        <div class="h-full w-2/3 rounded-full bg-gradient-to-r from-[#8B5CF6]/40 to-[#38BDF8]/40"></div>
                    @endif
                </div>
                <p class="text-[11px] text-[#94A3B8]">
                    <span class="font-semibold text-[#A1A1AA]">{{ $storeUsedDisplay }}</span>
                    &nbsp;of&nbsp;
                    <span class="font-semibold text-[#A1A1AA]">{{ $storeLimitDisplay }}</span>
                </p>
                @if(!$isUnlimitedStore && $storagePct >= 80)
                    <div class="mt-2.5 rounded-xl border border-[#EF4444]/20 bg-[#EF4444]/6 px-3 py-2 text-[10px] font-semibold text-[#EF4444]">
                        Storage {{ $storagePct >= 90 ? 'critically' : 'almost' }} full.
                        <a href="{{ route('dashboard.upgrade') }}" class="ml-1 underline">Upgrade</a>
                    </div>
                @endif
            </div>

        </div>
        {{-- end left --}}

        {{-- ── RIGHT COLUMN ── --}}
        <div class="min-w-0">

            {{-- Mobile horizontal tab scroll --}}
            <div class="mb-4 flex gap-1.5 overflow-x-auto pb-1 lg:hidden" style="scrollbar-width:none">
                @foreach($navItems as $item)
                <button
                    @click="tab = '{{ $item['id'] }}'"
                    :class="tab === '{{ $item['id'] }}'
                        ? '{{ $item['id'] === 'danger' ? 'border-[#EF4444]/40 bg-[#EF4444]/10 text-[#EF4444]' : 'border-[#8B5CF6] bg-[#8B5CF6]/12 text-white' }}'
                        : '{{ $item['id'] === 'danger' ? 'border-[#1E293B] text-[#EF4444]/50' : 'border-[#1E293B] text-[#94A3B8]' }}'"
                    class="shrink-0 rounded-xl border px-3 py-2 text-xs font-semibold transition whitespace-nowrap"
                >{{ $item['label'] }}</button>
                @endforeach
            </div>

            {{-- ═══════════════════════════
                 TAB: PROFILE
            ═══════════════════════════ --}}
            <div
                x-show="tab === 'profile'"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak
                class="space-y-4"
            >
                <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A]">
                    {{-- Section header --}}
                    <div class="flex items-center gap-3 border-b border-[#131220] px-6 py-4">
                        <div class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-[#8B5CF6]/12 text-[#8B5CF6]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-[#F8FAFC]">Profile Information</h2>
                            <p class="text-[11px] text-[#94A3B8]">Update your display name, username, and email address.</p>
                        </div>
                    </div>
                    {{-- Form --}}
                    <div class="p-6">
                        <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
                            @csrf
                            @method('patch')

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="name" value="Full Name" />
                                    <x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                                    <x-input-error class="mt-1.5 text-xs text-[#FCA5A5]" :messages="$errors->get('name')" />
                                </div>
                                <div>
                                    <x-input-label for="username" value="Username" />
                                    <x-text-input id="username" name="username" type="text" class="mt-1.5 block w-full" :value="old('username', $user->username)" required autocomplete="username" />
                                    <x-input-error class="mt-1.5 text-xs text-[#FCA5A5]" :messages="$errors->get('username')" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="email" value="Email Address" />
                                <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full" :value="old('email', $user->email)" required autocomplete="email" />
                                <x-input-error class="mt-1.5 text-xs text-[#FCA5A5]" :messages="$errors->get('email')" />
                                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                    <p class="mt-2 flex items-center gap-1.5 text-xs text-[#F59E0B]">
                                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                                        Your email address is unverified.
                                        <button form="send-verification" class="underline hover:text-[#FBBF24]">Resend verification</button>
                                    </p>
                                @endif
                            </div>

                            <div class="flex items-center justify-between border-t border-[#131220] pt-4">
                                <p class="text-[11px] text-[#52525B]">Changes apply immediately after saving.</p>
                                <button type="submit" class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white shadow-[0_0_18px_rgba(139,92,246,0.28)] transition hover:-translate-y-0.5 hover:shadow-[0_0_28px_rgba(139,92,246,0.42)]">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>
                        @endif
                    </div>
                </div>

                {{-- Account meta info tiles --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach([
                        ['Member Since', $user->created_at?->format('M j, Y') ?? '—',            '#8B5CF6'],
                        ['Account Role', $user->isAdmin() ? 'Administrator' : 'User',             '#F59E0B'],
                        ['Account Status', ucfirst($user->status ?? 'active'),                    $user->status === 'active' ? '#22C55E' : '#F59E0B'],
                        ['Plan',          $planName,                                               $planColor],
                    ] as [$label, $value, $color])
                    <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A] p-4">
                        <p class="text-[9px] font-black uppercase tracking-widest text-[#52525B]">{{ $label }}</p>
                        <p class="mt-1.5 truncate text-sm font-black" style="color:{{ $color }}">{{ $value }}</p>
                    </div>
                    @endforeach
                </div>
            </div>


            {{-- ═══════════════════════════
                 TAB: SECURITY
            ═══════════════════════════ --}}
            <div
                x-show="tab === 'security'"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak
                class="space-y-4"
            >
                {{-- Password Update --}}
                <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A]">
                    <div class="flex items-center gap-3 border-b border-[#131220] px-6 py-4">
                        <div class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-[#8B5CF6]/12 text-[#8B5CF6]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-[#F8FAFC]">Update Password</h2>
                            <p class="text-[11px] text-[#94A3B8]">Use a long, random password to keep your account secure.</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <form method="post" action="{{ route('password.profile.update') }}" class="space-y-5">
                            @csrf
                            @method('put')

                            <div>
                                <x-input-label for="update_password_current_password" value="Current Password" />
                                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1.5 block w-full" autocomplete="current-password" />
                                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5 text-xs text-[#FCA5A5]" />
                            </div>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="update_password_password" value="New Password" />
                                    <x-text-input id="update_password_password" name="password" type="password" class="mt-1.5 block w-full" autocomplete="new-password" />
                                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5 text-xs text-[#FCA5A5]" />
                                </div>
                                <div>
                                    <x-input-label for="update_password_password_confirmation" value="Confirm New Password" />
                                    <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1.5 block w-full" autocomplete="new-password" />
                                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5 text-xs text-[#FCA5A5]" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between border-t border-[#131220] pt-4">
                                <p class="text-[11px] text-[#52525B]">You will remain logged in after changing your password.</p>
                                <button type="submit" class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white shadow-[0_0_18px_rgba(139,92,246,0.28)] transition hover:-translate-y-0.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- 2FA Coming Soon --}}
                <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A] p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-[#1E293B] bg-[#07060F] text-[#7E7AA0]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-black text-[#F8FAFC]">Two-Factor Authentication</h3>
                                    <span class="rounded-full border border-[#38BDF8]/30 bg-[#38BDF8]/8 px-2 py-0.5 text-[9px] font-black uppercase tracking-wide text-[#38BDF8]">Coming Soon</span>
                                </div>
                                <p class="mt-1 text-xs text-[#94A3B8]">Add an extra layer of protection using an authenticator app.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 rounded-xl border border-[#131220] bg-[#07060F] px-4 py-3 text-xs text-[#52525B]">
                        Two-factor authentication will be available in a future update. Keep your password strong in the meantime.
                    </div>
                </div>

                {{-- Security info tiles --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A] p-4">
                        <p class="text-[9px] font-black uppercase tracking-widest text-[#52525B]">Last Seen</p>
                        <p class="mt-1.5 text-sm font-black text-[#A1A1AA]">{{ $user->updated_at?->diffForHumans() ?? 'Just now' }}</p>
                    </div>
                    <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A] p-4">
                        <p class="text-[9px] font-black uppercase tracking-widest text-[#52525B]">2FA Status</p>
                        <p class="mt-1.5 text-sm font-black text-[#94A3B8]">Not enabled</p>
                    </div>
                </div>
            </div>


            {{-- ═══════════════════════════
                 TAB: PLAN & MEMBERSHIP
            ═══════════════════════════ --}}
            <div
                x-show="tab === 'plan'"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak
                class="space-y-4"
            >
                {{-- Plan Header --}}
                <div class="relative overflow-hidden rounded-2xl border bg-[#0F0D1A] p-6" style="border-color:{{ $planColor }}25">
                    <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full blur-3xl" style="background-color:{{ $planColor }}0A"></div>
                    <div class="relative flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <div class="grid h-10 w-10 place-items-center rounded-xl" style="background-color:{{ $planColor }}14">
                                    <svg class="h-5 w-5" style="color:{{ $planColor }}" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-black text-[#F8FAFC]">{{ $planName }}</h2>
                                    @if($subscriptionPlan?->description)
                                        <p class="text-xs text-[#94A3B8]">{{ $subscriptionPlan->description }}</p>
                                    @endif
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wide" style="border-color:{{ $planColor }}30; color:{{ $planColor }}; background-color:{{ $planColor }}10">
                                <span class="h-1.5 w-1.5 rounded-full" style="background-color:{{ $planColor }}; box-shadow:0 0 4px {{ $planColor }}"></span>
                                {{ ucfirst($user->subscription_status ?? 'active') }}
                            </span>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-3xl font-black text-[#F8FAFC]">{{ $subscriptionPlan?->formattedPrice() ?? 'Free' }}</p>
                            @if($subscriptionPlan && (float) $subscriptionPlan->price > 0)
                                <p class="text-xs text-[#94A3B8]">per {{ $subscriptionPlan->billing_period ?? 'month' }}</p>
                            @endif
                        </div>
                    </div>

                    @if($user->subscription_started_at || $user->subscription_expires_at)
                    <div class="mt-5 grid grid-cols-2 gap-4 border-t pt-4" style="border-color:{{ $planColor }}12">
                        @if($user->subscription_started_at)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wide text-[#94A3B8] mb-0.5">Started</p>
                            <p class="text-sm font-semibold text-[#A1A1AA]">{{ $user->subscription_started_at->format('M d, Y') }}</p>
                        </div>
                        @endif
                        @if($user->subscription_expires_at)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wide text-[#94A3B8] mb-0.5">{{ $user->subscription_expires_at->isPast() ? 'Expired' : 'Renews' }}</p>
                            <p class="text-sm font-semibold {{ $user->subscription_expires_at->isPast() ? 'text-[#EF4444]' : 'text-[#A1A1AA]' }}">{{ $user->subscription_expires_at->format('M d, Y') }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    @if(!$user->isAdmin() && $planSlug !== 'business')
                    <div class="mt-5">
                        <a href="{{ route('dashboard.upgrade') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-4 py-2.5 text-sm font-black text-white shadow-[0_0_20px_rgba(139,92,246,0.3)] transition hover:-translate-y-0.5 hover:shadow-[0_0_30px_rgba(139,92,246,0.5)]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/></svg>
                            Upgrade Plan
                        </a>
                    </div>
                    @endif
                </div>

                {{-- Plan Limits --}}
                @php $visibleLimits = array_filter($limitCards, fn($key) => isset($planLimits[$key]), ARRAY_FILTER_USE_KEY); @endphp
                @if(!empty($visibleLimits))
                <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A] p-5">
                    <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Plan Limits</p>
                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 xl:grid-cols-4">
                        @foreach($limitCards as $key => $cfg)
                            @if(isset($planLimits[$key]))
                                @php
                                    $ld      = $planLimits[$key];
                                    $dv      = $fmtLimit($key, $ld);
                                    $isUnlim = $ld['is_unlimited'] || $ld['value'] === null;
                                @endphp
                                <div class="flex flex-col gap-2 rounded-xl border border-[#1E293B] bg-[#07060F] p-3.5">
                                    <svg class="h-3.5 w-3.5 text-[#7E7AA0]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $cfg['icon'] }}"/>
                                    </svg>
                                    <p class="text-lg font-black leading-none {{ $isUnlim ? 'text-[#22C55E]' : 'text-[#F8FAFC]' }}">{{ $dv }}</p>
                                    <p class="text-[10px] text-[#94A3B8]">{{ $cfg['label'] }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Plan Features --}}
                @if(!empty($planFeatures))
                <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A] p-5">
                    <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Features</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach($featureLabels as $key => $cfg)
                            @if(array_key_exists($key, $planFeatures))
                                @php $on = (bool) $planFeatures[$key]; @endphp
                                <div class="flex items-center gap-2.5 rounded-xl border px-3 py-2.5 {{ $on ? 'border-[#22C55E]/15 bg-[#22C55E]/5' : 'border-[#1E293B]/40 bg-[#07060F] opacity-50' }}">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $on ? 'bg-[#22C55E]/15' : 'bg-[#1E293B]' }}">
                                        @if($on)
                                            <svg class="h-2.5 w-2.5 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <svg class="h-2.5 w-2.5 text-[#7E7AA0]" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        @endif
                                    </span>
                                    <span class="text-xs font-semibold {{ $on ? 'text-[#A1A1AA]' : 'text-[#7E7AA0]' }}">{{ $cfg['label'] }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>


            {{-- ═══════════════════════════
                 TAB: MY BOTS
            ═══════════════════════════ --}}
            <div
                x-show="tab === 'bots'"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak
                class="space-y-4"
            >
                {{-- Stats --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach([
                        ['Total Bots',     $botStats['total'],    '#8B5CF6', 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18'],
                        ['Running',        $botStats['active'],   '#22C55E', 'M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728M12 9v3l2 2'],
                        ['Commands',       $botStats['commands'], '#38BDF8', 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['Bot Users',      $botStats['users'],    '#F59E0B', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                    ] as [$label, $value, $color, $icon])
                    <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A] p-4">
                        <div class="mb-2.5 grid h-8 w-8 place-items-center rounded-xl" style="background-color:{{ $color }}14; color:{{ $color }}">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        </div>
                        <p class="text-2xl font-black text-[#F8FAFC]">{{ number_format($value) }}</p>
                        <p class="mt-0.5 text-[11px] text-[#94A3B8]">{{ $label }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- Bots List --}}
                <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A]">
                    <div class="flex items-center justify-between border-b border-[#131220] px-5 py-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Your Bots</p>
                        <a href="{{ route('bots.index') }}" class="text-xs font-semibold text-[#8B5CF6] transition hover:text-[#A855F7]">View All →</a>
                    </div>
                    @if($botStats['recent']->isEmpty())
                        <div class="flex flex-col items-center gap-3 py-14 text-center">
                            <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#1E293B] bg-[#07060F] text-[#7E7AA0]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-[#94A3B8]">No bots yet</p>
                                <a href="{{ route('bots.create') }}" class="mt-1 inline-block text-xs font-semibold text-[#8B5CF6] transition hover:text-[#A855F7]">Create your first bot →</a>
                            </div>
                        </div>
                    @else
                        <div class="divide-y divide-[#131220]">
                            @foreach($botStats['recent'] as $bot)
                            <a href="{{ route('bots.show', $bot) }}" class="group flex items-center gap-3 px-5 py-3.5 transition hover:bg-[#111020]">
                                <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-[#8B5CF6]/20 to-[#8B5CF6]/5 text-xs font-black text-[#A855F7]">
                                    {{ strtoupper(substr($bot->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-[#F8FAFC] transition group-hover:text-[#8B5CF6]">{{ $bot->name }}</p>
                                    <p class="text-[10px] capitalize text-[#94A3B8]">{{ $bot->status }}</p>
                                </div>
                                <div class="hidden items-center gap-3 text-[11px] text-[#94A3B8] sm:flex">
                                    <span>{{ $bot->commands_count }} cmds</span>
                                    <span>{{ $bot->bot_users_count }} users</span>
                                </div>
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $bot->status === 'running' ? 'bg-[#22C55E] shadow-[0_0_5px_rgba(34,197,94,0.7)]' : ($bot->status === 'paused' ? 'bg-[#F59E0B]' : 'bg-[#4D4868]') }}"></span>
                            </a>
                            @endforeach
                        </div>
                        <div class="border-t border-[#131220] px-5 py-3">
                            <a href="{{ route('bots.create') }}" class="flex items-center gap-2 text-xs font-semibold text-[#8B5CF6] transition hover:text-[#A855F7]">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Create New Bot
                            </a>
                        </div>
                    @endif
                </div>
            </div>


            {{-- ═══════════════════════════
                 TAB: ACTIVITY LOG
            ═══════════════════════════ --}}
            <div
                x-show="tab === 'activity'"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak
            >
                <div class="rounded-2xl border border-[#1E293B] bg-[#0F0D1A]">
                    <div class="flex items-center justify-between border-b border-[#131220] px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-[#8B5CF6]/12 text-[#8B5CF6]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-black text-[#F8FAFC]">Recent Activity</h2>
                                <p class="text-[11px] text-[#94A3B8]">Last {{ $recentActivity->count() }} account events</p>
                            </div>
                        </div>
                    </div>
                    @if($recentActivity->isEmpty())
                        <div class="flex flex-col items-center gap-3 py-14 text-center">
                            <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#1E293B] bg-[#07060F] text-[#7E7AA0]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-[#A1A1AA]">No activity recorded yet</p>
                                <p class="mt-0.5 text-xs text-[#94A3B8]">Account events will appear here as you use BotHost Pro.</p>
                            </div>
                        </div>
                    @else
                        <div class="divide-y divide-[#131220]">
                            @foreach($recentActivity as $log)
                            <div class="flex items-start gap-3 px-5 py-3.5">
                                <div class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-[#8B5CF6]/60"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-[#A1A1AA]">{{ $log->action }}</p>
                                    @if($log->description)
                                        <p class="mt-0.5 truncate text-[11px] text-[#94A3B8]">{{ $log->description }}</p>
                                    @endif
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-[11px] text-[#94A3B8]">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->diffForHumans() : '—' }}</p>
                                    @if($log->ip_address)
                                        <p class="mt-0.5 font-mono text-[10px] text-[#6B6890]">{{ $log->ip_address }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>


            {{-- ═══════════════════════════
                 TAB: DANGER ZONE
            ═══════════════════════════ --}}
            <div
                x-show="tab === 'danger'"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak
                class="space-y-4"
            >
                <div class="flex items-start gap-3 rounded-2xl border border-[#EF4444]/20 bg-[#EF4444]/5 px-4 py-3.5">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#EF4444]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-xs font-semibold leading-relaxed text-[#EF4444]/80">Actions in this section are permanent and cannot be undone. Proceed with extreme caution.</p>
                </div>

                <div class="rounded-2xl border border-[#EF4444]/20 bg-[#0F0D1A] p-6">
                    <div class="flex items-start gap-4 mb-5">
                        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#EF4444]/20 bg-[#EF4444]/8 text-[#EF4444]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-[#F8FAFC]">Delete Account</h2>
                            <p class="mt-1 text-xs leading-relaxed text-[#94A3B8]">Permanently delete your account, all bots, commands, and data. This action cannot be reversed.</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-[#EF4444]/15 bg-[#07060F] px-4 py-3 mb-5">
                        <p class="text-xs text-[#94A3B8]">This will permanently delete:</p>
                        <ul class="mt-2 space-y-1 text-xs text-[#94A3B8]">
                            @foreach(['All your bots and their commands', 'All bot users and activity data', 'All broadcasts and logs', 'Your account and profile data'] as $item)
                            <li class="flex items-center gap-2">
                                <svg class="h-3 w-3 shrink-0 text-[#EF4444]/60" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                {{ $item }}
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <button
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                        class="flex items-center gap-2 rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/8 px-4 py-2.5 text-sm font-black text-[#EF4444] transition hover:bg-[#EF4444]/15 hover:border-[#EF4444]/50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete My Account
                    </button>
                </div>
            </div>

        </div>
        {{-- end right column --}}

    </div>
    {{-- end grid --}}

</div>

{{-- Deletion Modal --}}
<x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
    <form method="post" action="{{ route('profile.destroy') }}" class="rounded-2xl bg-[#0B1220] p-6">
        @csrf
        @method('delete')

        <div class="mb-5 flex items-start gap-3">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#EF4444]/20 bg-[#EF4444]/10 text-[#EF4444]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h2 class="text-base font-black text-[#F8FAFC]">Delete Account?</h2>
                <p class="mt-1 text-xs leading-relaxed text-[#94A3B8]">This will permanently delete all your bots, commands, settings, and account data. There is no recovery.</p>
            </div>
        </div>

        <div>
            <x-input-label for="delete_password" value="Confirm your password" />
            <x-text-input id="delete_password" name="password" type="password" class="mt-1.5 block w-full" placeholder="Enter your password to confirm" />
            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1.5 text-xs text-[#FCA5A5]" />
        </div>

        <div class="mt-5 flex items-center justify-end gap-3">
            <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-[#1E293B] px-4 py-2.5 text-sm font-bold text-[#F8FAFC] transition hover:border-[#8B5CF6]/40">
                Cancel
            </button>
            <button type="submit" class="rounded-xl bg-[#EF4444] px-4 py-2.5 text-sm font-black text-white transition hover:bg-red-400">
                Yes, Delete Everything
            </button>
        </div>
    </form>
</x-modal>

</x-dashboard-layout>
