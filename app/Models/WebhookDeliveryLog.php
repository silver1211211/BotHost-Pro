<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDeliveryLog extends Model
{
    protected $fillable = [
        'user_id',
        'bot_id',
        'event',
        'direction',
        'url',
        'status',
        'http_status',
        'response_body',
        'error_message',
        'attempts',
        'duration_ms',
        'payload',
        'headers',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'payload'     => 'array',
            'headers'     => 'array',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
            'attempts'    => 'integer',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
