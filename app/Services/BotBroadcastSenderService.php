<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BotBroadcast;
use App\Models\BotBroadcastRecipient;
use App\Models\BotUser;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Throwable;

class BotBroadcastSenderService
{
    public function __construct(
        private readonly BotBroadcastTargetingService $targeting,
        private readonly TelegramBotService $telegram,
        private readonly PlanAccessService $planAccess,
    ) {}

    public function start(BotBroadcast $broadcast): array
    {
        $broadcast->loadMissing('bot');

        if (! $this->botCanBroadcast($broadcast->bot)) {
            return [
                'ok' => false,
                'message' => 'This bot cannot send broadcasts until a verified token is available.',
                'target_count' => 0,
            ];
        }

        if ($broadcast->status !== 'draft') {
            return [
                'ok' => false,
                'message' => 'Only draft broadcasts can be started.',
                'target_count' => $broadcast->target_count,
            ];
        }

        try {
            $created = DB::transaction(function () use ($broadcast): int {
                $broadcast->recipients()->delete();

                $targetQuery = $this->targeting
                    ->queryTargets($broadcast->bot, $broadcast->target_type)
                    ->where('status', '!=', 'deleted')
                    ->whereNotNull('telegram_user_id')
                    ->where('telegram_user_id', '!=', '');

                $specificTargetIds = $this->specificTargetIds($broadcast);
                if ($broadcast->target_type === 'specific_users') {
                    $targetQuery->whereIn('id', $specificTargetIds);
                }

                $eligibleCount = (clone $targetQuery)->count();
                $plan = $this->planForBroadcast($broadcast);
                $broadcast->loadMissing('user');
                $limit = $this->limitForUser($broadcast->user);
                $limitApplied = is_int($limit) && $eligibleCount > $limit;
                $appliedLimit = is_int($limit) ? $limit : null;

                $metadata = $broadcast->metadata ?? [];
                $metadata['eligible_count'] = $eligibleCount;
                $metadata['applied_limit'] = $appliedLimit;
                $metadata['limit_applied'] = $limitApplied;
                $metadata['plan_at_send'] = $plan;
                $metadata['started_by'] = $broadcast->user_id;
                $customLimit = $this->customRecipientLimitForBroadcast($broadcast);
                $customLimitApplied = is_int($customLimit) && $eligibleCount > $customLimit;
                $effectiveLimit = $customLimit ?? (is_int($limit) ? $limit : null);
                $targetCount = min($eligibleCount, $effectiveLimit ?? $eligibleCount);

                $metadata['custom_recipient_limit'] = $customLimit;
                $metadata['custom_limit_applied'] = $customLimitApplied;
                $metadata['set_by_admin_id'] = $customLimit ? ($metadata['set_by_admin_id'] ?? $broadcast->user_id) : null;

                $estimatedSeconds = $this->estimateSeconds($targetCount);

                $targetIds = $targetQuery
                    ->orderByDesc('last_active_at')
                    ->orderByDesc('created_at')
                    ->when(is_int($effectiveLimit), fn ($query) => $query->limit($effectiveLimit))
                    ->pluck('id');

                $targetIds->chunk(500)->each(function ($ids) use ($broadcast): void {
                        $botUsers = BotUser::query()
                            ->whereIn('id', $ids)
                            ->orderByDesc('last_active_at')
                            ->orderByDesc('created_at')
                            ->get();

                        $now = now();
                        $insertRows = $botUsers
                            ->map(function (BotUser $botUser) use ($broadcast, $now): array {
                                $telegramUserId = (string) $botUser->telegram_user_id;

                                return [
                                    'bot_broadcast_id' => $broadcast->id,
                                    'bot_id' => $broadcast->bot_id,
                                    'bot_user_id' => $botUser->id,
                                    'telegram_user_id' => $telegramUserId,
                                    'chat_id' => $telegramUserId,
                                    'status' => 'pending',
                                    'attempts' => 0,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            })
                            ->all();

                        if ($insertRows !== []) {
                            BotBroadcastRecipient::query()->insertOrIgnore($insertRows);
                        }
                    });

                $created = $broadcast->recipients()->count();

                if ($created === 0) {
                    $metadata['last_error'] = 'No recipients found for this target.';

                    $broadcast->forceFill([
                        'status' => 'failed',
                        'target_count' => 0,
                        'sent_count' => 0,
                        'failed_count' => 0,
                        'started_at' => now(),
                        'completed_at' => now(),
                        'metadata' => $metadata,
                    ])->save();

                    return 0;
                }

                $broadcast->forceFill([
                    'status' => $broadcast->scheduled_at && $broadcast->scheduled_at->isFuture() ? 'scheduled' : 'queued',
                    'target_count' => $created,
                    'sent_count' => 0,
                    'failed_count' => 0,
                    'estimated_seconds' => $estimatedSeconds,
                    'started_at' => $broadcast->scheduled_at && $broadcast->scheduled_at->isFuture() ? null : now(),
                    'completed_at' => null,
                    'metadata' => $metadata,
                ])->save();

                return $created;
            });
        } catch (Throwable $exception) {
            $this->markBroadcastFailed($broadcast, $exception->getMessage());

            Log::error('Failed to start bot broadcast', [
                'broadcast_id' => $broadcast->id,
                'bot_id' => $broadcast->bot_id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Unable to start broadcast.',
                'target_count' => 0,
            ];
        }

        if ($created === 0) {
            $this->logActivity($broadcast, 'broadcast_failed', 'Broadcast failed: no recipients found.');

            return [
                'ok' => false,
                'message' => 'No recipients found for this target.',
                'target_count' => 0,
            ];
        }

        $this->logActivity($broadcast, 'broadcast_started', "Started broadcast {$broadcast->id}.");
        $this->audit($broadcast, 'broadcast.created', 'Broadcast queued.', [
            'broadcast_id' => $broadcast->id,
            'bot_id' => $broadcast->bot_id,
            'total_recipients' => $created,
        ]);

        Log::info('Broadcast started', [
            'broadcast_id' => $broadcast->id,
            'bot_id' => $broadcast->bot_id,
            'target_count' => $created,
            'queue_connection' => Config::get('queue.default'),
        ]);

        return [
            'ok' => true,
            'message' => 'Broadcast queued.',
            'target_count' => $created,
        ];
    }

    public function processNextBatch(BotBroadcast $broadcast, ?int $limit = null): array
    {
        $broadcast->loadMissing('bot');
        $limit ??= $this->batchSize();

        if (in_array($broadcast->status, ['cancelled', 'completed', 'failed'], true)) {
            return $this->summary($broadcast, 0, 0, 0);
        }

        if ($broadcast->status === 'scheduled' && $broadcast->scheduled_at?->isFuture()) {
            return $this->summary($broadcast, 0, 0, 0);
        }

        try {
            if (! $this->botCanBroadcast($broadcast->bot)) {
                $this->markBroadcastFailed($broadcast, 'This bot cannot send broadcasts until a verified token is available.');

                return $this->summary($broadcast->fresh(), 0, 0, 0);
            }

            if (in_array($broadcast->status, ['scheduled', 'queued'], true)) {
                $broadcast->forceFill([
                    'status' => 'running',
                    'started_at' => $broadcast->started_at ?: now(),
                ])->save();
                $this->audit($broadcast, 'broadcast.started', 'Broadcast processing started.', [
                    'broadcast_id' => $broadcast->id,
                    'bot_id' => $broadcast->bot_id,
                    'total_recipients' => $broadcast->target_count,
                ]);
            }

            if (! in_array($broadcast->status, ['running', 'sending'], true)) {
                return $this->summary($broadcast, 0, 0, 0);
            }

            $token = Crypt::decryptString($broadcast->bot->getRawOriginal('token_encrypted'));

            if ($broadcast->message_type === 'image' && ! $this->imagePathForTelegram($broadcast)) {
                $this->markBroadcastFailed($broadcast, 'Broadcast image file is missing.');

                return [
                    'processed' => 0,
                    'sent' => 0,
                    'failed' => 0,
                    'remaining' => $broadcast->recipients()->where('status', 'pending')->count(),
                    'status' => 'failed',
                ];
            }

            $recipients = $broadcast->recipients()
                ->where('status', 'pending')
                ->orderBy('id')
                ->limit(max(1, $limit))
                ->get();

            $processed = 0;
            $sent = 0;
            $failed = 0;

            Log::info('Broadcast batch processing started', [
                'broadcast_id' => $broadcast->id,
                'bot_id' => $broadcast->bot_id,
                'batch_size' => $limit,
                'recipient_count' => $recipients->count(),
            ]);

            foreach ($recipients as $recipient) {
                $processed++;
                $recipient->forceFill([
                    'status' => 'sending',
                    'attempts' => $recipient->attempts + 1,
                ])->save();

                try {
                    Log::info('Broadcast recipient send starting', [
                        'broadcast_id' => $broadcast->id,
                        'bot_id' => $broadcast->bot_id,
                        'recipient_id' => $recipient->id,
                        'telegram_user_id' => $recipient->telegram_user_id,
                    ]);

                    $result = $this->sendBroadcastToChat(
                        $broadcast,
                        $token,
                        $recipient->chat_id ?: $recipient->telegram_user_id,
                    );

                    if ($result['ok']) {
                        $recipient->forceFill([
                            'status' => 'sent',
                            'sent_at' => now(),
                            'failed_at' => null,
                            'error_message' => null,
                            'telegram_message_id' => isset($result['data']['message_id'])
                                ? (string) $result['data']['message_id']
                                : null,
                        ])->save();

                        $broadcast->increment('sent_count');
                        $sent++;

                        Log::info('Broadcast recipient send succeeded', [
                            'broadcast_id' => $broadcast->id,
                            'bot_id' => $broadcast->bot_id,
                            'recipient_id' => $recipient->id,
                            'telegram_user_id' => $recipient->telegram_user_id,
                        ]);
                    } else {
                        $this->markRecipientFailed($recipient, $result['message'] ?? 'Telegram sendMessage failed.');
                        $this->markBotUserSuppressedIfNeeded($recipient, $result['message'] ?? '');
                        $broadcast->increment('failed_count');
                        $failed++;

                        Log::warning('Broadcast recipient send failed', [
                            'broadcast_id' => $broadcast->id,
                            'bot_id' => $broadcast->bot_id,
                            'recipient_id' => $recipient->id,
                            'telegram_user_id' => $recipient->telegram_user_id,
                            'message' => $result['message'] ?? 'Telegram sendMessage failed.',
                        ]);
                    }
                } catch (Throwable $exception) {
                    $this->markRecipientFailed($recipient, 'Telegram sendMessage failed.');
                    $broadcast->increment('failed_count');
                    $failed++;

                    Log::warning('Broadcast recipient send failed', [
                        'broadcast_id' => $broadcast->id,
                        'bot_id' => $broadcast->bot_id,
                        'recipient_id' => $recipient->id,
                        'telegram_user_id' => $recipient->telegram_user_id,
                        'error' => $exception->getMessage(),
                    ]);
                }

                usleep($this->messageDelayMicroseconds());
            }

            $broadcast->refresh();
            $this->markCompletedIfDone($broadcast);

            return $this->summary($broadcast->fresh(), $processed, $sent, $failed);
        } catch (Throwable $exception) {
            $this->markBroadcastFailed($broadcast, $exception->getMessage());

            Log::error('Broadcast batch processing failed', [
                'broadcast_id' => $broadcast->id,
                'bot_id' => $broadcast->bot_id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
                'remaining' => 0,
                'status' => 'failed',
            ];
        }
    }

    public function markCompletedIfDone(BotBroadcast $broadcast): void
    {
        if (! in_array($broadcast->status, ['queued', 'running', 'sending'], true)) {
            return;
        }

        if ($broadcast->recipients()->where('status', 'pending')->exists()) {
            return;
        }

        $metadata = $broadcast->metadata ?? [];
        $metadata['completed_reason'] = 'All recipients processed.';

        $broadcast->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
            'metadata' => $metadata,
        ])->save();

        $this->logActivity($broadcast, 'broadcast_completed', "Completed broadcast {$broadcast->id}.");
        $this->audit($broadcast, 'broadcast.completed', 'Broadcast completed.', [
            'broadcast_id' => $broadcast->id,
            'bot_id' => $broadcast->bot_id,
            'total_recipients' => $broadcast->target_count,
            'sent_count' => $broadcast->sent_count,
            'failed_count' => $broadcast->failed_count,
        ]);
    }

    public function sendTest(BotBroadcast $broadcast): array
    {
        $broadcast->loadMissing('bot');

        if (! $this->botCanBroadcast($broadcast->bot)) {
            return ['ok' => false, 'message' => 'This bot cannot send broadcasts until a verified token is available.'];
        }

        $botUser = $broadcast->bot->botUsers()
            ->where('status', 'active')
            ->whereNotNull('telegram_user_id')
            ->where('telegram_user_id', '!=', '')
            ->latest('last_active_at')
            ->latest('created_at')
            ->first();

        if (! $botUser) {
            return ['ok' => false, 'message' => 'No active Telegram user found for test send.'];
        }

        if ($broadcast->message_type === 'image' && ! $this->imagePathForTelegram($broadcast)) {
            return ['ok' => false, 'message' => 'Broadcast image file is missing.'];
        }

        try {
            $token = Crypt::decryptString($broadcast->bot->getRawOriginal('token_encrypted'));

            return $this->sendBroadcastToChat($broadcast, $token, $botUser->telegram_user_id);
        } catch (Throwable $exception) {
            Log::warning('Broadcast test send failed', [
                'broadcast_id' => $broadcast->id,
                'bot_id' => $broadcast->bot_id,
                'error' => $exception->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Broadcast test send failed.'];
        }
    }

    private function markRecipientFailed(BotBroadcastRecipient $recipient, string $message): void
    {
        $recipient->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => str($message)->limit(500)->toString(),
        ])->save();
    }

    private function markBotUserSuppressedIfNeeded(BotBroadcastRecipient $recipient, string $message): void
    {
        $normalized = strtolower($message);

        if (! str_contains($normalized, 'blocked')
            && ! str_contains($normalized, 'chat not found')
            && ! str_contains($normalized, 'user is deactivated')) {
            return;
        }

        $recipient->botUser?->forceFill([
            'status' => 'blocked',
            'blocked_at' => now(),
        ])->save();
    }

    private function markBroadcastFailed(BotBroadcast $broadcast, string $message): void
    {
        $metadata = $broadcast->metadata ?? [];
        $metadata['last_error'] = str($message)->limit(500)->toString();

        $broadcast->forceFill([
            'status' => 'failed',
            'completed_at' => now(),
            'metadata' => $metadata,
        ])->save();

        $this->logActivity($broadcast, 'broadcast_failed', "Broadcast {$broadcast->id} failed.");
        $this->audit($broadcast, 'broadcast.failed', 'Broadcast failed.', [
            'broadcast_id' => $broadcast->id,
            'bot_id' => $broadcast->bot_id,
            'total_recipients' => $broadcast->target_count,
            'sent_count' => $broadcast->sent_count,
            'failed_count' => $broadcast->failed_count,
        ], 'failed');
    }

    private function summary(BotBroadcast $broadcast, int $processed, int $sent, int $failed): array
    {
        return [
            'processed' => $processed,
            'sent' => $sent,
            'failed' => $failed,
            'remaining' => $broadcast->recipients()->where('status', 'pending')->count(),
            'status' => $broadcast->status,
        ];
    }

    public function limitNotice(BotBroadcast $broadcast): ?string
    {
        $metadata = $broadcast->metadata ?? [];

        if (($metadata['limit_applied'] ?? false) !== true) {
            return null;
        }

        $limit = (int) ($metadata['applied_limit'] ?? 0);
        $plan = (string) ($metadata['plan_at_send'] ?? 'free');

        if ($plan === 'pro') {
            return 'Your current plan allows broadcasts to the most recent '.number_format($limit).' active users.';
        }

        return 'Your current plan allows broadcasts to the most recent '.number_format($limit).' active users. Upgrade to reach a larger audience.';
    }

    public function previewTarget(mixed $broadcastOrBot, string $targetType, mixed $owner = null, mixed $customRecipientLimit = null): array
    {
        $bot = $broadcastOrBot instanceof BotBroadcast ? $broadcastOrBot->bot : $broadcastOrBot;

        if (! $bot instanceof \App\Models\Bot) {
            $bot = \App\Models\Bot::query()->findOrFail($broadcastOrBot);
        }

        $query = $this->targeting
            ->queryTargets($bot, $targetType)
            ->where('status', '!=', 'deleted')
            ->whereNotNull('telegram_user_id')
            ->where('telegram_user_id', '!=', '');

        if ($targetType === 'specific_users' && $broadcastOrBot instanceof BotBroadcast) {
            $query->whereIn('id', $this->specificTargetIds($broadcastOrBot));
        } elseif ($targetType === 'specific_users') {
            $query->whereRaw('1 = 0');
        }

        $eligibleCount = (clone $query)->count();
        $plan = $this->planForUser($owner ?? $bot->user);
        $limit = $this->limitForUser($owner ?? $bot->user);
        $limitApplied = is_int($limit) && $eligibleCount > $limit;
        $customLimit = $this->normalizeCustomRecipientLimit($owner, $customRecipientLimit);
        $customLimitApplied = is_int($customLimit) && $eligibleCount > $customLimit;
        $effectiveLimit = $customLimit ?? (is_int($limit) ? $limit : null);
        $count = min($eligibleCount, $effectiveLimit ?? $eligibleCount);
        $estimatedSeconds = $this->estimateSeconds($count);

        return [
            'count' => $count,
            'eligible_count' => $eligibleCount,
            'limit_applied' => $limitApplied,
            'applied_limit' => is_int($limit) ? $limit : null,
            'plan_at_send' => $plan,
            'custom_recipient_limit' => $customLimit,
            'custom_limit_applied' => $customLimitApplied,
            'estimated_seconds' => $estimatedSeconds,
            'estimated_human' => BotBroadcast::humanDuration($estimatedSeconds),
        ];
    }

    public function batchSize(): int
    {
        return max(1, (int) config('broadcasts.batch_size', 20));
    }

    public function retryFailed(BotBroadcast $broadcast): array
    {
        if (! in_array($broadcast->status, ['completed', 'failed'], true)) {
            return ['ok' => false, 'message' => 'Only completed or failed broadcasts can retry failed recipients.'];
        }

        if (! $this->botCanBroadcast($broadcast->bot)) {
            return ['ok' => false, 'message' => 'This bot cannot send broadcasts until a verified token is available.'];
        }

        $failedRecipients = $broadcast->recipients()->where('status', 'failed')->count();

        if ($failedRecipients === 0) {
            return ['ok' => false, 'message' => 'There are no failed recipients to retry.'];
        }

        $broadcast->recipients()->where('status', 'failed')->update([
            'status' => 'pending',
            'failed_at' => null,
            'error_message' => null,
            'updated_at' => now(),
        ]);

        $broadcast->forceFill([
            'status' => 'queued',
            'failed_count' => max(0, $broadcast->failed_count - $failedRecipients),
            'completed_at' => null,
        ])->save();

        $this->logActivity($broadcast, 'broadcast_retry_failed', "Retrying failed recipients for broadcast {$broadcast->id}.");
        $this->audit($broadcast, 'broadcast.retry_failed', 'Retrying failed broadcast recipients.', [
            'broadcast_id' => $broadcast->id,
            'bot_id' => $broadcast->bot_id,
            'total_recipients' => $broadcast->target_count,
            'retried_count' => $failedRecipients,
        ]);

        return ['ok' => true, 'retried' => $failedRecipients];
    }

    public function botCanBroadcast(mixed $bot): bool
    {
        if (! $bot instanceof \App\Models\Bot) {
            return false;
        }

        if (! $bot->trashed() && ! in_array($bot->status, ['active', 'running', 'paused'], true)) {
            return false;
        }

        if (! $bot->token_verified_at || ! filled($bot->getRawOriginal('token_encrypted'))) {
            return false;
        }

        try {
            return filled(Crypt::decryptString($bot->getRawOriginal('token_encrypted')));
        } catch (Throwable) {
            return false;
        }
    }

    public function estimateSeconds(int $targetCount): int
    {
        if ($targetCount <= 0) {
            return 0;
        }

        $batchSize = $this->batchSize();
        $messageDelaySeconds = max(0, (int) config('broadcasts.message_delay_ms', 100)) / 1000;
        $batchDelaySeconds = max(0, (int) config('broadcasts.batch_delay_seconds', 1));
        $batchCount = (int) ceil($targetCount / $batchSize);

        return (int) ceil(($targetCount * $messageDelaySeconds) + (max(0, $batchCount - 1) * $batchDelaySeconds));
    }

    private function messageDelayMicroseconds(): int
    {
        return max(0, (int) config('broadcasts.message_delay_ms', 100)) * 1000;
    }

    private function planForBroadcast(BotBroadcast $broadcast): string
    {
        $broadcast->loadMissing('user');

        return $this->planForUser($broadcast->user);
    }

    private function planForUser(mixed $user): string
    {
        if ($user?->isAdmin()) {
            return 'business';
        }

        $plan = strtolower((string) ($user?->subscription_plan ?: 'free'));

        return in_array($plan, ['free', 'pro', 'business'], true) ? $plan : 'free';
    }

    private function limitForUser(mixed $user): int|string
    {
        if (! $user instanceof User) {
            return (int) config('broadcasts.limits.free', 20000);
        }

        return $this->planAccess->broadcastRecipientLimit($user);
    }

    private function limitForPlan(string $plan): int|string
    {
        $configured = config("broadcasts.limits.{$plan}", config('broadcasts.limits.free', 20000));

        if ($configured === 'unlimited') {
            return 'unlimited';
        }

        return max(0, (int) $configured);
    }

    private function normalizeCustomRecipientLimit(mixed $owner, mixed $customRecipientLimit): ?int
    {
        if (! $owner?->isAdmin()) {
            return null;
        }

        if ($customRecipientLimit === null || $customRecipientLimit === '') {
            return null;
        }

        $limit = (int) $customRecipientLimit;

        return $limit > 0 ? min($limit, 10000000) : null;
    }

    private function customRecipientLimitForBroadcast(BotBroadcast $broadcast): ?int
    {
        $broadcast->loadMissing('user');
        $metadata = $broadcast->metadata ?? [];

        return $this->normalizeCustomRecipientLimit(
            $broadcast->user,
            $metadata['custom_recipient_limit'] ?? null,
        );
    }

    private function specificTargetIds(BotBroadcast $broadcast): array
    {
        $identifiers = collect($broadcast->metadata['specific_recipient_identifiers'] ?? [])
            ->map(fn ($value) => $this->normalizeRecipientIdentifier($value))
            ->filter()
            ->unique()
            ->values();

        if ($identifiers->isEmpty()) {
            return [];
        }

        return $broadcast->bot->botUsers()
            ->where('status', '!=', 'deleted')
            ->whereNotNull('telegram_user_id')
            ->where('telegram_user_id', '!=', '')
            ->get()
            ->filter(function (BotUser $botUser) use ($identifiers): bool {
                $values = collect([
                    $botUser->telegram_user_id,
                    $botUser->telegram_username,
                    Arr::get($botUser->metadata ?? [], 'phone'),
                    Arr::get($botUser->metadata ?? [], 'phone_number'),
                    Arr::get($botUser->metadata ?? [], 'telegram_phone'),
                    Arr::get($botUser->metadata ?? [], 'contact.phone_number'),
                ])->map(fn ($value) => $this->normalizeRecipientIdentifier($value))->filter();

                return $values->intersect($identifiers)->isNotEmpty();
            })
            ->pluck('id')
            ->all();
    }

    private function normalizeRecipientIdentifier(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '@')) {
            return strtolower($value);
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits !== '' ? $digits : strtolower($value);
    }

