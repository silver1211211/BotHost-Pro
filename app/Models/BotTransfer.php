<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotTransfer extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'receiver_email',
        'source_bot_id',
        'bot_name',
        'payload',
        'status',
        'note',
        'imported_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
            'expires_at'  => 'datetime',
        ];
    }

    public const STATUSES = ['pending', 'imported', 'cancelled', 'expired'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function sourceBot(): BelongsTo
    {
        return $this->belongsTo(Bot::class, 'source_bot_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isImported(): bool
    {
        return $this->status === 'imported';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function decodedPayload(): array
    {
        return json_decode($this->payload, true) ?? [];
    }
}
