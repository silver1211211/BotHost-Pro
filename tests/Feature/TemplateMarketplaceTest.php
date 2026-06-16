<?php

use App\Models\Bot;
use App\Models\BotTemplate;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Http;

function marketplaceUser(array $attributes = []): User
{
    return User::factory()->create(array_merge(['role' => 'user', 'status' => 'active'], $attributes));
}

function marketplaceBot(User $user): Bot
{
    return Bot::create([
        'user_id' => $user->id,
        'name' => 'Market Bot',
        'slug' => 'market-bot-'.str()->random(8),
        'token_encrypted' => '123456:AA-secret-token',
        'status' => 'running',
        'language' => 'javascript',
        'setup_type' => 'custom',
    ]);
}

function marketplaceTemplate(array $attributes = []): BotTemplate
{
    $template = BotTemplate::create(array_merge([
        'name' => 'Paid Starter',
        'slug' => 'paid-starter-'.str()->random(8),
        'status' => 'published',
        'marketplace_status' => 'listed',
        'access_type' => 'paid',
        'price' => '5.00',
        'currency' => 'USD',
        'level' => 'beginner',
        'published_at' => now(),
    ], $attributes));

    $template->commands()->create([
        'command_name' => '/start',
        'response_text' => 'Paid welcome',
        'status' => 'active',
        'runtime' => 'node',
        'language' => 'javascript',
    ]);
    $template->forceFill(['commands_count' => 1])->save();

    return $template;
}

it('blocks direct import of locked paid templates and allows free imports', function (): void {
    $user = marketplaceUser();
    $bot = marketplaceBot($user);
    $paid = marketplaceTemplate();
    $free = marketplaceTemplate(['access_type' => 'free', 'price' => 0, 'slug' => 'free-'.str()->random(8)]);

    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $paid]))
        ->assertRedirect()
        ->assertSessionHasErrors(['template' => 'Please purchase this template before importing.']);

    $this->actingAs($user)
        ->post(route('dashboard.templates.unlock-free', $free))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $free]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($bot->commands()->where('command_name', '/start')->exists())->toBeTrue();
});

