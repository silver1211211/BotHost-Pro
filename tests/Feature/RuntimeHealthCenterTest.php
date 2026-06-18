<?php

use App\Models\AdminRuntimeActionLog;
use App\Models\Bot;
use App\Models\User;
use App\Services\AdminRuntimeHealthService;

function runtimeHealthAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function runtimeHealthBot(array $attributes = []): Bot
{
    $user = User::factory()->create();

    return Bot::query()->create($attributes + [
        'user_id' => $user->id,
        'name' => 'Runtime Health Bot',
        'slug' => 'runtime-health-bot-'.uniqid(),
        'token_encrypted' => '123456:RUNTIMEHEALTHSECRET'.strtoupper(uniqid()),
        'status' => 'running',
        'runtime_mode' => 'docker',
        'container_name' => 'bothost-runtime-health',
        'container_status' => 'running',
        'runtime_http_port' => 45555,
    ]);
}

function runtimeHealthReport(): array
{
    return [
        'ok' => true,
        'summary' => [
            'runtime_status' => 'needs_attention',
            'bundle_exists' => true,
            'containers_checked' => 1,
            'issues_found' => 1,
        ],
        'helper_bundle' => [
            'exists' => true,
            'current_hash' => 'helper-hash',
            'expected_hash' => 'helper-hash',
            'matches' => true,
            'last_publish_at' => null,
        ],
        'runtime_source' => [
            'current_hash' => 'runtime-hash',
            'expected_hash' => 'runtime-hash',
            'matches' => true,
        ],
        'bots' => [[
            'bot_id' => 1,
            'bot_name' => 'Runtime Health Bot',
            'runtime_mode' => 'docker',
            'container_name' => 'bothost-runtime-health',
            'container_status' => 'running',
            'runtime_http_port' => 45555,
            'helper_bundle_hash_matches' => false,
            'runtime_hash_matches' => true,
            'localhost_only' => true,
            'mounted' => true,
            'read_only' => true,
            'action_needed' => 'recreate',
            'reason' => 'helper bundle hash mismatch',
        ]],
        'queue' => [
            'connection' => 'database',
            'restart_timestamp' => null,
            'supervisor_status' => 'not checked',
        ],
        'cache' => [
            'app_environment' => 'testing',
            'debug' => true,
            'config_cached' => false,
            'routes_cached' => false,
        ],
        'bridge' => [
            'status' => 'not checked',
            'message' => 'Runtime bridge health is checked from each bot container without exposing tokens.',
        ],
    ];
}

