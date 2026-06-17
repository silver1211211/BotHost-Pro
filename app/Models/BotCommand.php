<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'bot_id',
    'command_name',
    'display_name',
    'trigger_type',
    'code',
    'response_text',
    'response_type',
    'status',
    'is_pinned',
    'admin_only',
    'aliases',
    'folder',
    'last_used_at',
    'last_error_at',
    'execution_count',
    'error_count',
    'source',
    'source_template_id',
    'source_template_purchase_id',
    'license_locked',
    'duplicate_count_used',
])]
class BotCommand extends Model
{
    use SoftDeletes;

    public const STATUSES = ['active', 'inactive'];
    public const TRIGGER_TYPES = ['slash', 'text', 'callback', 'direct_message'];
    public const DIRECT_MESSAGE_COMMAND_PREFIX = '__direct_message_handler_';

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'admin_only' => 'boolean',
            'aliases' => 'array',
            'last_used_at' => 'datetime',
            'last_error_at' => 'datetime',
            'execution_count' => 'integer',
            'error_count' => 'integer',
            'license_locked' => 'boolean',
            'duplicate_count_used' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function effectiveTriggerType(): string
    {
        if (in_array($this->trigger_type, self::TRIGGER_TYPES, true)) {
            return $this->trigger_type;
        }

        return str_starts_with((string) $this->command_name, '/') ? 'slash' : 'text';
    }

    public function displayName(): string
    {
        if (filled($this->display_name)) {
            return (string) $this->display_name;
        }

        return $this->effectiveTriggerType() === 'direct_message'
            ? 'Direct Message Handler'
            : (string) $this->command_name;
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BotCommandLog::class);
    }

}
