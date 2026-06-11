<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminBroadcast;
use App\Models\Bot;
use App\Models\BotBroadcast;
use App\Models\BotUser;
use App\Models\User;
use App\Services\AdminBroadcastService;
use App\Services\TelegramBroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class BroadcastController extends Controller
{
    public function index(TelegramBroadcastService $telegram): View
    {
        $stats = $this->stats();

        return view('admin.broadcasts.index', [
            'stats' => $stats,
            'broadcasts' => AdminBroadcast::query()
                ->with(['admin', 'targetBot'])
                ->latest()
                ->limit(50)
                ->get(),
            'telegramBots' => Bot::withTrashed()
                ->whereNotNull('token_verified_at')
                ->whereNotNull('token_encrypted')
                ->orderBy('name')
                ->get()
                ->filter(fn (Bot $bot) => $telegram->botCanBroadcast($bot))
                ->values(),
        ]);
    }

    public function store(Request $request, AdminBroadcastService $broadcasts, TelegramBroadcastService $telegram): RedirectResponse
    {
        $data = $request->validate([
            'campaign_name' => ['nullable', 'string', 'max:120'],
            'campaign_type' => ['nullable', Rule::in(['announcement', 'promotion', 'maintenance', 'update', 'security', 'billing', 'educational', 'custom'])],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:4096'],
            'message_type' => ['required', Rule::in(['text', 'html', 'markdown'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'critical'])],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(['in_app', 'email', 'telegram', 'platform'])],
            'target_type' => ['required', Rule::in([
                'all_users', 'active_users', 'free_users', 'pro_users', 'business_users',
                'admin_users', 'suspended_users', 'banned_users',
                'new_today', 'new_7d', 'new_30d',
                'users_with_bots', 'users_without_bots',
                'bot_24h', 'bot_48h', 'bot_72h', 'bot_7d',
            ])],
            'target_bot_id' => ['nullable', 'integer'],
            'tg_window'      => ['nullable', Rule::in(['24h', '48h', '72h', 'all'])],
            'max_recipients' => ['nullable', 'integer', 'min:1'],
            'batch_size' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'batch_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
            'cta_text' => ['nullable', 'string', 'max:40'],
            'cta_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'channels.required' => 'Select at least one delivery channel.',
            'channels.min' => 'Select at least one delivery channel.',
        ]);

        $platformChannels = array_intersect($data['channels'], ['in_app', 'email']);
        $telegramSelected = in_array('telegram', $data['channels'], true);

        $platformRecipientCount = $platformChannels === []
            ? 0
            : (clone $broadcasts->platformUsers($data['target_type']))->count();

        $telegramRecipientCount = 0;
        if ($telegramSelected) {
            $tgWindow = $data['tg_window'] ?? 'all';
            $tgQuery = BotUser::query()
                ->where('status', '!=', 'deleted')
                ->where('status', '!=', 'blocked')
                ->whereNotNull('telegram_user_id')
                ->where('telegram_user_id', '!=', '');
            if ($tgWindow === '24h') {
                $tgQuery->where('last_active_at', '>=', now()->subHours(24));
            } elseif ($tgWindow === '48h') {
                $tgQuery->where('last_active_at', '>=', now()->subHours(48));
            } elseif ($tgWindow === '72h') {
                $tgQuery->where('last_active_at', '>=', now()->subHours(72));
            }
            $telegramRecipientCount = $tgQuery->count();
        }

        if ($platformRecipientCount === 0 && $telegramRecipientCount === 0 && ! in_array('platform', $data['channels'], true)) {
            return back()->withInput()->withErrors(['target_type' => 'No eligible recipients found for the selected target.']);
        }

        $broadcasts->createAndSend($data, $request->user());

        $parts = ['Broadcast queued successfully.'];
        if ($telegramSelected && $telegramRecipientCount > 0) {
            $parts[] = number_format($telegramRecipientCount) . ' bot users targeted across all bots.';
        } elseif ($platformRecipientCount > 0) {
            $parts[] = number_format($platformRecipientCount) . ' platform users targeted.';
        }

        return redirect()->route('admin.broadcasts.index')
            ->with('status', implode(' ', $parts));
    }

    private function stats(): array
    {
        try {
            $totalUsers    = User::count();
            $freeUsers     = User::whereIn('subscription_plan', ['free', null, ''])->orWhereNull('subscription_plan')->count();
            $proUsers      = User::where('subscription_plan', 'pro')->count();
            $businessUsers = User::where('subscription_plan', 'business')->count();
            $adminUsers    = User::where('role', 'admin')->count();
            $activeUsers   = User::where('status', 'active')->count();
            $suspendedUsers= User::where('status', 'suspended')->count();
            $bannedUsers   = User::where('status', 'banned')->count();

            $newToday  = User::whereDate('created_at', today())->count();
            $new7d     = User::where('created_at', '>=', now()->subDays(7))->count();
            $new30d    = User::where('created_at', '>=', now()->subDays(30))->count();

            $usersWithBots = Bot::distinct('user_id')->count('user_id');
            $totalBots     = Bot::count();
            $runningBots   = Bot::where('status', 'running')->count();

            $totalBotUsers = $this->safe(fn () => BotUser::count(), 0);
            $botUsers24h   = $this->safe(fn () => BotUser::where('last_active_at', '>=', now()->subHours(24))->count(), 0);
            $botUsers48h   = $this->safe(fn () => BotUser::where('last_active_at', '>=', now()->subHours(48))->count(), 0);
            $botUsers72h   = $this->safe(fn () => BotUser::where('last_active_at', '>=', now()->subHours(72))->count(), 0);
            $botUsers7d    = $this->safe(fn () => BotUser::where('last_active_at', '>=', now()->subDays(7))->count(), 0);
            $totalBroadcasts = $this->safe(fn () => AdminBroadcast::count(), 0);
            $broadcastsToday = $this->safe(fn () => AdminBroadcast::whereDate('created_at', today())->count(), 0);
            $broadcastsThisWeek = $this->safe(fn () => AdminBroadcast::where('created_at', '>=', now()->subDays(7))->count(), 0);
            $broadcastsCompleted = $this->safe(fn () => AdminBroadcast::where('status', 'completed')->count(), 0);
            $broadcastsFailed = $this->safe(fn () => AdminBroadcast::where('status', 'failed')->count(), 0);
            $broadcastRecipientsSent = $this->safe(fn () => AdminBroadcast::sum('sent_count'), 0);
        } catch (Throwable) {
            $totalUsers = $freeUsers = $proUsers = $businessUsers = $adminUsers = 0;
            $activeUsers = $suspendedUsers = $bannedUsers = 0;
            $newToday = $new7d = $new30d = 0;
            $usersWithBots = $totalBots = $runningBots = 0;
            $totalBotUsers = $botUsers24h = $botUsers48h = $botUsers72h = $botUsers7d = 0;
            $totalBroadcasts = $broadcastsToday = $broadcastsThisWeek = $broadcastsCompleted = $broadcastsFailed = $broadcastRecipientsSent = 0;
        }

        return [
            'total_users'      => $totalUsers,
            'free_users'       => $freeUsers,
            'pro_users'        => $proUsers,
            'business_users'   => $businessUsers,
            'admin_users'      => $adminUsers,
            'active_users'     => $activeUsers,
            'suspended_users'  => $suspendedUsers,
            'banned_users'     => $bannedUsers,
            'new_today'        => $newToday,
            'new_7d'           => $new7d,
            'new_30d'          => $new30d,
            'users_with_bots'  => $usersWithBots,
            'total_bots'       => $totalBots,
            'running_bots'     => $runningBots,
            'total_bot_users'  => $totalBotUsers,
            'bot_users_24h'    => $botUsers24h,
            'bot_users_48h'    => $botUsers48h,
            'bot_users_72h'    => $botUsers72h,
            'bot_users_7d'     => $botUsers7d,
            'total_broadcasts' => $totalBroadcasts,
            'broadcasts_today' => $broadcastsToday,
            'broadcasts_this_week' => $broadcastsThisWeek,
            'broadcasts_completed' => $broadcastsCompleted,
            'broadcasts_failed' => $broadcastsFailed,
            'broadcast_recipients_sent' => $broadcastRecipientsSent,
        ];
    }

    /** @template T */
    private function safe(callable $fn, mixed $default): mixed
    {
        try { return $fn(); } catch (Throwable) { return $default; }
    }
}
