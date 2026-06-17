<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\NodeRuntimeConfig;
use Symfony\Component\Process\Process;
use Throwable;

class DockerRuntimeService
{
    public function __construct(private readonly RuntimeSettingsService $settings) {}

    public function startBotContainer(Bot $bot): array
    {
        $this->settings->all();

        if (! $this->dockerAvailable()) {
            return $this->fail($bot, 'Docker is not available on this server.', 'docker_unavailable');
        }

        $name = $this->containerName($bot);
        $port = $this->hostPort($bot);

        $existing = $this->inspectContainer($name);
        if (($existing['exists'] ?? false) && ($existing['running'] ?? false)) {
            $this->markContainer($bot, 'running', $name, $port);

            return $this->waitForHealthy($bot, 3);
        }

        if (($existing['exists'] ?? false) && ! ($existing['running'] ?? false)) {
            $start = $this->runDocker(['start', $name], 15);
            if (! $start['ok']) {
                return $this->fail($bot, $start['error'], 'start_failed');
            }

            $this->markContainer($bot, 'starting', $name, $port);

            return $this->waitForHealthy($bot, 8);
        }

        $this->ensureNetwork();

        $runArguments = [
            'run',
            '-d',
            '--name', $name,
            '--label', 'bothost.runtime=bot',
            '--label', 'bothost.bot_id='.$bot->id,
            '--label', 'com.bothost.helper_bundle_hash='.$this->adminHelperBundleHash(),
            '--restart', 'unless-stopped',
            '--memory', (string) config('runtime.docker.memory_limit'),
            '--cpus', (string) config('runtime.docker.cpu_limit'),
            '--pids-limit', '64',
            '--read-only',
            '--tmpfs', '/tmp:rw,noexec,nosuid,size=16m',
            '--security-opt', 'no-new-privileges',
            '--cap-drop', 'ALL',
        ];

        if ($this->shouldMountAdminHelperBundle()) {
            $runArguments[] = '-v';
            $runArguments[] = $this->adminHelperBundleHostPath().':'.$this->adminHelperBundleContainerPath().':ro';
        }

        $runArguments = array_merge($runArguments, [
            '-e', 'NODE_ENV=production',
            '-e', 'HOST=0.0.0.0',
            '-e', 'PORT='.(string) config('runtime.docker.internal_port'),
            '-e', 'COMMAND_TIMEOUT_MS='.(string) $this->settings->integer('command_timeout_ms', (int) config('runtime.docker.timeout_ms')),
            '-e', 'COMMAND_MAX_DELAY_MS='.(string) $this->settings->integer('max_delay_ms', 10000),
            '-e', 'NODE_RUNTIME_SECRET='.NodeRuntimeConfig::secret(),
            '-e', 'NODE_RUNTIME_INTERNAL_URL='.NodeRuntimeConfig::internalUrl(),
            '-e', 'BOTHOST_HELPER_BUNDLE_HASH='.$this->adminHelperBundleHash(),
            '-p', '127.0.0.1:'.$port.':'.config('runtime.docker.internal_port'),
            '--network', (string) config('runtime.docker.network'),
            (string) config('runtime.docker.image'),
        ]);

        $run = $this->runDocker($runArguments, 30);

        if (! $run['ok']) {
            return $this->fail($bot, $run['error'], 'create_failed');
        }

        $this->markContainer($bot, 'starting', $name, $port);

        return $this->waitForHealthy($bot, 10);
    }

    public function stopBotContainer(Bot $bot): array
    {
        $name = $this->containerName($bot);
        $status = $this->inspectContainer($name);

        if (! ($status['exists'] ?? false)) {
            $this->markContainer($bot, 'missing', $name, $this->hostPort($bot), 'stopped');

            return ['ok' => true, 'status' => 'missing'];
        }

        if ($status['running'] ?? false) {
            $stop = $this->runDocker(['stop', '--time', '5', $name], 15);
            if (! $stop['ok']) {
                return $this->fail($bot, $stop['error'], 'stop_failed');
            }
        }

        $this->markContainer($bot, 'stopped', $name, $this->hostPort($bot), 'stopped');

        return ['ok' => true, 'status' => 'stopped'];
    }

