<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotCommandLog;
use App\Models\BotTransfer;
use App\Models\BotUser;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user   = $request->user();
        $botIds = Bot::where('user_id', $user->id)->pluck('id');

        $activeUsers = $botIds->isNotEmpty()
            ? BotUser::whereIn('bot_id', $botIds)
                ->where('last_active_at', '>=', now()->subHours(24))
                ->count()
            : 0;

        $chartData  = $this->buildChartData($botIds);
        $chartStats = $this->buildChartStats($chartData);

        return view('dashboard', [
            'stats' => [
                'total_bots'       => $botIds->count(),
                'running_bots'     => $botIds->isNotEmpty()
                    ? Bot::where('user_id', $user->id)->where('status', 'running')->count()
                    : 0,
                'paused_bots'      => $botIds->isNotEmpty()
                    ? Bot::where('user_id', $user->id)->where('status', 'paused')->count()
                    : 0,
                'active_users'     => $activeUsers,
                'total_bot_users'  => $botIds->isNotEmpty()
                    ? BotUser::whereIn('bot_id', $botIds)->count()
                    : 0,
                'total_commands'   => $botIds->isNotEmpty()
                    ? BotCommand::whereIn('bot_id', $botIds)->count()
                    : 0,
                'cloned_bots'      => $botIds->isNotEmpty()
                    ? Bot::where('user_id', $user->id)->whereNotNull('cloned_from_bot_id')->count()
                    : 0,
                'transferred_bots' => BotTransfer::where(function ($q) use ($user) {
                    $q->where('receiver_email', $user->email)->orWhere('receiver_id', $user->id);
                })->where('status', 'imported')->count(),
                'subscription_plan' => ucfirst($user->subscription_plan ?? 'free'),
            ],
            'recentBots' => Bot::where('user_id', $user->id)
                ->where('status', 'running')
                ->withCount(['commands', 'botUsers'])
                ->orderByDesc('bot_users_count')
                ->take(3)
                ->get(),
            'chartData'  => $chartData,
            'chartStats' => $chartStats,
            'telegramCommunityUrl' => PlatformSetting::getValue('telegram_community_url', ''),
            'tutorialsUrl'         => PlatformSetting::getValue('tutorials_url', ''),
        ]);
    }

    private function buildChartData($botIds): array
    {
        if ($botIds->isEmpty()) {
            return [];
        }

        $now   = now();
        $since = $now->copy()->subHours(23)->startOfHour();

        $slotExpr = DB::getDriverName() === 'sqlite'
            ? DB::raw("strftime('%Y-%m-%d %H', created_at) as slot")
            : DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H') as slot");

        $raw = BotCommandLog::whereIn('bot_id', $botIds)
            ->where('created_at', '>=', $since)
            ->select($slotExpr, DB::raw('COUNT(*) as cnt'))
            ->groupBy('slot')
            ->pluck('cnt', 'slot')
            ->toArray();

        $points = [];
        for ($i = 0; $i < 24; $i++) {
            $slot     = $since->copy()->addHours($i);
            $key      = $slot->format('Y-m-d H');
            $points[] = [
                'label' => $slot->format('H:00'),
                'count' => (int) ($raw[$key] ?? 0),
            ];
        }

        return $points;
    }

    private function buildChartStats(array $data): array
    {
        $empty = ['total' => 0, 'peak_hour' => '—', 'avg_per_hour' => '0.0', 'active_hours' => 0];

        if (empty($data)) {
            return $empty;
        }

        $total       = array_sum(array_column($data, 'count'));
        $activeHours = count(array_filter($data, fn ($p) => $p['count'] > 0));
        $peak        = collect($data)->sortByDesc('count')->first();
        $peakHour    = ($peak && $peak['count'] > 0) ? $peak['label'] : '—';

        return [
            'total'        => $total,
            'peak_hour'    => $peakHour,
            'avg_per_hour' => $total > 0 ? number_format($total / 24, 1) : '0.0',
            'active_hours' => $activeHours,
        ];
    }
}
