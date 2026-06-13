<x-dashboard-layout title="Template Marketplace">
    <div class="mx-auto max-w-6xl space-y-3">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 rounded-2xl border border-[#27213D] bg-[#0F0D1A] px-4 py-3">
            <div>
                <h1 class="text-xl font-black leading-tight">Template Marketplace</h1>
                <p class="text-xs text-[#71717A]">Browse free and paid command templates.</p>
            </div>
            <a href="{{ route('dashboard.templates.purchased') }}"
               class="rounded-xl border border-[#22C55E]/35 bg-[#22C55E]/12 px-4 py-1.5 text-sm font-bold text-[#22C55E] transition hover:bg-[#22C55E]/20">
                Purchased
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] px-4 py-3">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-[1fr_1fr_auto_auto_auto]">
                <input name="search" value="{{ request('search') }}" placeholder="Search templates…"
                       class="col-span-2 rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2 text-sm text-white placeholder:text-[#4D4868] focus:border-[#8B5CF6]/50 focus:outline-none sm:col-span-2 lg:col-span-1">
                <div class="relative" x-data="{ open: false, val: '{{ request('category') }}', labels: @js(collect($categories)->mapWithKeys(fn($cnt, $cat) => [$cat => "$cat ($cnt)"])->all()), get label() { return this.val ? (this.labels[this.val] || this.val) : 'Category' } }" @click.away="open = false">
                    <input type="hidden" name="category" :value="val">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2 text-sm text-white transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/15' : ''">
                        <span x-text="label"></span>
                        <svg class="ml-2 h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                        <button type="button" @click="val = ''; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                            <svg :class="val === '' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            Category
                        </button>
                        @foreach($categories as $category => $count)
                        <button type="button" @click="val = '{{ $category }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $category }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                            <svg :class="val === '{{ $category }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            {{ $category }} ({{ $count }})
                        </button>
                        @endforeach
                    </div>
                </div>
                <div class="relative" x-data="{ open: false, val: '{{ request('level') }}', labels: @js(array_combine(\App\Models\BotTemplate::LEVELS, array_map('ucfirst', \App\Models\BotTemplate::LEVELS))), get label() { return this.val ? (this.labels[this.val] || this.val) : 'Level' } }" @click.away="open = false">
                    <input type="hidden" name="level" :value="val">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2 text-sm text-white transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/15' : ''">
                        <span x-text="label"></span>
                        <svg class="ml-2 h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                        <button type="button" @click="val = ''; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                            <svg :class="val === '' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            Level
                        </button>
                        @foreach(\App\Models\BotTemplate::LEVELS as $level)
                        <button type="button" @click="val = '{{ $level }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $level }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                            <svg :class="val === '{{ $level }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            {{ ucfirst($level) }}
                        </button>
                        @endforeach
                    </div>
                </div>
                <div class="relative" x-data="{ open: false, val: '{{ request('access_type') }}', labels: { '': 'All', free: 'Free', paid: 'Paid' }, get label() { return this.labels[this.val] || 'All' } }" @click.away="open = false">
                    <input type="hidden" name="access_type" :value="val">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-2 text-sm text-white transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/50 ring-1 ring-[#8B5CF6]/15' : ''">
                        <span x-text="label"></span>
                        <svg class="ml-2 h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                        @foreach (['' => 'All', 'free' => 'Free', 'paid' => 'Paid'] as $atv => $atl)
                        <button type="button" @click="val = '{{ $atv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $atv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                            <svg :class="val === '{{ $atv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            {{ $atl }}
                        </button>
                        @endforeach
                    </div>
                </div>
                <button class="col-span-2 rounded-xl bg-[#8B5CF6] px-5 py-2 text-sm font-black text-white transition hover:bg-[#7C3AED] sm:col-span-1 lg:col-span-1">
                    Filter
                </button>
            </div>
        </form>

        {{-- Template grid --}}
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($templates as $template)
                @php
                    $purchased      = in_array($template->id, $purchasedIds, true);
                    $pendingInvoice = $pendingInvoices->get($template->id);
                    $isFreeOrIncl   = $template->isFree() || $template->isIncludedFor(auth()->user());
                @endphp
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4"
                     data-card="{{ $template->id }}">

                    <div class="relative mb-3 w-full overflow-hidden rounded-xl" style="aspect-ratio:16/9">
                        @if($template->thumbnail_url)
                            <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }}"
                                 class="absolute inset-0 h-full w-full object-cover"
                                 onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="absolute inset-0 items-center justify-center bg-[#11101C] text-xs text-[#71717A]" style="display:none">No image</div>
                        @else
                            <div class="flex h-full w-full items-center justify-center border border-[#27213D] bg-[#11101C] text-xs text-[#71717A]">No image</div>
                        @endif
                    </div>

                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-black">{{ $template->name }}</h2>
                            <p class="mt-0.5 line-clamp-2 text-xs text-[#71717A]">{{ Str::limit($template->short_description ?: $template->description, 120) }}</p>
                        </div>
                        <span class="shrink-0 rounded-full border border-[#27213D] px-2 py-0.5 text-[11px] font-bold">{{ $template->formatted_price }}</span>
                    </div>

                    {{-- Meta badges --}}
                    <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] text-[#71717A]">
                        <span class="rounded border border-[#27213D] bg-[#11101C] px-1.5 py-0.5">{{ $template->category ?: 'General' }}</span>
                        <span class="rounded border border-[#27213D] bg-[#11101C] px-1.5 py-0.5">{{ ucfirst($template->level) }}</span>
                        <span class="rounded border border-[#27213D] bg-[#11101C] px-1.5 py-0.5">{{ $template->commands_count }} cmds</span>
                        <span class="rounded border border-[#27213D] bg-[#11101C] px-1.5 py-0.5">{{ $template->import_count }} imports</span>
                        @if($template->includedPlanLabel())
                            <span class="rounded border border-[#8B5CF6]/30 bg-[#8B5CF6]/10 px-1.5 py-0.5 text-[#8B5CF6]">{{ $template->includedPlanLabel() }}</span>
                        @endif
                        {{-- Status badge — updated by JS polling --}}
                        <span id="badge-{{ $template->id }}"
                              @if($purchased)
                                  class="rounded border border-[#22C55E]/30 bg-[#22C55E]/10 px-1.5 py-0.5 text-[#22C55E]"
                              @elseif($pendingInvoice)
                                  class="rounded border border-[#F59E0B]/30 bg-[#F59E0B]/10 px-1.5 py-0.5 text-[#F59E0B]"
                              @elseif($template->isPaid())
                                  class="rounded border border-[#27213D] bg-[#11101C] px-1.5 py-0.5"
                              @else
                                  class="hidden"
                              @endif>
                            @if($purchased)Unlocked
                            @elseif($pendingInvoice)Invoice Active
                            @elseif($template->isPaid())Locked
                            @endif
                        </span>
                    </div>

                    {{-- Action buttons — data attributes let JS rebuild without a reload --}}
                    <div class="mt-3 flex items-center justify-between gap-2">
                        <span class="text-sm font-black">{{ $template->formatted_price }}</span>
                        <div class="flex gap-2" id="actions-{{ $template->id }}"
                             data-template-id="{{ $template->id }}"
                             data-purchased="{{ $purchased ? '1' : '0' }}"
                             data-invoice-id="{{ $pendingInvoice?->id ?? '' }}"
                             data-is-free="{{ $isFreeOrIncl ? '1' : '0' }}"
                             data-is-paid="{{ $template->isPaid() ? '1' : '0' }}"
                             data-show-url="{{ route('dashboard.templates.show', $template) }}"
                             data-unlock-url="{{ route('dashboard.templates.unlock-free', $template) }}"
                             data-purchase-url="{{ route('dashboard.templates.crypto-invoice', $template) }}"
                             data-csrf="{{ csrf_token() }}">
                            <a href="{{ route('dashboard.templates.show', $template) }}"
                               class="rounded-xl border border-[#27213D] px-3 py-1.5 text-xs font-bold transition hover:border-[#3D3657] hover:text-white">
                                View Details
                            </a>
                            <span id="action-btn-{{ $template->id }}">
                                @if($purchased)
                                    <a href="{{ route('dashboard.templates.show', $template) }}"
                                       class="rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/10 px-3 py-1.5 text-xs font-bold text-[#22C55E] transition hover:bg-[#22C55E]/20">
                                        Install
                                    </a>
                                @elseif($isFreeOrIncl)
                                    <form method="POST" action="{{ route('dashboard.templates.unlock-free', $template) }}">
                                        @csrf
                                        <button class="rounded-xl bg-[#8B5CF6] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#7C3AED]">
                                            Unlock Free
                                        </button>
                                    </form>
                                @elseif($template->isPaid())
                                    @if($pendingInvoice)
                                        <a href="{{ route('dashboard.payments.show', $pendingInvoice) }}"
                                           class="rounded-xl bg-[#8B5CF6] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#7C3AED]">
                                            Continue Invoice
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('dashboard.templates.crypto-invoice', $template) }}">
                                            @csrf
                                            <input type="hidden" name="payment_method" value="crypto">
                                            <button class="rounded-xl bg-[#8B5CF6] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#7C3AED]">
                                                Purchase
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </span>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        {{ $templates->links() }}
    </div>

    {{-- Silent status polling --}}
    <script>
    (function () {
        var statusUrl = '{{ route('dashboard.templates.status') }}';
        var csrfToken = '{{ csrf_token() }}';
        var invoiceBase = '{{ url('/dashboard/payments') }}';

        // Collect all template IDs on this page
        var ids = Array.from(document.querySelectorAll('[data-template-id]'))
                       .map(function (el) { return el.dataset.templateId; });

        if (!ids.length) return;

        // Track last-known state so we only touch the DOM on change
        var state = {};
        ids.forEach(function (id) {
            var el = document.getElementById('actions-' + id);
            state[id] = {
                purchased:  el ? el.dataset.purchased  : '0',
                invoiceId:  el ? el.dataset.invoiceId  : '',
            };
        });

        function buildActionBtn(data, el) {
            var showUrl     = el.dataset.showUrl;
            var unlockUrl   = el.dataset.unlockUrl;
            var purchaseUrl = el.dataset.purchaseUrl;
            var isFree      = el.dataset.isFree === '1';
            var isPaid      = el.dataset.isPaid === '1';
            var invoiceUrl  = data.invoice_id ? invoiceBase + '/' + data.invoice_id : '';

            var cls  = 'rounded-xl px-3 py-1.5 text-xs font-bold transition';
            var purp = cls + ' bg-[#8B5CF6] text-white hover:bg-[#7C3AED]';

            if (data.purchased) {
                return '<a href="' + showUrl + '" class="' + cls + ' border border-[#22C55E]/30 bg-[#22C55E]/10 text-[#22C55E] hover:bg-[#22C55E]/20">Install</a>';
            }
            if (isFree) {
                return '<form method="POST" action="' + unlockUrl + '">'
                     + '<input type="hidden" name="_token" value="' + csrfToken + '">'
                     + '<button class="' + purp + '">Unlock Free</button></form>';
            }
            if (isPaid && invoiceUrl) {
                return '<a href="' + invoiceUrl + '" class="' + purp + '">Continue Invoice</a>';
            }
            if (isPaid) {
                return '<form method="POST" action="' + purchaseUrl + '">'
                     + '<input type="hidden" name="_token" value="' + csrfToken + '">'
                     + '<input type="hidden" name="payment_method" value="crypto">'
                     + '<button class="' + purp + '">Purchase</button></form>';
            }
            return '';
        }

        function updateBadge(id, purchased, invoiceId) {
            var badge = document.getElementById('badge-' + id);
            if (!badge) return;
            var base = 'rounded border px-1.5 py-0.5';
            if (purchased) {
                badge.className = base + ' border-[#22C55E]/30 bg-[#22C55E]/10 text-[#22C55E]';
                badge.textContent = 'Unlocked';
            } else if (invoiceId) {
                badge.className = base + ' border-[#F59E0B]/30 bg-[#F59E0B]/10 text-[#F59E0B]';
                badge.textContent = 'Invoice Active';
            }
        }

        function poll() {
            fetch(statusUrl + '?ids=' + ids.join(','), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                ids.forEach(function (id) {
                    var d   = data[id];
                    if (!d) return;
                    var old = state[id];
                    var newPurchased  = d.purchased  ? '1' : '0';
                    var newInvoiceId  = d.invoice_id ? String(d.invoice_id) : '';

                    if (newPurchased === old.purchased && newInvoiceId === old.invoiceId) return;

                    // State changed — update DOM silently
                    state[id] = { purchased: newPurchased, invoiceId: newInvoiceId };

                    var container = document.getElementById('actions-' + id);
                    var btnSlot   = document.getElementById('action-btn-' + id);
                    if (!container || !btnSlot) return;

                    container.dataset.purchased = newPurchased;
                    container.dataset.invoiceId  = newInvoiceId;

                    btnSlot.innerHTML = buildActionBtn(d, container);
                    updateBadge(id, d.purchased, d.invoice_id);
                });
            })
            .catch(function () {}); // silent on network error
        }

        setInterval(poll, 2000);
    })();
    </script>

</x-dashboard-layout>
