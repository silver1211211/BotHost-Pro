<?php

use App\Models\RuntimeHelper;
use App\Models\RuntimeHelperCategory;
use App\Models\RuntimeHelperVersion;
use App\Models\RuntimeReloadLog;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\DockerRuntimeService;
use App\Services\RuntimeHelperBundleGenerator;
use App\Services\RuntimeReloadProcessLauncher;
use App\Services\RuntimeReloadService;

function runtimeReloadHelperFixture(bool $requiresReload = true): RuntimeHelper
{
    $category = RuntimeHelperCategory::query()->create([
        'name' => 'Utility',
        'slug' => 'reload-utility-'.uniqid(),
        'helper_type' => 'utility',
        'allowed_domains' => [],
        'is_active' => true,
    ]);

    $helper = RuntimeHelper::query()->create([
        'category_id' => $category->id,
        'name' => 'reloadHelper'.uniqid(),
        'label' => 'Reload Helper',
        'helper_type' => 'utility',
        'code' => 'return { ok: true };',
        'status' => 'active',
        'expose_to_bot_code' => true,
        'requires_runtime_reload' => $requiresReload,
    ]);

    $version = RuntimeHelperVersion::query()->create([
        'helper_id' => $helper->id,
        'version_number' => 1,
        'code' => 'return { ok: true };',
        'safety_scan_status' => 'passed',
        'syntax_check_status' => 'passed',
        'test_status' => 'passed',
        'status' => 'active',
    ]);

    $helper->forceFill(['active_version_id' => $version->id])->save();

    return $helper;
}

function fakeRuntimeBundleGenerator(array $report): RuntimeHelperBundleGenerator
{
    return new class($report) extends RuntimeHelperBundleGenerator {
        public function __construct(private readonly array $fakeReport) {}

        public function publish(): array
        {
            return $this->fakeReport;
        }

        public function livePath(): string
        {
            return storage_path('framework/testing/admin-helpers-generated.js');
        }
    };
}

function fakeDockerRuntimeService(array $results = []): DockerRuntimeService
{
    return new class($results) extends DockerRuntimeService {
        public array $recreated = [];

        public function __construct(private readonly array $results) {}

        public function inspectAdminRuntimeSupport(string $containerName): array
        {
            $result = $this->results[$containerName] ?? [
                'ok' => true,
                'exists' => true,
                'running' => true,
                'mounted' => false,
                'read_only' => false,
                'reason' => 'bundle mount missing',
                'error' => null,
            ];

            $mounted = (bool) ($result['mounted'] ?? false);
            $readOnly = (bool) ($result['read_only'] ?? false);
            $helperLoaderSupported = $result['helper_loader_supported'] ?? ($mounted && $readOnly);
            $runtimeHashMatches = $result['runtime_hash_matches'] ?? ($mounted && $readOnly);

            return $result + [
                'ready' => $mounted && $readOnly && $helperLoaderSupported && $runtimeHashMatches && (bool) ($result['localhost_only'] ?? true),
                'runtime_hash' => $runtimeHashMatches ? $this->runtimeSourceHash() : null,
                'expected_runtime_hash' => $this->runtimeSourceHash(),
                'runtime_hash_matches' => $runtimeHashMatches,
                'helper_loader_supported' => $helperLoaderSupported,
                'localhost_only' => true,
            ];
        }

        public function hasAdminHelperBundleMount(string $containerName): array
        {
            return $this->results[$containerName] ?? [
                'ok' => true,
                'exists' => true,
                'running' => true,
                'mounted' => false,
                'read_only' => false,
                'reason' => 'bundle mount missing',
                'error' => null,
            ];
        }

        public function runtimeSourceHash(): string
        {
            return 'expected-runtime-hash';
        }

        public function buildImage(): array
        {
            return ['ok' => true, 'output' => 'built', 'error' => ''];
        }

        public function recreateBotContainerForHelperBundle(\App\Models\Bot $bot): array
        {
            $this->recreated[] = $bot->id;

            return [
                'ok' => true,
                'bot_id' => $bot->id,
                'container_name' => $bot->container_name,
                'action' => 'recreated',
                'reason' => 'runtime support updated',
                'error' => null,
            ];
        }
    };
}

function fakeRuntimeReloadProcessLauncher(): RuntimeReloadProcessLauncher
{
    return new class extends RuntimeReloadProcessLauncher {
        public array $started = [];

        public function start(RuntimeReloadLog $log, array $options): void
        {
            $this->started[] = ['log_id' => $log->id, 'options' => $options];
        }
    };
}

function fakeDockerRuntimeInspector(array $inspect): DockerRuntimeService
{
    return new class($inspect) extends DockerRuntimeService {
        public function __construct(private readonly array $inspect) {}

        public function inspectContainer(string $containerName): array
        {
            return $this->inspect;
        }

        public function runtimeSourceHash(): string
        {
            return 'expected-runtime-hash';
        }
    };
}

