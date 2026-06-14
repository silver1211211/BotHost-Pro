<x-dashboard-layout title="Support">

<div
    x-data="{
        search: '',
        open: null,
        toggle(id) { this.open = this.open === id ? null : id; },
        matches(text) {
            if (this.search.trim() === '') return true;
            return text.toLowerCase().includes(this.search.toLowerCase().trim());
        }
    }"
    class="space-y-6"
>

    {{-- ── Header ── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-[#F8FAFC]">Support</h1>
            <p class="mt-1 text-sm text-[#94A3B8]">Frequently asked questions and direct support for BotHost Pro.</p>
        </div>
        <a
            href="{{ route('help.index') }}"
            class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-4 py-2 text-xs font-black text-[#94A3B8] transition hover:border-[#8B5CF6]/40 hover:text-[#8B5CF6]"
        >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25"/></svg>
            Help Center
        </a>
    </div>

    {{-- ── Search ── --}}
    <div class="relative">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#52525B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        <input
            x-model="search"
            type="text"
            placeholder="Search frequently asked questions…"
            class="w-full rounded-xl border border-[#27213D] bg-[#0F0D1A] py-2.5 pl-10 pr-4 text-sm text-[#F8FAFC] placeholder-[#52525B] outline-none transition focus:border-[#8B5CF6]/50 focus:ring-1 focus:ring-[#8B5CF6]/30"
        >
        <button
            x-show="search.trim() !== ''"
            @click="search = ''"
            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#52525B] transition hover:text-[#A1A1AA]"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- ── Contact card ── --}}
    @if($supportUrl)
    <div class="rounded-2xl border border-[#8B5CF6]/20 bg-gradient-to-r from-[#8B5CF6]/6 to-transparent p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#8B5CF6]/20 bg-[#8B5CF6]/10">
                    <svg class="h-5 w-5 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-black text-[#F8FAFC]">Contact Support</p>
                    <p class="mt-0.5 text-xs text-[#94A3B8]">Can't find what you need in the FAQ? Reach out to the support team directly.</p>
                </div>
            </div>
            <a
                href="{{ $supportUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="shrink-0 rounded-xl bg-[#8B5CF6] px-5 py-2.5 text-xs font-black text-white transition hover:bg-[#7C3AED]"
            >
                Open Support
            </a>
        </div>
    </div>
    @endif

    {{-- ── Jump-to section grid ── --}}
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-[#52525B]">Jump to a topic</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach([
                ['Account',           'faq-account',   '#8B5CF6', 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
                ['Bots',              'faq-bots',      '#38BDF8', 'M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18'],
                ['Commands',          'faq-commands',  '#22C55E', 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['Templates',         'faq-templates', '#A855F7', 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'],
                ['Payments',          'faq-payments',  '#F59E0B', 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 21Z'],
                ['Plans & Limits',    'faq-plans',     '#38BDF8', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['Transfers',         'faq-transfers', '#8B5CF6', 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4'],
                ['Broadcasts',        'faq-broadcasts','#38BDF8', 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                ['Webhooks',          'faq-webhooks',  '#8B5CF6', 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244'],
                ['Storage',           'faq-storage',   '#F59E0B', 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 2.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125'],
                ['Errors & Logs',     'faq-errors',    '#EF4444', 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776'],
                ['Security',          'faq-security',  '#22C55E', 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
            ] as [$label, $anchor, $color, $icon])
            <a
                href="#{{ $anchor }}"
                class="flex items-center gap-2.5 rounded-xl border border-[#1B172B] bg-[#0B0918] px-3 py-2.5 text-xs font-black text-[#94A3B8] transition hover:border-[#27213D] hover:text-[#A1A1AA]"
            >
                <span class="shrink-0 grid h-6 w-6 place-items-center rounded-lg" style="background-color:{{ $color }}18">
                    <svg style="height:11px;width:11px;color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </span>
                <span class="leading-tight">{{ $label }}</span>
            </a>
            @endforeach
        </div>
    </div>

    {{-- ── FAQ sections ── --}}
    @php
    $faqs = [
        [
            'id'    => 'faq-account',
            'title' => 'Account',
            'color' => '#8B5CF6',
            'icon'  => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
            'items' => [
                ['Can I change my username or email?',      'Yes. Go to Profile from the left sidebar and update your email or display name. Email changes may require verification via a link sent to the new address.'],
                ['How do I reset my password?',             'On the login page, click Forgot Password and enter your email. You will receive a reset link. If you are logged in, go to Profile and update your password in the Security section.'],
                ['Can I delete my account?',                'Account deletion requires contacting support. Before requesting deletion, transfer or remove any bots you want to preserve, as account deletion removes all associated data permanently.'],
                ['How do I enable two-factor authentication?', 'Go to Profile, open the Security section, and follow the two-factor authentication setup prompts. You will link an authenticator app and receive backup codes to store safely.'],
                ['Is my personal data stored securely?',    'Yes. Passwords are hashed and never stored in plain text. Bot tokens are encrypted at rest. Payment data is handled via OxaPay and is not stored on BotHost Pro servers.'],
            ],
        ],
        [
            'id'    => 'faq-bots',
            'title' => 'Bots',
            'color' => '#38BDF8',
            'icon'  => 'M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18',
            'items' => [
                ['Why is my bot not responding on Telegram?','Check the Logs tab in your bot workspace for errors. Common causes include a syntax error in a command, an expired or invalid bot token, or the bot being paused. Fixing the error restarts delivery automatically.'],
                ['How do I change my bot token?',           'Open the bot workspace, go to Settings, and find the Token section. Enter the new token from @BotFather and save. The old token will stop receiving messages immediately.'],
                ['Can I pause or stop a bot?',              'Yes. In the bot workspace settings you can pause the bot. While paused, incoming Telegram messages are not processed. You can resume at any time without losing configuration.'],
                ['Why does my bot say "unauthorized"?',     'This usually means the bot token is invalid or was regenerated in @BotFather. Go to Settings, update the token to the current one from @BotFather, and save.'],
                ['How many bots can I have?',               'The number of bots depends on your plan. The free plan allows one bot. Paid plans allow more. Check the Plans page for your current limit and how to upgrade.'],
            ],
        ],
        [
            'id'    => 'faq-commands',
            'title' => 'Commands',
            'color' => '#22C55E',
            'icon'  => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'items' => [
                ['My command returns an error. What do I do?', 'Open the Logs tab, find the failed execution entry, and read the error message. Common issues include a JavaScript syntax error, an undefined variable, or a failed external call. Fix the code in the Commands editor and save.'],
                ['Can I use JavaScript in commands?',        'Yes. Commands support Node.js for logic, conditional responses, and external API calls. Use the code editor tab when adding or editing a command to write JavaScript.'],
                ['Are there reserved command names?',        'Telegram reserves /start, /help, and /settings as built-in commands. You can still create responses for these in BotHost Pro — they will be handled by your custom logic when the bot receives them.'],
                ['Can commands call external APIs?',         'Yes. Inside a JavaScript command you can use fetch() or http.get() to call external APIs and include the response in your bot reply. Make sure your endpoint is reachable and returns a response quickly to avoid timeouts.'],
                ['What is the command response size limit?', 'Telegram limits individual message text to 4096 characters. If your command response exceeds this, Telegram will reject it. Split long responses across multiple messages in your command logic.'],
            ],
        ],
        [
            'id'    => 'faq-templates',
            'title' => 'Templates',
            'color' => '#A855F7',
            'icon'  => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
            'items' => [
                ['Can I get a refund for a paid template?',  'Template purchases are generally non-refundable once imported. If a template is significantly different from its description, contact support with details and a screenshot for review.'],
                ['What if a template breaks my bot?',        'Imported templates add commands to your workspace but do not overwrite existing ones or change any settings. You can delete any or all imported commands from the Commands tab if needed.'],
                ['How do template updates work?',            'Templates are copied at the time of import. Updates to the original template are not pushed to your workspace automatically. You can re-import an updated version, but this will add commands again (not replace).'],
                ['Can I share a template I created?',        'Yes. Publish your template from the bot workspace settings. You can set it as free or paid. The template goes through a review before becoming publicly visible in the marketplace.'],
                ['What happens to my templates if I downgrade?', 'Previously imported template commands remain in your bot workspaces. However, if your plan no longer allows the number of commands you have, you may need to delete some before you can add new ones.'],
            ],
        ],
        [
            'id'    => 'faq-payments',
            'title' => 'Payments & Billing',
            'color' => '#F59E0B',
            'icon'  => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 21Z',
            'items' => [
                ['My payment was deducted but not credited.','If your wallet balance was not updated after a payment, wait a few minutes and refresh. If still not updated after 10 minutes, contact support with your transaction ID from OxaPay.'],
                ['Can I get a refund on a plan?',           'Plan refund eligibility depends on how recently the plan was activated and how much usage has occurred. Contact support to discuss your specific situation.'],
                ['Where do I find my transaction history?',  'Open the Billing section and scroll to Invoice History. All deposits, plan purchases, and template purchases are listed there with amounts, dates, and statuses.'],
                ['What cryptocurrencies are accepted?',      'Payments go through OxaPay, which supports multiple cryptocurrencies including USDT, BTC, ETH, LTC, and others. The exact list is shown during the deposit flow.'],
                ['Can I transfer wallet balance to another account?', 'No. Wallet balance is tied to your account and cannot be transferred to other BotHost Pro accounts.'],
            ],
        ],
        [
            'id'    => 'faq-plans',
            'title' => 'Plans & Limits',
            'color' => '#38BDF8',
            'icon'  => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            'items' => [
                ['What happens at the end of my billing period?', 'Your plan renews automatically from your wallet balance. If the balance is insufficient, the plan expires and certain features may be restricted until you top up and renew.'],
                ['Can I upgrade mid-cycle?',                 'Yes. Upgrading takes effect immediately. You are charged the new plan price from your wallet. The remaining time on your old plan is not refunded but your limits increase right away.'],
                ['What plan features are restricted on free?','Free accounts can create one bot, a limited number of commands, and cannot use broadcasts or some advanced features. The Plans page lists the full comparison.'],
                ['Will I lose my data if my plan expires?',  'Your data — bots, commands, logs — is retained for a grace period after expiry. You can log in and renew to restore access. After the grace period, inactive accounts may be cleaned up.'],
                ['Is there a student or non-profit discount?','Discounts are not listed publicly. Contact support to inquire about special pricing for educational or non-profit use cases.'],
            ],
        ],
        [
            'id'    => 'faq-transfers',
            'title' => 'Transfers',
            'color' => '#8B5CF6',
            'icon'  => 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4',
            'items' => [
                ['Does the receiver need a BotHost Pro account?', 'Yes. Transfers can only be made to existing BotHost Pro accounts. The receiver can import without a Telegram bot token and connect one later.'],
                ['Is my bot token shared during a transfer?',  'No. Your bot token is never shared. The receiver can add their own token from @BotFather during import or later.'],
                ['How long does a transfer take?',             'Transfers are instant once the receiver accepts and provides their token. There is no delay between initiating and completing a transfer.'],
                ['Can I undo a transfer?',                     'You can cancel a pending transfer before the receiver accepts. Once the receiver has accepted and imported the workspace, the transfer cannot be reversed — but your original bot is not affected.'],
                ['Are there limits on transfers?',             'Transfer limits may apply based on your plan. Check the Plans page or contact support for specific transfer limits.'],
            ],
        ],
        [
            'id'    => 'faq-broadcasts',
            'title' => 'Broadcasts',
            'color' => '#38BDF8',
            'icon'  => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
            'items' => [
                ['My broadcast was not delivered to all users.','Undelivered messages usually mean some users have blocked your bot or deactivated their Telegram accounts. These are filtered out automatically. Check the broadcast report for delivery statistics.'],
                ['Can I cancel a broadcast after sending?',    'Broadcasts sent to a large audience are queued and sent in batches. You may be able to cancel a queued broadcast before it finishes. Open the Broadcasts tab and look for a cancel option on in-progress broadcasts.'],
                ['Why is my broadcast delayed?',               'Telegram has rate limits on how many messages a bot can send per second. Large broadcasts are sent in controlled batches to comply with these limits, which may cause a delay for large audiences.'],
                ['Can I send images or media in broadcasts?',  'Media broadcasts may be available on higher plans. Check the Broadcasts section of your bot workspace for supported message types on your current plan.'],
                ['How do I see how many people received my broadcast?', 'Open the Broadcasts tab in your bot workspace and click the broadcast entry to see delivery stats including sent, delivered, and failed counts.'],
            ],
        ],
        [
            'id'    => 'faq-webhooks',
            'title' => 'Custom Webhooks',
            'color' => '#8B5CF6',
            'icon'  => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244',
            'items' => [
                ['My webhook endpoint is not receiving events.','Check that your endpoint URL is publicly reachable over HTTPS and returns a 2xx response. Review the Delivery Logs in your webhook settings to see the exact response your server returned.'],
                ['Can I use a localhost URL for testing?',      'No. Localhost URLs are not reachable from BotHost Pro servers. Use a tunneling tool like ngrok to expose a local server with a public HTTPS URL during development.'],
                ['What does the webhook secret header do?',     'The secret header allows your server to verify that a request came from BotHost Pro and not from a third party. Check the X-BotHost-Signature header against an HMAC hash of the payload using your secret.'],
                ['How many retries does BotHost Pro attempt?',  'BotHost Pro retries failed deliveries with exponential backoff — typically 3 to 5 attempts over increasing intervals. After all retries fail, the delivery is marked as failed in the logs.'],
                ['Is there documentation for the webhook payload?', 'Yes. Visit the Webhook Documentation page for full payload schemas, event types, header references, and code examples in multiple languages.'],
            ],
        ],
        [
            'id'    => 'faq-storage',
            'title' => 'Storage',
            'color' => '#F59E0B',
            'icon'  => 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 2.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125',
            'items' => [
                ['I am at my storage limit. What do I do?',    'Delete files you no longer need from the Files section of your bot workspace. If you need more storage long-term, upgrade to a plan with a higher storage limit.'],
                ['Are deleted files recoverable?',             'Files deleted from the Files section are removed immediately and cannot be recovered. Bots moved to the Recycle Bin retain their associated files until the bot is permanently deleted.'],
                ['Does log storage count toward my limit?',    'No. Log data is stored separately and does not count against your file storage quota. Only uploaded files and project assets count toward storage.'],
                ['What is the maximum file size for uploads?', 'The maximum individual file size for uploads depends on your plan. Check the Plans page or contact support for the limit on your current plan.'],
            ],
        ],
        [
            'id'    => 'faq-errors',
            'title' => 'Errors & Logs',
            'color' => '#EF4444',
            'icon'  => 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776',
            'items' => [
                ['I see a "Script timeout" error. What does that mean?', 'A timeout error means your command took longer than the allowed execution time to respond. Optimize your code to return faster, or check if an external API call is slow or unresponsive.'],
                ['What does "Unauthorized" mean in the logs?','This usually means the bot token was revoked or changed in @BotFather. Update the token in your bot workspace Settings to restore delivery.'],
                ['My logs are not showing new entries.','Logs may take a few seconds to appear after an event. Refresh the Logs tab. If logs are missing for recent commands, check that the bot is not paused and the token is valid.'],
                ['How do I export or download logs?',          'Log export functionality depends on your plan. If available, look for a Download or Export button on the Logs tab. For critical log data, copy entries manually if export is not available on your plan.'],
            ],
        ],
        [
            'id'    => 'faq-security',
            'title' => 'Security',
            'color' => '#22C55E',
            'icon'  => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
            'items' => [
                ['Is my bot token visible to other users?',    'No. Bot tokens are encrypted in the database and are never shown in logs, API responses, or to other users. Only you can view or regenerate your token from the bot settings.'],
                ['What happens if my bot token is compromised?','Immediately go to @BotFather on Telegram and use /revoke to invalidate the token. Then generate a new token and update it in your BotHost Pro bot settings. The old token will stop working immediately.'],
                ['Can BotHost Pro staff see my bot commands?', 'Platform staff have access to system-level data for support and maintenance purposes, but bot commands and user data are treated as private. Staff access is logged and limited to what is necessary to resolve issues.'],
                ['Is the connection to BotHost Pro encrypted?','Yes. All connections to the BotHost Pro dashboard use HTTPS/TLS encryption. Data in transit between the platform and Telegram is also encrypted using Telegram\'s API.'],
                ['How do I report a security vulnerability?',  'Contact support immediately with details of the vulnerability. Do not disclose vulnerabilities publicly before the team has had a chance to investigate and patch the issue.'],
            ],
        ],
    ];
    @endphp

    @foreach($faqs as $section)
    <div
        id="{{ $section['id'] }}"
        class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 scroll-mt-24"
        x-show="search.trim() === '' || {{ collect($section['items'])->map(fn($i) => "matches('" . addslashes($i[0]) . " " . addslashes($i[1]) . "')") ->join(' || ') }}"
    >
        {{-- Section header --}}
        <div class="mb-4 flex items-center gap-2.5">
            <div class="grid h-8 w-8 shrink-0 place-items-center rounded-xl" style="background-color:{{ $section['color'] }}18">
                <svg style="height:14px;width:14px;color:{{ $section['color'] }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $section['icon'] }}"/></svg>
            </div>
            <p class="text-sm font-black text-[#F8FAFC]">{{ $section['title'] }}</p>
        </div>

        {{-- FAQ accordion items --}}
        <div class="space-y-1.5">
            @foreach($section['items'] as $idx => $item)
            @php $uid = $section['id'] . '-' . $idx; @endphp
            <div
                x-show="matches('{{ addslashes($item[0]) }} {{ addslashes($item[1]) }}')"
                class="rounded-xl border border-[#1B172B] bg-[#0B0918] overflow-hidden"
            >
                <button
                    @click="toggle('{{ $uid }}')"
                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-xs font-black text-[#A1A1AA] transition hover:text-[#F8FAFC]"
                >
                    <span>{{ $item[0] }}</span>
                    <svg
                        :class="open === '{{ $uid }}' ? 'rotate-180' : ''"
                        class="h-3.5 w-3.5 shrink-0 text-[#52525B] transition-transform duration-200"
                        fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                    ><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </button>
                <div
                    x-show="open === '{{ $uid }}'"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    x-cloak
                    class="border-t border-[#1B172B] px-4 pb-4 pt-3"
                >
                    <p class="text-xs leading-relaxed text-[#94A3B8]">{{ $item[1] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    {{-- ── No results state ── --}}
    <div
        x-show="search.trim() !== '' && !{{ collect($faqs)->flatMap(fn($s) => collect($s['items'])->map(fn($i) => "matches('" . addslashes($i[0]) . " " . addslashes($i[1]) . "')"))->join(' || ') }}"
        x-cloak
        class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5"
    >
        <div class="flex flex-col items-center gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] py-14 text-center">
            <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            </div>
            <div>
                <p class="text-sm font-black text-[#94A3B8]">No results found</p>
                <p class="mt-1 text-xs text-[#52525B]">Try a different search term or browse the <a href="{{ route('help.index') }}" class="text-[#8B5CF6] hover:underline">Help Center</a> for guides.</p>
            </div>
        </div>
    </div>

    {{-- ── Footer CTA ── --}}
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#27213D] bg-[#151225]">
                    <svg class="h-5 w-5 text-[#52525B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.966 8.966 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                </div>
                <div>
                    <p class="text-sm font-black text-[#F8FAFC]">Browse the Help Center</p>
                    <p class="mt-0.5 text-xs text-[#94A3B8]">Detailed guides for every platform feature — from creating bots to setting up webhooks.</p>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a
                    href="{{ route('help.index') }}"
                    class="rounded-xl border border-[#27213D] bg-[#151225] px-5 py-2.5 text-xs font-black text-[#94A3B8] transition hover:border-[#8B5CF6]/40 hover:text-[#8B5CF6]"
                >
                    Go to Help Center
                </a>
                @if (!empty($supportUrl))
                    <a
                        href="{{ $supportUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-xl bg-[#8B5CF6] px-5 py-2.5 text-xs font-black text-white transition hover:bg-[#7C3AED]"
                    >
                        Contact Support
                    </a>
                @endif
            </div>
        </div>
    </div>

</div>

</x-dashboard-layout>
