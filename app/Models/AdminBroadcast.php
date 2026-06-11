<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'admin_id',
    'campaign_name',
    'title',
    'message',
    'campaign_type',
    'message_type',
    'priority',
    'channels',
    'target_type',
    'target_bot_id',
    'status',
    'total_recipients',
    'sent_count',
    'failed_count',
    'skipped_count',
    'batch_size',
    'batch_delay_seconds',
    'started_at',
    'completed_at',
    'metadata',
])]
class AdminBroadcast extends Model
{
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_recipients' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'skipped_count' => 'integer',
            'batch_size' => 'integer',
            'batch_delay_seconds' => 'integer',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function targetBot(): BelongsTo
    {
        return $this->belongsTo(Bot::class, 'target_bot_id')->withTrashed();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AdminBroadcastDelivery::class);
    }
}
