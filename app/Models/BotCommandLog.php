<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'bot_id',
    'bot_command_id',
    'bot_user_id',
    'telegram_user_id',
    'telegram_username',
    'telegram_first_name',
    'chat_id',
    'message_text',
    'status',
    'reply_count',
    'execution_id',
    'execution_time_ms',
    'error_type',
    'error_message',
])]
class BotCommandLog extends Model
{
    public const STATUSES = [
        'success',
        'failed',
        'no_match',
        'no_reply',
        'fallback_response',
        'runtime_unavailable',
        'blocked_user',
        'paused_user',
        'deleted_user',
    ];

    protected function casts(): array
    {
        return [
            'reply_count' => 'integer',
            'execution_time_ms' => 'integer',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(BotCommand::class, 'bot_command_id');
    }

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }
}
