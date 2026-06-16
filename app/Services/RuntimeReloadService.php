<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\RuntimeHelper;
use App\Models\RuntimeReloadLog;
use Illuminate\Support\Str;

class RuntimeReloadService
{
    public function __construct(
        private readonly RuntimeHelperBundleGenerator $generator,
        private readonly DockerRuntimeService $dockerRuntime,
        private readonly ?AuditLogService $audit = null,
    ) {}

    public function publishBundle(RuntimeReloadLog $log): array
    {
        $log->forceFill([
            'mode' => $log->mode ?: 'prepare_only',
            'current_step' => 'Preparing bundle publish',
            'steps_total' => max((int) $log->steps_total, 5),
            'steps_completed' => max((int) $log->steps_completed, 1),
        ])->save();

        $log->forceFill([
            'current_step' => 'Generating helper bundle',
            'steps_completed' => 2,
        ])->save();

        $report = $this->generator->publish();

        if (! ($report['ok'] ?? false)) {
            $log->forceFill([
                'current_step' => 'Bundle publish failed',
                'helpers_compiled' => (int) ($report['helpers_compiled'] ?? 0),
                'output' => $this->publishSummary($report),
                'error' => Str::limit((string) ($report['error'] ?? 'Helper bundle publish failed.'), 2000, ''),
            ])->save();

            $this->audit?->log('runtime', 'runtime.helper_bundle.publish_failed', 'Runtime helper bundle publish failed.', $this->auditMetadata($log), null, 'failed', $log);

            return $report;
        }

        $log->forceFill([
            'current_step' => 'Publishing bundle',
            'steps_completed' => 4,
        ])->save();

        $compiled = collect($report['compiled'] ?? []);
        if ((int) ($report['helpers_skipped'] ?? 0) === 0 && $compiled->isNotEmpty()) {
            RuntimeHelper::query()
                ->whereIn('id', $compiled->pluck('id')->filter()->all())
                ->where('status', 'active')
                ->where('expose_to_bot_code', true)
                ->update(['requires_runtime_reload' => false]);
        }

        $log->forceFill([
            'current_step' => 'Finalizing bundle publish',
            'steps_completed' => 5,
            'helpers_compiled' => (int) ($report['helpers_compiled'] ?? 0),
            'output' => $this->publishSummary($report),
        ])->save();

        $this->audit?->log('runtime', 'runtime.helper_bundle.published', 'Runtime helper bundle published.', $this->auditMetadata($log), null, 'success', $log);

        return $report;
    }

    public function planDockerRefresh(RuntimeReloadLog $log): array
    {
        $log->forceFill([
            'current_step' => 'Checking bundle file',
            'steps_completed' => max((int) $log->steps_completed, 2),
        ])->save();

        $bundleExists = is_file($this->generator->livePath());
        $ready = [];
        $wouldRecreate = [];
        $notRunning = [];
        $notFound = [];
        $unknown = [];
        $planned = [];
        $skipped = [];

        $log->forceFill([
            'current_step' => 'Inspecting containers',
            'steps_completed' => max((int) $log->steps_completed, 3),
        ])->save();

        Bot::query()
            ->orderBy('id')
            ->get()
            ->each(function (Bot $bot) use ($bundleExists, &$ready, &$wouldRecreate, &$notRunning, &$notFound, &$unknown, &$planned, &$skipped): void {
                $entry = [
                    'bot_id' => $bot->id,
                    'bot_name' => $bot->name,
                    'runtime_mode' => $bot->runtime_mode,
                    'container_name' => $bot->container_name,
                    'container_status' => $bot->container_status,
                    'runtime_http_port' => $bot->runtime_http_port,
                ];

                if ($bot->runtime_mode !== 'docker') {
                    $skipped[] = $entry + ['reason' => 'not docker mode'];
                    $planned[] = $entry + ['status' => 'skipped', 'action' => 'none', 'reason' => 'not docker mode'];

                    return;
                }

                if (! $bundleExists) {
                    $unknown[] = $entry + ['status' => 'unknown', 'action' => 'none', 'reason' => 'bundle file missing on host'];
                    $planned[] = $entry + ['status' => 'unknown', 'action' => 'none', 'reason' => 'bundle file missing on host'];

                    return;
                }

                if (! filled($bot->container_name)) {
                    $notFound[] = $entry + ['status' => 'not_found', 'action' => 'none', 'reason' => 'container not found'];
                    $planned[] = $entry + ['status' => 'not_found', 'action' => 'none', 'reason' => 'container not found'];

                    return;
                }

                $mount = $this->dockerRuntime->hasAdminHelperBundleMount((string) $bot->container_name);

                if (! ($mount['ok'] ?? false)) {
                    $unknown[] = $entry + ['status' => 'unknown', 'action' => 'manual_check', 'reason' => 'inspect failed'];
                    $planned[] = $entry + ['status' => 'unknown', 'action' => 'manual_check', 'reason' => 'inspect failed'];

                    return;
                }

                if (! ($mount['exists'] ?? false)) {
                    $notFound[] = $entry + ['status' => 'not_found', 'action' => 'none', 'reason' => 'container not found'];
                    $planned[] = $entry + ['status' => 'not_found', 'action' => 'none', 'reason' => 'container not found'];

                    return;
                }

                if (! ($mount['running'] ?? false)) {
                    $notRunning[] = $entry + ['status' => 'not_running', 'action' => 'none', 'reason' => 'container not running'];
                    $planned[] = $entry + ['status' => 'not_running', 'action' => 'none', 'reason' => 'container not running'];

                    return;
                }

                if (($mount['mounted'] ?? false) && ($mount['read_only'] ?? false)) {
                    $ready[] = $entry + ['status' => 'ready', 'action' => 'none', 'reason' => 'bundle mount already present'];
                    $planned[] = $entry + ['status' => 'ready', 'action' => 'none', 'reason' => 'bundle mount already present'];

                    return;
                }

                $reason = ($mount['mounted'] ?? false) ? 'bundle mounted but not read-only' : 'bundle mount missing';
                $wouldRecreate[] = $entry + ['status' => 'recreate_required', 'action' => 'would_recreate', 'reason' => $reason];
                $planned[] = $entry + ['status' => 'recreate_required', 'action' => 'would_recreate', 'reason' => $reason];
            });

        return [
            'ok' => true,
            'type' => 'docker_refresh',
            'dry_run' => true,
            'bundle_exists' => $bundleExists,
            'bots_checked' => count($planned),
            'ready' => $ready,
            'would_recreate' => $wouldRecreate,
            'not_running' => $notRunning,
            'not_found' => $notFound,
            'unknown' => $unknown,
            'skipped' => $skipped,
            'planned' => $planned,
        ];
    }

