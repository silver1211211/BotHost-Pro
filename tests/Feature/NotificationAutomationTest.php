<?php

use App\Models\Bot;
use App\Models\PaymentInvoice;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Http;

it('rings dashboard notification bell until notifications page is opened', function (): void {
    $user = User::factory()->create([
        'role' => 'user',
        'status' => 'active',
    ]);

    UserNotification::create([
        'user_id' => $user->id,
        'title' => 'New alert',
        'message' => 'Something happened.',
        'type' => 'system',
        'priority' => 'normal',
        'status' => 'unread',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-notification-bell="ringing"', false);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk();

    expect(UserNotification::first()->status)->toBe('read')
        ->and(UserNotification::first()->read_at)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-notification-bell="idle"', false);
});

it('saves active automation toggles without disabling hidden legacy settings', function (): void {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    PlatformSetting::setValue('automation_expire_invoices_enabled', '1');

    $this->actingAs($admin)
        ->post(route('admin.settings.automations.save'), [
            'automation_process_broadcasts_enabled' => '1',
            'automation_reconnect_webhooks_enabled' => '1',
            'automation_prune_logs_enabled' => '0',
        ])
        ->assertRedirect(route('admin.settings.index', ['tab' => 'automations']));

    expect(PlatformSetting::getValue('automation_process_broadcasts_enabled'))->toBe('1')
        ->and(PlatformSetting::getValue('automation_reconnect_webhooks_enabled'))->toBe('1')
        ->and(PlatformSetting::getValue('automation_webhook_health_check_enabled'))->toBe('1')
        ->and(PlatformSetting::getValue('automation_prune_logs_enabled'))->toBe('0')
        ->and(PlatformSetting::getValue('automation_expire_invoices_enabled'))->toBe('1');
});

it('reconnects a running bot telegram webhook when telegram reports a stale url', function (): void {
    config(['app.public_url' => 'https://public.example.com']);

    Http::fake([
        'https://api.telegram.org/*/getWebhookInfo' => Http::response([
            'ok' => true,
            'result' => ['url' => 'https://old.example.com/telegram/webhook/1/old'],
        ]),
        'https://api.telegram.org/*/setWebhook' => Http::response([
            'ok' => true,
            'result' => true,
        ]),
    ]);

    $owner = User::factory()->create([
        'role' => 'user',
        'status' => 'active',
    ]);

    $bot = Bot::create([
        'user_id' => $owner->id,
        'name' => 'Reconnect Bot',
        'slug' => 'reconnect-bot',
        'token_encrypted' => '123456:AA-reconnect-token',
        'status' => 'running',
        'language' => 'javascript',
        'setup_type' => 'custom',
        'token_verified_at' => now(),
        'webhook_secret' => 'secret-value',
        'webhook_status' => 'failed',
        'webhook_url' => 'https://old.example.com/telegram/webhook/1/old',
    ]);

    $this->artisan('webhooks:reconnect-telegram')
        ->assertExitCode(0);

    $bot->refresh();

    expect($bot->webhook_status)->toBe('active')
        ->and($bot->webhook_url)->toBe('https://public.example.com/telegram/webhook/'.$bot->id.'/secret-value')
        ->and($bot->webhook_last_error)->toBeNull();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/getWebhookInfo'));
    Http::assertSent(fn ($request) => str_contains($request->url(), '/setWebhook')
        && $request['url'] === 'https://public.example.com/telegram/webhook/'.$bot->id.'/secret-value');
});

it('reset all telegram webhooks saves public url, starts verified bots, and sets fresh endpoints', function (): void {
    config(['app.public_url' => 'http://127.0.0.1:8000']);

    Http::fake([
        'https://api.telegram.org/*/setWebhook' => Http::response([
            'ok' => true,
            'result' => true,
        ]),
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create([
        'role' => 'user',
        'status' => 'active',
    ]);

    $bot = Bot::create([
        'user_id' => $owner->id,
        'name' => 'Cloudflare Bot',
        'slug' => 'cloudflare-bot',
        'token_encrypted' => '123456:AA-cloudflare-token',
        'status' => 'stopped',
        'language' => 'javascript',
        'setup_type' => 'custom',
        'token_verified_at' => now(),
        'webhook_secret' => 'cloudflare-secret',
        'webhook_status' => 'not_set',
    ]);

    $publicUrl = 'https://circus-mineral-ancient-yield.trycloudflare.com';

    $this->actingAs($admin)
        ->post(route('admin.settings.maintenance.reset-webhooks'), [
            'app_public_url' => $publicUrl,
        ])
        ->assertRedirect(route('admin.settings.index', ['tab' => 'webhooks']))
        ->assertSessionHas('status');

    $bot->refresh();

    expect(PlatformSetting::getValue('app_public_url'))->toBe($publicUrl)
        ->and($bot->status)->toBe('running')
        ->and($bot->webhook_status)->toBe('active')
        ->and($bot->webhook_url)->toBe($publicUrl.'/telegram/webhook/'.$bot->id.'/cloudflare-secret')
        ->and($bot->webhook_last_error)->toBeNull();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/setWebhook')
        && $request['url'] === $publicUrl.'/telegram/webhook/'.$bot->id.'/cloudflare-secret');
});

it('uses one oxapay callback route to track subscription and template-style payment invoices', function (): void {
    PlatformSetting::setValue('oxapay_merchant_api_key', 'test-key', true);

    $user = User::factory()->create([
        'role' => 'user',
        'status' => 'active',
        'subscription_plan' => 'free',
    ]);

    $plan = SubscriptionPlan::create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price' => '15.00',
        'currency' => 'USD',
        'billing_period' => 'monthly',
        'status' => 'active',
        'features' => [],
        'limits' => [],
        'sort_order' => 2,
    ]);

    $subscriptionInvoice = PaymentInvoice::create([
        'user_id' => $user->id,
        'type' => PaymentInvoice::TYPE_SUBSCRIPTION_UPGRADE,
        'reference_type' => SubscriptionPlan::class,
        'reference_id' => $plan->id,
        'provider' => 'oxapay',
        'track_id' => 'sub-track',
        'order_id' => 'sub-order',
        'amount' => '15.00',
        'currency' => 'USD',
        'pay_currency' => 'USDT_TRC20',
        'status' => 'waiting',
    ]);

    $templateInvoice = PaymentInvoice::create([
        'user_id' => $user->id,
        'type' => PaymentInvoice::TYPE_TEMPLATE_PURCHASE,
        'reference_type' => 'manual-test',
        'reference_id' => 999,
        'provider' => 'oxapay',
        'track_id' => 'tpl-track',
        'order_id' => 'tpl-order',
        'amount' => '5.00',
        'currency' => 'USD',
        'pay_currency' => 'USDT_TRC20',
        'status' => 'waiting',
    ]);

    foreach ([
        ['track_id' => 'sub-track', 'order_id' => 'sub-order', 'status' => 'paid'],
        ['track_id' => 'tpl-track', 'order_id' => 'tpl-order', 'status' => 'confirming'],
    ] as $payload) {
        $content = json_encode($payload);

        $this->call('POST', route('webhooks.oxapay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_HMAC' => hash_hmac('sha512', $content, 'test-key'),
        ], $content)->assertOk()->assertSee('ok', false);
    }

    expect($subscriptionInvoice->fresh()->status)->toBe('paid')
        ->and($subscriptionInvoice->fresh()->paid_at)->not->toBeNull()
        ->and($user->fresh()->subscription_plan)->toBe('pro')
        ->and($templateInvoice->fresh()->status)->toBe('confirming')
        ->and($templateInvoice->fresh()->paid_at)->toBeNull();
});