    private function sendBroadcastToChat(BotBroadcast $broadcast, string $token, int|string $chatId): array
    {
        $replyMarkup = $this->replyMarkup($broadcast);
        $parseMode = $broadcast->parse_mode ?: null;

        if ($broadcast->message_type === 'image') {
            $photoPath = $this->imagePathForTelegram($broadcast);

            if (! $photoPath) {
                return ['ok' => false, 'message' => 'Broadcast image file is missing.'];
            }

            return $this->telegram->sendPhoto(
                $token,
                $chatId,
                $photoPath,
                filled($broadcast->message) ? $broadcast->message : null,
                $parseMode,
                $replyMarkup,
            );
        }

        return $this->telegram->sendMessage(
            $token,
            $chatId,
            $broadcast->message,
            $parseMode,
            $replyMarkup,
            (bool) $broadcast->disable_web_page_preview,
        );
    }

    private function replyMarkup(BotBroadcast $broadcast): ?array
    {
        if (! filled($broadcast->cta_text) || ! filled($broadcast->cta_url)) {
            return null;
        }

        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => $broadcast->cta_text,
                        'url' => $broadcast->cta_url,
                    ],
                ],
            ],
        ];
    }

    private function imagePathForTelegram(BotBroadcast $broadcast): ?string
    {
        if (! filled($broadcast->image_path)) {
            return null;
        }

        $path = Storage::disk('public')->path($broadcast->image_path);

        return is_file($path) ? $path : null;
    }

    private function logActivity(BotBroadcast $broadcast, string $action, string $description): void
    {
        try {
            ActivityLog::create([
                'user_id' => $broadcast->user_id,
                'action' => $action,
                'description' => $description,
                'ip_address' => request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed to write broadcast activity log', [
                'broadcast_id' => $broadcast->id,
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function audit(BotBroadcast $broadcast, string $action, string $description, array $metadata, string $status = 'success'): void
    {
        try {
            app(AuditLogService::class)->log('broadcast', $action, $description, $metadata, $broadcast->user, $status, BotBroadcast::class, $broadcast->id);
        } catch (Throwable) {
            //
        }
    }
}
