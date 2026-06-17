<?php

use App\Models\Bot;
use App\Models\BotTemplate;
use App\Models\PaymentInvoice;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Jobs\RecheckBotTemplatePurchase;
use App\Services\BotTemplateImporter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

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
        ->assertSessionHasErrors(['template' => 'You must unlock or purchase this template before importing.']);

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

it('preserves template command trigger type and exact command names on workspace import', function (): void {
    $user = marketplaceUser();
    $bot = marketplaceBot($user);
    $template = marketplaceTemplate(['access_type' => 'free', 'price' => 0, 'slug' => 'identity-'.str()->random(8)]);
    $template->commands()->delete();
    $template->commands()->create([
        'command_name' => '/start',
        'trigger_type' => 'slash',
        'response_text' => 'Start',
        'status' => 'active',
        'runtime' => 'node',
        'language' => 'javascript',
    ]);
    $template->commands()->create([
        'command_name' => 'ðŸ’°  Balance',
        'trigger_type' => 'text',
        'response_text' => 'Balance',
        'status' => 'active',
        'runtime' => 'node',
        'language' => 'javascript',
    ]);
    $template->commands()->create([
        'command_name' => '__direct_message_handler_exact',
        'trigger_type' => 'direct_message',
        'code' => 'await reply("dm");',
        'status' => 'active',
        'runtime' => 'node',
        'language' => 'javascript',
    ]);

    $this->actingAs($user)
        ->post(route('dashboard.templates.unlock-free', $template))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $template]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($bot->commands()->where('command_name', '/start')->where('trigger_type', 'slash')->exists())->toBeTrue()
        ->and($bot->commands()->where('command_name', 'ðŸ’°  Balance')->where('trigger_type', 'text')->exists())->toBeTrue()
        ->and($bot->commands()->where('command_name', '__direct_message_handler_exact')->exists())->toBeFalse()
        ->and($bot->commands()->where('trigger_type', 'direct_message')->first()?->displayName())->toBe('Direct Message Handler')
        ->and($bot->commands()->where('command_name', 'ðŸ’° Balance')->exists())->toBeFalse();
});

it('shows only unlocked templates in bot workspace picker with formatted about text', function (): void {
    $user = marketplaceUser();
    $bot = marketplaceBot($user);
    $locked = marketplaceTemplate(['name' => 'Locked Picker Template', 'slug' => 'locked-picker-'.str()->random(8)]);
    $unlocked = marketplaceTemplate([
        'name' => 'Unlocked Picker Template',
        'slug' => 'unlocked-picker-'.str()->random(8),
        'short_description' => 'Import **bold** flows.',
    ]);
    $unlocked->purchases()->create([
        'user_id' => $user->id,
        'amount' => '5.00',
        'currency' => 'USD',
        'status' => 'completed',
        'purchased_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('bots.templates.index', $bot))
        ->assertOk()
        ->assertSee('Unlocked Picker Template')
        ->assertSee('<strong>bold</strong>', false)
        ->assertSee('Import into this bot')
        ->assertDontSee('Locked Picker Template')
        ->assertDontSee('**bold**');
});

it('workspace picker matches the completed purchased library instead of all visible templates', function (): void {
    $user = marketplaceUser(['role' => 'admin']);
    $bot = marketplaceBot($user);
    $owned = marketplaceTemplate([
        'name' => 'Owned Library Template',
        'slug' => 'owned-library-'.str()->random(8),
    ]);
    $visibleButUnowned = marketplaceTemplate([
        'name' => 'Visible But Not Owned Template',
        'slug' => 'visible-but-not-owned-'.str()->random(8),
    ]);

    $owned->purchases()->create([
        'user_id' => $user->id,
        'amount' => '5.00',
        'currency' => 'USD',
        'status' => 'completed',
        'purchased_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.templates.purchased'))
        ->assertOk()
        ->assertSee('Owned Library Template')
        ->assertDontSee('Visible But Not Owned Template');

    $this->actingAs($user)
        ->get(route('bots.templates.index', $bot))
        ->assertOk()
        ->assertSee('Owned Library Template')
        ->assertSee('Downloaded')
        ->assertDontSee('Visible But Not Owned Template');

    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $visibleButUnowned]), ['conflict_strategy' => 'skip'])
        ->assertRedirect()
        ->assertSessionHasErrors(['template' => 'You must unlock or purchase this template before importing.']);
});

