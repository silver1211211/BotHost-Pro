<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\TelegramWebhookService;
use App\Support\PublicCallbackUrl;
use Illuminate\Console\Command;

class ReconnectTelegramWebhooks extends Command
{
    protected $signature = 'webhooks:reconnect-telegram {--limit=100 : Maximum bots to check per run}';

    protected $description = 'Verify and reconnect Telegram webhooks for running verified bots.';

    public function handle(TelegramWebhookService $webhooks): int
    {
        if (! $this->hasPublicHttpsUrl()) {
            $this->warn('APP_PUBLIC_URL must be a public HTTPS URL to reconnect Telegram webhooks.');

            return self::FAILURE;
        }

        $checked = 0;
        $summary = $webhooks->resetAllWebhooks(
            startBots: false,
            limit: (int) $this->option('limit'),
            filter: fn ($query) => $query
                ->where('status', 'running')
                ->orderByRaw("case when webhook_status in ('failed', 'not_set') then 0 else 1 end")
                ->orderBy('last_webhook_update_at'),
        );

        $this->info("Checked {$summary['checked']} bot(s). Reconnected {$summary['success']}, failed {$summary['failed']}.");

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function hasPublicHttpsUrl(): bool
    {
        return PublicCallbackUrl::isPublicHttps();
    }
}
