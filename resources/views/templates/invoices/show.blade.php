<x-dashboard-layout title="Crypto Invoice" :no-flash="true">
<div class="mx-auto max-w-xl space-y-4">

    @php
        $isPaid    = $invoice->status === 'paid';
        $isFailed  = in_array($invoice->status, ['failed', 'expired', 'cancelled'], true);
        $isActive  = filled($invoice->payment_address) && ! $isPaid && ! $isFailed;

        $networkLabel = \App\Services\OxaPayService::payCurrencyOptions()[$invoice->pay_currency ?? ''] ?? null;

        $statusColour = match(true) {
            $isPaid   => 'border-[#22C55E]/30 bg-[#22C55E]/10 text-[#22C55E]',
            $isFailed => 'border-[#EF4444]/30 bg-[#EF4444]/10 text-[#EF4444]',
            $isActive => 'border-[#F59E0B]/30 bg-[#F59E0B]/10 text-[#F59E0B]',
            default   => 'border-[#71717A]/30 bg-[#71717A]/10 text-[#94A3B8]',
        };

        $statusLabel = match($invoice->status) {
            'paid'               => 'Payment Confirmed',
            'waiting', 'pending' => 'Waiting for Payment',
            'paying'             => 'Payment Detected',
            'confirming'         => 'Confirming',
            'failed'             => 'Failed',
            'expired'            => 'Expired',
            'cancelled'          => 'Cancelled',
            default              => 'Pending',
        };
    @endphp

    {{-- Back --}}
    <a href="{{ route('dashboard.templates.show', $invoice->template) }}"
       class="inline-flex items-center gap-1.5 text-sm text-[#94A3B8] transition hover:text-[#A1A1AA]">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to template
    </a>

    {{-- Flash --}}
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-[#38BDF8]/25 bg-[#38BDF8]/8 px-4 py-3 text-sm font-semibold text-[#38BDF8]">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#EF4444] space-y-1">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Card --}}
    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-[#27213D] px-5 py-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-[#94A3B8]">Template Purchase</p>
                <h1 class="mt-0.5 text-lg font-black leading-tight">{{ $invoice->template->name }}</h1>
            </div>
            <span class="rounded-full border px-3 py-1 text-xs font-black {{ $statusColour }}">{{ $statusLabel }}</span>
        </div>

        <div class="p-5 space-y-4">

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#94A3B8]">Amount Due</p>
                    <p class="mt-0.5 text-xl font-black text-white">{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}</p>
                </div>
                <div class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#94A3B8]">Network</p>
                    <p class="mt-0.5 text-base font-black text-white">{{ $networkLabel ?? $invoice->pay_currency ?? $invoice->network ?? 'Crypto' }}</p>
                </div>
            </div>

            @if ($invoice->expires_at && $isActive)
            <div class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3">
                <p class="text-[10px] font-black uppercase tracking-wide text-[#94A3B8]">Expires In</p>
                <p class="mt-0.5 text-base font-black text-[#F59E0B]" id="expiry-timer">{{ $invoice->expires_at->diffForHumans() }}</p>
            </div>
            @endif

            @if ($isPaid)
                {{-- Paid state --}}
                <div class="flex flex-col items-center gap-4 py-2 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#22C55E]/15 shadow-[0_0_28px_rgba(34,197,94,0.25)]">
                        <svg class="h-7 w-7 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                    </div>
                    <div>
                        <p class="text-xl font-black text-[#22C55E]">Payment Confirmed</p>
                        <p class="mt-1 text-sm text-[#94A3B8]">Template unlocked and ready to import.</p>
                    </div>
                    <div class="flex flex-wrap justify-center gap-2">
                        <a href="{{ route('dashboard.templates.show', $invoice->template) }}"
                           class="rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white">
                            Install Template
                        </a>
                        <a href="{{ route('dashboard.templates.purchased') }}"
                           class="rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">
                            My Templates
                        </a>
                    </div>
                </div>

            @elseif ($isFailed)
                <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#EF4444]">
                    Invoice {{ $statusLabel }}. Please start a new purchase from the template page.
                </div>
                <a href="{{ route('dashboard.templates.show', $invoice->template) }}"
                   class="inline-block rounded-xl bg-[#8B5CF6] px-5 py-2.5 text-sm font-black text-white transition hover:bg-[#7C3AED]">
                    Back to Template
                </a>

            @elseif ($isActive)
                {{-- Address --}}
                <div class="rounded-xl border border-[#27213D] bg-[#090713] p-4 space-y-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#94A3B8]">Payment Address</p>
                    <p class="text-xs text-[#A1A1AA]">Send <span class="font-black text-white">{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}</span> worth of <span class="font-bold text-white">{{ $networkLabel ?? $invoice->pay_currency }}</span> in a single transaction.</p>
                    <div class="flex items-center gap-2 rounded-xl border border-[#8B5CF6]/20 bg-[#0F0D1A] px-3 py-2.5">
                        <code id="pay-address" class="flex-1 break-all font-mono text-sm font-bold leading-snug text-white">{{ $invoice->payment_address }}</code>
                        <button type="button" id="copy-btn" onclick="copyAddress()"
                                class="shrink-0 rounded-lg border border-[#27213D] bg-[#090713] px-3 py-1.5 text-xs font-bold text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-white">
                            Copy
                        </button>
                    </div>
                    <p class="text-[11px] text-[#F59E0B]">Unique address — send in a single transaction.</p>
                    <p class="mt-1 text-[11px] leading-snug text-[#EF4444]">⚠ Only send <strong>{{ $networkLabel ?? $invoice->pay_currency }}</strong>. Payments sent in any other currency or network are permanently lost and cannot be refunded.</p>
                    @if ($invoice->track_id)
                        <p class="text-[10px] text-[#7E7AA0]">Ref: <span class="font-mono">{{ $invoice->track_id }}</span></p>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('dashboard.template-invoices.check', $invoice) }}">
                        @csrf
                        <button class="rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white shadow-[0_0_18px_rgba(139,92,246,0.3)] transition hover:shadow-[0_0_26px_rgba(139,92,246,0.45)]">
                            Check Payment Status
                        </button>
                    </form>
                    <a href="{{ route('dashboard.templates.show', $invoice->template) }}"
                       class="rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">
                        Return
                    </a>
                </div>
                <p class="text-xs text-[#7E7AA0]">After sending payment, click <strong class="text-[#94A3B8]">Check Payment Status</strong> to confirm.</p>

            @else
                {{-- Pending, not yet generated --}}
                <div class="rounded-xl border border-[#27213D] bg-[#090713] px-4 py-3 text-sm text-[#94A3B8]">
                    Invoice is pending. Start a new purchase from the template page to generate a payment address.
                </div>
                <a href="{{ route('dashboard.templates.show', $invoice->template) }}"
                   class="inline-block rounded-xl bg-[#8B5CF6] px-5 py-2.5 text-sm font-black text-white transition hover:bg-[#7C3AED]">
                    Go to Template
                </a>
            @endif

        </div>
    </div>

</div>


{{-- Expiry countdown --}}
@if ($isActive && $invoice->expires_at)
<script>
(function () {
    var ts = {{ $invoice->expires_at->timestamp }};
    var el = document.getElementById('expiry-timer');
    function pad(n) { return n < 10 ? '0'+n : ''+n; }
    function tick() {
        var diff = ts - Math.floor(Date.now()/1000);
        if (!el) return;
        if (diff <= 0) { el.textContent = 'Expired'; el.style.color = '#EF4444'; return; }
        var h = Math.floor(diff/3600), m = Math.floor((diff%3600)/60), s = diff%60;
        el.textContent = (h>0 ? h+'h ' : '')+pad(m)+'m '+pad(s)+'s';
        setTimeout(tick, 1000);
    }
    tick();
})();
</script>
@endif

{{-- Copy address --}}
@if ($isActive)
<script>
window.copyAddress = function() {
    var addr = document.getElementById('pay-address');
    var btn  = document.getElementById('copy-btn');
    if (!addr||!btn) return;
    navigator.clipboard.writeText(addr.textContent.trim()).then(function() {
        btn.textContent = 'Copied!';
        setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
    }).catch(function(){});
};
</script>
@endif

</x-dashboard-layout>
