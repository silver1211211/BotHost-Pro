<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'admin_broadcast_id',
    'channel',
    'recipient_type',
    'recipient_id',
    'recipient_email',
    'telegram_chat_id',
    'status',
    'attempts',
    'error_message',
    'sent_at',
    'failed_at',
    'metadata',
])]
class AdminBroadcastDelivery extends Model
{
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(AdminBroadcast::class, 'admin_broadcast_id');
    }
}
