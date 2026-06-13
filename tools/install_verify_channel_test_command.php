<?php

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotRuntimeData;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$botId = (int) ($argv[1] ?? 0);
$channel = trim((string) ($argv[2] ?? '@YOUR_TEST_CHANNEL_USERNAME'));

if ($botId <= 0) {
    fwrite(STDERR, "Usage: php tools/install_verify_channel_test_command.php BOT_ID [@channel_or_chat_id]\n");
    exit(1);
}

$bot = Bot::query()->find($botId);

if (! $bot) {
    fwrite(STDERR, "Bot {$botId} not found.\n");
    exit(1);
}

if ($channel !== '') {
    BotRuntimeData::query()->updateOrCreate(
        ['bot_id' => $bot->id, 'key' => 'test_verify_channel'],
        ['value' => $channel],
    );
}

$code = <<<'JS'
const channel = await getBotData("test_verify_channel", "@YOUR_TEST_CHANNEL_USERNAME");
const result = await checkChannelMember(channel);

const status = result.status || "unknown";
const lines = [
  `Channel checked: <code>${safeHTML(channel)}</code>`,
  `User Telegram ID: <code>${safeHTML(userId)}</code>`,
  `Membership status: <code>${safeHTML(status)}</code>`,
  "",
  result.is_member
    ? `You are a member of ${safeHTML(channel)}`
    : `You have not joined ${safeHTML(channel)} yet`,
];

if (!result.ok && result.message) {
  lines.push("", `Check detail: ${safeHTML(result.message)}`);
}

await replyHTML(lines.join("\n"));
JS;

$command = BotCommand::query()->updateOrCreate(
    ['bot_id' => $bot->id, 'command_name' => '/test_verify_channel'],
    [
        'display_name' => '/test_verify_channel',
        'trigger_type' => 'slash',
        'response_type' => 'code',
        'status' => 'active',
        'admin_only' => false,
        'code' => $code,
        'response_text' => null,
    ],
);

echo "/test_verify_channel: command_id={$command->id}\n";
echo "test_verify_channel={$channel}\n";
