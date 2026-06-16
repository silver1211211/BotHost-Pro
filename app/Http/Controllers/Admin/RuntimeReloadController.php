<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RuntimeHelper;
use App\Models\RuntimeReloadLog;
use App\Services\AuditLogService;
use App\Services\RuntimeHelperBundleGenerator;
use App\Services\RuntimeReloadProcessLauncher;
use App\Services\RuntimeReloadReportBuilder;
use App\Services\RuntimeReloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RuntimeReloadController extends Controller
{
    public function __construct(
        private readonly RuntimeHelperBundleGenerator $generator,
        private readonly RuntimeReloadService $reload,
        private readonly RuntimeReloadProcessLauncher $launcher,
        private readonly RuntimeReloadReportBuilder $reports,
        private readonly AuditLogService $audit,
    ) {}

    public function index(): View
    {
        $livePath = $this->generator->livePath();
        $lastLog = RuntimeReloadLog::query()->latest()->first();
        $lastBundleLog = RuntimeReloadLog::query()->where('trigger_type', 'manual_bundle_publish')->latest()->first();
        $lastDryRunLog = RuntimeReloadLog::query()->where('trigger_type', 'docker_refresh_dry_run')->latest()->first();
        $lastLiveLog = RuntimeReloadLog::query()->where('trigger_type', 'docker_refresh_live')->latest()->first();
        $lastDryRunOutput = json_decode((string) $lastDryRunLog?->output, true);
        $lastDryRunWouldRecreateCount = is_array($lastDryRunOutput) ? count($lastDryRunOutput['would_recreate'] ?? []) : 0;

        return view('admin.runtime.reload.index', [
            'reloadRequiredCount' => RuntimeHelper::query()->where('requires_runtime_reload', true)->count(),
            'activeHelperCount' => RuntimeHelper::query()->where('status', 'active')->count(),
            'liveBundleExists' => is_file($livePath),
            'liveBundlePath' => 'runtime-node/admin-helpers-generated.js',
            'liveBundleModifiedAt' => is_file($livePath) ? date('M j, Y H:i:s', filemtime($livePath)) : null,
            'lastLog' => $lastLog,
            'lastBundleLog' => $lastBundleLog,
            'lastDryRunLog' => $lastDryRunLog,
            'lastLiveLog' => $lastLiveLog,
            'lastBundleSummary' => $lastBundleLog?->summaryCounts() ?? [],
            'lastDryRunSummary' => $lastDryRunLog?->summaryCounts() ?? [],
            'lastLiveSummary' => $lastLiveLog?->summaryCounts() ?? [],
            'lastDryRunWouldRecreateCount' => $lastDryRunWouldRecreateCount,
            'processDiagnostics' => $this->launcher->diagnostics(),
            'logs' => RuntimeReloadLog::query()->latest()->limit(10)->get(),
        ]);
    }

    public function publishBundle(Request $request): RedirectResponse
    {
        if ($this->hasActiveTask()) {
            return back()->with('error', 'A runtime reload task is already running. Wait for it to finish before starting another one.');
        }

        $log = RuntimeReloadLog::query()->create([
            'triggered_by' => $request->user()->id,
            'trigger_type' => 'manual_bundle_publish',
            'status' => 'pending',
            'mode' => 'prepare_only',
            'current_step' => 'Queued',
            'steps_total' => 5,
            'steps_completed' => 0,
        ]);

        $this->launcher->start($log, ['publish_bundle' => true]);

        $this->audit->log('runtime', 'runtime.reload.task_started', 'Runtime helper bundle publish started.', $this->auditMetadata($log), $request->user(), 'success', $log);

        return redirect()
            ->route('admin.runtime.reload.show', $log)
            ->with('status', 'Runtime task started. Progress will update automatically.');
    }

    public function dockerRefreshPlan(Request $request): RedirectResponse
    {
        if ($this->hasActiveTask()) {
            return back()->with('error', 'A runtime reload task is already running. Wait for it to finish before starting another one.');
        }

        $log = RuntimeReloadLog::query()->create([
            'triggered_by' => $request->user()->id,
            'trigger_type' => 'docker_refresh_dry_run',
            'status' => 'pending',
            'mode' => 'docker',
            'current_step' => 'Queued',
            'steps_total' => 5,
            'steps_completed' => 0,
        ]);

        $this->launcher->start($log, [
            'docker_refresh' => true,
            'dry_run' => true,
        ]);

        $this->audit->log('runtime', 'runtime.docker_refresh.dry_run_started', 'Docker runtime refresh dry run started.', $this->auditMetadata($log), $request->user(), 'success', $log);

        return redirect()
            ->route('admin.runtime.reload.show', $log)
            ->with('status', 'Runtime task started. Progress will update automatically.');
    }

    public function dockerRefreshLive(Request $request): RedirectResponse
    {
        if ($request->input('confirm_live_refresh') !== 'YES_RECREATE_DOCKER_CONTAINERS') {
            $this->audit->log('runtime', 'runtime.docker_refresh.live_blocked', 'Docker runtime live refresh blocked by missing confirmation.', [
                'log_id' => null,
                'trigger_type' => 'docker_refresh_live',
                'status' => 'blocked',
                'mode' => 'docker',
                'helpers_compiled' => null,
                'containers_affected' => null,
                'containers_ok' => null,
                'containers_failed' => null,
            ], $request->user(), 'failed');

            return back()->with('error', 'Live Docker refresh requires exact confirmation.');
        }

        if ($this->hasActiveTask()) {
            return back()->with('error', 'A runtime reload task is already running. Wait for it to finish before starting another one.');
        }

        $log = RuntimeReloadLog::query()->create([
            'triggered_by' => $request->user()->id,
            'trigger_type' => 'docker_refresh_live',
            'status' => 'pending',
            'mode' => 'docker',
            'current_step' => 'Queued',
            'steps_total' => 6,
            'steps_completed' => 0,
        ]);

        $this->launcher->start($log, [
            'docker_refresh' => true,
            'dry_run' => false,
            'confirm_live_refresh' => true,
        ]);

        $this->audit->log('runtime', 'runtime.docker_refresh.live_started', 'Docker runtime live refresh started.', $this->auditMetadata($log), $request->user(), 'success', $log);

        return redirect()
            ->route('admin.runtime.reload.show', $log)
            ->with('status', 'Runtime task started. Progress will update automatically.');
    }

    public function status(RuntimeReloadLog $log): JsonResponse
    {
        if ($log->isActiveTask() && $log->isStale()) {
            $log->markStaleFailed();
            $log->refresh();
        }

        return response()->json([
            'id' => $log->id,
            'status' => $log->status,
            'trigger_type' => $log->trigger_type,
            'mode' => $log->mode,
            'current_step' => $log->current_step,
            'steps_completed' => $log->steps_completed,
            'steps_total' => $log->steps_total,
            'helpers_compiled' => $log->helpers_compiled,
            'error' => $log->error,
            'containers_affected' => $log->containers_affected,
            'containers_ok' => $log->containers_ok,
            'containers_failed' => $log->containers_failed,
            'output' => $log->parsedOutput(),
            'duration_ms' => $log->duration_ms,
            'completed' => $log->isTerminal(),
        ]);
    }

    public function cancel(Request $request, RuntimeReloadLog $log): RedirectResponse
    {
        if (! $log->isActiveTask()) {
            return back()->with('error', 'Only pending or running runtime reload tasks can be cancelled.');
        }

        $completedAt = now();
        $log->forceFill([
            'status' => 'cancelled',
            'current_step' => 'Cancelled by admin',
            'error' => 'Cancelled by admin',
            'completed_at' => $completedAt,
            'duration_ms' => $log->started_at ? $log->started_at->diffInMilliseconds($completedAt) : null,
        ])->save();

        $this->audit->log('runtime', 'runtime.reload.task_cancelled', 'Runtime reload task marked cancelled.', $this->auditMetadata($log), $request->user(), 'success', $log);

        return back()->with('status', 'Runtime reload task marked cancelled. If the background process is still running, it may finish later, but the log is marked cancelled.');
    }

    public function retry(Request $request, RuntimeReloadLog $log): RedirectResponse
    {
        if (! in_array($log->status, ['failed', 'partial', 'cancelled'], true)) {
            return back()->with('error', 'Only failed, partial, or cancelled runtime reload tasks can be retried.');
        }

        if ($log->trigger_type === 'docker_refresh_live') {
            return back()->with('error', 'Live Docker refresh cannot be retried from log. Start it manually with confirmation.');
        }

        if ($this->hasActiveTask()) {
            return back()->with('error', 'A runtime reload task is already running. Wait for it to finish before starting another one.');
        }

        $options = match ($log->trigger_type) {
            'manual_bundle_publish' => ['publish_bundle' => true],
            'docker_refresh_dry_run' => ['docker_refresh' => true, 'dry_run' => true],
            default => null,
        };

        if ($options === null) {
            return back()->with('error', 'This runtime reload task type cannot be retried.');
        }

        $newLog = RuntimeReloadLog::query()->create([
            'triggered_by' => $request->user()->id,
            'trigger_type' => $log->trigger_type,
            'status' => 'pending',
            'mode' => $log->mode,
            'current_step' => 'Queued',
            'steps_total' => ($log->trigger_type === 'manual_bundle_publish') ? 5 : 5,
            'steps_completed' => 0,
        ]);

        $this->launcher->start($newLog, $options);

        $this->audit->log('runtime', 'runtime.reload.task_retried', 'Runtime reload task retried from log.', [
            ...$this->auditMetadata($newLog),
            'source_log_id' => $log->id,
        ], $request->user(), 'success', $newLog);

        return redirect()
            ->route('admin.runtime.reload.show', $newLog)
            ->with('status', 'Runtime task started. Progress will update automatically.');
    }

    public function logs(Request $request): View
    {
        $logs = RuntimeReloadLog::query()
            ->with('triggeredBy')
            ->when(filled($request->query('status')), fn ($query) => $query->where('status', $request->query('status')))
            ->when(filled($request->query('trigger_type')), fn ($query) => $query->where('trigger_type', $request->query('trigger_type')))
            ->when(filled($request->query('mode')), fn ($query) => $query->where('mode', $request->query('mode')))
            ->when(filled($request->query('date_from')), fn ($query) => $query->whereDate('created_at', '>=', $request->query('date_from')))
            ->when(filled($request->query('date_to')), fn ($query) => $query->whereDate('created_at', '<=', $request->query('date_to')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.runtime.reload.logs', [
            'logs' => $logs,
            'filters' => $request->only(['status', 'trigger_type', 'mode', 'date_from', 'date_to']),
        ]);
    }

    public function exportJson(Request $request, RuntimeReloadLog $log): JsonResponse
    {
        $this->audit->log('runtime', 'runtime.reload.export_json', 'Runtime reload JSON report exported.', $this->auditMetadata($log), $request->user(), 'success', $log);

        return response()
            ->json($this->reports->toArray($log), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            ->header('Content-Disposition', 'attachment; filename="runtime-reload-log-'.$log->id.'.json"');
    }

    public function exportText(Request $request, RuntimeReloadLog $log): Response
    {
        $this->audit->log('runtime', 'runtime.reload.export_text', 'Runtime reload text report exported.', $this->auditMetadata($log), $request->user(), 'success', $log);

        return response($this->reports->toText($log), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="runtime-reload-log-'.$log->id.'.txt"',
        ]);
    }

    public function show(RuntimeReloadLog $log): View
    {
        $log->load('triggeredBy');

        return view('admin.runtime.reload.show', [
            'log' => $log,
        ]);
    }

    private function hasActiveTask(): bool
    {
        RuntimeReloadLog::query()
            ->whereIn('status', ['pending', 'running'])
            ->latest()
            ->get()
            ->each(function (RuntimeReloadLog $log): void {
                if ($log->isStale()) {
                    $log->markStaleFailed();
                }
            });

        return RuntimeReloadLog::query()
            ->whereIn('status', ['pending', 'running'])
            ->exists();
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
