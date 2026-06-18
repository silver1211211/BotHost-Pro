<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotRuntimeData;
use App\Models\BotUserRuntimeData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Support\NodeRuntimeConfig;
use App\Support\PublicCallbackUrl;
use Symfony\Component\Process\Process;
use Throwable;

class NodeRuntimeService
{
    public function __construct(
        private readonly RuntimeSettingsService $settings,
        private readonly DockerRuntimeService $dockerRuntime,
    ) {}

    public function executeCommand(Bot $bot, BotCommand $command, array $telegramContext): array
    {
        $runtimeSettings = $this->settings->all();
        $runtimeSettings['command_timeout_ms'] = min(150000, max(1000, (int) ($runtimeSettings['command_timeout_ms'] ?? 150000)));
        $runtimeMode = $runtimeSettings['runtime_mode'] ?? 'local';
        $payload = $this->buildPayload($bot, $command, $telegramContext, $runtimeSettings, includeToken: true);
        $commandPayload = $payload;
        $executionContext = $this->runtimeLogContext($bot, $command, $telegramContext);

        Log::info('[BotHost] runtime_execution_started', $executionContext + [
            'runtime_mode' => $runtimeMode,
        ]);

        $path = [
            'runtime_mode' => $runtimeMode,
            'runtime_execute_url' => $this->safeUrlForLog($this->runtimeExecuteUrl($runtimeSettings)),
            'node_runtime_attempted' => false,
            'fallback_used' => false,
            'docker_used' => false,
        ];

        if ($runtimeMode === 'docker' && filter_var($runtimeSettings['runtime_docker_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            $path['docker_used'] = true;
            $result = $this->normalizeRuntimeResult($this->dockerRuntime->executeCommandInContainer($bot, $payload));
            Log::info('[BotHost] Docker runtime result', [
                'ok' => $result['ok'] ?? false,
                'error' => $result['error'] ?? null,
                'error_type' => $result['error_type'] ?? null,
            ]);

            if (! $result['ok'] && $this->shouldFallbackFromRuntimeError($result['error_type'] ?? null)) {
                $result = $this->fallbackResult($payload, $runtimeSettings, $path, $result);
            }

            $this->persistRuntimeResultMutations($bot, $telegramContext, $result);
            $this->logRuntimeExecutionOutcome($result, $executionContext);

            unset($result['storage']);
            $result['runtime_path'] = $path;
            $this->logRuntimePath($path);

            return $result;
        }

        if ($runtimeMode === 'docker') {
            Log::warning('[BotHost] Docker runtime requested but disabled; falling back to local runtime.', [
                'bot_id' => $bot->id,
                'command_id' => $command->id,
            ]);
        }

        if (! $this->localRuntimeHealthy($runtimeSettings)) {
            $result = $this->fallbackResult($payload, $runtimeSettings, $path);

            $this->persistRuntimeResultMutations($bot, $telegramContext, $result);
            $this->logRuntimeExecutionOutcome($result, $executionContext);

            unset($result['storage']);
            $result['runtime_path'] = $path;
            $this->logRuntimePath($path);

            return $result;
        }

        $url = $this->runtimeExecuteUrl($runtimeSettings);

        if ($url === '') {
            $result = $this->fallbackResult($payload, $runtimeSettings, $path, $this->runtimeUnavailableResult('Runtime execute URL is not configured.', 'RuntimeConfigurationError'));
            unset($result['storage']);
            $result['runtime_path'] = $path;
            $this->logRuntimePath($path);

            return $result;
        }

        $requestTimeout = max(10, (int) ceil(((int) $runtimeSettings['command_timeout_ms']) / 1000) + 5);
        $request = Http::connectTimeout(1)->timeout($requestTimeout)->acceptJson();
        $secret = NodeRuntimeConfig::secret();

        if (filled($secret)) {
            $request = $request->withHeaders(['X-Runtime-Secret' => $secret]);
        }

        try {
            $path['node_runtime_attempted'] = true;
            Log::info('[BotHost] Node runtime HTTP call', [
                'url' => $this->safeUrlForLog($url),
                'bot_id' => $bot->id,
                'command_id' => $command->id
            ]);
            $response = $request->post($url, $payload);
        } catch (ConnectionException $exception) {
            $result = $this->fallbackResult(
                $payload,
                $runtimeSettings,
                $path,
                $this->runtimeUnavailableResult(
                    $this->isTimeoutMessage($exception->getMessage()) ? 'Runtime request timed out.' : 'Runtime unavailable.',
                    $this->isTimeoutMessage($exception->getMessage()) ? 'RuntimeTimeout' : 'RuntimeUnavailable',
                ),
            );

            $this->persistRuntimeResultMutations($bot, $telegramContext, $result);
            $this->logRuntimeExecutionOutcome($result, $executionContext);

            unset($result['storage']);
            $result['runtime_path'] = $path;
            $this->logRuntimeFailure($path, $exception->getMessage(), 'RuntimeUnavailable', $result);
            $this->logRuntimePath($path);

            return $result;
        } catch (Throwable $exception) {
            $result = $this->fallbackResult($payload, $runtimeSettings, $path, $this->runtimeUnavailableResult('Runtime unavailable.', 'RuntimeUnavailable'));

            $this->persistRuntimeResultMutations($bot, $telegramContext, $result);
            $this->logRuntimeExecutionOutcome($result, $executionContext);

            unset($result['storage']);
            $result['runtime_path'] = $path;
            $this->logRuntimeFailure($path, $exception->getMessage(), 'RuntimeUnavailable', $result);
            $this->logRuntimePath($path);

            return $result;
        }

        if (! $response->successful()) {
            $responsePayload = $this->jsonPayload($response);

            $runtimeFailure = [
                'ok' => false,
                'execution_id' => is_array($responsePayload) ? ($responsePayload['execution_id'] ?? null) : null,
                'execution_time_ms' => is_array($responsePayload) ? ($responsePayload['execution_time_ms'] ?? null) : null,
                'replies' => [],
                'error' => is_array($responsePayload) ? ($responsePayload['error'] ?? 'Runtime request failed.') : 'Runtime request failed.',
                'error_type' => is_array($responsePayload) ? ($responsePayload['error_type'] ?? 'RuntimeRequestFailed') : 'RuntimeRequestFailed',
                'error_stack' => is_array($responsePayload) ? ($responsePayload['error_stack'] ?? null) : null,
            ];
            $normalizedFailure = $this->normalizeRuntimeResult($runtimeFailure);
            $result = count($normalizedFailure['replies'] ?? []) > 0
                ? $normalizedFailure
                : $this->fallbackResult($commandPayload, $runtimeSettings, $path, $normalizedFailure);

            $this->persistRuntimeResultMutations($bot, $telegramContext, $result);
            $this->logRuntimeExecutionOutcome($result, $executionContext);

            unset($result['storage']);
            $result['runtime_path'] = $path;
            $this->logRuntimeFailure($path, $runtimeFailure['error'], $runtimeFailure['error_type'], $result);
            $this->logRuntimePath($path);

            return $result;
        }

        $runtimePayload = $this->jsonPayload($response);

        if (! is_array($runtimePayload)) {
            $result = $this->fallbackResult($commandPayload, $runtimeSettings, $path, $this->runtimeUnavailableResult('Runtime returned an invalid response.', 'InvalidRuntimeResponse'));

            $this->persistRuntimeResultMutations($bot, $telegramContext, $result);
            $this->logRuntimeExecutionOutcome($result, $executionContext);

            unset($result['storage']);
            $result['runtime_path'] = $path;
            $this->logRuntimePath($path);

            return $result;
        }

        if (($runtimePayload['ok'] ?? false) !== true) {
            $runtimeFailure = $this->normalizeRuntimeResult($runtimePayload);
            $result = count($runtimeFailure['replies'] ?? []) > 0
                ? $runtimeFailure
                : ($this->shouldFallbackFromRuntimeError($runtimeFailure['error_type'] ?? null)
                ? $this->fallbackResult($commandPayload, $runtimeSettings, $path, $runtimeFailure)
                : $runtimeFailure);

            $this->persistRuntimeResultMutations($bot, $telegramContext, $result);
            $this->logRuntimeExecutionOutcome($result, $executionContext);

            unset($result['storage']);
            $result['runtime_path'] = $path;
            $this->logRuntimePath($path);

            return $result;
        }

        $result = $this->normalizeRuntimeResult($runtimePayload);
        $this->persistRuntimeResultMutations($bot, $telegramContext, $result);
        $this->logRuntimeExecutionOutcome($result, $executionContext);
        unset($result['storage']);
        $result['runtime_path'] = $path;
        $this->logRuntimePath($path);

        return $result;
    }

    private function buildPayload(Bot $bot, BotCommand $command, array $telegramContext, array $runtimeSettings, bool $includeToken): array
    {
        $telegramUserId = isset($telegramContext['user_id']) ? (string) $telegramContext['user_id'] : null;
        $runtime = [];

        if ($includeToken) {
            $runtime['oxapay_bridge_url'] = $this->runtimeOxaPayBridgeUrl();
            $runtime['telegram_bridge_url'] = $this->runtimeTelegramBridgeUrl();
            $runtime['storage_bridge_url'] = $this->runtimeStorageBridgeUrl();
            $runtime['oxapay_bridge_secret'] = NodeRuntimeConfig::secret();
            $runtime['telegram_bridge_secret'] = NodeRuntimeConfig::secret();
            $runtime['storage_bridge_secret'] = NodeRuntimeConfig::secret();
            $runtime['secrets'] = $this->runtimeSecrets($bot);
        }

        return [
            'bot' => [
                'id' => $bot->id,
                'name' => $bot->name,
                'username' => $bot->telegram_username,
                'language' => $bot->language,
                'status' => $bot->status,
            ],
            'runtime' => $runtime,
            'command' => [
                'id' => $command->id,
                'name' => $command->displayName(),
                'display_name' => $command->displayName(),
                'trigger' => $command->command_name,
                'trigger_type' => $command->effectiveTriggerType(),
                'type' => $command->response_type,
                'code' => $command->code,
            ],
            'telegram' => $telegramContext,
            'storage' => [
                'bot' => $this->botStorage($bot, $command, $telegramContext),
                'user' => $telegramUserId ? $this->userStorage($bot, $telegramUserId) : [],
                'cross_users' => $telegramUserId ? $this->crossUserPreload($bot, $telegramUserId, $command, $telegramContext) : [],
            ],
            'settings' => [
                'command_timeout_ms' => $runtimeSettings['command_timeout_ms'],
                'max_delay_ms' => $runtimeSettings['max_delay_ms'],
                'runtime_mode' => $runtimeSettings['runtime_mode'],
                'runtime_warm_enabled' => $runtimeSettings['runtime_warm_enabled'],
            ],
        ];
    }

    private function runtimeExecuteUrl(array $runtimeSettings): string
    {
        $executeUrl = rtrim(trim((string) ($runtimeSettings['runtime_execute_url'] ?? '')), '/');
        $baseUrl = rtrim(trim((string) ($runtimeSettings['runtime_base_url'] ?? config('services.node_runtime.url', ''))), '/');

        return $executeUrl !== '' ? $executeUrl : ($baseUrl !== '' ? $baseUrl.'/execute' : '');
    }

    private function runtimeHealthUrl(array $runtimeSettings): string
    {
        $healthUrl = rtrim(trim((string) ($runtimeSettings['runtime_health_url'] ?? '')), '/');
        $baseUrl = rtrim(trim((string) ($runtimeSettings['runtime_base_url'] ?? config('services.node_runtime.url', ''))), '/');

        return $healthUrl !== '' ? $healthUrl : ($baseUrl !== '' ? $baseUrl.'/health' : '');
    }

    private function runtimeBridgeBaseUrl(): string
    {
        $internal = NodeRuntimeConfig::internalUrl();
        if ($internal !== '') {
            return $internal;
        }
        return rtrim(PublicCallbackUrl::base(), '/');
    }

    private function runtimeOxaPayBridgeUrl(): string
    {
        return $this->runtimeBridgeBaseUrl().'/runtime/oxapay';
    }

    private function runtimeTelegramBridgeUrl(): string
    {
        return $this->runtimeBridgeBaseUrl().'/runtime/telegram';
    }

    private function runtimeStorageBridgeUrl(): string
    {
        return $this->runtimeBridgeBaseUrl().'/runtime/storage';
    }

    private function localRuntimeHealthy(array $runtimeSettings): bool
    {
        $url = $this->runtimeHealthUrl($runtimeSettings);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        try {
            return Http::connectTimeout(1)
                ->timeout(2)
                ->acceptJson()
                ->get($url)
                ->successful();
        } catch (Throwable $exception) {
            if ($this->settings->boolean('log_backend_runtime_errors', true)) {
                Log::warning('[BotHost] Local runtime health check failed; using fallback runner.', [
                    'runtime_health_url' => $this->safeUrlForLog($url),
                    'error' => $this->safeRuntimeMessage($exception->getMessage()),
                ]);
            }

            return false;
        }
    }

    private function fallbackResult(array $payload, array $runtimeSettings, array &$path, ?array $previousFailure = null): array
    {
        $path['fallback_used'] = true;
        $fallback = $this->executeWithLocalNodeProcess($payload, $runtimeSettings);

        Log::info('[BotHost] Runtime fallback result', [
            'fallback_used' => true,
            'ok' => $fallback['ok'] ?? false,
            'action_count' => is_countable($fallback['replies'] ?? null) ? count($fallback['replies']) : 0,
            'first_action_type' => $fallback['replies'][0]['type'] ?? null,
            'previous_error_type' => $previousFailure['error_type'] ?? null,
            'fallback_error_type' => $fallback['error_type'] ?? null,
            'fallback_error' => isset($fallback['error']) ? $this->safeRuntimeMessage((string) $fallback['error']) : null,
        ]);

        return ($fallback['ok'] ?? false) || count($fallback['replies'] ?? []) > 0 ? $fallback : ($previousFailure ?? $fallback);
    }

    private function shouldFallbackFromRuntimeError(?string $errorType): bool
    {
        return in_array($errorType, [
            'RuntimeUnavailable',
            'RuntimeTimeout',
            'RuntimeConfigurationError',
            'RuntimeRequestFailed',
            'InvalidRuntimeResponse',
        ], true);
    }

    private function normalizeRuntimeResult(array $payload): array
    {
        $errorType = $payload['error_type'] ?? 'RuntimeExecutionError';
        if ($errorType === 'TimeoutError') {
            $errorType = 'RuntimeTimeout';
        }

        if (($payload['ok'] ?? false) !== true) {
            return [
                'ok' => false,
                'execution_id' => $payload['execution_id'] ?? null,
                'execution_time_ms' => $payload['execution_time_ms'] ?? null,
                'replies' => $this->normalizeReplies($payload['replies'] ?? []),
                'error' => $payload['error'] ?? 'Runtime execution failed.',
                'error_type' => $errorType,
                'error_stack' => $payload['error_stack'] ?? null,
                'storage' => $payload['storage'] ?? null,
            ];
        }

        return [
            'ok' => true,
            'execution_id' => $payload['execution_id'] ?? null,
            'execution_time_ms' => $payload['execution_time_ms'] ?? null,
            'replies' => $this->normalizeReplies($payload['replies'] ?? []),
            'error' => null,
            'error_type' => null,
            'error_stack' => null,
            'storage' => $payload['storage'] ?? null,
        ];
    }

    private function executeWithLocalNodeProcess(array $payload, array $runtimeSettings): array
    {
        $script = base_path('runtime-node/execute-once.js');

        if (! is_file($script)) {
            return $this->runtimeUnavailableResult('Local runtime fallback is missing.', 'RuntimeConfigurationError');
        }

        $timeout = max(10, (int) ceil(((int) ($runtimeSettings['command_timeout_ms'] ?? 30000)) / 1000) + 5);

        try {
            $process = new Process(
                ['node', $script],
                base_path(),
                $this->nodeProcessEnvironment(),
                json_encode($payload, JSON_UNESCAPED_SLASHES),
                $timeout,
            );
            $process->run();
        } catch (Throwable $exception) {
            $this->logRuntimeFallbackFailure($exception->getMessage(), [
                'timeout_seconds' => $timeout,
                'command_timeout_ms' => (int) ($runtimeSettings['command_timeout_ms'] ?? 30000),
            ]);

            return $this->runtimeUnavailableResult('Local runtime fallback unavailable.', 'RuntimeUnavailable');
        }

        if (! $process->isSuccessful()) {
            $this->logRuntimeFallbackFailure($process->getErrorOutput() ?: $process->getOutput(), [
                'timeout_seconds' => $timeout,
                'command_timeout_ms' => (int) ($runtimeSettings['command_timeout_ms'] ?? 30000),
                'exit_code' => $process->getExitCode(),
            ]);

            return $this->runtimeUnavailableResult('Local runtime fallback failed.', 'RuntimeRequestFailed');
        }

        $payload = $this->decodeRuntimeJsonOutput($process->getOutput());

        if (! is_array($payload)) {
            return $this->runtimeUnavailableResult('Local runtime fallback returned an invalid response.', 'InvalidRuntimeResponse');
        }

        return $this->normalizeRuntimeResult($payload);
    }

    private function runtimeUnavailableResult(string $error, string $type): array
    {
        return [
            'ok' => false,
            'execution_id' => null,
            'execution_time_ms' => null,
            'replies' => [],
            'error' => $error,
            'error_type' => $type,
            'error_stack' => null,
            'storage' => null,
        ];
    }

    private function decodeRuntimeJsonOutput(string $output): mixed
    {
        $payload = json_decode($output, true);

        if (is_array($payload)) {
            return $payload;
        }

        $lines = preg_split('/\R/', trim($output));
        if (! is_array($lines)) {
            return null;
        }

        for ($index = count($lines) - 1; $index >= 0; $index--) {
            $line = trim($lines[$index]);
            if ($line === '' || ! str_starts_with($line, '{')) {
                continue;
            }

            $payload = json_decode($line, true);
            if (is_array($payload)) {
                return $payload;
            }
        }

        return null;
    }

    private function nodeProcessEnvironment(): array
    {
        $systemRoot = getenv('SystemRoot') ?: getenv('WINDIR') ?: 'C:\\Windows';
        $path = getenv('PATH') ?: getenv('Path') ?: implode(PATH_SEPARATOR, [
            'C:\\Program Files\\nodejs',
            $systemRoot.'\\System32',
            $systemRoot,
        ]);

        return array_filter([
            'SystemRoot' => $systemRoot,
            'WINDIR' => $systemRoot,
            'PATH' => $path,
            'Path' => $path,
            'TEMP' => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP' => getenv('TMP') ?: sys_get_temp_dir(),
            'NODE_OPTIONS' => getenv('NODE_OPTIONS') ?: null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function logRuntimeFallbackFailure(string $message, array $context = []): void
    {
        if (! $this->settings->boolean('log_backend_runtime_errors', true)) {
            return;
        }

        Log::warning('Local command runtime fallback failed.', $context + [
            'category' => 'runtime_backend',
            'error' => $this->safeRuntimeMessage($message),
        ]);
    }

    private function runtimeLogContext(Bot $bot, BotCommand $command, array $telegramContext): array
    {
        return [
            'bot_id' => $bot->id,
            'command_id' => $command->id,
            'command_name' => $command->command_name,
            'trigger_type' => $command->effectiveTriggerType(),
            'is_callback_command' => is_array($telegramContext['callback_query'] ?? null),
            'user_id' => isset($telegramContext['user_id']) ? (string) $telegramContext['user_id'] : null,
            'chat_id' => $telegramContext['chat_id'] ?? null,
        ];
    }

    private function logRuntimeExecutionOutcome(array $result, array $context): void
    {
        $payload = $context + [
            'execution_id' => $result['execution_id'] ?? null,
            'execution_time_ms' => $result['execution_time_ms'] ?? null,
            'ok' => (bool) ($result['ok'] ?? false),
            'error_type' => $result['error_type'] ?? null,
        ];

        if (($result['ok'] ?? false) === true) {
            Log::info('[BotHost] runtime_execution_finished', $payload);
            return;
        }

        Log::error('[BotHost] runtime_execution_failed', $payload + [
            'error' => isset($result['error']) ? $this->safeRuntimeMessage((string) $result['error']) : null,
            'stack' => isset($result['error_stack']) ? $this->safeRuntimeStack((string) $result['error_stack']) : null,
        ]);

        if (($context['trigger_type'] ?? null) === 'direct_message') {
            Log::error('[BotHost] direct_message_handler_failed', $payload + [
                'error' => isset($result['error']) ? $this->safeRuntimeMessage((string) $result['error']) : null,
                'stack' => isset($result['error_stack']) ? $this->safeRuntimeStack((string) $result['error_stack']) : null,
            ]);
        }

        if (! empty($context['is_callback_command'])) {
            Log::error('[BotHost] callback_command_failed', $payload + [
                'error' => isset($result['error']) ? $this->safeRuntimeMessage((string) $result['error']) : null,
                'stack' => isset($result['error_stack']) ? $this->safeRuntimeStack((string) $result['error_stack']) : null,
            ]);
        }
    }

    private function persistRuntimeResultMutations(Bot $bot, array $telegramContext, array $result): void
    {
        if (array_key_exists('storage', $result)) {
            $this->persistStorageMutations($bot, $telegramContext, $result['storage']);
        }
    }

    private function logRuntimePath(array $path): void
    {
        Log::info('[BotHost] Runtime path', [
            'runtime_mode' => $path['runtime_mode'] ?? null,
            'runtime_execute_url' => $path['runtime_execute_url'] ?? null,
            'node_runtime_attempted' => (bool) ($path['node_runtime_attempted'] ?? false),
            'fallback_used' => (bool) ($path['fallback_used'] ?? false),
            'docker_used' => (bool) ($path['docker_used'] ?? false),
        ]);
    }

    private function logRuntimeFailure(array $path, string $message, string $errorType, array $fallbackResult): void
    {
        if (! $this->settings->boolean('log_backend_runtime_errors', true)) {
            return;
        }

        Log::warning('[BotHost] Runtime failed', [
            'runtime_mode' => $path['runtime_mode'] ?? null,
            'runtime_execute_url' => $path['runtime_execute_url'] ?? null,
            'error_type' => $errorType,
            'error' => $this->safeRuntimeMessage($message),
            'fallback_attempted' => (bool) ($path['fallback_used'] ?? false),
            'fallback_ok' => (bool) ($fallbackResult['ok'] ?? false),
            'fallback_error_type' => $fallbackResult['error_type'] ?? null,
        ]);
    }

    private function safeRuntimeMessage(string $message): string
    {
        return str($message)
            ->replaceMatches('/\d{6,}:[A-Za-z0-9_-]{20,}/', '[redacted-token]')
            ->replaceMatches('/(password|secret|token|api[_-]?key)=\S+/i', '$1=[redacted]')
            ->limit(500, '')
            ->toString();
    }

    private function safeRuntimeStack(string $stack): string
    {
        return str($stack)
            ->replaceMatches('/\d{6,}:[A-Za-z0-9_-]{20,}/', '[redacted-token]')
            ->replaceMatches('/(password|secret|token|api[_-]?key)=\S+/i', '$1=[redacted]')
            ->limit(4000, '')
            ->toString();
    }

    private function safeUrlForLog(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';

        return $scheme.$host.$port.$path;
    }

    private function jsonPayload(Response $response): mixed
    {
        try {
            return $response->json();
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeReplies(mixed $replies): array
    {
        if (! is_array($replies)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($reply) {
            if (! is_array($reply)) {
                return null;
            }

            if (($reply['type'] ?? null) === 'sendMessage') {
                $reply['type'] = 'text';
            }

            if (($reply['type'] ?? null) === 'sendPhoto') {
                $reply['type'] = 'photo';
                $reply['photo_url'] = $reply['photo_url'] ?? $reply['photo'] ?? null;
            }

            return $reply;
        }, $replies)));
    }

    private function decryptBotToken(Bot $bot): ?string
    {
        $encrypted = $bot->getRawOriginal('token_encrypted');

        if (! filled($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return null;
        }
    }

    private function botStorage(Bot $bot, ?BotCommand $command = null, array $context = []): array
    {
        if (! $this->hasTable('bot_runtime_data')) {
            return [];
        }

        try {
            $code = (string) ($command?->code ?? '');
            $keys = $this->botStoragePreloadKeys($code, $context);

            foreach ($this->supportReplyTicketPreloadKeys($bot, $code, $context) as $key) {
                $this->addBotStoragePreloadKey($keys, $key);
            }

            if ($keys === []) {
                return [];
            }

            return BotRuntimeData::query()
                ->where('bot_id', $bot->id)
                ->whereIn('key', $keys)
                ->get(['key', 'value'])
                ->mapWithKeys(fn (BotRuntimeData $row) => [
                    $row->key => $this->isRuntimeSecretKey($row->key)
                        ? $this->maskSecretValue((string) $row->value)
                        : $row->value,
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function botStoragePreloadKeys(string $code, array $context): array
    {
        if ($code === '') {
            return [];
        }

        $keys = [];

        preg_match_all('/\b(?:getBotData|setBotData|incrementBotData|clearBotData|removeBotData)\s*\(\s*([\'"])([^\'"]{1,100})\1/', $code, $matches);
        foreach ($matches[2] ?? [] as $key) {
            $this->addBotStoragePreloadKey($keys, $key);
        }

        $args = is_array($context['args'] ?? null) ? $context['args'] : [];
        foreach ($args as $arg) {
            $ticketId = trim((string) $arg);
            if (preg_match('/^[A-Za-z0-9_-]{3,80}$/', $ticketId)) {
                $this->addBotStoragePreloadKey($keys, 'support_ticket_'.$ticketId);
            }
        }

        // This key can grow large. It is intentionally fetched lazily only when
        // command code explicitly asks for it.
        unset($keys['support_tickets']);

        return array_values($keys);
    }

    private function supportReplyTicketPreloadKeys(Bot $bot, string $code, array $context): array
    {
        if ($code === '' || ! str_contains($code, 'support_reply_ticket_id') || ! $this->hasTable('bot_user_runtime_data')) {
            return [];
        }

        $telegramUserId = isset($context['user_id']) ? trim((string) $context['user_id']) : '';
        if ($telegramUserId === '') {
            return [];
        }

        try {
            $row = BotUserRuntimeData::query()
                ->where('bot_id', $bot->id)
                ->where('telegram_user_id', $telegramUserId)
                ->where('key', 'support_reply_ticket_id')
                ->first(['value']);

            $ticketId = $row ? trim((string) $row->value) : '';
            if ($ticketId !== '' && preg_match('/^[A-Za-z0-9_-]{3,80}$/', $ticketId)) {
                return ['support_ticket_'.$ticketId];
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }

    private function addBotStoragePreloadKey(array &$keys, mixed $key): void
    {
        $key = trim((string) $key);
        if ($key !== '' && strlen($key) <= 100 && ! $this->isRuntimeSecretKey($key)) {
            $keys[$key] = $key;
        }
    }

    private function isRuntimeSecretKey(string $key): bool
    {
        return in_array($key, [
            'oxapay_merchant_api_key',
            'oxapay_payout_api_key',
            'faucetpay_api_key',
        ], true);
    }

    private function runtimeSecrets(Bot $bot): array
    {
        if (! $this->hasTable('bot_runtime_data')) {
            return [];
        }

        try {
            return BotRuntimeData::query()
                ->where('bot_id', $bot->id)
                ->whereIn('key', [
                    'oxapay_merchant_api_key',
                    'oxapay_payout_api_key',
                    'faucetpay_api_key',
                ])
                ->get(['key', 'value'])
                ->mapWithKeys(fn (BotRuntimeData $row) => [$row->key => $row->value])
                ->filter(fn (mixed $value) => filled($value))
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function maskSecretValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (strlen($value) <= 8) {
            return substr($value, 0, 2).'***';
        }

        return substr($value, 0, 5).'***'.substr($value, -3);
    }

    private function getFaucetPayApiKey(Bot $bot): ?string
    {
        if (! $this->hasTable('bot_runtime_data')) {
            return null;
        }

        try {
            $row = BotRuntimeData::query()
                ->where('bot_id', $bot->id)
                ->where('key', 'faucetpay_api_key')
                ->first(['value']);

            return $row ? (string) $row->value : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function crossUserPreload(Bot $bot, string $currentUserId, ?BotCommand $command = null, array $context = []): array
    {
        if (! $this->hasTable('bot_user_runtime_data')) {
            return [];
        }

        try {
            $result = [];

            // Existing: load admin_target_user so admin commands can read/write target user data
            $adminTargetRow = BotUserRuntimeData::query()
                ->where('bot_id', $bot->id)
                ->where('telegram_user_id', $currentUserId)
                ->where('key', 'admin_target_user')
                ->first(['value']);

            $targetUserId = $adminTargetRow ? (string) $adminTargetRow->value : null;
            if ($targetUserId && $targetUserId !== $currentUserId) {
                $result[$targetUserId] = $this->userStorage($bot, $targetUserId);
            }

            // Referral reward: load referrer's storage so balance increments are atomic
            $referrerRow = BotUserRuntimeData::query()
                ->where('bot_id', $bot->id)
                ->where('telegram_user_id', $currentUserId)
                ->where('key', 'referred_by')
                ->first(['value']);

            $referrerId = $referrerRow ? (string) $referrerRow->value : null;
            if ($referrerId && $referrerId !== $currentUserId && ! isset($result[$referrerId])) {
                $result[$referrerId] = $this->userStorage($bot, $referrerId);
            }

            $args = is_array($context['args'] ?? null) ? $context['args'] : [];
            foreach ($args as $arg) {
                $argUserId = trim((string) $arg);

                if (preg_match('/^\d{3,32}$/', $argUserId) && $argUserId !== $currentUserId && ! isset($result[$argUserId])) {
                    $result[$argUserId] = $this->userStorage($bot, $argUserId);
                }
            }

            // /start with referral arg: preload the referrer's data so the JS code can
            // safely append to referral_list without overwriting existing entries.
            $commandName = $command ? ($command->command_name ?? '') : '';
            if ($commandName === '/start') {
                $args   = is_array($context['args'] ?? null) ? $context['args'] : [];
                $refArg = isset($args[0]) ? (string) $args[0] : null;
                if ($refArg && $refArg !== $currentUserId && ! isset($result[$refArg])) {
                    $result[$refArg] = $this->userStorage($bot, $refArg);
                }
            }

            foreach ($this->crossUserHelperTargets((string) ($command?->code ?? '')) as $targetUserId) {
                if ($targetUserId !== $currentUserId && ! isset($result[$targetUserId])) {
                    $result[$targetUserId] = $this->userStorage($bot, $targetUserId);
                }
            }

            return $result;
        } catch (Throwable) {
            return [];
        }
    }

    private function crossUserHelperTargets(string $code): array
    {
        if ($code === '') {
            return [];
        }

        $targets = [];

        preg_match_all(
            '/\b(?:getUserDataFor|setUserDataFor|incrementUserDataFor|getBalance)\s*\(\s*([\'"])([^\'"]+)\1/',
            $code,
            $firstArgMatches,
        );

        foreach ($firstArgMatches[2] ?? [] as $target) {
            $this->addCrossUserTarget($targets, $target);
        }

        preg_match_all(
            '/\baddBalance\s*\(\s*[^,]+,\s*([\'"])([^\'"]+)\1/',
            $code,
            $secondArgMatches,
        );

        foreach ($secondArgMatches[2] ?? [] as $target) {
            $this->addCrossUserTarget($targets, $target);
        }

        return array_values($targets);
    }

    private function addCrossUserTarget(array &$targets, mixed $target): void
    {
        $telegramUserId = trim((string) $target);

        if ($telegramUserId !== '' && preg_match('/^\d{3,32}$/', $telegramUserId)) {
            $targets[$telegramUserId] = $telegramUserId;
        }
    }

    private function userStorage(Bot $bot, string $telegramUserId): array
    {
        if (! $this->hasTable('bot_user_runtime_data')) {
            return [];
        }

        $telegramUserId = trim($telegramUserId);
        if ($telegramUserId === '') {
            return [];
        }

        try {
            return BotUserRuntimeData::query()
                ->where('bot_id', $bot->id)
                ->where('telegram_user_id', $telegramUserId)
                ->get(['key', 'value'])
                ->mapWithKeys(fn (BotUserRuntimeData $row) => [$row->key => $row->value])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function persistStorageMutations(Bot $bot, array $telegramContext, mixed $storage): void
    {
        if (! is_array($storage)) {
            return;
        }

        $telegramUserId = isset($telegramContext['user_id']) ? (string) $telegramContext['user_id'] : null;
        $this->persistBotMutations($bot, $storage['bot'] ?? []);

        if ($telegramUserId !== null && $telegramUserId !== '') {
            $this->persistUserMutations($bot, $telegramUserId, $storage['user'] ?? [], 'user_data');
        }

        // Cross-user mutations: referral rewards, admin balance adjustments.
        // IMPORTANT: PHP's json_decode converts numeric-string keys (Telegram user IDs)
        // to integer keys, so we MUST cast $userId to string — never use is_string() to guard.
        $crossUsers = $storage['cross_users'] ?? [];

        Log::info('[BotHost] cross_user_mutation_received_by_laravel', [
            'bot_id'    => $bot->id,
            'user_count' => is_array($crossUsers) ? count($crossUsers) : 0,
            'user_ids'   => is_array($crossUsers) ? array_map('strval', array_keys($crossUsers)) : [],
        ]);

        if (is_array($crossUsers)) {
            foreach ($crossUsers as $userId => $mutations) {
                // Cast to string: PHP may decode numeric keys as integers
                $uid = trim((string) $userId);
                if ($uid === '' || ! is_array($mutations) || count($mutations) === 0) {
                    continue;
                }

                Log::info('[BotHost] cross_user_mutation_apply_start', [
                    'bot_id'         => $bot->id,
                    'target_user_id' => $uid,
                    'mutation_count' => count($mutations),
                    'keys'           => array_column($mutations, 'key'),
                    'ops'            => array_column($mutations, 'op'),
                ]);

                $this->persistUserMutations($bot, $uid, $mutations, 'cross_user');

                Log::info('[BotHost] cross_user_mutations_applied', [
                    'bot_id'         => $bot->id,
                    'target_user_id' => $uid,
                    'mutation_count' => count($mutations),
                ]);
            }
        }
    }

    private function persistBotMutations(Bot $bot, mixed $mutations): void
    {
        if (! is_array($mutations) || ! $this->hasTable('bot_runtime_data')) {
            return;
        }

        Log::info('[BotHost] bot_data_mutation_received_by_laravel', [
            'bot_id' => $bot->id,
            'mutation_count' => count($mutations),
            'keys' => array_values(array_filter(array_map(fn ($m) => is_array($m) ? ($m['key'] ?? null) : null, $mutations))),
        ]);

        foreach ($mutations as $mutation) {
            if (! is_array($mutation) || ! isset($mutation['key']) || ! is_string($mutation['key'])) {
                continue;
            }

            $key = $mutation['key'];
            $op = $mutation['op'] ?? 'set';

            try {
                if ($op === 'clear') {
                    BotRuntimeData::query()->where('bot_id', $bot->id)->where('key', $key)->delete();

                    if ($key === 'faucetpay_api_key') {
                        BotRuntimeData::query()->where('bot_id', $bot->id)->whereIn('key', ['faucetpay_api_key_masked', 'faucetpay_api_key_status'])->delete();
                    }

                    Log::info('[BotHost] bot_data_mutation_apply_success', [
                        'bot_id' => $bot->id,
                        'key' => $key,
                        'operation' => 'clear',
                        'success' => true,
                    ]);
                    continue;
                }

                $record = BotRuntimeData::firstOrNew([
                    'bot_id' => $bot->id,
                    'key' => $key,
                ]);
                $record->value = $mutation['value'] ?? null;
                $record->save();

                if ($key === 'faucetpay_api_key') {
                    $raw = isset($mutation['value']) ? (string) $mutation['value'] : '';

                    if ($raw !== '') {
                        foreach ([
                            'faucetpay_api_key_masked' => $this->maskSecretValue($raw),
                            'faucetpay_api_key_status' => 'saved',
                        ] as $metaKey => $metaValue) {
                            $meta = BotRuntimeData::firstOrNew(['bot_id' => $bot->id, 'key' => $metaKey]);
                            $meta->value = $metaValue;
                            $meta->save();
                        }
                    } else {
                        BotRuntimeData::query()->where('bot_id', $bot->id)->whereIn('key', ['faucetpay_api_key_masked', 'faucetpay_api_key_status'])->delete();
                    }
                }

                Log::info('[BotHost] bot_data_mutation_apply_success', [
                    'bot_id' => $bot->id,
                    'key' => $key,
                    'operation' => $op,
                    'success' => true,
                ]);
            } catch (Throwable $e) {
                Log::error('[BotHost] bot_data_mutation_apply_failed', [
                    'bot_id' => $bot->id,
                    'key' => $key,
                    'operation' => $op,
                    'success' => false,
                    'error' => $this->safeRuntimeMessage($e->getMessage()),
                ]);
                continue;
            }
        }
    }

    private function persistUserMutations(Bot $bot, string $telegramUserId, mixed $mutations, string $logScope = 'user_data'): void
    {
        if (! is_array($mutations) || ! $this->hasTable('bot_user_runtime_data')) {
            return;
        }

        $successEvent = $logScope === 'user_data' ? 'user_data_mutation_apply_success' : 'cross_user_mutation_apply_success';
        $failedEvent = $logScope === 'user_data' ? 'user_data_mutation_apply_failed' : 'cross_user_mutation_apply_failed';

        foreach ($mutations as $mutation) {
            if (! is_array($mutation) || ! isset($mutation['key']) || ! is_string($mutation['key'])) {
                continue;
            }

            $key = $mutation['key'];
            $op  = $mutation['op'] ?? 'set';

            try {
                // ── clear ────────────────────────────────────────────────────────
                if ($op === 'clear') {
                    BotUserRuntimeData::query()
                        ->where('bot_id', $bot->id)
                        ->where('telegram_user_id', $telegramUserId)
                        ->where('key', $key)
                        ->delete();
                    continue;
                }

                // ── increment ────────────────────────────────────────────────────
                // IMPORTANT: Do NOT use updateOrCreate() here. The BotUserRuntimeData
                // model uses Attribute::make() with an encrypt/decrypt setter. With
                // updateOrCreate the Eloquent dirty-check can silently skip the SQL
                // UPDATE when the setter transforms the value. We use firstOrNew +
                // explicit property assignment + save() to guarantee the setter fires
                // and the record is always marked dirty.
                if ($op === 'increment') {
                    $record  = BotUserRuntimeData::query()
                        ->where('bot_id', $bot->id)
                        ->where('telegram_user_id', $telegramUserId)
                        ->where('key', $key)
                        ->first();

                    $current = $record !== null ? (float) $record->value : 0.0;
                    $delta   = isset($mutation['amount']) ? (float) $mutation['amount'] : 0.0;
                    $newVal  = $current + $delta;

                    Log::info('[BotHost] cross_user_balance_before_db', [
                        'bot_id'         => $bot->id,
                        'target_user_id' => $telegramUserId,
                        'key'            => $key,
                        'before'         => $current,
                        'amount'         => $delta,
                        'expected_after' => $newVal,
                        'operation'      => 'increment',
                    ]);

                    if ($record !== null) {
                        $record->value = $newVal;
                        $record->save();
                    } else {
                        BotUserRuntimeData::create([
                            'bot_id'           => $bot->id,
                            'telegram_user_id' => $telegramUserId,
                            'key'              => $key,
                            'value'            => $newVal,
                        ]);
                    }

                    // Readback: re-query after save to confirm persistence
                    $verify   = BotUserRuntimeData::query()
                        ->where('bot_id', $bot->id)
                        ->where('telegram_user_id', $telegramUserId)
                        ->where('key', $key)
                        ->first();
                    $readback = $verify !== null ? (float) $verify->value : null;
                    $persisted = $readback !== null && abs($readback - $newVal) < 0.001;

                    Log::info('[BotHost] cross_user_balance_after_db', [
                        'bot_id'         => $bot->id,
                        'target_user_id' => $telegramUserId,
                        'key'            => $key,
                        'before'         => $current,
                        'after'          => $newVal,
                        'readback'       => $readback,
                        'success'        => $persisted,
                        'operation'      => 'increment',
                    ]);

                    if ($persisted) {
                        Log::info('[BotHost] '.$successEvent, [
                            'bot_id'         => $bot->id,
                            'target_user_id' => $telegramUserId,
                            'key'            => $key,
                            'operation'      => 'increment',
                            'amount'         => $delta,
                            'before'         => $current,
                            'after'          => $readback,
                        ]);
                    } else {
                        Log::warning('[BotHost] '.$failedEvent, [
                            'bot_id'         => $bot->id,
                            'target_user_id' => $telegramUserId,
                            'key'            => $key,
                            'operation'      => 'increment',
                            'amount'         => $delta,
                            'expected'       => $newVal,
                            'readback'       => $readback,
                        ]);
                    }

                    continue;
                }

                // ── push ─────────────────────────────────────────────────────────
                // Reads the current JSON array from DB, appends the item, trims to
                // limit, then saves. Atomic at the PHP level (read-append-save), so
                // the runtime never needs a preloaded stale value.
                if ($op === 'push') {
                    $record = BotUserRuntimeData::query()
                        ->where('bot_id', $bot->id)
                        ->where('telegram_user_id', $telegramUserId)
                        ->where('key', $key)
                        ->first();

                    $current = ($record !== null && is_array($record->value)) ? $record->value : [];
                    $item    = $mutation['item'] ?? null;
                    $limit   = isset($mutation['limit']) ? max(1, min((int) $mutation['limit'], 500)) : 50;

                    if ($item !== null) {
                        $current[] = $item;
                        $newValue  = array_slice($current, -$limit);

                        if ($record !== null) {
                            $record->value = $newValue;
                            $record->save();
                        } else {
                            BotUserRuntimeData::create([
                                'bot_id'           => $bot->id,
                                'telegram_user_id' => $telegramUserId,
                                'key'              => $key,
                                'value'            => $newValue,
                            ]);
                        }

                        Log::info('[BotHost] '.$successEvent, [
                            'bot_id'         => $bot->id,
                            'target_user_id' => $telegramUserId,
                            'key'            => $key,
                            'operation'      => 'push',
                            'new_count'      => count($newValue),
                            'success'        => true,
                        ]);
                    }

                    continue;
                }

                // ── set (default) ────────────────────────────────────────────────
                // Same reason: use firstOrNew + explicit save instead of updateOrCreate.
                $record = BotUserRuntimeData::firstOrNew([
                    'bot_id'           => $bot->id,
                    'telegram_user_id' => $telegramUserId,
                    'key'              => $key,
                ]);
                $record->value = $mutation['value'] ?? null;
                $record->save();

                Log::info('[BotHost] '.$successEvent, [
                    'bot_id'         => $bot->id,
                    'target_user_id' => $telegramUserId,
                    'key'            => $key,
                    'operation'      => 'set',
                    'success'        => true,
                ]);

            } catch (Throwable $e) {
                Log::error('[BotHost] '.$failedEvent, [
                    'bot_id'         => $bot->id,
                    'target_user_id' => $telegramUserId,
                    'key'            => $key,
                    'operation'      => $op,
                    'success'        => false,
                    'error'          => $e->getMessage(),
                ]);
                continue;
            }
        }
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function isTimeoutMessage(string $message): bool
    {
        return str_contains(strtolower($message), 'timed out')
            || str_contains(strtolower($message), 'timeout');
    }
}
