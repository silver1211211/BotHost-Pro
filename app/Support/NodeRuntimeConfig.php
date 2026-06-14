<?php

namespace App\Support;

class NodeRuntimeConfig
{
    public static function secret(): string
    {
        return (string) (
            config('services.node_runtime.secret')
            ?: self::envValue('NODE_RUNTIME_SECRET')
            ?: ''
        );
    }

    public static function internalUrl(): string
    {
        return rtrim((string) (
            config('services.node_runtime.internal_url')
            ?: self::envValue('NODE_RUNTIME_INTERNAL_URL')
            ?: ''
        ), '/');
    }

    private static function envValue(string $key): ?string
    {
        $path = base_path('.env');

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = ltrim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_starts_with($line, $key.'=')) {
                continue;
            }

            $value = trim(substr($line, strlen($key) + 1));

            if ($value === '') {
                return null;
            }

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            return trim($value) ?: null;
        }

        return null;
    }
}
