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
    $serverCode = <<<'JS'
const http = require('http');
const port = Number(process.argv[1]);
const server = http.createServer((req, res) => {
  let body = '';
  req.on('data', chunk => body += chunk);
  req.on('end', () => {
    let payload = {};
    try { payload = JSON.parse(body || '{}'); } catch (_) {}
    const response = payload.action === 'telegram.checkChannelMember'
      ? { ok: true, status: 'administrator', message: 'OK', result: { status: 'administrator', message: 'OK' } }
      : payload.action === 'telegram.getChatMember'
      ? { ok: true, result: { is_member: true, status: 'member', user: { id: payload.options.user_id } } }
      : { ok: false, error: 'unexpected action' };
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
  normal: await isChannelMember("@Bothost_1", 7701909986),
  reversed: await isChannelMember(7701909986, "@Bothost_1"),
  noAt: await isChannelMember("Bothost_1", 7701909986),
  chatMember: await getChatMember("@Bothost_1", 7701909986),
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
            ->and($result['normal'])->toBeTrue()
            ->and($result['reversed'])->toBeTrue()
            ->and($result['noAt'])->toBeTrue()
            ->and($result['chatMember']['is_member'] ?? null)->toBeTrue();
    } finally {
        $server->stop(0);
    }
});
