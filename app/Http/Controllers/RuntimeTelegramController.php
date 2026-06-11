<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

class RuntimeTelegramController extends Controller
{
    public function __invoke(Request $request, TelegramBotService $telegram): JsonResponse
    {
        $secret = (string) config('services.node_runtime.secret', '');

        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Runtime-Secret', ''))) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized runtime Telegram request.'], 401);
        }

        $bot = Bot::query()->find($request->integer('bot_id'));
        if (! $bot) {
            return response()->json(['ok' => false, 'error' => 'Bot not found.'], 404);
        }

        $token = $this->decryptBotToken($bot);
        if (! filled($token)) {
            return response()->json(['ok' => false, 'error' => 'Bot token is not configured.'], 422);
        }

        $action = (string) $request->input('action', '');
        $options = $request->input('options', []);
        $options = is_array($options) ? $options : [];

        try {
            $result = match ($action) {
                'telegram.sendMessage' => $telegram->sendMessage(
                    $token,
                    $options['chat_id'] ?? '',
                    (string) ($options['text'] ?? ''),
                    $options['parse_mode'] ?? null,
                    is_array($options['reply_markup'] ?? null) ? $options['reply_markup'] : null,
                    (bool) ($options['disable_web_page_preview'] ?? false),
                    (bool) ($options['protect_content'] ?? false),
                    $options['reply_to_message_id'] ?? null,
                ),
                'telegram.sendPhoto' => $telegram->sendPhoto(
                    $token,
                    $options['chat_id'] ?? '',
                    (string) ($options['photo'] ?? ''),
                    isset($options['caption']) ? (string) $options['caption'] : null,
                    $options['parse_mode'] ?? null,
                    is_array($options['reply_markup'] ?? null) ? $options['reply_markup'] : null,
                    (bool) ($options['protect_content'] ?? false),
                    $options['reply_to_message_id'] ?? null,
                ),
                'telegram.sendDocument' => $telegram->sendDocument(
                    $token,
                    $options['chat_id'] ?? '',
                    (string) ($options['document'] ?? ''),
                    isset($options['caption']) ? (string) $options['caption'] : null,
                    $options['parse_mode'] ?? null,
                    is_array($options['reply_markup'] ?? null) ? $options['reply_markup'] : null,
                    (bool) ($options['protect_content'] ?? false),
                    $options['reply_to_message_id'] ?? null,
                ),
                'telegram.editMessageText' => $telegram->editMessageText(
                    $token,
                    $options['chat_id'] ?? '',
                    $options['message_id'] ?? null,
                    (string) ($options['text'] ?? ''),
                    $options['parse_mode'] ?? null,
                    array_key_exists('reply_markup', $options) && is_array($options['reply_markup']) ? $options['reply_markup'] : null,
                    (bool) ($options['disable_web_page_preview'] ?? false),
                ),
                'telegram.editMessageCaption' => $telegram->editMessageCaption(
                    $token,
                    $options['chat_id'] ?? '',
                    $options['message_id'] ?? null,
                    isset($options['caption']) ? (string) $options['caption'] : null,
                    $options['parse_mode'] ?? null,
                    array_key_exists('reply_markup', $options) && is_array($options['reply_markup']) ? $options['reply_markup'] : null,
                ),
                'telegram.editMessageReplyMarkup' => $telegram->editMessageReplyMarkup(
                    $token,
                    $options['chat_id'] ?? '',
                    $options['message_id'] ?? null,
                    is_array($options['reply_markup'] ?? null) ? $options['reply_markup'] : null,
                ),
                'telegram.getChatMember' => $telegram->getChatMember(
                    $token,
                    $options['chat_id'] ?? '',
                    $options['user_id'] ?? '',
                ),
                'telegram.deleteMessage' => $telegram->deleteMessage($token, $options['chat_id'] ?? '', $options['message_id'] ?? ''),
                'telegram.answerCallbackQuery' => $telegram->answerCallbackQuery(
                    $token,
                    (string) ($options['callback_query_id'] ?? ''),
                    (string) ($options['text'] ?? ''),
                    (bool) ($options['show_alert'] ?? false),
                    filled($options['url'] ?? null) ? (string) $options['url'] : null,
                    (int) ($options['cache_time'] ?? 0),
                ),
                default => ['ok' => false, 'message' => 'Unsupported Telegram runtime action.'],
            };
        } catch (Throwable $exception) {
            Log::warning('[BotHost] Telegram runtime bridge failed', [
                'bot_id' => $bot->id,
                'action' => $action,
                'error' => str($exception->getMessage())->limit(500, '')->toString(),
            ]);

            $result = ['ok' => false, 'message' => 'Telegram request failed.'];
        }

        return response()->json($this->safeResult($result));
    }

    private function decryptBotToken(Bot $bot): ?string
    {
        $encrypted = $bot->getRawOriginal('token_encrypted');

        if (! filled($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return null;
        }
    }

    private function safeResult(array $result): array
    {
        return ($result['ok'] ?? false)
            ? ['ok' => true, 'result' => $result['data'] ?? null]
            : ['ok' => false, 'error' => $result['message'] ?? 'Telegram request failed.'];
    }
}
