<?php

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotRuntimeData;
use App\Services\NodeRuntimeService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$botId = (int) ($argv[1] ?? 12);
$bot = Bot::query()->findOrFail($botId);
$runtime = app(NodeRuntimeService::class);
$userId = '7701909986';

$context = [
    'user_id' => $userId,
    'chat_id' => $userId,
    'message_text' => '/persistdebug',
    'message' => [
        'text' => '/persistdebug',
        'from' => ['id' => $userId],
        'chat' => ['id' => $userId],
    ],
    'args' => [],
];

$write = BotCommand::query()->where('bot_id', $bot->id)->where('command_name', '/persistdebug')->firstOrFail();
$writeResult = $runtime->executeCommand($bot, $write, $context);
$stored = BotRuntimeData::query()->where('bot_id', $bot->id)->where('key', 'persist_debug_key')->first();

$read = BotCommand::query()->where('bot_id', $bot->id)->where('command_name', '/persistread')->firstOrFail();
$context['message_text'] = '/persistread';
$context['message']['text'] = '/persistread';
$readResult = $runtime->executeCommand($bot, $read, $context);

echo json_encode([
    'bot_id' => $bot->id,
    'persistdebug_ok' => $writeResult['ok'] ?? null,
    'persistdebug_error' => $writeResult['error'] ?? null,
    'persistdebug_replies' => $writeResult['replies'] ?? [],
    'stored_value' => $stored?->value,
    'persistread_ok' => $readResult['ok'] ?? null,
    'persistread_error' => $readResult['error'] ?? null,
    'persistread_replies' => $readResult['replies'] ?? [],
], JSON_PRETTY_PRINT)."\n";

