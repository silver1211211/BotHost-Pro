<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotUser;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class TelegramBroadcastService
{
    public function __construct(private readonly TelegramBotService $telegram) {}

    public function botCanBroadcast(?Bot $bot): bool
    {
        if (! $bot) {
            return false;
        }

        if (! $bot->trashed() && ! in_array($bot->status, ['active', 'running', 'paused'], true)) {
            return false;
        }

        if (! $bot->token_verified_at || ! filled($bot->getRawOriginal('token_encrypted'))) {
            return false;
        }

        try {
            return filled(Crypt::decryptString($bot->getRawOriginal('token_encrypted')));
        } catch (Throwable) {
            return false;
        }
    }

    public function sendMessage(Bot $bot, BotUser $recipient, string $message, ?string $parseMode = null): array
    {
        try {
            $token = Crypt::decryptString($bot->getRawOriginal('token_encrypted'));
        } catch (Throwable) {
            return ['ok' => false, 'message' => 'Unable to read verified bot token.'];
        }

        $chatId = $recipient->telegram_user_id;
        if (! filled($chatId)) {
            return ['ok' => false, 'message' => 'Recipient has no Telegram chat id.'];
        }

        $result = $this->telegram->sendMessage($token, $chatId, $message, $parseMode);

        if (! $result['ok']) {
            $this->markBlockedIfNeeded($recipient, (string) ($result['message'] ?? ''));
        }

        return $result;
    }

    private function markBlockedIfNeeded(BotUser $recipient, string $message): void
    {
        $normalized = strtolower($message);

        if (! str_contains($normalized, 'blocked')
            && ! str_contains($normalized, 'chat not found')
            && ! str_contains($normalized, 'user is deactivated')) {
            return;
        }

        $recipient->forceFill([
            'status' => 'blocked',
            'blocked_at' => now(),
        ])->save();
    }
}
