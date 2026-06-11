<?php

namespace App\Services;

use App\Models\PlatformSetting;

class PlatformSettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return PlatformSetting::getValue($key, $default);
    }

    public function set(string $key, mixed $value, bool $encrypted = false): void
    {
        PlatformSetting::setValue($key, $value, $encrypted);
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $val = PlatformSetting::getValue($key);

        if ($val === null) {
            return $default;
        }

        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    public function masked(string $key): ?string
    {
        return PlatformSetting::maskedValue($key);
    }

    public function integer(string $key, int $default = 0): int
    {
        $val = PlatformSetting::getValue($key);

        return $val !== null ? (int) $val : $default;
    }
}
