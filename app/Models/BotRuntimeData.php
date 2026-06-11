<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Throwable;

#[Fillable(['bot_id', 'key', 'value'])]
class BotRuntimeData extends Model
{
    protected $table = 'bot_runtime_data';

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => self::decryptValue($value),
            set: fn (mixed $value) => Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR)),
        );
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    private static function decryptValue(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($value), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }
    }
}
