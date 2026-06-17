<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class Seo
{
    public const ROBOTS = [
        'index,follow',
        'index,nofollow',
        'noindex,follow',
        'noindex,nofollow',
    ];

    public const FIELDS = [
        'title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'canonical_url',
        'robots',
    ];

    public static function defaults(): array
    {
        return [
            'home' => [
                'name' => 'Homepage',
                'route_name' => 'home',
                'path' => '/',
                'title' => 'BotHost Pro - Build, Host & Sell Telegram Bots Online',
                'meta_description' => 'Create, host, manage, and monetize Telegram bots with BotHost Pro. Launch support bots, referral bots, earning bots, and automation tools from one workspace.',
                'meta_keywords' => 'Telegram bot builder, host Telegram bot, BotHost Pro, Telegram automation, no code bot platform, bot marketplace',
                'robots' => 'index,follow',
            ],
            'login' => [
                'name' => 'Login page',
                'route_name' => 'login',
                'path' => '/login',
                'title' => 'Login to BotHost Pro',
                'meta_description' => 'Access your BotHost Pro workspace to manage bots, templates, support flows, automations, and your bot hosting dashboard.',
                'meta_keywords' => 'BotHost Pro login, Telegram bot dashboard, bot hosting account',
                'robots' => 'index,follow',
            ],
            'register' => [
                'name' => 'Register page',
                'route_name' => 'register',
                'path' => '/register',
                'title' => 'Create Your BotHost Pro Account',
                'meta_description' => 'Join BotHost Pro and start building Telegram bots with templates, admin tools, hosting, and automation features.',
                'meta_keywords' => 'create BotHost Pro account, Telegram bot builder signup, bot hosting registration',
                'robots' => 'index,follow',
            ],
            'admin_login' => [
                'name' => 'Admin login',
                'route_name' => 'admin.login',
                'path' => '/admin/login',
                'title' => 'BotHost Pro Admin Login',
                'meta_description' => 'Secure administrator access for BotHost Pro platform management.',
                'meta_keywords' => 'BotHost Pro admin login',
                'robots' => 'noindex,nofollow',
            ],
            'pricing' => [
                'name' => 'Pricing page',
                'route_name' => 'dashboard.upgrade',
                'path' => '/dashboard/upgrade',
                'title' => 'BotHost Pro Pricing - Choose Your Bot Hosting Plan',
                'meta_description' => 'Compare BotHost Pro plans for Telegram bot hosting, custom webhooks, premium templates, and automation tools.',
                'meta_keywords' => 'BotHost Pro pricing, Telegram bot hosting plans, premium bot templates',
                'robots' => 'noindex,nofollow',
            ],
            'marketplace' => [
                'name' => 'Marketplace / Templates',
                'route_name' => 'dashboard.templates.index',
                'path' => '/dashboard/templates',
                'title' => 'Bot Templates Marketplace - BotHost Pro',
                'meta_description' => 'Browse free and paid Telegram bot templates for support bots, referral bots, earning bots, FaucetPay bots, and business automation.',
                'meta_keywords' => 'Telegram bot templates, bot template marketplace, referral bot template, FaucetPay bot',
                'robots' => 'noindex,nofollow',
            ],
            'template_details' => [
                'name' => 'Template details',
                'route_name' => 'dashboard.templates.show',
                'path' => '/dashboard/templates/{template}',
                'title' => 'Telegram Bot Template Details - BotHost Pro',
                'meta_description' => 'View Telegram bot template details, features, requirements, and purchase or unlock options inside BotHost Pro.',
                'meta_keywords' => 'Telegram bot template details, BotHost Pro templates',
                'robots' => 'noindex,nofollow',
            ],
            'terms' => [
                'name' => 'Terms page',
                'route_name' => 'legal.terms',
                'path' => '/terms',
                'title' => 'Terms of Service - BotHost Pro',
                'meta_description' => 'Read the BotHost Pro terms of service for using our Telegram bot hosting, templates, marketplace, and automation platform.',
                'meta_keywords' => 'BotHost Pro terms, Telegram bot hosting terms, bot marketplace terms',
                'robots' => 'index,follow',
            ],
            'privacy' => [
                'name' => 'Privacy page',
                'route_name' => 'legal.privacy',
                'path' => '/privacy-policy',
                'title' => 'Privacy Policy - BotHost Pro',
                'meta_description' => 'Learn how BotHost Pro handles account data, bot workspace data, templates, and platform security.',
                'meta_keywords' => 'BotHost Pro privacy, bot hosting privacy policy, Telegram bot data security',
                'robots' => 'index,follow',
            ],
            'support' => [
                'name' => 'Support page',
                'route_name' => 'support.index',
                'path' => '/support',
                'title' => 'BotHost Pro Support',
                'meta_description' => 'Get help with BotHost Pro bot hosting, templates, Telegram automation, workspace setup, and account support.',
                'meta_keywords' => 'BotHost Pro support, Telegram bot hosting help, bot workspace support',
                'robots' => 'noindex,nofollow',
            ],
            'cookies' => [
                'name' => 'Cookie policy',
                'route_name' => 'legal.cookies',
                'path' => '/cookie-policy',
                'title' => 'Cookie Policy - BotHost Pro',
                'meta_description' => 'Read how BotHost Pro uses cookies and similar technologies across its Telegram bot hosting platform.',
                'meta_keywords' => 'BotHost Pro cookie policy, bot hosting cookies',
                'robots' => 'index,follow',
            ],
            'refunds' => [
                'name' => 'Refund policy',
                'route_name' => 'legal.refunds',
                'path' => '/refund-policy',
                'title' => 'Refund Policy - BotHost Pro',
                'meta_description' => 'Review BotHost Pro refund terms for plans, paid templates, and Telegram bot hosting services.',
                'meta_keywords' => 'BotHost Pro refund policy, template refund policy',
                'robots' => 'index,follow',
            ],
            'acceptable_use' => [
                'name' => 'Acceptable use',
                'route_name' => 'legal.acceptable-use',
                'path' => '/acceptable-use',
                'title' => 'Acceptable Use Policy - BotHost Pro',
                'meta_description' => 'Understand acceptable use rules for BotHost Pro bot hosting, templates, automations, and workspaces.',
                'meta_keywords' => 'BotHost Pro acceptable use, bot hosting rules',
                'robots' => 'index,follow',
            ],
        ];
    }

    public static function page(string $key, array $overrides = []): array
    {
        $default = self::defaults()[$key] ?? self::defaults()['home'];
        $page = $default;

        foreach (self::FIELDS as $field) {
            $stored = PlatformSetting::getValue(self::settingKey($key, $field), null);
            if ($stored !== null && trim((string) $stored) !== '') {
                $page[$field] = (string) $stored;
            }
        }

        $page = array_merge($page, $overrides);
        $page['og_title'] = filled($page['og_title'] ?? null) ? $page['og_title'] : $page['title'];
        $page['og_description'] = filled($page['og_description'] ?? null) ? $page['og_description'] : $page['meta_description'];
        $page['twitter_title'] = filled($page['twitter_title'] ?? null) ? $page['twitter_title'] : $page['title'];
        $page['twitter_description'] = filled($page['twitter_description'] ?? null) ? $page['twitter_description'] : $page['meta_description'];
        $page['robots'] = in_array($page['robots'] ?? '', self::ROBOTS, true) ? $page['robots'] : 'index,follow';
        $page['canonical_url'] = filled($page['canonical_url'] ?? null) ? $page['canonical_url'] : '';
        $page['url'] = filled($page['canonical_url']) ? $page['canonical_url'] : URL::current();
        $page['og_type'] = $page['og_type'] ?? 'website';

        return $page;
    }

    public static function pages(): array
    {
        return collect(self::defaults())
            ->map(fn (array $page, string $key) => self::page($key) + ['key' => $key])
            ->all();
    }

    public static function editablePages(): array
    {
        return collect(self::pages())
            ->filter(fn (array $page) => self::routeExists($page['route_name'] ?? null))
            ->all();
    }

    public static function save(string $key, array $data): void
    {
        foreach (self::FIELDS as $field) {
            PlatformSetting::setValue(self::settingKey($key, $field), $data[$field] ?? '');
        }
    }

    public static function reset(string $key): void
    {
        PlatformSetting::query()
            ->where('key', 'like', 'seo.'.$key.'.%')
            ->delete();
    }

    public static function keyForRoute(?string $fallback = null): ?string
    {
        $routeName = request()->route()?->getName();

        foreach (self::defaults() as $key => $page) {
            if (($page['route_name'] ?? null) === $routeName) {
                return $key;
            }
        }

        return $fallback;
    }

    public static function sitemapUrls(): array
    {
        $publicKeys = ['home', 'login', 'register', 'terms', 'privacy', 'cookies', 'refunds', 'acceptable_use'];

        return collect($publicKeys)
            ->map(fn (string $key) => self::defaults()[$key] ?? null)
            ->filter(fn (?array $page) => $page && self::routeExists($page['route_name'] ?? null))
            ->map(fn (array $page) => [
                'loc' => route($page['route_name']),
                'lastmod' => now()->toDateString(),
                'changefreq' => $page['route_name'] === 'home' ? 'weekly' : 'monthly',
                'priority' => $page['route_name'] === 'home' ? '1.0' : '0.6',
            ])
            ->values()
            ->all();
    }

    public static function robotsTxt(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /api/internal',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ]);
    }

    public static function settingKey(string $pageKey, string $field): string
    {
        return 'seo.'.$pageKey.'.'.$field;
    }

    private static function routeExists(?string $routeName): bool
    {
        return filled($routeName) && Route::has($routeName);
    }
}
