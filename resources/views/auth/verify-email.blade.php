<x-guest-layout>
    <div class="mb-8 text-center">
        <div class="mx-auto mb-5 grid h-14 w-14 place-items-center rounded-2xl border border-[#38BDF8]/30 bg-[#38BDF8]/10 text-[#38BDF8]">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.75 6.75h14.5v10.5H4.75V6.75Zm0 0L12 12.25l7.25-5.5"/></svg>
        </div>
        <h1 class="text-3xl font-black text-[#F8FAFC]">Verify Your Email</h1>
        <p class="mt-3 text-sm leading-6 text-[#A1A1AA]">Before accessing your dashboard, please verify your email address using the link sent to your inbox.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 rounded-xl border border-[#38BDF8]/30 bg-[#38BDF8]/10 px-4 py-3 text-sm text-[#38BDF8]">A new verification link has been sent to your email address.</div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/8 px-4 py-3 text-sm text-[#EF4444]">{{ $errors->first() }}</div>
    @endif

    <div class="space-y-3">
        @if (Route::has('verification.send'))
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="w-full rounded-xl bg-gradient-to-r from-[#8B5CF6] to-[#A855F7] px-5 py-3.5 text-sm font-extrabold text-white shadow-[0_0_30px_rgba(139,92,246,0.32)]">Resend Verification Email</button>
            </form>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-xl border border-[#27213D] bg-[#11101C] px-5 py-3.5 text-sm font-bold text-[#A1A1AA] transition hover:border-[#38BDF8]/50 hover:text-[#F8FAFC]">Logout</button>
        </form>
    </div>
</x-guest-layout>
