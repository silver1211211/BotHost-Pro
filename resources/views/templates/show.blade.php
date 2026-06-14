<x-dashboard-layout title="{{ $template->name }}">
    <div class="mx-auto max-w-4xl space-y-5">
        <a href="{{ route('dashboard.templates.index') }}" class="text-sm text-[#A1A1AA]">Back to marketplace</a>
        @if(session('status'))<div class="rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/10 p-3 text-sm text-[#22C55E]">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 p-3 text-sm text-[#EF4444] space-y-1">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            <div class="relative mb-5 w-full overflow-hidden rounded-xl" style="aspect-ratio:16/9">
                @if($template->thumbnail_url)
                    <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }}"
                         class="absolute inset-0 h-full w-full object-cover"
                         onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="absolute inset-0 items-center justify-center bg-[#11101C] text-sm text-[#94A3B8]" style="display:none">No image</div>
                @else
                    <div class="flex h-full w-full items-center justify-center border border-[#27213D] bg-[#11101C] text-sm text-[#94A3B8]">No image</div>
                @endif
            </div>
            <h1 class="text-2xl font-black">{{ $template->name }}</h1>
            <p class="mt-2 text-sm font-bold text-[#A1A1AA]">{{ $template->short_description }}</p>
            <p class="mt-2 text-sm text-[#A1A1AA]">{{ $template->description }}</p>
            <div class="mt-3 flex flex-wrap gap-2 text-xs text-[#94A3B8]"><span>{{ $template->category ?: 'General' }}</span><span>{{ ucfirst($template->level) }}</span><span>{{ $template->commands_count }} commands</span><span>{{ $template->import_count }} imports</span>@if($template->includedPlanLabel())<span>{{ $template->includedPlanLabel() }}</span>@endif</div>
            <p class="mt-3 text-lg font-black">{{ $template->formatted_price }}</p>
            @if($template->demo_url)<a href="{{ $template->demo_url }}" class="mt-3 inline-flex items-center gap-1.5 rounded-xl border border-[#38BDF8]/30 bg-[#38BDF8]/10 px-4 py-2 text-sm font-bold text-[#38BDF8] transition hover:bg-[#38BDF8]/20 hover:text-white" rel="noopener noreferrer" target="_blank">
    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
    View Demo Bot
</a>@endif
            @if($template->tags)<div class="mt-3 flex flex-wrap gap-1">@foreach($template->tags as $tag)<span class="rounded border border-[#27213D] px-2 py-0.5 text-[10px] text-[#94A3B8]">{{ $tag }}</span>@endforeach</div>@endif
        </div>

        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
            @if(($template->isFree() || $template->isIncludedFor(auth()->user())) && ! $purchased && ! auth()->user()->isAdmin())
                <form method="POST" action="{{ route('dashboard.templates.unlock-free', $template) }}">@csrf<button class="rounded-xl bg-[#8B5CF6] px-5 py-3 text-sm font-black text-white">Unlock $0.00 Template</button></form>
            @elseif($template->isPaid() && ! $purchased && ! auth()->user()->isAdmin())
                @if($pendingInvoice)
                    <p class="text-sm text-[#A1A1AA]">Payment is required before this template can be imported.</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <a href="{{ route('dashboard.payments.show', $pendingInvoice) }}" class="rounded-xl bg-[#8B5CF6] px-5 py-3 text-sm font-black text-white">Continue Payment</a>
                        <span class="text-xs text-[#94A3B8]">Invoice status: {{ ucfirst($pendingInvoice->status) }}</span>
                    </div>
                @else
                    <p class="text-sm text-[#A1A1AA]">Payment is required before this template can be imported. Amount due: <span class="font-bold text-white">{{ $template->formatted_price }}</span>.</p>
                    <form method="POST" action="{{ route('dashboard.templates.crypto-invoice', $template) }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="payment_method" value="crypto">
                        <p class="mb-3 text-xs text-[#94A3B8]">You will select your payment network on the next step.</p>
                        <button class="rounded-xl bg-[#8B5CF6] px-5 py-3 text-sm font-black text-white">Purchase with Crypto</button>
                    </form>
                @endif
            @else
                <p class="text-sm font-bold text-[#22C55E]">{{ $template->isFree() ? 'Unlocked' : 'Purchased / Unlocked' }}</p>
                <form x-data="{ open: false, val: '{{ $bots->first()?->id }}', urls: @js($bots->mapWithKeys(fn($b) => [$b->id => route('bots.templates.import', [$b, $template])])->all()), labels: @js($bots->pluck('name', 'id')->all()), get label() { return this.labels[this.val] || 'Select bot' } }" :action="val && urls[val] ? urls[val] : '#'" method="POST" class="mt-3 grid gap-3 md:grid-cols-[1fr_auto]">
                    @csrf
                    <input type="hidden" name="bot_id" :value="val">
                    <div class="relative" @click.away="open = false">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#11101C] px-3 py-3 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-2 ring-[#8B5CF6]/20' : ''">
                            <span x-text="label"></span>
                            <svg class="ml-2 h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                            @forelse($bots as $bot)
                            <button type="button" @click="val = '{{ $bot->id }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $bot->id }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                                <svg :class="val === '{{ $bot->id }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                {{ $bot->name }}
                            </button>
                            @empty
                            <div class="px-4 py-3 text-sm text-[#94A3B8]">No bots available</div>
                            @endforelse
                        </div>
                    </div>
                    <button @disabled($bots->isEmpty()) class="rounded-xl bg-[#8B5CF6] px-5 py-3 text-sm font-black text-white">Import into Bot</button>
                </form>
            @endif
        </div>

        @if($template->features)<div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5"><h2 class="font-black">Features</h2><ul class="mt-2 list-disc pl-5 text-sm text-[#A1A1AA]">@foreach($template->features as $feature)<li>{{ $feature }}</li>@endforeach</ul></div>@endif
        @if($template->requirements)<div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5"><h2 class="font-black">Requirements</h2><ul class="mt-2 list-disc pl-5 text-sm text-[#A1A1AA]">@foreach($template->requirements as $requirement)<li>{{ $requirement }}</li>@endforeach</ul></div>@endif
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 text-sm text-[#A1A1AA]">
            This template includes {{ $template->commands_count }} commands. Command names and code are only visible after importing into your bot workspace.
        </div>
    </div>
</x-dashboard-layout>
