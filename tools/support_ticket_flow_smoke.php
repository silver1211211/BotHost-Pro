<?php

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotRuntimeData;
use App\Models\BotUserRuntimeData;
use App\Services\NodeRuntimeService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$botId = (int) ($argv[1] ?? 12);
$userId = (string) ($argv[2] ?? '7701909986');

$bot = Bot::query()->findOrFail($botId);
$runtime = app(NodeRuntimeService::class);

BotUserRuntimeData::query()->updateOrCreate(
    ['bot_id' => $bot->id, 'telegram_user_id' => $userId, 'key' => 'awaiting_support_message'],
    ['value' => true],
);

$direct = BotCommand::query()
    ->where('bot_id', $bot->id)
    ->where('trigger_type', 'direct_message')
    ->firstOrFail();

$messageText = 'runtime support smoke '.date('Y-m-d H:i:s');
$context = [
    'user_id' => $userId,
    'chat_id' => $userId,
    'message_text' => $messageText,
    'message' => [
        'message_id' => random_int(100000, 999999),
        'text' => $messageText,
        'from' => ['id' => $userId, 'first_name' => 'Runtime', 'username' => 'runtime_smoke'],
        'chat' => ['id' => $userId, 'type' => 'private'],
    ],
    'args' => [],
];

$directResult = $runtime->executeCommand($bot, $direct, $context);
$tickets = BotRuntimeData::query()
    ->where('bot_id', $bot->id)
    ->where('key', 'like', 'support_ticket_support_%')
    ->get()
    ->map(fn ($row) => is_array($row->value) ? $row->value : null)
    ->filter()
    ->sortByDesc(fn ($ticket) => (int) ($ticket['created_at_ms'] ?? 0))
    ->values();

$ticket = $tickets->first();
$ticketId = $ticket['id'] ?? null;

$adminResult = null;
$finalReplyResult = null;
if ($ticketId) {
    $admin = BotCommand::query()
        ->where('bot_id', $bot->id)
        ->where('command_name', '/admin')
        ->firstOrFail();

    $adminContext = [
        'user_id' => $userId,
        'chat_id' => $userId,
        'message_text' => "/admin reply {$ticketId} {$userId}",
        'callback_data' => "/admin reply {$ticketId} {$userId}",
        'callback_query' => [
            'id' => 'runtime-smoke-'.time(),
            'data' => "/admin reply {$ticketId} {$userId}",
            'from' => ['id' => $userId],
            'message' => ['chat' => ['id' => $userId]],
        ],
        'message' => [
            'text' => "/admin reply {$ticketId} {$userId}",
            'from' => ['id' => $userId],
            'chat' => ['id' => $userId],
        ],
        'args' => ['reply', $ticketId, $userId],
    ];

    $adminResult = $runtime->executeCommand($bot, $admin, $adminContext);

    $replyText = 'runtime smoke admin reply '.date('Y-m-d H:i:s');
    $replyContext = [
        'user_id' => $userId,
        'chat_id' => $userId,
        'message_text' => $replyText,
        'message' => [
            'message_id' => random_int(100000, 999999),
            'text' => $replyText,
            'from' => ['id' => $userId, 'first_name' => 'Runtime', 'username' => 'runtime_smoke'],
            'chat' => ['id' => $userId, 'type' => 'private'],
        ],
        'args' => [],
    ];

    $finalReplyResult = $runtime->executeCommand($bot, $direct, $replyContext);
}

echo json_encode([
    'bot_id' => $bot->id,
    'user_id' => $userId,
    'direct_ok' => $directResult['ok'] ?? null,
    'direct_error' => $directResult['error'] ?? null,
    'ticket_id' => $ticketId,
    'ticket_found_in_direct_key' => (bool) $ticketId,
    'admin_reply_ok' => $adminResult['ok'] ?? null,
    'admin_reply_error' => $adminResult['error'] ?? null,
    'final_reply_ok' => $finalReplyResult['ok'] ?? null,
    'final_reply_error' => $finalReplyResult['error'] ?? null,
], JSON_PRETTY_PRINT)."\n";
