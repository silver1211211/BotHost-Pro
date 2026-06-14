<x-dashboard-layout title="Upgrade Plan">
<div class="mx-auto max-w-6xl px-2 py-6 space-y-10">

    {{-- ── Page header ── --}}
    <div class="text-center space-y-3">
        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-[#8B5CF6]">Pricing</p>
        <h1 class="text-4xl font-black tracking-tight text-white">Simple, transparent pricing</h1>
        <p class="text-sm text-[#94A3B8] max-w-md mx-auto leading-relaxed">
            Build and scale your Telegram bots with the right plan for your needs.
            Upgrade or downgrade at any time.
        </p>
        <p class="text-xs text-[#7E7AA0]">
            Current plan:
            <span class="font-bold text-[#A1A1AA]">{{ ucfirst($currentPlan) }}</span>
        </p>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-[#38BDF8]/25 bg-[#38BDF8]/8 p-3 text-sm font-semibold text-[#38BDF8] text-center">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 p-3 text-sm text-[#EF4444] text-center">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- ── Cards ── --}}
    <div class="grid gap-6 md:grid-cols-3 items-stretch">
    @foreach ($plans as $plan)
    @php
        $alreadyHas      = auth()->user()->hasPlanAtLeast($plan->slug);
        $isCurrent       = $currentPlan === $plan->slug;
        $isFeatured      = $plan->slug === 'business';
        $displayFeatures = $plan->_displayFeatures ?? [];
        $displayLimits   = $plan->_displayLimits   ?? [];
    @endphp

    {{-- ── Card wrapper ── --}}
    <div class="relative flex flex-col overflow-hidden rounded-2xl
        {{ $isFeatured
            ? 'border-2 border-[#8B5CF6]/60 bg-gradient-to-b from-[#12102B] via-[#0F0D1A] to-[#0B091A] shadow-[0_8px_40px_rgba(139,92,246,0.22)]'
            : 'border border-[#27213D] bg-[#0F0D1A]' }}">

        {{-- Top accent line --}}
        @if ($isFeatured)
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#8B5CF6] to-transparent"></div>
        @endif

        {{-- Most popular badge --}}
        @if ($isFeatured)
        <div class="flex justify-center pt-5 pb-1">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#8B5CF6]/20 border border-[#8B5CF6]/40 px-3.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#C4B5FD]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#A855F7] animate-pulse"></span>
                Most Popular
            </span>
        </div>
        @else
        <div class="pt-6"></div>
        @endif

        <div class="flex flex-col flex-1 px-6 pb-7 space-y-6">

            {{-- Plan name & description --}}
            <div class="space-y-1">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-[13px] font-black uppercase tracking-[0.14em]
                        {{ $isFeatured ? 'text-[#C4B5FD]' : 'text-[#94A3B8]' }}">
                        {{ $plan->name }}
                    </h2>
                    @if ($isCurrent)
                        <span class="inline-flex items-center gap-1 rounded-full border border-[#22C55E]/30 bg-[#22C55E]/10 px-2.5 py-0.5 text-[10px] font-black text-[#22C55E]">
                            ✓ Active
                        </span>
                    @endif
                </div>
                @if ($plan->description)
                    <p class="text-xs text-[#94A3B8] leading-relaxed">{{ $plan->description }}</p>
                @endif
            </div>

            {{-- Price --}}
            <div class="border-b border-[#27213D] pb-6">
                @if ((float) $plan->price === 0.0)
                    <div class="flex items-baseline gap-1">
                        <span class="text-5xl font-black text-white tracking-tight">Free</span>
                    </div>
                    <p class="mt-1.5 text-xs text-[#7E7AA0]">No credit card required · Forever free</p>
                @else
                    <div class="flex items-start gap-0.5">
                        <span class="mt-2 text-base font-bold text-[#A1A1AA]">$</span>
                        <span class="text-5xl font-black text-white tracking-tight">{{ number_format((float) $plan->price, 0) }}</span>
                        <div class="mt-2 ml-0.5 flex flex-col leading-none">
                            <span class="text-sm font-semibold text-[#A1A1AA]">.{{ substr(number_format((float) $plan->price, 2), -2) }}</span>
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-[#94A3B8]">per {{ $plan->billing_period }} · billed in crypto</p>
                @endif
            </div>

            {{-- Limits table --}}
            @if (count($displayLimits) > 0)
            <div class="space-y-3">
                <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[#6B6890]">Plan limits</p>
                <div class="space-y-2">
                    @foreach ($displayLimits as $limit)
                    <div class="flex items-center justify-between gap-4 py-0.5">
                        <span class="text-[13px] text-[#94A3B8] leading-snug">{{ $limit['name'] }}</span>
                        <span class="shrink-0 text-[13px] font-bold text-right
                            {{ $limit['unlimited'] ? 'text-[#A855F7]' : 'text-white' }}">
                            {{ $limit['display'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Divider before features --}}
            @if (count($displayLimits) > 0 && count($displayFeatures) > 0)
                <div class="border-t border-[#1B172B]"></div>
            @endif

            {{-- Features checklist --}}
            @if (count($displayFeatures) > 0)
            <div class="space-y-3 flex-1">
                <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[#6B6890]">What's included</p>
                <ul class="space-y-2.5">
                    @foreach ($displayFeatures as $feature)
                    <li class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full
                            {{ $isFeatured ? 'bg-[#8B5CF6]/25 text-[#A855F7]' : 'bg-[#22C55E]/12 text-[#22C55E]' }}">
                            <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                            </svg>
                        </span>
                        <span class="text-[13px] text-[#A1A1AA] leading-snug">{{ $feature['label'] }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Empty state --}}
            @if (count($displayLimits) === 0 && count($displayFeatures) === 0)
                <p class="text-xs italic text-[#6B6890] flex-1">No features configured yet.</p>
            @endif

            {{-- CTA --}}
            <div class="pt-2">
                @if ($alreadyHas || $plan->slug === 'free')
                    <div class="flex items-center justify-center gap-2 rounded-xl border border-[#27213D] bg-[#090713] px-4 py-3.5 text-sm text-[#94A3B8]">
                        <svg class="h-4 w-4 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                        </svg>
                        {{ $isCurrent ? 'Your current plan' : 'Already included in your plan' }}
                    </div>
                @else
                    <form method="POST" action="{{ route('dashboard.upgrade.crypto-invoice', $plan) }}">
                        @csrf
                        <button type="submit"
                            class="group w-full rounded-xl py-4 text-sm font-black text-white transition-all duration-200
                            {{ $isFeatured
                                ? 'bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] shadow-[0_0_28px_rgba(139,92,246,0.40)] hover:shadow-[0_0_40px_rgba(139,92,246,0.60)] hover:from-[#7C3AED] hover:to-[#9333EA]'
                                : 'border border-[#27213D] bg-[#18152A] hover:bg-[#1F1A35] hover:border-[#8B5CF6]/40' }}">
                            <span class="flex items-center justify-center gap-2">
                                Upgrade to {{ $plan->name }}
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </button>
                        <p class="mt-2 text-center text-[11px] text-[#6B6890]">
                            Pay with cryptocurrency &mdash; choose your network on the next step
                        </p>
                    </form>
                @endif
            </div>

        </div>{{-- /px-6 --}}
    </div>{{-- /card --}}
    @endforeach
    </div>{{-- /grid --}}

    {{-- ── Bottom trust note ── --}}
    <div class="flex flex-col items-center gap-3 pt-2">
        <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs text-[#7E7AA0]">
            <span class="flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                </svg>
                Secure crypto payments
            </span>
            <span class="flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
                Instant activation
            </span>
            <span class="flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                </svg>
                No hidden fees
            </span>
        </div>
        <p class="text-[11px] text-[#6B6890]">All plans include core bot building features &bull; Upgrade anytime</p>
    </div>

</div>
</x-dashboard-layout>
