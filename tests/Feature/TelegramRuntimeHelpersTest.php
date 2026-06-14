<?php

use Symfony\Component\Process\Process;

it('exposes telegram membership helpers in the fallback runtime', function (): void {
    $payload = [
        'bot' => ['id' => 123, 'name' => 'Membership Helper Bot'],
        'runtime' => [],
        'command' => [
            'id' => 1,
            'name' => '/verify-debug',
            'trigger' => '/verify-debug',
            'type' => 'code',
            'code' => <<<'JS'
await reply([
  typeof checkChannelMember,
  typeof verifyTelegramChannel,
  typeof isChannelMember,
  typeof getTelegramChatMember,
  typeof getChannelMember,
  typeof checkTelegramChannelMember,
  typeof checkTelegramChannel,
  typeof checkChannel,
  typeof verifyChannel,
  typeof verifyChannelMember,
  typeof isTelegramChannelMember
].join(","));
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/verify-debug'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
    ];

    $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 8);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    $output = json_decode($process->getOutput(), true);

    expect($output)->toBeArray()
        ->and($output['replies'][0]['text'] ?? null)->toBe(str_repeat('function,', 10).'function');
});

it('preserves runtime replies when command execution times out', function (): void {
    $payload = [
        'bot' => ['id' => 123, 'name' => 'Timeout Reply Bot'],
        'runtime' => [],
        'command' => [
            'id' => 1,
            'name' => '/timeout-debug',
            'trigger' => '/timeout-debug',
            'type' => 'code',
            'code' => <<<'JS'
await reply("Checking membership...");
await delay(1500);
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/timeout-debug'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 100, 'max_delay_ms' => 2000],
    ];

    $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 8);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    $output = json_decode($process->getOutput(), true);

    expect($output)->toBeArray()
        ->and($output['ok'])->toBeFalse()
        ->and($output['error_type'])->toBe('TimeoutError')
        ->and($output['replies'][0]['text'] ?? null)->toBe('Checking membership...');
});

it('supports channel membership helpers with either argument order and env bridge config', function (): void {
    $port = random_int(19000, 25000);
    // Mock bridge: returns realistic Telegram API shapes (no is_member for member status, only for restricted).
    $serverCode = <<<'JS'
const http = require('http');
const port = Number(process.argv[1]);
const server = http.createServer((req, res) => {
  let body = '';
  req.on('data', chunk => body += chunk);
  req.on('end', () => {
    let payload = {};
    try { payload = JSON.parse(body || '{}'); } catch (_) {}
    const userId = (payload.options || {}).user_id;
    const response = payload.action === 'telegram.checkChannelMember'
      ? { ok: true, is_member: true, status: 'administrator', message: 'User is a member.' }
      : payload.action === 'telegram.getChatMember'
      ? { ok: true, result: { status: 'member', user: { id: userId, is_bot: false } } }
      : { ok: false, error: 'unexpected action: ' + (payload.action || 'none') };
    res.writeHead(200, { 'content-type': 'application/json' });
    res.end(JSON.stringify(response));
  });
});
server.listen(port, '127.0.0.1');
setInterval(() => {}, 1000);
JS;

    $server = new Process(['node', '-e', $serverCode, (string) $port], base_path(), null, null, 10);
    $server->start();
    usleep(500_000);

    try {
        $payload = [
            'bot' => ['id' => 123, 'name' => 'Membership Helper Bot'],
            'runtime' => [],
            'command' => [
                'id' => 1,
                'name' => '/verify-debug',
                'trigger' => '/verify-debug',
                'type' => 'code',
                'code' => <<<'JS'
const results = {
  normal:     await isChannelMember("@Bothost_1", 7701909986),
  reversed:   await isChannelMember(7701909986, "@Bothost_1"),
  noAt:       await isChannelMember("Bothost_1", 7701909986),
  chatMember: await getChatMember("@Bothost_1", 7701909986),
  checkMember: await checkChannelMember("@Bothost_1", 7701909986),
  verifyMember: await verifyTelegramChannel("@Bothost_1", 7701909986),
};
await reply(JSON.stringify(results));
JS,
            ],
            'telegram' => [
                'user_id' => 7701909986,
                'chat_id' => 7701909986,
                'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/verify-debug'],
            ],
            'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
            'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
        ];

        $process = new Process(
            ['node', base_path('runtime-node/execute-once.js')],
            base_path(),
            [
                'NODE_RUNTIME_INTERNAL_URL' => "http://127.0.0.1:{$port}",
                'NODE_RUNTIME_SECRET' => 'runtime-test-secret',
            ],
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            8,
        );
        $process->run();

        expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

        $output = json_decode($process->getOutput(), true);
        $result = json_decode($output['replies'][0]['text'] ?? '', true);

        expect($result)->toBeArray()
            // isChannelMember — boolean true for both argument orders
            ->and($result['normal'])->toBeTrue()
            ->and($result['reversed'])->toBeTrue()
            ->and($result['noAt'])->toBeTrue()
            // getChatMember — normalized shape: ok, is_member, status, user_id, channel
            ->and($result['chatMember']['ok'] ?? null)->toBeTrue()
            ->and($result['chatMember']['is_member'] ?? null)->toBeTrue()
            ->and($result['chatMember']['status'] ?? null)->toBe('member')
            ->and($result['chatMember']['user_id'] ?? null)->toBe('7701909986')
            ->and($result['chatMember']['channel'] ?? null)->toBe('@Bothost_1')
            // checkChannelMember — ok, is_member, status, user_id, channel
            ->and($result['checkMember']['ok'] ?? null)->toBeTrue()
            ->and($result['checkMember']['is_member'] ?? null)->toBeTrue()
            ->and($result['checkMember']['status'] ?? null)->toBe('administrator')
            ->and($result['checkMember']['user_id'] ?? null)->toBe('7701909986')
            ->and($result['checkMember']['channel'] ?? null)->toBe('@Bothost_1')
            // verifyTelegramChannel — same as checkChannelMember
            ->and($result['verifyMember']['ok'] ?? null)->toBeTrue()
            ->and($result['verifyMember']['is_member'] ?? null)->toBeTrue();
    } finally {
        $server->stop(0);
    }
});

