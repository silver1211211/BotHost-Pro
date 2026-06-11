<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-3xl font-black text-[#F8FAFC]">Create New Password</h1>
        <p class="mt-3 text-sm leading-6 text-[#A1A1AA]">Choose a strong, secure password for your BotHost Pro account.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" x-data="{ showPassword: false, loading: false }" @submit="loading = true" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <label for="email" class="text-sm font-black text-[#F8FAFC]">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="mt-2 block w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6] focus:ring-2 focus:ring-[#8B5CF6]/20">
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-[#EF4444]" />
        </div>
        <div>
            <label for="password" class="text-sm font-black text-[#F8FAFC]">New Password</label>
            <input id="password" name="password" required autocomplete="new-password" :type="showPassword ? 'text' : 'password'" class="mt-2 block w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6] focus:ring-2 focus:ring-[#8B5CF6]/20" placeholder="Create a strong password">
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-[#EF4444]" />
        </div>
        <div>
            <label for="password_confirmation" class="text-sm font-black text-[#F8FAFC]">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-2 block w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6] focus:ring-2 focus:ring-[#8B5CF6]/20" placeholder="Repeat your new password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-[#EF4444]" />
        </div>
        <button class="flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-3.5 text-sm font-extrabold text-white shadow-[0_0_30px_rgba(139,92,246,0.32)] transition hover:-translate-y-0.5">
            <span x-show="!loading">Reset Password</span>
            <span x-show="loading">Resetting...</span>
        </button>
    </form>
</x-guest-layout>
