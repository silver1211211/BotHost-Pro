<x-dashboard-layout title="Crypto Invoice" :no-flash="true">
<div class="mx-auto max-w-xl space-y-4">

    @php
        $isTemplate  = $invoice->type === \App\Models\PaymentInvoice::TYPE_TEMPLATE_PURCHASE;
        $isPaid      = $invoice->status === 'paid';
        $isFailed    = in_array($invoice->status, ['failed', 'expired', 'cancelled'], true);
        $isGenerated = filled($invoice->payment_address) && ! $isFailed;
        $isActive    = $isGenerated && ! $isPaid;

        $networkLabel = data_get($invoice->metadata, 'selected_payment_label')
            ?? (filled($invoice->pay_currency) && filled($invoice->network)
                ? $invoice->pay_currency.' '.$invoice->network
                : ($invoice->pay_currency ?? null));

        $statusColour = match(true) {
            $isPaid   => 'border-[#22C55E]/30 bg-[#22C55E]/10 text-[#22C55E]',
            $isFailed => 'border-[#EF4444]/30 bg-[#EF4444]/10 text-[#EF4444]',
            $isActive => 'border-[#F59E0B]/30 bg-[#F59E0B]/10 text-[#F59E0B]',
            default   => 'border-[#71717A]/30 bg-[#71717A]/10 text-[#71717A]',
        };

        $statusLabel = match($invoice->status) {
            'paid'               => 'Payment Confirmed',
            'waiting', 'pending' => 'Waiting for Payment',
            'paying'             => 'Payment Detected',
            'confirming'         => 'Confirming',
            'failed'             => 'Failed',
            'expired'            => 'Expired',
            'cancelled'          => 'Cancelled',
            default              => 'Not Generated',
        };
    @endphp

    {{-- Back link --}}
    @if ($isTemplate && ! $isPaid)
        <button id="back-btn" class="inline-flex items-center gap-1.5 text-sm text-[#71717A] transition hover:text-[#A1A1AA]">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back
        </button>
    @else
        <a href="{{ $product['return_url'] }}" class="inline-flex items-center gap-1.5 text-sm text-[#71717A] transition hover:text-[#A1A1AA]">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
    @endif

    {{-- Status / error flash (in-page only — layout flash is suppressed) --}}
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

    {{-- Main card --}}
    <div class="overflow-hidden rounded-2xl border border-[#27213D] bg-[#0F0D1A]">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-[#27213D] px-5 py-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-[#71717A]">{{ $product['type'] }}</p>
                <h1 class="mt-0.5 text-lg font-black leading-tight">{{ $product['name'] }}</h1>
            </div>
            <span class="rounded-full border px-3 py-1 text-xs font-black {{ $statusColour }}">{{ $statusLabel }}</span>
        </div>


        {{-- ══════════════════════════════════════════ --}}
        {{-- STATE 1 — Not generated yet                --}}
        {{-- ══════════════════════════════════════════ --}}
        @if (! $isGenerated && ! $isPaid)
        <div class="p-5 space-y-4">

            <div class="rounded-xl border border-[#27213D] bg-[#090713] px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A]">Amount Due</p>
                <p class="mt-0.5 text-2xl font-black text-white">{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}</p>
                <p class="mt-2 text-[10px] text-[#4D4868]">Invoice ID: <span class="font-mono font-bold text-[#71717A]">#{{ $invoice->id }}</span></p>
            </div>

            @if ($isFailed)
                <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#EF4444]">
                    This invoice {{ $invoice->status }}. Generate a new one below.
                </div>
            @endif

            <form method="POST" action="{{ route('dashboard.payments.generate', $invoice) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-[#71717A]">Payment Network <span class="text-[#EF4444]">*</span></label>
                    <select name="pay_currency" required
                            class="w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white focus:border-[#8B5CF6]/50 focus:outline-none">
                        <option value="" disabled selected>Select Network</option>
                        @foreach ($currencyOptions as $value => $label)
                            <option value="{{ $value }}" @selected(filled($invoice->pay_currency) && $invoice->pay_currency === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button class="rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-6 py-2.5 text-sm font-black text-white shadow-[0_0_18px_rgba(139,92,246,0.3)] transition hover:shadow-[0_0_26px_rgba(139,92,246,0.45)]">
                        {{ $isFailed ? 'Generate New Invoice' : 'Generate Invoice' }}
                    </button>
                    @if ($isTemplate)
                        <button type="button" id="back-btn-form" class="rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">Return</button>
                    @else
                        <a href="{{ $product['return_url'] }}" class="rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">Return</a>
                    @endif
                </div>
            </form>
        </div>


        {{-- ══════════════════════════════════════════ --}}
        {{-- STATE 2 — Active, awaiting payment         --}}
        {{-- ══════════════════════════════════════════ --}}
        @elseif ($isActive)
        <div class="p-5 space-y-4">

            {{-- Stats row --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A]">Amount Due</p>
                    <p class="mt-0.5 text-xl font-black text-white">{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}</p>
                </div>
                @if (filled($invoice->pay_amount))
                <div class="rounded-xl border border-[#8B5CF6]/30 bg-[#8B5CF6]/8 px-3 py-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#8B5CF6]">Pay Amount</p>
                    <p class="mt-0.5 text-lg font-black text-white leading-tight">{{ $invoice->pay_amount }}<span class="ml-1 text-xs font-bold text-[#A1A1AA]">{{ $networkLabel ?? $invoice->pay_currency }}</span></p>
                </div>
                @else
                <div class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A]">Network</p>
                    <p class="mt-0.5 text-base font-black text-white">{{ $networkLabel ?? $invoice->pay_currency ?? 'Crypto' }}</p>
                </div>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3">
                @if (filled($invoice->pay_amount))
                <div class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A]">Network</p>
                    <p class="mt-0.5 text-base font-black text-white">{{ $networkLabel ?? $invoice->pay_currency ?? 'Crypto' }}</p>
                </div>
                @endif
                @if ($invoice->expires_at)
                <div class="rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3 {{ ! filled($invoice->pay_amount) ? 'col-span-2 sm:col-span-1' : '' }}">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A]">Expires In</p>
                    <p class="mt-0.5 text-base font-black text-[#F59E0B]" id="expiry-timer">{{ $invoice->expires_at->diffForHumans() }}</p>
                </div>
                @endif
            </div>

            {{-- Address + QR side-by-side on md --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-start">

                {{-- Payment address (takes more space) --}}
                <div class="flex-1 rounded-xl border border-[#27213D] bg-[#090713] p-4 space-y-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A]">Payment Address</p>
                    @if (filled($invoice->pay_amount))
                        <p class="text-xs text-[#A1A1AA]">Send exactly <span class="font-black text-white">{{ $invoice->pay_amount }} {{ $networkLabel ?? $invoice->pay_currency }}</span> in one transaction.</p>
                    @else
                        <p class="text-xs text-[#A1A1AA]">Send <span class="font-black text-white">{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}</span> worth of <span class="font-bold text-white">{{ $networkLabel ?? $invoice->pay_currency }}</span>.</p>
                    @endif
                    <div class="flex items-center gap-2 rounded-xl border border-[#8B5CF6]/20 bg-[#0F0D1A] px-3 py-2.5">
                        <code id="pay-address" class="flex-1 break-all font-mono text-sm font-bold leading-snug text-white">{{ $invoice->payment_address }}</code>
                        <button type="button" id="copy-btn" onclick="copyAddress()"
                                class="shrink-0 rounded-lg border border-[#27213D] bg-[#090713] px-3 py-1.5 text-xs font-bold text-[#A1A1AA] transition hover:border-[#8B5CF6]/40 hover:text-white">
                            Copy
                        </button>
                    </div>
                    <p class="text-[11px] text-[#F59E0B]">Unique address — send in a single transaction.</p>
                    <p class="mt-1 text-[11px] leading-snug text-[#EF4444]">⚠ Only send <strong>{{ $networkLabel ?? $invoice->pay_currency }}</strong>. Payments sent in any other currency or network are permanently lost and cannot be refunded.</p>
                    @if (filled($invoice->track_id))
                        <p class="text-[10px] text-[#4D4868]">Ref: <span class="font-mono">{{ $invoice->track_id }}</span></p>
                    @endif
                </div>

                {{-- QR code --}}
                @if (filled($invoice->qr_code))
                <div class="flex shrink-0 flex-col items-center gap-2 rounded-xl border border-[#27213D] bg-[#090713] p-4 md:w-44">
                    <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A]">Scan to Pay</p>
                    <div class="rounded-xl bg-white p-1.5">
                        <img src="{{ $invoice->qr_code }}" alt="QR"
                             class="h-32 w-32 rounded-lg md:h-28 md:w-28"
                             onerror="this.parentElement.style.display='none'">
                    </div>
                    <p class="text-center text-[10px] text-[#71717A]">Scan with wallet</p>
                </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap items-center gap-2 pt-1">
                <form method="POST" action="{{ route('dashboard.payments.check', $invoice) }}">
                    @csrf
                    <button class="rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white shadow-[0_0_18px_rgba(139,92,246,0.3)] transition hover:shadow-[0_0_26px_rgba(139,92,246,0.45)]">
                        Check Payment Status
                    </button>
                </form>
                @if ($isTemplate)
                    <button type="button" id="back-btn-active" class="rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">Return</button>
                @else
                    <a href="{{ $product['return_url'] }}" class="rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">Return</a>
                @endif
            </div>

            <p class="text-xs text-[#4D4868]">Status updates automatically every 12 seconds or click <strong class="text-[#71717A]">Check Payment Status</strong> manually.</p>
        </div>


        {{-- ══════════════════════════════════════════ --}}
        {{-- STATE 3 — Paid                             --}}
        {{-- ══════════════════════════════════════════ --}}
        @elseif ($isPaid)
        <div class="p-5 space-y-4 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#22C55E]/15 shadow-[0_0_28px_rgba(34,197,94,0.25)]">
                <svg class="h-7 w-7 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-black text-[#22C55E]">Payment Confirmed</p>
                <p class="mt-1 text-sm text-[#71717A]">
                    {{ $isTemplate ? 'Your template has been unlocked and is ready to import.' : 'Your plan has been upgraded successfully.' }}
                </p>
            </div>
            <dl class="mx-auto grid max-w-xs gap-2 text-left text-sm">
                <div class="flex justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2">
                    <dt class="text-[#71717A]">Amount paid</dt>
                    <dd class="font-bold">{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}</dd>
                </div>
                @if (filled($invoice->pay_amount))
                <div class="flex justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2">
                    <dt class="text-[#71717A]">Paid in crypto</dt>
                    <dd class="font-bold">{{ $invoice->pay_amount }} {{ $networkLabel ?? $invoice->pay_currency }}</dd>
                </div>
                @endif
                @if ($invoice->paid_at)
                <div class="flex justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2">
                    <dt class="text-[#71717A]">Confirmed at</dt>
                    <dd class="font-bold text-xs">{{ $invoice->paid_at->toDayDateTimeString() }}</dd>
                </div>
                @endif
            </dl>
            <div class="flex flex-wrap justify-center gap-2">
                @if ($isTemplate && $reference)
                    <a href="{{ route('dashboard.templates.show', $reference) }}"
                       class="rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white">
                        Install Template
                    </a>
                    <a href="{{ route('dashboard.templates.purchased') }}"
                       class="rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">
                        My Templates
                    </a>
                @else
                    <a href="{{ $product['return_url'] }}"
                       class="rounded-xl bg-gradient-to-r from-[#22C55E] to-[#16A34A] px-5 py-2.5 text-sm font-black text-white">
                        Continue
                    </a>
                @endif
            </div>
        </div>


        {{-- ══════════════════════════════════════════ --}}
        {{-- STATE 4 — Failed / Expired / Cancelled     --}}
        {{-- ══════════════════════════════════════════ --}}
        @else
        <div class="p-5 space-y-4">
            <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#EF4444]">
                Invoice {{ $statusLabel }}. Generate a new crypto invoice below.
            </div>
            <div class="rounded-xl border border-[#27213D] bg-[#090713] px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-wide text-[#71717A]">Amount Due</p>
                <p class="mt-0.5 text-2xl font-black text-white">{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}</p>
                <p class="mt-2 text-[10px] text-[#4D4868]">Invoice #{{ $invoice->id }}</p>
            </div>
            <form method="POST" action="{{ route('dashboard.payments.generate', $invoice) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-[#71717A]">Payment Network <span class="text-[#EF4444]">*</span></label>
                    <select name="pay_currency" required
                            class="w-full rounded-xl border border-[#27213D] bg-[#090713] px-3 py-2.5 text-sm text-white focus:border-[#8B5CF6]/50 focus:outline-none">
                        <option value="" disabled selected>Select Network</option>
                        @foreach ($currencyOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button class="rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-6 py-2.5 text-sm font-black text-white shadow-[0_0_18px_rgba(139,92,246,0.3)] transition hover:shadow-[0_0_26px_rgba(139,92,246,0.45)]">
                        Generate New Invoice
                    </button>
                    @if ($isTemplate)
                        <button type="button" id="back-btn-state4" class="rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">Return</button>
                    @else
                        <a href="{{ $product['return_url'] }}" class="rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">Return</a>
                    @endif
                </div>
            </form>
        </div>
        @endif

    </div>{{-- /main card --}}

</div>


{{-- ════════════════════════════════════════════ --}}
{{-- TEMPLATE BACK MODAL                         --}}
{{-- ════════════════════════════════════════════ --}}
@if ($isTemplate && ! $isPaid)
<div id="back-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 space-y-3 shadow-2xl">
        <div>
            <h2 class="text-base font-black">Leave Payment Page?</h2>
            <p class="mt-0.5 text-sm text-[#71717A]">Your invoice is still active. What would you like to do?</p>
        </div>
        <form method="POST" action="{{ route('dashboard.payments.keep-active', $invoice) }}">
            @csrf
            <button class="w-full rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-3 text-sm font-black text-white text-left shadow-[0_0_18px_rgba(139,92,246,0.2)]">
                Keep Payment Active
                <span class="block text-xs font-normal text-[#C4B5FD]">Return to marketplace. Invoice stays open.</span>
            </button>
        </form>
        <form method="POST" action="{{ route('dashboard.payments.cancel', $invoice) }}">
            @csrf
            <button class="w-full rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/8 px-5 py-3 text-sm font-bold text-[#EF4444] text-left transition hover:bg-[#EF4444]/15">
                Cancel Purchase
                <span class="block text-xs font-normal text-[#EF4444]/70">Cancel this invoice and return.</span>
            </button>
        </form>
        <button type="button" onclick="closeBackModal()" class="w-full rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">
            Stay Here
        </button>
    </div>
</div>
@endif


{{-- ════════════════════════════════════════════ --}}
{{-- SUCCESS POPUP                               --}}
{{-- ════════════════════════════════════════════ --}}
<div id="success-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-2xl border border-[#22C55E]/20 bg-[#0F0D1A] p-5 space-y-4 shadow-2xl text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#22C55E]/15 shadow-[0_0_22px_rgba(34,197,94,0.25)]">
            <svg class="h-6 w-6 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
            </svg>
        </div>
        @if ($isPaid)
            @if ($isTemplate && $reference)
                <div>
                    <p class="text-lg font-black text-[#22C55E]">Template Unlocked!</p>
                    <p class="mt-0.5 text-sm font-bold text-white">{{ $reference->name }}</p>
                    <p class="mt-1 text-xs text-[#71717A]">Ready to import into any of your bots.</p>
                </div>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('dashboard.templates.show', $reference) }}" class="w-full rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white">Install Template</a>
                    <a href="{{ route('dashboard.templates.purchased') }}" class="w-full rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">View My Templates</a>
                </div>
            @else
                <div>
                    <p class="text-lg font-black text-[#22C55E]">Plan Upgraded!</p>
                    @if ($reference)
                        <p class="mt-0.5 text-sm font-bold text-white">{{ $reference->name }}</p>
                        @if ($reference->features)
                            <ul class="mt-2 space-y-1 text-left">
                                @foreach ($reference->features as $feature)
                                    <li class="flex items-center gap-2 text-xs text-[#A1A1AA]">
                                        <svg class="h-3 w-3 shrink-0 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endif
                </div>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('dashboard') }}" class="w-full rounded-xl bg-gradient-to-r from-[#22C55E] to-[#16A34A] px-5 py-2.5 text-sm font-black text-white">Go to Dashboard</a>
                    <button onclick="closeSuccessModal()" class="w-full rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-white">Close</button>
                </div>
            @endif
        @else
            <div>
                <p class="text-lg font-black text-[#22C55E]" id="popup-title">Payment Confirmed!</p>
                <p class="mt-1 text-sm text-[#71717A]" id="popup-subtitle"></p>
            </div>
            <div class="flex flex-col gap-2" id="popup-actions"></div>
        @endif
    </div>
</div>


{{-- ════════════════════════════════════════════ --}}
{{-- JAVASCRIPT                                  --}}
{{-- ════════════════════════════════════════════ --}}
<script>
(function () {

    // ── Back modal (template invoices only) ──────────
    @if ($isTemplate && ! $isPaid)
    var backModal = document.getElementById('back-modal');
    function openBackModal()  { backModal.classList.replace('hidden', 'flex'); }
    function closeBackModal() { backModal.classList.replace('flex', 'hidden'); }
    window.closeBackModal = closeBackModal;

    ['back-btn','back-btn-form','back-btn-active','back-btn-state4'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', openBackModal);
    });
    backModal.addEventListener('click', function(e) { if (e.target === backModal) closeBackModal(); });
    @endif

    // ── Success modal ─────────────────────────────────
    var successModal = document.getElementById('success-modal');
    function openSuccessModal()  { successModal.classList.replace('hidden', 'flex'); }
    function closeSuccessModal() { successModal.classList.replace('flex', 'hidden'); }
    window.closeSuccessModal = closeSuccessModal;
    successModal.addEventListener('click', function(e) { if (e.target === successModal) closeSuccessModal(); });

    @if ($isPaid && session('just_paid'))
    openSuccessModal();
    @endif

    // ── Expiry countdown ──────────────────────────────
    @if ($isActive && $invoice->expires_at)
    var expiryTs = {{ $invoice->expires_at->timestamp }};
    var timerEl  = document.getElementById('expiry-timer');
    function pad(n) { return n < 10 ? '0'+n : ''+n; }
    function tickExpiry() {
        var diff = expiryTs - Math.floor(Date.now()/1000);
        if (!timerEl) return;
        if (diff <= 0) { timerEl.textContent = 'Expired'; timerEl.style.color = '#EF4444'; return; }
        var h = Math.floor(diff/3600), m = Math.floor((diff%3600)/60), s = diff%60;
        timerEl.textContent = (h>0 ? h+'h ' : '')+pad(m)+'m '+pad(s)+'s';
        setTimeout(tickExpiry, 1000);
    }
    tickExpiry();
    @endif

    // ── Copy address ──────────────────────────────────
    @if ($isActive)
    window.copyAddress = function() {
        var addr = document.getElementById('pay-address');
        var btn  = document.getElementById('copy-btn');
        if (!addr||!btn) return;
        navigator.clipboard.writeText(addr.textContent.trim()).then(function() {
            btn.textContent = 'Copied!';
            setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
        }).catch(function(){});
    };
    @endif

    // ── Polling every 12 s ────────────────────────────
    @if ($isActive)
    var pollUrl    = '{{ route('dashboard.payments.poll', $invoice) }}';
    var isTemplate = {{ $isTemplate ? 'true' : 'false' }};
    var pollTimer;

    function showPaidPopup() {
        var t = document.getElementById('popup-title');
        var s = document.getElementById('popup-subtitle');
        var a = document.getElementById('popup-actions');
        if (!t) { openSuccessModal(); return; }
        if (isTemplate) {
            t.textContent = 'Template Unlocked!';
            s.textContent = 'Your template is ready to import.';
            a.innerHTML   = '<a href="{{ $product['return_url'] }}" class="w-full rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-2.5 text-sm font-black text-white">Install Template</a>' +
                            '<a href="{{ route('dashboard.templates.purchased') }}" class="w-full rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA]">My Templates</a>';
        } else {
            t.textContent = 'Plan Upgraded!';
            s.textContent = 'Your account has been upgraded.';
            a.innerHTML   = '<a href="{{ route('dashboard') }}" class="w-full rounded-xl bg-gradient-to-r from-[#22C55E] to-[#16A34A] px-5 py-2.5 text-sm font-black text-white">Go to Dashboard</a>' +
                            '<button onclick="closeSuccessModal()" class="w-full rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#A1A1AA]">Close</button>';
        }
        openSuccessModal();
    }

    function pollStatus() {
        fetch(pollUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.paid)    { clearInterval(pollTimer); showPaidPopup(); }
                else if (d.expired) { clearInterval(pollTimer); location.reload(); }
            })
            .catch(function(){});
    }

    pollTimer = setInterval(pollStatus, 12000);
    @endif

})();
</script>

</x-dashboard-layout>