    public function restartBotContainer(Bot $bot): array
    {
        $this->stopBotContainer($bot);
        $result = $this->startBotContainer($bot);

        $bot->forceFill(['runtime_restarted_at' => now()])->saveQuietly();

        return $result;
    }

    public function removeBotContainer(Bot $bot): array
    {
        $name = $this->containerName($bot);
        $status = $this->inspectContainer($name);

        if ($status['exists'] ?? false) {
            $remove = $this->runDocker(['rm', '-f', $name], 20);
            if (! $remove['ok']) {
                return $this->fail($bot, $remove['error'], 'remove_failed');
            }
        }

        Cache::forget($this->statusCacheKey($bot));
        $bot->forceFill([
            'runtime_status' => null,
            'container_status' => null,
            'container_name' => null,
            'runtime_http_port' => null,
            'last_runtime_error' => null,
            'last_runtime_heartbeat_at' => null,
            'runtime_started_at' => null,
            'runtime_restarted_at' => null,
        ])->saveQuietly();

        return ['ok' => true, 'status' => 'removed'];
    }

    public function ensureBotContainerRunning(Bot $bot): array
    {
        $status = $this->getBotContainerStatus($bot, useCache: true);

        if (($status['running'] ?? false) && ($status['healthy'] ?? false)) {
            return $status + ['ok' => true];
        }

        if (! $this->settings->boolean('runtime_auto_restart', (bool) config('runtime.docker.auto_restart'))) {
            return $this->fail($bot, 'Runtime container is not healthy.', 'unhealthy');
        }

        return $this->startBotContainer($bot);
    }

    public function getBotContainerStatus(Bot $bot, bool $useCache = false): array
    {
        if ($useCache) {
            try {
                $cached = Cache::get($this->statusCacheKey($bot));
                if (is_array($cached)) {
                    return $cached;
                }
            } catch (Throwable) {
                // Status cache is optional.
            }
        }

        $name = $this->containerName($bot);
        $port = $this->hostPort($bot);
        $container = $this->inspectContainer($name);
        $health = ['healthy' => false, 'health' => null];

        if ($container['running'] ?? false) {
            $health = $this->health($bot);
        }

        $status = [
            'ok' => true,
            'mode' => 'docker',
            'container_name' => $name,
            'host_port' => $port,
            'exists' => (bool) ($container['exists'] ?? false),
            'running' => (bool) ($container['running'] ?? false),
            'container_status' => $container['status'] ?? 'missing',
            'healthy' => (bool) ($health['healthy'] ?? false),
            'health' => $health['health'] ?? null,
        ];

        $runtimeStatus = $status['healthy'] ? 'running' : ($status['running'] ? 'unhealthy' : 'stopped');
        $bot->forceFill([
            'runtime_mode' => 'docker',
            'runtime_status' => $runtimeStatus,
            'container_name' => $name,
            'container_status' => $status['container_status'],
            'runtime_http_port' => $port,
            'last_runtime_heartbeat_at' => $status['healthy'] ? now() : $bot->last_runtime_heartbeat_at,
            'last_runtime_error' => $status['healthy'] ? null : $bot->last_runtime_error,
        ])->saveQuietly();

        try {
            Cache::put($this->statusCacheKey($bot), $status, 10);
        } catch (Throwable) {
            // Status cache is optional.
        }

        return $status;
    }

