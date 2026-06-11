<?php

namespace Database\Seeders;

use App\Models\Bot;
use App\Models\BotCommand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InvestBotDollarsSeeder extends Seeder
{
    public function run(): void
    {
        $bot = Bot::where('name', 'Invest Bot Dollars')->first();

        if (! $bot) {
            // Create bot if missing
            $owner = \App\Models\User::where('role', 'admin')->orderBy('id')->first()
                ?? \App\Models\User::orderBy('id')->first();
            if (! $owner) { $this->command->error('No users found.'); return; }
            $bot = new Bot();
            $bot->user_id  = $owner->id;
            $bot->name     = 'Invest Bot Dollars';
            $bot->slug     = 'invest-bot-dollars-' . Str::random(6);
            $bot->language = 'javascript';
            $bot->status   = 'stopped';
            $bot->save();
        }

        $this->command->info("Bot #{$bot->id}: {$bot->name}");

        // Deactivate old menu aliases that are no longer in the bottom keyboard
        BotCommand::where('bot_id', $bot->id)
            ->whereIn('command_name', [
                '💼 Balance', '💳 Deposit', '📊 My Investments',
                '💰 Withdraw', '📜 History',
            ])
            ->update(['status' => 'inactive']);

        foreach ($this->commands() as $def) {
            BotCommand::updateOrCreate(
                ['bot_id' => $bot->id, 'command_name' => $def['command_name']],
                [
                    'display_name' => $def['display_name'],
                    'trigger_type' => $def['trigger_type'],
                    'code'         => $def['code'],
                    'response_type'=> 'code',
                    'status'       => 'active',
                    'is_pinned'    => $def['is_pinned'] ?? false,
                ]
            );
            $this->command->line("  ✓ {$def['display_name']}");
        }

        $this->command->newLine();
        $this->command->info('✅ Done. Set your bot token in the dashboard to activate.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function commands(): array
    {
        return [

// ═══════════════════════════════════════════ /start ═══════════════════════
[
'command_name' => '/start',
'display_name' => 'Start',
'trigger_type' => 'slash',
'is_pinned'    => true,
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId   = String(user.id);
const currency = await getBotData('investment_currency', 'USDT');

// [1] Owner is never banned — always clear it on entry
if (userId === OWNER_ID) await setUserData('banned', false);

// [3] Ban check — blocked users get no further access
if ((await getUserData('banned', false)) === true) {
  await replyHTML('🚫 You are banned from this bot.');
  return;
}

// [8][14] Last active time + session — update every visit
await setUserData('last_active_at', now());

// [5][6][7][9] Profile sync — always write latest Telegram data
await setUserData('tg_username',   user.username   || null);
await setUserData('tg_first_name', user.first_name || null);
if (user.language_code) await setUserData('language_code', user.language_code);

// [1][2] Detect new vs returning user — joined_at is the registration flag
const joinedAt  = await getUserData('joined_at', null);
const isNewUser = !joinedAt;
let   bonusAdded = 0;

if (isNewUser) {
  // [1] First-time registration — initialise all account fields exactly once
  await setUserData('joined_at', now());
  await incrementBotData('total_users', 1);

  await setUserData('account_status',      'active');        // [12] defaults to active
  await setUserData('kyc_status',          'not_required'); // [11] no KYC gate yet
  await setUserData('risk_flag',           false);          // [13] clean by default
  await setUserData('terms_accepted',      true);           // [18] auto-accept (no legal screen yet)
  await setUserData('terms_accepted_at',   now());
  await setUserData('privacy_accepted',    true);           // [19] auto-accept
  await setUserData('privacy_accepted_at', now());
  await setUserData('onboarding_step',     'completed');    // [17] no onboarding screen yet
  await setUserData('timezone',            null);           // [10] Telegram provides no timezone

  // [16] Welcome bonus — admin sets 'welcome_bonus' via bot data (default 0 = disabled)
  const bonusAmt = toNumber(await getBotData('welcome_bonus', 0), 0);
  if (bonusAmt > 0) {
    await addBalance(bonusAmt);
    await setUserData('welcome_bonus_given', true);
    await addTransaction(userId, {
      id: generateId('tx'), type: 'admin_credit', amount: bonusAmt,
      currency, status: 'success', date: now(), note: 'Welcome bonus',
    });
    bonusAdded = bonusAmt;
  } else {
    await setUserData('welcome_bonus_given', false);
  }
}

// [15] Referral source — save once at first visit
const refArg = args[0] ? String(args[0]).trim() : null;
if (refArg && refArg !== userId && !(await getUserData('referred_by', null))) {
  await setUserData('referred_by',     refArg);
  if (isNewUser) await setUserData('referral_source', `ref_${refArg}`);
  const existingList = await getUserDataFor(refArg, 'referral_list', []);
  const rList = Array.isArray(existingList) ? [...existingList] : [];
  rList.push({ user_id: userId, first_name: user.first_name || 'User', username: user.username || null, joined_at: now() });
  await setUserDataFor(refArg, 'referral_list', rList.slice(-50));
} else if (isNewUser && !(await getUserData('referral_source', null))) {
  await setUserData('referral_source', 'direct'); // [15] direct entry
}

// [20] Build welcome message — differentiate new vs returning user [2]
const firstName = user.first_name || 'there';
const botName   = bot.name || 'Invest Bot Dollars';
let   welcomeText = isNewUser
  ? `👋 <b>Welcome to ${botName}, ${escapeHTML(firstName)}!</b> 🎉\n\nYour account is ready.`
  : `👋 <b>Welcome back, ${escapeHTML(firstName)}!</b>`;
if (bonusAdded > 0) {
  welcomeText += `\n\n🎁 <b>Welcome Bonus:</b> <code>${formatMoney(bonusAdded, currency)}</code> added to your account!`;
}

const menu   = bottomMenu([
  ['📈 Invest', '👛 My Wallet', '💼 My Account'],
  ['🔗 Referral', '🎁 Daily Reward', '🆘 Support'],
]);
const imgUrl = await getBotData('welcome_image_url', null);
if (imgUrl) {
  const r = await sendPhoto(chat.id, imgUrl, { caption: welcomeText, parse_mode: 'HTML', ...menu });
  if (!r.ok) await replyHTML(welcomeText, menu);
} else {
  await replyHTML(welcomeText, menu);
}
JS,
],

// ═══════════════════════════════════════ /main_menu ═══════════════════════
[
'command_name' => '/main_menu',
'display_name' => 'Main Menu',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData(); await clearCommandFlow();
await setUserData('admin_state', null);
const menu = bottomMenu([
  ['📈 Invest', '👛 My Wallet', '💼 My Account'],
  ['🔗 Referral', '🎁 Daily Reward', '🆘 Support'],
]);
await replyHTML('📋 <b>Main Menu</b>\n\nChoose an option below.', menu);
JS,
],

// ═══════════════════════════════════════ 📈 Invest (text) ═════════════════
[
'command_name' => '📈 Invest',
'display_name' => 'Invest',
'trigger_type' => 'text',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData(); await clearCommandFlow();
await setUserData('admin_state', null);

const currency = await getBotData('investment_currency', 'USDT');
const defaultPlans = [
  { id:'starter', name:'Starter Plan', min:10,   max:99,      percent:10, duration_hours:24 },
  { id:'growth',  name:'Growth Plan',  min:100,  max:499,     percent:20, duration_hours:48 },
  { id:'premium', name:'Premium Plan', min:500,  max:999,     percent:35, duration_hours:72 },
  { id:'elite',   name:'Elite Plan',   min:1000, max:1000000, percent:50, duration_hours:96 },
];
const plansRaw = await getBotData('investment_plans', null);
const plans = Array.isArray(plansRaw) ? plansRaw : defaultPlans;

let planText = `📈 <b>Investment Plans</b>\n\n`;
for (const p of plans) {
  planText += `<b>${p.name}</b>\n`;
  planText += `  Range: <code>${formatMoney(p.min, currency)} – ${formatMoney(p.max, currency)}</code>\n`;
  planText += `  Return: ${p.percent}% in ${p.duration_hours}h\n\n`;
}
planText += `<i>Type the amount you want to invest:</i>`;

await setState('awaiting_invest_amount');
await setStateData('currency', currency);
await setStateData('plans', plans);

const kb = inlineMenu([
  [button('📊 My Investments', '/my_investments')],
  [button('⬅️ Back', '/main_menu')],
]);
const imgUrl = await getBotData('investment_image_url', null);
if (imgUrl) {
  try { await sendPhoto(chat.id, imgUrl, { caption: planText, parse_mode: 'HTML', ...kb }); }
  catch (e) { await replyHTML(planText, kb); }
} else {
  await replyHTML(planText, kb);
}
JS,
],

// ═══════════════════════════════════════ /invest (slash, callbacks) ════════
[
'command_name' => '/invest',
'display_name' => 'Invest Slash',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;

const currency = await getBotData('investment_currency', 'USDT');
const cbData = telegram.callback_data || '';
const action = args[0] || '';

if (cbData.startsWith('/invest')) {
  if (action === 'cancel') {
    await clearState(); await clearStateData();
    await replyHTML('❌ Investment cancelled.', bottomMenu([
      ['📈 Invest', '👛 My Wallet', '💼 My Account'],
      ['🔗 Referral', '🎁 Daily Reward', '🆘 Support'],
    ]));
    return;
  }
  if (action === 'confirm') {
    const pending = await getStateData('pending_invest', null);
    if (!pending || typeof pending !== 'object') {
      await replyHTML('⚠️ Session expired. Tap 📈 Invest to start again.');
      await clearState(); await clearStateData();
      return;
    }
    const { amount, plan, profit, totalReturn, maturesAt } = pending;
    const currentBalance = await getBalance();
    if (currentBalance < amount) {
      await replyHTML(`❌ <b>Insufficient Balance</b>\n\nRequired: <code>${formatMoney(amount, currency)}</code>\nAvailable: <code>${formatMoney(currentBalance, currency)}</code>`);
      await clearState(); await clearStateData();
      return;
    }
    const investId = generateId('inv');
    const startedAt = now();
    const investment = { id: investId, amount, plan_id: plan.id, plan_name: plan.name, percent: plan.percent, profit, total_return: totalReturn, started_at: startedAt, matures_at: maturesAt, status: 'active' };
    await removeBalance(amount);
    const investments = await getUserData('active_investments', []);
    const invList = Array.isArray(investments) ? [...investments] : [];
    invList.push(investment);
    await setUserData('active_investments', invList);
    const prevInv = toNumber(await getUserData('total_invested', 0), 0);
    await setUserData('total_invested', prevInv + amount);
    await incrementBotData('total_invested', amount);
    await incrementBotData('total_active_investments', 1);
    await addTransaction(userId, { id: generateId('tx'), type: 'investment', amount, currency, status: 'success', date: startedAt, note: `${plan.name} — ${plan.percent}% / ${plan.duration_hours}h` });
    await clearState(); await clearStateData();
    await replyHTML(`✅ <b>Investment Started</b>\n\n• Plan: ${plan.name}\n• Amount: <code>${formatMoney(amount, currency)}</code>\n• Expected Profit: <code>${formatMoney(profit, currency)}</code>\n• Total Return: <code>${formatMoney(totalReturn, currency)}</code>\n• Matures At: ${maturesAt.slice(0,16).replace('T',' ')} UTC\n\nUse 📊 My Investments to track progress.`);
    return;
  }
}
// Direct /invest — show plans (same as text menu)
await runCommand('/invest');
JS,
],

// ════════════════════════════════════ 👛 My Wallet (text) ════════════════
[
'command_name' => '👛 My Wallet',
'display_name' => 'My Wallet',
'trigger_type' => 'text',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData(); await clearCommandFlow();
await setUserData('admin_state', null);

const wallet = await getUserData('wallet', null);
const walletLine = wallet ? `\n• <b>Current:</b> <code>${wallet}</code>` : '\n• <b>No wallet saved yet.</b>';
const text = `👛 <b>My Wallet</b>${walletLine}`;

const kb = inlineMenu([
  [button('✏️ Set Wallet', '/setwallet')],
  [button('⬅️ Back', '/main_menu')],
]);
await replyHTML(text, kb);
JS,
],

// ════════════════════════════════════ /setwallet (slash) ═════════════════
[
'command_name' => '/setwallet',
'display_name' => 'Set Wallet',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;

// Only set state; DM handler will process the actual wallet input
await clearState(); await clearStateData();
await setState('awaiting_wallet');
const currency = await getBotData('investment_currency', 'USDT');
await replyHTML(`✏️ <b>Set Wallet</b>\n\nEnter your ${currency} wallet address or payment email:\n\n/cancel to go back.`);
JS,
],

// ════════════════════════════════════ 💼 My Account (text) ══════════════
[
'command_name' => '💼 My Account',
'display_name' => 'My Account',
'trigger_type' => 'text',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData(); await clearCommandFlow();
await setUserData('admin_state', null);

const currency         = await getBotData('investment_currency', 'USDT');
const balance          = await getBalance();
const totalDeposited   = toNumber(await getUserData('total_deposited', 0), 0);
const totalInvested    = toNumber(await getUserData('total_invested', 0), 0);
const totalProfit      = toNumber(await getUserData('total_profit_claimed', 0), 0);
const totalWithdrawn   = toNumber(await getUserData('total_withdrawn', 0), 0);
const activeInvestments= await getUserData('active_investments', []);
const activeCount      = Array.isArray(activeInvestments) ? activeInvestments.filter(i => i.status === 'active').length : 0;
const acctStatus       = await getUserData('account_status', 'active'); // [12][20]
const acctStatusLine   = acctStatus === 'active' ? '✅ Active' : String(acctStatus || 'Active');

const text = `💼 <b>My Account</b>

• <b>Status:</b>         ${acctStatusLine}
• <b>Balance:</b>        <code>${formatMoney(balance, currency)}</code>
• <b>Deposited:</b>      <code>${formatMoney(totalDeposited, currency)}</code>
• <b>Invested:</b>       <code>${formatMoney(totalInvested, currency)}</code>
• <b>Profit Claimed:</b> <code>${formatMoney(totalProfit, currency)}</code>
• <b>Withdrawn:</b>      <code>${formatMoney(totalWithdrawn, currency)}</code>
• <b>Active Investments:</b> ${activeCount}`;

const kb = inlineMenu([
  [button('📜 History', '/history'), button('💰 Withdraw', '/withdraw'), button('💳 Deposit', '/deposit')],
  [button('⬅️ Back', '/main_menu')],
]);

const imgUrl = await getBotData('balance_image_url', null);
if (imgUrl) {
  try { await sendPhoto(chat.id, imgUrl, { caption: text, parse_mode: 'HTML', ...kb }); }
  catch (e) { await replyHTML(text, kb); }
} else {
  await replyHTML(text, kb);
}
JS,
],

// ════════════════════════════════════ 🔗 Referral (text) ════════════════
[
'command_name' => '🔗 Referral',
'display_name' => 'Referral',
'trigger_type' => 'text',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData(); await clearCommandFlow();
await setUserData('admin_state', null);

const currency = await getBotData('investment_currency', 'USDT');
const refReward = toNumber(await getBotData('referral_reward', 1), 1);
const referrals = toNumber(await getUserData('referrals', 0), 0);
const botUsername = bot.username || '';
const refLink = makeRefLink(botUsername, userId);

const text = `🔗 <b>Referral Program</b>

• <b>Your Link:</b>
<code>${refLink}</code>

• <b>Total Referrals:</b> ${referrals}
• <b>Reward per referral deposit:</b> <code>${formatMoney(refReward, currency)}</code>

Share your link and earn when referrals make their first deposit.`;

const kb = inlineMenu([
  [button('📋 Referral History', '/refhistory')],
  [button('⬅️ Back', '/main_menu')],
]);
await replyHTML(text, kb);
JS,
],

// ════════════════════════════════════ /refhistory ════════════════════════
[
'command_name' => '/refhistory',
'display_name' => 'Referral History',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData();

const list = await getUserData('referral_list', []);
const refs = Array.isArray(list) ? list.slice(-10).reverse() : [];

if (refs.length === 0) {
  await replyHTML(`📋 <b>Referral History</b>\n\nNo referrals yet.\n\nShare your referral link to invite friends.`,
    inlineMenu([[button('⬅️ Back', '/referral')]]));
  return;
}

let text = `📋 <b>Your Last ${refs.length} Referrals</b>\n\n`;
for (const r of refs) {
  const name = r.first_name || 'User';
  const un = r.username ? ` (@${r.username})` : '';
  const date = String(r.joined_at || '').slice(0, 16).replace('T', ' ');
  text += `• ${name}${un}\n  <code>${date} UTC</code>\n\n`;
}

await replyHTML(text, inlineMenu([[button('⬅️ Back', '/referral')]]));
JS,
],

// ════════════════════════════════════ /referral (slash alias) ════════════
[
'command_name' => '/referral',
'display_name' => 'Referral Slash',
'trigger_type' => 'slash',
'code'         => "await runCommand('🔗 Referral');",
],

// ════════════════════════════════════ 🎁 Daily Reward (text) ════════════
[
'command_name' => '🎁 Daily Reward',
'display_name' => 'Daily Reward',
'trigger_type' => 'text',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData(); await clearCommandFlow();
await setUserData('admin_state', null);

const currency = await getBotData('investment_currency', 'USDT');
const rewardAmount = toNumber(await getBotData('daily_reward_amount', 1), 1);
const enabled = await getBotData('daily_reward_enabled', true);

if (!enabled) {
  await replyHTML(`🎁 <b>Daily Reward</b>\n\nThe daily reward is currently disabled.`);
  return;
}
if (rewardAmount <= 0) {
  await replyHTML(`🎁 <b>Daily Reward</b>\n\nNo reward configured yet. Check back soon.`);
  return;
}

const lastClaim = await getUserData('last_daily_claim', null);
const MS_24H = 24 * 60 * 60 * 1000;
const nowTs = Date.now();

if (lastClaim) {
  const elapsed = nowTs - new Date(lastClaim).getTime();
  if (elapsed < MS_24H) {
    const rem = MS_24H - elapsed;
    const h = Math.floor(rem / 3600000);
    const m = Math.floor((rem % 3600000) / 60000);
    await replyHTML(`🎁 <b>Daily Reward</b>\n\nAlready claimed today!\n\n⏳ Next claim available in: <b>${h}h ${m}m</b>`);
    return;
  }
}

await addBalance(rewardAmount);
await setUserData('last_daily_claim', now());
await addTransaction(userId, {
  id: generateId('tx'), type: 'admin_credit', amount: rewardAmount,
  currency, status: 'success', date: now(), note: 'Daily reward',
});
const newBalance = await getBalance();
await replyHTML(`🎁 <b>Daily Reward Claimed!</b>\n\n• <b>Reward:</b> <code>${formatMoney(rewardAmount, currency)}</code>\n• <b>New Balance:</b> <code>${formatMoney(newBalance, currency)}</code>\n\nCome back in 24 hours for your next reward!`);
JS,
],

// ════════════════════════════════════ 🆘 Support (text) ═════════════════
[
'command_name' => '🆘 Support',
'display_name' => 'Support',
'trigger_type' => 'text',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData(); await clearCommandFlow();
await setUserData('admin_state', null);

const supportUsername = await getBotData('support_username', '@support');
const defaultFaq = `<b>Frequently Asked Questions</b>

<b>Q: How do I deposit?</b>
Tap 💼 My Account → Deposit and follow the instructions.

<b>Q: How do I invest?</b>
Tap 📈 Invest, read the plans, then type the amount you want to invest.

<b>Q: How do I withdraw?</b>
Tap 💼 My Account → Withdraw. Set your wallet first if prompted.

<b>Q: How do referrals work?</b>
Share your link from 🔗 Referral. Earn a reward when your referral deposits.

<b>Q: What is the daily reward?</b>
Tap 🎁 Daily Reward once every 24 hours to claim a small bonus.

<i>Contact: ${supportUsername}</i>`;

const faqText = await getBotData('support_faq', null) || defaultFaq;
const kb = inlineMenu([
  [button('✅ Yes, it helped', '/support yes'), button('❌ No, need help', '/support no')],
]);
await replyHTML(faqText + '\n\n─────────────────\n<i>Did this answer your question?</i>', kb);
JS,
],

// ════════════════════════════════════ /support (slash, callbacks) ════════
[
'command_name' => '/support',
'display_name' => 'Support Slash',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;

const action = args[0] || '';
const cbData = telegram.callback_data || '';

if (action === 'yes' || cbData === '/support yes') {
  await replyHTML('👍 Glad it helped! Tap 🆘 Support anytime you need help again.');
  return;
}

if (action === 'no' || cbData === '/support no') {
  await setState('awaiting_support_message');
  await replyHTML(`📩 <b>Contact Support</b>\n\nPlease send your message below. You can include images for clarification.\n\nOur team will respond as soon as possible.\n\n/cancel to go back.`);
  return;
}

// Fallback: show support section
await runCommand('🆘 Support');
JS,
],

// ════════════════════════════════════ /adminreply (slash) ════════════════
[
'command_name' => '/adminreply',
'display_name' => 'Admin Reply',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID) { await replyHTML('🚫 Admin only.'); return; }

const targetUserId = args[0] || null;
if (!targetUserId) {
  await replyHTML('⚠️ No target user. Use the Reply button from support notifications.');
  return;
}

await setUserData('admin_state', 'admin_support_reply');
await setUserData('admin_target_user', targetUserId);
await replyHTML(`📤 <b>Reply to User</b>\n\nTarget: <code>${targetUserId}</code>\n\nType your reply message (text only) and send:\n\n/cancel to abort.`);
JS,
],

// ════════════════════════════════════ /my_investments (slash) ════════════
[
'command_name' => '/my_investments',
'display_name' => 'My Investments',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData();

const currency = await getBotData('investment_currency', 'USDT');
const investments = await getUserData('active_investments', []);
const list = Array.isArray(investments) ? investments : [];
const active = list.filter(i => i.status === 'active');

const backKb = inlineMenu([[button('⬅️ Back', '/invest')]]);

if (active.length === 0) {
  await replyHTML(`📊 <b>My Investments</b>\n\nNo active investments.\n\nTap 📈 Invest to start.`, backKb);
  return;
}

let text = `📊 <b>Active Investments (${active.length})</b>\n\n`;
let hasMature = false;
for (const inv of active) {
  const isMature = new Date(inv.matures_at) <= new Date();
  if (isMature) hasMature = true;
  const statusStr = isMature ? '✅ Ready to Claim' : `⏳ ${timeLeft(inv.matures_at)}`;
  text += `<b>${inv.plan_name}</b>\n  Amount: <code>${formatMoney(inv.amount, currency)}</code>\n  Return: <code>${formatMoney(inv.total_return, currency)}</code> (${inv.percent}%)\n  ${statusStr}\n\n`;
}

const kb = hasMature
  ? inlineMenu([[button('💰 Claim Matured', '/claim')], [button('⬅️ Back', '/invest')]])
  : backKb;
await replyHTML(text, kb);
JS,
],

// ════════════════════════════════════ /claim ════════════════════════════
[
'command_name' => '/claim',
'display_name' => 'Claim',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData();

const currency = await getBotData('investment_currency', 'USDT');
const investments = await getUserData('active_investments', []);
const list = Array.isArray(investments) ? investments : [];
const matured = list.filter(i => i.status === 'active' && new Date(i.matures_at) <= new Date());

if (matured.length === 0) {
  await replyHTML(`💰 <b>Claim Profits</b>\n\nNo matured investments to claim yet.`,
    inlineMenu([[button('📊 My Investments', '/my_investments')]]));
  return;
}

let totalReceived = 0, totalProfit = 0, summary = '';
for (const inv of matured) { totalReceived += inv.total_return; totalProfit += inv.profit; summary += `• ${inv.plan_name}: <code>${formatMoney(inv.total_return, currency)}</code>\n`; }
const updated = list.map(i => matured.find(m => m.id === i.id) ? { ...i, status: 'claimed' } : i);
await setUserData('active_investments', updated);
await addBalance(totalReceived);
const prevProfit = toNumber(await getUserData('total_profit_claimed', 0), 0);
await setUserData('total_profit_claimed', prevProfit + totalProfit);
await incrementBotData('total_profit_paid', totalProfit);
await incrementBotData('total_matured_investments', matured.length);
for (const inv of matured) {
  await addTransaction(userId, { id: generateId('tx'), type: 'profit', amount: inv.total_return, currency, status: 'success', date: now(), note: `Claimed: ${inv.plan_name}` });
}
const newBalance = await getBalance();
await replyHTML(`✅ <b>Profits Claimed!</b>\n\n${summary}\n• <b>Total Received:</b> <code>${formatMoney(totalReceived, currency)}</code>\n• <b>New Balance:</b> <code>${formatMoney(newBalance, currency)}</code>`);
JS,
],

// ════════════════════════════════════ /deposit ═══════════════════════════
[
'command_name' => '/deposit',
'display_name' => 'Deposit',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;

const cbData = telegram.callback_data || '';

// Check payment status callback
if (cbData === '/deposit check') {
  const trackId = await getUserData('pending_deposit_track_id', null);
  if (!trackId) { await replyHTML('⚠️ No pending deposit found.'); return; }
  const result = await oxapayGetPayment(trackId);
  const status = (result && (result.status || (result.result && result.result.status))) || 'unknown';
  const paid = ['paid', 'completed', 'confirmed'].includes(String(status).toLowerCase());
  if (paid) {
    const pendingAmount = toNumber(await getUserData('pending_deposit_amount', 0), 0);
    const cur = await getUserData('pending_deposit_currency', 'USDT');
    if (pendingAmount > 0) {
      await addBalance(pendingAmount);
      const prevDep = toNumber(await getUserData('total_deposited', 0), 0);
      await setUserData('total_deposited', prevDep + pendingAmount);
      await incrementBotData('total_deposits', pendingAmount);
      const referrerId = await getUserData('referred_by', null);
      const refRewarded = await getUserData('ref_rewarded', false);
      if (referrerId && referrerId !== userId && !refRewarded) {
        const refReward = toNumber(await getBotData('referral_reward', 1), 1);
        if (refReward > 0) {
          await addBalance(refReward, referrerId);
          await incrementUserDataFor(referrerId, 'referrals', 1);
          await setUserData('ref_rewarded', true);
          await notifyUser(referrerId, `🎁 <b>Referral Reward</b>\n\nYour referral made a deposit!\n• Reward: <code>${formatMoney(refReward, cur)}</code>`);
        }
      }
      await addTransaction(userId, { id: generateId('tx'), type: 'deposit', amount: pendingAmount, currency: cur, status: 'success', date: now(), note: `Track: ${trackId}` });
      await setUserData('pending_deposit_track_id', null);
      await setUserData('pending_deposit_amount', 0);
      await replyHTML(`✅ <b>Deposit Confirmed</b>\n\n• Amount: <code>${formatMoney(pendingAmount, cur)}</code>\n• New Balance: <code>${formatMoney(await getBalance(), cur)}</code>`);
    } else { await replyHTML('✅ Payment confirmed. Contact support if your balance wasn\'t updated.'); }
  } else {
    await replyHTML(`⏳ <b>Payment Pending</b>\n\nStatus: <code>${status}</code>\n\nComplete payment then tap Check again.`,
      inlineMenu([[button('🔄 Check Again', '/deposit check')]]));
  }
  return;
}

// New deposit flow — auto-clear any waiting state
await clearState(); await clearStateData(); await clearCommandFlow();
const currency = await getBotData('investment_currency', 'USDT');
const minDeposit = toNumber(await getBotData('minimum_deposit', 10), 10);
const imgUrl = await getBotData('deposit_image_url', null);
await setState('awaiting_deposit_amount');
await setStateData('min_deposit', minDeposit); await setStateData('currency', currency);
const text = `💳 <b>Deposit Funds</b>\n\n• Currency: ${currency}\n• Minimum: <code>${formatMoney(minDeposit, currency)}</code>\n\nEnter the amount to deposit:\n\n/cancel to go back.`;
if (imgUrl) { try { await sendPhoto(chat.id, imgUrl, { caption: text, parse_mode: 'HTML' }); } catch (e) { await replyHTML(text); } }
else { await replyHTML(text); }
JS,
],

// ════════════════════════════════════ /withdraw ══════════════════════════
[
'command_name' => '/withdraw',
'display_name' => 'Withdraw',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData(); await clearCommandFlow();

const currency = await getBotData('investment_currency', 'USDT');
const minWithdraw = toNumber(await getBotData('minimum_withdraw', 10), 10);
const balance = await getBalance();
const wallet = await getUserData('wallet', null);
const imgUrl = await getBotData('withdraw_image_url', null);

if (!wallet) {
  await setState('awaiting_wallet');
  const t = `💰 <b>Withdraw</b>\n\nNo wallet saved. Enter your ${currency} wallet address or email:\n\n/cancel to go back.`;
  if (imgUrl) { try { await sendPhoto(chat.id, imgUrl, { caption: t, parse_mode: 'HTML' }); } catch (e) { await replyHTML(t); } }
  else { await replyHTML(t); }
  return;
}
if (balance < minWithdraw) {
  await replyHTML(`💰 <b>Withdraw</b>\n\n• Balance: <code>${formatMoney(balance, currency)}</code>\n• Minimum: <code>${formatMoney(minWithdraw, currency)}</code>\n\nBalance is below the minimum withdrawal amount.`);
  return;
}
await setState('awaiting_withdraw_amount');
await setStateData('min_withdraw', minWithdraw); await setStateData('currency', currency); await setStateData('wallet', wallet);
const text = `💰 <b>Withdraw Funds</b>\n\n• Balance: <code>${formatMoney(balance, currency)}</code>\n• Minimum: <code>${formatMoney(minWithdraw, currency)}</code>\n• Wallet: <code>${wallet}</code>\n\nEnter amount to withdraw:\n\n/cancel to go back.`;
if (imgUrl) { try { await sendPhoto(chat.id, imgUrl, { caption: text, parse_mode: 'HTML' }); } catch (e) { await replyHTML(text); } }
else { await replyHTML(text); }
JS,
],

// ════════════════════════════════════ /history ═══════════════════════════
[
'command_name' => '/history',
'display_name' => 'History',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID && (await getUserData('banned', false)) === true) return;
await clearState(); await clearStateData();

const currency = await getBotData('investment_currency', 'USDT');
const history = await getUserData('transaction_history', []);
const list = Array.isArray(history) ? history : [];

const backKb = inlineMenu([[button('⬅️ Back', '/my_account')]]);
if (list.length === 0) { await replyHTML(`📜 <b>History</b>\n\nNo transactions yet.`, backKb); return; }

const typeLabel = { deposit:'📥 Deposit', withdrawal:'📤 Withdrawal', investment:'📈 Investment', profit:'💰 Profit', admin_credit:'➕ Credit', admin_debit:'➖ Debit', referral_reward:'🎁 Referral' };
const recent = list.slice(-10).reverse();
let text = `📜 <b>Transaction History</b> (Last ${recent.length})\n\n`;
for (const tx of recent) {
  const label = typeLabel[tx.type] || tx.type;
  const date = String(tx.date || '').slice(0, 10);
  const icon = tx.status === 'success' ? '✅' : tx.status === 'pending' ? '⏳' : '❌';
  text += `${icon} ${label}: <code>${formatMoney(tx.amount, currency)}</code> — ${date}\n`;
}
await replyHTML(text, backKb);
JS,
],

// ════════════════════════════════════ /my_account (slash, for Back btn) ═
[
'command_name' => '/my_account',
'display_name' => 'My Account Slash',
'trigger_type' => 'slash',
'code'         => "await runCommand('💼 My Account');",
],

// ════════════════════════════════════ /cancel ════════════════════════════
[
'command_name' => '/cancel',
'display_name' => 'Cancel',
'trigger_type' => 'slash',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
await clearState(); await clearStateData(); await clearCommandFlow();
await setUserData('admin_state', null);
await setUserData('admin_target_user', null);
await setUserData('admin_step_data', null);

const menu = bottomMenu([
  ['📈 Invest', '👛 My Wallet', '💼 My Account'],
  ['🔗 Referral', '🎁 Daily Reward', '🆘 Support'],
]);
if (userId === OWNER_ID) {
  await replyHTML('❌ <b>Cancelled</b>\n\nReturning to admin panel.', { reply_markup: { remove_keyboard: true } });
  await runCommand('/admin');
} else {
  await replyHTML('❌ <b>Cancelled</b>', menu);
}
JS,
],

// ════════════════════════════════════ /admin ═════════════════════════════
[
'command_name' => '/admin',
'display_name' => 'Admin',
'trigger_type' => 'slash',
'is_pinned'    => true,
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId = String(user.id);
if (userId !== OWNER_ID) {
  await setUserData('banned', true);
  await replyHTML('🚫 <b>Access Denied</b>');
  return;
}
await setUserData('banned', false);

const cbData = telegram.callback_data || '';
const subCmd = (cbData.startsWith('/admin') && args[0]) ? args[0] : '';
const currency = await getBotData('investment_currency', 'USDT');

const adminKbd = () => inlineMenu([
  [button('➕ Add Balance',    '/admin add_balance'),    button('➖ Remove Balance',  '/admin remove_balance')],
  [button('🔍 Check User',     '/admin check_user'),     button('🚫 Ban User',        '/admin ban_user')],
  [button('✅ Unban User',      '/admin unban_user'),     button('📊 Stats',           '/admin stats')],
  [button('💰 Min Deposit',    '/admin set_min_deposit'),button('💸 Min Withdraw',    '/admin set_min_withdraw')],
  [button('🎁 Ref Reward',     '/admin set_ref_reward'), button('📢 Payout Channel', '/admin set_payout')],
  [button('👤 Support',        '/admin set_support'),    button('🎁 Daily Reward Amt','/admin set_daily_reward')],
  [button('❓ Set FAQ',         '/admin set_faq'),        button('📋 Set Plans',       '/admin set_plans')],
  [button('🖼 Welcome Img',    '/admin set_welcome_img'),button('🖼 Balance Img',     '/admin set_balance_img')],
  [button('🖼 Deposit Img',    '/admin set_deposit_img'),button('🖼 Invest Img',      '/admin set_invest_img')],
  [button('🖼 Withdraw Img',   '/admin set_withdraw_img'),button('📣 Broadcast',      '/admin broadcast')],
  [button('🎁 Welcome Bonus',  '/admin set_welcome_bonus'),button('🏠 Main Menu',      '/main_menu')],
]);

const showPanel = async () => {
  const tu = toNumber(await getBotData('total_users', 0), 0);
  const td = toNumber(await getBotData('total_deposits', 0), 0);
  const tw = toNumber(await getBotData('total_withdrawals', 0), 0);
  const ti = toNumber(await getBotData('total_invested', 0), 0);
  const tp = toNumber(await getBotData('total_profit_paid', 0), 0);
  const ai = toNumber(await getBotData('total_active_investments', 0), 0);
  const md = toNumber(await getBotData('minimum_deposit', 10), 10);
  const mw = toNumber(await getBotData('minimum_withdraw', 10), 10);
  const rr = toNumber(await getBotData('referral_reward', 1), 1);
  const dr = toNumber(await getBotData('daily_reward_amount', 1), 1);
  const pc = await getBotData('payout_channel', 'Not set');
  const su = await getBotData('support_username', '@support');
  await replyHTML(`⚙️ <b>Admin Panel</b>\n\n<u>Stats</u>\n• Users: ${tu} | Deposits: <code>${formatMoney(td, currency)}</code>\n• Withdrawals: <code>${formatMoney(tw, currency)}</code> | Invested: <code>${formatMoney(ti, currency)}</code>\n• Profit Paid: <code>${formatMoney(tp, currency)}</code> | Active Inv: ${ai}\n\n<u>Settings</u>\n• Min Deposit: <code>${formatMoney(md, currency)}</code> | Min Withdraw: <code>${formatMoney(mw, currency)}</code>\n• Ref Reward: <code>${formatMoney(rr, currency)}</code> | Daily Reward: <code>${formatMoney(dr, currency)}</code>\n• Payout: ${pc} | Support: ${su}`, adminKbd());
};

if (!subCmd) { await showPanel(); return; }
if (subCmd === 'stats') { await showPanel(); return; }

const prompt = async (state, text) => { await setUserData('admin_state', state); await replyHTML(text + '\n\n/cancel to go back.'); };

if (subCmd === 'add_balance')      { await prompt('admin_add_balance_user_id',   '➕ <b>Add Balance</b>\n\nEnter Telegram User ID:'); return; }
if (subCmd === 'remove_balance')   { await prompt('admin_remove_balance_user_id','➖ <b>Remove Balance</b>\n\nEnter Telegram User ID:'); return; }
if (subCmd === 'check_user')       { await prompt('admin_check_user_id',         '🔍 <b>Check User</b>\n\nEnter Telegram User ID:'); return; }
if (subCmd === 'ban_user')         { await prompt('admin_ban_user_id',           '🚫 <b>Ban User</b>\n\nEnter Telegram User ID:'); return; }
if (subCmd === 'unban_user')       { await prompt('admin_unban_user_id',         '✅ <b>Unban User</b>\n\nEnter Telegram User ID:'); return; }
if (subCmd === 'set_min_deposit')  { await prompt('admin_set_min_deposit',       `💰 <b>Set Min Deposit</b>\n\nCurrent: <code>${formatMoney(toNumber(await getBotData('minimum_deposit',10),10), currency)}</code>\n\nEnter new amount:`); return; }
if (subCmd === 'set_min_withdraw') { await prompt('admin_set_min_withdraw',      `💸 <b>Set Min Withdraw</b>\n\nCurrent: <code>${formatMoney(toNumber(await getBotData('minimum_withdraw',10),10), currency)}</code>\n\nEnter new amount:`); return; }
if (subCmd === 'set_ref_reward')   { await prompt('admin_set_ref_reward',        `🎁 <b>Set Referral Reward</b>\n\nCurrent: <code>${formatMoney(toNumber(await getBotData('referral_reward',1),1), currency)}</code>\n\nEnter new amount:`); return; }
if (subCmd === 'set_daily_reward') { await prompt('admin_set_daily_reward',      `🎁 <b>Set Daily Reward</b>\n\nCurrent: <code>${formatMoney(toNumber(await getBotData('daily_reward_amount',1),1), currency)}</code>\n\nEnter new amount:`); return; }
if (subCmd === 'set_payout')       { await prompt('admin_set_payout',            `📢 <b>Set Payout Channel</b>\n\nCurrent: ${await getBotData('payout_channel','Not set')}\n\nEnter @ChannelName:`); return; }
if (subCmd === 'set_support')      { await prompt('admin_set_support',           `👤 <b>Set Support</b>\n\nCurrent: ${await getBotData('support_username','@support')}\n\nEnter @Username:`); return; }
if (subCmd === 'set_faq')          { await prompt('admin_set_faq',               '❓ <b>Set FAQ Text</b>\n\nSend the full FAQ message (HTML supported):'); return; }
if (subCmd === 'set_welcome_img')  { await prompt('admin_set_welcome_image',     '🖼 <b>Set Welcome Image</b>\n\nEnter image URL (https://...):'); return; }
if (subCmd === 'set_balance_img')  { await prompt('admin_set_balance_image',     '🖼 <b>Set Balance Image</b>\n\nEnter image URL (https://...):'); return; }
if (subCmd === 'set_deposit_img')  { await prompt('admin_set_deposit_image',     '🖼 <b>Set Deposit Image</b>\n\nEnter image URL (https://...):'); return; }
if (subCmd === 'set_invest_img')   { await prompt('admin_set_investment_image',  '🖼 <b>Set Invest Image</b>\n\nEnter image URL (https://...):'); return; }
if (subCmd === 'set_withdraw_img') { await prompt('admin_set_withdraw_image',    '🖼 <b>Set Withdraw Image</b>\n\nEnter image URL (https://...):'); return; }
if (subCmd === 'set_plans')        { await prompt('admin_set_plans',             '📋 <b>Set Plans</b>\n\nSend JSON array:\n\n<code>[{"id":"starter","name":"Starter","min":10,"max":99,"percent":10,"duration_hours":24}]</code>'); return; }
if (subCmd === 'broadcast')        { await prompt('admin_broadcast',             '📣 <b>Broadcast</b>\n\nEnter message to send to all users:'); return; }
if (subCmd === 'set_welcome_bonus') { await prompt('admin_set_welcome_bonus', `🎁 <b>Set Welcome Bonus</b>\n\nCurrent: <code>${formatMoney(toNumber(await getBotData('welcome_bonus',0),0), currency)}</code>\n\nEnter amount (0 to disable):`); return; }

// [20] view_profile — loads full account summary for a previously selected user.
// Data is available because NodeRuntimeService preloads cross_users[admin_target_user]
// on the callback request BEFORE this handler runs.
if (subCmd === 'view_profile') {
  const tid = await getUserData('admin_target_user', null);
  if (!tid) { await showPanel(); return; }
  await setUserData('admin_target_user', null); // clear after reading
  const bal         = await getBalance(tid);
  const dep         = toNumber(await getUserDataFor(tid, 'total_deposited',      0),     0);
  const inv         = toNumber(await getUserDataFor(tid, 'total_invested',       0),     0);
  const refs        = toNumber(await getUserDataFor(tid, 'referrals',            0),     0);
  const isBanned    = (await getUserDataFor(tid, 'banned',             false)) === true;
  const wallet      = await getUserDataFor(tid, 'wallet',            null);
  const joined      = await getUserDataFor(tid, 'joined_at',         null);
  const acctStatus  = await getUserDataFor(tid, 'account_status',    'active');
  const kycStatus   = await getUserDataFor(tid, 'kyc_status',        'not_required');
  const riskFlag    = (await getUserDataFor(tid, 'risk_flag',         false)) === true;
  const lastActive  = await getUserDataFor(tid, 'last_active_at',    null);
  const tgUsername  = await getUserDataFor(tid, 'tg_username',       null);
  const tgFirstName = await getUserDataFor(tid, 'tg_first_name',     null);
  const bonusGiven  = await getUserDataFor(tid, 'welcome_bonus_given',false);
  const refSrc      = await getUserDataFor(tid, 'referral_source',   null);
  const refBy       = await getUserDataFor(tid, 'referred_by',       null);
  const statusStr   = isBanned ? '🚫 Banned' : (acctStatus === 'active' ? '✅ Active' : String(acctStatus || 'Active'));
  const kycStr      = String(kycStatus || 'not_required').replace(/_/g, ' ');
  const riskStr     = riskFlag ? '⚠️ Flagged' : '✅ Clear';
  const lastStr     = lastActive ? lastActive.slice(0,16).replace('T',' ') + ' UTC' : 'Never';
  const nameStr     = tgFirstName ? escapeHTML(String(tgFirstName)) : 'Unknown';
  const userStr     = tgUsername  ? `@${escapeHTML(String(tgUsername))}` : 'No username';
  await replyHTML(
    `🔍 <b>Account Summary</b>\n\n` +
    `• <b>ID:</b> <code>${tid}</code>\n` +
    `• <b>Name:</b> ${nameStr} (${userStr})\n` +
    `• <b>Status:</b> ${statusStr}\n` +
    `• <b>KYC:</b> ${kycStr}\n` +
    `• <b>Risk:</b> ${riskStr}\n` +
    `• <b>Joined:</b> ${joined ? joined.slice(0,10) : 'Unknown'}\n` +
    `• <b>Last Active:</b> ${lastStr}\n` +
    `• <b>Ref Source:</b> ${refSrc || 'direct'}\n` +
    `• <b>Referred By:</b> ${refBy || 'None'}\n` +
    `• <b>Bonus Given:</b> ${bonusGiven === true ? '✅ Yes' : '❌ No'}\n\n` +
    `<u>Finances (${currency})</u>\n` +
    `• Balance:   <code>${formatMoney(toNumber(bal,0), currency)}</code>\n` +
    `• Deposited: <code>${formatMoney(toNumber(dep,0), currency)}</code>\n` +
    `• Invested:  <code>${formatMoney(toNumber(inv,0), currency)}</code>\n` +
    `• Referrals: ${toNumber(refs,0)}\n` +
    `• Wallet:    ${wallet ? `<code>${wallet}</code>` : 'Not set'}`,
    inlineMenu([[button('⚙️ Admin Panel', '/admin')]]),
  );
  return;
}

await showPanel();
JS,
],

// ════════════════════════════════════ Direct Message Handler ═════════════
[
'command_name' => '__direct_message_handler_investbot',
'display_name' => 'Direct Message Handler',
'trigger_type' => 'direct_message',
'code'         => <<<'JS'
const OWNER_ID = '7701909986';
const userId   = String(user.id);
const msgText  = String(message.text || message.caption || '').trim();
if ((await getUserData('banned', false)) === true && userId !== OWNER_ID) return;

// [8] Keep last_active_at current on every message interaction
await setUserData('last_active_at', now());

const state = await getState();
const adminState = await getUserData('admin_state', null);
const currency = await getBotData('investment_currency', 'USDT');

const mainMenu = bottomMenu([
  ['📈 Invest', '👛 My Wallet', '💼 My Account'],
  ['🔗 Referral', '🎁 Daily Reward', '🆘 Support'],
]);

const clearAdmin = async () => {
  await setUserData('admin_state', null);
  await setUserData('admin_target_user', null);
  await setUserData('admin_step_data', null);
};

// ══════════════ ADMIN STATE MACHINE (owner only) ══════════════════════════
if (userId === OWNER_ID && adminState) {

  // ── ADD BALANCE ──────────────────────────────────────────────────────────
  if (adminState === 'admin_add_balance_user_id') {
    if (!isTelegramUserId(msgText)) { await replyHTML('⚠️ Invalid ID.\n\n/cancel to go back.'); return; }
    await setUserData('admin_target_user', msgText);
    await setUserData('admin_state', 'admin_add_balance_amount');
    await replyHTML(`➕ User: <code>${msgText}</code>\nCurrent balance: <code>${formatMoney(await getBalance(msgText), currency)}</code>\n\nEnter amount to add:\n\n/cancel to go back.`);
    return;
  }
  if (adminState === 'admin_add_balance_amount') {
    const targetId = await getUserData('admin_target_user', null);
    const amount = parseAmount(msgText);
    if (!isPositiveAmount(amount)) { await replyHTML('⚠️ Invalid amount.\n\n/cancel to go back.'); return; }
    await addBalance(amount, targetId);
    const newBal = await getBalance(targetId);
    await addTransaction(targetId, { id: generateId('tx'), type: 'admin_credit', amount, currency, status: 'success', date: now(), note: `Admin credit by ${userId}` });
    await clearAdmin();
    await replyHTML(`✅ Added <code>${formatMoney(amount, currency)}</code> to user <code>${targetId}</code>.\nNew balance: <code>${formatMoney(newBal, currency)}</code>`);
    return;
  }

  // ── REMOVE BALANCE ───────────────────────────────────────────────────────
  if (adminState === 'admin_remove_balance_user_id') {
    if (!isTelegramUserId(msgText)) { await replyHTML('⚠️ Invalid ID.\n\n/cancel to go back.'); return; }
    await setUserData('admin_target_user', msgText);
    await setUserData('admin_state', 'admin_remove_balance_amount');
    await replyHTML(`➖ User: <code>${msgText}</code>\nBalance: <code>${formatMoney(await getBalance(msgText), currency)}</code>\n\nEnter amount to deduct:\n\n/cancel to go back.`);
    return;
  }
  if (adminState === 'admin_remove_balance_amount') {
    const targetId = await getUserData('admin_target_user', null);
    const amount = parseAmount(msgText);
    if (!isPositiveAmount(amount)) { await replyHTML('⚠️ Invalid amount.\n\n/cancel to go back.'); return; }
    await removeBalance(amount, targetId);
    const newBal = await getBalance(targetId);
    await addTransaction(targetId, { id: generateId('tx'), type: 'admin_debit', amount, currency, status: 'success', date: now(), note: `Admin debit by ${userId}` });
    await clearAdmin();
    await replyHTML(`✅ Removed <code>${formatMoney(amount, currency)}</code> from user <code>${targetId}</code>.\nNew balance: <code>${formatMoney(newBal, currency)}</code>`);
    return;
  }

  // ── CHECK USER ───────────────────────────────────────────────────────────
  // Two-step flow: store target ID here, then /admin view_profile callback
  // triggers PHP's crossUserPreload for that user so getUserDataFor() works.
  if (adminState === 'admin_check_user_id') {
    if (!isTelegramUserId(msgText)) { await replyHTML('⚠️ Invalid ID.\n\n/cancel to go back.'); return; }
    await setUserData('admin_state', null);
    await setUserData('admin_target_user', msgText); // kept — preloaded on next request
    await replyHTML(
      `🔍 User: <code>${msgText}</code>\n\nTap <b>View Profile</b> to load the full account summary.`,
      inlineMenu([[button('📋 View Profile', '/admin view_profile'), button('⚙️ Admin Panel', '/admin')]]),
    );
    return;
  }

  // ── BAN / UNBAN ──────────────────────────────────────────────────────────
  if (adminState === 'admin_ban_user_id') {
    if (!isTelegramUserId(msgText)) { await replyHTML('⚠️ Invalid ID.'); return; }
    if (msgText === OWNER_ID) { await replyHTML('⚠️ Cannot ban the owner.'); await clearAdmin(); return; }
    await setUserDataFor(msgText, 'banned', true);
    await clearAdmin();
    await replyHTML(`✅ User <code>${msgText}</code> banned.`);
    return;
  }
  if (adminState === 'admin_unban_user_id') {
    if (!isTelegramUserId(msgText)) { await replyHTML('⚠️ Invalid ID.'); return; }
    await setUserDataFor(msgText, 'banned', false);
    await clearAdmin();
    await replyHTML(`✅ User <code>${msgText}</code> unbanned.`);
    return;
  }

  // ── NUMERIC SETTINGS ─────────────────────────────────────────────────────
  const numericSettings = {
    admin_set_min_deposit:   ['minimum_deposit',    'Min Deposit'],
    admin_set_min_withdraw:  ['minimum_withdraw',   'Min Withdraw'],
    admin_set_ref_reward:    ['referral_reward',    'Referral Reward'],
    admin_set_daily_reward:  ['daily_reward_amount','Daily Reward Amount'],
    admin_set_welcome_bonus: ['welcome_bonus',      'Welcome Bonus'],       // [16]
  };
  if (numericSettings[adminState]) {
    const [key, label] = numericSettings[adminState];
    const amount = parseAmount(msgText);
    if (!isPositiveAmount(amount)) { await replyHTML('⚠️ Invalid amount.\n\n/cancel to go back.'); return; }
    await setBotData(key, amount);
    await clearAdmin();
    await replyHTML(`✅ ${label} set to <code>${formatMoney(amount, currency)}</code>`);
    return;
  }

  // ── PAYOUT CHANNEL ───────────────────────────────────────────────────────
  if (adminState === 'admin_set_payout') {
    const ch = normalizeTelegramUsername(msgText);
    if (!ch) { await replyHTML('⚠️ Invalid. Use @ChannelName format.\n\n/cancel to go back.'); return; }
    await setBotData('payout_channel', ch);
    await clearAdmin();
    await replyHTML(`✅ Payout channel set to <b>${ch}</b>\n\n<i>Make sure the bot is admin in that channel.</i>`);
    return;
  }

  // ── SUPPORT USERNAME ─────────────────────────────────────────────────────
  if (adminState === 'admin_set_support') {
    const su = normalizeTelegramUsername(msgText);
    if (!su) { await replyHTML('⚠️ Invalid. Use @Username format.\n\n/cancel to go back.'); return; }
    await setBotData('support_username', su);
    await clearAdmin();
    await replyHTML(`✅ Support username set to <b>${su}</b>`);
    return;
  }

  // ── FAQ TEXT ──────────────────────────────────────────────────────────────
  if (adminState === 'admin_set_faq') {
    if (msgText.length < 10) { await replyHTML('⚠️ FAQ text too short. Send the full message.\n\n/cancel to go back.'); return; }
    await setBotData('support_faq', msgText);
    await clearAdmin();
    await replyHTML(`✅ FAQ text updated.`);
    return;
  }

  // ── IMAGE URLS ───────────────────────────────────────────────────────────
  const imageStates = {
    admin_set_welcome_image:    'welcome_image_url',
    admin_set_balance_image:    'balance_image_url',
    admin_set_deposit_image:    'deposit_image_url',
    admin_set_investment_image: 'investment_image_url',
    admin_set_withdraw_image:   'withdraw_image_url',
  };
  if (imageStates[adminState]) {
    const key = imageStates[adminState];
    const url = msgText;
    if (!url || !url.startsWith('https://')) {
      await replyHTML('⚠️ URL must start with https://\n\nSend the image URL or /cancel to go back.');
      return;
    }
    await setBotData(key, url);
    await clearAdmin();
    const backKb = inlineMenu([[button('⚙️ Admin Panel', '/admin')]]);
    // Try to show the saved image as a preview — sendPhoto now returns {ok} so this is safe
    const preview = await sendPhoto(chat.id, url, {
      caption: `✅ <b>Image saved.</b>\n\n<code>${escapeHTML(url)}</code>`,
      parse_mode: 'HTML',
      reply_markup: backKb.reply_markup,
    });
    if (!preview.ok) {
      // Telegram rejected the URL — still saved, just show text confirmation
      await replyHTML(
        `✅ Image URL saved:\n<code>${escapeHTML(url)}</code>\n\n<i>Preview unavailable. The URL will be used when the section is opened.</i>`,
        backKb,
      );
    }
    return;
  }

  // ── SET PLANS ────────────────────────────────────────────────────────────
  if (adminState === 'admin_set_plans') {
    let plans;
    try { plans = JSON.parse(msgText); } catch (e) { plans = null; }
    if (!Array.isArray(plans) || plans.length === 0) {
      await replyHTML('⚠️ Invalid JSON array. Check format and try again.\n\n/cancel to go back.');
      return;
    }
    await setBotData('investment_plans', plans);
    await clearAdmin();
    await replyHTML(`✅ ${plans.length} plan(s) saved.`);
    return;
  }

  // ── BROADCAST ────────────────────────────────────────────────────────────
  if (adminState === 'admin_broadcast') {
    await clearAdmin();
    const result = await queueBroadcast({ message: msgText, parse_mode: 'HTML' });
    if (result && result.ok) {
      await replyHTML(`📣 <b>Broadcast Queued</b>\n\n${result.message || 'Sending...'}`);
    } else {
      await replyHTML(`⚠️ Broadcast not available: ${(result && result.error) || 'Service unavailable.'}`);
    }
    return;
  }

  // ── ADMIN SUPPORT REPLY ──────────────────────────────────────────────────
  if (adminState === 'admin_support_reply') {
    const targetId = await getUserData('admin_target_user', null);
    if (!targetId) { await clearAdmin(); await replyHTML('⚠️ No target user set. Use the Reply button.'); return; }
    await notifyUser(targetId, `📨 <b>Support Reply</b>\n\n${msgText}`);
    await clearAdmin();
    await replyHTML(`✅ Reply sent to user <code>${targetId}</code>.`);
    return;
  }

  // Unknown admin state — clear and return to panel
  await clearAdmin();
  await replyHTML('⚠️ Unknown state cleared.', inlineMenu([[button('⚙️ Admin Panel', '/admin')]]));
  return;
}

// ══════════════ USER STATE MACHINE ════════════════════════════════════════

// ── SET WALLET ──────────────────────────────────────────────────────────
if (state === 'awaiting_wallet') {
  if (msgText.length < 3) { await replyHTML('⚠️ Invalid wallet address.\n\n/cancel to go back.'); return; }
  await setUserData('wallet', msgText);
  await clearState(); await clearStateData();
  await replyHTML(`✅ Wallet saved:\n<code>${msgText}</code>\n\nUse 💰 Withdraw to request a withdrawal.`);
  return;
}

// ── DEPOSIT AMOUNT ──────────────────────────────────────────────────────
if (state === 'awaiting_deposit_amount') {
  const minDeposit = toNumber(await getStateData('min_deposit', 10), 10);
  const amount = parseAmount(msgText);
  if (!isPositiveAmount(amount)) { await replyHTML('⚠️ Enter a valid positive number.\n\n/cancel to go back.'); return; }
  if (amount < minDeposit) { await replyHTML(`⚠️ Minimum deposit is <code>${formatMoney(minDeposit, currency)}</code>.\n\n/cancel to go back.`); return; }
  await clearState(); await clearStateData();
  await replyHTML(`⏳ Creating invoice for <code>${formatMoney(amount, currency)}</code>...`);
  try {
    const invoice = await oxapayCreateWhiteLabel({ amount, currency, description: `Deposit — ${userId}` });
    const ok = invoice && (invoice.ok === true || invoice.ok === 1 || invoice.ok === 'true');
    if (!ok) { await replyHTML('❌ <b>Deposit Unavailable</b>\n\nPlease try again later.'); return; }
    const trackId = invoice.trackId || (invoice.result && invoice.result.trackId) || null;
    const payUrl = invoice.payLink || invoice.url || (invoice.result && (invoice.result.payLink || invoice.result.url)) || null;
    await setUserData('pending_deposit_track_id', trackId);
    await setUserData('pending_deposit_amount', amount);
    await setUserData('pending_deposit_currency', currency);
    await addTransaction(userId, { id: generateId('tx'), type: 'deposit', amount, currency, status: 'pending', date: now(), note: `Track: ${trackId}` });
    const kb = inlineMenu([
      payUrl ? [urlButton('💳 Pay Now', payUrl)] : [],
      [button('🔄 Check Payment', '/deposit check')],
    ].filter(r => r.length > 0));
    await replyHTML(`💳 <b>Invoice Created</b>\n\n• Amount: <code>${formatMoney(amount, currency)}</code>\n• Track ID: <code>${trackId || 'N/A'}</code>\n\nComplete payment, then tap Check Payment.`, kb);
  } catch (e) {
    await replyHTML('❌ <b>Deposit Unavailable</b>\n\nPlease try again later.');
  }
  return;
}

// ── INVEST AMOUNT ───────────────────────────────────────────────────────
if (state === 'awaiting_invest_amount') {
  const plansRaw = await getStateData('plans', null);
  const defaultPlans = [
    { id:'starter', name:'Starter Plan', min:10,   max:99,      percent:10, duration_hours:24 },
    { id:'growth',  name:'Growth Plan',  min:100,  max:499,     percent:20, duration_hours:48 },
    { id:'premium', name:'Premium Plan', min:500,  max:999,     percent:35, duration_hours:72 },
    { id:'elite',   name:'Elite Plan',   min:1000, max:1000000, percent:50, duration_hours:96 },
  ];
  const plans = Array.isArray(plansRaw) ? plansRaw : defaultPlans;
  const amount = parseAmount(msgText);
  if (!isPositiveAmount(amount)) { await replyHTML('⚠️ Enter a valid positive amount.\n\n/cancel to go back.'); return; }
  const balance = await getBalance();
  if (balance < amount) { await replyHTML(`❌ Insufficient balance.\n\nAvailable: <code>${formatMoney(balance, currency)}</code>\n\n/cancel to go back.`); return; }
  const plan = plans.find(p => amount >= p.min && amount <= p.max);
  if (!plan) {
    let ranges = '';
    for (const p of plans) ranges += `• ${p.name}: ${formatMoney(p.min, currency)} – ${formatMoney(p.max, currency)}\n`;
    await replyHTML(`⚠️ <b>No Matching Plan</b>\n\n${formatMoney(amount, currency)} doesn't fit any plan.\n\nAvailable ranges:\n${ranges}\n/cancel to go back.`);
    return;
  }
  const profit = parseFloat(((amount * plan.percent) / 100).toFixed(8));
  const totalReturn = parseFloat((amount + profit).toFixed(8));
  const maturesAt = addHours(now(), plan.duration_hours);
  await setStateData('pending_invest', { amount, plan, profit, totalReturn, maturesAt, currency });
  const confirmKbd = inlineMenu([
    [button('✅ Confirm', '/invest confirm'), button('❌ Cancel', '/invest cancel')],
  ]);
  await replyHTML(`📈 <b>Investment Confirmation</b>\n\n• Plan: ${plan.name}\n• Amount: <code>${formatMoney(amount, currency)}</code>\n• Return: ${plan.percent}% in ${plan.duration_hours}h\n• Profit: <code>${formatMoney(profit, currency)}</code>\n• Total Return: <code>${formatMoney(totalReturn, currency)}</code>\n• Matures: ${maturesAt.slice(0,16).replace('T',' ')} UTC\n\n<i>Only invest what you can afford to risk.</i>`, confirmKbd);
  return;
}

// ── WITHDRAW AMOUNT ─────────────────────────────────────────────────────
if (state === 'awaiting_withdraw_amount') {
  const minWith = toNumber(await getStateData('min_withdraw', 10), 10);
  const wallet = await getStateData('wallet', await getUserData('wallet', null));
  const amount = parseAmount(msgText);
  const balance = await getBalance();
  if (!isPositiveAmount(amount)) { await replyHTML('⚠️ Invalid amount.\n\n/cancel to go back.'); return; }
  if (amount < minWith) { await replyHTML(`⚠️ Minimum withdrawal: <code>${formatMoney(minWith, currency)}</code>.\n\n/cancel to go back.`); return; }
  if (amount > balance) { await replyHTML(`❌ Insufficient balance.\n\nAvailable: <code>${formatMoney(balance, currency)}</code>\n\n/cancel to go back.`); return; }
  await clearState(); await clearStateData();
  await removeBalance(amount);
  const prevWith = toNumber(await getUserData('total_withdrawn', 0), 0);
  await setUserData('total_withdrawn', prevWith + amount);
  await incrementBotData('total_withdrawals', amount);
  const txId = generateId('tx');
  await addTransaction(userId, { id: txId, type: 'withdrawal', amount, currency, status: 'pending', date: now(), note: `Wallet: ${wallet}` });
  await notifyUser(OWNER_ID, `📤 <b>Withdrawal Request</b>\n\n• User: <code>${userId}</code>\n• Amount: <code>${formatMoney(amount, currency)}</code>\n• Wallet: <code>${wallet}</code>\n• TX: <code>${txId}</code>`);
  const payoutCh = await getBotData('payout_channel', null);
  if (payoutCh) {
    await sendPayoutNotice(payoutCh, { user_id: userId, amount, currency, wallet, bot: bot.username || '' });
  }
  await replyHTML(`✅ <b>Withdrawal Submitted</b>\n\n• Amount: <code>${formatMoney(amount, currency)}</code>\n• Wallet: <code>${wallet}</code>\n• Status: Pending\n\nYour request is being processed. Contact support if needed.`);
  return;
}

// ── SUPPORT MESSAGE ─────────────────────────────────────────────────────
if (state === 'awaiting_support_message') {
  await clearState(); await clearStateData();
  // Forward message to admin (handles text and photos)
  try { await copyMessage(OWNER_ID, chat.id, message.message_id); } catch (e) {}
  // Notify admin with reply button
  const userName = user.username ? ` (@${user.username})` : '';
  await notifyUser(OWNER_ID,
    `📩 <b>Support Message</b>\nFrom: <code>${userId}</code>${userName}`,
    { reply_markup: inlineMenu([[button('📤 Reply to User', `/adminreply ${userId}`)]]).reply_markup }
  );
  await replyHTML(`✅ Your message has been sent to support.\n\nWe will respond as soon as possible.`);
  return;
}

// ── DEFAULT: no state, show menu ─────────────────────────────────────────
await replyHTML('Use the menu below to navigate.', mainMenu);
JS,
],

        ]; // end commands array
    }
}
