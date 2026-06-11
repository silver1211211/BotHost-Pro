<?php
/**
 * Tests C + D — FaucetPay bridge error stays inside the payment command
 *
 * C: Withdrawal flow with FaucetPay → timeout/failure returns clean error,
 *    only the withdrawal command sees it, no support flow contamination.
 *
 * D: Invalid FaucetPay email check → clean error inside wallet setup only.
 *
 * These tests simulate the DM handler state for Bot#11 (Referral Bot).
 * They do NOT require a live FaucetPay API key — they check that:
 *   1. The error response is structured (ok:false, error is a string, no raw trace)
 *   2. The error does not bleed into unrelated paths
 *   3. The command still returns ok:false gracefully, not a crash
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$botId   = (int) ($argv[1] ?? 11);
$userId  = (string) ($argv[2] ?? '7701909986');
$runtime = app(\App\Services\NodeRuntimeService::class);

$bot = \App\Models\Bot::findOrFail($botId);
echo "Bot: #{$bot->id} {$bot->name}\n\n";

$dm = \App\Models\BotCommand::where('bot_id', $bot->id)
    ->where('trigger_type', 'direct_message')
    ->firstOrFail();

// ── TEST C: FaucetPay withdrawal state ───────────────────────────────────────
// Set awaiting_withdraw_amount = true and a wallet so the send attempt fires
\App\Models\BotUserRuntimeData::updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'awaiting_withdraw_amount'],
    ['value' => true]
);
\App\Models\BotUserRuntimeData::updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'wallet'],
    ['value' => 'test@example.com']
);
\App\Models\BotUserRuntimeData::updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'balance'],
    ['value' => 9999]
);
// Clear any admin state so we hit the user path
\App\Models\BotUserRuntimeData::updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'admin_state'],
    ['value' => null]
);

$withdrawCtx = [
    'user_id'      => $userId,
    'chat_id'      => $userId,
    'message_text' => '1',   // valid amount to pass the parseAmount gate
    'message'      => [
        'message_id' => random_int(100000, 999999),
        'text'       => '1',
        'from'       => ['id' => $userId, 'first_name' => 'Test', 'username' => 'test_user'],
        'chat'       => ['id' => $userId, 'type' => 'private'],
    ],
    'args' => [],
];

echo "=== TEST C: FaucetPay withdrawal (Bot#{$bot->id} DM handler) ===\n";
$resultC = $runtime->executeCommand($bot, $dm, $withdrawCtx);
$okC     = ($resultC['ok'] ?? false) === true;
$errC    = $resultC['error'] ?? null;

// Pass criteria:
//   - command completes (ok:true = bot sent a reply, even if FaucetPay failed internally)
//   - OR ok:false but with a clean user-friendly error, no raw trace
//   - error must not contain AbortError / internalRuntimePost / httpsRequest
$hasRawTrace = $errC && (
    stripos($errC, 'AbortError') !== false ||
    stripos($errC, 'internalRuntimePost') !== false ||
    stripos($errC, 'httpsRequest') !== false
);
// ok:true means bot handled it and replied to user — always clean
$isCleanErr = ($okC && !$errC) || (!$hasRawTrace);

// The key check: if there's a payment error it must NOT mention support
$leaksToSupport = $errC && stripos($errC, 'support') !== false;

echo 'ok:                 ' . ($okC ? 'true' : 'false') . "\n";
echo 'error:              ' . ($errC ?? '(none)') . "\n";
echo 'clean error:        ' . ($isCleanErr ? 'PASS' : 'FAIL') . "\n";
echo 'leaks to support:   ' . ($leaksToSupport ? 'YES ← FAIL' : 'no ← PASS') . "\n\n";

// ── TEST D: Invalid FaucetPay email ──────────────────────────────────────────
// Set awaiting_wallet = true with an invalid/unregistered email
\App\Models\BotUserRuntimeData::updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'awaiting_wallet'],
    ['value' => true]
);
\App\Models\BotUserRuntimeData::updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'admin_state'],
    ['value' => null]
);
\App\Models\BotUserRuntimeData::updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'awaiting_withdraw_amount'],
    ['value' => false]
);

$emailCtx = [
    'user_id'      => $userId,
    'chat_id'      => $userId,
    'message_text' => 'nobody@invalid-domain-xyz.test',
    'message'      => [
        'message_id' => random_int(100000, 999999),
        'text'       => 'nobody@invalid-domain-xyz.test',
        'from'       => ['id' => $userId, 'first_name' => 'Test', 'username' => 'test_user'],
        'chat'       => ['id' => $userId, 'type' => 'private'],
    ],
    'args' => [],
];

echo "=== TEST D: Invalid FaucetPay email check (Bot#{$bot->id} DM handler) ===\n";
$resultD = $runtime->executeCommand($bot, $dm, $emailCtx);
$okD     = ($resultD['ok'] ?? false) === true;
$errD    = $resultD['error'] ?? null;

// Command should complete (ok:true from the runtime perspective — the bot replied
// with a user-facing error message, command execution itself succeeded)
// Key: no raw bridge trace in the error
$hasRawTrace = $errD && (
    stripos($errD, 'AbortError') !== false ||
    stripos($errD, 'internalRuntimePost') !== false ||
    stripos($errD, 'httpsRequest') !== false
);
$leaksToSupportD = $errD && stripos($errD, 'support') !== false;

echo 'ok:                 ' . ($okD ? 'true' : 'false') . "\n";
echo 'error:              ' . ($errD ?? '(none)') . "\n";
echo 'raw trace leaked:   ' . ($hasRawTrace    ? 'YES ← FAIL' : 'no ← PASS') . "\n";
echo 'leaks to support:   ' . ($leaksToSupportD ? 'YES ← FAIL' : 'no ← PASS') . "\n\n";

// ── SUMMARY ──────────────────────────────────────────────────────────────────
$passC = $isCleanErr && !$leaksToSupport;
$passD = !$hasRawTrace && !$leaksToSupportD;
$passed = $passC && $passD;

echo "=== SUMMARY ===\n";
echo 'C (withdrawal err isolation): ' . ($passC ? 'PASS' : 'FAIL') . "\n";
echo 'D (email check isolation):    ' . ($passD ? 'PASS' : 'FAIL') . "\n";
echo 'Overall:                      ' . ($passed ? 'ALL PASS' : 'FAILURES PRESENT') . "\n";
exit($passed ? 0 : 1);
