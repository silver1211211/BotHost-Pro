<?php

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotRuntimeData;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

function oxapayRuntimeBot(array $attributes = []): Bot
{
    $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

    return Bot::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Oxa Runtime Bot',
        'slug' => 'oxa-runtime-'.str()->random(8),
        'token_encrypted' => '123456:AA-runtime-token-'.str()->random(12),
        'status' => 'running',
        'language' => 'javascript',
        'setup_type' => 'custom',
    ], $attributes));
}

function setBotRuntime(Bot $bot, string $key, string $value): void
{
    BotRuntimeData::query()->updateOrCreate(
        ['bot_id' => $bot->id, 'key' => $key],
        ['value' => $value],
    );
}

beforeEach(function (): void {
    config([
        'services.node_runtime.secret' => 'runtime-test-secret',
        'oxapay.base_url' => 'https://api.oxapay.com',
    ]);
});

it('creates an OxaPay deposit invoice through the runtime bridge with exact decimal amount', function (): void {
    $bot = oxapayRuntimeBot();
    setBotRuntime($bot, 'oxapay_merchant_api_key', 'merchant-key-one');

    Http::fake([
        'api.oxapay.com/v1/payment/invoice' => function ($request) {
            expect($request->header('merchant_api_key'))->toBe(['merchant-key-one']);
            expect($request['amount'])->toBe('0.3992');
            expect($request['currency'])->toBe('USD');
            expect($request['pay_currency'])->toBe('USDT');

            return Http::response([
                'status' => 200,
                'message' => 'Operation completed successfully!',
                'data' => [
                    'track_id' => 'dep-track-1',
                    'payment_url' => 'https://pay.example/invoice/dep-track-1',
                    'amount' => '0.3992',
                    'currency' => 'USD',
                ],
            ]);
        },
    ]);

    $this->postJson(route('runtime.oxapay'), [
        'bot_id' => $bot->id,
        'action' => 'oxapay.createInvoice',
        'options' => [
            'amount' => '0.3992',
            'currency' => 'USD',
            'pay_currency' => 'USDT',
            'network' => 'TRC20',
            'order_id' => 'dep_test_1',
        ],
    ], ['X-Runtime-Secret' => 'runtime-test-secret'])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'track_id' => 'dep-track-1',
            'payment_url' => 'https://pay.example/invoice/dep-track-1',
            'amount' => '0.3992',
            'currency' => 'USD',
        ])
        ->assertJsonMissing(['merchant_api_key' => 'merchant-key-one']);
});

it('creates OxaPay white-label deposit details through the runtime bridge', function (): void {
    $bot = oxapayRuntimeBot();
    setBotRuntime($bot, 'oxapay_merchant_api_key', 'merchant-white-label');

    Http::fake([
        'api.oxapay.com/v1/payment/white-label' => function ($request) {
            expect($request->header('merchant_api_key'))->toBe(['merchant-white-label']);
            expect($request['amount'])->toBe('1');

            return Http::response([
                'status' => 200,
                'data' => [
                    'track_id' => 'wl-track-1',
                    'address' => 'TWhiteLabelAddress',
                    'amount' => '1',
                    'pay_amount' => '1.002',
                    'currency' => 'USD',
                    'pay_currency' => 'USDT',
                    'network' => 'TRC20',
                    'expired_at' => 1893456000,
                ],
            ]);
        },
    ]);

    $this->postJson(route('runtime.oxapay'), [
        'bot_id' => $bot->id,
        'action' => 'oxapay.createWhiteLabel',
        'options' => ['amount' => '1.0000', 'currency' => 'USD', 'pay_currency' => 'USDT', 'network' => 'TRC20'],
    ], ['X-Runtime-Secret' => 'runtime-test-secret'])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'track_id' => 'wl-track-1',
            'address' => 'TWhiteLabelAddress',
            'pay_amount' => '1.002',
            'pay_currency' => 'USDT',
        ]);
});

it('creates static deposit addresses and fetches payment info', function (): void {
    $bot = oxapayRuntimeBot();
    setBotRuntime($bot, 'oxapay_merchant_api_key', 'merchant-static');

    Http::fake([
        'api.oxapay.com/v1/payment/static-address' => function ($request) {
            expect($request->header('merchant_api_key'))->toBe(['merchant-static']);
            expect($request['currency'])->toBe('USDT');
            expect($request['track_id'])->toBe('user_7701909986');

            return Http::response([
                'status' => 200,
                'data' => [
                    'track_id' => 'user_7701909986',
                    'address' => 'TStaticAddress',
                    'currency' => 'USDT',
                    'network' => 'TRC20',
                ],
            ]);
        },
        'api.oxapay.com/v1/payment/user_7701909986' => function ($request) {
            expect($request->header('merchant_api_key'))->toBe(['merchant-static']);

            return Http::response([
                'status' => 200,
                'data' => [
                    'track_id' => 'user_7701909986',
                    'status' => 'paid',
                    'amount' => '10',
                    'currency' => 'USD',
                ],
            ]);
        },
    ]);

    $this->postJson(route('runtime.oxapay'), [
        'bot_id' => $bot->id,
        'action' => 'oxapay.createStaticAddress',
        'options' => ['currency' => 'USDT', 'network' => 'TRC20', 'track_id' => 'user_7701909986'],
    ], ['X-Runtime-Secret' => 'runtime-test-secret'])
        ->assertOk()
        ->assertJson(['ok' => true, 'track_id' => 'user_7701909986', 'address' => 'TStaticAddress']);

    $this->postJson(route('runtime.oxapay'), [
        'bot_id' => $bot->id,
        'action' => 'oxapay.getPayment',
        'track_id' => 'user_7701909986',
    ], ['X-Runtime-Secret' => 'runtime-test-secret'])
        ->assertOk()
        ->assertJson(['ok' => true, 'track_id' => 'user_7701909986', 'status' => 'paid', 'amount' => '10']);
});

