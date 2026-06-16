<x-guest-layout>

    <script>
        function adminLoginForm() {
            return {
                loading: false,
                e: { username: '', password: '' },
                focusEl(id) {
                    this.$nextTick(() => {
                        const el = document.getElementById(id);
                        if (el) { el.focus(); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                    });
                },
                validate() {
                    this.e = { username: '', password: '' };
                    const u = (document.getElementById('username')?.value || '').trim();
                    const p =  document.getElementById('password')?.value || '';
                    if (!u) { this.e.username = 'Enter your username.'; this.focusEl('username'); return false; }
                    if (!p) { this.e.password = 'Enter your password.'; this.focusEl('password'); return false; }
                    return true;
                }
            };
        }
    </script>

    <div class="mb-8">
        <span class="inline-flex rounded-full border border-[#EF4444]/30 bg-[#EF4444]/10 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-[#EF4444]">Admin Access Only</span>
        <h1 class="mt-5 text-3xl font-black text-[#F8FAFC]">Admin Login</h1>
        <p class="mt-3 text-sm leading-6 text-[#A1A1AA]">Sign in to access the BotHost Pro administration panel.</p>
    </div>

    @if (session('error'))
        <div class="mb-5 rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 px-4 py-3 text-sm text-[#EF4444]">{{ session('error') }}</div>
    @endif

    <form
        method="POST"
        action="{{ route('admin.login.store') }}"
        novalidate
        x-data="adminLoginForm()"
        @submit="if (!validate()) $event.preventDefault(); else loading = true"
        class="space-y-5"
    >
        @csrf
        <div>
            <label for="username" class="text-sm font-black text-[#F8FAFC]">Username</label>
            <input
                id="username"
                type="text"
                name="username"
                value="{{ old('username') }}"
                autofocus
                autocomplete="username"
                placeholder="admin"
                @input="e.username = ''"
                class="mt-2 block w-full rounded-xl border bg-[#11101C] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:ring-2 {{ $errors->has('username') ? 'border-[#EF4444]/50 focus:border-[#EF4444]/70 focus:ring-[#EF4444]/12' : 'border-[#27213D] focus:border-[#8B5CF6] focus:ring-[#8B5CF6]/20' }}"
                :style="e.username ? 'border-color:rgba(239,68,68,.5);box-shadow:0 0 0 2px rgba(239,68,68,.1)' : ''"
            >
            <p
                x-show="e.username"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="mt-1.5 flex items-center gap-1.5 text-[11px] font-semibold text-[#F87171]"
            >
                <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <span x-text="e.username"></span>
            </p>
            <x-input-error :messages="$errors->get('username')" class="mt-2 text-[#EF4444]" />
        </div>
        <div>
            <label for="password" class="text-sm font-black text-[#F8FAFC]">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                autocomplete="current-password"
                placeholder="Your password"
                @input="e.password = ''"
                class="mt-2 block w-full rounded-xl border bg-[#11101C] px-4 py-3 text-[#F8FAFC] outline-none transition placeholder:text-[#71717A] focus:ring-2 {{ $errors->has('password') ? 'border-[#EF4444]/50 focus:border-[#EF4444]/70 focus:ring-[#EF4444]/12' : 'border-[#27213D] focus:border-[#8B5CF6] focus:ring-[#8B5CF6]/20' }}"
                :style="e.password ? 'border-color:rgba(239,68,68,.5);box-shadow:0 0 0 2px rgba(239,68,68,.1)' : ''"
            >
            <p
                x-show="e.password"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="mt-1.5 flex items-center gap-1.5 text-[11px] font-semibold text-[#F87171]"
            >
                <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <span x-text="e.password"></span>
            </p>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-[#EF4444]" />
        </div>
        <label for="admin_remember" class="flex items-center gap-3 text-sm text-[#A1A1AA]">
            <input id="admin_remember" type="checkbox" name="remember" class="rounded border-[#27213D] bg-[#11101C] text-[#8B5CF6] focus:ring-[#8B5CF6]/30">
            Remember me
        </label>
        <button
            type="submit"
            class="flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-3.5 text-sm font-extrabold text-white shadow-[0_0_30px_rgba(139,92,246,0.32)] transition hover:-translate-y-0.5 active:scale-[0.99] active:translate-y-0"
            :class="{ 'opacity-70 cursor-not-allowed': loading }"
            :disabled="loading"
        >
            <span x-show="!loading">Sign in to Admin</span>
            <span x-show="loading" x-cloak class="flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Signing in...
            </span>
        </button>
    </form>
</x-guest-layout>
