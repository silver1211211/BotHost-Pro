<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerWebhookService
{
    public function __construct(private readonly PlatformSettingsService $settings) {}

    /**
     * Dispatch a trigger webhook event for a user.
     * Silently returns if not configured or if the global switch is off.
     */
    public function dispatch(User $user, string $event, array $payload = []): void
    {
        // Global master switch
        if (! $this->settings->boolean('trigger_webhooks_enabled', true)) {
            return;
        }

        // Per-event toggle (e.g. trigger_webhook_payment_success)
        $eventKey = 'trigger_webhook_' . str_replace('.', '_', $event);
        if (PlatformSettings::class && ! $this->settings->boolean($eventKey, true)) {
            return;
        }

        // User custom webhook URL — not yet implemented; prepare foundation only
        $webhookUrl = null; // Will be populated once user-level webhook URL storage is added

        if (! filled($webhookUrl)) {
            return;
        }

        try {
            Http::timeout(5)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($webhookUrl, array_merge([
                    'event'     => $event,
                    'timestamp' => now()->toIso8601String(),
                ], $payload));
        } catch (Throwable $e) {
            Log::warning("TriggerWebhook dispatch failed for user #{$user->id}", [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