    public function executeCommandInContainer(Bot $bot, array $payload): array
    {
        $running = $this->ensureBotContainerRunning($bot);

        if (! ($running['ok'] ?? false) || ! ($running['healthy'] ?? false)) {
            return [
                'ok' => false,
                'execution_id' => null,
                'execution_time_ms' => null,
                'replies' => [],
                'error' => $running['error'] ?? 'Runtime container is unavailable.',
                'error_type' => 'RuntimeUnavailable',
            ];
        }

        $timeout = max(2, (int) ceil($this->settings->integer('command_timeout_ms', (int) config('runtime.docker.timeout_ms')) / 1000) + 1);

        try {
            $request = Http::timeout($timeout)->acceptJson();
            $secret = NodeRuntimeConfig::secret();

            if (filled($secret)) {
                $request = $request->withHeaders(['X-Runtime-Secret' => $secret]);
            }

            $response = $request->post($this->baseUrl($bot).'/execute', $payload);
        } catch (ConnectionException $exception) {
            $this->fail($bot, 'Runtime bridge unavailable.', 'execute_unavailable', $exception);

            return [
                'ok' => false,
                'execution_id' => null,
                'execution_time_ms' => null,
                'replies' => [],
                'error' => 'Runtime bridge unavailable.',
                'error_type' => 'RuntimeUnavailable',
            ];
        } catch (Throwable $exception) {
            $this->fail($bot, 'Runtime request failed.', 'execute_failed', $exception);

            return [
                'ok' => false,
                'execution_id' => null,
                'execution_time_ms' => null,
                'replies' => [],
                'error' => 'Runtime request failed.',
                'error_type' => 'RuntimeRequestFailed',
            ];
        }

        return $this->jsonPayload($response) ?: [
            'ok' => false,
            'execution_id' => null,
            'execution_time_ms' => null,
            'replies' => [],
            'error' => 'Runtime returned an invalid response.',
            'error_type' => 'InvalidRuntimeResponse',
        ];
    }

    public function dockerAvailable(): bool
    {
        return $this->runDocker(['version', '--format', '{{.Server.Version}}'], 5)['ok'] ?? false;
    }

    public function imageExists(): bool
    {
        $this->settings->all();

        return $this->runDocker(['image', 'inspect', (string) config('runtime.docker.image')], 8)['ok'] ?? false;
    }

    public function buildImage(): array
    {
        $this->settings->all();

        $context = base_path((string) config('runtime.docker.build_context'));

        return $this->runDocker([
            'build',
            '--build-arg',
            'BOTHOST_RUNTIME_SOURCE_HASH='.$this->runtimeSourceHash(),
            '-t',
            (string) config('runtime.docker.image'),
            $context,
        ], 180);
    }

    public function cleanupOrphans(): array
    {
        $list = $this->runDocker(['ps', '-a', '--filter', 'label=bothost.runtime=bot', '--format', '{{.Names}}|{{.Label "bothost.bot_id"}}'], 15);
        if (! ($list['ok'] ?? false)) {
            return ['ok' => false, 'removed' => 0, 'error' => $list['error'] ?? 'Unable to list containers.'];
        }

        $removed = 0;
        foreach (preg_split('/\R/', trim($list['output'] ?? '')) ?: [] as $line) {
            if ($line === '') {
                continue;
            }

            [$name, $botId] = array_pad(explode('|', $line, 2), 2, null);
            if (! $botId || Bot::withTrashed()->whereKey($botId)->exists()) {
                continue;
            }

            $remove = $this->runDocker(['rm', '-f', $name], 20);
            if ($remove['ok']) {
                $removed++;
            }
        }

        return ['ok' => true, 'removed' => $removed];
    }

    public function activeContainerSummary(): array
    {
        $list = $this->runDocker(['ps', '-a', '--filter', 'label=bothost.runtime=bot', '--format', '{{.Names}}|{{.Status}}'], 10);
        if (! ($list['ok'] ?? false)) {
            return ['available' => $this->dockerAvailable(), 'active' => 0, 'unhealthy' => 0, 'error' => $list['error'] ?? null];
        }

        $active = 0;
        $unhealthy = 0;
        foreach (preg_split('/\R/', trim($list['output'] ?? '')) ?: [] as $line) {
            if ($line === '') {
                continue;
            }
            $active++;
            if (str_contains(strtolower($line), 'unhealthy') || ! str_contains(strtolower($line), 'up')) {
                $unhealthy++;
            }
        }

        return ['available' => true, 'active' => $active, 'unhealthy' => $unhealthy, 'error' => null];
    }