it('returns actionable error when telegram bridge is unreachable', function (): void {
    $payload = [
        'bot' => ['id' => 123, 'name' => 'Bridge Failure Bot'],
        'runtime' => [
            'telegram_bridge_url' => 'http://127.0.0.1:1', // port 1 is always refused
            'telegram_bridge_secret' => 'any-secret',
        ],
        'command' => [
            'id' => 1,
            'name' => '/bridge-fail',
            'trigger' => '/bridge-fail',
            'type' => 'code',
            'code' => <<<'JS'
const r = await checkChannelMember("@Bothost_1", 7701909986);
await reply(JSON.stringify({ ok: r.ok, error: r.error || null }));
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/bridge-fail'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
    ];

    $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 8);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    $output = json_decode($process->getOutput(), true);
    $r = json_decode($output['replies'][0]['text'] ?? '', true);

    expect($r['ok'])->toBeFalse()
        ->and($r['error'])->not->toBeNull()
        ->and($r['error'])->not->toBe('Telegram bridge request failed.')
        ->and(strlen($r['error'] ?? ''))->toBeGreaterThan(5);
});

it('server.js returns a text action for sendMessage even when bridge URL and secret are configured', function (): void {
    $runtimePort = random_int(26000, 30000);
    $bridgePort = $runtimePort + 1;

    // Mock bridge — tracks whether sendMessage incorrectly calls it.
    $bridgeCode = <<<'JS'
const http = require('http');
const port = Number(process.argv[1]);
http.createServer((req, res) => {
  res.writeHead(200, { 'content-type': 'application/json' });
  res.end(JSON.stringify({ ok: true, _bridge_was_called: true, result: {} }));
}).listen(port, '127.0.0.1');
setInterval(() => {}, 1000);
JS;

    $bridge = new Process(['node', '-e', $bridgeCode, (string) $bridgePort], base_path(), null, null, 30);
    $bridge->start();

    $runtime = new Process(
        ['node', base_path('runtime-node/server.js')],
        base_path(),
        ['PORT' => (string) $runtimePort, 'HOST' => '127.0.0.1', 'NODE_RUNTIME_SECRET' => 'test-runtime-secret'],
        null,
        30,
    );
    $runtime->start();

    // Poll until server.js is accepting connections (up to 4 s).
    $pingCtx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 1, 'ignore_errors' => true]]);
    $ready = false;
    for ($i = 0; $i < 20; $i++) {
        usleep(200_000);
        if (@file_get_contents("http://127.0.0.1:{$runtimePort}/", false, $pingCtx) !== false) {
            $ready = true;
            break;
        }
    }

    try {
        expect($ready)->toBeTrue('server.js did not start in time: ' . $runtime->getErrorOutput());

        $payload = [
            'bot' => ['id' => 123, 'name' => 'NodePing Bot'],
            'runtime' => [
                'telegram_bridge_url' => "http://127.0.0.1:{$bridgePort}/runtime/telegram",
                'telegram_bridge_secret' => 'test-secret',
            ],
            'command' => [
                'id' => 1,
                'name' => '/nodeping',
                'trigger' => '/nodeping',
                'type' => 'code',
                'code' => 'await sendMessage("✅ NODE PING WORKING");',
            ],
            'telegram' => [
                'user_id' => 99,
                'chat_id' => 99,
                'message' => ['chat' => ['id' => 99], 'from' => ['id' => 99], 'text' => '/nodeping'],
            ],
            'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
            'settings' => ['command_timeout_ms' => 4000, 'max_delay_ms' => 1000],
        ];

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nX-Runtime-Secret: test-runtime-secret\r\n",
                'content' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                'timeout' => 8,
                'ignore_errors' => true,
            ],
        ]);
        $raw = file_get_contents("http://127.0.0.1:{$runtimePort}/execute", false, $ctx);
        $result = json_decode($raw ?: '{}', true) ?: [];

        expect($result['ok'])->toBeTrue('server.js execute returned: ' . ($raw ?: 'empty'))
            ->and($result['replies'])->toHaveCount(1)
            ->and($result['replies'][0]['type'])->toBe('text')
            ->and($result['replies'][0]['text'])->toBe('✅ NODE PING WORKING');
    } finally {
        $runtime->stop(0);
        $bridge->stop(0);
    }
});
