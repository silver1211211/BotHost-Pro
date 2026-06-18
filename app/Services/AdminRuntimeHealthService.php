<?php

namespace App\Services;

use App\Models\AdminRuntimeActionLog;
use App\Models\Bot;
use App\Models\RuntimeReloadLog;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class AdminRuntimeHealthService
{
    public function __construct(
        private readonly RuntimeHelperBundleGenerator $generator,
        private readonly RuntimeReloadService $reload,
        private readonly DockerRuntimeService $dockerRuntime,
    ) {}

    public function healthReport(): array
    {
        $expectedHelperHash = $this->generator->liveHash();
        $expectedRuntimeHash = $this->dockerRuntime->runtimeSourceHash();
        $bundleExists = is_file($this->generator->livePath());
        $bots = Bot::query()
            ->orderBy('id')
            ->get()
            ->map(function (Bot $bot) use ($bundleExists, $expectedHelperHash, $expectedRuntimeHash): array {
                $base = [
                    'bot_id' => $bot->id,
                    'bot_name' => $bot->name,
                    'runtime_mode' => $bot->runtime_mode,
                    'container_name' => $bot->container_name,
                    'container_status' => $bot->container_status,
                    'runtime_http_port' => $bot->runtime_http_port,
                    'helper_bundle_hash_matches' => null,
                    'runtime_hash_matches' => null,
                    'localhost_only' => null,
                    'mounted' => null,
                    'read_only' => null,
                    'action_needed' => 'none',
                    'reason' => null,
                ];

                if ($bot->runtime_mode !== 'docker') {
                    return $base + ['reason' => 'not docker mode'];
                }

                if (! $bundleExists) {
                    return $base + ['action_needed' => 'unknown', 'reason' => 'bundle file missing on host'];
                }

                if (! filled($bot->container_name)) {
                    return $base + ['action_needed' => 'unknown', 'reason' => 'container not found'];
                }

                $support = $this->dockerRuntime->inspectAdminRuntimeSupport((string) $bot->container_name);

                if (! ($support['ok'] ?? false)) {
                    return $base + ['action_needed' => 'unknown', 'reason' => 'inspect failed'];
                }

                $reason = (string) ($support['reason'] ?? '');
                $action = ($support['ready'] ?? false) ? 'none' : match ($reason) {
                    'container not running' => 'not running',
                    'container not found' => 'unknown',
                    default => 'recreate',
                };

                return [
                    ...$base,
                    'container_status' => $support['running'] ?? false ? 'running' : ($bot->container_status ?: 'unknown'),
                    'helper_bundle_hash_matches' => $support['helper_bundle_hash_matches'] ?? null,
                    'runtime_hash_matches' => $support['runtime_hash_matches'] ?? null,
                    'localhost_only' => $support['localhost_only'] ?? null,
                    'mounted' => $support['mounted'] ?? null,
                    'read_only' => $support['read_only'] ?? null,
                    'action_needed' => $action,
                    'reason' => $reason,
                    'expected_helper_bundle_hash' => $expectedHelperHash,
                    'expected_runtime_hash' => $expectedRuntimeHash,
                ];
            })
            ->values()
            ->all();
        $issues = collect($bots)->filter(fn (array $row) => $row['action_needed'] !== 'none')->count();

        return [
            'ok' => true,
            'summary' => [
                'runtime_status' => $issues === 0 ? 'ready' : 'needs_attention',
                'bundle_exists' => $bundleExists,
                'containers_checked' => count($bots),
                'issues_found' => $issues,
            ],
            'helper_bundle' => [
                'exists' => $bundleExists,
                'current_hash' => $expectedHelperHash,
                'expected_hash' => $expectedHelperHash,
                'matches' => $bundleExists,
                'last_publish_at' => RuntimeReloadLog::query()
                    ->whereIn('trigger_type', ['manual_bundle_publish', 'manual_publish_and_apply', 'admin_force_apply_helpers'])
                    ->whereIn('status', ['success', 'partial'])
                    ->latest()
                    ->value('completed_at'),
            ],
            'runtime_source' => [
                'current_hash' => $expectedRuntimeHash,
                'expected_hash' => $expectedRuntimeHash,
                'matches' => true,
            ],
            'bots' => $bots,
            'queue' => [
                'connection' => (string) config('queue.default'),
                'restart_timestamp' => Cache::get('illuminate:queue:restart'),
                'supervisor_status' => 'not checked',
            ],
            'cache' => [
                'app_environment' => (string) config('app.env'),
                'debug' => (bool) config('app.debug'),
                'config_cached' => app()->configurationIsCached(),
                'routes_cached' => app()->routesAreCached(),
            ],
            'bridge' => [
                'status' => 'not checked',
                'message' => 'Runtime bridge health is checked from each bot container without exposing tokens.',
            ],
        ];
    }

    public function forceApplyHelpers(User $admin): array
    {
        return $this->logged($admin, 'force_apply_helpers', function () use ($admin): array {
            $reloadLog = RuntimeReloadLog::query()->create([
                'triggered_by' => $admin->id,
                'trigger_type' => 'admin_force_apply_helpers',
                'status' => 'pending',
                'mode' => 'docker',
                'current_step' => 'Queued from Runtime Health Center',
                'steps_total' => 6,
                'steps_completed' => 0,
            ]);

            $result = $this->reload->run($reloadLog, [
                'publish_bundle' => true,
                'docker_refresh' => true,
                'dry_run' => false,
                'confirm_live_refresh' => true,
            ]);

            $reloadLog->refresh();

            return [
                'ok' => (bool) ($result['ok'] ?? false),
                'summary' => $this->reloadSummary($reloadLog, 'Force apply completed.'),
                'reload_log_id' => $reloadLog->id,
                'report' => $this->safePayload($reloadLog->parsedOutput()),
            ];
        });
    }

    public function forceRuntimeRefresh(User $admin): array
    {
        return $this->logged($admin, 'force_runtime_refresh', function () use ($admin): array {
            $reloadLog = RuntimeReloadLog::query()->create([
                'triggered_by' => $admin->id,
                'trigger_type' => 'admin_force_runtime_refresh',
                'status' => 'pending',
                'mode' => 'docker',
                'current_step' => 'Queued from Runtime Health Center',
                'steps_total' => 6,
                'steps_completed' => 0,
            ]);

            $result = $this->reload->run($reloadLog, [
                'publish_bundle' => false,
                'docker_refresh' => true,
                'dry_run' => false,
                'confirm_live_refresh' => true,
            ]);

            $reloadLog->refresh();

            return [
                'ok' => (bool) ($result['ok'] ?? false),
                'summary' => $this->reloadSummary($reloadLog, 'Runtime refresh completed.'),
                'reload_log_id' => $reloadLog->id,
                'report' => $this->safePayload($reloadLog->parsedOutput()),
            ];
        });
    }

    public function recreateBot(User $admin, Bot $bot): array
    {
        return $this->logged($admin, 'force_recreate_bot_runtime', function () use ($bot): array {
            $result = $this->dockerRuntime->recreateBotContainerForHelperBundle($bot, true);

            return [
                'ok' => (bool) ($result['ok'] ?? false),
                'summary' => ($result['ok'] ?? false)
                    ? "Runtime recreated for bot #{$bot->id}."
                    : "Runtime recreate failed for bot #{$bot->id}.",
                'report' => $this->safePayload($result),
            ];
        });
    }

    public function recreateAll(User $admin): array
    {
        return $this->logged($admin, 'force_recreate_all_runtimes', function (): array {
            $recreated = [];
            $failed = [];
            $skipped = [];

            Bot::query()->where('runtime_mode', 'docker')->orderBy('id')->get()->each(function (Bot $bot) use (&$recreated, &$failed, &$skipped): void {
                $result = $this->dockerRuntime->recreateBotContainerForHelperBundle($bot, true);
                if (($result['ok'] ?? false) && ($result['action'] ?? null) === 'recreated') {
                    $recreated[] = $result;
                } elseif ($result['ok'] ?? false) {
                    $skipped[] = $result;
                } else {
                    $failed[] = $result;
                }
            });

            return [
                'ok' => count($failed) === 0,
                'summary' => 'Recreated '.count($recreated).' runtime(s), skipped '.count($skipped).', failed '.count($failed).'.',
                'report' => $this->safePayload(compact('recreated', 'skipped', 'failed')),
            ];
        });
    }

    public function clearCache(User $admin): array
    {
        return $this->logged($admin, 'clear_cache', function (): array {
            Artisan::call('optimize:clear');

            return [
                'ok' => true,
                'summary' => 'Laravel cache cleared.',
                'report' => ['output' => Str::limit(Artisan::output(), 1000, '')],
            ];
        });
    }

    public function restartQueue(User $admin): array
    {
        return $this->logged($admin, 'queue_restart', function (): array {
            Artisan::call('queue:restart');

            return [
                'ok' => true,
                'summary' => 'Queue workers restart signal sent.',
                'report' => ['output' => Str::limit(Artisan::output(), 1000, '')],
            ];
        });
    }

    public function logHealthCheck(User $admin, array $report): AdminRuntimeActionLog
    {
        return AdminRuntimeActionLog::query()->create([
            'admin_user_id' => $admin->id,
            'action' => 'health_check',
            'status' => 'success',
            'summary' => 'Runtime health check completed.',
            'payload_json' => $this->safePayload($report),
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    private function logged(User $admin, string $action, callable $callback): array
    {
        $log = AdminRuntimeActionLog::query()->create([
            'admin_user_id' => $admin->id,
            'action' => $action,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $callback();
            $status = ($result['ok'] ?? false) ? 'success' : 'failed';
            $summary = Str::limit((string) ($result['summary'] ?? 'Runtime action completed.'), 500, '');

            $log->update([
                'status' => $status,
                'summary' => $summary,
                'payload_json' => $this->safePayload($result),
                'error_message' => $status === 'failed' ? Str::limit((string) data_get($result, 'report.error', ''), 2000, '') : null,
                'finished_at' => now(),
            ]);

            return $result + ['action_log_id' => $log->id];
        } catch (Throwable $exception) {
            $message = Str::limit($this->sanitize((string) $exception->getMessage()), 2000, '');
            $log->update([
                'status' => 'failed',
                'summary' => 'Runtime action failed.',
                'error_message' => $message,
                'finished_at' => now(),
            ]);

            return ['ok' => false, 'summary' => 'Runtime action failed.', 'error' => $message, 'action_log_id' => $log->id];
        }
    }

    private function botRuntimeRow(array $row): array
    {
        $action = match ($row['status'] ?? null) {
            'ready', 'skipped' => 'none',
            'recreate_required' => 'recreate',
            'not_running' => 'not running',
            default => 'unknown',
        };

        return [
            'bot_id' => $row['bot_id'] ?? null,
            'bot_name' => $row['bot_name'] ?? null,
            'runtime_mode' => $row['runtime_mode'] ?? null,
            'container_name' => $row['container_name'] ?? null,
            'container_status' => $row['container_status'] ?? ($row['status'] ?? null),
            'runtime_http_port' => $row['runtime_http_port'] ?? null,
            'helper_bundle_hash_matches' => $row['helper_bundle_hash_matches'] ?? null,
            'runtime_hash_matches' => $row['runtime_hash_matches'] ?? null,
            'localhost_only' => $row['localhost_only'] ?? null,
            'mounted' => $row['mounted'] ?? null,
            'read_only' => $row['read_only'] ?? null,
            'action_needed' => $action,
            'reason' => $row['reason'] ?? null,
        ];
    }

    private function reloadSummary(RuntimeReloadLog $log, string $fallback): string
    {
        $counts = $log->summaryCounts();

        return "{$fallback} Checked {$counts['bots_checked']} bot(s), recreated {$counts['recreated']}, skipped {$counts['skipped']}, failed {$counts['failed']}.";
    }

    private function safePayload(mixed $value): mixed
    {
        if (is_array($value)) {
            $safe = [];
            foreach ($value as $key => $item) {
                $keyString = is_string($key) ? $key : (string) $key;
                if (preg_match('/token|secret|password|api[_-]?key|encrypted/i', $keyString)) {
                    $safe[$key] = '[redacted]';
                    continue;
                }
                $safe[$key] = $this->safePayload($item);
            }

            return $safe;
        }

        return is_string($value) ? $this->sanitize($value) : $value;
    }

    private function sanitize(string $value): string
    {
        return str($value)
            ->replaceMatches('/\d{6,}:[A-Za-z0-9_-]{10,}/', '[redacted-token]')
            ->replaceMatches('/(password|secret|token|api[_-]?key)=\S+/i', '$1=[redacted]')
            ->replaceMatches('#[A-Z]:\\\\[^\s,;]+#i', '[redacted-path]')
            ->replaceMatches('#/(?:home|var|srv|www)/[^\s,;]+#i', '[redacted-path]')
            ->limit(2000, '')
            ->toString();
    }
}