    public function recreateBotContainerForHelperBundle(Bot $bot, bool $force = false): array
    {
        $containerName = (string) $bot->container_name;

        if ($bot->runtime_mode !== 'docker' || ! filled($containerName)) {
            return [
                'ok' => false,
                'bot_id' => $bot->id,
                'container_name' => $containerName ?: null,
                'action' => 'skipped',
                'reason' => 'bot is not configured for Docker runtime',
                'error' => 'Bot is not configured for Docker runtime.',
            ];
        }

        if (! $this->shouldMountAdminHelperBundle()) {
            return [
                'ok' => false,
                'bot_id' => $bot->id,
                'container_name' => $containerName,
                'action' => 'skipped',
                'reason' => 'bundle file missing',
                'error' => 'Helper bundle file is missing or unreadable.',
            ];
        }

        $support = $this->inspectAdminRuntimeSupport($containerName);

        if (! $force && ($support['ok'] ?? false) && ($support['ready'] ?? false)) {
            return [
                'ok' => true,
                'bot_id' => $bot->id,
                'container_name' => $containerName,
                'action' => 'skipped',
                'reason' => 'runtime support already up to date',
                'error' => null,
            ];
        }

        if (($support['ok'] ?? false) && ! ($support['exists'] ?? false)) {
            return [
                'ok' => false,
                'bot_id' => $bot->id,
                'container_name' => $containerName,
                'action' => 'skipped',
                'reason' => 'container not found',
                'error' => 'Container not found.',
            ];
        }

        if (($support['ok'] ?? false) && ! ($support['running'] ?? false)) {
            return [
                'ok' => false,
                'bot_id' => $bot->id,
                'container_name' => $containerName,
                'action' => 'skipped',
                'reason' => 'container not running',
                'error' => 'Container is not running.',
            ];
        }

        if (! ($support['ok'] ?? false)) {
            return [
                'ok' => false,
                'bot_id' => $bot->id,
                'container_name' => $containerName,
                'action' => 'failed',
                'reason' => 'inspect failed',
                'error' => $this->sanitize((string) ($support['error'] ?? 'Docker inspect failed.')),
            ];
        }

        $port = $this->hostPort($bot);
        $stop = $this->stopBotContainer($bot);

        if (! ($stop['ok'] ?? false)) {
            return [
                'ok' => false,
                'bot_id' => $bot->id,
                'container_name' => $containerName,
                'action' => 'failed',
                'reason' => 'stop failed',
                'error' => $this->sanitize((string) ($stop['error'] ?? 'Unable to stop container.')),
            ];
        }

        $remove = $this->removeBotContainer($bot);

        if (! ($remove['ok'] ?? false)) {
            return [
                'ok' => false,
                'bot_id' => $bot->id,
                'container_name' => $containerName,
                'action' => 'failed',
                'reason' => 'remove failed',
                'error' => $this->sanitize((string) ($remove['error'] ?? 'Unable to remove container.')),
            ];
        }

        $bot->forceFill([
            'runtime_mode' => 'docker',
            'runtime_http_port' => $port,
        ])->saveQuietly();

        $start = $this->startBotContainer($bot->fresh() ?: $bot);

        if (! ($start['ok'] ?? false)) {
            return [
                'ok' => false,
                'bot_id' => $bot->id,
                'container_name' => $containerName,
                'action' => 'failed',
                'reason' => 'start failed',
                'error' => $this->sanitize((string) ($start['error'] ?? 'Unable to start recreated container.')),
            ];
        }

        return [
            'ok' => true,
            'bot_id' => $bot->id,
            'container_name' => $containerName,
            'action' => 'recreated',
            'reason' => $support['reason'] ?? 'runtime support updated',
            'error' => null,
        ];
    }

