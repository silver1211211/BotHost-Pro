<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        $run = $this->runDocker([
            'run',
            '-d',
            '--name', $name,
            '--label', 'bothost.runtime=bot',
            '--label', 'bothost.bot_id='.$bot->id,
            '--restart', 'unless-stopped',
            '--memory', (string) config('runtime.docker.memory_limit'),
            '--cpus', (string) config('runtime.docker.cpu_limit'),
            '--pids-limit', '64',
            '--read-only',
            '--tmpfs', '/tmp:rw,noexec,nosuid,size=16m',
            '--security-opt', 'no-new-privileges',
            '--cap-drop', 'ALL',
            '-e', 'NODE_ENV=production',
            '-e', 'HOST=0.0.0.0',
            '-e', 'PORT='.(string) config('runtime.docker.internal_port'),
            '-e', 'COMMAND_TIMEOUT_MS='.(string) $this->settings->integer('command_timeout_ms', (int) config('runtime.docker.timeout_ms')),
            '-e', 'COMMAND_MAX_DELAY_MS='.(string) $this->settings->integer('max_delay_ms', 10000),
            '-p', '127.0.0.1:'.$port.':'.config('runtime.docker.internal_port'),
            '--network', (string) config('runtime.docker.network'),
            (string) config('runtime.docker.image'),
        ], 30);

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
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->post($this->baseUrl($bot).'/execute', $payload);
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

        return $this->runDocker(['build', '-t', (string) config('runtime.docker.image'), $context], 180);
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

    private function inspectContainer(string $name): array
    {
        $result = $this->runDocker(['inspect', '--format', '{{.State.Running}}|{{.State.Status}}', $name], 8);

        if (! ($result['ok'] ?? false)) {
            return ['exists' => false, 'running' => false, 'status' => 'missing'];
        }

        [$running, $status] = array_pad(explode('|', trim($result['output'] ?? ''), 2), 2, null);

        return [
            'exists' => true,
            'running' => $running === 'true',
            'status' => $status ?: 'unknown',
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
