<?php

use Symfony\Component\Process\Process;

function decodeNodeRuntimeOutput(string $output): ?array
{
    $lines = preg_split('/\R/', trim($output));

    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $line = trim($lines[$i]);
        if ($line === '' || $line[0] !== '{') {
            continue;
        }

        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

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

    $output = decodeNodeRuntimeOutput($process->getOutput());

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

    $output = decodeNodeRuntimeOutput($process->getOutput());

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

        $output = decodeNodeRuntimeOutput($process->getOutput());
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

    $output = decodeNodeRuntimeOutput($process->getOutput());
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

it('direct telegram helpers return bridge message ids without queuing', function (): void {
    $bridgePort = random_int(30001, 34000);

    $bridgeCode = <<<'JS'
const http = require('http');
const port = Number(process.argv[1]);
let count = 0;
http.createServer((req, res) => {
  let raw = '';
  req.on('data', chunk => { raw += chunk; });
  req.on('end', () => {
    const body = JSON.parse(raw || '{}');
    const options = body.options || {};
    count += 1;
    res.writeHead(200, { 'content-type': 'application/json' });
    res.end(JSON.stringify({
      ok: true,
      queued: false,
      result: {
        message_id: 1000 + count,
        chat: { id: options.chat_id },
        date: 1890000000 + count,
        text: options.text || null,
        parse_mode: options.parse_mode || null,
        disable_web_page_preview: options.disable_web_page_preview || false,
        reply_markup: options.reply_markup || null
      }
    }));
  });
}).listen(port, '127.0.0.1');
setInterval(() => {}, 1000);
JS;

    $bridge = new Process(['node', '-e', $bridgeCode, (string) $bridgePort], base_path(), null, null, 30);
    $bridge->start();
    usleep(300_000);

    $payload = [
        'bot' => ['id' => 123, 'name' => 'Direct Bridge Bot'],
        'runtime' => [
            'telegram_bridge_url' => "http://127.0.0.1:{$bridgePort}/runtime/telegram",
            'telegram_bridge_secret' => 'test-secret',
        ],
        'command' => [
            'id' => 1,
            'name' => '/direct',
            'trigger' => '/direct',
            'type' => 'code',
            'code' => <<<'JS'
const keyboard = { inline_keyboard: [[{ text: "Reply", callback_data: "/admin reply T1 7701909986" }]] };
async function notifyUserWithButtons(userId, text, buttons, options = {}) {
  return notifyUser(userId, text, { ...options, reply_markup: { inline_keyboard: buttons } });
}
const direct = await sendMessage(8801909986, "<b>Direct</b>", {
  parse_mode: "HTML",
  disable_web_page_preview: true,
  reply_markup: keyboard
});
const notified = await notifyUser(7701909986, "<b>Notify</b>", { reply_markup: keyboard });
const helper = await notifyUserWithButtons(8801909986, "Buttons", [[{ text: "Open", callback_data: "/open" }]], { parse_mode: "HTML" });
const queued = await sendMessage("Queued reply");
await reply(JSON.stringify({ direct, notified, helper, queued }));
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/direct'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 6000, 'max_delay_ms' => 1000],
    ];

    try {
        $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 10);
        $process->run();

        expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

        $output = decodeNodeRuntimeOutput($process->getOutput());
        $summary = json_decode($output['replies'][1]['text'] ?? '', true);

        expect($output['replies'])->toHaveCount(2)
            ->and($output['replies'][0]['text'])->toBe('Queued reply')
            ->and($summary['queued']['queued'])->toBeTrue()
            ->and($summary['direct']['queued'])->toBeFalse()
            ->and($summary['direct']['result']['message_id'])->toBe(1001)
            ->and($summary['direct']['result']['chat']['id'])->toBe(8801909986)
            ->and($summary['direct']['result']['date'])->toBe(1890000001)
            ->and($summary['direct']['result']['parse_mode'])->toBe('HTML')
            ->and($summary['direct']['result']['disable_web_page_preview'])->toBeTrue()
            ->and($summary['direct']['result']['reply_markup']['inline_keyboard'][0][0]['callback_data'])->toBe('/admin reply T1 7701909986')
            ->and($summary['notified']['queued'])->toBeFalse()
            ->and($summary['notified']['result']['message_id'])->toBe(1002)
            ->and($summary['notified']['result']['chat']['id'])->toBe(7701909986)
            ->and($summary['notified']['result']['parse_mode'])->toBe('HTML')
            ->and($summary['notified']['result']['reply_markup']['inline_keyboard'][0][0]['callback_data'])->toBe('/admin reply T1 7701909986')
            ->and($summary['helper']['queued'])->toBeFalse()
            ->and($summary['helper']['result']['message_id'])->toBe(1003);
    } finally {
        $bridge->stop(0);
    }
});

it('direct telegram helper errors return safe ok false payloads', function (): void {
    $bridgePort = random_int(34001, 38000);

    $bridgeCode = <<<'JS'
const http = require('http');
const port = Number(process.argv[1]);
http.createServer((req, res) => {
  res.writeHead(200, { 'content-type': 'application/json' });
  res.end(JSON.stringify({ ok: false, error: 'Telegram sendMessage failed.' }));
}).listen(port, '127.0.0.1');
setInterval(() => {}, 1000);
JS;

    $bridge = new Process(['node', '-e', $bridgeCode, (string) $bridgePort], base_path(), null, null, 30);
    $bridge->start();
    usleep(300_000);

    $payload = [
        'bot' => ['id' => 123, 'name' => 'Direct Bridge Error Bot'],
        'runtime' => [
            'telegram_bridge_url' => "http://127.0.0.1:{$bridgePort}/runtime/telegram",
            'telegram_bridge_secret' => 'test-secret',
        ],
        'command' => [
            'id' => 1,
            'name' => '/direct-error',
            'trigger' => '/direct-error',
            'type' => 'code',
            'code' => <<<'JS'
const result = await sendMessage(8801909986, "Fail");
await reply(JSON.stringify(result));
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => ['chat' => ['id' => 7701909986], 'from' => ['id' => 7701909986], 'text' => '/direct-error'],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 6000, 'max_delay_ms' => 1000],
    ];

    try {
        $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 10);
        $process->run();

        expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

        $output = decodeNodeRuntimeOutput($process->getOutput());
        $result = json_decode($output['replies'][0]['text'] ?? '', true);

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toBe('Telegram sendMessage failed.');
    } finally {
        $bridge->stop(0);
    }
});