    private function waitForHealthy(Bot $bot, int $seconds): array
    {
        $deadline = microtime(true) + $seconds;

        do {
            $status = $this->getBotContainerStatus($bot);
            if (($status['running'] ?? false) && ($status['healthy'] ?? false)) {
                $bot->forceFill([
                    'runtime_status' => 'running',
                    'runtime_started_at' => $bot->runtime_started_at ?: now(),
                    'last_runtime_error' => null,
                ])->saveQuietly();

                return $status + ['ok' => true];
            }

            usleep(250000);
        } while (microtime(true) < $deadline);

        return $this->fail($bot, 'Runtime container did not become healthy.', 'health_timeout');
    }

    private function health(Bot $bot): array
    {
        try {
            $response = Http::timeout(2)->acceptJson()->get($this->baseUrl($bot).'/health');
            $payload = $this->jsonPayload($response);

            return [
                'healthy' => $response->successful() && is_array($payload) && ($payload['ok'] ?? false) === true,
                'health' => is_array($payload) ? $payload : null,
            ];
        } catch (Throwable) {
            return ['healthy' => false, 'health' => null];
        }
    }

    public function inspectContainer(string $containerName): array
    {
        $result = $this->runDocker(['inspect', '--format', '{{json .State.Running}}'.PHP_EOL.'{{json .State.Status}}'.PHP_EOL.'{{json .Mounts}}'.PHP_EOL.'{{json .Config.Labels}}'.PHP_EOL.'{{json .Config.Env}}'.PHP_EOL.'{{json .NetworkSettings.Ports}}', $containerName], 5);

        if (! ($result['ok'] ?? false)) {
            $error = (string) ($result['error'] ?? '');

            if (str_contains(strtolower($error), 'no such object') || str_contains(strtolower($error), 'no such container')) {
                return [
                    'ok' => true,
                    'exists' => false,
                    'running' => false,
                    'status' => 'missing',
                    'mounts' => [],
                    'labels' => [],
                    'env' => [],
                    'ports' => [],
                    'raw' => null,
                    'error' => null,
                ];
            }

            return [
                'ok' => false,
                'exists' => false,
                'running' => false,
                'status' => 'unknown',
                'mounts' => [],
                'labels' => [],
                'env' => [],
                'ports' => [],
                'raw' => null,
                'error' => $this->sanitize($error ?: 'Docker inspect failed.'),
            ];
        }

        [$runningJson, $statusJson, $mountsJson, $labelsJson, $envJson, $portsJson] = array_pad(preg_split('/\R/', trim($result['output'] ?? ''), 6), 6, null);
        $running = json_decode((string) $runningJson, true) === true;
        $status = json_decode((string) $statusJson, true);
        $labels = json_decode((string) $labelsJson, true);
        $env = json_decode((string) $envJson, true);
        $ports = json_decode((string) $portsJson, true);
        $mounts = collect(json_decode((string) $mountsJson, true) ?: [])
            ->map(fn (array $mount) => [
                'source' => $mount['Source'] ?? null,
                'destination' => $mount['Destination'] ?? null,
                'mode' => $mount['Mode'] ?? null,
                'rw' => $mount['RW'] ?? null,
                'type' => $mount['Type'] ?? null,
            ])
            ->values()
            ->all();

        return [
            'ok' => true,
            'exists' => true,
            'running' => $running,
            'status' => is_string($status) && $status !== '' ? $status : 'unknown',
            'mounts' => $mounts,
            'labels' => is_array($labels) ? $labels : [],
            'env' => is_array($env) ? $env : [],
            'ports' => is_array($ports) ? $ports : [],
            'raw' => null,
            'error' => null,
        ];
    }

