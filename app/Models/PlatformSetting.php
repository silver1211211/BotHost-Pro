<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

#[Fillable(['key', 'value', 'is_encrypted'])]
class PlatformSetting extends Model
{
    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        if ($setting->is_encrypted && filled($setting->value)) {
            return Crypt::decryptString($setting->value);
        }

        return $setting->value ?? $default;
    }

    public static function setValue(string $key, mixed $value, bool $encrypted = false): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $encrypted && filled($value) ? Crypt::encryptString((string) $value) : $value,
                'is_encrypted' => $encrypted,
            ],
        );
    }

    public static function maskedValue(string $key): ?string
    {
        $value = (string) static::getValue($key, '');

        if ($value === '') {
            return null;
        }

        return '****'.substr($value, -4);
    }
}
