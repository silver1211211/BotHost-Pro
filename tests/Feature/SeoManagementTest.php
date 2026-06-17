<?php

use App\Models\BotTemplate;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Support\Facades\Storage;

function seoAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
}

it('renders default SEO on the public homepage', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<title>BotHost Pro - Build, Host &amp; Sell Telegram Bots Online</title>', false)
        ->assertSee('<meta name="description" content="Create, host, manage, and monetize Telegram bots with BotHost Pro.', false)
        ->assertSee('<meta property="og:title" content="BotHost Pro - Build, Host &amp; Sell Telegram Bots Online">', false);
});

it('allows an admin to update SEO settings', function () {
    $admin = seoAdmin();

    $this->actingAs($admin)
        ->post(route('admin.settings.seo.save'), [
            'page_key' => 'home',
            'title' => 'Custom BotHost SEO Title',
            'meta_description' => 'Custom search description for the BotHost Pro homepage.',
            'meta_keywords' => 'custom, telegram bots',
            'og_title' => 'Custom OG Title',
            'og_description' => 'Custom Open Graph description.',
            'og_image' => 'https://example.com/og.png',
            'twitter_title' => 'Custom Twitter Title',
            'twitter_description' => 'Custom Twitter description.',
            'canonical_url' => 'https://example.com/',
            'robots' => 'index,nofollow',
        ])
        ->assertRedirect(route('admin.settings.index', ['tab' => 'seo']));

    expect(PlatformSetting::getValue(Seo::settingKey('home', 'title')))->toBe('Custom BotHost SEO Title');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<title>Custom BotHost SEO Title</title>', false)
        ->assertSee('<meta name="robots" content="index,nofollow">', false)
        ->assertSee('<link rel="canonical" href="https://example.com/">', false)
        ->assertSee('<meta property="og:image" content="https://example.com/og.png">', false);
});

it('falls back to default SEO when settings are missing', function () {
    PlatformSetting::setValue(Seo::settingKey('home', 'title'), 'Temporary title');
    PlatformSetting::query()->where('key', Seo::settingKey('home', 'title'))->delete();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<title>BotHost Pro - Build, Host &amp; Sell Telegram Bots Online</title>', false);
});

it('resets SEO settings to defaults', function () {
    $admin = seoAdmin();
    PlatformSetting::setValue(Seo::settingKey('home', 'title'), 'Temporary title');

    $this->actingAs($admin)
        ->post(route('admin.settings.seo.reset'), ['page_key' => 'home'])
        ->assertRedirect(route('admin.settings.index', ['tab' => 'seo']));

    expect(PlatformSetting::getValue(Seo::settingKey('home', 'title')))->toBeNull();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<title>BotHost Pro - Build, Host &amp; Sell Telegram Bots Online</title>', false);
});

it('escapes SEO output safely', function () {
    PlatformSetting::setValue(Seo::settingKey('home', 'title'), 'Bad " <script>alert(1)</script>');
    PlatformSetting::setValue(Seo::settingKey('home', 'meta_description'), 'Description with "quotes" and <script>bad()</script>');

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<script>bad()</script>', false)
        ->assertSee('Bad &quot; &lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('serves robots txt with private areas disallowed', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /admin', false)
        ->assertSee('Disallow: /dashboard', false)
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
});

it('serves sitemap xml without private routes', function () {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(route('home'), false)
        ->assertSee(route('login'), false)
        ->assertSee(route('register'), false)
        ->assertSee(route('legal.terms'), false)
        ->assertSee(route('legal.privacy'), false)
        ->assertDontSee('/admin', false)
        ->assertDontSee('/dashboard', false);
});

it('renders marketplace SEO tags for the templates page', function () {
    $user = User::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->get(route('dashboard.templates.index'))
        ->assertOk()
        ->assertSee('<title>Bot Templates Marketplace - BotHost Pro</title>', false)
        ->assertSee('<meta name="description" content="Browse free and paid Telegram bot templates', false)
        ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
});

it('uses dynamic template detail SEO when template content is available', function () {
    Storage::fake('public');

    $user = User::factory()->create(['status' => 'active']);
    $template = BotTemplate::query()->create([
        'name' => 'Referral Booster Bot',
        'slug' => 'referral-booster-bot',
        'short_description' => 'Referral automation with captcha, rewards, and proof posting.',
        'description' => 'Long fallback description.',
        'category' => 'referral_bot',
        'level' => 'beginner',
        'status' => 'published',
        'marketplace_status' => 'listed',
        'access_type' => 'free',
        'price' => 0,
        'currency' => 'USD',
        'commands_count' => 5,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.templates.show', $template))
        ->assertOk()
        ->assertSee('<title>Referral Booster Bot - BotHost Pro</title>', false)
        ->assertSee('<meta name="description" content="Referral automation with captcha, rewards, and proof posting.">', false)
        ->assertSee('<meta property="og:title" content="Referral Booster Bot - BotHost Pro">', false);
});