it('does not show plan included templates in bot workspace picker until they are downloaded', function (): void {
    $user = marketplaceUser(['subscription_plan' => 'business']);
    $bot = marketplaceBot($user);
    $included = marketplaceTemplate([
        'name' => 'Included But Not Downloaded',
        'slug' => 'included-not-downloaded-'.str()->random(8),
        'access_type' => 'paid',
        'price' => '9.00',
        'included_plan' => 'business',
    ]);

    $this->actingAs($user)
        ->get(route('bots.templates.index', $bot))
        ->assertOk()
        ->assertDontSee('Included But Not Downloaded');

    $this->actingAs($user)
        ->post(route('dashboard.templates.unlock-free', $included))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('bots.templates.index', $bot))
        ->assertOk()
        ->assertSee('Included But Not Downloaded')
        ->assertSee('Downloaded');
});

it('queues purchase recheck and still blocks imports without completed library ownership', function (): void {
    Queue::fake();

    $user = marketplaceUser();
    $bot = marketplaceBot($user);
    $template = marketplaceTemplate(['name' => 'Recheck Locked Template']);

    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $template]))
        ->assertRedirect()
        ->assertSessionHasErrors(['template' => 'You must unlock or purchase this template before importing.']);

    Queue::assertPushed(RecheckBotTemplatePurchase::class, fn (RecheckBotTemplatePurchase $job) => $job->userId === $user->id
        && $job->templateId === $template->id);
});

it('core template importer rejects templates without completed ownership', function (): void {
    $user = marketplaceUser();
    $bot = marketplaceBot($user);
    $template = marketplaceTemplate(['name' => 'Service Locked Template']);

    app(BotTemplateImporter::class)->import($bot, $template, $user);
})->throws(ValidationException::class);

it('background purchase rechecker repairs paid invoice ownership before import', function (): void {
    config(['queue.default' => 'sync']);

    $user = marketplaceUser();
    $bot = marketplaceBot($user);
    $template = marketplaceTemplate(['name' => 'Paid Invoice Recheck Template']);

    PaymentInvoice::query()->create([
        'user_id' => $user->id,
        'type' => PaymentInvoice::TYPE_TEMPLATE_PURCHASE,
        'reference_type' => BotTemplate::class,
        'reference_id' => $template->id,
        'provider' => 'oxapay',
        'order_id' => 'paid-recheck-'.str()->random(8),
        'amount' => $template->price,
        'currency' => $template->currency,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $template]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($user->templatePurchases()->where('bot_template_id', $template->id)->where('status', 'completed')->exists())->toBeTrue()
        ->and($bot->commands()->where('command_name', '/start')->exists())->toBeTrue();
});

it('template import skips commands that conflict with recycle bin commands', function (): void {
    $user = marketplaceUser();
    $bot = marketplaceBot($user);
    $command = $bot->commands()->create([
        'command_name' => '/start',
        'trigger_type' => 'slash',
        'response_text' => 'Old',
        'response_type' => 'text',
        'status' => 'active',
    ]);
    $command->delete();

    $template = marketplaceTemplate(['access_type' => 'free', 'price' => 0, 'slug' => 'recycle-conflict-'.str()->random(8)]);

    $this->actingAs($user)
        ->post(route('dashboard.templates.unlock-free', $template))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $template]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($bot->commands()->where('command_name', '/start')->count())->toBe(0)
        ->and($bot->commands()->withTrashed()->where('command_name', '/start')->count())->toBe(1);
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

it('does not show purchased unlocked import controls on marketplace details', function (): void {
    $user = marketplaceUser();
    $template = marketplaceTemplate([
        'name' => 'Purchased Marketplace Template',
        'description' => 'This template is already owned.',
    ]);

    $template->purchases()->create([
        'user_id' => $user->id,
        'amount' => '5.00',
        'currency' => 'USD',
        'status' => 'completed',
        'purchased_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.templates.show', $template))
        ->assertOk()
        ->assertSee('Purchased Marketplace Template')
        ->assertDontSee('Purchased / Unlocked')
        ->assertDontSee('Unlocked')
        ->assertDontSee('Import into Bot')
        ->assertDontSee('silver test bot');
});

it('does not allow importing from the global purchased templates page', function (): void {
    $user = marketplaceUser();
    $bot = marketplaceBot($user);
    $template = marketplaceTemplate([
        'name' => 'Library Only Template',
        'description' => 'Purchased templates are details-only from the library.',
    ]);

    $template->purchases()->create([
        'user_id' => $user->id,
        'amount' => '5.00',
        'currency' => 'USD',
        'status' => 'completed',
        'purchased_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.templates.purchased'))
        ->assertOk()
        ->assertSee('Library Only Template')
        ->assertSee('View Details')
        ->assertDontSee('Import into Bot')
        ->assertDontSee(route('bots.templates.import', [$bot, $template]), false);
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
