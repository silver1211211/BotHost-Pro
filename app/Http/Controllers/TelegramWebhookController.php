<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotCommandLog;
use App\Models\BotUser;
use App\Models\BotLog;
use App\Models\BotUserRuntimeData;
use App\Services\BotRuntimeCacheService;
use App\Services\BotUserTracker;
use App\Services\CommandRuntimeCacheService;
use App\Services\NodeRuntimeService;
use App\Services\RuntimeSettingsService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, string $webhookBot, string $secret, TelegramBotService $telegram, NodeRuntimeService $runtime, BotUserTracker $users, CommandRuntimeCacheService $commandCache, BotRuntimeCacheService $botCache, RuntimeSettingsService $settings): JsonResponse
    {
        $lookupStarted = microtime(true);
        $runtimeBot = $botCache->findWebhookBot($webhookBot, $secret);
        $botLookupMs = $this->elapsedMs($lookupStarted);

        if (! $runtimeBot) {
            if ($settings->boolean('log_webhook_errors', true)) {
                Log::warning('Webhook bot lookup failed.', ['bot_id' => $webhookBot]);
            }

            return response()->json(['ok' => false], 404);
        }

        try {
            return $this->handleWebhook($request, $runtimeBot, $secret, $telegram, $runtime, $users, $commandCache, $settings, $botLookupMs);
        } catch (Throwable $exception) {
            if ($settings->boolean('log_webhook_errors', true)) {
                Log::error('Telegram webhook crashed', [
                    'bot_id' => $runtimeBot->id ?? null,
                    'error' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }

            if (isset($runtimeBot) && $runtimeBot->id) {
                $this->safeBotLog([
                    'bot_id' => $runtimeBot->id,
                    'type' => 'error',
                    'title' => 'Webhook crashed',
                    'message' => $exception->getMessage(),
                    'context' => [
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                    ],
                ]);
            }

            return response()->json(['ok' => true]);
        }
    }

    private function handleWebhook(Request $request, Bot $bot, string $secret, TelegramBotService $telegram, NodeRuntimeService $runtime, BotUserTracker $users, CommandRuntimeCacheService $commandCache, RuntimeSettingsService $settings, int $botLookupMs = 0): JsonResponse
    {
        $startedAt = microtime(true);
        $timings = ['bot_lookup_ms' => $botLookupMs];
        $callbackQueryAnswered = false;

        if (! $bot->webhook_secret || ! hash_equals($bot->webhook_secret, $secret)) {
            if ($settings->boolean('log_webhook_errors', true)) {
                Log::warning('Webhook secret verification failed', ['bot_id' => $bot->id]);
            }

            return response()->json(['ok' => false], 403);
        }

        if ($bot->status !== 'running') {
            return response()->json(['ok' => true]);
        }

        $bot->update(['last_webhook_update_at' => now()]);

        $message = $request->input('message');
        $callbackQuery = $request->input('callback_query');

        if (! is_array($message) && ! is_array($callbackQuery)) {
            return response()->json(['ok' => true]);
        }

        $botUser = $users->trackFromTelegramUpdate($bot, $request->all());

        $effectiveMessage = is_array($message) ? $message : (array) data_get($callbackQuery, 'message', []);
        $from = is_array($callbackQuery) ? (array) data_get($callbackQuery, 'from', []) : (array) data_get($effectiveMessage, 'from', []);
        $callbackData = is_array($callbackQuery) ? data_get($callbackQuery, 'data') : null;
        $isSlashCallback = is_array($callbackQuery) && is_string($callbackData) && str_starts_with(trim($callbackData), '/');
        $text = is_string($callbackData) ? $callbackData : ($effectiveMessage['text'] ?? $effectiveMessage['caption'] ?? null);
        $chatId = data_get($effectiveMessage, 'chat.id');
        $fromId = data_get($from, 'id');
        $username = data_get($from, 'username');
        $firstName = data_get($from, 'first_name');
        $lastName = data_get($from, 'last_name');
        $languageCode = data_get($from, 'language_code');
        $isBot = (bool) data_get($from, 'is_bot', false);

        if ($chatId === null) {
            return response()->json(['ok' => true]);
        }

        if (! is_string($text)) {
            if (! is_array($message) || $callbackQuery) {
                return response()->json(['ok' => true]);
            }

            $text = '';
        }

        $text = preg_replace('/^\R+|\R+$/u', '', $text) ?? $text;
        $commandParts = is_string($callbackData)
            ? (str_starts_with(trim($callbackData), '/')
                ? $this->parseCommandText($callbackData, $bot->telegram_username)
                : ['trigger' => $text, 'args' => []])
            : $this->parseCommandText($text, $bot->telegram_username);
        $routeDebug = array_merge([
            'bot_id' => $bot->id,
            'update_type' => $this->updateType(is_array($message) ? $message : null, is_array($callbackQuery) ? $callbackQuery : null),
            'has_message' => is_array($message),
            'has_text' => is_string(data_get($effectiveMessage, 'text')) && data_get($effectiveMessage, 'text') !== '',
            'has_caption' => is_string(data_get($effectiveMessage, 'caption')) && data_get($effectiveMessage, 'caption') !== '',
            'chat_id' => $chatId,
            'user_id' => $fromId,
            'command_candidate' => $commandParts['trigger'] !== '' ? str($commandParts['trigger'])->limit(80, '')->toString() : null,
            'command_match_found' => false,
            'direct_handler_found' => false,
            'direct_handler_id' => null,
            'direct_handler_trigger_type' => null,
            'route_decision' => 'received',
        ], $this->messageMediaFlags(is_array($effectiveMessage) ? $effectiveMessage : []));

        $telegramContext = [
            'chat_id' => $chatId,
            'user_id' => $fromId,
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'language_code' => $languageCode,
            'is_bot' => $isBot,
            'message_text' => $text,
            'message' => $effectiveMessage,
            'update' => $request->all(),
            'callback_query' => is_array($callbackQuery) ? $callbackQuery : null,
            'callback_query_id' => is_array($callbackQuery) ? data_get($callbackQuery, 'id') : null,
            'callback_data' => is_string($callbackData) ? $callbackData : null,
            'trigger' => $commandParts['trigger'],
            'args' => $commandParts['args'],
            'bot_user_id' => $botUser?->id,
        ];

        Log::info('[BotHost] update_received', [
            'bot_id' => $bot->id,
            'user_id' => $fromId,
            'chat_id' => $chatId,
            'has_message' => is_array($message),
            'has_callback_query' => is_array($callbackQuery),
            'text_preview' => str($text)->limit(80, '')->toString(),
            'callback_data_preview' => is_string($callbackData) ? str($callbackData)->limit(80, '')->toString() : null,
        ]);

        if (! is_array($callbackQuery) && $commandParts['trigger'] === '/cancel' && $fromId !== null) {
            $this->cancelAllFlows($bot, (string) $fromId);
        }

        if (in_array($bot->status, ['paused', 'suspended'], true)) {
            return response()->json(['ok' => true]);
        }

        if ($botUser) {
            if ($botUser->status === 'blocked') {
                $this->commandLog($bot, null, $telegramContext, [
                    'status' => 'blocked_user',
                ]);

                return response()->json(['ok' => true]);
            }

            if ($botUser->status === 'paused') {
                $this->commandLog($bot, null, $telegramContext, [
                    'status' => 'paused_user',
                ]);

                return response()->json(['ok' => true]);
            }

            if ($botUser->status === 'deleted') {
                // Deleted means data was reset — restore as a fresh user and continue
                \App\Models\BotUserRuntimeData::where('bot_id', $bot->id)
                    ->where('telegram_user_id', $botUser->telegram_user_id)
                    ->delete();

                $botUser->forceFill([
                    'status' => 'active',
                    'first_seen_at' => now(),
                    'message_count' => 0,
                    'command_count' => 0,
                    'blocked_at' => null,
                ])->save();

                Log::info('[BotHost] bot_user_recreated_after_delete', [
                    'bot_id' => $bot->id,
                    'telegram_user_id' => $botUser->telegram_user_id,
                    'action' => 'restored_as_fresh',
                ]);
            }
        }

        if ($botUser && ! in_array($botUser->status, BotUser::STATUSES, true)) {
            Log::warning('Bot user has unknown status; continuing as active.', [
                'bot_id' => $bot->id,
                'bot_user_id' => $botUser->id,
                'status' => $botUser->status,
            ]);
        }

        if (is_array($callbackQuery)) {
            Log::info('[BotHost] callback_update_received', [
                'bot_id' => $bot->id,
                'user_id' => $fromId,
                'chat_id' => $chatId,
            ]);

            Log::info('[BotHost] callback_data_received', [
                'bot_id' => $bot->id,
                'user_id' => $fromId,
                'chat_id' => $chatId,
                'callback_data' => is_string($callbackData) ? str($callbackData)->limit(80, '')->toString() : null,
            ]);

            if ($isSlashCallback) {
                Log::info('[BotHost] callback_command_detected', [
                    'bot_id' => $bot->id,
                    'user_id' => $fromId,
                    'chat_id' => $chatId,
                    'callback_data' => str((string) $callbackData)->limit(80, '')->toString(),
                    'command_name' => $commandParts['trigger'],
                    'args' => $commandParts['args'],
                    'command_id' => null,
                ]);
            }
        }

        $matchStarted = microtime(true);
        $command = $commandCache->findMatchingCommandForUpdate($bot, $text, $commandParts['trigger'], is_string($callbackData) ? $callbackData : null, false);
        $commandMatchedBeforeFlow = $command !== null;
        $routeDebug['command_match_found'] = $commandMatchedBeforeFlow;
        $directHandler = ! $isSlashCallback ? $commandCache->findDirectMessageHandler($bot) : null;
        $routeDebug['direct_handler_found'] = $directHandler !== null;
        $routeDebug['direct_handler_id'] = $directHandler?->id;
        $routeDebug['direct_handler_trigger_type'] = $directHandler?->effectiveTriggerType();

        if ($isSlashCallback && $command) {
            Log::info('[BotHost] callback_command_match_found', [
                'bot_id' => $bot->id,
                'user_id' => $fromId,
                'chat_id' => $chatId,
                'callback_data' => str((string) $callbackData)->limit(80, '')->toString(),
                'command_name' => $command->command_name,
                'args' => $commandParts['args'],
                'command_id' => $command->id,
            ]);
        }

        if (! is_array($callbackQuery) && $commandMatchedBeforeFlow) {
            Log::info('[BotHost] text_command_detected', [
                'bot_id' => $bot->id,
                'user_id' => $fromId,
                'chat_id' => $chatId,
                'text_preview' => str($text)->limit(80, '')->toString(),
                'command_name' => $command->command_name,
                'command_id' => $command->id,
                'trigger_type' => $command->effectiveTriggerType(),
            ]);
        }

        if ($commandMatchedBeforeFlow) {
            Log::info('[BotHost] command_matched_before_flow', [
                'bot_id' => $bot->id,
                'user_id' => $fromId,
                'chat_id' => $chatId,
                'command_id' => $command->id,
                'command_name' => $command->command_name,
                'trigger_type' => $command->effectiveTriggerType(),
                'is_callback_command' => $isSlashCallback,
            ]);

            if ($fromId !== null) {
                $activeFlow = $this->resolveCommandFlow($bot, (string) $fromId);

                if ($activeFlow) {
                    Log::info('[BotHost] command_flow_skipped_due_to_command_match', [
                        'bot_id' => $bot->id,
                        'user_id' => $fromId,
                        'chat_id' => $chatId,
                        'matched_command_id' => $command->id,
                        'matched_command_name' => $command->command_name,
                        'skipped_command_id' => $activeFlow['command']->id ?? null,
                        'skipped_command_name' => $activeFlow['command']->command_name ?? null,
                        'step' => $activeFlow['context']['step'] ?? null,
                    ]);

                    $this->clearCommandFlow($bot, (string) $fromId);
                }
            }

            $routeDebug['route_decision'] = 'command';
            Log::info('[BotHost] direct_message_handler_skipped_due_to_command_match', [
                'bot_id' => $bot->id,
                'user_id' => $fromId,
                'chat_id' => $chatId,
                'command_id' => $command->id,
                'command_name' => $command->command_name,
            ]);
        }

        if (! $command && ! is_array($callbackQuery) && $fromId !== null) {
            $flow = $this->resolveCommandFlow($bot, (string) $fromId);

            if ($flow) {
                $command = $flow['command'];
                $telegramContext['command_flow'] = $flow['context'];
                $telegramContext['trigger'] = $command->command_name;
                $routeDebug['route_decision'] = 'active_command_flow';

                Log::info('[BotHost] active_command_flow_routed', [
                    'bot_id' => $bot->id,
                    'user_id' => $fromId,
                    'chat_id' => $chatId,
                    'command_id' => $command->id,
                    'command_name' => $command->command_name,
                    'step' => $flow['context']['step'] ?? null,
                ]);
            }
        }

        if (! $command && ! $isSlashCallback) {
            $command = $directHandler;

            if ($command) {
                $routeDebug['route_decision'] = 'direct_message_handler';
                Log::info('[BotHost] direct_message_handler_routed', [
                    'bot_id' => $bot->id,
                    'user_id' => $fromId,
                    'chat_id' => $chatId,
                    'command_id' => $command->id,
                    'text_preview' => str($text)->limit(80, '')->toString(),
                    'callback_data_preview' => is_string($callbackData) ? str($callbackData)->limit(80, '')->toString() : null,
                ]);
            }
        }

        if (! $command) {
            $routeDebug['route_decision'] = $isSlashCallback ? 'callback_no_match' : 'no_match';
        }

        $timings['command_match_ms'] = $this->elapsedMs($matchStarted);
        Log::info('[BotHost] webhook_route_decision', $routeDebug);
        Log::info('[BotHost] Command Match', [
            'bot_id' => $bot->id,
            'user_id' => $fromId,
            'chat_id' => $chatId,
            'text_preview' => str($text)->limit(80, '')->toString(),
            'callback_data_preview' => is_string($callbackData) ? str($callbackData)->limit(80, '')->toString() : null,
            'command_id' => $command?->id,
            'trigger_type' => $command?->effectiveTriggerType(),
            'trigger' => $commandParts['trigger'],
        ]);

        if (! $command) {
            if ($isSlashCallback) {
                Log::info('[BotHost] callback_command_match_not_found', [
                    'bot_id' => $bot->id,
                    'user_id' => $fromId,
                    'chat_id' => $chatId,
                    'callback_data' => str((string) $callbackData)->limit(80, '')->toString(),
                    'command_name' => $commandParts['trigger'],
                    'args' => $commandParts['args'],
                    'command_id' => null,
                ]);
            }

            Log::info('[BotHost] no_match_routed', [
                'bot_id' => $bot->id,
                'user_id' => $fromId,
                'chat_id' => $chatId,
                'text_preview' => str($text)->limit(80, '')->toString(),
                'callback_data_preview' => is_string($callbackData) ? str($callbackData)->limit(80, '')->toString() : null,
            ]);

            $this->commandLog($bot, null, $telegramContext, [
                'status' => 'no_match',
            ]);

            return response()->json(['ok' => true]);
        }

        if ($command->effectiveTriggerType() === 'direct_message') {
            Log::debug('[BotHost] direct_message_handler_executed', [
                'bot_id' => $bot->id,
                'command_id' => $command->id,
                'trigger_type' => $command->effectiveTriggerType(),
                'text_preview' => str($text)->limit(80, '')->toString(),
            ]);
        }

        $users->incrementCommandCount($botUser);

        $token = Crypt::decryptString($bot->getRawOriginal('token_encrypted'));

        if ($isSlashCallback && filled($telegramContext['callback_query_id'] ?? null)) {
            try {
                $telegram->answerCallbackQuery($token, (string) $telegramContext['callback_query_id'], '');
                $callbackQueryAnswered = true;
            } catch (Throwable $exception) {
                Log::warning('[BotHost] callback_answer_failed', [
                    'bot_id' => $bot->id,
                    'user_id' => $fromId,
                    'chat_id' => $chatId,
                    'callback_data' => str((string) $callbackData)->limit(80, '')->toString(),
                    'command_name' => $command->command_name,
                    'command_id' => $command->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($isSlashCallback) {
            Log::info('[BotHost] callback_command_started', [
                'bot_id' => $bot->id,
                'user_id' => $fromId,
                'chat_id' => $chatId,
                'callback_data' => str((string) $callbackData)->limit(80, '')->toString(),
                'command_name' => $command->command_name,
                'args' => $commandParts['args'],
                'command_id' => $command->id,
            ]);

            Log::info('[BotHost] callback_command_execute_start', [
                'bot_id' => $bot->id,
                'user_id' => $fromId,
                'chat_id' => $chatId,
                'callback_data' => str((string) $callbackData)->limit(80, '')->toString(),
                'command_name' => $command->command_name,
                'args' => $commandParts['args'],
                'command_id' => $command->id,
            ]);
        }

        if (filled($command->code)) {
            if ($command->effectiveTriggerType() === 'direct_message') {
                Log::info('[BotHost] direct_message_handler_started', [
                    'bot_id' => $bot->id,
                    'user_id' => $fromId,
                    'chat_id' => $chatId,
                    'command_id' => $command->id,
                    'command_name' => $command->command_name,
                ]);
            }

            $updateId = $request->input('update_id');
            if ($updateId !== null) {
                $dedupeKey = "webhook_upd_{$bot->id}_{$updateId}";
                if (! Cache::add($dedupeKey, 1, now()->addSeconds(120))) {
                    return response()->json(['ok' => true]);
                }
            }

            $runtimeStarted = microtime(true);
            Log::info('[BotHost] Calling Node runtime', [
                'bot_id' => $bot->id,
                'command_id' => $command->id,
                'chat_id' => $chatId,
                'runtime_mode' => $settings->string('runtime_mode', 'local'),
            ]);
            $result = $runtime->executeCommand($bot, $command, $telegramContext);
            $timings['runtime_execution_ms'] = $this->elapsedMs($runtimeStarted);
            Log::info('[BotHost] Runtime result', [
                'execution_id' => $result['execution_id'] ?? null,
                'ok' => $result['ok'] ?? false,
                'replies_count' => is_countable($result['replies'] ?? null) ? count($result['replies']) : 0,
                'error' => $result['error'] ?? null,
                'error_type' => $result['error_type'] ?? null,
                'first_reply_type' => $result['replies'][0]['type'] ?? null,
                'runtime_path' => $result['runtime_path'] ?? null,
            ]);

            $runtimeReplyCount = is_countable($result['replies'] ?? null) ? count($result['replies']) : 0;

            if (($result['ok'] ?? false) || $runtimeReplyCount > 0) {
                $sentReply = false;
                $usedFallback = (bool) data_get($result, 'runtime_path.fallback_used', false);
                $replyCount = 0;

                foreach ($result['replies'] as $reply) {
                    if (($reply['type'] ?? null) === 'run_command' && filled($reply['command_name'] ?? null)) {
                        $nestedCount = $this->executeRunCommandAction(
                            $bot,
                            $command,
                            $reply,
                            $telegramContext,
                            $token,
                            $chatId,
                            $effectiveMessage,
                            $telegram,
                            $runtime,
                            $settings,
                        );

                        if ($nestedCount > 0) {
                            $sentReply = true;
                            $replyCount += $nestedCount;
                        }

                        continue;
                    }

                    if (($reply['type'] ?? null) === 'text' && filled($reply['text'] ?? null)) {
                        $targetChatId = $reply['chat_id'] ?? $chatId;
                        $sendStarted = microtime(true);
                        Log::info('[BotHost] Sending message to Telegram', [
                            'bot_id' => $bot->id,
                            'chat_id' => $targetChatId,
                            'text_preview' => str((string) $reply['text'])->limit(120, '')->toString(),
                            'parse_mode' => $reply['parse_mode'] ?? null,
                            'fallback_used' => $usedFallback,
                        ]);
                        $sendResult = $telegram->sendMessage(
                            $token,
                            $targetChatId,
                            (string) $reply['text'],
                            $reply['parse_mode'] ?? null,
                            is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                            (bool) ($reply['disable_web_page_preview'] ?? false),
                            (bool) ($reply['protect_content'] ?? false),
                            $reply['reply_to_message_id'] ?? null,
                        );
                        $timings['telegram_send_ms'] = ($timings['telegram_send_ms'] ?? 0) + $this->elapsedMs($sendStarted);
                        Log::info('[BotHost] Telegram API sendMessage', [
                            'bot_id' => $bot->id,
                            'chat_id' => $targetChatId,
                            'telegram_send_ok' => (bool) ($sendResult['ok'] ?? false),
                            'error_code' => $sendResult['error_code'] ?? null,
                            'message' => $sendResult['ok'] ?? false ? null : ($sendResult['message'] ?? null),
                        ]);

                        if ($sendResult['ok'] ?? false) {
                            $sentReply = true;
                            $replyCount++;
                        } else {
                            $this->botLog($bot, 'error', 'Telegram sendMessage failed', $sendResult['message'] ?? 'Telegram sendMessage failed.', [
                                'command_id' => $command->id,
                                'command_name' => $command->command_name,
                                'execution_id' => $result['execution_id'] ?? null,
                            ]);
                        }
                    }

                    if (($reply['type'] ?? null) === 'photo' && filled($reply['photo_url'] ?? null)) {
                        $targetChatId = $reply['chat_id'] ?? $chatId;
                        $sendStarted = microtime(true);
                        Log::info('[BotHost] Sending photo to Telegram', [
                            'bot_id' => $bot->id,
                            'chat_id' => $targetChatId,
                            'caption_preview' => isset($reply['caption']) ? str((string) $reply['caption'])->limit(120, '')->toString() : null,
                            'fallback_used' => $usedFallback,
                        ]);
                        $sendResult = $telegram->sendPhoto(
                            $token,
                            $targetChatId,
                            (string) $reply['photo_url'],
                            isset($reply['caption']) ? (string) $reply['caption'] : null,
                            $reply['parse_mode'] ?? null,
                            is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                            (bool) ($reply['protect_content'] ?? false),
                            $reply['reply_to_message_id'] ?? null,
                        );
                        $timings['telegram_send_ms'] = ($timings['telegram_send_ms'] ?? 0) + $this->elapsedMs($sendStarted);

                        if ($sendResult['ok'] ?? false) {
                            $sentReply = true;
                            $replyCount++;
                        } else {
                            $this->botLog($bot, 'error', 'Telegram sendPhoto failed', $sendResult['message'] ?? 'Telegram sendPhoto failed.', [
                                'command_id' => $command->id,
                                'command_name' => $command->command_name,
                                'execution_id' => $result['execution_id'] ?? null,
                            ]);
                        }
                    }

                    if (($reply['type'] ?? null) === 'edit_message_text' && filled($reply['text'] ?? null)) {
                        $sendStarted = microtime(true);
                        $sendResult = $telegram->editMessageText(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            $reply['message_id'] ?? data_get($effectiveMessage, 'message_id'),
                            (string) $reply['text'],
                            $reply['parse_mode'] ?? null,
                            is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                            (bool) ($reply['disable_web_page_preview'] ?? false),
                        );
                        $timings['telegram_send_ms'] = ($timings['telegram_send_ms'] ?? 0) + $this->elapsedMs($sendStarted);

                        if ($sendResult['ok'] ?? false) {
                            $sentReply = true;
                            $replyCount++;
                        } else {
                            $this->botLog($bot, 'error', 'Telegram editMessageText failed', $sendResult['message'] ?? 'Telegram editMessageText failed.', [
                                'command_id' => $command->id,
                                'command_name' => $command->command_name,
                                'execution_id' => $result['execution_id'] ?? null,
                            ]);
                        }
                    }

                    if (($reply['type'] ?? null) === 'edit_message_caption') {
                        $sendStarted = microtime(true);
                        $sendResult = $telegram->editMessageCaption(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            $reply['message_id'] ?? data_get($effectiveMessage, 'message_id'),
                            array_key_exists('caption', $reply) ? (string) $reply['caption'] : null,
                            $reply['parse_mode'] ?? null,
                            is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                        );
                        $timings['telegram_send_ms'] = ($timings['telegram_send_ms'] ?? 0) + $this->elapsedMs($sendStarted);

                        if ($sendResult['ok'] ?? false) {
                            $sentReply = true;
                            $replyCount++;
                        } else {
                            $this->botLog($bot, 'error', 'Telegram editMessageCaption failed', $sendResult['message'] ?? 'Telegram editMessageCaption failed.', [
                                'command_id' => $command->id,
                                'command_name' => $command->command_name,
                                'execution_id' => $result['execution_id'] ?? null,
                            ]);
                        }
                    }

                    if (($reply['type'] ?? null) === 'edit_message_reply_markup') {
                        $sendStarted = microtime(true);
                        $sendResult = $telegram->editMessageReplyMarkup(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            $reply['message_id'] ?? data_get($effectiveMessage, 'message_id'),
                            is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                        );
                        $timings['telegram_send_ms'] = ($timings['telegram_send_ms'] ?? 0) + $this->elapsedMs($sendStarted);

                        if ($sendResult['ok'] ?? false) {
                            $sentReply = true;
                            $replyCount++;
                        } else {
                            $this->botLog($bot, 'error', 'Telegram editMessageReplyMarkup failed', $sendResult['message'] ?? 'Telegram editMessageReplyMarkup failed.', [
                                'command_id' => $command->id,
                                'command_name' => $command->command_name,
                                'execution_id' => $result['execution_id'] ?? null,
                            ]);
                        }
                    }

                    if (($reply['type'] ?? null) === 'delete_message' && isset($reply['message_id'])) {
                        $sendStarted = microtime(true);
                        $sendResult = $telegram->deleteMessage($token, $reply['chat_id'] ?? $chatId, $reply['message_id']);
                        $timings['telegram_send_ms'] = ($timings['telegram_send_ms'] ?? 0) + $this->elapsedMs($sendStarted);

                        if ($sendResult['ok'] ?? false) {
                            $sentReply = true;
                            $replyCount++;
                        } else {
                            $this->botLog($bot, 'error', 'Telegram deleteMessage failed', $sendResult['message'] ?? 'Telegram deleteMessage failed.', [
                                'command_id' => $command->id,
                                'command_name' => $command->command_name,
                                'execution_id' => $result['execution_id'] ?? null,
                            ]);
                        }
                    }

                    if (($reply['type'] ?? null) === 'answer_callback_query' && filled($reply['callback_query_id'] ?? null)) {
                        $sendStarted = microtime(true);
                        $sendResult = $telegram->answerCallbackQuery(
                            $token,
                            (string) $reply['callback_query_id'],
                            (string) ($reply['text'] ?? ''),
                            (bool) ($reply['show_alert'] ?? false),
                            filled($reply['url'] ?? null) ? (string) $reply['url'] : null,
                            (int) ($reply['cache_time'] ?? 0),
                        );
                        $timings['telegram_send_ms'] = ($timings['telegram_send_ms'] ?? 0) + $this->elapsedMs($sendStarted);

                        if ($sendResult['ok'] ?? false) {
                            $sentReply = true;
                            $replyCount++;
                        } else {
                            $this->botLog($bot, 'error', 'Telegram answerCallbackQuery failed', $sendResult['message'] ?? 'Telegram answerCallbackQuery failed.', [
                                'command_id' => $command->id,
                                'command_name' => $command->command_name,
                                'execution_id' => $result['execution_id'] ?? null,
                            ]);
                        }
                    }

                    if (($reply['type'] ?? null) === 'document' && filled($reply['document'] ?? null)) {
                        $sendResult = $telegram->sendDocument(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            (string) $reply['document'],
                            isset($reply['caption']) ? (string) $reply['caption'] : null,
                            $reply['parse_mode'] ?? null,
                            is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                            (bool) ($reply['protect_content'] ?? false),
                            $reply['reply_to_message_id'] ?? null,
                        );
                        if ($sendResult['ok'] ?? false) { $sentReply = true; $replyCount++; }
                    }

                    if (($reply['type'] ?? null) === 'video' && filled($reply['video'] ?? null)) {
                        $sendResult = $telegram->sendVideo(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            (string) $reply['video'],
                            isset($reply['caption']) ? (string) $reply['caption'] : null,
                            $reply['parse_mode'] ?? null,
                            is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                            (bool) ($reply['protect_content'] ?? false),
                            $reply['reply_to_message_id'] ?? null,
                        );
                        if ($sendResult['ok'] ?? false) { $sentReply = true; $replyCount++; }
                    }

                    if (($reply['type'] ?? null) === 'audio' && filled($reply['audio'] ?? null)) {
                        $sendResult = $telegram->sendAudio(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            (string) $reply['audio'],
                            isset($reply['caption']) ? (string) $reply['caption'] : null,
                            $reply['parse_mode'] ?? null,
                            is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                            (bool) ($reply['protect_content'] ?? false),
                            $reply['reply_to_message_id'] ?? null,
                        );
                        if ($sendResult['ok'] ?? false) { $sentReply = true; $replyCount++; }
                    }

                    if (($reply['type'] ?? null) === 'animation' && filled($reply['animation'] ?? null)) {
                        $sendResult = $telegram->sendAnimation(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            (string) $reply['animation'],
                            isset($reply['caption']) ? (string) $reply['caption'] : null,
                            $reply['parse_mode'] ?? null,
                            is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                            (bool) ($reply['protect_content'] ?? false),
                            $reply['reply_to_message_id'] ?? null,
                        );
                        if ($sendResult['ok'] ?? false) { $sentReply = true; $replyCount++; }
                    }

                    if (($reply['type'] ?? null) === 'sticker' && filled($reply['sticker'] ?? null)) {
                        $sendResult = $telegram->sendSticker($token, $reply['chat_id'] ?? $chatId, (string) $reply['sticker']);
                        if ($sendResult['ok'] ?? false) { $sentReply = true; $replyCount++; }
                    }

                    if (($reply['type'] ?? null) === 'location' && isset($reply['latitude'], $reply['longitude'])) {
                        $sendResult = $telegram->sendLocation(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            (float) $reply['latitude'],
                            (float) $reply['longitude'],
                        );
                        if ($sendResult['ok'] ?? false) { $sentReply = true; $replyCount++; }
                    }

                    if (($reply['type'] ?? null) === 'contact' && filled($reply['phone_number'] ?? null) && filled($reply['first_name'] ?? null)) {
                        $sendResult = $telegram->sendContact(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            (string) $reply['phone_number'],
                            (string) $reply['first_name'],
                            filled($reply['last_name'] ?? null) ? (string) $reply['last_name'] : null,
                        );
                        if ($sendResult['ok'] ?? false) { $sentReply = true; $replyCount++; }
                    }

                    if (($reply['type'] ?? null) === 'copy_message' && filled($reply['from_chat_id'] ?? null) && isset($reply['message_id'])) {
                        $sendResult = $telegram->copyMessage(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            $reply['from_chat_id'],
                            $reply['message_id'],
                            filled($reply['caption'] ?? null) ? (string) $reply['caption'] : null,
                            $reply['parse_mode'] ?? null,
                            is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                        );
                        if ($sendResult['ok'] ?? false) { $sentReply = true; $replyCount++; }
                    }

                    if (($reply['type'] ?? null) === 'forward_message' && filled($reply['from_chat_id'] ?? null) && isset($reply['message_id'])) {
                        $sendResult = $telegram->forwardMessage(
                            $token,
                            $reply['chat_id'] ?? $chatId,
                            $reply['from_chat_id'],
                            $reply['message_id'],
                            (bool) ($reply['disable_notification'] ?? false),
                            (bool) ($reply['protect_content'] ?? false),
                        );
                        if ($sendResult['ok'] ?? false) { $sentReply = true; $replyCount++; }
                    }
                }

                // Auto-answer callback query if user code did not answer it
                if (is_array($callbackQuery) && filled($telegramContext['callback_query_id'] ?? null)) {
                    $cbAlreadyAnswered = collect($result['replies'])->contains(fn ($r) => ($r['type'] ?? null) === 'answer_callback_query');
                    if (! $cbAlreadyAnswered && ! $callbackQueryAnswered) {
                        $telegram->answerCallbackQuery($token, (string) $telegramContext['callback_query_id'], '');
                        $callbackQueryAnswered = true;
                    }
                }

                if ($isSlashCallback) {
                    Log::info('[BotHost] callback_command_execute_done', [
                        'bot_id' => $bot->id,
                        'user_id' => $fromId,
                        'chat_id' => $chatId,
                        'command_id' => $command->id,
                        'callback_data' => str($callbackData)->limit(80, '')->toString(),
                        'command_name' => $command->command_name,
                        'args' => $commandParts['args'],
                        'ok' => $result['ok'],
                    ]);
                }

                if (! $sentReply && $runtimeReplyCount === 0 && filled($command->response_text)) {
                    $sendStarted = microtime(true);
                    $sendResult = $telegram->sendMessage($token, $chatId, $command->response_text);
                    $timings['telegram_send_ms'] = ($timings['telegram_send_ms'] ?? 0) + $this->elapsedMs($sendStarted);
                    $sentReply = true;
                    $usedFallback = true;
                    $replyCount = 1;
                    Log::info('[BotHost] Telegram fallback response send', [
                        'bot_id' => $bot->id,
                        'chat_id' => $chatId,
                        'text_preview' => str((string) $command->response_text)->limit(120, '')->toString(),
                        'telegram_send_ok' => (bool) ($sendResult['ok'] ?? false),
                        'error_code' => $sendResult['error_code'] ?? null,
                    ]);
                }

                Log::info('[BotHost] Runtime reply actions processed', [
                    'bot_id' => $bot->id,
                    'command_id' => $command->id,
                    'fallback_used' => $usedFallback,
                    'action_count' => $runtimeReplyCount,
                    'first_action_type' => $result['replies'][0]['type'] ?? null,
                    'telegram_send_ok' => $sentReply,
                ]);

                $status = $sentReply
                    ? ($usedFallback ? 'fallback_response' : 'success')
                    : (($result['ok'] ?? false) ? 'no_reply' : 'failed');

                $this->commandLog($bot, $command, $telegramContext, [
                    'status' => $status,
                    'reply_count' => $replyCount,
                    'execution_id' => $result['execution_id'] ?? null,
                    'execution_time_ms' => $result['execution_time_ms'] ?? null,
                ]);

                if (in_array($status, ['success', 'fallback_response'], true)) {
                    $this->markCommandSuccess($command);
                }

                $timings['total_command_ms'] = $this->elapsedMs($startedAt);
                $this->logSlowCommandIfNeeded($settings, $bot, $command, $telegramContext, $timings);

                return response()->json(['ok' => true]);
            }

            $status = ($result['error_type'] ?? null) === 'RuntimeUnavailable' ? 'runtime_unavailable' : 'failed';

            $this->commandLog($bot, $command, $telegramContext, [
                'status' => $status,
                'execution_id' => $result['execution_id'] ?? null,
                'execution_time_ms' => $result['execution_time_ms'] ?? null,
                'error_type' => $result['error_type'] ?? 'RuntimeExecutionError',
                'error_message' => $result['error'] ?? 'Runtime execution failed.',
                'public_error_message' => 'Command error. Please contact the bot owner.',
                'internal_error_type' => $result['error_type'] ?? 'RuntimeExecutionError',
                'internal_error_message' => $result['error'] ?? 'Runtime execution failed.',
                'internal_error_stack' => $result['error_stack'] ?? null,
            ]);
            $this->logRuntimeFailureIfNeeded($settings, $bot, $command, $telegramContext, $result);
            $this->markCommandFailure($command);
            $sendStarted = microtime(true);
            $telegram->sendMessage($token, $chatId, 'Command error. Please contact the bot owner.');
            $timings['telegram_send_ms'] = ($timings['telegram_send_ms'] ?? 0) + $this->elapsedMs($sendStarted);
            $timings['total_command_ms'] = $this->elapsedMs($startedAt);
            $this->logSlowCommandIfNeeded($settings, $bot, $command, $telegramContext, $timings);

            return response()->json(['ok' => true]);
        }

        $responseText = filled($command->response_text)
            ? $command->response_text
            : 'Command received, but no response has been configured.';

        $sendStarted = microtime(true);
        $telegram->sendMessage($token, $chatId, $responseText);
        $timings['telegram_send_ms'] = $this->elapsedMs($sendStarted);

        // Auto-answer callback query for no-code commands
        if (is_array($callbackQuery) && filled($telegramContext['callback_query_id'] ?? null) && ! $callbackQueryAnswered) {
            $telegram->answerCallbackQuery($token, (string) $telegramContext['callback_query_id'], '');
            $callbackQueryAnswered = true;
        }

        $status = filled($command->response_text) ? 'fallback_response' : 'no_reply';
        $this->commandLog($bot, $command, $telegramContext, [
            'status' => $status,
            'reply_count' => 1,
        ]);

        if ($status === 'fallback_response') {
            $this->markCommandSuccess($command);
        }

        if ($isSlashCallback) {
            Log::info('[BotHost] callback_command_execute_done', [
                'bot_id' => $bot->id,
                'user_id' => $fromId,
                'chat_id' => $chatId,
                'command_id' => $command->id,
                'callback_data' => str((string) $callbackData)->limit(80, '')->toString(),
                'command_name' => $command->command_name,
                'args' => $commandParts['args'],
                'ok' => true,
            ]);
        }

        $timings['total_command_ms'] = $this->elapsedMs($startedAt);
        $this->logSlowCommandIfNeeded($settings, $bot, $command, $telegramContext, $timings);

        return response()->json(['ok' => true]);
    }

    private function log(string $action, string $description): void
    {
        try {
            if (! Schema::hasTable('activity_logs')) {
                return;
            }

            ActivityLog::create([
                'user_id' => null,
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to write activity log', [
                'error' => $exception->getMessage(),
                'action' => $action,
            ]);
        }
    }

    private function parseCommandText(string $text, ?string $botUsername = null): array
    {
        $parts = preg_split('/\s+/u', trim($text)) ?: [];
        $trigger = (string) ($parts[0] ?? '');
        $args = array_values(array_filter(array_slice($parts, 1), fn ($arg) => $arg !== ''));

        if (str_starts_with($trigger, '/') && str_contains($trigger, '@')) {
            [$command, $username] = explode('@', $trigger, 2);

            if ($botUsername === null || strcasecmp($username, $botUsername) === 0) {
                $trigger = $command;
            }
        }

        return [
            'trigger' => $trigger,
            'args' => $args,
        ];
    }

    private function updateType(?array $message, ?array $callbackQuery): string
    {
        if ($callbackQuery !== null) {
            return 'callback_query';
        }

        if ($message === null) {
            return 'unknown';
        }

        foreach (['photo', 'document', 'video', 'audio', 'voice', 'animation', 'sticker', 'text'] as $type) {
            if (array_key_exists($type, $message)) {
                return $type;
            }
        }

        return 'message';
    }

    private function messageMediaFlags(array $message): array
    {
        return [
            'has_photo' => is_array($message['photo'] ?? null) && $message['photo'] !== [],
            'has_document' => is_array($message['document'] ?? null),
            'has_video' => is_array($message['video'] ?? null),
            'has_audio' => is_array($message['audio'] ?? null),
            'has_voice' => is_array($message['voice'] ?? null),
            'has_animation' => is_array($message['animation'] ?? null),
            'has_sticker' => is_array($message['sticker'] ?? null),
        ];
    }

    private function findMatchingCommand(Bot $bot, string $text, string $trigger): ?BotCommand
    {
        $exact = $bot->commands()
            ->where('status', 'active')
            ->where('command_name', $text)
            ->first();

        if ($exact || $trigger === '' || $trigger === $text) {
            return $exact ?: $this->findAliasMatch($bot, $text);
        }

        $triggerMatch = $bot->commands()
            ->where('status', 'active')
            ->where('command_name', $trigger)
            ->first();

        return $triggerMatch ?: $this->findAliasMatch($bot, $text, $trigger);
    }

    private function findAliasMatch(Bot $bot, string ...$triggers): ?BotCommand
    {
        $triggers = array_values(array_unique(array_filter($triggers, fn (string $trigger) => $trigger !== '')));

        if ($triggers === []) {
            return null;
        }

        return $bot->commands()
            ->where('status', 'active')
            ->get()
            ->first(function (BotCommand $command) use ($triggers): bool {
                $aliases = is_array($command->aliases) ? $command->aliases : [];

                foreach ($aliases as $alias) {
                    if (is_string($alias) && in_array($alias, $triggers, true)) {
                        return true;
                    }
                }

                return false;
            });
    }

    private function resolveCommandFlow(Bot $bot, string $telegramUserId): ?array
    {
        if (! Schema::hasTable('bot_user_runtime_data')) {
            return null;
        }

        $keys = [
            'awaiting_command_id',
            'awaiting_command_name',
            'awaiting_command_step',
            'awaiting_command_data',
            'awaiting_command_started_at',
        ];

        $rows = BotUserRuntimeData::query()
            ->where('bot_id', $bot->id)
            ->where('telegram_user_id', $telegramUserId)
            ->whereIn('key', $keys)
            ->get(['key', 'value'])
            ->mapWithKeys(fn (BotUserRuntimeData $row) => [$row->key => $row->value])
            ->all();

        $commandId = $rows['awaiting_command_id'] ?? null;
        $commandName = $rows['awaiting_command_name'] ?? null;

        if (($commandId === null || $commandId === '') && ($commandName === null || $commandName === '')) {
            return null;
        }

        if ($this->commandFlowExpired($rows['awaiting_command_started_at'] ?? null)) {
            $this->clearCommandFlow($bot, $telegramUserId);
            Log::info('[BotHost] command_flow_expired', [
                'bot_id' => $bot->id,
                'telegram_user_id' => $telegramUserId,
            ]);

            return null;
        }

        $command = BotCommand::query()
            ->where('bot_id', $bot->id)
            ->where('status', 'active')
            ->when($commandId !== null && $commandId !== '', fn ($query) => $query->where('id', $commandId))
            ->when($commandId === null || $commandId === '', fn ($query) => $query->where('command_name', (string) $commandName))
            ->first();

        if (! $command || $command->effectiveTriggerType() === 'direct_message') {
            $this->clearCommandFlow($bot, $telegramUserId);

            return null;
        }

        $data = is_array($rows['awaiting_command_data'] ?? null) ? $rows['awaiting_command_data'] : [];

        return [
            'command' => $command,
            'context' => [
                'active' => true,
                'step' => $rows['awaiting_command_step'] ?? null,
                'data' => $data,
                'command_name' => $command->command_name,
                'command_id' => $command->id,
            ],
        ];
    }

    private function commandFlowExpired(mixed $startedAt, int $minutes = 30): bool
    {
        if (! is_string($startedAt) || trim($startedAt) === '') {
            return false;
        }

        try {
            return \Illuminate\Support\Carbon::parse($startedAt)->lte(now()->subMinutes(abs($minutes)));
        } catch (Throwable) {
            return true;
        }
    }

    private function clearCommandFlow(Bot $bot, string $telegramUserId): void
    {
        BotUserRuntimeData::query()
            ->where('bot_id', $bot->id)
            ->where('telegram_user_id', $telegramUserId)
            ->whereIn('key', [
                'awaiting_command_id',
                'awaiting_command_name',
                'awaiting_command_step',
                'awaiting_command_data',
                'awaiting_command_started_at',
            ])
            ->delete();
    }

    private function cancelAllFlows(Bot $bot, string $telegramUserId): void
    {
        BotUserRuntimeData::query()
            ->where('bot_id', $bot->id)
            ->where('telegram_user_id', $telegramUserId)
            ->whereIn('key', [
                'awaiting_command_id',
                'awaiting_command_name',
                'awaiting_command_step',
                'awaiting_command_data',
                'awaiting_command_started_at',
                'workflow_state',
                'workflow_state_data',
                'admin_state',
                'awaiting_wallet',
                'awaiting_withdraw_amount',
            ])
            ->delete();
    }

    private function executeRunCommandAction(Bot $bot, BotCommand $sourceCommand, array $action, array $telegramContext, string $token, mixed $chatId, array $effectiveMessage, TelegramBotService $telegram, NodeRuntimeService $runtime, RuntimeSettingsService $settings, int $depth = 0): int
    {
        if ($depth >= 3) {
            $this->botLog($bot, 'runtime', 'runCommand depth limit reached', 'Nested runCommand calls were stopped to prevent a loop.', [
                'source_command_id' => $sourceCommand->id,
                'requested_command' => (string) ($action['command_name'] ?? ''),
            ]);

            return 0;
        }

        $commandName = trim((string) ($action['command_name'] ?? ''));

        if ($commandName === '') {
            return 0;
        }

        $target = BotCommand::query()
            ->where('bot_id', $bot->id)
            ->where('status', 'active')
            ->where('command_name', $commandName)
            ->first();

        if (! $target || $target->effectiveTriggerType() === 'direct_message') {
            $this->botLog($bot, 'runtime', 'runCommand target not found', 'A command tried to run another command that was not available.', [
                'source_command_id' => $sourceCommand->id,
                'requested_command' => $commandName,
            ]);

            return 0;
        }

        $nestedContext = array_merge($telegramContext, [
            'trigger' => $target->command_name,
            'args' => is_array($action['args'] ?? null) ? array_map('strval', $action['args']) : [],
        ]);

        Log::info('[BotHost] runCommand executing target', [
            'bot_id' => $bot->id,
            'source_command_id' => $sourceCommand->id,
            'target_command_id' => $target->id,
            'target_command' => $target->command_name,
        ]);

        if (! filled($target->code)) {
            if (! filled($target->response_text)) {
                return 0;
            }

            $sendResult = $telegram->sendMessage($token, $chatId, $target->response_text);
            if ($sendResult['ok'] ?? false) {
                $this->markCommandSuccess($target);
                return 1;
            }

            $this->botLog($bot, 'error', 'Telegram sendMessage failed', $sendResult['message'] ?? 'Telegram sendMessage failed.', [
                'command_id' => $target->id,
                'command_name' => $target->command_name,
            ]);

            return 0;
        }

        $result = $runtime->executeCommand($bot, $target, $nestedContext);

        if (! ($result['ok'] ?? false)) {
            $this->commandLog($bot, $target, $nestedContext, [
                'status' => ($result['error_type'] ?? null) === 'RuntimeUnavailable' ? 'runtime_unavailable' : 'failed',
                'execution_id' => $result['execution_id'] ?? null,
                'execution_time_ms' => $result['execution_time_ms'] ?? null,
                'error_type' => $result['error_type'] ?? 'RuntimeExecutionError',
                'error_message' => $result['error'] ?? 'Runtime execution failed.',
                'public_error_message' => 'Command error. Please contact the bot owner.',
                'internal_error_type' => $result['error_type'] ?? 'RuntimeExecutionError',
                'internal_error_message' => $result['error'] ?? 'Runtime execution failed.',
                'internal_error_stack' => $result['error_stack'] ?? null,
            ]);
            $this->logRuntimeFailureIfNeeded($settings, $bot, $target, $nestedContext, $result);
            $this->markCommandFailure($target);

            return 0;
        }

        $count = $this->dispatchNestedRuntimeReplies(
            $bot,
            $target,
            $result['replies'] ?? [],
            $nestedContext,
            $token,
            $chatId,
            $effectiveMessage,
            $telegram,
            $runtime,
            $settings,
            $depth + 1,
        );

        if ($count > 0) {
            $this->markCommandSuccess($target);
        }

        $this->commandLog($bot, $target, $nestedContext, [
            'status' => $count > 0 ? 'success' : 'no_reply',
            'reply_count' => $count,
            'execution_id' => $result['execution_id'] ?? null,
            'execution_time_ms' => $result['execution_time_ms'] ?? null,
        ]);

        return $count;
    }

    private function dispatchNestedRuntimeReplies(Bot $bot, BotCommand $command, array $replies, array $telegramContext, string $token, mixed $chatId, array $effectiveMessage, TelegramBotService $telegram, NodeRuntimeService $runtime, RuntimeSettingsService $settings, int $depth): int
    {
        $count = 0;

        foreach ($replies as $reply) {
            if (! is_array($reply)) {
                continue;
            }

            if (($reply['type'] ?? null) === 'run_command' && filled($reply['command_name'] ?? null)) {
                $count += $this->executeRunCommandAction($bot, $command, $reply, $telegramContext, $token, $chatId, $effectiveMessage, $telegram, $runtime, $settings, $depth);
                continue;
            }

            if (($reply['type'] ?? null) === 'text' && filled($reply['text'] ?? null)) {
                $result = $telegram->sendMessage(
                    $token,
                    $reply['chat_id'] ?? $chatId,
                    (string) $reply['text'],
                    $reply['parse_mode'] ?? null,
                    is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                    (bool) ($reply['disable_web_page_preview'] ?? false),
                );
                $count += ($result['ok'] ?? false) ? 1 : 0;
                continue;
            }

            if (($reply['type'] ?? null) === 'photo' && filled($reply['photo_url'] ?? null)) {
                $result = $telegram->sendPhoto(
                    $token,
                    $reply['chat_id'] ?? $chatId,
                    (string) $reply['photo_url'],
                    isset($reply['caption']) ? (string) $reply['caption'] : null,
                    $reply['parse_mode'] ?? null,
                    is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                );
                $count += ($result['ok'] ?? false) ? 1 : 0;
                continue;
            }

            if (($reply['type'] ?? null) === 'edit_message_text' && filled($reply['text'] ?? null)) {
                $result = $telegram->editMessageText(
                    $token,
                    $reply['chat_id'] ?? $chatId,
                    $reply['message_id'] ?? data_get($effectiveMessage, 'message_id'),
                    (string) $reply['text'],
                    $reply['parse_mode'] ?? null,
                    is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                    (bool) ($reply['disable_web_page_preview'] ?? false),
                );
                $count += ($result['ok'] ?? false) ? 1 : 0;
                continue;
            }

            if (($reply['type'] ?? null) === 'edit_message_caption') {
                $result = $telegram->editMessageCaption(
                    $token,
                    $reply['chat_id'] ?? $chatId,
                    $reply['message_id'] ?? data_get($effectiveMessage, 'message_id'),
                    array_key_exists('caption', $reply) ? (string) $reply['caption'] : null,
                    $reply['parse_mode'] ?? null,
                    is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                );
                $count += ($result['ok'] ?? false) ? 1 : 0;
                continue;
            }

            if (($reply['type'] ?? null) === 'edit_message_reply_markup') {
                $result = $telegram->editMessageReplyMarkup(
                    $token,
                    $reply['chat_id'] ?? $chatId,
                    $reply['message_id'] ?? data_get($effectiveMessage, 'message_id'),
                    is_array($reply['reply_markup'] ?? null) ? $reply['reply_markup'] : null,
                );
                $count += ($result['ok'] ?? false) ? 1 : 0;
                continue;
            }

            if (($reply['type'] ?? null) === 'delete_message' && isset($reply['message_id'])) {
                $result = $telegram->deleteMessage($token, $reply['chat_id'] ?? $chatId, $reply['message_id']);
                $count += ($result['ok'] ?? false) ? 1 : 0;
                continue;
            }

            if (($reply['type'] ?? null) === 'answer_callback_query' && filled($reply['callback_query_id'] ?? null)) {
                $result = $telegram->answerCallbackQuery($token, (string) $reply['callback_query_id'], (string) ($reply['text'] ?? ''));
                $count += ($result['ok'] ?? false) ? 1 : 0;
            }
        }

        return $count;
    }

    private function commandLog(Bot $bot, ?BotCommand $command, array $telegramContext, array $data): void
    {
        $logData = [
            'bot_id' => $bot->id,
            'bot_command_id' => $command?->id,
            'command_name' => $command?->command_name,
            'bot_user_id' => $telegramContext['bot_user_id'] ?? null,
            'telegram_user_id' => isset($telegramContext['user_id']) ? (string) $telegramContext['user_id'] : null,
            'telegram_username' => $telegramContext['username'] ?? null,
            'telegram_first_name' => $telegramContext['first_name'] ?? null,
            'chat_id' => isset($telegramContext['chat_id']) ? (string) $telegramContext['chat_id'] : null,
            'message_text' => $telegramContext['message_text'] ?? null,
            'status' => $data['status'],
            'reply_count' => $data['reply_count'] ?? 0,
            'execution_id' => $data['execution_id'] ?? null,
            'execution_time_ms' => $data['execution_time_ms'] ?? null,
            'public_error_message' => isset($data['public_error_message']) ? $this->sanitizeRuntimeLogValue((string) $data['public_error_message']) : null,
            'internal_error_type' => isset($data['internal_error_type']) ? $this->sanitizeRuntimeLogValue((string) $data['internal_error_type'], 255) : null,
            'internal_error_message' => isset($data['internal_error_message']) ? $this->sanitizeRuntimeLogValue((string) $data['internal_error_message']) : null,
            'internal_error_stack' => isset($data['internal_error_stack']) ? $this->sanitizeRuntimeLogValue((string) $data['internal_error_stack'], 4000) : null,
            'error_type' => isset($data['error_type']) ? $this->sanitizeRuntimeLogValue((string) $data['error_type'], 255) : null,
            'error_message' => isset($data['error_message']) ? $this->sanitizeRuntimeLogValue((string) $data['error_message']) : null,
        ];

        if (! $this->hasTable('bot_command_logs')) {
            Log::error('Failed to write command log', [
                'error' => 'bot_command_logs table is missing.',
                'data_keys' => array_keys($logData),
            ]);

            return;
        }

        foreach (array_keys($logData) as $column) {
            if (! $this->hasColumn('bot_command_logs', $column)) {
                unset($logData[$column]);
            }
        }

        $this->safeCommandLog($logData);
    }

    private function botLog(Bot $bot, string $type, ?string $title, string $message, array $context = []): void
    {
        $logData = [
            'bot_id' => $bot->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'context' => $context ?: null,
        ];

        if (! $this->hasTable('bot_logs')) {
            Log::error('Failed to write bot log', [
                'error' => 'bot_logs table is missing.',
                'data_keys' => array_keys($logData),
            ]);

            return;
        }

        foreach (array_keys($logData) as $column) {
            if (! $this->hasColumn('bot_logs', $column)) {
                unset($logData[$column]);
            }
        }

        $this->safeBotLog($logData);
    }

    private function logRuntimeFailureIfNeeded(RuntimeSettingsService $settings, Bot $bot, BotCommand $command, array $telegramContext, array $result): void
    {
        $errorType = $result['error_type'] ?? 'RuntimeExecutionError';
        $isBackend = in_array($errorType, [
            'RuntimeUnavailable',
            'RuntimeTimeout',
            'RuntimeConfigurationError',
            'RuntimeRequestFailed',
            'InvalidRuntimeResponse',
        ], true);

        if ($isBackend && ! $settings->boolean('log_backend_runtime_errors', true)) {
            return;
        }

        if (! $isBackend && ! $settings->boolean('log_user_code_errors', false) && ! $settings->boolean('show_user_code_errors_to_owners', false)) {
            return;
        }

        $this->botLog(
            $bot,
            $isBackend ? 'error' : 'runtime',
            $isBackend ? 'Runtime command failed' : 'Command user-code error',
            $this->sanitizeRuntimeLogValue((string) ($result['error'] ?? 'Runtime execution failed.')),
            [
                'category' => $isBackend && $settings->string('runtime_mode', 'local') === 'docker' ? 'docker_runtime' : ($isBackend ? 'backend_runtime' : 'user_code'),
                'command_id' => $command->id,
                'command_name' => $command->command_name,
                'telegram_user_id' => isset($telegramContext['user_id']) ? (string) $telegramContext['user_id'] : null,
                'message_text' => $telegramContext['message_text'] ?? null,
                'callback_data' => $telegramContext['callback_data'] ?? null,
                'execution_id' => $result['execution_id'] ?? null,
                'execution_time_ms' => $result['execution_time_ms'] ?? null,
                'public_error_message' => 'Command error. Please contact the bot owner.',
                'error_type' => $this->sanitizeRuntimeLogValue((string) $errorType, 255),
                'error_message' => $this->sanitizeRuntimeLogValue((string) ($result['error'] ?? 'Runtime execution failed.')),
                'error_stack' => isset($result['error_stack']) ? $this->sanitizeRuntimeLogValue((string) $result['error_stack'], 4000) : null,
            ],
        );
    }

    private function sanitizeRuntimeLogValue(string $value, int $limit = 2000): string
    {
        return str($value)
            ->replaceMatches('/\d{6,}:[A-Za-z0-9_-]{20,}/', '[redacted-token]')
            ->replaceMatches('/(password|secret|token|api[_-]?key)(["\'\s:=]+)([^"\'\s,}]+)/i', '$1$2[redacted]')
            ->replaceMatches('/(Bearer\s+)[A-Za-z0-9._~+\/=-]{16,}/i', '$1[redacted]')
            ->replaceMatches('/([?&](?:token|secret|api[_-]?key)=)[^&\s]+/i', '$1[redacted]')
            ->limit($limit, '')
            ->toString();
    }

    private function logSlowCommandIfNeeded(RuntimeSettingsService $settings, Bot $bot, BotCommand $command, array $telegramContext, array $timings): void
    {
        $totalMs = (int) ($timings['total_command_ms'] ?? 0);

        if (! $settings->boolean('log_slow_commands', false) || $totalMs < $settings->integer('slow_command_threshold_ms', 1000)) {
            return;
        }

        $this->botLog($bot, 'runtime', 'Slow command warning', "Command exceeded {$totalMs}ms.", [
            'category' => 'performance',
            'command_id' => $command->id,
            'command_name' => $command->command_name,
            'telegram_user_id' => isset($telegramContext['user_id']) ? (string) $telegramContext['user_id'] : null,
            'message_text' => $telegramContext['message_text'] ?? null,
            'timings' => $timings,
        ]);
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function markCommandSuccess(BotCommand $command): void
    {
        try {
            $command->forceFill([
                'last_used_at' => now(),
                'last_error_at' => null,
            ])->save();
            $command->increment('execution_count');
        } catch (Throwable $exception) {
            Log::error('Failed to mark command success', [
                'command_id' => $command->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function markCommandFailure(BotCommand $command): void
    {
        try {
            $command->forceFill(['last_error_at' => now()])->save();
            $command->increment('error_count');
        } catch (Throwable $exception) {
            Log::error('Failed to mark command failure', [
                'command_id' => $command->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function safeCommandLog(array $data): void
    {
        try {
            BotCommandLog::create($data);
        } catch (Throwable $exception) {
            Log::error('Failed to write command log', [
                'error' => $exception->getMessage(),
                'data_keys' => array_keys($data),
            ]);
        }
    }

    private function safeBotLog(array $data): void
    {
        try {
            BotLog::create($data);
        } catch (Throwable $exception) {
            Log::error('Failed to write bot log', [
                'error' => $exception->getMessage(),
                'data_keys' => array_keys($data),
            ]);
        }
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable $exception) {
            Log::error('Failed to inspect database table', [
                'table' => $table,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (Throwable $exception) {
            Log::error('Failed to inspect database column', [
                'table' => $table,
                'column' => $column,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
