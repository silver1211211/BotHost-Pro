<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramBotService
{
    private const INVALID_TOKEN_MESSAGE = 'Invalid Telegram bot token. Please check the token from BotFather.';

    private const UNAVAILABLE_MESSAGE = 'Unable to verify Telegram bot token right now. Please try again.';

    public function validateToken(string $token): array
    {
        $token = $this->normalizeToken($token);

        if (! $this->looksLikeBotToken($token)) {
            return ['valid' => false, 'message' => self::INVALID_TOKEN_MESSAGE];
        }

        $response = $this->request($token, 'getMe');

        if (! $response['ok']) {
            return $response['network_failed']
                ? ['valid' => false, 'message' => self::UNAVAILABLE_MESSAGE]
                : ['valid' => false, 'message' => self::INVALID_TOKEN_MESSAGE];
        }

        $payload = $response['payload'];

        if (! is_array($payload) || ($payload['ok'] ?? false) !== true) {
            return [
                'valid' => false,
                'message' => self::INVALID_TOKEN_MESSAGE,
            ];
        }

        if (! isset($payload['result']) || ! is_array($payload['result'])) {
            return [
                'valid' => false,
                'message' => 'Unable to read bot details from Telegram. Please try again.',
            ];
        }

        return [
            'valid' => true,
            'data' => [
                'id' => $payload['result']['id'] ?? null,
                'is_bot' => $payload['result']['is_bot'] ?? true,
                'first_name' => $payload['result']['first_name'] ?? null,
                'username' => $payload['result']['username'] ?? null,
                'can_join_groups' => $payload['result']['can_join_groups'] ?? null,
                'can_read_all_group_messages' => $payload['result']['can_read_all_group_messages'] ?? null,
                'supports_inline_queries' => $payload['result']['supports_inline_queries'] ?? null,
            ],
        ];
    }

    public function setWebhook(string $token, string $url): array
    {
        return $this->telegramBooleanResult($this->request($token, 'setWebhook', ['url' => $url]));
    }

    public function deleteWebhook(string $token): array
    {
        return $this->telegramBooleanResult($this->request($token, 'deleteWebhook'));
    }

    public function sendMessage(
        string $token,
        int|string $chatId,
        string $text,
        ?string $parseMode = null,
        ?array $replyMarkup = null,
        bool $disableWebPagePreview = false,
        bool $protectContent = false,
        int|string|null $replyToMessageId = null,
    ): array
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if (filled($parseMode)) {
            $payload['parse_mode'] = $parseMode;
        }

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        if ($disableWebPagePreview) {
            $payload['disable_web_page_preview'] = true;
        }

        if ($protectContent) {
            $payload['protect_content'] = true;
        }

        if ($replyToMessageId !== null && $replyToMessageId !== '') {
            $payload['reply_to_message_id'] = $replyToMessageId;
        }

        Log::info('[BotHost] TelegramBotService.sendMessage', [
            'chat_id' => $chatId,
            'text_preview' => str($text)->limit(120, '')->toString(),
            'parse_mode' => $parseMode,
        ]);
        $result = $this->telegramBooleanResult($this->request($token, 'sendMessage', $payload));

        Log::info('[BotHost] TelegramBotService.sendMessage API result', [
            'ok' => $result['ok'],
            'message' => $result['message'] ?? null,
        ]);

        if ($result['ok'] || ! filled($parseMode)) {
            return $result;
        }

        Log::warning('Telegram sendMessage failed with parse mode; retrying without parse mode.', [
            'chat_id' => (string) $chatId,
            'parse_mode' => $parseMode,
            'message' => $result['message'] ?? null,
        ]);

        $retryPayload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyMarkup) {
            $retryPayload['reply_markup'] = $replyMarkup;
        }

        if ($disableWebPagePreview) {
            $retryPayload['disable_web_page_preview'] = true;
        }

        if ($protectContent) {
            $retryPayload['protect_content'] = true;
        }

        if ($replyToMessageId !== null && $replyToMessageId !== '') {
            $retryPayload['reply_to_message_id'] = $replyToMessageId;
        }

        return $this->telegramBooleanResult($this->request($token, 'sendMessage', $retryPayload));
    }

    public function sendPhoto(
        string $token,
        int|string $chatId,
        string $photoPathOrUrl,
        ?string $caption = null,
        ?string $parseMode = null,
        ?array $replyMarkup = null,
        bool $protectContent = false,
        int|string|null $replyToMessageId = null,
    ): array {
        $payload = ['chat_id' => $chatId];

        if (filled($caption)) {
            $payload['caption'] = $caption;
        }

        if (filled($parseMode)) {
            $payload['parse_mode'] = $parseMode;
        }

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        if ($protectContent) $payload['protect_content'] = true;
        if ($replyToMessageId !== null && $replyToMessageId !== '') $payload['reply_to_message_id'] = $replyToMessageId;

        $result = $this->telegramBooleanResult($this->request($token, 'sendPhoto', $payload, [
            'photo' => $photoPathOrUrl,
        ]));

        if ($result['ok'] || ! filled($parseMode)) {
            return $result;
        }

        Log::warning('Telegram sendPhoto failed with parse mode; retrying without parse mode.', [
            'chat_id' => (string) $chatId,
            'parse_mode' => $parseMode,
            'message' => $result['message'] ?? null,
        ]);

        unset($payload['parse_mode']);

        return $this->telegramBooleanResult($this->request($token, 'sendPhoto', $payload, [
            'photo' => $photoPathOrUrl,
        ]));
    }

    public function editMessageText(
        string $token,
        int|string $chatId,
        int|string|null $messageId,
        string $text,
        ?string $parseMode = null,
        ?array $replyMarkup = null,
        bool $disableWebPagePreview = false,
    ): array {
        if ($messageId === null || $messageId === '') {
            return ['ok' => false, 'message' => 'Telegram editMessageText failed.'];
        }

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
        ];

        if (filled($parseMode)) {
            $payload['parse_mode'] = $parseMode;
        }

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        if ($disableWebPagePreview) {
            $payload['disable_web_page_preview'] = true;
        }

        return $this->telegramBooleanResult($this->request($token, 'editMessageText', $payload));
    }

    public function editMessageCaption(
        string $token,
        int|string $chatId,
        int|string|null $messageId,
        ?string $caption = null,
        ?string $parseMode = null,
        ?array $replyMarkup = null,
    ): array {
        if ($messageId === null || $messageId === '') {
            return ['ok' => false, 'message' => 'Telegram editMessageCaption failed.'];
        }

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        if (filled($parseMode)) {
            $payload['parse_mode'] = $parseMode;
        }

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        return $this->telegramBooleanResult($this->request($token, 'editMessageCaption', $payload));
    }

    public function editMessageReplyMarkup(
        string $token,
        int|string $chatId,
        int|string|null $messageId,
        ?array $replyMarkup = null,
    ): array {
        if ($messageId === null || $messageId === '') {
            return ['ok' => false, 'message' => 'Telegram editMessageReplyMarkup failed.'];
        }

        return $this->telegramBooleanResult($this->request($token, 'editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => $replyMarkup ?: ['inline_keyboard' => []],
        ]));
    }

    public function getChatMember(string $token, int|string $chatId, int|string $userId): array
    {
        $result = $this->checkTelegramChannelMember($token, $chatId, $userId);

        return ($result['ok'] ?? false)
            ? ['ok' => true, 'data' => $result['raw'] ?? []]
            : [
                'ok' => false,
                'message' => $result['message'] ?? 'Telegram getChatMember failed.',
                'error_code' => $result['error_code'] ?? null,
            ];
    }

    public function checkTelegramChannelMember(string $token, int|string $chatId, int|string $telegramUserId): array
    {
        $startedAt = microtime(true);
        $token = $this->normalizeToken($token);

        if (! $this->looksLikeBotToken($token)) {
            return $this->channelMemberFailure('Invalid Telegram bot token.', 'unknown', null, null, $startedAt);
        }

        if ($chatId === '' || $telegramUserId === '') {
            return $this->channelMemberFailure('Channel and Telegram user ID are required.', 'unknown', null, null, $startedAt);
        }

        $response = $this->requestQuick($token, 'getChatMember', [
            'chat_id' => $chatId,
            'user_id' => $telegramUserId,
        ]);

        if (! ($response['ok'] ?? false)) {
            $payload = $response['payload'] ?? null;
            $message = is_array($payload)
                ? (string) ($payload['description'] ?? 'Telegram getChatMember failed.')
                : 'Telegram getChatMember failed.';

            Log::warning('Telegram getChatMember request failed.', [
                'chat_id' => (string) $chatId,
                'telegram_user_id' => (string) $telegramUserId,
                'network_failed' => (bool) ($response['network_failed'] ?? false),
                'error_code' => is_array($payload) ? ($payload['error_code'] ?? null) : null,
                'message' => str($message)->limit(500, '')->toString(),
            ]);

            return $this->channelMemberFailure(
                ($response['network_failed'] ?? false)
                    ? 'Telegram API request timed out or is unavailable.'
                    : $this->friendlyGetChatMemberError($message),
                'unknown',
                is_array($payload) ? ($payload['error_code'] ?? null) : null,
                is_array($payload) ? $payload : null,
                $startedAt,
            );
        }

        $payload = $response['payload'] ?? null;

        if (! is_array($payload) || ($payload['ok'] ?? false) !== true || ! is_array($payload['result'] ?? null)) {
            $message = is_array($payload)
                ? (string) ($payload['description'] ?? 'Telegram getChatMember failed.')
                : 'Telegram returned an invalid getChatMember response.';

            Log::warning('Telegram getChatMember API returned an error.', [
                'chat_id' => (string) $chatId,
                'telegram_user_id' => (string) $telegramUserId,
                'error_code' => is_array($payload) ? ($payload['error_code'] ?? null) : null,
                'message' => str($message)->limit(500, '')->toString(),
            ]);

            return $this->channelMemberFailure(
                $this->friendlyGetChatMemberError($message),
                'unknown',
                is_array($payload) ? ($payload['error_code'] ?? null) : null,
                is_array($payload) ? $payload : null,
                $startedAt,
            );
        }

        $member = $payload['result'];
        $status = (string) ($member['status'] ?? 'unknown');
        $isMember = in_array($status, ['creator', 'administrator', 'member'], true)
            || ($status === 'restricted' && ($member['is_member'] ?? false) === true);

        return [
            'ok' => true,
            'is_member' => $isMember,
            'status' => $status,
            'message' => $isMember ? 'User is a member.' : 'User is not a member.',
            'raw' => $member,
            'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }

    public function deleteMessage(string $token, int|string $chatId, int|string $messageId): array
    {
        return $this->telegramBooleanResult($this->request($token, 'deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]));
    }

    public function answerCallbackQuery(
        string $token,
        string $callbackQueryId,
        string $text = '',
        bool $showAlert = false,
        ?string $url = null,
        int $cacheTime = 0,
    ): array {
        $payload = ['callback_query_id' => $callbackQueryId];
        if ($text !== '') $payload['text'] = $text;
        if ($showAlert) $payload['show_alert'] = true;
        if (filled($url)) $payload['url'] = $url;
        if ($cacheTime > 0) $payload['cache_time'] = $cacheTime;

        return $this->telegramBooleanResult($this->request($token, 'answerCallbackQuery', $payload));
    }

    public function sendDocument(
        string $token,
        int|string $chatId,
        string $documentPathOrUrl,
        ?string $caption = null,
        ?string $parseMode = null,
        ?array $replyMarkup = null,
        bool $protectContent = false,
        int|string|null $replyToMessageId = null,
    ): array {
        $payload = ['chat_id' => $chatId];
        if (filled($caption)) $payload['caption'] = $caption;
        if (filled($parseMode)) $payload['parse_mode'] = $parseMode;
        if ($replyMarkup) $payload['reply_markup'] = $replyMarkup;
        if ($protectContent) $payload['protect_content'] = true;
        if ($replyToMessageId !== null && $replyToMessageId !== '') $payload['reply_to_message_id'] = $replyToMessageId;

        return $this->telegramBooleanResult($this->request($token, 'sendDocument', $payload, [
            'document' => $documentPathOrUrl,
        ]));
    }

    public function sendVideo(
        string $token,
        int|string $chatId,
        string $videoPathOrUrl,
        ?string $caption = null,
        ?string $parseMode = null,
        ?array $replyMarkup = null,
        bool $protectContent = false,
        int|string|null $replyToMessageId = null,
    ): array {
        $payload = ['chat_id' => $chatId];
        if (filled($caption)) $payload['caption'] = $caption;
        if (filled($parseMode)) $payload['parse_mode'] = $parseMode;
        if ($replyMarkup) $payload['reply_markup'] = $replyMarkup;
        if ($protectContent) $payload['protect_content'] = true;
        if ($replyToMessageId !== null && $replyToMessageId !== '') $payload['reply_to_message_id'] = $replyToMessageId;

        return $this->telegramBooleanResult($this->request($token, 'sendVideo', $payload, [
            'video' => $videoPathOrUrl,
        ]));
    }

    public function sendAudio(
        string $token,
        int|string $chatId,
        string $audioPathOrUrl,
        ?string $caption = null,
        ?string $parseMode = null,
        ?array $replyMarkup = null,
        bool $protectContent = false,
        int|string|null $replyToMessageId = null,
    ): array {
        $payload = ['chat_id' => $chatId];
        if (filled($caption)) $payload['caption'] = $caption;
        if (filled($parseMode)) $payload['parse_mode'] = $parseMode;
        if ($replyMarkup) $payload['reply_markup'] = $replyMarkup;
        if ($protectContent) $payload['protect_content'] = true;
        if ($replyToMessageId !== null && $replyToMessageId !== '') $payload['reply_to_message_id'] = $replyToMessageId;

        return $this->telegramBooleanResult($this->request($token, 'sendAudio', $payload, [
            'audio' => $audioPathOrUrl,
        ]));
    }

    public function sendAnimation(
        string $token,
        int|string $chatId,
        string $animationPathOrUrl,
        ?string $caption = null,
        ?string $parseMode = null,
        ?array $replyMarkup = null,
        bool $protectContent = false,
        int|string|null $replyToMessageId = null,
    ): array {
        $payload = ['chat_id' => $chatId];
        if (filled($caption)) $payload['caption'] = $caption;
        if (filled($parseMode)) $payload['parse_mode'] = $parseMode;
        if ($replyMarkup) $payload['reply_markup'] = $replyMarkup;
        if ($protectContent) $payload['protect_content'] = true;
        if ($replyToMessageId !== null && $replyToMessageId !== '') $payload['reply_to_message_id'] = $replyToMessageId;

        return $this->telegramBooleanResult($this->request($token, 'sendAnimation', $payload, [
            'animation' => $animationPathOrUrl,
        ]));
    }

    public function sendSticker(string $token, int|string $chatId, string $stickerFileId): array
    {
        return $this->telegramBooleanResult($this->request($token, 'sendSticker', [
            'chat_id' => $chatId,
            'sticker' => $stickerFileId,
        ]));
    }

    public function sendLocation(string $token, int|string $chatId, float $latitude, float $longitude): array
    {
        return $this->telegramBooleanResult($this->request($token, 'sendLocation', [
            'chat_id' => $chatId,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]));
    }

    public function sendContact(
        string $token,
        int|string $chatId,
        string $phoneNumber,
        string $firstName,
        ?string $lastName = null,
    ): array {
        $payload = ['chat_id' => $chatId, 'phone_number' => $phoneNumber, 'first_name' => $firstName];
        if (filled($lastName)) $payload['last_name'] = $lastName;

        return $this->telegramBooleanResult($this->request($token, 'sendContact', $payload));
    }

    public function copyMessage(
        string $token,
        int|string $chatId,
        int|string $fromChatId,
        int|string $messageId,
        ?string $caption = null,
        ?string $parseMode = null,
        ?array $replyMarkup = null,
    ): array {
        $payload = ['chat_id' => $chatId, 'from_chat_id' => $fromChatId, 'message_id' => $messageId];
        if (filled($caption)) $payload['caption'] = $caption;
        if (filled($parseMode)) $payload['parse_mode'] = $parseMode;
        if ($replyMarkup) $payload['reply_markup'] = $replyMarkup;

        return $this->telegramBooleanResult($this->request($token, 'copyMessage', $payload));
    }

    public function forwardMessage(
        string $token,
        int|string $chatId,
        int|string $fromChatId,
        int|string $messageId,
        bool $disableNotification = false,
        bool $protectContent = false,
    ): array {
        $payload = [
            'chat_id' => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId,
        ];

        if ($disableNotification) $payload['disable_notification'] = true;
        if ($protectContent) $payload['protect_content'] = true;

        return $this->telegramBooleanResult($this->request($token, 'forwardMessage', $payload));
    }

    public function getWebhookInfo(string $token): array
    {
        $response = $this->request($token, 'getWebhookInfo');

        if (! $response['ok']) {
            return [
                'ok' => false,
                'message' => $response['network_failed'] ? self::UNAVAILABLE_MESSAGE : self::INVALID_TOKEN_MESSAGE,
                'data' => null,
            ];
        }

        $payload = $response['payload'];

        if (! is_array($payload) || ($payload['ok'] ?? false) !== true || ! isset($payload['result'])) {
            return [
                'ok' => false,
                'message' => self::INVALID_TOKEN_MESSAGE,
                'data' => null,
            ];
        }

        return [
            'ok' => true,
            'message' => null,
            'data' => $payload['result'],
        ];
    }

    private function telegramBooleanResult(array $response): array
    {
        if (! $response['ok']) {
            return [
                'ok' => false,
                'message' => match ($response['method']) {
                    'sendMessage' => 'Telegram sendMessage failed.',
                    'sendPhoto' => 'Telegram sendPhoto failed.',
                    'editMessageText' => 'Telegram editMessageText failed.',
                    'deleteMessage' => 'Telegram deleteMessage failed.',
                    'answerCallbackQuery' => 'Telegram answerCallbackQuery failed.',
                    default => $response['network_failed'] ? self::UNAVAILABLE_MESSAGE : self::INVALID_TOKEN_MESSAGE,
                },
            ];
        }

        $payload = $response['payload'];

        if (! is_array($payload) || ($payload['ok'] ?? false) !== true) {
            return [
                'ok' => false,
                'message' => match ($response['method']) {
                    'sendMessage' => $payload['description'] ?? 'Telegram sendMessage failed.',
                    'sendPhoto' => $payload['description'] ?? 'Telegram sendPhoto failed.',
                    'editMessageText' => $payload['description'] ?? 'Telegram editMessageText failed.',
                    'deleteMessage' => $payload['description'] ?? 'Telegram deleteMessage failed.',
                    'answerCallbackQuery' => $payload['description'] ?? 'Telegram answerCallbackQuery failed.',
                    default => $payload['description'] ?? self::INVALID_TOKEN_MESSAGE,
                },
                'error_code' => is_array($payload) ? ($payload['error_code'] ?? null) : null,
            ];
        }

        return [
            'ok' => true,
            'message' => $payload['description'] ?? null,
            'data' => $payload['result'] ?? null,
        ];
    }

    private function request(string $token, string $method, array $payload = [], array $files = []): array
    {
        $token = $this->normalizeToken($token);

        try {
            $request = Http::connectTimeout(5)->timeout(15)->retry(2, 250, throw: false)->acceptJson();
            $url = "https://api.telegram.org/bot{$token}/{$method}";

            foreach ($files as $field => $pathOrUrl) {
                if (is_string($pathOrUrl) && is_file($pathOrUrl)) {
                    $request = $request->attach($field, fopen($pathOrUrl, 'r'), basename($pathOrUrl));
                    continue;
                }

                $payload[$field] = $pathOrUrl;
            }

            if ($files !== [] && isset($payload['reply_markup']) && is_array($payload['reply_markup'])) {
                $payload['reply_markup'] = json_encode($payload['reply_markup']);
            }

            $response = $payload === []
                ? $request->get($url)
                : $request->post($url, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('Telegram API request failed.', [
                'method' => $method,
                'error' => $exception->getMessage(),
            ]);

            return ['ok' => false, 'network_failed' => true, 'payload' => null, 'method' => $method];
        } catch (Throwable $exception) {
            Log::warning('Telegram API request failed.', [
                'method' => $method,
                'error' => $exception->getMessage(),
            ]);

            return ['ok' => false, 'network_failed' => true, 'payload' => null, 'method' => $method];
        }

        try {
            $responsePayload = $response->json();
        } catch (Throwable $exception) {
            Log::warning('Telegram API returned invalid JSON.', [
                'method' => $method,
                'error' => $exception->getMessage(),
            ]);

            $responsePayload = null;
        }

        return [
            'ok' => $response->successful(),
            'network_failed' => false,
            'payload' => $responsePayload,
            'method' => $method,
        ];
    }

    private function requestQuick(string $token, string $method, array $payload = []): array
    {
        $token = $this->normalizeToken($token);

        try {
            $request = Http::connectTimeout(3)->timeout(15)->acceptJson();
            $url = "https://api.telegram.org/bot{$token}/{$method}";
            $response = $payload === [] ? $request->get($url) : $request->post($url, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('Telegram API quick request failed.', ['method' => $method, 'error' => $exception->getMessage()]);

            return ['ok' => false, 'network_failed' => true, 'payload' => null, 'method' => $method];
        } catch (Throwable $exception) {
            Log::warning('Telegram API quick request failed.', ['method' => $method, 'error' => $exception->getMessage()]);

            return ['ok' => false, 'network_failed' => true, 'payload' => null, 'method' => $method];
        }

        try {
            $responsePayload = $response->json();
        } catch (Throwable) {
            $responsePayload = null;
        }

        return [
            'ok' => $response->successful(),
            'network_failed' => false,
            'payload' => $responsePayload,
            'method' => $method,
        ];
    }

    private function normalizeToken(string $token): string
    {
        return trim($token);
    }

    private function looksLikeBotToken(string $token): bool
    {
        return preg_match('/^\d+:[A-Za-z0-9_-]{10,}$/', $token) === 1;
    }

    private function channelMemberFailure(string $message, string $status = 'unknown', ?int $errorCode = null, ?array $raw = null, ?float $startedAt = null): array
    {
        $result = [
            'ok' => false,
            'is_member' => false,
            'status' => $status,
            'message' => $message,
            'elapsed_ms' => $startedAt !== null ? (int) round((microtime(true) - $startedAt) * 1000) : null,
        ];

        if ($errorCode !== null) {
            $result['error_code'] = $errorCode;
        }

        if ($raw !== null) {
            $result['raw'] = $raw;
        }

        return $result;
    }

    private function friendlyGetChatMemberError(string $message): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'chat not found')) {
            return 'Telegram channel or group was not found. Check the @username or numeric chat ID.';
        }

        if (str_contains($lower, 'user not found')) {
            return 'Telegram user was not found in this chat.';
        }

        if (str_contains($lower, 'bot is not a member') || str_contains($lower, 'not enough rights') || str_contains($lower, 'member list is inaccessible')) {
            return 'Bot cannot access this channel. Add the bot as an admin of the channel.';
        }

        if (str_contains($lower, 'unauthorized') || str_contains($lower, 'token')) {
            return 'Invalid Telegram bot token.';
        }

        return 'Telegram getChatMember failed.';
    }
}