    public function hasAdminHelperBundleMount(string $containerName): array
    {
        $inspect = $this->inspectContainer($containerName);

        if (! ($inspect['ok'] ?? false)) {
            return [
                'ok' => false,
                'exists' => false,
                'running' => false,
                'mounted' => false,
                'read_only' => false,
                'reason' => 'inspect failed',
                'error' => $inspect['error'] ?? 'Docker inspect failed.',
            ];
        }

        if (! ($inspect['exists'] ?? false)) {
            return [
                'ok' => true,
                'exists' => false,
                'running' => false,
                'mounted' => false,
                'read_only' => false,
                'reason' => 'container not found',
                'error' => null,
            ];
        }

        if (! ($inspect['running'] ?? false)) {
            return [
                'ok' => true,
                'exists' => true,
                'running' => false,
                'mounted' => false,
                'read_only' => false,
                'reason' => 'container not running',
                'error' => null,
            ];
        }

        $mount = collect($inspect['mounts'] ?? [])
            ->first(fn (array $mount) => ($mount['destination'] ?? null) === $this->adminHelperBundleContainerPath());

        if (! $mount) {
            return [
                'ok' => true,
                'exists' => true,
                'running' => true,
                'mounted' => false,
                'read_only' => false,
                'reason' => 'bundle mount missing',
                'error' => null,
            ];
        }

        $readOnly = ($mount['rw'] ?? true) === false || str_contains((string) ($mount['mode'] ?? ''), 'ro');

        return [
            'ok' => true,
            'exists' => true,
            'running' => true,
            'mounted' => true,
            'read_only' => $readOnly,
            'reason' => $readOnly ? 'bundle mount present' : 'bundle mounted but not read-only',
            'error' => null,
        ];
    }

    public function inspectAdminRuntimeSupport(string $containerName): array
    {
        $inspect = $this->inspectContainer($containerName);

        if (! ($inspect['ok'] ?? false)) {
            return [
                'ok' => false,
                'exists' => false,
                'running' => false,
                'mounted' => false,
                'read_only' => false,
                'ready' => false,
                'runtime_hash' => null,
                'expected_runtime_hash' => $this->runtimeSourceHash(),
                'runtime_hash_matches' => false,
                'helper_bundle_hash' => null,
                'expected_helper_bundle_hash' => $this->adminHelperBundleHash(),
                'helper_bundle_hash_matches' => false,
                'helper_loader_supported' => false,
                'reason' => 'inspect failed',
                'error' => $inspect['error'] ?? 'Docker inspect failed.',
            ];
        }

        if (! ($inspect['exists'] ?? false)) {
            return [
                'ok' => true,
                'exists' => false,
                'running' => false,
                'mounted' => false,
                'read_only' => false,
                'ready' => false,
                'runtime_hash' => null,
                'expected_runtime_hash' => $this->runtimeSourceHash(),
                'runtime_hash_matches' => false,
                'helper_bundle_hash' => null,
                'expected_helper_bundle_hash' => $this->adminHelperBundleHash(),
                'helper_bundle_hash_matches' => false,
                'helper_loader_supported' => false,
                'reason' => 'container not found',
                'error' => null,
            ];
        }

        if (! ($inspect['running'] ?? false)) {
            return [
                'ok' => true,
                'exists' => true,
                'running' => false,
                'mounted' => false,
                'read_only' => false,
                'ready' => false,
                'runtime_hash' => $this->containerRuntimeSourceHash($inspect),
                'expected_runtime_hash' => $this->runtimeSourceHash(),
                'runtime_hash_matches' => false,
                'helper_bundle_hash' => $this->containerHelperBundleHash($inspect),
                'expected_helper_bundle_hash' => $this->adminHelperBundleHash(),
                'helper_bundle_hash_matches' => false,
                'helper_loader_supported' => false,
                'reason' => 'container not running',
                'error' => null,
            ];
        }

        $mount = collect($inspect['mounts'] ?? [])
            ->first(fn (array $mount) => ($mount['destination'] ?? null) === $this->adminHelperBundleContainerPath());
        $mounted = $mount !== null;
        $readOnly = $mounted && (($mount['rw'] ?? true) === false || str_contains((string) ($mount['mode'] ?? ''), 'ro'));
        $expectedHash = $this->runtimeSourceHash();
        $containerHash = $this->containerRuntimeSourceHash($inspect);
        $hashMatches = is_string($containerHash) && hash_equals($expectedHash, $containerHash);
        $expectedHelperHash = $this->adminHelperBundleHash();
        $containerHelperHash = $this->containerHelperBundleHash($inspect);
        $helperHashMatches = is_string($expectedHelperHash)
            && is_string($containerHelperHash)
            && hash_equals($expectedHelperHash, $containerHelperHash);
        $helperLoaderSupported = is_string($containerHash) && $containerHash !== '';
        $localhostOnly = $this->hasLocalhostOnlyPortBinding($inspect);
        $reason = 'runtime support already up to date';

        if (! $mounted) {
            $reason = 'bundle mount missing';
        } elseif (! $readOnly) {
            $reason = 'bundle mounted but not read-only';
        } elseif (! $helperLoaderSupported) {
            $reason = 'missing helper loader support';
        } elseif (! $hashMatches) {
            $reason = 'runtime source hash outdated';
        } elseif (is_string($containerHelperHash) && ! $helperHashMatches) {
            $reason = 'helper bundle hash outdated';
        } elseif (! $localhostOnly) {
            $reason = 'port binding is not localhost-only';
        }

        return [
            'ok' => true,
            'exists' => true,
            'running' => true,
            'mounted' => $mounted,
            'read_only' => $readOnly,
            'ready' => $mounted
                && $readOnly
                && $helperLoaderSupported
                && $hashMatches
                && (! is_string($containerHelperHash) || $helperHashMatches)
                && $localhostOnly,
            'runtime_hash' => $containerHash,
            'expected_runtime_hash' => $expectedHash,
            'runtime_hash_matches' => $hashMatches,
            'helper_bundle_hash' => $containerHelperHash,
            'expected_helper_bundle_hash' => $expectedHelperHash,
            'helper_bundle_hash_matches' => $helperHashMatches,
            'helper_loader_supported' => $helperLoaderSupported,
            'localhost_only' => $localhostOnly,
            'reason' => $reason,
            'error' => null,
        ];
    }

