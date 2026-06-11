<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class BotRuntimeCacheService
{
    public function __construct(private readonly RuntimeSettingsService $settings) {}

    public function findWebhookBot(int|string $botId, string $secret): ?Bot
    {
        $key = "bot:webhook:{$botId}";

        try {
            $attributes = Cache::remember($key, 300, fn () => $this->loadWebhookBotAttributes($botId));
        } catch (Throwable $exception) {
            if ($this->settings->boolean('log_redis_errors', true)) {
                Log::warning('Webhook bot cache unavailable; falling back to database.', [
                    'bot_id' => $botId,
                    'error' => $exception->getMessage(),
                ]);
            }

            $attributes = $this->loadWebhookBotAttributes($botId);
        }

        if (! is_array($attributes)) {
            return null;
        }

        $bot = new Bot();
        $bot->setRawAttributes($attributes, true);
        $bot->exists = true;

        return $bot;
    }

    public function clearBot(Bot|int|string $bot): void
    {
        $botId = $bot instanceof Bot ? $bot->id : $bot;

        foreach (["bot:{$botId}:runtime_config", "bot:webhook:{$botId}"] as $key) {
            try {
                Cache::forget($key);
            } catch (Throwable $exception) {
                if ($this->settings->boolean('log_redis_errors', true)) {
                    Log::warning('Failed to clear bot cache.', [
                        'bot_id' => $botId,
                        'cache_key' => $key,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }
    }

    private function loadWebhookBotAttributes(int|string $botId): ?array
    {
        $bot = Bot::query()
            ->where(function ($query) use ($botId): void {
                $query->whereKey($botId)
                    ->orWhere('slug', (string) $botId);
            })
            ->first();

        return $bot?->getAttributes();
    }
}
