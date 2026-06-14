<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotRuntimeData;
use App\Models\BotUserRuntimeData;
use App\Support\NodeRuntimeConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RuntimeStorageController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = NodeRuntimeConfig::secret();

        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Runtime-Secret', ''))) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized runtime storage request.'], 401);
        }

        $bot = Bot::query()->find($request->integer('bot_id'));
        if (! $bot) {
            return response()->json(['ok' => false, 'error' => 'Bot not found.'], 404);
        }

        $action = (string) $request->input('action', '');
        $key = trim((string) $request->input('key', ''));

        if ($key === '' || strlen($key) > 100) {
            return response()->json(['ok' => false, 'error' => 'Storage key is invalid.'], 422);
        }

        if (! in_array($action, ['bot.get', 'user.get', 'bot.set', 'user.set', 'user.find'], true)) {
            return response()->json(['ok' => false, 'error' => 'Unsupported runtime storage action.'], 422);
        }

        if ($this->isSecretStorageKey($key)) {
            return response()->json(['ok' => false, 'error' => 'Storage key is not available through runtime bridge.'], 403);
        }

        if (str_ends_with($action, '.set') && ! $this->isRuntimeWritableStorageKey($action, $key)) {
            return response()->json(['ok' => false, 'error' => 'Storage key is not writable through runtime bridge.'], 403);
        }

        try {
            $value = match ($action) {
                'bot.get' => $this->botValue($bot->id, $key),
                'user.get' => $this->userValue($bot->id, (string) $request->input('telegram_user_id', ''), $key),
                'bot.set' => $this->setBotValue($bot->id, $key, $request->input('value')),
                'user.set' => $this->setUserValue($bot->id, (string) $request->input('telegram_user_id', ''), $key, $request->input('value')),
                'user.find' => $this->findUserValue($bot->id, $key, $request->input('value')),
            };
        } catch (Throwable $exception) {
            Log::error('[BotHost] runtime_storage_bridge_failed', [
                'bot_id' => $bot->id,
                'action' => $action,
                'key' => $key,
                'error' => str($exception->getMessage())->limit(500, '')->toString(),
            ]);

            return response()->json(['ok' => false, 'error' => 'Runtime storage request failed.'], 500);
        }

        return response()->json([
            'ok' => true,
            'found' => $value['found'],
            'value' => $value['value'],
            'user_id' => $value['user_id'] ?? null,
        ]);
    }

    private function botValue(int $botId, string $key): array
    {
        $row = BotRuntimeData::query()
            ->where('bot_id', $botId)
            ->where('key', $key)
            ->first(['value']);

        return ['found' => $row !== null, 'value' => $row?->value];
    }

    private function userValue(int $botId, string $telegramUserId, string $key): array
    {
        $telegramUserId = trim($telegramUserId);
        if ($telegramUserId === '') {
            return ['found' => false, 'value' => null];
        }

        $row = BotUserRuntimeData::query()
            ->where('bot_id', $botId)
            ->where('telegram_user_id', $telegramUserId)
            ->where('key', $key)
            ->first(['value']);

        return ['found' => $row !== null, 'value' => $row?->value];
    }

    private function setBotValue(int $botId, string $key, mixed $value): array
    {
        $row = BotRuntimeData::firstOrNew([
            'bot_id' => $botId,
            'key' => $key,
        ]);

        $row->value = $value;
        $row->save();

        return ['found' => true, 'value' => $row->value];
    }

    private function setUserValue(int $botId, string $telegramUserId, string $key, mixed $value): array
    {
        $telegramUserId = trim($telegramUserId);
        if ($telegramUserId === '') {
            return ['found' => false, 'value' => null];
        }

        $row = BotUserRuntimeData::firstOrNew([
            'bot_id' => $botId,
            'telegram_user_id' => $telegramUserId,
            'key' => $key,
        ]);

        $row->value = $value;
        $row->save();

        return ['found' => true, 'value' => $row->value];
    }

    private function findUserValue(int $botId, string $key, mixed $value): array
    {
        $needle = $this->canonicalStorageValue($value);

        if ($needle === null) {
            return ['found' => false, 'value' => null, 'user_id' => null];
        }

        $rows = BotUserRuntimeData::query()
            ->where('bot_id', $botId)
            ->where('key', $key)
            ->get(['telegram_user_id', 'value']);

        foreach ($rows as $row) {
            if ($this->canonicalStorageValue($row->value) === $needle) {
                return [
                    'found' => true,
                    'user_id' => (string) $row->telegram_user_id,
                    'value' => $row->value,
                ];
            }
        }

        return ['found' => false, 'value' => null, 'user_id' => null];
    }

    private function isSecretStorageKey(string $key): bool
    {
        return in_array($key, [
            'oxapay_merchant_api_key',
            'oxapay_payout_api_key',
            'faucetpay_api_key',
        ], true);
    }

    private function isRuntimeWritableStorageKey(string $action, string $key): bool
    {
        if ($action === 'bot.set') {
            return $key === 'support_tickets' || str_starts_with($key, 'support_ticket_');
        }

        if ($action === 'user.set') {
            return in_array($key, [
                'admin_state',
                'support_reply_ticket_id',
                'support_target_user',
                'admin_reply_ticket_id',
            ], true);
        }

        return false;
    }

    private function canonicalStorageValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return strtolower(trim($value));
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
