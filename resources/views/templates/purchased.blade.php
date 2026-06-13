<x-dashboard-layout title="Purchased Templates">
    <div class="mx-auto max-w-5xl space-y-5">
        <a href="{{ route('dashboard.templates.index') }}" class="text-sm text-[#A1A1AA]">Back to marketplace</a>
        <h1 class="text-2xl font-black">Purchased Templates</h1>
        <div class="grid gap-4 md:grid-cols-2">
            @forelse($purchases as $purchase)
                <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4" x-data="{ open: false }">
                    <div class="relative mb-3 w-full overflow-hidden rounded-xl" style="aspect-ratio:16/9">
                        @if($purchase->template?->thumbnail_url)
                            <img src="{{ $purchase->template->thumbnail_url }}" alt="{{ $purchase->template->name }}"
                                 class="absolute inset-0 h-full w-full object-cover"
                                 onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="absolute inset-0 items-center justify-center bg-[#11101C] text-xs text-[#71717A]" style="display:none">No image</div>
                        @else
                            <div class="flex h-full w-full items-center justify-center border border-[#27213D] bg-[#11101C] text-xs text-[#71717A]">No image</div>
                        @endif
                    </div>

                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h2 class="text-base font-black">{{ $purchase->template?->name ?? 'Template removed' }}</h2>
                            <p class="mt-1 text-xs text-[#71717A]">
                                Purchased {{ $purchase->purchased_at?->diffForHumans() }}
                                @if((float)$purchase->amount > 0) &middot; {{ $purchase->currency }} {{ number_format((float)$purchase->amount, 2) }} @endif
                            </p>
                        </div>
                        @if($purchase->template && ($purchase->template->short_description || $purchase->template->description))
                            <button type="button" @click="open = !open"
                                    class="mt-0.5 shrink-0 rounded-lg border border-[#27213D] bg-[#11101C] p-1.5 text-[#71717A] transition hover:border-[#8B5CF6]/40 hover:text-[#A1A1AA]"
                                    :aria-expanded="open">
                                <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }"
                                     fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                                </svg>
                            </button>
                        @endif
                    </div>

                    @if($purchase->template && ($purchase->template->short_description || $purchase->template->description))
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="mt-3 rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3 text-sm text-[#A1A1AA]">
                            {{ $purchase->template->short_description ?: $purchase->template->description }}
                        </div>
                    @endif

                    @if($purchase->template)
                        <a href="{{ route('dashboard.templates.show', $purchase->template) }}"
                           class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-[#8B5CF6] px-4 py-2 text-sm font-black text-white transition hover:bg-[#7C3AED]">
                            Import into Bot
                        </a>
                    @endif
                </div>
            @empty
                <div class="col-span-2 py-10 text-center text-sm text-[#71717A]">No purchased templates yet. <a href="{{ route('dashboard.templates.index') }}" class="text-[#8B5CF6] hover:underline">Browse the marketplace</a>.</div>
            @endforelse
        </div>
        {{ $purchases->links() }}
    </div>
</x-dashboard-layout>
