<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RuntimeSettingsService
{
    private const CACHE_KEY = 'runtime:settings';

    private ?array $settingsCache = null;

    private bool $runtimeConfigApplied = false;

    public function all(): array
    {
        $settings = $this->settings();

        if (! $this->runtimeConfigApplied) {
            $this->applyRuntimeConfigValues($settings);
            $this->runtimeConfigApplied = true;
        }

        return $settings;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return filter_var($this->all()[$key] ?? $default, FILTER_VALIDATE_BOOLEAN);
    }

    public function integer(string $key, int $default): int
    {
        $value = $this->all()[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->all()[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    public function save(array $data): void
    {
        $encrypted = ['redis_password'];

        foreach ($data as $key => $value) {
            if ($key === 'redis_password' && blank($value)) {
                continue;
            }

            PlatformSetting::setValue($key, $value, in_array($key, $encrypted, true));
        }

        $this->clear();
        $this->applyRuntimeConfig();
    }

    public function clear(): void
    {
        $this->settingsCache = null;
        $this->runtimeConfigApplied = false;

        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // Cache backend may be unavailable while runtime settings are being fixed.
        }
    }

    public function applyRuntimeConfig(): void
    {
        $this->applyRuntimeConfigValues($this->settings());
        $this->runtimeConfigApplied = true;
    }

    private function settings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }

        try {
            $settings = Cache::remember(self::CACHE_KEY, 60, fn () => $this->load());
        } catch (Throwable) {
            $settings = $this->load();
        }

        return $this->settingsCache = $settings;
    }

    private function applyRuntimeConfigValues(array $settings): void
    {
        config([
            'cache.default' => $settings['cache_store'],
            'queue.default' => $settings['queue_connection'],
            'database.redis.default.host' => $settings['redis_host'],
            'database.redis.default.port' => (string) $settings['redis_port'],
            'database.redis.default.password' => $settings['redis_password'] ?: null,
            'database.redis.default.database' => (string) $settings['redis_db'],
            'database.redis.cache.host' => $settings['redis_host'],
            'database.redis.cache.port' => (string) $settings['redis_port'],
            'database.redis.cache.password' => $settings['redis_password'] ?: null,
            'database.redis.cache.database' => (string) ($settings['redis_cache_db'] ?? 1),
            'runtime.mode' => $settings['runtime_mode'],
            'runtime.docker.enabled' => $settings['runtime_docker_enabled'],
            'runtime.docker.image' => $settings['runtime_docker_image'],
            'runtime.docker.container_prefix' => $settings['runtime_container_prefix'],
            'runtime.docker.http_port_start' => $settings['runtime_http_port_start'],
            'runtime.docker.memory_limit' => $settings['runtime_memory_limit'],
            'runtime.docker.cpu_limit' => $settings['runtime_cpu_limit'],
            'runtime.docker.timeout_ms' => $settings['command_timeout_ms'],
            'runtime.docker.keep_paused_warm' => $settings['runtime_keep_paused_warm'],
            'runtime.docker.auto_restart' => $settings['runtime_auto_restart'],
            'services.node_runtime.url' => $settings['runtime_base_url'],
        ]);
    }

    private function load(): array
    {
        $runtimeHost = (string) PlatformSetting::getValue('runtime_host', '127.0.0.1');
        $runtimePort = $this->settingInt('runtime_port', 8787, 1, 65535);
        $runtimeBaseUrl = rtrim((string) PlatformSetting::getValue(
            'runtime_base_url',
            config('services.node_runtime.url', "http://{$runtimeHost}:{$runtimePort}")
        ), '/');

        return [
            'redis_enabled' => $this->settingBool('redis_enabled', false),
            'redis_host' => (string) PlatformSetting::getValue('redis_host', config('database.redis.default.host', '127.0.0.1')),
            'redis_port' => (int) PlatformSetting::getValue('redis_port', config('database.redis.default.port', 6379)),
            'redis_password' => (string) PlatformSetting::getValue('redis_password', ''),
            'redis_db' => (int) PlatformSetting::getValue('redis_db', config('database.redis.default.database', 0)),
            'redis_cache_db' => (int) config('database.redis.cache.database', 1),
            'cache_store' => $this->option('cache_store', config('cache.default', 'database'), ['file', 'database', 'redis']),
            'queue_connection' => $this->option('queue_connection', config('queue.default', 'database'), ['sync', 'database', 'redis']),
            'runtime_warm_enabled' => $this->settingBool('runtime_warm_enabled', true),
            'queue_simple_commands' => $this->settingBool('queue_simple_commands', false),
            'command_timeout_ms' => $this->settingInt('command_timeout_ms', 15000, 1000, 30000),
            'max_delay_ms' => $this->settingInt('max_delay_ms', 10000, 0, 30000),
            'slow_command_threshold_ms' => $this->settingInt('slow_command_threshold_ms', 1000, 100, 30000),
            'log_slow_commands' => $this->settingBool('log_slow_commands', false),
            'runtime_mode' => $this->option('runtime_mode', 'local', ['local', 'docker']),
            'runtime_host' => $runtimeHost,
            'runtime_port' => $runtimePort,
            'runtime_base_url' => $runtimeBaseUrl,
            'runtime_health_url' => (string) PlatformSetting::getValue('runtime_health_url', $runtimeBaseUrl.'/health'),
            'runtime_execute_url' => (string) PlatformSetting::getValue('runtime_execute_url', $runtimeBaseUrl.'/execute'),
            'runtime_docker_enabled' => $this->settingBool('runtime_docker_enabled', (bool) config('runtime.docker.enabled', false)),
            'runtime_docker_image' => (string) PlatformSetting::getValue('runtime_docker_image', config('runtime.docker.image', 'bothost-node-runtime')),
            'runtime_container_prefix' => (string) PlatformSetting::getValue('runtime_container_prefix', config('runtime.docker.container_prefix', 'bothost-bot')),
            'runtime_http_port_start' => $this->settingInt('runtime_http_port_start', (int) config('runtime.docker.http_port_start', 8800), 1024, 65500),
            'runtime_memory_limit' => (string) PlatformSetting::getValue('runtime_memory_limit', config('runtime.docker.memory_limit', '128m')),
            'runtime_cpu_limit' => (string) PlatformSetting::getValue('runtime_cpu_limit', config('runtime.docker.cpu_limit', '0.25')),
            'runtime_keep_paused_warm' => $this->settingBool('runtime_keep_paused_warm', (bool) config('runtime.docker.keep_paused_warm', false)),
            'runtime_auto_restart' => $this->settingBool('runtime_auto_restart', (bool) config('runtime.docker.auto_restart', true)),
            'log_docker_errors' => $this->settingBool('log_docker_errors', true),
            'show_user_code_errors_to_owners' => $this->settingBool('show_user_code_errors_to_owners', false),
            'log_user_code_errors' => $this->settingBool('log_user_code_errors', false),
            'log_backend_runtime_errors' => $this->settingBool('log_backend_runtime_errors', true),
            'log_webhook_errors' => $this->settingBool('log_webhook_errors', true),
            'log_telegram_api_errors' => $this->settingBool('log_telegram_api_errors', true),
            'log_redis_errors' => $this->settingBool('log_redis_errors', true),
        ];
    }

    private function settingBool(string $key, bool $default): bool
    {
        $value = PlatformSetting::getValue($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function settingInt(string $key, int $default, int $min, int $max): int
    {
        $value = PlatformSetting::getValue($key, $default);
        $int = is_numeric($value) ? (int) $value : $default;

        return min(max($int, $min), $max);
    }

    private function option(string $key, string $default, array $allowed): string
    {
        $value = (string) PlatformSetting::getValue($key, $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }
}
