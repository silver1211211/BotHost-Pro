<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'admin_user_id',
    'action',
    'status',
    'summary',
    'payload_json',
    'error_message',
    'started_at',
    'finished_at',
])]
class AdminRuntimeActionLog extends Model
{
    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
