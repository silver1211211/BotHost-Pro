<x-dashboard-layout title="Help Center">

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
            <h1 class="text-xl font-black text-[#F8FAFC]">Help Center</h1>
            <p class="mt-1 text-sm text-[#71717A]">Quick guides and answers for building Telegram bot workspaces.</p>
        </div>
        <a
            href="{{ route('support.index') }}"
            class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-[#27213D] bg-[#0F0D1A] px-4 py-2 text-xs font-black text-[#71717A] transition hover:border-[#8B5CF6]/40 hover:text-[#8B5CF6]"
        >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
            Contact Support
        </a>
    </div>

    {{-- ── Search ── --}}
    <div class="relative">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#52525B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        <input
            x-model="search"
            type="text"
            placeholder="Search help articles…"
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

    {{-- ── Jump-to section grid ── --}}
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-4">
        <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-[#52525B]">Jump to a topic</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach([
                ['Getting Started',      'getting-started', '#8B5CF6', 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z'],
                ['Creating Bots',        'creating-bots',   '#38BDF8', 'M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18'],
                ['Commands',             'commands',        '#22C55E', 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['Templates',            'templates',       '#A855F7', 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'],
                ['Payments & Invoices',  'payments',        '#F59E0B', 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 21Z'],
                ['Plans & Limits',       'plans',           '#38BDF8', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['Transfers',            'transfers',       '#8B5CF6', 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4'],
                ['Recycle Bin',          'recycle-bin',     '#EF4444', 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
                ['Broadcasts',           'broadcasts',      '#38BDF8', 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                ['Custom Webhooks',      'webhooks',        '#8B5CF6', 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244'],
                ['Storage',              'storage',         '#F59E0B', 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 2.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125'],
                ['Logs & Errors',        'logs',            '#EF4444', 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776'],
                ['Account & Security',   'account',         '#22C55E', 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
            ] as [$label, $anchor, $color, $icon])
            <a
                href="#{{ $anchor }}"
                class="group flex items-center gap-2.5 rounded-xl border border-[#1B172B] bg-[#0B0918] px-3 py-2.5 text-xs font-black text-[#71717A] transition hover:border-[#27213D] hover:text-[#A1A1AA]"
            >
                <span class="shrink-0 grid h-6 w-6 place-items-center rounded-lg transition" style="background-color:{{ $color }}18">
                    <svg style="height:11px;width:11px;color:{{ $color }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </span>
                <span class="leading-tight">{{ $label }}</span>
            </a>
            @endforeach
        </div>
    </div>

    {{-- ── Help categories ── --}}
    @php
    $categories = [
        [
            'id'    => 'getting-started',
            'title' => 'Getting Started',
            'color' => '#8B5CF6',
            'icon'  => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
            'items' => [
                ['What is BotHost Pro?',                 'BotHost Pro is a platform for creating and hosting Telegram bots with a Node.js runtime. You write commands, BotHost Pro keeps your bot alive, handles delivery, and provides logs — all from one dashboard.'],
                ['How do I get started?',               'Create an account, go to My Bots and click Create Bot, paste your Telegram bot token (from @BotFather), then open your bot workspace and start adding commands.'],
                ['What do I need before creating a bot?','You need a Telegram account and a bot token. Get one by messaging @BotFather on Telegram, sending /newbot, choosing a name and username, then copying the token it gives you.'],
                ['Is BotHost Pro free to use?',         'There is a free tier with limits on bots, commands, and storage. Paid plans unlock higher limits. Check the Plans page for a full comparison.'],
                ['Where do I find my dashboard?',       'After logging in you land on the dashboard automatically. Use the left sidebar to navigate between Bots, Templates, Broadcasts, and other sections.'],
            ],
        ],
        [
            'id'    => 'creating-bots',
            'title' => 'Creating Bots',
            'color' => '#38BDF8',
            'icon'  => 'M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18',
            'items' => [
                ['How do I create a new bot?',          'Click Create Bot from the My Bots page, enter a display name, and paste your Telegram bot token. BotHost Pro will verify the token, create the workspace, and take you to the bot editor.'],
                ['Where do I get a bot token?',         'Open Telegram, search for @BotFather, send /newbot, follow the prompts to choose a name and username, and copy the token BotFather sends back. Never share this token publicly.'],
                ['Can I have multiple bots?',           'Yes. The number of bots you can create depends on your plan. Free accounts can have one bot; paid plans allow more. Check the Plans page for your current limit.'],
                ['What happens after I create a bot?',  'You are taken to the bot workspace where you can add commands, set a description, configure settings, and send test broadcasts. The bot starts running automatically once the token is validated.'],
                ['Can I rename a bot?',                 'Yes. Open the bot workspace, go to Settings, and update the display name. The Telegram username is set through @BotFather and cannot be changed from BotHost Pro.'],
            ],
        ],
        [
            'id'    => 'commands',
            'title' => 'Commands',
            'color' => '#22C55E',
            'icon'  => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'items' => [
                ['How do I add a command?',             'Open your bot workspace, go to the Commands tab, and click Add Command. Enter the command trigger (e.g. /start), write the response text or code, and save. The command is live immediately.'],
                ['What types of commands can I create?','You can create text response commands, code-execution commands using Node.js, and conditional logic commands. The editor supports plain text, variables, and JavaScript.'],
                ['How do I test a command?',            'After saving, open your bot in Telegram and send the command trigger. The response will appear instantly if the bot is running. You can also check the Logs tab for execution details.'],
                ['Can I use variables in responses?',   'Yes. Use double-curly-brace placeholders like {{username}} or {{message}} in your response text. These are filled in at runtime with data from the incoming Telegram message.'],
                ['Is there a limit on commands?',       'Yes, each plan has a command limit per bot. You can see your current usage and limit in the bot workspace header. Upgrade your plan to add more commands.'],
                ['How do I delete a command?',          'Open the Commands tab, click the three-dot menu on the command you want to remove, and select Delete. Deletion is immediate and cannot be undone.'],
            ],
        ],
        [
            'id'    => 'templates',
            'title' => 'Templates Marketplace',
            'color' => '#A855F7',
            'icon'  => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
            'items' => [
                ['What is the Templates Marketplace?',  'The marketplace is a library of pre-built bot command sets published by the community and the BotHost Pro team. You can browse, preview, and import templates directly into your bot workspaces.'],
                ['Are templates free?',                 'Some templates are free and some are paid. Paid templates are purchased with your account wallet. The price is listed on each template card before you import.'],
                ['How do I import a template?',         'Browse the marketplace, click a template to preview its commands, then click Import. Choose which bot workspace to import into, and the commands are added immediately.'],
                ['Can I publish my own template?',      'Yes. Open a bot workspace, go to Settings, and look for the Publish as Template option. Fill in a title, description, and price, then submit for review.'],
                ['What happens after I import a template?', 'All commands from the template are copied into your chosen bot workspace. You own the copy and can edit, rename, or delete any command freely. Updates to the original template are not pushed automatically.'],
            ],
        ],
        [
            'id'    => 'payments',
            'title' => 'Payments & Invoices',
            'color' => '#F59E0B',
            'icon'  => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 21Z',
            'items' => [
                ['How do I deposit funds?',             'Go to Billing, select Deposit, choose an amount, and complete payment via OxaPay. Funds are credited to your account wallet and can be used for templates, plan upgrades, or other platform purchases.'],
                ['How do I upgrade my plan?',           'Go to Billing or click Upgrade Plan from the dashboard. Select the plan you want, confirm the charge from your wallet balance, and the upgrade takes effect immediately.'],
                ['Where can I see my invoices?',        'Open the Billing section and scroll to the Invoice History. All plan purchases and template payments are listed with date, amount, and status.'],
                ['What payment methods are accepted?',  'BotHost Pro uses OxaPay for deposits, which supports multiple cryptocurrencies. Once funds are in your wallet, all in-platform purchases deduct from the wallet balance directly.'],
                ['What happens if my wallet runs out?', 'If your wallet balance is insufficient for a renewal or purchase, the action is blocked and you are prompted to deposit more funds. Your active bots continue running until the plan expires.'],
            ],
        ],
        [
            'id'    => 'plans',
            'title' => 'Plans & Limits',
            'color' => '#38BDF8',
            'icon'  => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            'items' => [
                ['What limits does the free plan have?','The free plan allows one bot, a limited number of commands per bot, and limited storage. Broadcasts and some advanced features may require a paid plan.'],
                ['How do I see my current usage?',      'The dashboard overview shows bot count, command count, and storage used at a glance. Individual bot workspaces show per-bot command usage in the header bar.'],
                ['Can I downgrade my plan?',            'Plan downgrades are not automatic. Contact support to discuss downgrade options. Note that if your current usage exceeds the lower plan limits, you will need to reduce bots or commands first.'],
                ['Do plans renew automatically?',       'Plans renew from your wallet balance at the end of each billing period. Make sure your wallet has enough funds before the renewal date to avoid interruption.'],
                ['What happens when my plan expires?',  'Your bots continue to exist but may be paused if the plan limits are exceeded. You can still log in, view data, and renew. Expired accounts cannot create new bots until renewed.'],
            ],
        ],
        [
            'id'    => 'transfers',
            'title' => 'Transfers',
            'color' => '#8B5CF6',
            'icon'  => 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4',
            'items' => [
                ['What is a bot transfer?',             'A bot transfer copies a self-coded bot workspace to another BotHost Pro account. The receiver can import without a token and connect one later; your token is never shared.'],
                ['How do I transfer a bot?',            'Go to your bot settings, find the Transfer Bot option, enter the email address of the receiving account, and confirm. The receiver sees the transfer in their Transfers page and can import it with their own token.'],
                ['Is the original bot affected?',       'No. Transfers create a copy. Your original bot and its token remain completely intact and continue working as normal after the transfer.'],
                ['Can the receiver edit the workspace?','Yes. Once the receiver imports the transferred workspace under their own token, they own the copy fully and can edit, rename, or delete any part of it.'],
                ['Can I cancel a pending transfer?',    'Yes. Open the Transfers page, go to the Sent tab, find the pending transfer, and cancel it before the receiver accepts.'],
            ],
        ],
        [
            'id'    => 'recycle-bin',
            'title' => 'Recycle Bin',
            'color' => '#EF4444',
            'icon'  => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
            'items' => [
                ['Where do deleted bots go?',           'When you delete a bot, it moves to the Recycle Bin instead of being permanently removed. It stays there until you restore it or the retention period expires.'],
                ['How do I restore a deleted bot?',     'Open the Recycle Bin from the sidebar, find the bot you want to restore, and click Restore. The bot is moved back to your My Bots list with all commands intact.'],
                ['How long before a bot is permanently deleted?', 'Bots in the Recycle Bin are permanently deleted after the retention period set by the platform (typically 30 days). Items expiring soon are flagged in the Expiring Soon tab.'],
                ['Can I empty the Recycle Bin manually?','Yes. Use the Empty Recycle Bin button at the top of the Recycle Bin page. You will be asked to confirm before anything is permanently deleted.'],
                ['Is there any way to recover a permanently deleted bot?', 'No. Once a bot passes the retention period or is manually emptied from the Recycle Bin, it cannot be recovered. Restore bots before the expiry date.'],
            ],
        ],
        [
            'id'    => 'broadcasts',
            'title' => 'Broadcasts',
            'color' => '#38BDF8',
            'icon'  => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
            'items' => [
                ['What is a broadcast?',                'A broadcast sends a message to multiple Telegram users who have interacted with your bot. It is useful for announcements, updates, or promotions.'],
                ['Who receives a broadcast?',           'Broadcasts are sent to users in your bot subscriber list — people who have previously messaged your bot. You can filter recipients by segment if segments are configured.'],
                ['Is there a limit on broadcasts?',     'Yes, broadcast limits depend on your plan. Higher plans allow more broadcasts per day and larger recipient lists. Check the Plans page for specifics.'],
                ['Can I schedule a broadcast?',         'Broadcast scheduling may be available on higher plans. Check the Broadcasts section of your bot workspace for scheduling options.'],
                ['What message formats are supported?', 'Broadcasts support plain text and basic Telegram markdown (bold, italic, links). Image and media broadcasts may be available depending on your plan.'],
            ],
        ],
        [
            'id'    => 'webhooks',
            'title' => 'Custom Webhooks',
            'color' => '#8B5CF6',
            'icon'  => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244',
            'items' => [
                ['What is the Custom Webhook feature?', 'Custom Webhooks let you send an HTTPS POST request to an external URL whenever a specific event happens in your bot — such as a new message, command trigger, or user join. Your own server receives the data and can act on it.'],
                ['How do I set up a webhook?',          'Open your bot workspace, go to Settings, find the Custom Webhook section, enter your endpoint URL, and save. BotHost Pro will deliver event payloads to that URL in real time.'],
                ['What events can trigger a webhook?',  'Supported trigger events include incoming messages, command matches, new subscribers, and broadcast completions. See the Webhook Documentation for the full event payload reference.'],
                ['Where can I find the webhook docs?',  'Visit the Webhook Documentation page from your bot workspace settings or from the main navigation. It covers payload format, security headers, retry logic, and example code.'],
                ['What if my endpoint is down?',        'BotHost Pro retries failed deliveries with exponential backoff. You can see all delivery attempts in the Delivery Logs section of your webhook settings.'],
            ],
        ],
        [
            'id'    => 'storage',
            'title' => 'Storage',
            'color' => '#F59E0B',
            'icon'  => 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 2.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125',
            'items' => [
                ['What counts toward storage?',         'Storage includes uploaded project files, assets attached to commands, and any files associated with your bot workspace. Bot configuration and command text do not count toward storage.'],
                ['How do I see my storage usage?',      'The dashboard overview shows total storage used across all bots. Individual bot workspaces may show per-bot storage in their settings or files section.'],
                ['What happens when I reach the limit?','You will not be able to upload new files until you delete existing ones or upgrade to a plan with more storage. Existing files and commands continue to work.'],
                ['How do I free up storage?',           'Open the Files section of a bot workspace, review uploaded files, and delete anything no longer needed. Deleting a bot also removes its associated files.'],
                ['What file types are supported?',      'Supported file types vary by plan and use case. Common types include images, JSON configs, and script files. Contact support if you need to use a specific file type.'],
            ],
        ],
        [
            'id'    => 'logs',
            'title' => 'Logs & Errors',
            'color' => '#EF4444',
            'icon'  => 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776',
            'items' => [
                ['Where do I see my bot logs?',         'Open your bot workspace and go to the Logs tab. You will see a real-time feed of command executions, errors, and runtime events with timestamps.'],
                ['What does a red log entry mean?',     'A red entry indicates an error — usually a failed command execution, syntax error in your code, or an unhandled exception. Click the entry to see the full error message and stack trace.'],
                ['My bot stopped responding. What do I check?', 'Check the Logs tab for recent errors. Common causes are syntax errors in a command, a missing variable, or a failed external API call. Fix the issue and the bot resumes automatically.'],
                ['How long are logs kept?',             'Log retention depends on your plan. Older log entries are pruned automatically. Download or copy important log data if you need to keep it long-term.'],
                ['What are webhook delivery logs?',     'Delivery logs track every HTTPS POST attempt BotHost Pro makes to your Custom Webhook endpoint — including the payload sent, the response received, and the HTTP status code.'],
            ],
        ],
        [
            'id'    => 'account',
            'title' => 'Account & Security',
            'color' => '#22C55E',
            'icon'  => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
            'items' => [
                ['How do I change my password?',        'Go to Profile from the sidebar, scroll to the Security section, and enter your current password followed by the new password. Save to apply the change.'],
                ['How do I update my email address?',   'Open Profile and update the email field. You may need to verify the new address before it takes effect. Check your inbox for a verification link.'],
                ['How do I enable two-factor authentication?', 'Two-factor authentication settings are in the Profile section under Security. Follow the prompts to link an authenticator app and save your backup codes.'],
                ['Is my bot token secure?',             'Yes. Bot tokens are encrypted at rest in the database and are never exposed in the UI, logs, or API responses after the initial save. Only you can see or regenerate the token.'],
                ['How do I delete my account?',         'Account deletion is handled by contacting support. Before deletion, make sure to remove or transfer any bots you want to keep, as account deletion is permanent.'],
                ['What data does BotHost Pro store?',   'BotHost Pro stores your account details, bot configurations, commands, logs, and payment history. Message content from your bot users is processed in transit but not stored long-term by the platform.'],
            ],
        ],
    ];
    @endphp

    @foreach($categories as $cat)
    @php
        $visibleItems = array_filter($cat['items'], function($item) {
            return true; // filtering is done via Alpine x-show
        });
    @endphp
    <div
        id="{{ $cat['id'] }}"
        class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5 scroll-mt-24"
        x-show="search.trim() === '' || {{ collect($cat['items'])->map(fn($i) => "matches('" . addslashes($i[0]) . " " . addslashes($i[1]) . "')") ->join(' || ') }}"
    >
        {{-- Category header --}}
        <div class="mb-4 flex items-center gap-2.5">
            <div class="grid h-8 w-8 shrink-0 place-items-center rounded-xl" style="background-color:{{ $cat['color'] }}18">
                <svg style="height:14px;width:14px;color:{{ $cat['color'] }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $cat['icon'] }}"/></svg>
            </div>
            <p class="text-sm font-black text-[#F8FAFC]">{{ $cat['title'] }}</p>
        </div>

        {{-- Accordion items --}}
        <div class="space-y-1.5">
            @foreach($cat['items'] as $idx => $item)
            @php $uid = $cat['id'] . '-' . $idx; @endphp
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
                    <p class="text-xs leading-relaxed text-[#71717A]">{{ $item[1] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    {{-- ── No results state ── --}}
    <div
        x-show="search.trim() !== '' && !{{ collect($categories)->flatMap(fn($c) => collect($c['items'])->map(fn($i) => "matches('" . addslashes($i[0]) . " " . addslashes($i[1]) . "')"))->join(' || ') }}"
        x-cloak
        class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-5"
    >
        <div class="flex flex-col items-center gap-3 rounded-xl border border-[#1B172B] bg-[#0B0918] py-14 text-center">
            <div class="grid h-12 w-12 place-items-center rounded-2xl border border-[#27213D] bg-[#151225] text-[#52525B]">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            </div>
            <div>
                <p class="text-sm font-black text-[#71717A]">No results found</p>
                <p class="mt-1 text-xs text-[#52525B]">Try a different search term or <a href="{{ route('support.index') }}" class="text-[#8B5CF6] hover:underline">contact support</a> for help.</p>
            </div>
        </div>
    </div>

    {{-- ── Footer CTA ── --}}
    <div class="rounded-2xl border border-[#27213D] bg-[#0F0D1A] p-6">
        <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:text-left">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border border-[#8B5CF6]/20 bg-[#8B5CF6]/8">
                <svg class="h-6 w-6 text-[#8B5CF6]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-black text-[#F8FAFC]">Still need help?</p>
                <p class="mt-1 text-xs text-[#71717A]">Check the Support page for FAQs or reach out to the support team directly.</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a
                    href="{{ route('support.index') }}"
                    class="rounded-xl bg-[#8B5CF6] px-5 py-2.5 text-xs font-black text-white transition hover:bg-[#7C3AED]"
                >
                    Go to Support
                </a>
                @if (!empty($supportUrl))
                    <a
                        href="{{ $supportUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-xl border border-[#8B5CF6]/40 bg-[#8B5CF6]/8 px-5 py-2.5 text-xs font-black text-[#A855F7] transition hover:bg-[#8B5CF6]/15 hover:border-[#8B5CF6]/60"
                    >
                        Contact Support
                    </a>
                @endif
            </div>
        </div>
    </div>

</div>

</x-dashboard-layout>
