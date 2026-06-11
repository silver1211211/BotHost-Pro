<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotLog;
use App\Models\WebhookDeliveryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class CustomWebhookService
{
    public function __construct(
        private readonly PlanAccessService $planAccess,
        private readonly AuditLogService $audit,
    ) {}

    public function receive(Bot $bot, array $payload, Request $request, string $event = 'external.callback'): WebhookDeliveryLog
    {
        $event = $this->safeEvent($payload['event'] ?? $event);

        $log = WebhookDeliveryLog::create([
            'user_id' => $bot->user_id,
            'bot_id' => $bot->id,
            'event' => $event,
            'direction' => 'incoming',
            'url' => $request->fullUrl(),
            'status' => 'received',
            'http_status' => 200,
            'payload' => $this->payloadPreview($payload),
            'headers' => $this->safeHeaders($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'attempts' => 1,
            'duration_ms' => 0,
        ]);

        if (Schema::hasTable('bot_logs')) {
            BotLog::create([
                'bot_id' => $bot->id,
                'type' => 'info',
                'title' => $event === 'test.webhook' ? 'Test Webhook' : 'Incoming Webhook',
                'message' => 'Received custom webhook callback.',
                'context' => [
                    'event' => $event,
                    'ip' => $request->ip(),
                    'payload' => $this->payloadPreview($payload),
                ],
                'created_at' => now(),
            ]);
        }

        $this->audit->log(
            'webhook',
            $event === 'test.webhook' ? 'custom_webhook.tested' : 'custom_webhook.received',
            $event === 'test.webhook' ? 'Custom webhook test received.' : 'Incoming custom webhook received.',
            ['bot_id' => $bot->id, 'event' => $event],
            $bot->user,
            'success',
            $bot,
        );

        return $log;
    }

    public function canReceive(Bot $bot): bool
    {
        return (bool) $bot->setting?->user_webhook_enabled
            && filled($bot->setting?->user_webhook_secret)
            && $this->planAccess->userHasFeature($bot->user, 'custom_webhooks');
    }

    public function payloadPreview(array $payload): array
    {
        $json = json_encode($this->maskPayload($payload), JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return ['_error' => 'Unable to encode payload preview.'];
        }

        if (strlen($json) > 12000) {
            return [
                '_truncated' => true,
                'preview' => Str::limit($json, 12000, '...'),
            ];
        }

        return json_decode($json, true) ?: [];
    }

    private function safeHeaders(Request $request): array
    {
        $headers = [];

        foreach ($request->headers->all() as $key => $values) {
            $headers[$key] = str_contains(strtolower($key), 'authorization')
                || str_contains(strtolower($key), 'token')
                || str_contains(strtolower($key), 'secret')
                    ? ['[masked]']
                    : array_map(fn ($value) => Str::limit((string) $value, 500), $values);
        }

        return $headers;
    }

    private function maskPayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            $normalized = strtolower((string) $key);

            if (str_contains($normalized, 'token')
                || str_contains($normalized, 'secret')
                || str_contains($normalized, 'password')
                || str_contains($normalized, 'api_key')
                || str_contains($normalized, 'authorization')) {
                $payload[$key] = '[masked]';
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->maskPayload($value);
            } elseif (is_string($value)) {
                $payload[$key] = Str::limit($value, 2000);
            }
        }

        return $payload;
    }

    private function safeEvent(mixed $event): string
    {
        $event = is_string($event) ? trim($event) : '';

        return $event !== '' && strlen($event) <= 100
            ? preg_replace('/[^A-Za-z0-9_.:-]/', '', $event) ?: 'external.callback'
            : 'external.callback';
    }
}
