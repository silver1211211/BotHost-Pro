<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'bot_broadcast_id',
    'bot_id',
    'bot_user_id',
    'telegram_user_id',
    'chat_id',
    'status',
    'sent_at',
    'failed_at',
    'error_message',
    'telegram_message_id',
    'attempts',
])]
class BotBroadcastRecipient extends Model
{
    public const STATUSES = ['pending', 'sending', 'sent', 'failed', 'skipped'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(BotBroadcast::class, 'bot_broadcast_id');
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }
}
