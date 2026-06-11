<?php
/**
 * Tests A + B — Bot#14 support message and admin reply
 *
 * A: User sends support message → admin receives it, user sees success, no payment bridge error.
 * B: Admin replies to that support message → user receives reply, no payment bridge error.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$botId   = (int) ($argv[1] ?? 14);
$userId  = (string) ($argv[2] ?? '7701909986');
$runtime = app(\App\Services\NodeRuntimeService::class);

$bot = \App\Models\Bot::findOrFail($botId);
echo "Bot: #{$bot->id} {$bot->name}\n\n";

// ── TEST A: Support message ──────────────────────────────────────────────────
// Set awaiting_support_message = true (the raw flag Bot#14 DM handler reads)
\App\Models\BotUserRuntimeData::updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'awaiting_support_message'],
    ['value' => true]
);

$dm = \App\Models\BotCommand::where('bot_id', $bot->id)
    ->where('trigger_type', 'direct_message')
    ->firstOrFail();

$supportCtx = [
    'user_id'      => $userId,
    'chat_id'      => $userId,
    'message_text' => 'test support message ' . date('H:i:s'),
    'message'      => [
        'message_id' => random_int(100000, 999999),
        'text'       => 'test support message ' . date('H:i:s'),
        'from'       => ['id' => $userId, 'first_name' => 'Test', 'username' => 'test_user'],
        'chat'       => ['id' => $userId, 'type' => 'private'],
    ],
    'args' => [],
];

echo "=== TEST A: Support message (Bot#{$bot->id} DM handler) ===\n";
$resultA = $runtime->executeCommand($bot, $dm, $supportCtx);
$okA     = ($resultA['ok'] ?? false) === true;
$errA    = $resultA['error'] ?? null;
$hasPaymentErr = $errA && (
    stripos($errA, 'oxapay') !== false ||
    stripos($errA, 'faucetpay') !== false ||
    stripos($errA, 'payment') !== false ||
    stripos($errA, 'timed out') !== false
);

echo 'ok:             ' . ($okA ? 'true' : 'false') . "\n";
echo 'error:          ' . ($errA ?? '(none)') . "\n";
echo 'payment error:  ' . ($hasPaymentErr ? 'YES ← FAIL' : 'none ← PASS') . "\n";
echo 'result A:       ' . ($okA && !$hasPaymentErr ? 'PASS' : 'FAIL') . "\n\n";

// ── TEST B: Admin support reply ──────────────────────────────────────────────
// Set admin into admin_reply_support_message state with a support_target_user
\App\Models\BotUserRuntimeData::updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'admin_state'],
    ['value' => 'admin_reply_support_message']
);
\App\Models\BotUserRuntimeData::updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'support_target_user'],
    ['value' => $userId]
);

$replyCtx = [
    'user_id'      => $userId,
    'chat_id'      => $userId,
    'message_text' => 'test admin reply ' . date('H:i:s'),
    'message'      => [
        'message_id' => random_int(100000, 999999),
        'text'       => 'test admin reply ' . date('H:i:s'),
        'from'       => ['id' => $userId, 'first_name' => 'Admin', 'username' => 'admin_user'],
        'chat'       => ['id' => $userId, 'type' => 'private'],
    ],
    'args' => [],
];

echo "=== TEST B: Admin reply (Bot#{$bot->id} DM handler) ===\n";
$resultB = $runtime->executeCommand($bot, $dm, $replyCtx);
$okB     = ($resultB['ok'] ?? false) === true;
$errB    = $resultB['error'] ?? null;
$hasPaymentErrB = $errB && (
    stripos($errB, 'oxapay') !== false ||
    stripos($errB, 'faucetpay') !== false ||
    stripos($errB, 'payment') !== false ||
    stripos($errB, 'timed out') !== false
);

echo 'ok:             ' . ($okB ? 'true' : 'false') . "\n";
echo 'error:          ' . ($errB ?? '(none)') . "\n";
echo 'payment error:  ' . ($hasPaymentErrB ? 'YES ← FAIL' : 'none ← PASS') . "\n";
echo 'result B:       ' . ($okB && !$hasPaymentErrB ? 'PASS' : 'FAIL') . "\n\n";

// ── SUMMARY ──────────────────────────────────────────────────────────────────
$passed = ($okA && !$hasPaymentErr) && ($okB && !$hasPaymentErrB);
echo '=== SUMMARY ===', "\n";
echo 'A (support msg):   ' . ($okA && !$hasPaymentErr    ? 'PASS' : 'FAIL') . "\n";
echo 'B (admin reply):   ' . ($okB && !$hasPaymentErrB   ? 'PASS' : 'FAIL') . "\n";
echo 'Overall:           ' . ($passed ? 'ALL PASS' : 'FAILURES PRESENT') . "\n";
exit($passed ? 0 : 1);
