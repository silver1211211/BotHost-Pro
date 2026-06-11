<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($branding = \App\Support\Branding::assets())
    @if($branding['favicon_url'])
        <link rel="icon" href="{{ $branding['favicon_url'] }}">
    @endif
    <title>Account Locked — {{ config('app.name', 'BotHost Pro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#05040A] font-sans text-[#F8FAFC] antialiased">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">

        {{-- Background --}}
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_45%_0%,rgba(239,68,68,0.12),transparent_35%),radial-gradient(circle_at_80%_60%,rgba(139,92,246,0.10),transparent_30%),linear-gradient(180deg,#05040A,#090713_54%,#05040A)]"></div>
        <div class="absolute inset-0 opacity-[0.07] [background-image:linear-gradient(rgba(248,250,252,0.12)_1px,transparent_1px),linear-gradient(90deg,rgba(248,250,252,0.12)_1px,transparent_1px)] [background-size:46px_46px]"></div>

        <div class="relative w-full max-w-[480px]">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="mx-auto mb-8 flex w-fit items-center gap-3">
                @if($branding['platform_logo_url'])
                    <img src="{{ $branding['platform_logo_url'] }}" alt="{{ $branding['platform_name'] }}" class="h-12 w-12 rounded-2xl object-contain">
                @else
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-[#8B5CF6] via-[#A855F7] to-[#229ED9] shadow-[0_0_36px_rgba(139,92,246,0.34)]">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.75 12 14.5-7.25-3.2 14.5-4.12-4.18-3.08 3.18.45-4.55L4.75 12Z"/></svg>
                    </span>
                @endif
                <span class="text-xl font-black tracking-tight">BotHost <span class="bg-gradient-to-r from-[#38BDF8] to-[#A855F7] bg-clip-text text-transparent">Pro</span></span>
            </a>

            <section class="rounded-3xl border border-[#27213D] bg-[#0F0D1A]/95 p-8 shadow-[0_30px_100px_rgba(0,0,0,0.62)] backdrop-blur-xl">

                @php $user = auth()->user(); @endphp

                @if($user->isBanned())
                    {{-- Permanent ban --}}
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl border border-[#EF4444]/20 bg-[#EF4444]/10">
                        <svg class="h-6 w-6 text-[#EF4444]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black text-white">Account Banned</h1>
                    <p class="mt-2 text-sm leading-relaxed text-[#71717A]">
                        Your account has been permanently banned from this platform.
                        If you believe this is a mistake, please reach out to our support team.
                    </p>

                @else
                    {{-- Suspended --}}
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl border border-[#F59E0B]/20 bg-[#F59E0B]/10">
                        <svg class="h-6 w-6 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black text-white">Account Suspended</h1>

                    @if($user->suspended_until)
                        <p class="mt-1 text-xs font-bold text-[#F59E0B]">
                            Suspended until {{ $user->suspended_until->format('M d, Y \a\t h:i A') }}
                        </p>
                    @else
                        <p class="mt-1 text-xs font-bold text-[#F59E0B]">Indefinitely suspended</p>
                    @endif

                    @if($user->suspension_message)
                        <p class="mt-3 text-sm leading-relaxed text-[#A1A1AA]">{{ $user->suspension_message }}</p>
                    @else
                        <p class="mt-3 text-sm leading-relaxed text-[#71717A]">
                            Your account has been temporarily suspended. Please contact support for assistance.
                        </p>
                    @endif
                @endif

                {{-- CTA Button (admin-defined) --}}
                @if($user->suspension_cta_label && $user->suspension_cta_url)
                    <a href="{{ $user->suspension_cta_url }}"
                       target="_blank" rel="noopener noreferrer"
                       class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-[#8B5CF6] px-5 py-3 text-sm font-black text-white transition hover:bg-[#7C3AED]">
                        {{ $user->suspension_cta_label }}
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                    </a>
                @endif

                {{-- Sign out --}}
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-[#27213D] px-5 py-2.5 text-sm font-bold text-[#71717A] transition hover:border-[#3D3657] hover:text-[#A1A1AA]">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25"/>
                        </svg>
                        Sign Out
                    </button>
                </form>

            </section>
        </div>
    </main>
</body>
</html>
