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
  typeof isChannelMember
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
        ->and($output['replies'][0]['text'] ?? null)->toBe('function,function,function');
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
