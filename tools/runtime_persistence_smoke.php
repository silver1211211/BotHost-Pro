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
$userId = '999000111';
$ctx = [
    'user_id' => $userId,
    'chat_id' => $userId,
    'message_text' => '/botdatapersisttest',
    'message' => [
        'text' => '/botdatapersisttest',
        'from' => ['id' => $userId],
        'chat' => ['id' => $userId],
    ],
    'args' => [],
];

$id = 'test_'.time();
$write = new App\Models\BotCommand([
    'bot_id' => $bot->id,
    'command_name' => '/botdatapersisttest',
    'trigger_type' => 'slash',
    'response_type' => 'code',
    'code' => 'const id = "'.$id.'"; await setBotData("persist_test_key", id); const readNow = await getBotData("persist_test_key", null); await setBotData("persist_test_runtime_read", readNow);',
]);
$writeResult = $runtime->executeCommand($bot, $write, $ctx);
$stored = App\Models\BotRuntimeData::query()->where('bot_id', $bot->id)->where('key', 'persist_test_key')->first();

$read = new App\Models\BotCommand([
    'bot_id' => $bot->id,
    'command_name' => '/botdatareadtest',
    'trigger_type' => 'slash',
    'response_type' => 'code',
    'code' => 'const value = await getBotData("persist_test_key", null); await setBotData("persist_test_second_read", value);',
]);
$readResult = $runtime->executeCommand($bot, $read, $ctx);
$second = App\Models\BotRuntimeData::query()->where('bot_id', $bot->id)->where('key', 'persist_test_second_read')->first();

$crashId = 'crash_'.time();
$crash = new App\Models\BotCommand([
    'bot_id' => $bot->id,
    'command_name' => '/botdatacrashtest',
    'trigger_type' => 'slash',
    'response_type' => 'code',
    'code' => 'await setBotData("persist_crash_key", "'.$crashId.'"); throw new Error("crash after setBotData smoke test");',
]);
$crashResult = $runtime->executeCommand($bot, $crash, $ctx);
$crashStored = App\Models\BotRuntimeData::query()->where('bot_id', $bot->id)->where('key', 'persist_crash_key')->first();

echo json_encode([
    'bot_id' => $bot->id,
    'write_ok' => $writeResult['ok'] ?? null,
    'stored' => $stored?->value,
    'read_ok' => $readResult['ok'] ?? null,
    'second_read' => $second?->value,
    'crash_ok' => $crashResult['ok'] ?? null,
    'crash_error_type' => $crashResult['error_type'] ?? null,
    'crash_stored' => $crashStored?->value,
], JSON_PRETTY_PRINT)."\n";
