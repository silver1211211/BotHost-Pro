<?php

namespace App\Models;

use App\Support\PublicCallbackUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['bot_id', 'auto_restart', 'ram_limit', 'cpu_limit', 'timezone', 'user_webhook_enabled', 'user_webhook_secret', 'custom_webhook_url', 'custom_webhook_events'])]
class BotSetting extends Model
{
    protected function casts(): array
    {
        return [
            'auto_restart'          => 'boolean',
            'cpu_limit'             => 'decimal:1',
            'user_webhook_enabled'  => 'boolean',
            'custom_webhook_events' => 'array',
        ];
    }

    public function userWebhookEndpoint(): ?string
    {
        if (! $this->user_webhook_secret) {
            return null;
        }

        return PublicCallbackUrl::to('/webhooks/bot/'.$this->bot_id.'/'.$this->user_webhook_secret);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
