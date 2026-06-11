<?php

namespace App\Services;

use App\Models\Bot;
use App\Support\PublicCallbackUrl;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TelegramWebhookService
{
    public function __construct(private readonly TelegramBotService $telegram) {}

    public function buildWebhookUrl(Bot $bot): string
    {
        if (! $bot->webhook_secret) {
            $bot->forceFill(['webhook_secret' => Str::random(48)])->save();
        }

        return PublicCallbackUrl::to('/telegram/webhook/'.$bot->id.'/'.$bot->webhook_secret);
    }

    public function resetWebhook(Bot $bot, bool $startBot = true): array
    {
        if (! PublicCallbackUrl::isPublicHttps()) {
            $message = 'The public callback URL must be a public HTTPS URL to set Telegram webhooks.';

            $bot->forceFill([
                'webhook_status' => 'failed',
                'webhook_last_error' => $message,
                'last_webhook_update_at' => now(),
            ])->save();

            return ['ok' => false, 'message' => $message, 'url' => null, 'started' => false, 'info' => null];
        }

        if (! $bot->token_verified_at || ! filled($bot->getRawOriginal('token_encrypted'))) {
            $message = 'Verify the Telegram bot token before setting a webhook.';

            $bot->forceFill([
                'webhook_status' => 'failed',
                'webhook_last_error' => $message,
                'last_webhook_update_at' => now(),
            ])->save();

            return ['ok' => false, 'message' => $message, 'url' => null, 'started' => false, 'info' => null];
        }

        try {
            $token = $this->decryptToken($bot);
            $url = $this->buildWebhookUrl($bot);
            $result = $this->telegram->setWebhook($token, $url);

            if (! ($result['ok'] ?? false)) {
                $message = $result['message'] ?? 'Unable to set Telegram webhook.';

                $bot->forceFill([
                    'webhook_url' => $url,
                    'webhook_status' => 'failed',
                    'webhook_last_error' => $message,
                    'last_webhook_update_at' => now(),
                ])->save();

                return ['ok' => false, 'message' => $message, 'url' => $url, 'started' => false, 'info' => null];
            }

            $info = $this->telegram->getWebhookInfo($token);
            $currentUrl = (string) data_get($info, 'data.url', '');
            $started = $startBot && $bot->status !== 'running';

            $bot->forceFill([
                'status' => $startBot ? 'running' : $bot->status,
                'webhook_url' => $url,
                'webhook_set_at' => now(),
                'webhook_status' => 'active',
                'webhook_last_error' => null,
                'last_webhook_update_at' => now(),
            ])->save();

            return [
                'ok' => true,
                'message' => null,
                'url' => $url,
                'started' => $started,
                'info' => $info,
                'verified' => ! ($info['ok'] ?? false) || $currentUrl === '' || hash_equals($url, $currentUrl),
            ];
        } catch (Throwable $exception) {
            Log::warning('Telegram webhook reset failed for bot.', [
                'bot_id' => $bot->id,
                'error' => $exception->getMessage(),
            ]);

            $bot->forceFill([
                'webhook_status' => 'failed',
                'webhook_last_error' => $exception->getMessage(),
                'last_webhook_update_at' => now(),
            ])->save();

            return ['ok' => false, 'message' => $exception->getMessage(), 'url' => null, 'started' => false, 'info' => null];
        }
    }

    public function resetAllWebhooks(bool $startBots = true, ?int $limit = null, ?callable $filter = null): array
    {
        $summary = [
            'checked' => 0,
            'success' => 0,
            'failed' => 0,
            'started' => 0,
            'public_url' => PublicCallbackUrl::base(),
        ];

        $query = Bot::query()
            ->whereNotNull('token_verified_at')
            ->whereNotNull('token_encrypted')
            ->where('status', '!=', 'suspended')
            ->orderBy('id');

        if ($filter) {
            $filter($query);
        }

        if ($limit !== null) {
            $query->limit(max(1, $limit))->get()->each(function (Bot $bot) use (&$summary, $startBots): void {
                $this->countReset($bot, $summary, $startBots);
            });

            return $summary;
        }

        $query->chunkById(100, function ($bots) use (&$summary, $startBots): void {
            foreach ($bots as $bot) {
                $this->countReset($bot, $summary, $startBots);
            }
        });

        return $summary;
    }

    public function getWebhookInfo(Bot $bot): array
    {
        try {
            $info = $this->telegram->getWebhookInfo($this->decryptToken($bot));
            $expectedUrl = $this->buildWebhookUrl($bot);
            $currentUrl = (string) data_get($info, 'data.url', '');

            return [
                'ok' => (bool) ($info['ok'] ?? false),
                'expected_url' => $expectedUrl,
                'current_url' => $currentUrl,
                'uses_current_public_url' => $currentUrl !== '' && hash_equals($expectedUrl, $currentUrl),
                'pending_update_count' => data_get($info, 'data.pending_update_count'),
                'last_error_message' => data_get($info, 'data.last_error_message'),
                'data' => $info['data'] ?? null,
                'message' => $info['message'] ?? null,
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'expected_url' => null,
                'current_url' => null,
                'uses_current_public_url' => false,
                'pending_update_count' => null,
                'last_error_message' => null,
                'data' => null,
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function countReset(Bot $bot, array &$summary, bool $startBots): void
    {
        $summary['checked']++;
        $result = $this->resetWebhook($bot, $startBots);

        if ($result['ok'] ?? false) {
            $summary['success']++;
            $summary['started'] += ($result['started'] ?? false) ? 1 : 0;

            return;
        }

        $summary['failed']++;
    }

    private function decryptToken(Bot $bot): string
    {
        return Crypt::decryptString($bot->getRawOriginal('token_encrypted'));
    }
}
