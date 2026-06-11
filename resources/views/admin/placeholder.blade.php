<x-admin-layout :title="$title">

@php
    $configs = [
        'Templates' => [
            'subtitle'    => 'Template marketplace and publishing controls',
            'description' => 'Create, publish, and manage bot templates that users can clone with one click. Includes review workflows, pricing, and marketplace analytics.',
            'icon_color'  => '#8B5CF6',
            'icon_bg'     => 'rgba(139,92,246,0.12)',
            'accent'      => '#A855F7',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />',
            'features'    => ['Template marketplace', 'One-click clone', 'Review & approval workflow', 'Pricing controls', 'Usage analytics'],
        ],
        'Deposits' => [
            'subtitle'    => 'Payment records and billing management',
            'description' => 'View all deposits, subscription payments, and billing history across all users. Includes payment gateway integration and refund management.',
            'icon_color'  => '#22C55E',
            'icon_bg'     => 'rgba(34,197,94,0.1)',
            'accent'      => '#22C55E',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />',
            'features'    => ['Deposit history', 'Subscription tracking', 'Payment gateway integration', 'Refund management', 'Revenue analytics'],
        ],
        'Broadcasts' => [
            'subtitle'    => 'Platform-wide messaging and announcements',
            'description' => 'Send targeted messages to all bot users across the platform, segment by plan or activity, and schedule campaigns.',
            'icon_color'  => '#38BDF8',
            'icon_bg'     => 'rgba(56,189,248,0.1)',
            'accent'      => '#38BDF8',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />',
            'features'    => ['Targeted broadcasts', 'Audience segmentation', 'Campaign scheduling', 'Delivery analytics', 'Template library'],
        ],
        'Logs' => [
            'subtitle'    => 'Platform activity audit trail',
            'description' => 'Full audit log of all admin actions, user events, bot runtime activity, errors, and security events across the platform.',
            'icon_color'  => '#F59E0B',
            'icon_bg'     => 'rgba(245,158,11,0.1)',
            'accent'      => '#F59E0B',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />',
            'features'    => ['Admin audit trail', 'Bot runtime logs', 'Error aggregation', 'Security events', 'Export & search'],
        ],
        'Security' => [
            'subtitle'    => 'Security monitoring and threat review',
            'description' => 'Monitor suspicious activity, review security alerts, manage IP blocks, and audit authentication events across the platform.',
            'icon_color'  => '#EF4444',
            'icon_bg'     => 'rgba(239,68,68,0.1)',
            'accent'      => '#EF4444',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />',
            'features'    => ['Failed login tracking', 'IP blocklist', 'Suspicious activity alerts', 'Authentication audit', 'Token security review'],
        ],
        'Settings' => [
            'subtitle'    => 'Global platform configuration',
            'description' => 'Control platform-wide settings including plan limits, feature flags, maintenance mode, default configurations, and integrations.',
            'icon_color'  => '#A1A1AA',
            'icon_bg'     => 'rgba(161,161,170,0.08)',
            'accent'      => '#A1A1AA',
            'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
            'features'    => ['Plan limits configuration', 'Feature flags', 'Maintenance mode', 'Default bot settings', 'Integration settings'],
        ],
    ];
    $cfg = $configs[$title] ?? [
        'subtitle' => 'Coming soon',
        'description' => 'This admin section will be available in a future release.',
        'icon_color' => '#8B5CF6', 'icon_bg' => 'rgba(139,92,246,0.12)', 'accent' => '#A855F7',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        'features' => [],
    ];
@endphp

{{-- Hero card --}}
<div class="relative overflow-hidden rounded-3xl border border-[#27213D] p-8 sm:p-10" style="background: linear-gradient(135deg, #0F0D1A 0%, #151225 100%);">
    {{-- Background glow --}}
    <div class="pointer-events-none absolute right-0 top-0 h-64 w-64 rounded-full opacity-20" style="background: radial-gradient(circle, {{ $cfg['icon_color'] }} 0%, transparent 70%); transform: translate(30%, -30%);"></div>

    <div class="relative flex flex-col gap-6 sm:flex-row sm:items-start">
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl" style="background: {{ $cfg['icon_bg'] }}; border: 1px solid {{ $cfg['icon_color'] }}22;">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="{{ $cfg['icon_color'] }}">
                {!! $cfg['icon'] !!}
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-3">
                <h2 class="text-3xl font-black text-white sm:text-4xl">{{ $title }}</h2>
                <span class="rounded-full border px-3 py-1 text-xs font-black uppercase tracking-widest" style="border-color: {{ $cfg['accent'] }}40; background: {{ $cfg['accent'] }}15; color: {{ $cfg['accent'] }};">
                    Coming Soon
                </span>
            </div>
            <p class="mt-1 text-sm font-semibold" style="color: {{ $cfg['accent'] }};">{{ $cfg['subtitle'] }}</p>
            <p class="mt-3 max-w-2xl text-[#A1A1AA]">{{ $cfg['description'] }}</p>
        </div>
    </div>
</div>

{{-- Planned features grid --}}
@if (!empty($cfg['features']))
    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($cfg['features'] as $i => $feature)
            <div class="flex items-center gap-3 rounded-2xl border border-[#27213D] px-4 py-3.5" style="background: #0F0D1A;">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg" style="background: {{ $cfg['icon_bg'] }};">
                    <span class="text-xs font-black" style="color: {{ $cfg['icon_color'] }};">{{ $i + 1 }}</span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-white">{{ $feature }}</p>
                    <span class="text-[10px] font-black uppercase tracking-wider" style="color: {{ $cfg['accent'] }}; opacity: 0.7;">Planned</span>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Status row --}}
<div class="mt-5 flex items-center gap-4 rounded-2xl border border-[#1B172B] px-5 py-4" style="background: #090713;">
    <div class="flex items-center gap-2">
        <span class="h-2 w-2 rounded-full bg-[#F59E0B]" style="box-shadow: 0 0 6px #F59E0B;"></span>
        <span class="text-xs font-bold text-[#F59E0B]">Foundation Ready</span>
    </div>
    <span class="text-[#27213D]">·</span>
    <p class="text-xs text-[#71717A]">Backend scaffolding is in place. UI and logic will be connected in a dedicated release phase.</p>
</div>

</x-admin-layout>
