<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class SafeTemplateText
{
    public static function visibleLength(?string $text): int
    {
        $visible = strip_tags((string) $text);
        $visible = str_replace('**', '', $visible);
        $visible = trim(preg_replace('/\s+/u', ' ', $visible) ?? $visible);

        return mb_strlen($visible);
    }

    public static function inline(?string $text): HtmlString
    {
        $html = self::bold(e((string) $text));

        return new HtmlString($html);
    }

    public static function paragraphs(?string $text): HtmlString
    {
        $text = trim((string) $text);

        if ($text === '') {
            return new HtmlString('');
        }

        $paragraphs = preg_split('/\R{2,}/', str_replace(["\r\n", "\r"], "\n", $text)) ?: [];
        $html = collect($paragraphs)
            ->map(fn (string $paragraph): string => '<p>'.nl2br(self::bold(e(trim($paragraph))), false).'</p>')
            ->implode('');

        return new HtmlString($html);
    }

    private static function bold(string $escaped): string
    {
        return preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped) ?? $escaped;
    }
}
