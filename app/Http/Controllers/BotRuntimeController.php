<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Services\AuditLogService;
use App\Services\BotAccessService;
use App\Services\DockerRuntimeService;
use App\Services\RuntimeSettingsService;
use App\Services\TelegramBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;
use App\Support\PublicCallbackUrl;

class BotRuntimeController extends Controller
{
    public function __construct(
        private readonly BotAccessService $access,
        private readonly AuditLogService $audit,
    ) {}

    public function verifyToken(Request $request, Bot $bot, TelegramBotService $telegram): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        $token = $this->token($bot);

        if (! $token) {
            return back()->withErrors(['token' => 'Add and verify a bot token before activating this bot.']);
        }

        $result = $telegram->validateToken($token);

        if (! $result['valid']) {
            $this->audit->log('bot', 'bot_token.verification_failed', 'Bot token verification failed.', [
                'bot_id' => $bot->id,
                'error' => $result['message'] ?? null,
            ], $request->user(), 'failed', $bot);

            return back()->withErrors(['token' => $result['message']]);
        }

        $data = $result['data'];

        $bot->update([
            'telegram_bot_id' => isset($data['id']) ? (string) $data['id'] : null,
            'telegram_username' => $data['username'] ?? null,
            'telegram_first_name' => $data['first_name'] ?? null,
            'telegram_can_join_groups' => $data['can_join_groups'] ?? null,
            'telegram_can_read_all_group_messages' => $data['can_read_all_group_messages'] ?? null,
            'telegram_supports_inline_queries' => $data['supports_inline_queries'] ?? null,
            'token_verified_at' => now(),
        ]);

        $this->audit->log('bot', 'bot_token.verified', 'Bot token verified.', ['bot_id' => $bot->id], $request->user(), 'success', $bot);

        return back()->with('status', 'Bot token verified.');
    }

    public function activate(Request $request, Bot $bot, TelegramBotService $telegram, RuntimeSettingsService $settings, DockerRuntimeService $dockerRuntime): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        $token = $this->token($bot);

        if (! $token || ! $bot->token_hash) {
            return back()->withErrors(['runtime' => 'Add and verify a bot token before activating this bot.']);
        }

        if (! $bot->token_verified_at) {
            $verify = $telegram->validateToken($token);

            if (! $verify['valid']) {
                return back()->withErrors(['runtime' => 'Add and verify a bot token before activating this bot.']);
            }

            $bot->forceFill(['token_verified_at' => now()])->save();
        }

        if (! $this->hasPublicHttpsUrl()) {
            $bot->update([
                'status' => 'stopped',
                'webhook_status' => 'failed',
                'webhook_last_error' => 'Telegram requires a public HTTPS webhook URL. Set the public callback URL to your Cloudflare Tunnel, LocalTunnel, or ngrok HTTPS URL.',
            ]);

            return back()->withErrors(['runtime' => 'Telegram requires a public HTTPS webhook URL before activation.']);
        }

        if (! $bot->webhook_secret) {
            $bot->forceFill(['webhook_secret' => Str::random(48)])->save();
        }

        $url = $bot->webhook_endpoint;
        $result = $telegram->setWebhook($token, $url);

        if (! $result['ok']) {
            $bot->update([
                'status' => 'stopped',
                'webhook_url' => $url,
                'webhook_status' => 'failed',
                'webhook_last_error' => $result['message'] ?? 'Unable to set Telegram webhook.',
            ]);

            $this->audit->log('bot', 'bot.activation_failed', 'Bot activation failed.', ['bot_id' => $bot->id, 'error' => $result['message'] ?? null], $request->user(), 'failed', $bot);

            return back()->withErrors(['runtime' => $result['message'] ?? 'Unable to set Telegram webhook.']);
        }

        if ($settings->string('runtime_mode', 'local') === 'docker' && $settings->boolean('runtime_docker_enabled', false)) {
            $runtime = $dockerRuntime->startBotContainer($bot);

            if (! ($runtime['ok'] ?? false) || ! ($runtime['healthy'] ?? false)) {
                $bot->update([
                    'status' => 'stopped',
                    'webhook_url' => $url,
                    'webhook_status' => 'active',
                    'webhook_last_error' => null,
                    'runtime_mode' => 'docker',
                    'runtime_status' => 'error',
                    'last_runtime_error' => $runtime['error'] ?? 'Docker runtime failed to start.',
                ]);

                $this->audit->log('bot', 'bot.activation_failed', 'Bot activation failed because Docker runtime did not start.', [
                    'bot_id' => $bot->id,
                    'category' => 'docker_runtime',
                    'error' => $runtime['error'] ?? null,
                ], $request->user(), 'failed', $bot);

                return back()->withErrors(['runtime' => 'Docker runtime failed to start. Check Docker runtime status.']);
            }
        }

        $bot->update([
            'status' => 'running',
            'runtime_mode' => $settings->string('runtime_mode', 'local'),
            'runtime_status' => $settings->string('runtime_mode', 'local') === 'docker' && $settings->boolean('runtime_docker_enabled', false) ? 'running' : null,
            'webhook_url' => $url,
            'webhook_set_at' => now(),
            'webhook_status' => 'active',
            'webhook_last_error' => null,
            'last_webhook_update_at' => now(),
        ]);

        $this->audit->log('bot', 'bot.activated', 'Bot activated.', ['bot_id' => $bot->id], $request->user(), 'success', $bot);

        return back()->with('status', 'Bot activated.');
    }

    public function stop(Request $request, Bot $bot, RuntimeSettingsService $settings, DockerRuntimeService $dockerRuntime): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        $bot->update(['status' => 'stopped']);

        if ($settings->string('runtime_mode', 'local') === 'docker' || $bot->runtime_mode === 'docker') {
            $dockerRuntime->stopBotContainer($bot);
        }

        $this->audit->log('bot', 'bot.stopped', 'Bot stopped.', ['bot_id' => $bot->id], $request->user(), 'success', $bot);

        return back()->with('status', 'Bot stopped.');
    }

    private function token(Bot $bot): ?string
    {
        try {
            $raw = $bot->getRawOriginal('token_encrypted');

            return $raw ? Crypt::decryptString($raw) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function hasPublicHttpsUrl(): bool
    {
        return PublicCallbackUrl::isPublicHttps();
    }
}
