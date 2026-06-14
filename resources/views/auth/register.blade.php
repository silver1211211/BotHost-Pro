<x-guest-layout>

    {{-- Alpine component logic --}}
    <script>
        function registerForm() {
            return {
                showPassword: false,
                showConfirm: false,
                loading: false,
                termsAccepted: false,
                e: { username: '', email: '', password: '', password_confirmation: '', terms: '' },

                focusEl(id) {
                    this.$nextTick(() => {
                        const el = document.getElementById(id);
                        if (el) { el.focus(); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                    });
                },

                validate() {
                    this.e = { username: '', email: '', password: '', password_confirmation: '', terms: '' };

                    const u  = (document.getElementById('username')?.value  || '').trim();
                    const em = (document.getElementById('email')?.value     || '').trim();
                    const p  =  document.getElementById('password')?.value  || '';
                    const c  =  document.getElementById('password_confirmation')?.value || '';

                    if (!u)  { this.e.username = 'Enter your username.'; this.focusEl('username'); return false; }

                    if (!em) { this.e.email = 'Enter your email address.'; this.focusEl('email'); return false; }
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { this.e.email = 'Enter a valid email address.'; this.focusEl('email'); return false; }

                    if (!p)  { this.e.password = 'Enter your password.'; this.focusEl('password'); return false; }
                    if (p.length < 8) { this.e.password = 'Password must be at least 8 characters.'; this.focusEl('password'); return false; }

                    if (!c)  { this.e.password_confirmation = 'Confirm your password.'; this.focusEl('password_confirmation'); return false; }
                    if (p !== c) { this.e.password_confirmation = 'Passwords do not match.'; this.focusEl('password_confirmation'); return false; }

                    if (!this.termsAccepted) { this.e.terms = 'Accept the Terms of Service and Privacy Policy to continue.'; this.focusEl('terms'); return false; }

                    return true;
                }
            };
        }
    </script>

    {{-- Back button --}}
    <a href="{{ route('login') }}" class="-mt-1 mb-6 inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#4B4565] transition hover:text-[#A1A1AA]">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Back to Login
    </a>

    {{-- Header --}}
    <div class="mb-7">
        <span class="mb-3 inline-flex items-center gap-1.5 rounded-lg border border-[#8B5CF6]/20 bg-[#8B5CF6]/8 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-[#8B5CF6]">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
            </svg>
            Create Account
        </span>
        <h1 class="text-[26px] font-black tracking-tight text-[#F8FAFC]">Join BotHost Pro</h1>
        <p class="mt-1.5 text-sm leading-relaxed text-[#94A3B8]">Set up your free workspace and start building Telegram bots.</p>
    </div>

    <form
        method="POST"
        action="{{ route('register') }}"
        novalidate
        x-data="registerForm()"
        @submit="if (!validate()) $event.preventDefault(); else loading = true"
        class="space-y-5"
    >
        @csrf

        {{-- Username --}}
        <div>
            <label for="username" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#94A3B8]">Username</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#3D3759]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    autofocus
                    autocomplete="username"
                    spellcheck="false"
                    autocapitalize="off"
                    placeholder="botbuilder"
                    @input="e.username = ''"
                    class="block w-full rounded-xl border bg-[#0D0C18] py-3 pl-10 pr-4 text-sm text-[#F8FAFC] outline-none transition placeholder:text-[#3D3759] focus:ring-2 {{ $errors->has('username') ? 'border-[#EF4444]/50 focus:border-[#EF4444]/70 focus:ring-[#EF4444]/12' : 'border-[#27213D] focus:border-[#8B5CF6]/70 focus:bg-[#100F1C] focus:ring-[#8B5CF6]/12' }}"
                    :style="e.username ? 'border-color:rgba(239,68,68,.5);box-shadow:0 0 0 2px rgba(239,68,68,.1)' : ''"
                >
            </div>
            <p x-show="e.username" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-0.5" x-transition:enter-end="opacity-100 translate-y-0" class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-[#EF4444]">
                <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span x-text="e.username"></span>
            </p>
            @error('username')
                <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-[#EF4444]">
                    <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#94A3B8]">Email Address</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#3D3759]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                </div>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    spellcheck="false"
                    autocapitalize="off"
                    placeholder="you@example.com"
                    @input="e.email = ''"
                    class="block w-full rounded-xl border bg-[#0D0C18] py-3 pl-10 pr-4 text-sm text-[#F8FAFC] outline-none transition placeholder:text-[#3D3759] focus:ring-2 {{ $errors->has('email') ? 'border-[#EF4444]/50 focus:border-[#EF4444]/70 focus:ring-[#EF4444]/12' : 'border-[#27213D] focus:border-[#8B5CF6]/70 focus:bg-[#100F1C] focus:ring-[#8B5CF6]/12' }}"
                    :style="e.email ? 'border-color:rgba(239,68,68,.5);box-shadow:0 0 0 2px rgba(239,68,68,.1)' : ''"
                >
            </div>
            <p x-show="e.email" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-0.5" x-transition:enter-end="opacity-100 translate-y-0" class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-[#EF4444]">
                <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span x-text="e.email"></span>
            </p>
            @error('email')
                <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-[#EF4444]">
                    <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#94A3B8]">Password</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#3D3759]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                </div>
                <input
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    spellcheck="false"
                    autocapitalize="off"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Create a strong password"
                    @input="e.password = ''"
                    class="block w-full rounded-xl border bg-[#0D0C18] py-3 pl-10 pr-11 text-sm text-[#F8FAFC] outline-none transition placeholder:text-[#3D3759] focus:ring-2 {{ $errors->has('password') ? 'border-[#EF4444]/50 focus:border-[#EF4444]/70 focus:ring-[#EF4444]/12' : 'border-[#27213D] focus:border-[#8B5CF6]/70 focus:bg-[#100F1C] focus:ring-[#8B5CF6]/12' }}"
                    :style="e.password ? 'border-color:rgba(239,68,68,.5);box-shadow:0 0 0 2px rgba(239,68,68,.1)' : ''"
                >
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-[#3D3759] transition hover:text-[#94A3B8]" :title="showPassword ? 'Hide password' : 'Show password'">
                    <svg x-show="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                    </svg>
                </button>
            </div>
            <p x-show="e.password" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-0.5" x-transition:enter-end="opacity-100 translate-y-0" class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-[#EF4444]">
                <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span x-text="e.password"></span>
            </p>
            @error('password')
                <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-[#EF4444]">
                    <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-[#94A3B8]">Confirm Password</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#3D3759]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </div>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    spellcheck="false"
                    :type="showConfirm ? 'text' : 'password'"
                    placeholder="Repeat your password"
                    @input="e.password_confirmation = ''"
                    class="block w-full rounded-xl border bg-[#0D0C18] py-3 pl-10 pr-11 text-sm text-[#F8FAFC] outline-none transition placeholder:text-[#3D3759] focus:ring-2 border-[#27213D] focus:border-[#8B5CF6]/70 focus:bg-[#100F1C] focus:ring-[#8B5CF6]/12"
                    :style="e.password_confirmation ? 'border-color:rgba(239,68,68,.5);box-shadow:0 0 0 2px rgba(239,68,68,.1)' : ''"
                >
                <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-[#3D3759] transition hover:text-[#94A3B8]" :title="showConfirm ? 'Hide password' : 'Show password'">
                    <svg x-show="!showConfirm" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <svg x-show="showConfirm" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                    </svg>
                </button>
            </div>
            <p x-show="e.password_confirmation" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-0.5" x-transition:enter-end="opacity-100 translate-y-0" class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-[#EF4444]">
                <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span x-text="e.password_confirmation"></span>
            </p>
        </div>

        {{-- Terms --}}
        <label
            for="terms"
            class="group flex cursor-pointer gap-3.5 rounded-2xl border p-4 transition"
            :class="e.terms ? 'border-[#EF4444]/40 bg-[#EF4444]/5' : 'border-[#27213D] bg-[#0D0C18] hover:border-[#8B5CF6]/30 hover:bg-[#100F1C]'"
        >
            <div class="mt-0.5 shrink-0">
                <input
                    id="terms"
                    type="checkbox"
                    name="terms"
                    value="1"
                    x-model="termsAccepted"
                    @change="e.terms = ''"
                    class="h-4 w-4 rounded border-[#3A3553] bg-[#0D0C18] text-[#8B5CF6] outline-none focus:ring-2 focus:ring-[#8B5CF6]/20 focus:ring-offset-0"
                >
            </div>
            <span class="select-none text-sm leading-relaxed text-[#94A3B8]">
                I agree to the
                <a href="{{ route('legal.terms') }}" class="font-semibold text-[#38BDF8] transition hover:text-[#7DD3FC]" target="_blank" rel="noopener noreferrer">Terms of Service</a>
                and
                <a href="{{ route('legal.privacy') }}" class="font-semibold text-[#38BDF8] transition hover:text-[#7DD3FC]" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.
                By creating an account you consent to receiving service-related emails.
            </span>
        </label>

        {{-- Inline terms error (small, no card) --}}
        <p
            x-show="e.terms"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-0.5"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="-mt-2 flex items-center gap-1.5 text-xs font-medium text-[#EF4444]"
        >
            <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span x-text="e.terms"></span>
        </p>
        @error('terms')
            <p class="flex items-center gap-1.5 text-xs font-medium text-[#EF4444]">
                <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ $message }}</span>
            </p>
        @enderror

        {{-- Submit --}}
        <button
            type="submit"
            class="group flex w-full items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] py-3.5 text-sm font-extrabold text-white shadow-[0_0_28px_rgba(139,92,246,0.30)] transition hover:-translate-y-0.5 hover:shadow-[0_0_44px_rgba(139,92,246,0.44)] active:scale-[0.99] active:translate-y-0"
            :class="{ 'opacity-70 cursor-not-allowed': loading }"
            :disabled="loading"
        >
            <span x-show="!loading" class="flex items-center gap-2.5">
                <svg class="h-4 w-4 transition group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                </svg>
                Create Free Account
            </span>
            <span x-show="loading" x-cloak class="flex items-center gap-2.5">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Creating account...
            </span>
        </button>

        {{-- Divider --}}
        <div class="flex items-center gap-3">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-[#27213D] to-transparent"></div>
            <span class="text-[10px] font-semibold uppercase tracking-widest text-[#3D3759]">Have an account?</span>
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-[#27213D] to-transparent"></div>
        </div>

        {{-- Login link --}}
        <a
            href="{{ route('login') }}"
            class="flex w-full items-center justify-center gap-2.5 rounded-xl border border-[#27213D] bg-[#0D0C18] py-3 text-sm font-bold text-[#94A3B8] transition hover:border-[#8B5CF6]/35 hover:bg-[#13111E] hover:text-[#F8FAFC]"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
            </svg>
            Sign in to existing account
        </a>

    </form>
</x-guest-layout>