test('runtime Dockerfile includes admin helper loader', function () {
    $dockerfile = file_get_contents(base_path('runtime-node/Dockerfile'));

    expect($dockerfile)->toContain('server.js')
        ->and($dockerfile)->toContain('admin-helper-loader.js')
        ->and($dockerfile)->toContain('ARG BOTHOST_RUNTIME_SOURCE_HASH')
        ->and($dockerfile)->toContain('LABEL com.bothost.runtime_source_hash');
});

test('docker runtime support detects old container missing runtime source hash', function () {
    $docker = fakeDockerRuntimeInspector([
        'ok' => true,
        'exists' => true,
        'running' => true,
        'mounts' => [[
            'destination' => '/app/admin-helpers-generated.js',
            'mode' => 'ro',
            'rw' => false,
        ]],
        'labels' => [],
        'env' => [],
        'ports' => ['8787/tcp' => [['HostIp' => '127.0.0.1', 'HostPort' => '41001']]],
    ]);

    $support = $docker->inspectAdminRuntimeSupport('bothost-bot-old');

    expect($support['ready'])->toBeFalse()
        ->and($support['helper_loader_supported'])->toBeFalse()
        ->and($support['reason'])->toBe('missing helper loader support');
});

test('docker runtime support detects stale runtime source hash', function () {
    $docker = fakeDockerRuntimeInspector([
        'ok' => true,
        'exists' => true,
        'running' => true,
        'mounts' => [[
            'destination' => '/app/admin-helpers-generated.js',
            'mode' => 'ro',
            'rw' => false,
        ]],
        'labels' => ['com.bothost.runtime_source_hash' => 'old-runtime-hash'],
        'env' => [],
        'ports' => ['8787/tcp' => [['HostIp' => '127.0.0.1', 'HostPort' => '41001']]],
    ]);

    $support = $docker->inspectAdminRuntimeSupport('bothost-bot-stale');

    expect($support['ready'])->toBeFalse()
        ->and($support['helper_loader_supported'])->toBeTrue()
        ->and($support['runtime_hash_matches'])->toBeFalse()
        ->and($support['reason'])->toBe('runtime source hash outdated');
});

test('docker runtime support skips healthy up to date localhost-only container', function () {
    $docker = fakeDockerRuntimeInspector([
        'ok' => true,
        'exists' => true,
        'running' => true,
        'mounts' => [[
            'destination' => '/app/admin-helpers-generated.js',
            'mode' => 'ro',
            'rw' => false,
        ]],
        'labels' => ['com.bothost.runtime_source_hash' => 'expected-runtime-hash'],
        'env' => [],
        'ports' => ['8787/tcp' => [['HostIp' => '127.0.0.1', 'HostPort' => '41001']]],
    ]);

    $support = $docker->inspectAdminRuntimeSupport('bothost-bot-ready');

    expect($support['ready'])->toBeTrue()
        ->and($support['localhost_only'])->toBeTrue()
        ->and($support['reason'])->toBe('runtime support already up to date');
});

test('docker runtime support flags non localhost port binding', function () {
    $docker = fakeDockerRuntimeInspector([
        'ok' => true,
        'exists' => true,
        'running' => true,
        'mounts' => [[
            'destination' => '/app/admin-helpers-generated.js',
            'mode' => 'ro',
            'rw' => false,
        ]],
        'labels' => ['com.bothost.runtime_source_hash' => 'expected-runtime-hash'],
        'env' => [],
        'ports' => ['8787/tcp' => [['HostIp' => '0.0.0.0', 'HostPort' => '41001']]],
    ]);

    $support = $docker->inspectAdminRuntimeSupport('bothost-bot-open');

    expect($support['ready'])->toBeFalse()
        ->and($support['localhost_only'])->toBeFalse()
        ->and($support['reason'])->toBe('port binding is not localhost-only');
});

test('publish bundle route runs publish and returns completed log', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $helper = runtimeReloadHelperFixture();
    app()->instance(RuntimeHelperBundleGenerator::class, fakeRuntimeBundleGenerator([
        'ok' => true,
        'helpers_total' => 1,
        'helpers_compiled' => 1,
        'helpers_skipped' => 0,
        'compiled' => [['id' => $helper->id, 'name' => $helper->name]],
        'skipped' => [],
        'content' => "'use strict';",
    ]));
    app()->instance(DockerRuntimeService::class, fakeDockerRuntimeService());

    $this->actingAs($admin)->post(route('admin.runtime.reload.publish-bundle'))
        ->assertRedirect()
        ->assertSessionHas('status');

    $log = RuntimeReloadLog::query()->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('success')
        ->and($log->helpers_compiled)->toBe(1)
        ->and($helper->fresh()->requires_runtime_reload)->toBeFalse();
});

test('failed publish service marks runtime reload log failed', function () {
    $log = RuntimeReloadLog::query()->create([
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'running',
        'mode' => 'prepare_only',
    ]);

    $service = new RuntimeReloadService(fakeRuntimeBundleGenerator([
        'ok' => false,
        'helpers_total' => 1,
        'helpers_compiled' => 0,
        'helpers_skipped' => 0,
        'skipped' => [],
        'content' => null,
        'error' => 'Synthetic failure.',
    ]), fakeDockerRuntimeService());

    $report = $service->publishBundle($log);

    expect($report['ok'])->toBeFalse()
        ->and($log->fresh()->current_step)->toBe('Bundle publish failed')
        ->and($log->fresh()->error)->toContain('Synthetic failure');
});

