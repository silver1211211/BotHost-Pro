<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-3xl font-black text-[#F8FAFC]">Reset Your Password</h1>
        <p class="mt-3 text-sm leading-6 text-[#A1A1AA]">Enter your email and we'll send you a reset link.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl border border-[#38BDF8]/30 bg-[#38BDF8]/10 px-4 py-3 text-sm text-[#38BDF8]" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" x-data="{ loading: false }" @submit="loading = true" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="text-sm font-black text-[#F8FAFC]">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="mt-2 block w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6] focus:ring-2 focus:ring-[#8B5CF6]/20" placeholder="you@example.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-[#EF4444]" />
        </div>
        <button class="flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-3.5 text-sm font-extrabold text-white shadow-[0_0_30px_rgba(139,92,246,0.32)] transition hover:-translate-y-0.5">
            <span x-show="!loading">Send Reset Link</span>
            <span x-show="loading">Sending...</span>
        </button>
        <a href="{{ route('login') }}" class="block text-center text-sm font-semibold text-[#A1A1AA] transition hover:text-[#F8FAFC]">Back to login</a>
    </form>
</x-guest-layout>
