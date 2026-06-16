<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'triggered_by',
    'trigger_type',
    'status',
    'mode',
    'current_step',
    'steps_total',
    'steps_completed',
    'helpers_compiled',
    'containers_affected',
    'containers_ok',
    'containers_failed',
    'output',
    'error',
    'started_at',
    'completed_at',
    'duration_ms',
])]
class RuntimeReloadLog extends Model
{
    public const PENDING_STALE_MINUTES = 5;
    public const RUNNING_STALE_MINUTES = 30;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'steps_total' => 'integer',
            'steps_completed' => 'integer',
            'helpers_compiled' => 'integer',
            'containers_affected' => 'integer',
            'containers_ok' => 'integer',
            'containers_failed' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function markRunning(?string $step = null): bool
    {
        return $this->forceFill([
            'status' => 'running',
            'current_step' => $step,
            'started_at' => $this->started_at ?? now(),
        ])->save();
    }

    public function advance(string $step): bool
    {
        return $this->forceFill([
            'current_step' => $step,
            'steps_completed' => ((int) $this->steps_completed) + 1,
        ])->save();
    }

    public function markSuccess(): bool
    {
        $completedAt = now();

        return $this->forceFill([
            'status' => 'success',
            'completed_at' => $completedAt,
            'duration_ms' => $this->started_at ? $this->started_at->diffInMilliseconds($completedAt) : null,
        ])->save();
    }

    public function markFailed(string $error): bool
    {
        $completedAt = now();

        return $this->forceFill([
            'status' => 'failed',
            'error' => $error,
            'completed_at' => $completedAt,
            'duration_ms' => $this->started_at ? $this->started_at->diffInMilliseconds($completedAt) : null,
        ])->save();
    }

    public function appendOutput(string $text): bool
    {
        return $this->forceFill([
            'output' => trim(((string) $this->output).PHP_EOL.$text),
        ])->save();
    }

    public function parsedOutput(): array
    {
        if (blank($this->output)) {
            return [];
        }

        $decoded = json_decode((string) $this->output, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function outputType(): ?string
    {
        $type = $this->parsedOutput()['type'] ?? null;

        return is_string($type) && $type !== '' ? $type : null;
    }

    public function isDryRun(): bool
    {
        return ($this->parsedOutput()['dry_run'] ?? false) === true;
    }

    public function summaryCounts(): array
    {
        $output = $this->parsedOutput();

        return [
            'helpers_total' => (int) ($output['helpers_total'] ?? 0),
            'helpers_compiled' => (int) ($output['helpers_compiled'] ?? $this->helpers_compiled ?? 0),
            'helpers_skipped' => (int) ($output['helpers_skipped'] ?? 0),
            'bots_checked' => (int) ($output['bots_checked'] ?? 0),
            'ready' => count($output['ready'] ?? []),
            'would_recreate' => count($output['would_recreate'] ?? []),
            'recreated' => count($output['recreated'] ?? []),
            'failed' => count($output['failed'] ?? []),
            'skipped' => count($output['skipped'] ?? []),
            'not_running' => count($output['not_running'] ?? []),
            'not_found' => count($output['not_found'] ?? []),
            'unknown' => count($output['unknown'] ?? []),
        ];
    }

    public function isActiveTask(): bool
    {
        return in_array($this->status, ['pending', 'running'], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['success', 'partial', 'failed', 'cancelled'], true);
    }

    public function isStale(): bool
    {
        if (! $this->isActiveTask() || ! $this->updated_at) {
            return false;
        }

        $minutes = $this->status === 'pending'
            ? self::PENDING_STALE_MINUTES
            : self::RUNNING_STALE_MINUTES;

        return $this->updated_at->lte(now()->subMinutes($minutes));
    }

    public function staleReason(): ?string
    {
        if (! $this->isStale()) {
            return null;
        }

        return $this->status === 'pending'
            ? 'Pending task did not start within '.self::PENDING_STALE_MINUTES.' minutes.'
            : 'Running task did not update within '.self::RUNNING_STALE_MINUTES.' minutes.';
    }

    public function markStaleFailed(): bool
    {
        $completedAt = now();

        return $this->forceFill([
            'status' => 'failed',
            'current_step' => 'Task marked stale',
            'error' => 'Task marked failed because it became stale.',
            'completed_at' => $completedAt,
            'duration_ms' => $this->started_at ? $this->started_at->diffInMilliseconds($completedAt) : null,
        ])->save();
    }
}
