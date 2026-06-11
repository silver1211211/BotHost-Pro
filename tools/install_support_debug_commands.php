<?php

use App\Models\Bot;
use App\Models\BotCommand;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$botId = (int) ($argv[1] ?? 12);
$bot = Bot::query()->find($botId);

if (! $bot) {
    fwrite(STDERR, "Bot {$botId} not found.\n");
    exit(1);
}

$commands = [
    '/persistdebug' => <<<'JS'
const id = "persist_" + Date.now();
await setBotData("persist_debug_key", id);
await replyHTML("Saved persist_debug_key: " + id);
JS,
    '/persistread' => <<<'JS'
const value = await getBotData("persist_debug_key", null);
await replyHTML("Read persist_debug_key: " + value);
JS,
    '/senddebug' => <<<'JS'
const target = args[0] || getArg(0, null);
if (!target) {
  await replyHTML("Usage: /senddebug USER_ID");
} else {
  await sendMessage(target, "Send debug test");
  await replyHTML("Send attempted");
}
JS,
];

foreach ($commands as $name => $code) {
    $command = BotCommand::query()->updateOrCreate(
        ['bot_id' => $bot->id, 'command_name' => $name],
        [
            'display_name' => $name,
            'trigger_type' => 'slash',
            'response_type' => 'code',
            'status' => 'active',
            'admin_only' => false,
            'code' => $code,
            'response_text' => null,
        ],
    );

    echo "{$name}: command_id={$command->id}\n";
}

