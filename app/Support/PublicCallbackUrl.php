<?php

namespace App\Support;

use App\Models\PlatformSetting;

class PublicCallbackUrl
{
    public static function base(): string
    {
        return self::normalize((string) (
            PlatformSetting::getValue('app_public_url')
            ?: self::envValue('APP_PUBLIC_URL')
            ?: self::envValue('APP_URL')
            ?: config('app.public_url')
        ));
    }

    public static function to(string $path): string
    {
        return self::base().'/'.ltrim($path, '/');
    }

    public static function isPublicHttps(?string $url = null): bool
    {
        $url = self::normalize($url ?? self::base());
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        return $scheme === 'https'
            && filled($host)
            && ! in_array(strtolower((string) $host), ['localhost', '127.0.0.1'], true);
    }

    public static function normalize(?string $url): string
    {
        $url = rtrim(trim((string) $url), '/');
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($scheme === 'http' && $host !== '' && ! in_array($host, ['localhost', '127.0.0.1'], true)) {
            return 'https://'.substr($url, strlen('http://'));
        }

        return $url;
    }

    private static function envValue(string $key): ?string
    {
        if (app()->runningUnitTests()) {
            return null;
        }

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