test('status route returns runtime reload log json', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'success',
        'mode' => 'prepare_only',
        'current_step' => 'Bundle published',
        'helpers_compiled' => 2,
        'duration_ms' => 123,
    ]);

    $this->actingAs($admin)->getJson(route('admin.runtime.reload.status', $log))
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('current_step', 'Bundle published')
        ->assertJsonPath('helpers_compiled', 2)
        ->assertJsonPath('completed', true);
});

test('status route reports running task as not completed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'running',
        'mode' => 'docker',
        'current_step' => 'Inspecting containers',
        'steps_total' => 5,
        'steps_completed' => 2,
    ]);

    $this->actingAs($admin)->getJson(route('admin.runtime.reload.status', $log))
        ->assertOk()
        ->assertJsonPath('status', 'running')
        ->assertJsonPath('completed', false)
        ->assertJsonPath('current_step', 'Inspecting containers');
});

test('status route reports partial and failed tasks as completed', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach (['partial', 'failed'] as $status) {
        $log = RuntimeReloadLog::query()->create([
            'triggered_by' => $admin->id,
            'trigger_type' => 'docker_refresh_live',
            'status' => $status,
            'mode' => 'docker',
            'current_step' => 'Done',
        ]);

        $this->actingAs($admin)->getJson(route('admin.runtime.reload.status', $log))
            ->assertOk()
            ->assertJsonPath('status', $status)
            ->assertJsonPath('completed', true);
    }
});

test('runtime reload service publish bundle uses generator and updates log', function () {
    $helper = runtimeReloadHelperFixture();
    $log = RuntimeReloadLog::query()->create([
        'trigger_type' => 'test_publish',
        'status' => 'running',
        'mode' => 'prepare_only',
    ]);

    $service = new RuntimeReloadService(fakeRuntimeBundleGenerator([
        'ok' => true,
        'helpers_total' => 1,
        'helpers_compiled' => 1,
        'helpers_skipped' => 0,
        'compiled' => [['id' => $helper->id, 'name' => $helper->name]],
        'skipped' => [],
        'content' => "'use strict';",
    ]), fakeDockerRuntimeService());

    $report = $service->publishBundle($log);

    expect($report['ok'])->toBeTrue()
        ->and($log->fresh()->current_step)->toBe('Finalizing bundle publish')
        ->and($log->fresh()->helpers_compiled)->toBe(1)
        ->and($helper->fresh()->requires_runtime_reload)->toBeFalse();
});

test('runtime reload service docker refresh dry run plans without destructive runtime service', function () {
    $user = User::factory()->create();
    $bundlePath = storage_path('framework/testing/admin-helpers-generated.js');
    if (! is_dir(dirname($bundlePath))) {
        mkdir(dirname($bundlePath), 0755, true);
    }
    file_put_contents($bundlePath, "'use strict';");

    \App\Models\Bot::query()->create([
        'user_id' => $user->id,
        'name' => 'Docker Bot',
        'slug' => 'docker-bot',
        'token_encrypted' => '123456:ABCDEF1234567890ABCDEF',
        'status' => 'running',
        'runtime_mode' => 'docker',
        'container_name' => 'bothost-bot-1',
        'container_status' => 'running',
        'runtime_http_port' => 41001,
    ]);

    \App\Models\Bot::query()->create([
        'user_id' => $user->id,
        'name' => 'Local Bot',
        'slug' => 'local-bot',
        'token_encrypted' => '123456:ABCDEF1234567890LOCAL',
        'status' => 'running',
        'runtime_mode' => 'local',
    ]);

    $log = RuntimeReloadLog::query()->create([
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'running',
        'mode' => 'docker',
    ]);

    $service = new RuntimeReloadService(fakeRuntimeBundleGenerator([
        'ok' => true,
        'helpers_total' => 0,
        'helpers_compiled' => 0,
        'helpers_skipped' => 0,
        'compiled' => [],
        'skipped' => [],
    ]), fakeDockerRuntimeService());

    $report = $service->refreshDockerContainers($log, true);

    expect($report['ok'])->toBeTrue()
        ->and($report['dry_run'])->toBeTrue()
        ->and($report['type'])->toBe('docker_refresh')
        ->and($report['bundle_exists'])->toBeTrue()
        ->and($report['bots_checked'])->toBe(2)
        ->and($report['would_recreate'])->toHaveCount(1)
        ->and($report['skipped'])->toHaveCount(1)
        ->and($log->fresh()->containers_affected)->toBe(1)
        ->and($log->fresh()->parsedOutput()['type'])->toBe('docker_refresh')
        ->and($log->fresh()->output)->toContain('would_recreate');
});