it('exposes secure telegram file helpers and returns safe proxy urls', function (): void {
    $bridgePort = random_int(38001, 42000);

    $bridgeCode = <<<'JS'
const http = require('http');
const port = Number(process.argv[1]);
http.createServer((req, res) => {
  let raw = '';
  req.on('data', chunk => { raw += chunk; });
  req.on('end', () => {
    const body = JSON.parse(raw || '{}');
    const fileId = (body.options || {}).file_id;
    const response = body.action === 'telegram.getFile'
      ? {
          ok: true,
          queued: false,
          result: {
            file_id: fileId,
            file_unique_id: 'unique_' + fileId,
            file_path: 'photos/file_1.jpg',
            file_size: 12345,
            file_url: 'https://bothost.test/dashboard/bots/123/files/safe-hash'
          }
        }
      : { ok: false, error: 'unexpected action: ' + (body.action || 'none') };
    res.writeHead(200, { 'content-type': 'application/json' });
    res.end(JSON.stringify(response));
  });
}).listen(port, '127.0.0.1');
setInterval(() => {}, 1000);
JS;

    $bridge = new Process(['node', '-e', $bridgeCode, (string) $bridgePort], base_path(), null, null, 30);
    $bridge->start();
    usleep(300_000);

    $payload = [
        'bot' => ['id' => 123, 'name' => 'File Helper Bot'],
        'runtime' => [
            'telegram_bridge_url' => "http://127.0.0.1:{$bridgePort}/runtime/telegram",
            'telegram_bridge_secret' => 'test-secret',
        ],
        'command' => [
            'id' => 1,
            'name' => '/file-debug',
            'trigger' => '/file-debug',
            'type' => 'code',
            'code' => <<<'JS'
const bySize = getTelegramImageFileId({ photo: [
  { file_id: "small", file_unique_id: "u1", width: 90, height: 90, file_size: 100 },
  { file_id: "large", file_unique_id: "u2", width: 640, height: 480, file_size: 5000 }
] });
const byArea = getTelegramImageFileId({ photos: [
  { file_id: "wide", width: 1000, height: 100 },
  { file_id: "big", width: 800, height: 800 }
] });
const direct = getTelegramImageFileId({ file_id: "direct_file_id" });
const invalid = await getTelegramFile({ file_id: "../bad" });
const file = await getTelegramFile({ file_id: bySize.file_id });
const url = await getTelegramFileUrl({ file_id: bySize.file_id });
const ticket = await createSupportTicket({
  user_id: String(user.id),
  type: "photo",
  text: "photo caption",
  attachment: url,
  file_url: url.file_url,
  file_id: bySize.file_id
});
await reply(JSON.stringify({
  helpers: [typeof getTelegramFile, typeof getTelegramFileUrl, typeof getTelegramImageFileId],
  bySize, byArea, direct, invalid, file, url, ticket
}));
JS,
        ],
        'telegram' => [
            'user_id' => 7701909986,
            'chat_id' => 7701909986,
            'message' => [
                'chat' => ['id' => 7701909986],
                'from' => ['id' => 7701909986],
                'text' => '/file-debug',
            ],
        ],
        'storage' => ['bot' => [], 'user' => [], 'cross_users' => []],
        'settings' => ['command_timeout_ms' => 6000, 'max_delay_ms' => 1000],
    ];

    try {
        $process = new Process(['node', base_path('runtime-node/execute-once.js')], base_path(), null, json_encode($payload, JSON_UNESCAPED_SLASHES), 10);
        $process->run();

        expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

        $output = decodeNodeRuntimeOutput($process->getOutput());
        $summary = json_decode($output['replies'][0]['text'] ?? '', true);

        expect($summary['helpers'])->toBe(['function', 'function', 'function'])
            ->and($summary['bySize']['file_id'])->toBe('large')
            ->and($summary['byArea']['file_id'])->toBe('big')
            ->and($summary['direct']['file_id'])->toBe('direct_file_id')
            ->and($summary['invalid']['ok'])->toBeFalse()
            ->and($summary['file']['ok'])->toBeTrue()
            ->and($summary['file']['file_path'])->toBe('photos/file_1.jpg')
            ->and($summary['file']['file_url'])->toBeNull()
            ->and($summary['url']['file_url'])->toBe('https://bothost.test/dashboard/bots/123/files/safe-hash')
            ->and($summary['url']['file_url'])->not->toContain('bot123456:')
            ->and($summary['ticket']['ok'])->toBeTrue()
            ->and($summary['ticket']['ticket']['file_url'])->toBe('https://bothost.test/dashboard/bots/123/files/safe-hash');
    } finally {
        $bridge->stop(0);
    }
});