    public function runtimeSourceHash(): string
    {
        $hash = hash_init('sha256');

        foreach ($this->runtimeSourceFiles() as $relativePath) {
            $path = base_path($relativePath);
            hash_update($hash, $relativePath."\n");
            hash_update($hash, is_file($path) ? (string) file_get_contents($path) : "__missing__\n");
        }

        return hash_final($hash);
    }

    public function adminHelperBundleHash(): ?string
    {
        $path = $this->adminHelperBundleHostPath();

        return is_file($path) ? hash_file('sha256', $path) ?: null : null;
    }

    public function runtimeSourceFiles(): array
    {
        return [
            'runtime-node/server.js',
            'runtime-node/admin-helper-loader.js',
            'runtime-node/package.json',
            'runtime-node/package-lock.json',
            'runtime-node/Dockerfile',
        ];
    }

    private function ensureNetwork(): void
    {
        $network = (string) config('runtime.docker.network');

        if ($network === '' || $this->runDocker(['network', 'inspect', $network], 8)['ok']) {
            return;
        }

        $this->runDocker(['network', 'create', $network], 10);
    }

    private function containerRuntimeSourceHash(array $inspect): ?string
    {
        $labels = $inspect['labels'] ?? [];
        if (is_array($labels) && filled($labels['com.bothost.runtime_source_hash'] ?? null)) {
            return (string) $labels['com.bothost.runtime_source_hash'];
        }

        foreach (($inspect['env'] ?? []) as $env) {
            if (! is_string($env) || ! str_starts_with($env, 'BOTHOST_RUNTIME_SOURCE_HASH=')) {
                continue;
            }

            return substr($env, strlen('BOTHOST_RUNTIME_SOURCE_HASH='));
        }

        return null;
    }

    private function containerHelperBundleHash(array $inspect): ?string
    {
        $labels = $inspect['labels'] ?? [];
        if (is_array($labels) && filled($labels['com.bothost.helper_bundle_hash'] ?? null)) {
            return (string) $labels['com.bothost.helper_bundle_hash'];
        }

        foreach (($inspect['env'] ?? []) as $env) {
            if (! is_string($env) || ! str_starts_with($env, 'BOTHOST_HELPER_BUNDLE_HASH=')) {
                continue;
            }

            return substr($env, strlen('BOTHOST_HELPER_BUNDLE_HASH='));
        }

        return null;
    }

