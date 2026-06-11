<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotSetting;
use App\Services\AuditLogService;
use App\Services\CustomWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserBotWebhookController extends Controller
{
    public function handle(Request $request, int $botId, string $secret, CustomWebhookService $webhooks, AuditLogService $audit): JsonResponse
    {
        $setting = BotSetting::query()
            ->where('bot_id', $botId)
            ->where('user_webhook_secret', $secret)
            ->where('user_webhook_enabled', true)
            ->first();

        if (! $setting) {
            $audit->log('webhook', 'custom_webhook.failed', 'Invalid or inactive custom webhook endpoint.', [
                'bot_id' => $botId,
                'reason' => 'invalid_or_inactive',
            ], null, 'failed');

            return response()->json(['error' => 'Invalid or inactive webhook endpoint.'], 404);
        }

        $bot = Bot::find($botId);

        if (! $bot) {
            $audit->log('webhook', 'custom_webhook.failed', 'Custom webhook bot not found.', [
                'bot_id' => $botId,
                'reason' => 'bot_not_found',
            ], null, 'failed');

            return response()->json(['error' => 'Bot not found.'], 404);
        }

        $bot->loadMissing(['user', 'setting']);

        if (! $webhooks->canReceive($bot)) {
            $audit->log('webhook', 'custom_webhook.failed', 'Custom webhook endpoint unavailable.', [
                'bot_id' => $bot->id,
                'reason' => 'feature_or_endpoint_disabled',
            ], $bot->user, 'failed', $bot);

            return response()->json(['error' => 'Webhook endpoint is not available.'], 403);
        }

        $webhooks->receive($bot, $request->all(), $request);

        return response()->json(['ok' => true, 'message' => 'Webhook received.']);
    }
}
