<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-3xl font-black text-[#F8FAFC]">Confirm Password</h1>
        <p class="mt-3 text-sm leading-6 text-[#A1A1AA]">This is a secure area. Please confirm your password before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf
        <div>
            <label for="password" class="text-sm font-black text-[#F8FAFC]">Password</label>
            <input id="password" class="mt-2 block w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6] focus:ring-2 focus:ring-[#8B5CF6]/20" type="password" name="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-[#EF4444]" />
        </div>
        <button class="w-full rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-3.5 text-sm font-extrabold text-white shadow-[0_0_30px_rgba(139,92,246,0.32)]">Confirm</button>
    </form>
</x-guest-layout>
