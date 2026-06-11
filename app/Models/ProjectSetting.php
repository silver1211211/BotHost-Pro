<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

#[Fillable(['project_id', 'auto_restart', 'ram_limit', 'cpu_limit', 'webhook_enabled', 'timezone', 'bot_token', 'admin_id', 'oxapay_api_key', 'external_apis'])]
class ProjectSetting extends Model
{
    protected function casts(): array
    {
        return [
            'auto_restart' => 'boolean',
            'webhook_enabled' => 'boolean',
            'cpu_limit' => 'decimal:1',
            'external_apis' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function botToken(): Attribute
    {
        return $this->encryptedStringAttribute();
    }

    protected function oxapayApiKey(): Attribute
    {
        return $this->encryptedStringAttribute();
    }

    private function encryptedStringAttribute(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }
}
