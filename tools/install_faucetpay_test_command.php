<?php

use App\Models\Bot;
use App\Models\BotCommand;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$botId = (int) ($argv[1] ?? 0);

if ($botId <= 0) {
    fwrite(STDERR, "Usage: php tools/install_faucetpay_test_command.php BOT_ID\n");
    exit(1);
}

$bot = Bot::query()->find($botId);

if (! $bot) {
    fwrite(STDERR, "Bot {$botId} not found.\n");
    exit(1);
}

$code = <<<'JS'
const TEST_API_KEY = ""; // Optional local-only key. Leave blank to validate the saved bot FaucetPay key.
const TEST_EMAIL = "TEST_EMAIL"; // Replace in the bot editor with a real FaucetPay-linked email/address.
const ENABLE_TEST_PAYOUT = false;
const TEST_PAYOUT_AMOUNT = "1000"; // Already in FaucetPay units; not multiplied by helpers.
const TEST_CURRENCY = "USDT";

const apiKey = TEST_API_KEY || null;
const validation = await faucetPayValidateKey(apiKey);
const balance = apiKey
  ? await faucetPayGetBalance(apiKey, TEST_CURRENCY)
  : await faucetPayBalance(TEST_CURRENCY);
const emailCheck = TEST_EMAIL === "TEST_EMAIL"
  ? { ok: false, error: "Replace TEST_EMAIL with a real FaucetPay-linked email/address before testing." }
  : await faucetPayCheckEmail(TEST_EMAIL, TEST_CURRENCY);

let payout = { ok: false, error: "Test payout disabled." };
if (ENABLE_TEST_PAYOUT && TEST_EMAIL !== "TEST_EMAIL") {
  payout = await faucetPaySend(TEST_EMAIL, TEST_PAYOUT_AMOUNT, TEST_CURRENCY);
}

await replyHTML([
  "<b>FaucetPay Runtime Test</b>",
  "",
  `Validate key: <code>${safeHTML(validation.ok ? "ok" : (validation.error || validation.message || "failed"))}</code>`,
  `Balance: <code>${safeHTML(balance.ok ? String(balance.balance) + " " + (balance.currency || TEST_CURRENCY) : (balance.error || "failed"))}</code>`,
  `Check email: <code>${safeHTML(emailCheck.ok ? "ok" : (emailCheck.error || emailCheck.message || "failed"))}</code>`,
  `Payout: <code>${safeHTML(payout.ok ? "sent" : (payout.error || "disabled"))}</code>`,
].join("\n"));
JS;

$command = BotCommand::query()->updateOrCreate(
    ['bot_id' => $bot->id, 'command_name' => '/test_faucetpay'],
    [
        'display_name' => '/test_faucetpay',
        'trigger_type' => 'slash',
        'response_type' => 'code',
        'status' => 'active',
        'admin_only' => false,
        'code' => $code,
        'response_text' => null,
    ],
);

echo "/test_faucetpay: command_id={$command->id}\n";