test('runtime reload service docker refresh plan categorizes inspect results', function () {
    $user = User::factory()->create();
    $bundlePath = storage_path('framework/testing/admin-helpers-generated.js');
    if (! is_dir(dirname($bundlePath))) {
        mkdir(dirname($bundlePath), 0755, true);
    }
    file_put_contents($bundlePath, "'use strict';");

    foreach ([
        ['Ready Bot', 'ready-bot', 'bothost-bot-ready'],
        ['Missing Mount Bot', 'missing-mount-bot', 'bothost-bot-missing'],
        ['Gone Bot', 'gone-bot', 'bothost-bot-gone'],
        ['Inspect Failed Bot', 'inspect-failed-bot', 'bothost-bot-failed'],
    ] as [$name, $slug, $container]) {
        \App\Models\Bot::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
            'token_encrypted' => '123456:'.strtoupper(str_replace('-', '', $slug)).'TOKEN',
            'status' => 'running',
            'runtime_mode' => 'docker',
            'container_name' => $container,
            'container_status' => 'running',
            'runtime_http_port' => 41000 + random_int(1, 999),
        ]);
    }

    $log = RuntimeReloadLog::query()->create([
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'running',
        'mode' => 'docker',
    ]);

    $service = new RuntimeReloadService(fakeRuntimeBundleGenerator([
        'ok' => true,
        'helpers_total' => 0,
        'helpers_compiled' => 0,
        'helpers_skipped' => 0,
        'compiled' => [],
        'skipped' => [],
    ]), fakeDockerRuntimeService([
        'bothost-bot-ready' => [
            'ok' => true,
            'exists' => true,
            'running' => true,
            'mounted' => true,
            'read_only' => true,
            'reason' => 'bundle mount present',
            'error' => null,
        ],
        'bothost-bot-missing' => [
            'ok' => true,
            'exists' => true,
            'running' => true,
            'mounted' => false,
            'read_only' => false,
            'reason' => 'bundle mount missing',
            'error' => null,
        ],
        'bothost-bot-gone' => [
            'ok' => true,
            'exists' => false,
            'running' => false,
            'mounted' => false,
            'read_only' => false,
            'reason' => 'container not found',
            'error' => null,
        ],
        'bothost-bot-failed' => [
            'ok' => false,
            'exists' => false,
            'running' => false,
            'mounted' => false,
            'read_only' => false,
            'reason' => 'inspect failed',
            'error' => 'inspect failed',
        ],
    ]));

    $report = $service->planDockerRefresh($log);

    expect($report['ready'])->toHaveCount(1)
        ->and($report['would_recreate'])->toHaveCount(1)
        ->and($report['not_found'])->toHaveCount(1)
        ->and($report['unknown'])->toHaveCount(1)
        ->and($report['not_running'])->toHaveCount(0)
        ->and($report['ready'][0]['reason'])->toBe('runtime support already up to date')
        ->and($report['would_recreate'][0]['reason'])->toBe('bundle mount missing');
});

test('runtime reload service docker refresh plan categorizes not running containers', function () {
    $user = User::factory()->create();
    $bundlePath = storage_path('framework/testing/admin-helpers-generated.js');
    if (! is_dir(dirname($bundlePath))) {
        mkdir(dirname($bundlePath), 0755, true);
    }
    file_put_contents($bundlePath, "'use strict';");

    \App\Models\Bot::query()->create([
        'user_id' => $user->id,
        'name' => 'Stopped Bot',
        'slug' => 'stopped-bot',
        'token_encrypted' => '123456:STOPPEDBOTTOKEN',
        'status' => 'stopped',
        'runtime_mode' => 'docker',
        'container_name' => 'bothost-bot-stopped',
        'container_status' => 'exited',
        'runtime_http_port' => 41010,
    ]);

    $log = RuntimeReloadLog::query()->create([
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'running',
        'mode' => 'docker',
    ]);

    $service = new RuntimeReloadService(fakeRuntimeBundleGenerator([
        'ok' => true,
        'helpers_total' => 0,
        'helpers_compiled' => 0,
        'helpers_skipped' => 0,
        'compiled' => [],
        'skipped' => [],
    ]), fakeDockerRuntimeService([
        'bothost-bot-stopped' => [
            'ok' => true,
            'exists' => true,
            'running' => false,
            'mounted' => false,
            'read_only' => false,
            'reason' => 'container not running',
            'error' => null,
        ],
    ]));

    $report = $service->planDockerRefresh($log);

    expect($report['not_running'])->toHaveCount(1)
        ->and($report['not_running'][0]['reason'])->toBe('container not running');
});

test('runtime reload service live docker refresh remains blocked', function () {
    $log = RuntimeReloadLog::query()->create([
        'trigger_type' => 'docker_refresh_live',
        'status' => 'running',
        'mode' => 'docker',
    ]);

    $service = new RuntimeReloadService(fakeRuntimeBundleGenerator([
        'ok' => true,
        'helpers_total' => 0,
        'helpers_compiled' => 0,
        'helpers_skipped' => 0,
        'compiled' => [],
        'skipped' => [],
    ]), fakeDockerRuntimeService());

    $report = $service->refreshDockerContainers($log, false);

    expect($report['ok'])->toBeFalse()
        ->and($report['error'])->toBe('Live Docker refresh requires explicit confirmation.')
        ->and($log->fresh()->current_step)->toBe('Live Docker refresh blocked');
});

