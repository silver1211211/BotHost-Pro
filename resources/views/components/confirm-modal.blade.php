{{--
    Global confirmation modal — powered by Alpine.store('confirm').
    Included once in each layout. Triggered via confirm.js data-confirm attributes.
--}}
<div
    x-data
    x-show="$store.confirm.open"
    x-cloak
    @keydown.escape.window="$store.confirm.type !== 'danger' && $store.confirm.cancel()"
    class="fixed inset-0 z-[9999] flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="$store.confirm.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$store.confirm.type !== 'danger' && $store.confirm.cancel()"
        class="absolute inset-0 bg-black/70 backdrop-blur-sm"
    ></div>

    {{-- Card --}}
    <div
        x-show="$store.confirm.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full max-w-md rounded-2xl border bg-[#0F0D1A] p-6 shadow-2xl"
        :class="{
            'border-[#EF4444]/30': $store.confirm.type === 'danger',
            'border-[#F59E0B]/30': $store.confirm.type === 'warning',
            'border-[#8B5CF6]/30': $store.confirm.type === 'default',
            'border-[#22C55E]/30': $store.confirm.type === 'success',
        }"
    >
        {{-- Header --}}
        <div class="flex items-start gap-4 mb-5">
            <div
                class="mt-0.5 h-10 w-10 shrink-0 rounded-xl border flex items-center justify-center"
                :class="{
                    'bg-[#EF4444]/10 border-[#EF4444]/20 text-[#EF4444]': $store.confirm.type === 'danger',
                    'bg-[#F59E0B]/10 border-[#F59E0B]/20 text-[#F59E0B]': $store.confirm.type === 'warning',
                    'bg-[#8B5CF6]/10 border-[#8B5CF6]/20 text-[#8B5CF6]': $store.confirm.type === 'default',
                    'bg-[#22C55E]/10 border-[#22C55E]/20 text-[#22C55E]': $store.confirm.type === 'success',
                }"
            >
                <template x-if="$store.confirm.type === 'danger'">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                </template>
                <template x-if="$store.confirm.type === 'warning'">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                </template>
                <template x-if="$store.confirm.type === 'default'">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/></svg>
                </template>
                <template x-if="$store.confirm.type === 'success'">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </template>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-base font-black text-[#F8FAFC]" x-text="$store.confirm.title"></h3>
                <p class="mt-1 text-sm text-[#A1A1AA] leading-relaxed" x-text="$store.confirm.message"></p>
                <p x-show="$store.confirm.warning" x-cloak class="mt-2 text-xs font-semibold text-[#F59E0B]" x-text="$store.confirm.warning"></p>
            </div>
        </div>

        {{-- Typed confirmation --}}
        <div x-show="$store.confirm.requireTyped" x-cloak class="mb-4">
            <label class="block text-xs font-bold text-[#71717A] mb-1.5">
                Type <span class="font-black text-[#EF4444]" x-text="$store.confirm.typedWord"></span> to confirm
            </label>
            <input
                type="text"
                x-model="$store.confirm.typedValue"
                autocomplete="off"
                spellcheck="false"
                class="w-full rounded-xl border border-[#27213D] bg-[#151225] px-4 py-2.5 text-sm text-[#F8FAFC] outline-none focus:border-[#EF4444]/60 focus:ring-2 focus:ring-[#EF4444]/15 transition"
                :placeholder="$store.confirm.typedWord"
                @keydown.enter="$store.confirm.typedValue === $store.confirm.typedWord && $store.confirm.accept()"
            >
        </div>

        {{-- Buttons --}}
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button
                type="button"
                @click="$store.confirm.cancel()"
                :disabled="$store.confirm.loading"
                class="w-full sm:w-auto rounded-xl border border-[#27213D] bg-[#151225] px-5 py-2.5 text-sm font-bold text-[#A1A1AA] transition hover:text-[#F8FAFC] disabled:opacity-50"
            >Cancel</button>
            <button
                type="button"
                @click="$store.confirm.accept()"
                :disabled="$store.confirm.loading || ($store.confirm.requireTyped && $store.confirm.typedValue !== $store.confirm.typedWord)"
                class="w-full sm:w-auto rounded-xl px-5 py-2.5 text-sm font-black text-white transition disabled:opacity-40 disabled:cursor-not-allowed"
                :class="{
                    'bg-[#EF4444] hover:bg-red-400':    $store.confirm.type === 'danger',
                    'bg-[#F59E0B] hover:bg-amber-400':  $store.confirm.type === 'warning',
                    'bg-[#8B5CF6] hover:bg-violet-400': $store.confirm.type === 'default',
                    'bg-[#22C55E] hover:bg-green-400':  $store.confirm.type === 'success',
                }"
            >
                <span x-show="!$store.confirm.loading" x-text="$store.confirm.confirmText"></span>
                <span x-show="$store.confirm.loading" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 6 12 12h4z"/>
                    </svg>
                    Processing…
                </span>
            </button>
        </div>
    </div>
</div>
