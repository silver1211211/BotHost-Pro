<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'admin_broadcast_id',
    'created_by',
    'title',
    'message',
    'type',
    'priority',
    'starts_at',
    'ends_at',
    'is_active',
    'dismissible',
    'metadata',
])]
class PlatformAnnouncement extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'dismissible' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(AdminBroadcast::class, 'admin_broadcast_id');
    }
}