    public function refreshDockerContainers(RuntimeReloadLog $log, bool $dryRun = true, bool $confirmLiveRefresh = false): array
    {
        if (! $dryRun) {
            if (! $confirmLiveRefresh) {
                return $this->blockLiveRefresh($log);
            }

            $plan = $this->planDockerRefresh($log);

            if (! ($plan['bundle_exists'] ?? false)) {
                return $this->blockLiveRefresh($log);
            }

            $targets = collect($plan['would_recreate'] ?? []);
            $recreated = [];
            $failed = [];
            $skipped = collect([
                ...($plan['ready'] ?? []),
                ...($plan['not_running'] ?? []),
                ...($plan['not_found'] ?? []),
                ...($plan['unknown'] ?? []),
                ...($plan['skipped'] ?? []),
            ])->values()->all();

            $log->forceFill([
                'current_step' => 'Recreating required containers',
                'steps_total' => 6,
                'steps_completed' => 4,
            ])->save();

            $bots = Bot::query()
                ->whereIn('id', $targets->pluck('bot_id')->filter()->all())
                ->get()
                ->keyBy('id');

            foreach ($targets as $target) {
                $bot = $bots->get($target['bot_id'] ?? null);

                if (! $bot) {
                    $failed[] = $target + [
                        'action' => 'failed',
                        'reason' => 'bot not found',
                        'error' => 'Bot not found.',
                    ];
                } else {
                    $result = $this->dockerRuntime->recreateBotContainerForHelperBundle($bot);

                    if ($result['ok'] ?? false) {
                        $recreated[] = $target + $result;
                    } else {
                        $failed[] = $target + $result;
                    }
                }

                $log->forceFill([
                    'steps_completed' => min(5, ((int) $log->steps_completed) + 1),
                ])->save();
            }

            $status = count($failed) === 0
                ? 'success'
                : (count($recreated) > 0 ? 'partial' : 'failed');

            $report = [
                'ok' => $status !== 'failed',
                'type' => 'docker_refresh',
                'status' => $status,
                'dry_run' => false,
                'bundle_exists' => true,
                'bots_checked' => $plan['bots_checked'] ?? 0,
                'recreated' => $recreated,
                'failed' => $failed,
                'skipped' => $skipped,
                'ready' => $plan['ready'] ?? [],
                'not_running' => $plan['not_running'] ?? [],
                'not_found' => $plan['not_found'] ?? [],
                'unknown' => $plan['unknown'] ?? [],
            ];

            $log->forceFill([
                'status' => $status,
                'current_step' => 'Writing live refresh report',
                'steps_completed' => 6,
                'containers_affected' => count($recreated),
                'containers_ok' => count($recreated),
                'containers_failed' => count($failed),
                'output' => $this->jsonSummary($report),
                'error' => count($failed) > 0 ? 'One or more Docker containers failed to refresh.' : null,
                'completed_at' => now(),
                'duration_ms' => $log->started_at ? $log->started_at->diffInMilliseconds(now()) : null,
            ])->save();

            return $report;
        }

        $report = $this->planDockerRefresh($log);

        $log->forceFill([
            'current_step' => 'Writing dry-run report',
            'steps_completed' => 5,
            'containers_affected' => count($report['would_recreate'] ?? []),
            'containers_ok' => count($report['ready'] ?? []),
            'containers_failed' => count($report['unknown'] ?? []),
            'output' => $this->jsonSummary($report),
        ])->save();

        return $report;
    }

