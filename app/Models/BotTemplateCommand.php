<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'bot_template_id',
    'command_name',
    'trigger_type',
    'description',
    'code',
    'response_text',
    'aliases',
    'folder',
    'status',
    'runtime',
    'language',
    'sort_order',
    'metadata',
])]
class BotTemplateCommand extends Model
{
    public const STATUSES = ['active', 'paused', 'disabled'];

    public const DIRECT_MESSAGE_LABEL = 'Direct Message Handler';

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'metadata' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BotTemplate::class, 'bot_template_id');
    }

    public function effectiveTriggerType(): ?string
    {
        if ($this->trigger_type === 'direct_message' || self::isDirectMessageMarker($this->command_name)) {
            return 'direct_message';
        }

        return $this->trigger_type;
    }

    public function isDirectMessageHandler(): bool
    {
        return $this->effectiveTriggerType() === 'direct_message';
    }

    public function displayName(): string
    {
        return $this->isDirectMessageHandler()
            ? self::DIRECT_MESSAGE_LABEL
            : (string) $this->command_name;
    }

    public static function isDirectMessageMarker(?string $value): bool
    {
        $value = (string) $value;

        if ($value === '') {
            return false;
        }

        if (str_starts_with($value, \App\Models\BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX)) {
            return true;
        }

        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '', $value) ?? '');

        return str_starts_with($normalized, 'directmessagehandler');
    }
}
