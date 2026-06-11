<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Storage;

class Branding
{
    public static function platformName(): string
    {
        $name = trim((string) PlatformSetting::getValue('platform_name', config('app.name', 'BotHost Pro')));

        return $name !== '' ? $name : 'BotHost Pro';
    }

    public static function footerText(): string
    {
        $text = trim((string) PlatformSetting::getValue('footer_text', ''));

        return $text !== '' ? $text : '© '.date('Y').' BotHost Pro. All rights reserved.';
    }

    public static function platformLogoUrl(): ?string
    {
        return self::publicUrl((string) PlatformSetting::getValue('platform_logo_path', ''));
    }

    public static function adminLogoUrl(): ?string
    {
        return self::publicUrl((string) PlatformSetting::getValue('admin_logo_path', ''));
    }

    public static function faviconUrl(): ?string
    {
        return self::publicUrl((string) PlatformSetting::getValue('favicon_path', ''));
    }

    public static function assets(): array
    {
        return [
            'platform_name' => self::platformName(),
            'footer_text' => self::footerText(),
            'platform_logo_url' => self::platformLogoUrl(),
            'admin_logo_url' => self::adminLogoUrl(),
            'favicon_url' => self::faviconUrl(),
        ];
    }

    private static function publicUrl(string $path): ?string
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}

