<x-dashboard-layout title="Webhook Documentation">

    @php
        $tocSections = [
            'overview'         => 'Overview',
            'incoming-endpoint'=> 'Incoming Endpoint',
            'how-it-works'     => 'How It Works',
            'request-method'   => 'Request Method',
            'headers'          => 'Headers',
            'example-payload'  => 'Example Payload',
            'nodejs-example'   => 'Node.js Example',
            'testing'          => 'Testing',
            'delivery-logs'    => 'Delivery Logs',
            'security-notes'   => 'Security Notes',
            'troubleshooting'  => 'Troubleshooting',
        ];
    @endphp

    <div
        x-data="{ active: 'overview' }"
        x-init="
            const obs = new IntersectionObserver((entries) => {
                entries.forEach(e => { if (e.isIntersecting) active = e.target.id; });
            }, { rootMargin: '-15% 0px -75% 0px', threshold: 0 });
            document.querySelectorAll('section[id]').forEach(s => obs.observe(s));
        "
    >

        {{-- ── Page header ── --}}
        <div class="mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-[#8B5CF6]/30 bg-[#8B5CF6]/10 text-[#8B5CF6]">
                    <svg style="height:18px;width:18px" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                </div>
                <div>
                    <h1 class="text-lg font-black text-[#F8FAFC] sm:text-xl">Custom Webhook Documentation</h1>
                    <p class="text-xs text-[#94A3B8] sm:text-sm">Learn how to receive external POST callbacks into your bot workspace. All endpoints use HTTPS only.</p>
                </div>
            </div>
        </div>

        {{-- ── Mobile TOC: sticky horizontal pill-tabs ── --}}
        <div class="sticky top-[60px] z-20 -mx-4 mb-5 border-b border-[#1B172B] bg-[#05040A]/95 px-4 pb-3 pt-2 backdrop-blur-xl sm:-mx-6 sm:px-6 lg:hidden">
            <div class="flex gap-1.5 overflow-x-auto" style="scrollbar-width:none;-ms-overflow-style:none">
                @foreach ($tocSections as $id => $label)
                    <a
                        href="#{{ $id }}"
                        :class="active === '{{ $id }}'
                            ? 'border-[#8B5CF6] bg-[#8B5CF6]/12 text-white'
                            : 'border-[#27213D] bg-[#0F0D1A] text-[#94A3B8] hover:border-[#8B5CF6]/40 hover:text-[#8B5CF6]'"
                        class="shrink-0 rounded-lg border px-3 py-1.5 text-[11px] font-black transition"
                        @click="active = '{{ $id }}'"
                    >{{ $label }}</a>
                @endforeach
            </div>
        </div>

        {{-- ── Two-column layout ── --}}
        <div class="flex gap-6 lg:gap-8 items-start">

            {{-- ── Sticky sidebar — desktop only, own scroll, never moves with content ── --}}
            <aside class="hidden lg:flex lg:flex-col shrink-0" style="width:192px">
                <div
                    class="sticky top-[68px] rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-3 overflow-y-auto"
                    style="max-height:calc(100vh - 84px)"
                >
                    <p class="mb-2.5 px-2 text-[9px] font-black uppercase tracking-[0.18em] text-[#6B6890]">Contents</p>
                    <nav class="space-y-0.5">
                        @foreach ($tocSections as $id => $label)
                            <a
                                href="#{{ $id }}"
                                @click="active = '{{ $id }}'"
                                :class="active === '{{ $id }}'
                                    ? 'bg-[#8B5CF6]/12 text-white border-l-2 border-[#8B5CF6] pl-[9px]'
                                    : 'text-[#94A3B8] pl-2.5 hover:bg-[#151225] hover:text-[#C4C0D8]'"
                                class="flex items-center gap-2 rounded-lg pr-2.5 py-2 text-xs font-semibold transition-all duration-150"
                            >
                                <span
                                    :class="active === '{{ $id }}' ? 'bg-[#8B5CF6] shadow-[0_0_6px_rgba(139,92,246,0.7)]' : 'bg-[#3D3657]'"
                                    class="h-1 w-1 shrink-0 rounded-full transition-all"
                                ></span>
                                {{ $label }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            {{-- ── Main content — page-level scroll, independent of sidebar ── --}}
            <div class="min-w-0 flex-1 space-y-6 sm:space-y-10">

                {{-- ────────────────── 1. OVERVIEW ────────────────── --}}
                <section id="overview" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">1</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">Overview</h2>
                        </div>
                        <p class="text-sm leading-relaxed text-[#A1A1AA]">
                            <strong class="text-[#F8FAFC]">Custom Webhook</strong> is an incoming endpoint that allows external platforms to send POST callbacks directly into your bot workspace on BotHost Pro.
                        </p>
                        <p class="mt-3 text-sm leading-relaxed text-[#A1A1AA]">
                            This means you can connect any external service — such as a payment provider, form builder, automation tool, or your own server — and have it send data into your bot environment automatically.
                        </p>
                        <div class="mt-4 flex items-start gap-3 rounded-xl border border-[#8B5CF6]/20 bg-[#8B5CF6]/8 px-4 py-3">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                            <p class="text-xs leading-relaxed text-[#A1A1AA]">
                                The webhook endpoint <strong class="text-[#F8FAFC]">receives</strong> data from external platforms. BotHost Pro does not push internal platform events to your endpoint.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- ────────────────── 2. INCOMING ENDPOINT ────────────────── --}}
                <section id="incoming-endpoint" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">2</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">Incoming Endpoint</h2>
                        </div>
                        <p class="text-sm leading-relaxed text-[#A1A1AA]">Each bot has its own unique incoming webhook endpoint. This URL is where external platforms send their POST requests.</p>
                        <p class="mt-3 text-sm leading-relaxed text-[#A1A1AA]">Your endpoint follows this format:</p>
                        <div class="mt-3 rounded-xl border border-[#1B172B] bg-[#0B0918] px-4 py-3">
                            <p class="break-all font-mono text-xs text-[#8B5CF6]">https://your-domain.com/webhooks/bot/{bot_id}/{secret}</p>
                        </div>
                        <p class="mt-4 text-sm leading-relaxed text-[#A1A1AA]">To find your endpoint:</p>
                        <ol class="mt-2 space-y-1.5 text-sm text-[#A1A1AA]">
                            <li class="flex items-start gap-2"><span class="mt-0.5 shrink-0 text-[#52525B]">1.</span> Open <strong class="text-[#F8FAFC]">My Bots</strong> and select your bot.</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 shrink-0 text-[#52525B]">2.</span> Navigate to the <strong class="text-[#F8FAFC]">Settings</strong> tab.</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 shrink-0 text-[#52525B]">3.</span> Scroll to the <strong class="text-[#F8FAFC]">Custom Webhook</strong> section.</li>
                            <li class="flex items-start gap-2"><span class="mt-0.5 shrink-0 text-[#52525B]">4.</span> Click <strong class="text-[#F8FAFC]">Generate</strong> to create your endpoint, then <strong class="text-[#F8FAFC]">Copy Endpoint</strong>.</li>
                        </ol>
                        <div class="mt-4 flex items-start gap-3 rounded-xl border border-[#F59E0B]/20 bg-[#F59E0B]/8 px-4 py-3">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                            <p class="text-xs leading-relaxed text-[#A1A1AA]">
                                Keep your endpoint URL <strong class="text-[#F8FAFC]">private</strong>. Do not share it publicly. If your endpoint is exposed, use <strong class="text-[#F8FAFC]">Regenerate</strong> to create a new one immediately.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- ────────────────── 3. HOW IT WORKS ────────────────── --}}
                <section id="how-it-works" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">3</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">How It Works</h2>
                        </div>
                        <p class="text-sm leading-relaxed text-[#A1A1AA]">Follow these steps to receive external callbacks into your bot:</p>
                        <div class="mt-4 space-y-3">
                            @foreach([
                                ['Step 1', 'Open your bot settings', 'Go to My Bots, select a bot, and open the Settings tab.'],
                                ['Step 2', 'Copy your Incoming Webhook Endpoint', 'In the Custom Webhook section, click Generate then Copy Endpoint.'],
                                ['Step 3', 'Paste into the external platform', 'In your external service (payment provider, form, automation tool), paste the endpoint URL into the webhook or callback URL field.'],
                                ['Step 4', 'Send a test request', 'Use the Test Webhook button in settings to verify connectivity, or trigger a test event from the external platform.'],
                                ['Step 5', 'Check Delivery Logs', 'Incoming callbacks appear in the Delivery Logs section inside your bot settings.'],
                            ] as [$step, $title, $desc])
                                <div class="flex items-start gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] px-4 py-3">
                                    <span class="mt-0.5 shrink-0 rounded-lg border border-[#27213D] bg-[#151225] px-2 py-0.5 text-[10px] font-black text-[#52525B]">{{ $step }}</span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-black text-[#A1A1AA]">{{ $title }}</p>
                                        <p class="mt-0.5 text-xs text-[#94A3B8]">{{ $desc }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- ────────────────── 4. REQUEST METHOD ────────────────── --}}
                <section id="request-method" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">4</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">Request Method</h2>
                        </div>
                        <p class="text-sm leading-relaxed text-[#A1A1AA]">Your webhook endpoint accepts requests with the following configuration:</p>
                        <div class="mt-4 overflow-x-auto rounded-xl border border-[#1B172B]">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b border-[#1B172B] bg-[#0B0918]">
                                        <th class="px-4 py-2.5 text-left font-black text-[#52525B]">Property</th>
                                        <th class="px-4 py-2.5 text-left font-black text-[#52525B]">Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#1B172B]">
                                    <tr class="bg-[#0F0D1A]">
                                        <td class="px-4 py-2.5 font-mono font-black text-[#94A3B8]">Method</td>
                                        <td class="px-4 py-2.5"><span class="rounded-full border border-[#22C55E]/30 bg-[#22C55E]/10 px-2 py-0.5 font-mono text-[10px] font-black text-[#22C55E]">POST</span></td>
                                    </tr>
                                    <tr class="bg-[#0F0D1A]">
                                        <td class="px-4 py-2.5 font-mono font-black text-[#94A3B8]">Content-Type</td>
                                        <td class="px-4 py-2.5 font-mono text-[#A1A1AA]">application/json</td>
                                    </tr>
                                    <tr class="bg-[#0F0D1A]">
                                        <td class="px-4 py-2.5 font-mono font-black text-[#94A3B8]">Body</td>
                                        <td class="px-4 py-2.5 font-mono text-[#A1A1AA]">JSON object</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                {{-- ────────────────── 5. HEADERS ────────────────── --}}
                <section id="headers" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">5</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">Headers</h2>
                        </div>
                        <p class="text-sm leading-relaxed text-[#A1A1AA]">When sending a request to your webhook endpoint, include the following headers:</p>
                        <div x-data="{ copied: false }" class="mt-4 overflow-hidden rounded-xl border border-[#1B172B] bg-[#0B0918]">
                            <div class="flex items-center justify-between border-b border-[#1B172B] px-4 py-2.5">
                                <span class="text-[10px] font-black uppercase tracking-widest text-[#6B6890]">HTTPS Headers</span>
                                <button @click="navigator.clipboard.writeText($refs.headersCode.textContent.trim()).then(()=>{copied=true;setTimeout(()=>copied=false,2000)})" class="text-[10px] font-black text-[#94A3B8] transition hover:text-[#A1A1AA]" x-text="copied ? '✓ Copied' : 'Copy'"></button>
                            </div>
                            <pre class="overflow-x-auto p-4 text-xs leading-relaxed"><code x-ref="headersCode" class="font-mono text-[#A1A1AA]">Content-Type: application/json
User-Agent: YourService/1.0</code></pre>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-[#94A3B8]">Some external platforms may include additional signature headers (e.g. <code class="rounded bg-[#151225] px-1 py-0.5 font-mono text-[#A1A1AA]">X-Signature</code>, <code class="rounded bg-[#151225] px-1 py-0.5 font-mono text-[#A1A1AA]">X-Webhook-Token</code>). These are passed through and will be available in future BotHost Pro automation features.</p>
                    </div>
                </section>

                {{-- ────────────────── 6. EXAMPLE PAYLOAD ────────────────── --}}
                <section id="example-payload" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">6</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">Example Payload</h2>
                        </div>
                        <p class="text-sm leading-relaxed text-[#A1A1AA]">Your external platform can send any JSON payload to the endpoint. Below is an example of what a payment provider might send:</p>
                        <div x-data="{ copied: false }" class="mt-4 overflow-hidden rounded-xl border border-[#1B172B] bg-[#0B0918]">
                            <div class="flex items-center justify-between border-b border-[#1B172B] px-4 py-2.5">
                                <span class="text-[10px] font-black uppercase tracking-widest text-[#6B6890]">JSON</span>
                                <button @click="navigator.clipboard.writeText($refs.payloadCode.textContent.trim()).then(()=>{copied=true;setTimeout(()=>copied=false,2000)})" class="text-[10px] font-black text-[#94A3B8] transition hover:text-[#A1A1AA]" x-text="copied ? '✓ Copied' : 'Copy'"></button>
                            </div>
                            <pre class="overflow-x-auto p-4 text-xs leading-relaxed"><code x-ref="payloadCode" class="font-mono text-[#A1A1AA]">{
  "event": "payment_success",
  "amount": 25,
  "currency": "USD",
  "reference": "INV-1001",
  "customer": {
    "name": "John Doe",
    "email": "john@example.com"
  }
}</code></pre>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-[#94A3B8]">This is an example external callback payload. The structure depends entirely on the external platform you are integrating with.</p>
                    </div>
                </section>

                {{-- ────────────────── 7. NODE.JS EXAMPLE ────────────────── --}}
                <section id="nodejs-example" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">7</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">Node.js Example</h2>
                        </div>
                        <p class="text-sm leading-relaxed text-[#A1A1AA]">Use this pattern to send a POST callback from a Node.js environment (such as a bot command or external service) to your BotHost Pro webhook endpoint.</p>

                        {{-- fetch example --}}
                        <p class="mt-4 mb-2 text-xs font-black uppercase tracking-widest text-[#52525B]">Using fetch (built-in Node.js 18+)</p>
                        <div x-data="{ copied: false }" class="overflow-hidden rounded-xl border border-[#1B172B] bg-[#0B0918]">
                            <div class="flex items-center justify-between border-b border-[#1B172B] px-4 py-2.5">
                                <span class="text-[10px] font-black uppercase tracking-widest text-[#6B6890]">JavaScript</span>
                                <button @click="navigator.clipboard.writeText($refs.fetchCode.textContent.trim()).then(()=>{copied=true;setTimeout(()=>copied=false,2000)})" class="text-[10px] font-black text-[#94A3B8] transition hover:text-[#A1A1AA]" x-text="copied ? '✓ Copied' : 'Copy'"></button>
                            </div>
                            <pre class="overflow-x-auto p-4 text-xs leading-relaxed"><code x-ref="fetchCode" class="font-mono text-[#A1A1AA]">const WEBHOOK_URL = "https://your-domain.com/webhooks/bot/{bot_id}/{secret}";

await fetch(WEBHOOK_URL, {
  method: "POST",
  headers: {
    "Content-Type": "application/json"
  },
  body: JSON.stringify({
    event: "payment_success",
    amount: 25,
    currency: "USD",
    reference: "INV-1001"
  })
});

console.log("Webhook delivered.");</code></pre>
                        </div>

                        {{-- axios example --}}
                        <p class="mt-5 mb-2 text-xs font-black uppercase tracking-widest text-[#52525B]">Using axios (popular alternative)</p>
                        <div x-data="{ copied: false }" class="overflow-hidden rounded-xl border border-[#1B172B] bg-[#0B0918]">
                            <div class="flex items-center justify-between border-b border-[#1B172B] px-4 py-2.5">
                                <span class="text-[10px] font-black uppercase tracking-widest text-[#6B6890]">JavaScript</span>
                                <button @click="navigator.clipboard.writeText($refs.axiosCode.textContent.trim()).then(()=>{copied=true;setTimeout(()=>copied=false,2000)})" class="text-[10px] font-black text-[#94A3B8] transition hover:text-[#A1A1AA]" x-text="copied ? '✓ Copied' : 'Copy'"></button>
                            </div>
                            <pre class="overflow-x-auto p-4 text-xs leading-relaxed"><code x-ref="axiosCode" class="font-mono text-[#A1A1AA]">const axios = require("axios");

const WEBHOOK_URL = "https://your-domain.com/webhooks/bot/{bot_id}/{secret}";

await axios.post(WEBHOOK_URL, {
  event: "payment_success",
  amount: 25,
  currency: "USD",
  reference: "INV-1001"
}, {
  headers: { "Content-Type": "application/json" }
});

console.log("Webhook delivered.");</code></pre>
                        </div>

                        <p class="mt-3 text-xs leading-relaxed text-[#94A3B8]">Replace <code class="rounded bg-[#151225] px-1 py-0.5 font-mono text-[#A1A1AA]">YOUR_WEBHOOK_ENDPOINT</code> with the URL copied from your bot settings. The payload structure can be any valid JSON object.</p>
                    </div>
                </section>

                {{-- ────────────────── 8. TESTING ────────────────── --}}
                <section id="testing" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">8</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">Testing</h2>
                        </div>
                        <p class="text-sm leading-relaxed text-[#A1A1AA]">Use the <strong class="text-[#F8FAFC]">Test Webhook</strong> button inside your bot settings to verify that your endpoint is active.</p>
                        <div class="mt-4 space-y-3">
                            <div class="flex items-start gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] px-4 py-3">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                                <div>
                                    <p class="text-xs font-black text-[#A1A1AA]">What the test does</p>
                                    <p class="mt-0.5 text-xs text-[#94A3B8]">The test sends a sample POST request to your bot's incoming webhook endpoint and confirms that BotHost Pro receives and processes it. It does not test a third-party external platform.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] px-4 py-3">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#22C55E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                <div>
                                    <p class="text-xs font-black text-[#A1A1AA]">Expected result</p>
                                    <p class="mt-0.5 text-xs text-[#94A3B8]">You should see a <strong class="text-[#22C55E]">Received</strong> response with an HTTP 200 status code. The test delivery also appears in the Delivery Logs section.</p>
                                </div>
                            </div>
                        </div>
                        <p class="mt-4 text-xs leading-relaxed text-[#94A3B8]">To test a full external integration, trigger a real event from your external platform (e.g. a payment in test mode) and watch for it in Delivery Logs.</p>
                    </div>
                </section>

                {{-- ────────────────── 9. DELIVERY LOGS ────────────────── --}}
                <section id="delivery-logs" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">9</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">Delivery Logs</h2>
                        </div>
                        <p class="text-sm leading-relaxed text-[#A1A1AA]">Delivery Logs appear in your bot settings under the <strong class="text-[#F8FAFC]">Custom Webhook</strong> section. They record each incoming webhook request received by your endpoint.</p>
                        <div class="mt-4 overflow-x-auto rounded-xl border border-[#1B172B]">
                            <table class="w-full min-w-[400px] text-xs">
                                <thead>
                                    <tr class="border-b border-[#1B172B] bg-[#0B0918]">
                                        <th class="px-4 py-2.5 text-left font-black text-[#52525B]">Column</th>
                                        <th class="px-4 py-2.5 text-left font-black text-[#52525B]">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#1B172B]">
                                    @foreach([
                                        ['Event',    'The event identifier from the payload or "test.webhook" for manual tests.'],
                                        ['Status',   'Success, Failed, Pending, or Retrying.'],
                                        ['Status',   'HTTP status code returned by the endpoint handler.'],
                                        ['Duration', 'Time taken to process the request (milliseconds).'],
                                        ['Received', 'When the request was received (relative time).'],
                                    ] as [$col, $desc])
                                        <tr class="bg-[#0F0D1A]">
                                            <td class="px-4 py-2.5 font-mono font-black text-[#94A3B8]">{{ $col }}</td>
                                            <td class="px-4 py-2.5 text-[#A1A1AA]">{{ $desc }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                {{-- ────────────────── 10. SECURITY NOTES ────────────────── --}}
                <section id="security-notes" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">10</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">Security Notes</h2>
                        </div>
                        <div class="space-y-3">
                            @foreach([
                                ['Keep your endpoint private', 'Do not share your webhook URL publicly. It acts as a secret access point to your bot workspace.'],
                                ['Regenerate if exposed',      'If your endpoint URL is accidentally exposed, use the Regenerate button immediately to invalidate the old URL.'],
                                ['Avoid sensitive data in payloads', 'Do not include passwords, private keys, or API secrets inside webhook payloads. Send only the minimum data required.'],
                                ['Use platform signatures',    'When your external platform supports webhook signatures (e.g. HMAC headers), enable them to verify request authenticity. BotHost Pro will support signature verification in a future update.'],
                                ['HTTPS only',                 'Your BotHost Pro webhook endpoint is served over HTTPS. Ensure the external platform is configured to send HTTPS requests.'],
                            ] as [$title, $desc])
                                <div class="flex items-start gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] px-4 py-3">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                                    <div>
                                        <p class="text-xs font-black text-[#A1A1AA]">{{ $title }}</p>
                                        <p class="mt-0.5 text-xs text-[#94A3B8]">{{ $desc }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- ────────────────── 11. TROUBLESHOOTING ────────────────── --}}
                <section id="troubleshooting" class="scroll-mt-[110px] lg:scroll-mt-24">
                    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-[#8B5CF6]/15 text-[10px] font-black text-[#8B5CF6]">11</span>
                            <h2 class="text-base font-black text-[#F8FAFC]">Troubleshooting</h2>
                        </div>
                        <div class="space-y-3">
                            @php
                            $troubleshootingItems = [
                                ["Webhook not received",         "Check that the endpoint URL was copied correctly from bot settings. Ensure the external platform webhook/callback URL field contains the full URL including the secret segment."],
                                ["HTTP 404 response",             "The endpoint may have been disabled or regenerated. Go to bot settings and generate a new endpoint, then update the external platform with the new URL."],
                                ["Invalid JSON error",           "Ensure the request Content-Type header is set to application/json and the body is valid JSON. Use a JSON validator if needed."],
                                ["No entries in Delivery Logs",  "Try clicking the Test Webhook button to send a test request. If that succeeds but external requests are not appearing, double-check the external platform webhook configuration."],
                                ["External platform not notifying", "Confirm that the external platform webhook/callback URL is saved and enabled. Some platforms require domain verification or HTTPS before sending live requests."],
                                ["Test Webhook button shows an error", "Make sure you have clicked Generate first to create your endpoint. The test sends to the incoming endpoint and requires it to be active."],
                            ];
                        @endphp
                        @foreach($troubleshootingItems as [$problem, $solution])
                                <details class="group rounded-xl border border-[#1B172B] bg-[#0B0918] overflow-hidden">
                                    <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-xs font-black text-[#A1A1AA] transition hover:text-[#F8FAFC] select-none">
                                        {{ $problem }}
                                        <svg class="h-4 w-4 shrink-0 text-[#52525B] transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                                    </summary>
                                    <div class="border-t border-[#1B172B] px-4 py-3">
                                        <p class="text-xs leading-relaxed text-[#94A3B8]">{{ $solution }}</p>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- Back to settings link --}}
                <div class="pb-4">
                    <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 text-xs font-black text-[#94A3B8] transition hover:text-[#A1A1AA]">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                        Back to previous page
                    </a>
                </div>

            </div>{{-- /main content --}}
        </div>{{-- /two-column --}}
    </div>

</x-dashboard-layout>