it('supports OxaPay withdrawal payout safely and preserves amount decimals', function (): void {
    $bot = oxapayRuntimeBot();
    setBotRuntime($bot, 'oxapay_merchant_api_key', 'merchant-for-payout-bot');

    $this->postJson(route('runtime.oxapay'), [
        'bot_id' => $bot->id,
        'action' => 'oxapay.payout',
        'options' => ['amount' => '5', 'currency' => 'USDT', 'network' => 'TRC20', 'address' => 'TWithdrawAddress'],
    ], ['X-Runtime-Secret' => 'runtime-test-secret'])
        ->assertOk()
        ->assertJson(['ok' => false, 'error' => 'OxaPay payout key not configured']);

    setBotRuntime($bot, 'oxapay_payout_api_key', 'payout-key-one');

    Http::fake([
        'api.oxapay.com/v1/payout' => function ($request) {
            expect($request->header('payout_api_key'))->toBe(['payout-key-one']);
            expect($request['amount'])->toBe('5.1234');
            expect($request['currency'])->toBe('USDT');
            expect($request['address'])->toBe('TWithdrawAddress');

            return Http::response([
                'status' => 200,
                'data' => [
                    'track_id' => 'payout-track-1',
                    'status' => 'processing',
                    'amount' => '5.1234',
                    'currency' => 'USDT',
                    'address' => 'TWithdrawAddress',
                ],
            ]);
        },
    ]);

    $this->postJson(route('runtime.oxapay'), [
        'bot_id' => $bot->id,
        'action' => 'oxapay.payout',
        'options' => ['amount' => '5.123400', 'currency' => 'USDT', 'network' => 'TRC20', 'address' => 'TWithdrawAddress'],
    ], ['X-Runtime-Secret' => 'runtime-test-secret'])
        ->assertOk()
        ->assertJson(['ok' => true, 'track_id' => 'payout-track-1', 'amount' => '5.1234', 'currency' => 'USDT'])
        ->assertJsonMissing(['payout_api_key' => 'payout-key-one']);
});

it('keeps OxaPay merchant keys isolated per bot and rejects unauthorized bridge calls', function (): void {
    $botOne = oxapayRuntimeBot();
    $botTwo = oxapayRuntimeBot();
    setBotRuntime($botOne, 'oxapay_merchant_api_key', 'merchant-key-one');
    setBotRuntime($botTwo, 'oxapay_merchant_api_key', 'merchant-key-two');

    $this->postJson(route('runtime.oxapay'), [
        'bot_id' => $botTwo->id,
        'action' => 'oxapay.createInvoice',
        'options' => ['amount' => '1'],
    ], ['X-Runtime-Secret' => 'wrong-secret'])->assertUnauthorized();

    Http::fake([
        'api.oxapay.com/v1/payment/invoice' => function ($request) {
            expect($request->header('merchant_api_key'))->toBe(['merchant-key-two']);

            return Http::response([
                'status' => 200,
                'data' => ['track_id' => 'bot-two-track', 'payment_url' => 'https://pay.example/bot-two'],
            ]);
        },
    ]);

    $this->postJson(route('runtime.oxapay'), [
        'bot_id' => $botTwo->id,
        'action' => 'oxapay.createInvoice',
        'options' => ['amount' => '1'],
    ], ['X-Runtime-Secret' => 'runtime-test-secret'])
        ->assertOk()
        ->assertJson(['ok' => true, 'track_id' => 'bot-two-track']);
});

it('masks OxaPay keys in JavaScript getBotData while persisting the full setBotData value server-side', function (): void {
    $payload = [
        'bot' => ['id' => 999, 'name' => 'Runtime Mask Bot'],
        'runtime' => [],
        'command' => [
            'id' => 1,
            'name' => '/masktest',
            'trigger' => '/masktest',
            'type' => 'code',
            'code' => <<<'JS'
await setBotData("oxapay_merchant_api_key", "ox_123456789ab");
const shown = await getBotData("oxapay_merchant_api_key");
await replyHTML(shown);
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/masktest'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
    ];

    $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 8);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
    $result = json_decode($process->getOutput(), true);

    expect($result['ok'])->toBeTrue();
    expect($result['replies'][0]['text'])->toBe('ox_12***9ab');
    expect($result['storage']['bot'][0])->toMatchArray([
        'op' => 'set',
        'key' => 'oxapay_merchant_api_key',
        'value' => 'ox_123456789ab',
    ]);
});
