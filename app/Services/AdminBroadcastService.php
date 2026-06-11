<?php

namespace App\Services;

use App\Mail\BroadcastEmail;
use App\Models\AdminBroadcast;
use App\Models\AdminBroadcastDelivery;
use App\Models\Bot;
use App\Models\BotUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class AdminBroadcastService
{
    public function __construct(
        private readonly MailSettingsService $mailSettings,
        private readonly InAppNotificationService $inApp,
        private readonly TelegramBroadcastService $telegram,
        private readonly PlatformAnnouncementService $announcements,
        private readonly AuditLogService $audit,
    ) {}

    public function createAndSend(array $data, User $admin): AdminBroadcast
    {
        return DB::transaction(function () use ($data, $admin): AdminBroadcast {
            $broadcast = AdminBroadcast::create([
                'admin_id' => $admin->id,
                'campaign_name' => $data['campaign_name'] ?? null,
                'title' => $data['title'],
                'message' => $data['message'],
                'campaign_type' => $data['campaign_type'] ?? 'announcement',
                'message_type' => $data['message_type'] ?? 'text',
                'priority' => $data['priority'] ?? 'normal',
                'channels' => $data['channels'],
                'target_type' => $data['target_type'] ?? 'all_users',
                'target_bot_id' => $data['target_bot_id'] ?? null,
                'status' => 'running',
                'batch_size' => (int) ($data['batch_size'] ?? 500),
                'batch_delay_seconds' => (int) ($data['batch_delay_seconds'] ?? 5),
                'started_at' => now(),
                'metadata' => [
                    'cta_text'       => $data['cta_text'] ?? null,
                    'cta_url'        => $data['cta_url'] ?? null,
                    'tg_window'      => $data['tg_window'] ?? 'all',
                    'max_recipients' => ! empty($data['max_recipients']) ? (int) $data['max_recipients'] : null,
                ],
            ]);

            $this->auditBroadcast($broadcast, 'broadcast.created', 'Admin broadcast created.', $admin);
            $this->process($broadcast, $admin);

            return $broadcast->fresh();
        });
    }

    public function process(AdminBroadcast $broadcast, User $admin): void
    {
        $total = $sent = $failed = $skipped = 0;

        foreach ($broadcast->channels as $channel) {
            $result = match ($channel) {
                'in_app' => $this->processInApp($broadcast),
                'email' => $this->processEmail($broadcast),
                'telegram' => $this->processTelegram($broadcast),
                'platform' => $this->processPlatform($broadcast, $admin),
                default => ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 1],
            };

            $total += $result['total'];
            $sent += $result['sent'];
            $failed += $result['failed'];
            $skipped += $result['skipped'];
        }

        $broadcast->forceFill([
            'status' => $failed > 0 && $sent === 0 ? 'failed' : 'completed',
            'total_recipients' => $total,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'skipped_count' => $skipped,
            'completed_at' => now(),
        ])->save();

        $this->auditBroadcast(
            $broadcast,
            $broadcast->status === 'completed' ? 'broadcast.sent' : 'broadcast.failed',
            $broadcast->status === 'completed' ? 'Admin broadcast sent.' : 'Admin broadcast failed.',
            $admin,
            $broadcast->status === 'completed' ? 'success' : 'failed',
        );
    }

    private function processInApp(AdminBroadcast $broadcast): array
    {
        $counts = $this->emptyCounts();

        $this->platformUsers($broadcast->target_type)->chunkById(200, function ($users) use ($broadcast, &$counts): void {
            foreach ($users as $user) {
                $counts['total']++;
                $delivery = $this->delivery($broadcast, 'in_app', User::class, $user->id, $user->email);

                try {
                    $this->inApp->send($user, $broadcast);
                    $this->sent($delivery);
                    $counts['sent']++;
                } catch (Throwable $exception) {
                    $this->failed($delivery, $exception->getMessage());
                    $counts['failed']++;
                }
            }
        });

        return $counts;
    }

    private function processEmail(AdminBroadcast $broadcast): array
    {
        $counts = $this->emptyCounts();

        if (! $this->mailSettings->enabled()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'channels' => 'Email sending is disabled. Enable email sending in Admin Settings -> Notifications.',
            ]);
        }

        $this->mailSettings->apply();

        $this->platformUsers($broadcast->target_type)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->chunkById(100, function ($users) use ($broadcast, &$counts): void {
                foreach ($users as $user) {
                    $counts['total']++;
                    $delivery = $this->delivery($broadcast, 'email', User::class, $user->id, $user->email);

                    try {
                        Mail::to($user->email)->send(new BroadcastEmail($broadcast));
                        $this->sent($delivery);
                        $counts['sent']++;
                    } catch (Throwable $exception) {
                        $this->failed($delivery, 'Email delivery failed.');
                        $counts['failed']++;
                    }
                }
            });

        return $counts;
    }

    private function processTelegram(AdminBroadcast $broadcast): array
    {
        $counts        = $this->emptyCounts();
        $tgWindow      = $broadcast->metadata['tg_window'] ?? 'all';
        $maxRecipients = isset($broadcast->metadata['max_recipients']) && $broadcast->metadata['max_recipients'] > 0
            ? (int) $broadcast->metadata['max_recipients']
            : null;
        $botCache      = [];

        $query = BotUser::query()
            ->where('status', '!=', 'deleted')
            ->where('status', '!=', 'blocked')
            ->whereNotNull('telegram_user_id')
            ->where('telegram_user_id', '!=', '');

        match ($tgWindow) {
            '24h'   => $query->where('last_active_at', '>=', now()->subHours(24)),
            '48h'   => $query->where('last_active_at', '>=', now()->subHours(48)),
            '72h'   => $query->where('last_active_at', '>=', now()->subHours(72)),
            default => null,
        };

        $query->chunkById(100, function ($botUsers) use ($broadcast, &$counts, &$botCache, $maxRecipients): bool|null {
            foreach ($botUsers as $botUser) {
                if ($maxRecipients !== null && $counts['total'] >= $maxRecipients) {
                    return false;
                }

                $botId = $botUser->bot_id;
                if (! isset($botCache[$botId])) {
                    $botCache[$botId] = Bot::withTrashed()->find($botId);
                }
                $bot = $botCache[$botId];

                if (! $bot || ! $this->telegram->botCanBroadcast($bot)) {
                    $counts['skipped']++;
                    continue;
                }

                $counts['total']++;
                $delivery = $this->delivery($broadcast, 'telegram', BotUser::class, $botUser->id, null, $botUser->telegram_user_id);

                try {
                    $result = $this->telegram->sendMessage($bot, $botUser, $broadcast->message, $this->telegramParseMode($broadcast));
                    if ($result['ok']) {
                        $this->sent($delivery, ['telegram_message_id' => $result['data']['message_id'] ?? null]);
                        $counts['sent']++;
                    } else {
                        $this->failed($delivery, $result['message'] ?? 'Telegram sendMessage failed.');
                        $counts['failed']++;
                    }
                } catch (Throwable $exception) {
                    $this->failed($delivery, 'Telegram sendMessage failed.');
                    $counts['failed']++;
                }
            }

            return null;
        });

        return $counts;
    }

    private function processPlatform(AdminBroadcast $broadcast, User $admin): array
    {
        $announcement = $this->announcements->createAnnouncement($broadcast, $admin);
        $delivery = $this->delivery($broadcast, 'platform', 'platform_announcement', $announcement->id);
        $this->sent($delivery);

        return ['total' => 1, 'sent' => 1, 'failed' => 0, 'skipped' => 0];
    }

    public function platformUsers(string $targetType): Builder
    {
        $query = User::query()
            ->where(function (Builder $query): void {
                $query->whereNull('status')->orWhereNotIn('status', ['banned', 'suspended']);
            });

        return match ($targetType) {
            'active_users' => $query->where('status', 'active'),
            'free_users' => $query->where(function (Builder $q): void {
                $q->where('subscription_plan', 'free')->orWhereNull('subscription_plan')->orWhere('subscription_plan', '');
            }),
            'pro_users' => $query->where('subscription_plan', 'pro'),
            'business_users' => $query->where('subscription_plan', 'business'),
            'admin_users' => $query->where('role', 'admin'),
            'new_today' => $query->whereDate('created_at', today()),
            'new_7d' => $query->where('created_at', '>=', now()->subDays(7)),
            'new_30d' => $query->where('created_at', '>=', now()->subDays(30)),
            'users_with_bots' => $query->whereIn('id', Bot::query()->select('user_id')->whereNotNull('user_id')),
            'users_without_bots' => $query->whereNotIn('id', Bot::query()->select('user_id')->whereNotNull('user_id')),
            default => $query,
        };
    }

    private function delivery(AdminBroadcast $broadcast, string $channel, ?string $recipientType = null, ?int $recipientId = null, ?string $email = null, ?string $chatId = null): AdminBroadcastDelivery
    {
        return AdminBroadcastDelivery::create([
            'admin_broadcast_id' => $broadcast->id,
            'channel' => $channel,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'recipient_email' => $email,
            'telegram_chat_id' => $chatId,
            'status' => 'pending',
        ]);
    }

    private function sent(AdminBroadcastDelivery $delivery, array $metadata = []): void
    {
        $delivery->forceFill([
            'status' => 'sent',
            'attempts' => $delivery->attempts + 1,
            'sent_at' => now(),
            'metadata' => $metadata ?: $delivery->metadata,
        ])->save();
    }

    private function failed(AdminBroadcastDelivery $delivery, string $message): void
    {
        $delivery->forceFill([
            'status' => 'failed',
            'attempts' => $delivery->attempts + 1,
            'failed_at' => now(),
            'error_message' => Str::limit($message, 500),
        ])->save();
    }

    private function emptyCounts(): array
    {
        return ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
    }

    private function telegramParseMode(AdminBroadcast $broadcast): ?string
    {
        return match ($broadcast->message_type) {
            'html' => 'HTML',
            'markdown' => 'Markdown',
            default => null,
        };
    }

    private function auditBroadcast(AdminBroadcast $broadcast, string $action, string $description, User $admin, string $status = 'success'): void
    {
        $this->audit->log('broadcast', $action, $description, [
            'broadcast_id' => $broadcast->id,
            'channels' => $broadcast->channels,
            'total_recipients' => $broadcast->total_recipients,
            'sent_count' => $broadcast->sent_count,
            'failed_count' => $broadcast->failed_count,
            'skipped_count' => $broadcast->skipped_count,
        ], $admin, $status, AdminBroadcast::class, $broadcast->id);
    }
}