function fakeRuntimeHealthService(array &$calls): AdminRuntimeHealthService
{
    return new class($calls) extends AdminRuntimeHealthService {
        public function __construct(private array &$calls) {}

        public function healthReport(): array
        {
            $this->calls['health_report'] = ($this->calls['health_report'] ?? 0) + 1;

            return runtimeHealthReport();
        }

        public function logHealthCheck(User $admin, array $report): AdminRuntimeActionLog
        {
            $this->calls['health_check'] = ($this->calls['health_check'] ?? 0) + 1;

            return AdminRuntimeActionLog::query()->create([
                'admin_user_id' => $admin->id,
                'action' => 'health_check',
                'status' => 'success',
                'summary' => 'Runtime health check completed.',
                'payload_json' => $report,
                'started_at' => now(),
                'finished_at' => now(),
            ]);
        }

        public function forceApplyHelpers(User $admin): array
        {
            $this->calls['force_apply_helpers'] = ($this->calls['force_apply_helpers'] ?? 0) + 1;

            AdminRuntimeActionLog::query()->create([
                'admin_user_id' => $admin->id,
                'action' => 'force_apply_helpers',
                'status' => 'success',
                'summary' => 'Force apply completed.',
                'payload_json' => ['ok' => true],
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            return ['ok' => true, 'summary' => 'Force apply completed.'];
        }

        public function forceRuntimeRefresh(User $admin): array
        {
            $this->calls['force_runtime_refresh'] = ($this->calls['force_runtime_refresh'] ?? 0) + 1;

            AdminRuntimeActionLog::query()->create([
                'admin_user_id' => $admin->id,
                'action' => 'force_runtime_refresh',
                'status' => 'success',
                'summary' => 'Runtime refresh completed.',
                'payload_json' => ['ok' => true],
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            return ['ok' => true, 'summary' => 'Runtime refresh completed.'];
        }

        public function recreateBot(User $admin, Bot $bot): array
        {
            $this->calls['recreate_bot'] = $bot->id;

            return ['ok' => true, 'summary' => "Runtime recreated for bot #{$bot->id}."];
        }

        public function recreateAll(User $admin): array
        {
            $this->calls['recreate_all'] = ($this->calls['recreate_all'] ?? 0) + 1;

            return ['ok' => true, 'summary' => 'Recreated 2 runtime(s), skipped 0, failed 0.'];
        }

        public function clearCache(User $admin): array
        {
            $this->calls['clear_cache'] = ($this->calls['clear_cache'] ?? 0) + 1;

            AdminRuntimeActionLog::query()->create([
                'admin_user_id' => $admin->id,
                'action' => 'clear_cache',
                'status' => 'success',
                'summary' => 'Laravel cache cleared.',
                'payload_json' => ['ok' => true],
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            return ['ok' => true, 'summary' => 'Laravel cache cleared.'];
        }

        public function restartQueue(User $admin): array
        {
            $this->calls['restart_queue'] = ($this->calls['restart_queue'] ?? 0) + 1;

            AdminRuntimeActionLog::query()->create([
                'admin_user_id' => $admin->id,
                'action' => 'queue_restart',
                'status' => 'success',
                'summary' => 'Queue workers restart signal sent.',
                'payload_json' => ['ok' => true],
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            return ['ok' => true, 'summary' => 'Queue workers restart signal sent.'];
        }
    };
}

test('runtime health center is admin only and renders the dashboard', function () {
    $calls = [];
    app()->instance(AdminRuntimeHealthService::class, fakeRuntimeHealthService($calls));
    runtimeHealthBot();

    $this->actingAs(User::factory()->create(['role' => 'user', 'status' => 'active']))
        ->get(route('admin.runtime.health.index'))
        ->assertForbidden();

    $this->actingAs(runtimeHealthAdmin())
        ->get(route('admin.runtime.health.index'))
        ->assertOk()
        ->assertSee('Runtime Health Center')
        ->assertSee('Force Apply Runtime Helpers')
        ->assertSee('Runtime Health Bot');
});

test('health check logs safely without running destructive actions', function () {
    $admin = runtimeHealthAdmin();
    $calls = [];
    app()->instance(AdminRuntimeHealthService::class, fakeRuntimeHealthService($calls));

    $this->actingAs($admin)
        ->post(route('admin.runtime.health.check'))
        ->assertRedirect()
        ->assertSessionHas('status', 'Runtime health check completed.');

    expect($calls['health_report'])->toBe(1)
        ->and($calls['health_check'])->toBe(1)
        ->and($calls['force_apply_helpers'] ?? 0)->toBe(0);

    expect(AdminRuntimeActionLog::query()->where('action', 'health_check')->where('status', 'success')->exists())->toBeTrue();
});

test('force apply and runtime refresh require exact confirmation and log admin actions', function () {
    $admin = runtimeHealthAdmin();
    $calls = [];
    app()->instance(AdminRuntimeHealthService::class, fakeRuntimeHealthService($calls));

    $this->actingAs($admin)
        ->post(route('admin.runtime.health.force-apply-helpers'), ['confirm_force_apply' => 'wrong'])
        ->assertRedirect()
        ->assertSessionHas('error', 'Force Apply Runtime Helpers requires exact confirmation.');

    expect($calls['force_apply_helpers'] ?? 0)->toBe(0);

    $this->actingAs($admin)
        ->post(route('admin.runtime.health.force-apply-helpers'), ['confirm_force_apply' => 'FORCE_APPLY_RUNTIME_HELPERS'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Force apply completed.');

    $this->actingAs($admin)
        ->post(route('admin.runtime.health.force-runtime-refresh'), ['confirm_runtime_refresh' => 'FORCE_RUNTIME_REFRESH'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Runtime refresh completed.');

    expect($calls['force_apply_helpers'])->toBe(1)
        ->and($calls['force_runtime_refresh'])->toBe(1)
        ->and(AdminRuntimeActionLog::query()->where('action', 'force_apply_helpers')->exists())->toBeTrue()
        ->and(AdminRuntimeActionLog::query()->where('action', 'force_runtime_refresh')->exists())->toBeTrue();
});

test('selected bot recreate targets only the requested bot', function () {
    $admin = runtimeHealthAdmin();
    $bot = runtimeHealthBot(['name' => 'Selected Runtime Bot', 'slug' => 'selected-runtime-bot']);
    runtimeHealthBot(['name' => 'Other Runtime Bot', 'slug' => 'other-runtime-bot', 'container_name' => 'bothost-other']);
    $calls = [];
    app()->instance(AdminRuntimeHealthService::class, fakeRuntimeHealthService($calls));

    $this->actingAs($admin)
        ->post(route('admin.runtime.health.recreate-bot'), [
            'bot_id' => $bot->id,
            'confirm_recreate_bot' => 'RECREATE_SELECTED_BOT_RUNTIME',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', "Runtime recreated for bot #{$bot->id}.");

    expect($calls['recreate_bot'])->toBe($bot->id);
});

test('recreate all clear cache and queue restart stay confirmation guarded', function () {
    $admin = runtimeHealthAdmin();
    $calls = [];
    app()->instance(AdminRuntimeHealthService::class, fakeRuntimeHealthService($calls));

    $this->actingAs($admin)
        ->post(route('admin.runtime.health.recreate-all'), ['confirm_recreate_all' => 'wrong'])
        ->assertRedirect()
        ->assertSessionHas('error', 'Force Recreate All Bot Runtimes requires exact confirmation.');

    $this->actingAs($admin)
        ->post(route('admin.runtime.health.recreate-all'), ['confirm_recreate_all' => 'RECREATE_ALL_BOT_RUNTIMES'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Recreated 2 runtime(s), skipped 0, failed 0.');

    $this->actingAs($admin)
        ->post(route('admin.runtime.health.clear-cache'), ['confirm_clear_cache' => 'CLEAR_LARAVEL_CACHE'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Laravel cache cleared.');

    $this->actingAs($admin)
        ->post(route('admin.runtime.health.restart-queue'), ['confirm_queue_restart' => 'RESTART_QUEUE_WORKERS'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Queue workers restart signal sent.');

    expect($calls['recreate_all'])->toBe(1)
        ->and($calls['clear_cache'])->toBe(1)
        ->and($calls['restart_queue'])->toBe(1);
});

test('runtime action logs redact token and host path values', function () {
    $admin = runtimeHealthAdmin();
    $service = app(AdminRuntimeHealthService::class);

    $log = $service->logHealthCheck($admin, [
        'bot_token' => '123456:THISSECRETISLONG',
        'runtime_path' => 'C:\\laragon\\www\\bot\\.env',
        'nested' => ['api_key' => 'secret-api-key-value'],
    ]);

    $payload = json_encode($log->fresh()->payload_json);

    expect($payload)->not->toContain('THISSECRETISLONG')
        ->and($payload)->not->toContain('laragon')
        ->and($payload)->not->toContain('secret-api-key-value')
        ->and($payload)->toContain('[redacted]');
});

test('runtime helper pages expose force apply shortcut', function () {
    $admin = runtimeHealthAdmin();
    runtimeHealthBot();

    $this->actingAs($admin)
        ->get(route('admin.runtime.helpers.index'))
        ->assertOk()
        ->assertSee('Force Apply Runtime Helpers');

    $this->actingAs($admin)
        ->get(route('admin.runtime.reload.index'))
        ->assertOk()
        ->assertSee('Force Apply Runtime Helpers')
        ->assertSee('Open Runtime Health Center');
});
