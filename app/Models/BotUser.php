<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'bot_id',
    'telegram_user_id',
    'telegram_username',
    'telegram_first_name',
    'telegram_last_name',
    'telegram_language_code',
    'is_bot',
    'status',
    'first_seen_at',
    'last_active_at',
    'message_count',
    'command_count',
    'blocked_at',
    'metadata',
])]
class BotUser extends Model
{
    public const STATUSES = ['active', 'blocked', 'deleted', 'paused'];

    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_active_at' => 'datetime',
            'blocked_at' => 'datetime',
            'message_count' => 'integer',
            'command_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function broadcastRecipients(): HasMany
    {
        return $this->hasMany(BotBroadcastRecipient::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeActiveSince(Builder $query, mixed $since): Builder
    {
        return $query->active()->where('last_active_at', '>=', $since);
    }

    public function scopeBroadcastTarget(Builder $query, string $target): Builder
    {
        return match ($target) {
            'active_24h' => $query->activeSince(now()->subHours(24)),
            'active_48h' => $query->activeSince(now()->subHours(48)),
            'active_72h' => $query->activeSince(now()->subHours(72)),
            'paused' => $query->where('status', 'paused'),
            'blocked' => $query->where('status', 'blocked'),
            default => $query->active(),
        };
    }
}