test('runtime reload service dry run does not call recreate method', function () {
    $user = User::factory()->create();
    $bundlePath = storage_path('framework/testing/admin-helpers-generated.js');
    if (! is_dir(dirname($bundlePath))) {
        mkdir(dirname($bundlePath), 0755, true);
    }
    file_put_contents($bundlePath, "'use strict';");

    \App\Models\Bot::query()->create([
        'user_id' => $user->id,
        'name' => 'Needs Recreate',
        'slug' => 'needs-recreate',
        'token_encrypted' => '123456:NEEDSRECREATETOKEN',
        'status' => 'running',
        'runtime_mode' => 'docker',
        'container_name' => 'bothost-bot-needs-recreate',
        'container_status' => 'running',
        'runtime_http_port' => 42001,
    ]);

    $docker = fakeDockerRuntimeService();
    $service = new RuntimeReloadService(fakeRuntimeBundleGenerator([
        'ok' => true,
        'helpers_total' => 0,
        'helpers_compiled' => 0,
        'helpers_skipped' => 0,
        'compiled' => [],
        'skipped' => [],
    ]), $docker);

    $log = RuntimeReloadLog::query()->create([
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'running',
        'mode' => 'docker',
    ]);

    $report = $service->refreshDockerContainers($log, true);

    expect($report['would_recreate'])->toHaveCount(1)
        ->and($docker->recreated)->toBe([]);
});

test('runtime reload service live refresh recreates only planned containers', function () {
    $user = User::factory()->create();
    $bundlePath = storage_path('framework/testing/admin-helpers-generated.js');
    if (! is_dir(dirname($bundlePath))) {
        mkdir(dirname($bundlePath), 0755, true);
    }
    file_put_contents($bundlePath, "'use strict';");

    foreach ([
        ['Ready Bot', 'ready-live-bot', 'bothost-live-ready'],
        ['Missing Mount Bot', 'missing-live-bot', 'bothost-live-missing'],
        ['Stopped Bot', 'stopped-live-bot', 'bothost-live-stopped'],
        ['Gone Bot', 'gone-live-bot', 'bothost-live-gone'],
        ['Unknown Bot', 'unknown-live-bot', 'bothost-live-unknown'],
    ] as [$name, $slug, $container]) {
        \App\Models\Bot::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
            'token_encrypted' => '123456:'.strtoupper(str_replace('-', '', $slug)).'TOKEN',
            'status' => 'running',
            'runtime_mode' => 'docker',
            'container_name' => $container,
            'container_status' => 'running',
            'runtime_http_port' => 43000 + random_int(1, 999),
        ]);
    }

    $docker = fakeDockerRuntimeService([
        'bothost-live-ready' => [
            'ok' => true,
            'exists' => true,
            'running' => true,
            'mounted' => true,
            'read_only' => true,
            'reason' => 'bundle mount present',
            'error' => null,
        ],
        'bothost-live-missing' => [
            'ok' => true,
            'exists' => true,
            'running' => true,
            'mounted' => false,
            'read_only' => false,
            'reason' => 'bundle mount missing',
            'error' => null,
        ],
        'bothost-live-stopped' => [
            'ok' => true,
            'exists' => true,
            'running' => false,
            'mounted' => false,
            'read_only' => false,
            'reason' => 'container not running',
            'error' => null,
        ],
        'bothost-live-gone' => [
            'ok' => true,
            'exists' => false,
            'running' => false,
            'mounted' => false,
            'read_only' => false,
            'reason' => 'container not found',
            'error' => null,
        ],
        'bothost-live-unknown' => [
            'ok' => false,
            'exists' => false,
            'running' => false,
            'mounted' => false,
            'read_only' => false,
            'reason' => 'inspect failed',
            'error' => 'inspect failed',
        ],
    ]);
    $service = new RuntimeReloadService(fakeRuntimeBundleGenerator([
        'ok' => true,
        'helpers_total' => 0,
        'helpers_compiled' => 0,
        'helpers_skipped' => 0,
        'compiled' => [],
        'skipped' => [],
    ]), $docker);

    $log = RuntimeReloadLog::query()->create([
        'trigger_type' => 'docker_refresh_live',
        'status' => 'running',
        'mode' => 'docker',
        'started_at' => now(),
    ]);

    $report = $service->refreshDockerContainers($log, false, true);

    expect($report['ok'])->toBeTrue()
        ->and($report['type'])->toBe('docker_refresh')
        ->and($report['recreated'])->toHaveCount(1)
        ->and($report['skipped'])->toHaveCount(4)
        ->and($docker->recreated)->toHaveCount(1)
        ->and($log->fresh()->status)->toBe('success')
        ->and($log->fresh()->output)->toContain('recreated');
});

