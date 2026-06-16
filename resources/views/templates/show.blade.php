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
            @if($template->short_description)
                <p class="mt-2 text-sm font-bold text-[#D4D4D8]">{!! \App\Support\SafeTemplateText::inline($template->short_description) !!}</p>
            @endif
            @if($template->description)
                <div class="mt-3 space-y-3 text-sm leading-6 text-[#A1A1AA] [&_strong]:font-bold [&_strong]:text-white">
                    {!! \App\Support\SafeTemplateText::paragraphs($template->description) !!}
                </div>
            @endif
            <div class="mt-3 flex flex-wrap gap-2 text-xs text-[#94A3B8]"><span>{{ $template->category ?: 'General' }}</span><span>{{ ucfirst($template->level) }}</span><span>{{ $template->commands_count }} commands</span>@if($template->includedPlanLabel())<span>{{ $template->includedPlanLabel() }}</span>@endif</div>
            <p class="mt-3 text-lg font-black">{{ $template->formatted_price }}</p>
            @if($template->demo_url)<a href="{{ $template->demo_url }}" class="mt-3 inline-flex items-center gap-1.5 rounded-xl border border-[#38BDF8]/30 bg-[#38BDF8]/10 px-4 py-2 text-sm font-bold text-[#38BDF8] transition hover:bg-[#38BDF8]/20 hover:text-white" rel="noopener noreferrer" target="_blank">
    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
    View Demo Bot
</a>@endif
            @if($template->tags)<div class="mt-3 flex flex-wrap gap-1">@foreach($template->tags as $tag)<span class="rounded border border-[#27213D] px-2 py-0.5 text-[10px] text-[#94A3B8]">{{ $tag }}</span>@endforeach</div>@endif
        </div>

        @if((($template->isFree() || $template->isIncludedFor(auth()->user())) && ! $purchased && ! auth()->user()->isAdmin()) || ($template->isPaid() && ! $purchased && ! auth()->user()->isAdmin()))
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
            @endif
        </div>
        @endif

        @if($template->features)<div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5"><h2 class="font-black">Features</h2><ul class="mt-2 list-disc pl-5 text-sm text-[#A1A1AA]">@foreach($template->features as $feature)<li>{{ $feature }}</li>@endforeach</ul></div>@endif
        @if($template->requirements)<div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5"><h2 class="font-black">Requirements</h2><ul class="mt-2 list-disc pl-5 text-sm text-[#A1A1AA]">@foreach($template->requirements as $requirement)<li>{{ $requirement }}</li>@endforeach</ul></div>@endif
        <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 text-sm text-[#A1A1AA]">
            This template includes {{ $template->commands_count }} commands. Command names and code are only visible after importing into your bot workspace.
        </div>
    </div>
</x-dashboard-layout>
