<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bot = App\Models\Bot::query()->first();
if (! $bot) {
    echo "NO_BOT\n";
    exit(0);
}

$runtime = app(App\Services\NodeRuntimeService::class);
$adminId = '999000111';
$targetId = '999000222';
$referrerId = '999000333';

$ctx = [
    'user_id' => $adminId,
    'chat_id' => $adminId,
    'message_text' => '/batch4smoke '.$targetId,
    'message' => [
        'text' => '/batch4smoke '.$targetId,
        'from' => ['id' => $adminId],
        'chat' => ['id' => $adminId],
    ],
    'args' => [$targetId],
];

$code = <<<'JS'
const target = getArg(0);
const credit = await recordAdminCredit(target, 12.5, 'batch4 smoke credit');
const debit = await recordAdminDebit(target, 2.5, 'batch4 smoke debit');
const tx = await addTransaction(target, { type: 'manual_smoke', amount: 1, status: 'success' });
const txs = await getTransactions(target, 5);
const referral = await addReferralReward('999000333', 3, target, { currency: 'USDT' });
const referralStats = await getReferralStats('999000333');
const investment = await createInvestment(target, { amount: 100, percent: 5, duration_hours: 0 });
const claim = await claimMaturedInvestments(target);
const invStats = await getInvestmentStats(target);
const task = await createTask({ title: 'Batch 4 smoke task' });
const tasks = await getTasks({ status: 'active' });
const broadcast = await broadcastMessage('all', 'Batch 4 smoke', { dry_run: true, user_ids: [target] });
const stats = await getBotStats();
const schedule = await scheduleJob('batch4_smoke', {}, null);

await setBotData('batch4_helper_smoke_result', {
  credit_ok: credit.ok,
  debit_ok: debit.ok,
  tx_ok: tx.ok,
  tx_count: txs.length,
  referral_ok: referral.ok,
  referral_count: referralStats.referrals,
  investment_ok: investment.ok,
  claim_ok: claim.ok,
  claimed_count: claim.claimed_count,
  inv_active_count: invStats.active_count,
  task_ok: task.ok,
  task_count: tasks.length,
  broadcast_ok: broadcast.ok,
  stats_bot_id: stats.bot_id,
  schedule_ok: schedule.ok,
  schedule_error: schedule.error || null,
});
JS;

$command = new App\Models\BotCommand([
    'bot_id' => $bot->id,
    'command_name' => '/batch4smoke',
    'trigger_type' => 'slash',
    'response_type' => 'code',
    'code' => $code,
]);

$result = $runtime->executeCommand($bot, $command, $ctx);
$stored = App\Models\BotRuntimeData::query()
    ->where('bot_id', $bot->id)
    ->where('key', 'batch4_helper_smoke_result')
    ->first();

$targetBalance = App\Models\BotUserRuntimeData::query()
    ->where('bot_id', $bot->id)
    ->where('telegram_user_id', $targetId)
    ->where('key', 'balance')
    ->first();

echo json_encode([
    'bot_id' => $bot->id,
    'runtime_ok' => $result['ok'] ?? null,
    'runtime_error' => $result['error'] ?? null,
    'stored' => $stored?->value,
    'target_balance' => $targetBalance?->value,
], JSON_PRETTY_PRINT)."\n";
