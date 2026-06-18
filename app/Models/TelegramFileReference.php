<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'bot_id',
    'file_hash',
    'file_id',
    'file_unique_id',
    'file_path',
    'file_size',
    'last_accessed_at',
])]
class TelegramFileReference extends Model
{
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
