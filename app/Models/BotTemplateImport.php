<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'bot_id',
    'bot_template_id',
    'user_id',
    'status',
    'imported_commands_count',
    'skipped_commands_count',
    'conflict_strategy',
    'summary',
])]
class BotTemplateImport extends Model
{
    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'imported_commands_count' => 'integer',
            'skipped_commands_count' => 'integer',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BotTemplate::class, 'bot_template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
