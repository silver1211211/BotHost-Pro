<x-admin-layout title="Payment Settings" subtitle="OxaPay White Label configuration">
    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/10 p-3 text-sm text-[#22C55E]">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 p-3 text-sm text-[#EF4444]">{{ $errors->first() }}</div>
        @endif

        {{-- OxaPay Merchant Config --}}
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-black text-white">OxaPay Merchant</h2>
                    <p class="mt-1 text-sm text-[#71717A]">Configure OxaPay White Label payments for template purchases and plan upgrades.</p>
                </div>
                <span class="rounded-full border px-3 py-1 text-xs font-black {{ $providerConfigured ? 'border-[#22C55E]/30 text-[#22C55E]' : 'border-[#F59E0B]/30 text-[#F59E0B]' }}">
                    {{ $providerConfigured ? 'Configured' : 'Not Configured' }}
                </span>
            </div>

            <form method="POST" action="{{ route('admin.deposits.settings.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')

                {{-- API Key --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-[#A1A1AA]">OxaPay Merchant API Key</label>
                    <input name="merchant_api_key" type="password" placeholder="{{ $maskedApiKey ?: 'Enter OxaPay merchant API key' }}"
                        class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-white placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                    @if($maskedApiKey)
                        <p class="text-xs text-[#71717A]">Saved key: <span class="font-mono text-[#A1A1AA]">{{ $maskedApiKey }}</span></p>
                    @endif
                    <p class="text-[11px] text-[#71717A]">Stored encrypted. Never displayed in full.</p>
                </div>

                {{-- Base URL --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-[#A1A1AA]">Base URL</label>
                    <input name="base_url" value="{{ old('base_url', $baseUrl) }}"
                        class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-white font-mono outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                </div>

                {{-- Invoice Lifetime --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-[#A1A1AA]">Invoice Lifetime (minutes)</label>
                    <input name="invoice_lifetime" type="number" min="15" max="2880"
                        value="{{ old('invoice_lifetime', $invoiceLifetime) }}"
                        class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-white outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                    <p class="text-[11px] text-[#71717A]">Min 15, max 2880 (48 hours). Default 60.</p>
                </div>

                {{-- Under Paid Coverage --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-[#A1A1AA]">Under Paid Coverage (%)</label>
                    <input name="under_paid_coverage" type="number" min="0" max="100" step="0.01"
                        value="{{ old('under_paid_coverage', $underPaidCoverage) }}"
                        placeholder="Leave empty to use OxaPay default"
                        class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-3.5 py-2.5 text-sm text-white placeholder-[#71717A] outline-none transition focus:border-[#8B5CF6]/60 focus:ring-1 focus:ring-[#8B5CF6]/20">
                    <p class="text-[11px] text-[#71717A]">Percentage of underpayment OxaPay will accept as paid.</p>
                </div>

                {{-- Toggles --}}
                <div class="space-y-2 rounded-xl border border-[#27213D] bg-[#11101C] p-4">
                    @foreach([
                        ['oxapay_enabled',  $enabled,       'Enable OxaPay',        'Allow crypto payment processing via OxaPay.'],
                        ['fee_paid_by_user', $feePaidByUser, 'User pays network fee', 'Pass the blockchain network fee to the payer.'],
                        ['sandbox',          $sandbox,       'Sandbox mode',          'Use OxaPay test environment. Disable for production.'],
                    ] as [$key, $checked, $label, $desc])
                    <label class="flex items-start gap-3 cursor-pointer py-1">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($checked)
                            class="mt-0.5 rounded border-[#27213D] bg-[#090713] text-[#8B5CF6] focus:ring-[#8B5CF6]/30">
                        <div>
                            <p class="text-sm font-semibold text-[#F8FAFC]">{{ $label }}</p>
                            <p class="text-[11px] text-[#71717A]">{{ $desc }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>

                @if($sandbox)
                    <div class="rounded-xl border border-[#F59E0B]/30 bg-[#F59E0B]/10 p-3 text-xs text-[#F59E0B]">Sandbox mode is ON. Invoices will use OxaPay test environment. Disable for production payments.</div>
                @endif

                <button class="w-full sm:w-auto rounded-xl bg-[#8B5CF6] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#7C3AED] transition">Save Payment Settings</button>
            </form>
        </div>

        {{-- Webhook URLs --}}
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <h3 class="font-black text-white">Webhook &amp; Callback URLs</h3>
            <p class="mt-1 text-xs text-[#71717A]">Public callback base: <span class="font-mono text-[#A1A1AA] break-all">{{ $publicCallbackBaseUrl }}</span></p>

            <div class="mt-4 space-y-4">
                <div>
                    <p class="text-xs font-bold uppercase text-[#71717A]">OxaPay Webhook URL</p>
                    <p class="text-xs text-[#71717A] mt-0.5 mb-1">Enter this URL in your OxaPay merchant dashboard as the callback URL.</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 break-all rounded-xl border border-[#27213D] bg-[#11101C] p-3 font-mono text-xs text-[#A1A1AA]">{{ $webhookUrl }}</code>
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $webhookUrl }}').then(()=>{this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)})"
                            class="shrink-0 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-3 py-2 text-xs font-bold text-[#A1A1AA] hover:text-white transition">Copy</button>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase text-[#71717A]">Return URL base (local)</p>
                    <p class="text-xs text-[#71717A] mt-0.5 mb-1">Used for payment return redirects. Uses APP_URL (local browser navigation).</p>
                    <code class="block break-all rounded-xl border border-[#27213D] bg-[#11101C] p-3 font-mono text-xs text-[#A1A1AA]">{{ rtrim(config('app.url'), '/') }}/dashboard/payments/{invoice}</code>
                </div>
            </div>

            <p class="mt-3 text-xs text-[#71717A]">Use the generic webhook URL above in your OxaPay dashboard. It handles both template purchases and plan upgrades.</p>
        </div>
    </div>
</x-admin-layout>