test('runtime reload log parses output and summarizes counts', function () {
    $log = RuntimeReloadLog::query()->create([
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'success',
        'mode' => 'docker',
        'output' => json_encode([
            'type' => 'docker_refresh',
            'dry_run' => true,
            'bots_checked' => 2,
            'ready' => [['bot_id' => 1]],
            'would_recreate' => [['bot_id' => 2]],
            'unknown' => [],
        ]),
    ]);

    expect($log->parsedOutput()['type'])->toBe('docker_refresh')
        ->and($log->outputType())->toBe('docker_refresh')
        ->and($log->isDryRun())->toBeTrue()
        ->and($log->summaryCounts()['ready'])->toBe(1)
        ->and($log->summaryCounts()['would_recreate'])->toBe(1);
});

test('bundle publish output does not include helper code or generated content', function () {
    $helper = runtimeReloadHelperFixture();
    $log = RuntimeReloadLog::query()->create([
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'running',
        'mode' => 'prepare_only',
    ]);

    $service = new RuntimeReloadService(fakeRuntimeBundleGenerator([
        'ok' => true,
        'helpers_total' => 1,
        'helpers_compiled' => 1,
        'helpers_skipped' => 0,
        'compiled' => [['id' => $helper->id, 'name' => $helper->name, 'code' => 'return secretCode;']],
        'skipped' => [],
        'content' => 'generated bundle content should not be logged',
    ]), fakeDockerRuntimeService());

    $service->publishBundle($log);

    expect($log->fresh()->output)->toContain('bundle_publish')
        ->and($log->fresh()->output)->not->toContain('secretCode')
        ->and($log->fresh()->output)->not->toContain('generated bundle content');
});

test('log detail page renders bundle helper sections', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'success',
        'mode' => 'prepare_only',
        'helpers_compiled' => 1,
        'output' => json_encode([
            'type' => 'bundle_publish',
            'helpers_total' => 2,
            'helpers_compiled' => 1,
            'helpers_skipped' => 1,
            'compiled' => [['id' => 10, 'name' => 'goodHelper']],
            'skipped' => [['name' => 'badHelper', 'reason' => 'Safety scan failed']],
        ]),
    ]);

    $this->actingAs($admin)->get(route('admin.runtime.reload.show', $log))
        ->assertOk()
        ->assertSee('Compiled Helpers')
        ->assertSee('goodHelper')
        ->assertSee('Skipped Helpers')
        ->assertSee('Safety scan failed');
});

test('log detail page renders docker refresh sections', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'success',
        'mode' => 'docker',
        'output' => json_encode([
            'type' => 'docker_refresh',
            'dry_run' => true,
            'bundle_exists' => true,
            'bots_checked' => 2,
            'ready' => [['bot_id' => 1, 'bot_name' => 'Ready Bot', 'container_name' => 'ready', 'reason' => 'runtime support already up to date']],
            'would_recreate' => [['bot_id' => 2, 'bot_name' => 'Needs Bot', 'container_name' => 'needs', 'reason' => 'bundle mount missing']],
            'not_running' => [],
            'not_found' => [],
            'unknown' => [],
            'skipped' => [],
        ]),
    ]);

    $this->actingAs($admin)->get(route('admin.runtime.reload.show', $log))
        ->assertOk()
        ->assertSee('Ready Containers')
        ->assertSee('Ready Bot')
        ->assertSee('Would Recreate')
        ->assertSee('Needs Bot');
});

test('reload logs index filters by status and trigger type', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    RuntimeReloadLog::query()->create([
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'success',
        'mode' => 'docker',
        'output' => json_encode(['type' => 'docker_refresh', 'dry_run' => true]),
    ]);
    RuntimeReloadLog::query()->create([
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'failed',
        'mode' => 'prepare_only',
        'output' => json_encode(['type' => 'bundle_publish']),
    ]);

    $this->actingAs($admin)->get(route('admin.runtime.reload.logs', [
        'status' => 'success',
        'trigger_type' => 'docker_refresh_dry_run',
    ]))->assertOk()
        ->assertSee('docker_refresh_dry_run')
        ->assertDontSee('manual_bundle_publish');
});

test('reload logs index filters by mode and date range', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    RuntimeReloadLog::query()->create([
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'success',
        'mode' => 'docker',
        'created_at' => now(),
        'updated_at' => now(),
        'output' => json_encode(['type' => 'docker_refresh', 'dry_run' => true]),
    ]);
    RuntimeReloadLog::query()->create([
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'success',
        'mode' => 'prepare_only',
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
        'output' => json_encode(['type' => 'bundle_publish']),
    ]);

    $this->actingAs($admin)->get(route('admin.runtime.reload.logs', [
        'mode' => 'docker',
        'date_from' => now()->subDay()->toDateString(),
        'date_to' => now()->toDateString(),
    ]))->assertOk()
        ->assertSee('docker_refresh_dry_run')
        ->assertDontSee('manual_bundle_publish');
});