    private function hasLocalhostOnlyPortBinding(array $inspect): bool
    {
        $ports = $inspect['ports'] ?? [];
        $internalPort = (string) config('runtime.docker.internal_port').'/tcp';
        $bindings = is_array($ports) ? ($ports[$internalPort] ?? null) : null;

        if (! is_array($bindings) || $bindings === []) {
            return false;
        }

        foreach ($bindings as $binding) {
            if (($binding['HostIp'] ?? null) !== '127.0.0.1') {
                return false;
            }
        }

        return true;
    }

    private function runDocker(array $arguments, int $timeout): array
    {
        try {
            $process = new Process(array_merge(['docker'], $arguments), base_path(), null, null, $timeout);
            $process->run();

            return [
                'ok' => $process->isSuccessful(),
                'output' => trim($process->getOutput()),
                'error' => $this->sanitize($process->getErrorOutput() ?: $process->getOutput()),
            ];
        } catch (Throwable $exception) {
            return ['ok' => false, 'output' => '', 'error' => $this->sanitize($exception->getMessage())];
        }
    }

    private function adminHelperBundleHostPath(): string
    {
        return base_path('runtime-node/admin-helpers-generated.js');
    }

    private function adminHelperBundleContainerPath(): string
    {
        return '/app/admin-helpers-generated.js';
    }

    private function shouldMountAdminHelperBundle(): bool
    {
        $path = $this->adminHelperBundleHostPath();

        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $realPath = realpath($path);
        $runtimeRoot = realpath(base_path('runtime-node'));

        if ($realPath === false || $runtimeRoot === false) {
            return false;
        }

        return str_starts_with($realPath, rtrim($runtimeRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
    }

    private function fail(Bot $bot, string $message, string $reason, ?Throwable $exception = null): array
    {
        $safeMessage = $this->sanitize($message);
        $bot->forceFill([
            'runtime_mode' => 'docker',
            'runtime_status' => 'error',
            'container_name' => $this->containerName($bot),
            'container_status' => $reason,
            'runtime_http_port' => $this->hostPort($bot),
            'last_runtime_error' => $safeMessage,
        ])->saveQuietly();

        if ($this->settings->boolean('log_backend_runtime_errors', true) && $this->settings->boolean('log_docker_errors', true)) {
            Log::error('Docker runtime error', [
                'category' => 'docker_runtime',
                'bot_id' => $bot->id,
                'reason' => $reason,
                'error' => $safeMessage,
                'exception' => $exception ? $this->sanitize($exception->getMessage()) : null,
            ]);
        }

        return ['ok' => false, 'healthy' => false, 'error' => $safeMessage, 'reason' => $reason];
    }

    private function markContainer(Bot $bot, string $containerStatus, string $name, int $port, ?string $runtimeStatus = null): void
    {
        $bot->forceFill([
            'runtime_mode' => 'docker',
            'runtime_status' => $runtimeStatus ?: $containerStatus,
            'container_name' => $name,
            'container_status' => $containerStatus,
            'runtime_http_port' => $port,
        ])->saveQuietly();
    }

    private function jsonPayload(Response $response): ?array
    {
        try {
            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function baseUrl(Bot $bot): string
    {
        return 'http://127.0.0.1:'.$this->hostPort($bot);
    }

    private function containerName(Bot $bot): string
    {
        return config('runtime.docker.container_prefix').'-'.$bot->id;
    }

    private function hostPort(Bot $bot): int
    {
        return (int) ($bot->runtime_http_port ?: ((int) config('runtime.docker.http_port_start') + (int) $bot->id));
    }

    private function statusCacheKey(Bot $bot): string
    {
        return 'runtime:docker:status:'.$bot->id;
    }

    private function sanitize(string $value): string
    {
        return str($value)
            ->replaceMatches('/\d{6,}:[A-Za-z0-9_-]{20,}/', '[redacted-token]')
            ->replaceMatches('/(password|secret|token|api[_-]?key)=\S+/i', '$1=[redacted]')
            ->limit(1000, '')
            ->toString();
    }
}