it('creates crypto invoices and unlocks paid templates after OxaPay confirms payment', function (): void {
    config(['oxapay.merchant_api_key' => 'test-key']);
    Http::fake([
        'api.oxapay.com/v1/payment/invoice' => Http::response([
            'track_id' => 'track-123',
            'payment_url' => 'https://pay.example/invoice',
            'address' => 'wallet-address',
            'status' => 'waiting',
        ]),
        'api.oxapay.com/v1/payment/track-123*' => Http::response([
            'track_id' => 'track-123',
            'status' => 'paid',
        ]),
    ]);

    $user = marketplaceUser(['wallet_balance' => '10.00', 'wallet_currency' => 'USD']);
    $template = marketplaceTemplate();

    // Step 1: Create local invoice record (no OxaPay call yet)
    $this->actingAs($user)
        ->post(route('dashboard.templates.crypto-invoice', $template), [
            'payment_method' => 'crypto',
            'pay_currency' => 'USDT_TRC20',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $paymentInvoice = $user->paymentInvoices()->where('type', 'template_purchase')->firstOrFail();

    // Template still locked (no purchase yet)
    $bot = marketplaceBot($user);
    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $template]))
        ->assertRedirect()
        ->assertSessionHasErrors('template');

    // Step 2: Generate OxaPay invoice
    $this->actingAs($user)
        ->post(route('dashboard.payments.generate', $paymentInvoice), ['pay_currency' => 'USDT_TRC20'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // Step 3: Check payment status
    $this->actingAs($user)
        ->post(route('dashboard.payments.check', $paymentInvoice))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $user->refresh();
    $template->refresh();

    expect((float) $user->wallet_balance)->toBe(10.0)
        ->and($user->templatePurchases()->where('bot_template_id', $template->id)->count())->toBe(1)
        ->and($template->sales_count)->toBe(1)
        ->and((float) $template->revenue_total)->toBe(5.0);
});

it('shows safe error when crypto invoices cannot be created', function (): void {
    config(['oxapay.merchant_api_key' => null]);
    $user = marketplaceUser(['wallet_balance' => '1.00']);
    $template = marketplaceTemplate();

    // Step 1: Create local invoice record (succeeds even without API key)
    $this->actingAs($user)
        ->post(route('dashboard.templates.crypto-invoice', $template), [
            'payment_method' => 'crypto',
            'pay_currency' => 'USDT_TRC20',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $paymentInvoice = $user->paymentInvoices()->where('type', 'template_purchase')->firstOrFail();

    // Step 2: Generate OxaPay invoice fails because API key is not configured
    $this->actingAs($user)
        ->post(route('dashboard.payments.generate', $paymentInvoice), ['pay_currency' => 'USDT_TRC20'])
        ->assertRedirect()
        ->assertSessionHasErrors('invoice');

    expect((float) $user->fresh()->wallet_balance)->toBe(1.0)
        ->and($user->templatePurchases()->count())->toBe(0);
});

it('marketplace purchase button posts directly to crypto invoice creation', function (): void {
    $user = marketplaceUser();
    $template = marketplaceTemplate(['name' => 'Card Purchase Template']);

    $this->actingAs($user)
        ->get(route('dashboard.templates.index'))
        ->assertOk()
        ->assertSee(route('dashboard.templates.crypto-invoice', $template), false);

    $this->actingAs($user)
        ->post(route('dashboard.templates.crypto-invoice', $template), [
            'payment_method' => 'crypto',
            'pay_currency' => 'USDT_TRC20',
        ])
        ->assertRedirect();

    expect($user->templatePaymentInvoices()->where('bot_template_id', $template->id)->exists())->toBeTrue()
        ->and($user->templatePurchases()->where('bot_template_id', $template->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->get(route('dashboard.templates.index'))
        ->assertOk()
        ->assertSee('Continue Payment');
});

it('uses about text on cards and safely formats details text', function (): void {
    $user = marketplaceUser();
    $template = marketplaceTemplate([
        'name' => 'Formatted Template',
        'short_description' => 'Build **fast** flows.',
        'description' => "First **paragraph**.\n\nSecond paragraph <script>alert(1)</script>.",
        'demo_url' => 'https://t.me/demo_bot',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.templates.index'))
        ->assertOk()
        ->assertSee('<strong>fast</strong>', false)
        ->assertSee('View Demo Bot')
        ->assertSee('https://t.me/demo_bot', false)
        ->assertDontSee('Second paragraph');

    $this->actingAs($user)
        ->get(route('dashboard.templates.show', $template))
        ->assertOk()
        ->assertSee('<strong>paragraph</strong>', false)
        ->assertSee('<p>First <strong>paragraph</strong>.</p>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('View Demo Bot')
        ->assertDontSee('Import into Bot');
});

it('allows admins to credit user wallets for testing', function (): void {
    $admin = marketplaceUser(['role' => 'admin']);
    $user = marketplaceUser(['wallet_balance' => '0.00']);

    $this->actingAs($admin)
        ->post(route('admin.users.wallet.credit', $user), [
            'amount' => '10.00',
            'description' => 'Test credit',
        ])
        ->assertRedirect();

    expect((float) $user->fresh()->wallet_balance)->toBe(10.0)
        ->and(WalletTransaction::query()->where('user_id', $user->id)->where('type', 'credit')->exists())->toBeTrue();
});

it('does not expose template command names on marketplace details', function (): void {
    $user = marketplaceUser();
    $template = marketplaceTemplate([
        'name' => 'Private Logic Template',
        'description' => 'This template has private commands.',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.templates.show', $template))
        ->assertOk()
        ->assertSee('Private Logic Template')
        ->assertSee('1 commands')
        ->assertDontSee('/start')
        ->assertDontSee('Paid welcome');
});

it('unlocks paid templates from OxaPay webhook idempotently', function (): void {
    config(['oxapay.merchant_api_key' => 'test-key']);
    $user = marketplaceUser();
    $template = marketplaceTemplate();
    $invoice = $user->templatePaymentInvoices()->create([
        'bot_template_id' => $template->id,
        'order_id' => 'tpl_'.$template->id.'_user_'.$user->id.'_abc',
        'track_id' => 'track-webhook',
        'amount' => '5.00',
        'currency' => 'USD',
        'pay_currency' => 'USDT_TRC20',
        'status' => 'waiting',
    ]);

    $payload = ['track_id' => 'track-webhook', 'order_id' => $invoice->order_id, 'status' => 'paid'];
    $content = json_encode($payload);
    $headers = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_HMAC' => hash_hmac('sha512', $content, 'test-key'),
    ];

    $this->call('POST', route('webhooks.oxapay'), [], [], [], $headers, $content)->assertOk()->assertSee('ok', false);
    $this->call('POST', route('webhooks.oxapay'), [], [], [], $headers, $content)->assertOk()->assertSee('ok', false);

    expect($user->templatePurchases()->where('bot_template_id', $template->id)->count())->toBe(1)
        ->and($invoice->fresh()->status)->toBe('paid')
        ->and($template->fresh()->sales_count)->toBe(1);
});
