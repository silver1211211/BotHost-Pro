<?php

use Symfony\Component\Process\Process;

it('keeps fallback stdout valid JSON when payment helpers emit debug logs', function (): void {
    $payload = [
        'bot' => ['id' => 123, 'name' => 'Payment Helper Bot'],
        'runtime' => [
            'oxapay_bridge_url' => 'http://127.0.0.1:9/runtime/oxapay',
            'oxapay_bridge_secret' => 'runtime-test-secret',
        ],
        'command' => [
            'id' => 1,
            'name' => '/payment-debug',
            'trigger' => '/payment-debug',
            'type' => 'code',
            'code' => <<<'JS'
const result = await oxapayCreateInvoice({ amount: "0.3992", currency: "USD", pay_currency: "USDT" });
await replyHTML(result.ok ? "ok" : "failed safely");
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/payment-debug'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
    ];

    $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 8);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
    expect(json_decode($process->getOutput(), true))->toBeArray()
        ->and($process->getOutput())->not->toContain('[BotHost]')
        ->and($process->getErrorOutput())->toContain('oxapay_amount_debug');
});

it('keeps FaucetPay global helpers available and fallback diagnostics off stdout', function (): void {
    $server = file_get_contents(base_path('runtime-node/server.js'));
    $fallback = file_get_contents(base_path('runtime-node/execute-once.js'));

    foreach (['faucetPaySend', 'faucetPayWithdraw', 'faucetPayBalance', 'faucetPayGetBalance', 'faucetPayCheckAddress', 'faucetPayCheckEmail', 'faucetPayValidateKey', 'faucetPayGetCurrencies', 'findUserByData'] as $helper) {
        expect($server)->toContain($helper)
            ->and($fallback)->toContain($helper);
    }

    expect($fallback)->not->toContain("console.log('[BotHost] faucetpay_amount_debug'")
        ->and($fallback)->toContain("console.error('[BotHost] faucetpay_amount_debug'");
});

it('exposes FaucetPay and user lookup helpers to command code', function (): void {
    $payload = [
        'bot' => ['id' => 123, 'name' => 'FaucetPay Helper Bot'],
        'runtime' => [],
        'command' => [
            'id' => 1,
            'name' => '/fp-debug',
            'trigger' => '/fp-debug',
            'type' => 'code',
            'code' => <<<'JS'
await reply([
  typeof faucetPayValidateKey,
  typeof faucetPayGetBalance,
  typeof faucetPayCheckEmail,
  typeof faucetPaySend,
  typeof faucetPayGetCurrencies,
  typeof findUserByData
].join(","));
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/fp-debug'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
    ];

    $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 8);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    $output = json_decode($process->getOutput(), true);

    expect($output['replies'][0]['text'] ?? null)->toBe('function,function,function,function,function,function');
});
