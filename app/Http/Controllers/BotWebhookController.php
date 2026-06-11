<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bot;
use App\Services\AuditLogService;
use App\Services\BotAccessService;
use App\Services\TelegramBotService;
use App\Services\TelegramWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use App\Support\PublicCallbackUrl;

class BotWebhookController extends Controller
{
    public function __construct(
        private readonly BotAccessService $access,
        private readonly AuditLogService $audit,
    ) {}

    public function set(Request $request, Bot $bot, TelegramWebhookService $webhooks): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        abort_unless($request->user()?->isAdmin(), 403);

        if (! $bot->token_verified_at) {
            return back()->withErrors(['webhook' => 'Verify the Telegram bot token before setting a webhook.']);
        }

        if (! $this->hasPublicHttpsUrl()) {
            $message = 'Telegram requires a public HTTPS webhook URL. Set the public callback URL to your Cloudflare Tunnel, LocalTunnel, or ngrok HTTPS URL.';

            $bot->update([
                'webhook_status' => 'failed',
                'webhook_last_error' => $message,
            ]);

            return back()->withErrors(['webhook' => $message]);
        }

        $result = $webhooks->resetWebhook($bot, startBot: true);

        if (! $result['ok']) {
            return back()->withErrors(['webhook' => $result['message'] ?? 'Unable to set Telegram webhook.']);
        }

        $this->log($request, 'webhook_set', 'Set webhook for bot: '.$bot->name);

        return back()->with('status', 'Webhook set successfully.');
    }

    public function delete(Request $request, Bot $bot, TelegramBotService $telegram): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        abort_unless($request->user()?->isAdmin(), 403);

        $result = $telegram->deleteWebhook($this->decryptToken($bot));

        if (! $result['ok']) {
            $bot->update([
                'webhook_status' => 'failed',
                'webhook_last_error' => $result['message'] ?? 'Unable to remove Telegram webhook.',
            ]);

            return back()->withErrors(['webhook' => $result['message'] ?? 'Unable to remove Telegram webhook.']);
        }

        $bot->update([
            'webhook_status' => 'not_set',
            'webhook_last_error' => null,
        ]);

        $this->log($request, 'webhook_deleted', 'Removed webhook for bot: '.$bot->name);

        return back()->with('status', 'Webhook removed successfully.');
    }

    private function hasPublicHttpsUrl(): bool
    {
        return PublicCallbackUrl::isPublicHttps();
    }

    private function decryptToken(Bot $bot): string
    {
        return Crypt::decryptString($bot->getRawOriginal('token_encrypted'));
    }

    private function log(Request $request, string $action, string $description): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        $this->audit->log('webhook', str_replace('_', '.', $action), $description, [], $request->user());
    }
}
