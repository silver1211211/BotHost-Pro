<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotUser;
use Illuminate\Support\Facades\Log;
use Throwable;

class BotUserTracker
{
    public function __construct(private readonly PlanAccessService $planAccess) {}

    public function trackFromTelegramUpdate(Bot $bot, array $update): ?BotUser
    {
        try {
            $from = data_get($update, 'message.from') ?: data_get($update, 'callback_query.from');

            if (! is_array($from) || ! isset($from['id'])) {
                return null;
            }

            $telegramUserId = (string) $from['id'];
            $now = now();

            $botUser = BotUser::query()->firstOrNew([
                'bot_id' => $bot->id,
                'telegram_user_id' => $telegramUserId,
            ]);

            if (! $botUser->exists) {
                // Check account-wide tracking limit before creating a new record
                $botOwner = $bot->user;

                if ($botOwner && ! $this->planAccess->canTrackBotUser($botOwner)) {
                    Log::info('Bot user tracking limit reached — new user not tracked', [
                        'bot_id' => $bot->id,
                        'telegram_user_id' => $telegramUserId,
                    ]);

                    return null;
                }

                $botUser->first_seen_at = $now;
                $botUser->status = 'active';
            }

            $botUser->forceFill([
                'telegram_username' => data_get($from, 'username'),
                'telegram_first_name' => data_get($from, 'first_name'),
                'telegram_last_name' => data_get($from, 'last_name'),
                'telegram_language_code' => data_get($from, 'language_code'),
                'is_bot' => (bool) data_get($from, 'is_bot', false),
                'last_active_at' => $now,
                'metadata' => [
                    'chat_id' => data_get($update, 'message.chat.id') !== null || data_get($update, 'callback_query.message.chat.id') !== null
                        ? (string) (data_get($update, 'message.chat.id') ?? data_get($update, 'callback_query.message.chat.id'))
                        : null,
                    'chat_type' => data_get($update, 'message.chat.type') ?? data_get($update, 'callback_query.message.chat.type'),
                ],
            ]);

            if (! $botUser->first_seen_at) {
                $botUser->first_seen_at = $now;
            }

            $botUser->save();
            $botUser->increment('message_count');

            return $botUser->refresh();
        } catch (Throwable $exception) {
            Log::error('Failed to track bot user', [
                'bot_id' => $bot->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function incrementCommandCount(?BotUser $botUser): void
    {
        if (! $botUser) {
            return;
        }

        try {
            $botUser->increment('command_count');
        } catch (Throwable $exception) {
            Log::error('Failed to increment bot user command count', [
                'bot_user_id' => $botUser->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