test('export json returns downloadable safe report', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'success',
        'mode' => 'prepare_only',
        'helpers_compiled' => 1,
        'output' => json_encode([
            'type' => 'bundle_publish',
            'helpers_total' => 1,
            'helpers_compiled' => 1,
            'helpers_skipped' => 0,
            'compiled' => [['id' => 1, 'name' => 'safeHelper']],
            'skipped' => [],
        ]),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.runtime.reload.logs.export-json', $log));

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="runtime-reload-log-'.$log->id.'.json"')
        ->assertJsonPath('id', $log->id)
        ->assertJsonPath('output.type', 'bundle_publish');

    expect($response->getContent())->toContain('safeHelper')
        ->and($response->getContent())->not->toContain('return secretCode')
        ->and($response->getContent())->not->toContain('generated bundle content');
});

test('export text returns downloadable safe report', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'success',
        'mode' => 'docker',
        'containers_affected' => 1,
        'containers_ok' => 1,
        'containers_failed' => 0,
        'output' => json_encode([
            'type' => 'docker_refresh',
            'dry_run' => true,
            'would_recreate' => [['bot_id' => 4, 'bot_name' => 'Bot Four', 'container_name' => 'bothost-bot-4', 'reason' => 'bundle mount missing']],
        ]),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.runtime.reload.logs.export-text', $log));

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="runtime-reload-log-'.$log->id.'.txt"');

    expect($response->getContent())->toContain('BotHost Pro Runtime Reload Report')
        ->and($response->getContent())->toContain('Bot Four')
        ->and($response->getContent())->not->toContain('NODE_RUNTIME_SECRET');
});

test('log detail page shows export buttons', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'success',
        'mode' => 'prepare_only',
    ]);

    $this->actingAs($admin)->get(route('admin.runtime.reload.show', $log))
        ->assertOk()
        ->assertSee('Download JSON Report')
        ->assertSee('Download Text Report');
});

test('export creates audit log entry', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'success',
        'mode' => 'prepare_only',
    ]);

    $this->actingAs($admin)->get(route('admin.runtime.reload.logs.export-json', $log))->assertOk();

    expect(AuditLog::query()->where('action', 'runtime.reload.export_json')->exists())->toBeTrue();
});

test('docker refresh live route rejects wrong confirmation text', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.runtime.reload.docker-refresh-live'), [
        'confirm_live_refresh' => 'wrong',
    ])->assertRedirect()
        ->assertSessionHas('error', 'Live Docker refresh requires exact confirmation.');

    expect(RuntimeReloadLog::query()->where('trigger_type', 'docker_refresh_live')->exists())->toBeFalse();
});

test('starting async reload is blocked when another task is active', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $launcher = fakeRuntimeReloadProcessLauncher();
    app()->instance(RuntimeReloadProcessLauncher::class, $launcher);

    RuntimeReloadLog::query()->create([
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'running',
        'mode' => 'docker',
        'current_step' => 'Inspecting containers',
    ]);

    $this->actingAs($admin)->post(route('admin.runtime.reload.publish-bundle'))
        ->assertRedirect()
        ->assertSessionHas('error', 'A runtime reload task is already running. Wait for it to finish before starting another one.');

    expect($launcher->started)->toBe([])
        ->and(RuntimeReloadLog::query()->where('trigger_type', 'manual_bundle_publish')->exists())->toBeFalse();
});

test('starting async reload is blocked when another task is pending', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $launcher = fakeRuntimeReloadProcessLauncher();
    app()->instance(RuntimeReloadProcessLauncher::class, $launcher);

    RuntimeReloadLog::query()->create([
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'pending',
        'mode' => 'prepare_only',
        'current_step' => 'Queued',
    ]);

    $this->actingAs($admin)->post(route('admin.runtime.reload.docker-refresh-plan'))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($launcher->started)->toBe([])
        ->and(RuntimeReloadLog::query()->where('trigger_type', 'docker_refresh_dry_run')->exists())->toBeFalse();
});

test('stale pending log is marked failed when starting a new task', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $launcher = fakeRuntimeReloadProcessLauncher();
    app()->instance(RuntimeReloadProcessLauncher::class, $launcher);

    $stale = RuntimeReloadLog::query()->create([
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'pending',
        'mode' => 'prepare_only',
        'current_step' => 'Queued',
    ]);
    $stale->timestamps = false;
    $stale->forceFill(['updated_at' => now()->subMinutes(6)])->save();

    $this->actingAs($admin)->post(route('admin.runtime.reload.docker-refresh-plan'))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($stale->fresh()->status)->toBe('failed')
        ->and($stale->fresh()->error)->toBe('Task marked failed because it became stale.')
        ->and(RuntimeReloadLog::query()->where('trigger_type', 'docker_refresh_dry_run')->where('status', 'pending')->exists())->toBeTrue()
        ->and($launcher->started)->toHaveCount(1);
});

