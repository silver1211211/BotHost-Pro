<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotUser;
use Illuminate\Database\Eloquent\Builder;

class BotBroadcastTargetingService
{
    public function queryTargets(Bot $bot, string $targetType): Builder
    {
        $query = BotUser::query()->where('bot_id', $bot->id);

        return match ($targetType) {
            'active_24h' => $query->where('status', 'active')->where('last_active_at', '>=', now()->subHours(24)),
            'active_48h' => $query->where('status', 'active')->where('last_active_at', '>=', now()->subHours(48)),
            'active_72h' => $query->where('status', 'active')->where('last_active_at', '>=', now()->subHours(72)),
            'active_7d' => $query->where('status', 'active')->where('last_active_at', '>=', now()->subDays(7)),
            'active_30d' => $query->where('status', 'active')->where('last_active_at', '>=', now()->subDays(30)),
            'paused_users' => $query->where('status', 'paused'),
            'blocked_users' => $query->where('status', 'blocked'),
            'specific_users' => $query,
            default => $query->where('status', 'active'),
        };
    }
}
