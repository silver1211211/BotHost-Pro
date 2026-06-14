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
  typeof findUserByData,
  typeof getBotSecret,
  typeof setTimeout,
  typeof clearTimeout,
  typeof validateFaucetPayKey,
  typeof faucetPayValidateApiKey,
  typeof getFaucetPayBalance,
  typeof checkFaucetPayEmail,
  typeof sendFaucetPay,
  typeof faucetPayWithdraw,
  typeof faucetPayWithdrawal,
  typeof faucetPayPayout,
  typeof getFaucetPayCurrencies,
  typeof faucetPayCurrencies
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

    expect($output['replies'][0]['text'] ?? null)->toBe(str_repeat('function,', 18).'function');
});

it('returns clean FaucetPay errors without a configured key or payment bridge', function (): void {
    $payload = [
        'bot' => ['id' => 123, 'name' => 'No Key FaucetPay Bot'],
        'runtime' => [],
        'command' => [
            'id' => 1,
            'name' => '/fp-no-key',
            'trigger' => '/fp-no-key',
            'type' => 'code',
            'code' => <<<'JS'
const results = {
  balanceAlias: await faucetPayGetBalance("USDT"),
  balanceSaved: await faucetPayBalance("USDT"),
  validate: await validateFaucetPayKey(),
  checkEmail: await checkFaucetPayEmail("user@example.com", "USDT"),
  send: await faucetPayPayout("user@example.com", "1", "USDT"),
  currencies: await getFaucetPayCurrencies()
};
await reply(JSON.stringify(results));
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/fp-no-key'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
    ];

    $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 8);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    $output = json_decode($process->getOutput(), true);
    $results = json_decode($output['replies'][0]['text'] ?? '', true);

    foreach ($results as $result) {
        expect($result['ok'])->toBeFalse()
            ->and($result['error'] ?? '')->toContain('FaucetPay API key not configured');
    }
});

it('tolerates pasted markdown fences in command code', function (): void {
    $payload = [
        'bot' => ['id' => 123, 'name' => 'Fence Helper Bot'],
        'runtime' => [],
        'command' => [
            'id' => 1,
            'name' => '/fence-debug',
            'trigger' => '/fence-debug',
            'type' => 'code',
            'code' => <<<'JS'
await sendMessage("before");

```
await sendMessage("inside fence markers");
```

await sendMessage("after");
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/fence-debug'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
    ];

    $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 8);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    $output = json_decode($process->getOutput(), true);

    expect($output['ok'])->toBeTrue()
        ->and(array_column($output['replies'], 'text'))->toBe([
            'before',
            'inside fence markers',
            'after',
        ]);
});

it('returns newly saved FaucetPay secret during the same runtime execution', function (): void {
    $payload = [
        'bot' => ['id' => 123, 'name' => 'Secret Save Bot'],
        'runtime' => [],
        'command' => [
            'id' => 1,
            'name' => '/set-fp-key-debug',
            'trigger' => '/set-fp-key-debug',
            'type' => 'code',
            'code' => <<<'JS'
await setBotData("faucetpay_api_key", "fp_runtime_saved_key");
const secret = await getBotSecret("faucetpay_api_key");
const data = await getBotData("faucetpay_api_key");
await reply(JSON.stringify({ secret, data }));
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/set-fp-key-debug'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
    ];

    $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 8);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    $output = json_decode($process->getOutput(), true);
    $result = json_decode($output['replies'][0]['text'] ?? '', true);

    expect($result['secret'])->toBe('fp_runtime_saved_key')
        ->and($result['data'])->toBe('fp_ru***key');
});

it('returns an explicit scoped object from findUserByData', function (): void {
    $payload = [
        'bot' => ['id' => 123, 'name' => 'FaucetPay Lookup Bot'],
        'runtime' => [],
        'command' => [
            'id' => 1,
            'name' => '/fp-lookup-debug',
            'trigger' => '/fp-lookup-debug',
            'type' => 'code',
            'code' => <<<'JS'
const found = await findUserByData("fp_email", "linked@example.com");
const missing = await findUserByData("fp_email", "missing@example.com");
await reply(JSON.stringify({ found, missing }));
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/fp-lookup-debug'],
        ],
        'storage' => [
            'bot' => [],
            'user' => ['fp_email' => 'linked@example.com'],
            'cross_users' => [],
        ],
        'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
    ];

    $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 8);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    $output = json_decode($process->getOutput(), true);
    $result = json_decode($output['replies'][0]['text'] ?? '', true);

    expect($result['found'])
        ->toMatchArray(['ok' => true, 'found' => true, 'user_id' => '7701909986', 'value' => 'linked@example.com'])
        ->and($result['missing'])
        ->toMatchArray(['ok' => true, 'found' => false, 'user_id' => null, 'value' => null]);
});
