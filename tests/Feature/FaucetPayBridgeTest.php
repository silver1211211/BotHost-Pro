<?php

use App\Http\Controllers\RuntimeStorageController;
use App\Models\Bot;
use App\Models\BotRuntimeData;
use App\Models\BotUserRuntimeData;
use App\Models\User;
use App\Services\FaucetPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

function faucetPayTestBot(string $slug = 'faucetpay-test-bot'): Bot
{
    $user = User::factory()->create();

    return Bot::create([
        'user_id' => $user->id,
        'name' => 'FaucetPay Test Bot',
        'slug' => $slug.'-'.str()->random(8),
        'token_encrypted' => '123456:AA-secret-token-'.$slug,
        'status' => 'running',
        'language' => 'javascript',
        'setup_type' => 'custom',
    ]);
}

it('validates FaucetPay keys and reads balance with an explicit key', function (): void {
    Http::fake([
        'https://faucetpay.io/api/v1/getbalance' => Http::response([
            'status' => 200,
            'message' => 'OK',
            'balance' => 12345,
        ]),
    ]);

    $bot = faucetPayTestBot();
    $service = app(FaucetPayService::class);

    expect($service->validateKey($bot, 'fp_test_key'))
        ->ok->toBeTrue()
        ->valid->toBeTrue();

    expect($service->getBalanceWithKey('fp_test_key', 'USDT'))
        ->ok->toBeTrue()
        ->balance->toBe(12345.0)
        ->currency->toBe('USDT');

    Http::assertSent(fn ($request) => $request['api_key'] === 'fp_test_key'
        && $request['currency'] === 'USDT');
});

it('sends FaucetPay amount without converting it again', function (): void {
    Http::fake([
        'https://faucetpay.io/api/v1/send' => Http::response([
            'status' => 200,
            'message' => 'Queued',
        ]),
    ]);

    $bot = faucetPayTestBot('faucetpay-send-test');
    BotRuntimeData::create([
        'bot_id' => $bot->id,
        'key' => 'faucetpay_api_key',
        'value' => 'fp_saved_key',
    ]);

    $result = app(FaucetPayService::class)->send($bot, [
        'to' => 'linked@example.com',
        'amount' => '100000000',
        'currency' => 'USDT',
    ]);

    expect($result['ok'])->toBeTrue()
        ->and($result['amount_smallest_unit'])->toBe('100000000');

    Http::assertSent(fn ($request) => $request['amount'] === '100000000');
});

it('finds user runtime data only within the current bot scope', function (): void {
    config(['services.node_runtime.secret' => 'runtime-test-secret']);

    $bot = faucetPayTestBot('find-user-data-a');
    $otherBot = faucetPayTestBot('find-user-data-b');

    BotUserRuntimeData::create([
        'bot_id' => $bot->id,
        'telegram_user_id' => '111',
        'key' => 'fp_email',
        'value' => 'linked@example.com',
    ]);

    BotUserRuntimeData::create([
        'bot_id' => $otherBot->id,
        'telegram_user_id' => '222',
        'key' => 'fp_email',
        'value' => 'linked@example.com',
    ]);

    $request = Request::create('/runtime/storage', 'POST', [
        'bot_id' => $bot->id,
        'action' => 'user.find',
        'key' => 'fp_email',
        'value' => 'linked@example.com',
    ]);
    $request->headers->set('X-Runtime-Secret', 'runtime-test-secret');

    $response = app(RuntimeStorageController::class)($request);
    $payload = json_decode($response->getContent(), true);

    expect($payload['ok'])->toBeTrue()
        ->and($payload['found'])->toBeTrue()
        ->and($payload['user_id'])->toBe('111');
});