test('cancel route marks running log cancelled', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'docker_refresh_dry_run',
        'status' => 'running',
        'mode' => 'docker',
        'current_step' => 'Inspecting containers',
        'started_at' => now()->subMinute(),
    ]);

    $this->actingAs($admin)->post(route('admin.runtime.reload.logs.cancel', $log))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($log->fresh()->status)->toBe('cancelled')
        ->and($log->fresh()->current_step)->toBe('Cancelled by admin')
        ->and($log->fresh()->error)->toBe('Cancelled by admin')
        ->and($log->fresh()->duration_ms)->not->toBeNull();
});

test('retry failed bundle publish creates new pending log', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $launcher = fakeRuntimeReloadProcessLauncher();
    app()->instance(RuntimeReloadProcessLauncher::class, $launcher);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'failed',
        'mode' => 'prepare_only',
        'current_step' => 'Failed',
    ]);

    $this->actingAs($admin)->post(route('admin.runtime.reload.logs.retry', $log))
        ->assertRedirect()
        ->assertSessionHas('status');

    $newLog = RuntimeReloadLog::query()->whereKeyNot($log->id)->latest()->first();

    expect($newLog->trigger_type)->toBe('manual_bundle_publish')
        ->and($newLog->status)->toBe('pending')
        ->and($launcher->started)->toHaveCount(1)
        ->and($launcher->started[0]['options']['publish_bundle'])->toBeTrue();
});

test('retry live docker refresh is blocked', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $launcher = fakeRuntimeReloadProcessLauncher();
    app()->instance(RuntimeReloadProcessLauncher::class, $launcher);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'docker_refresh_live',
        'status' => 'failed',
        'mode' => 'docker',
    ]);

    $this->actingAs($admin)->post(route('admin.runtime.reload.logs.retry', $log))
        ->assertRedirect()
        ->assertSessionHas('error', 'Live Docker refresh cannot be retried from log. Start it manually with confirmation.');

    expect($launcher->started)->toBe([]);
});

test('process launcher diagnostics returns expected structure', function () {
    $diagnostics = app(RuntimeReloadProcessLauncher::class)->diagnostics();

    expect($diagnostics)->toHaveKeys(['ok', 'php_binary', 'artisan_exists', 'logs_writable', 'proc_open_available', 'errors'])
        ->and($diagnostics['artisan_exists'])->toBeTrue()
        ->and(is_array($diagnostics['errors']))->toBeTrue();
});

test('status endpoint marks stale task failed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = RuntimeReloadLog::query()->create([
        'triggered_by' => $admin->id,
        'trigger_type' => 'manual_bundle_publish',
        'status' => 'pending',
        'mode' => 'prepare_only',
        'current_step' => 'Queued',
    ]);
    $log->timestamps = false;
    $log->forceFill(['updated_at' => now()->subMinutes(6)])->save();

    $this->actingAs($admin)->getJson(route('admin.runtime.reload.status', $log))
        ->assertOk()
        ->assertJsonPath('status', 'failed')
        ->assertJsonPath('completed', true)
        ->assertJsonPath('error', 'Task marked failed because it became stale.');
});

test('docker refresh live route accepts exact confirmation and creates log', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $launcher = fakeRuntimeReloadProcessLauncher();
    app()->instance(RuntimeReloadProcessLauncher::class, $launcher);

    $this->actingAs($admin)->post(route('admin.runtime.reload.docker-refresh-live'), [
        'confirm_live_refresh' => 'YES_RECREATE_DOCKER_CONTAINERS',
    ])->assertRedirect();

    $log = RuntimeReloadLog::query()->latest()->first();

    expect($log->trigger_type)->toBe('docker_refresh_live')
        ->and($log->status)->toBe('pending')
        ->and($log->mode)->toBe('docker')
        ->and($log->current_step)->toBe('Queued')
        ->and($launcher->started)->toHaveCount(1)
        ->and($launcher->started[0]['options']['dry_run'])->toBeFalse()
        ->and($launcher->started[0]['options']['confirm_live_refresh'])->toBeTrue();
});

test('runtime reload command dry run works', function () {
    $this->artisan('runtime:reload', ['--dry-run' => '1'])
        ->assertExitCode(0);

    expect(RuntimeReloadLog::query()->where('trigger_type', 'cli')->exists())->toBeTrue();
});

test('docker refresh dry run route creates pending async log', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $launcher = fakeRuntimeReloadProcessLauncher();
    app()->instance(RuntimeReloadProcessLauncher::class, $launcher);

    $this->actingAs($admin)->post(route('admin.runtime.reload.docker-refresh-plan'))
        ->assertRedirect()
        ->assertSessionHas('status');

    $log = RuntimeReloadLog::query()->latest()->first();

    expect($log->trigger_type)->toBe('docker_refresh_dry_run')
        ->and($log->status)->toBe('pending')
        ->and($log->mode)->toBe('docker')
        ->and($log->current_step)->toBe('Queued')
        ->and($launcher->started)->toHaveCount(1)
        ->and($launcher->started[0]['options']['docker_refresh'])->toBeTrue()
        ->and($launcher->started[0]['options']['dry_run'])->toBeTrue();
});
