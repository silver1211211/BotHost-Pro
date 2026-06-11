<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'bot_id',
    'user_id',
    'title',
    'message',
    'message_type',
    'image_path',
    'image_original_name',
    'image_mime',
    'image_size',
    'cta_text',
    'cta_url',
    'parse_mode',
    'disable_web_page_preview',
    'estimated_seconds',
    'target_type',
    'status',
    'target_count',
    'sent_count',
    'failed_count',
    'started_at',
    'completed_at',
    'cancelled_at',
    'scheduled_at',
    'metadata',
])]
class BotBroadcast extends Model
{
    public const TARGET_TYPES = [
        'all_active',
        'active_24h',
        'active_48h',
        'active_72h',
        'active_7d',
        'active_30d',
        'paused_users',
        'blocked_users',
        'specific_users',
    ];

    public const STATUSES = ['draft', 'scheduled', 'queued', 'running', 'sending', 'completed', 'failed', 'cancelled'];

    protected function casts(): array
    {
        return [
            'target_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'image_size' => 'integer',
            'disable_web_page_preview' => 'boolean',
            'estimated_seconds' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BotBroadcastRecipient::class);
    }

    public function pendingRecipients(): HasMany
    {
        return $this->hasMany(BotBroadcastRecipient::class)->where('status', 'pending');
    }

    public function estimatedSendTimeHuman(): Attribute
    {
        return Attribute::get(fn () => self::humanDuration((int) ($this->estimated_seconds ?? 0)));
    }

    public function imageUrl(): Attribute
    {
        return Attribute::get(fn () => filled($this->image_path) ? Storage::disk('public')->url($this->image_path) : null);
    }

    public static function humanDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return 'About '.$seconds.' '.Str::plural('second', $seconds);
        }

        if ($seconds < 3600) {
            $minutes = (int) ceil($seconds / 60);

            return 'About '.$minutes.' '.Str::plural('minute', $minutes);
        }

        $hours = intdiv($seconds, 3600);
        $minutes = (int) ceil(($seconds % 3600) / 60);

        if ($minutes >= 60) {
            $hours++;
            $minutes = 0;
        }

        return trim('About '.$hours.' '.Str::plural('hour', $hours).' '.($minutes > 0 ? $minutes.' '.Str::plural('minute', $minutes) : ''));
    }
}
