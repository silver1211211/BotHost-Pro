<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotSetting;
use App\Services\AuditLogService;
use App\Services\BotAccessService;
use App\Services\CustomWebhookService;
use App\Services\PlanAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BotUserWebhookController extends Controller
{
    public function __construct(
        private readonly BotAccessService $access,
        private readonly PlanAccessService $planAccess,
        private readonly AuditLogService $audit,
    ) {}

    public function regenerate(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        if (! $this->planAccess->userHasFeature($request->user(), 'custom_webhooks')) {
            return back()->withErrors(['user_webhook' => 'Custom webhooks are not available on your current plan.']);
        }

        if (! $bot->setting) {
            $bot->setting()->create();
            $bot->load('setting');
        }

        $bot->setting->update([
            'user_webhook_enabled' => true,
            'user_webhook_secret'  => $this->uniqueSecret(),
        ]);

        $this->audit->log('webhook', 'custom_webhook.regenerated', 'Custom webhook endpoint regenerated.', [
            'bot_id' => $bot->id,
        ], $request->user(), 'success', $bot);

        return back()->with('status', 'Webhook endpoint regenerated.');
    }

    public function disable(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        if ($bot->setting) {
            $bot->setting->update([
                'user_webhook_enabled' => false,
                'user_webhook_secret'  => null,
            ]);
        }

        $this->audit->log('webhook', 'custom_webhook.disabled', 'Custom webhook endpoint disabled.', [
            'bot_id' => $bot->id,
        ], $request->user(), 'success', $bot);

        return back()->with('status', 'Webhook endpoint disabled.');
    }

    public function saveSettings(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        if (! $this->planAccess->userHasFeature($request->user(), 'custom_webhooks')) {
            return back()->withErrors(['custom_webhook_url' => 'Custom webhooks are not available on your current plan.']);
        }

        $data = $request->validate([
            'custom_webhook_url'      => ['nullable', 'url', 'max:500'],
            'custom_webhook_events'   => ['nullable', 'array'],
            'custom_webhook_events.*' => ['string', 'max:100'],
        ]);

        if (! $bot->setting) {
            $bot->setting()->create();
            $bot->load('setting');
        }

        $bot->setting->update([
            'custom_webhook_url'    => $data['custom_webhook_url'] ?? null,
            'custom_webhook_events' => $data['custom_webhook_events'] ?? [],
        ]);

        $this->audit->log('webhook', 'custom_webhook.settings_updated', 'Custom webhook settings updated.', [
            'bot_id' => $bot->id,
            'events' => $data['custom_webhook_events'] ?? [],
            'custom_webhook_url_set' => filled($data['custom_webhook_url'] ?? null),
        ], $request->user(), 'success', $bot);

        return back()->with('status', 'Webhook settings saved.');
    }

    public function test(Request $request, Bot $bot, CustomWebhookService $webhooks): JsonResponse
    {
        $this->access->authorize($request, $bot);

        if (! $this->planAccess->userHasFeature($request->user(), 'custom_webhooks')) {
            return response()->json(['ok' => false, 'error' => 'Custom webhooks are not available on your current plan.'], 403);
        }

        if (! $bot->setting?->user_webhook_enabled || ! $bot->setting?->user_webhook_secret) {
            return response()->json(['ok' => false, 'error' => 'No endpoint generated. Click Generate first.'], 422);
        }

        $payload = [
            'event'     => 'test.webhook',
            'message'   => 'This is a test webhook from BotHost Pro.',
            'bot_id'    => $bot->id,
            'timestamp' => now()->toIso8601String(),
        ];

        $bot->loadMissing(['user', 'setting']);
        $log = $webhooks->receive($bot, $payload, $request, 'test.webhook');

        return response()->json([
            'ok'          => true,
            'message'     => 'Webhook received.',
            'http_status' => $log->http_status,
            'duration_ms' => $log->duration_ms,
        ]);
    }

    private function uniqueSecret(): string
    {
        do {
            $secret = Str::random(64);
        } while (BotSetting::where('user_webhook_secret', $secret)->exists());

        return $secret;
    }
}
