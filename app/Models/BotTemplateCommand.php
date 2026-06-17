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
}
