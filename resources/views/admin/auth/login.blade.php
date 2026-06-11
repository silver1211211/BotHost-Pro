<x-guest-layout>
    <div class="mb-8">
        <span class="inline-flex rounded-full border border-[#EF4444]/30 bg-[#EF4444]/10 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-[#EF4444]">Admin Access Only</span>
        <h1 class="mt-5 text-3xl font-black text-[#F8FAFC]">Admin Login</h1>
        <p class="mt-3 text-sm leading-6 text-[#A1A1AA]">Sign in to access the BotHost Pro administration panel.</p>
    </div>

    @if (session('error'))
        <div class="mb-5 rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#EF4444]">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.login.store') }}" x-data="{ loading: false }" @submit="loading = true" class="space-y-5">
        @csrf
        <div>
            <label for="username" class="text-sm font-black text-[#F8FAFC]">Username</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" class="mt-2 block w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6] focus:ring-2 focus:ring-[#8B5CF6]/20" placeholder="admin">
            <x-input-error :messages="$errors->get('username')" class="mt-2 text-[#EF4444]" />
        </div>
        <div>
            <label for="password" class="text-sm font-black text-[#F8FAFC]">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="mt-2 block w-full rounded-xl border border-[#27213D] bg-[#11101C] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:border-[#8B5CF6] focus:ring-2 focus:ring-[#8B5CF6]/20" placeholder="Your password">
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-[#EF4444]" />
        </div>
        <label for="admin_remember" class="flex items-center gap-3 text-sm text-[#A1A1AA]">
            <input id="admin_remember" type="checkbox" name="remember" class="rounded border-[#27213D] bg-[#11101C] text-[#8B5CF6] focus:ring-[#8B5CF6]/30">
            Remember me
        </label>
        <button class="flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-3.5 text-sm font-extrabold text-white shadow-[0_0_30px_rgba(139,92,246,0.32)]">
            <span x-show="!loading">Sign in to Admin</span>
            <span x-show="loading">Signing in...</span>
        </button>
    </form>
</x-guest-layout>