    private function blockLiveRefresh(RuntimeReloadLog $log): array
    {
        $report = [
            'ok' => false,
            'type' => 'docker_refresh',
            'dry_run' => false,
            'bundle_exists' => is_file($this->generator->livePath()),
            'error' => 'Live Docker refresh requires explicit confirmation.',
        ];

        $log->forceFill([
            'status' => 'failed',
            'current_step' => 'Live Docker refresh blocked',
            'containers_affected' => 0,
            'containers_ok' => 0,
            'containers_failed' => 0,
            'output' => $this->jsonSummary($report),
            'error' => $report['error'],
            'completed_at' => now(),
            'duration_ms' => $log->started_at ? $log->started_at->diffInMilliseconds(now()) : null,
        ])->save();

        return $report;
    }

    public function run(RuntimeReloadLog $log, array $options = []): array
    {
        $started = now();
        $publishBundle = (bool) ($options['publish_bundle'] ?? false);
        $dockerRefresh = (bool) ($options['docker_refresh'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? true);
        $stepsTotal = ($publishBundle ? 1 : 0) + ($dockerRefresh ? 1 : 0);
        $reports = [];

        $log->forceFill([
            'status' => 'running',
            'started_at' => $log->started_at ?? $started,
            'steps_total' => $dockerRefresh && ! $dryRun ? 6 : 5,
            'steps_completed' => 0,
        ])->save();

        if ($publishBundle) {
            $log->forceFill(['current_step' => 'Preparing bundle publish'])->save();
            $reports['publish_bundle'] = $this->publishBundle($log);

            if (! ($reports['publish_bundle']['ok'] ?? false)) {
                return $this->finishFailed($log, $reports, (string) ($reports['publish_bundle']['error'] ?? 'Helper bundle publish failed.'));
            }
        }

        if ($dockerRefresh) {
            $log->forceFill([
                'current_step' => $dryRun ? 'Preparing Docker refresh plan' : 'Preparing live Docker refresh',
                'steps_completed' => 1,
            ])->save();
            $reports['docker_refresh'] = $this->refreshDockerContainers($log, $dryRun, (bool) ($options['confirm_live_refresh'] ?? false));

            if (! ($reports['docker_refresh']['ok'] ?? false)) {
                return $this->finishFailed($log, $reports, (string) ($reports['docker_refresh']['error'] ?? 'Docker refresh failed.'));
            }
        }

        if (! $publishBundle && ! $dockerRefresh) {
            $reports['noop'] = ['ok' => true, 'message' => 'No runtime reload action selected.'];
            $log->forceFill([
                'current_step' => 'No action selected',
                'output' => $this->jsonSummary($reports),
            ])->save();
        }

        $log->refresh();

        if ($log->status !== 'partial') {
            $log->forceFill([
                'status' => 'success',
                'completed_at' => now(),
                'duration_ms' => $log->started_at ? $log->started_at->diffInMilliseconds(now()) : null,
            ])->save();
        }

        return ['ok' => true, 'reports' => $reports];
    }

    private function finishFailed(RuntimeReloadLog $log, array $reports, string $error): array
    {
        $log->forceFill([
            'status' => 'failed',
            'error' => Str::limit($error, 2000, ''),
            'output' => $log->output ?: $this->jsonSummary($reports),
            'completed_at' => now(),
            'duration_ms' => $log->started_at ? $log->started_at->diffInMilliseconds(now()) : null,
        ])->save();

        return ['ok' => false, 'reports' => $reports, 'error' => $error];
    }

    private function publishSummary(array $report): string
    {
        return $this->jsonSummary([
            'type' => 'bundle_publish',
            'helpers_total' => $report['helpers_total'] ?? 0,
            'helpers_compiled' => $report['helpers_compiled'] ?? 0,
            'helpers_skipped' => $report['helpers_skipped'] ?? 0,
            'compiled' => collect($report['compiled'] ?? [])
                ->map(fn (array $helper) => [
                    'id' => $helper['id'] ?? null,
                    'name' => $helper['name'] ?? null,
                ])
                ->values()
                ->all(),
            'skipped' => collect($report['skipped'] ?? [])
                ->map(fn (array $helper) => [
                    'name' => $helper['name'] ?? null,
                    'reason' => $helper['reason'] ?? null,
                ])
                ->values()
                ->all(),
        ]);
    }

    private function jsonSummary(array $report): string
    {
        return Str::limit(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 6000, "\n...[truncated]");
    }

    private function auditMetadata(RuntimeReloadLog $log): array
    {
        return [
            'log_id' => $log->id,
            'trigger_type' => $log->trigger_type,
            'status' => $log->status,
            'mode' => $log->mode,
            'helpers_compiled' => $log->helpers_compiled,
            'containers_affected' => $log->containers_affected,
            'containers_ok' => $log->containers_ok,
            'containers_failed' => $log->containers_failed,
        ];
    }
}
