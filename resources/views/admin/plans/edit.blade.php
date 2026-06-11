<x-admin-layout title="Edit Plan" subtitle="{{ $plan->name }}">
    <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="grid max-w-3xl gap-4 rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5">
        @csrf
        @method('PATCH')
        @if($errors->any())<div class="rounded border border-[#EF4444]/30 bg-[#EF4444]/10 p-3 text-sm text-[#EF4444]">{{ $errors->first() }}</div>@endif
        <label class="grid gap-2 text-sm font-bold text-[#A1A1AA]">Name<input name="name" value="{{ old('name', $plan->name) }}" class="rounded border border-[#27213D] bg-[#090713] p-3 text-white"></label>
        <label class="grid gap-2 text-sm font-bold text-[#A1A1AA]">Description<textarea name="description" class="rounded border border-[#27213D] bg-[#090713] p-3 text-white">{{ old('description', $plan->description) }}</textarea></label>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-2 text-sm font-bold text-[#A1A1AA]">Price<input name="price" value="{{ old('price', $plan->price) }}" class="rounded border border-[#27213D] bg-[#090713] p-3 text-white"></label>
            <label class="grid gap-2 text-sm font-bold text-[#A1A1AA]">Currency<input name="currency" value="{{ old('currency', $plan->currency) }}" class="rounded border border-[#27213D] bg-[#090713] p-3 text-white"></label>
            <div class="grid gap-2">
                <span class="text-sm font-bold text-[#A1A1AA]">Billing Period</span>
                <div class="relative" x-data="{ open: false, val: '{{ old('billing_period', $plan->billing_period) }}', get label() { return { 'monthly': 'Monthly', 'yearly': 'Yearly' }[this.val] || 'Monthly' } }" @click.away="open = false">
                    <input type="hidden" name="billing_period" :value="val">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : ''">
                        <span x-text="label"></span>
                        <svg class="ml-2 h-3.5 w-3.5 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                        @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $bpv => $bpl)
                        <button type="button" @click="val = '{{ $bpv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $bpv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                            <svg :class="val === '{{ $bpv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            {{ $bpl }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="grid gap-2">
                <span class="text-sm font-bold text-[#A1A1AA]">Status</span>
                <div class="relative" x-data="{ open: false, val: '{{ old('status', $plan->status) }}', get label() { return { 'active': 'Active', 'inactive': 'Inactive' }[this.val] || 'Active' } }" @click.away="open = false">
                    <input type="hidden" name="status" :value="val">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-[#27213D] bg-[#090713] px-3 py-3 text-sm text-[#F8FAFC] transition focus:outline-none" :class="open ? 'border-[#8B5CF6]/60 ring-1 ring-[#8B5CF6]/15' : ''">
                        <span x-text="label"></span>
                        <svg class="ml-2 h-3.5 w-3.5 shrink-0 text-[#71717A] transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-xl border border-[#27213D] bg-[#151225] shadow-[0_16px_48px_rgba(0,0,0,0.55)]">
                        @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $stv => $stl)
                        <button type="button" @click="val = '{{ $stv }}'; open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-[#1D1930]" :class="val === '{{ $stv }}' ? 'text-[#A855F7] bg-[#8B5CF6]/8' : 'text-[#A1A1AA]'">
                            <svg :class="val === '{{ $stv }}' ? 'opacity-100 text-[#A855F7]' : 'opacity-0'" class="h-3.5 w-3.5 shrink-0 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            {{ $stl }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <label class="grid gap-2 text-sm font-bold text-[#A1A1AA]">Features<textarea name="features" rows="6" class="rounded border border-[#27213D] bg-[#090713] p-3 text-white">{{ old('features', implode("\n", $plan->features ?? [])) }}</textarea></label>
        <button class="w-fit rounded bg-[#8B5CF6] px-4 py-2 text-sm font-bold text-white">Save Plan</button>
    </form>
</x-admin-layout>
