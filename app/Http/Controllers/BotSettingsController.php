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
use Illuminate\Support\Str;

class BotSettingsController extends Controller
{
    public function __construct(
        private readonly BotAccessService $access,
        private readonly AuditLogService $audit,
    ) {}

    public function update(Request $request, Bot $bot, TelegramBotService $telegram, TelegramWebhookService $webhooks): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'token' => ['nullable', 'string', 'max:255'],
            'auto_restart' => ['nullable', 'boolean'],
            'ram_limit' => ['nullable', 'integer', 'min:128', 'max:1024'],
            'cpu_limit' => ['nullable', 'numeric', 'min:0.1', 'max:2'],
            'timezone' => ['nullable', 'timezone'],
        ]);

        $name = trim((string) ($data['name'] ?? ''));
        $changedName = $name !== '' && $bot->name !== $name;
        $tokenChanged = false;
        $payload = [];

        if ($changedName) {
            $payload['name'] = $name;
            $payload['slug'] = $this->uniqueSlug($request->user()->id, $name, $bot->id);
        }

        if ($request->filled('token')) {
            $newTokenHash = Bot::tokenHash($data['token']);
            $tokenChanged = $bot->token_hash !== $newTokenHash;

            if (Bot::tokenInUse($data['token'], $bot->id)) {
                return back()
                    ->withErrors(['token' => 'This bot token is already connected to another workspace.'])
                    ->withInput($request->except('token'));
            }

            $telegramResult = $telegram->validateToken($data['token']);

            if (! $telegramResult['valid']) {
                return back()
                    ->withErrors(['token' => $telegramResult['message']])
                    ->withInput($request->except('token'));
            }

            $telegramData = $telegramResult['data'];

            $payload['token_encrypted'] = $data['token'];
            $payload['token_hash'] = $newTokenHash;
            $payload['telegram_bot_id'] = isset($telegramData['id']) ? (string) $telegramData['id'] : null;
            $payload['telegram_username'] = $telegramData['username'] ?? null;
            $payload['telegram_first_name'] = $telegramData['first_name'] ?? null;
            $payload['telegram_can_join_groups'] = $telegramData['can_join_groups'] ?? null;
            $payload['telegram_can_read_all_group_messages'] = $telegramData['can_read_all_group_messages'] ?? null;
            $payload['telegram_supports_inline_queries'] = $telegramData['supports_inline_queries'] ?? null;
            $payload['token_verified_at'] = now();
        }

        if ($payload !== []) {
            $bot->update($payload);
            $bot->refresh();
        }

        if ($tokenChanged) {
            $webhookResult = $webhooks->resetWebhook($bot, startBot: true);

            $this->audit->log('webhook', 'telegram_webhook.auto_reset_after_token_change', 'Telegram webhook auto reset after bot token change.', [
                'bot_id' => $bot->id,
                'ok' => (bool) ($webhookResult['ok'] ?? false),
            ], $request->user(), ($webhookResult['ok'] ?? false) ? 'success' : 'warning', Bot::class, $bot->id);
        }

        $settingPayload = [];

        if ($request->has('auto_restart')) {
            $settingPayload['auto_restart'] = $request->boolean('auto_restart');
        }

        foreach (['ram_limit', 'cpu_limit', 'timezone'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $settingPayload[$field] = $data[$field];
            }
        }

        if ($settingPayload !== []) {
            $bot->setting()->updateOrCreate(['bot_id' => $bot->id], $settingPayload);
        }

        if ($changedName || $request->filled('token')) {
            $this->log($request, 'bot_updated', 'Updated bot settings for: '.$bot->name);
            $this->audit->log('bot', 'bot_settings_updated', 'Bot settings updated.', [
                'bot_id' => $bot->id,
                'name_changed' => $changedName,
                'token_updated' => $request->filled('token'),
            ], $request->user(), 'success', Bot::class, $bot->id);
        }

        return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'settings'])->with('status', 'Bot settings updated.');
    }

    private function uniqueSlug(int $userId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'bot';
        $slug = $base;
        $count = 2;

        while (Bot::query()
            ->where('user_id', $userId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$count++;
        }

        return $slug;
    }

    private function log(Request $request, string $action, string $description): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
