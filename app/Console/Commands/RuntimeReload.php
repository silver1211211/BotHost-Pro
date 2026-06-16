<?php

namespace App\Console\Commands;

use App\Models\RuntimeReloadLog;
use App\Services\RuntimeReloadService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class RuntimeReload extends Command
{
    protected $signature = 'runtime:reload
        {--log-id= : Runtime reload log id}
        {--publish-bundle : Publish generated helper bundle}
        {--docker-refresh : Plan Docker container refresh}
        {--dry-run=1 : Dry run only}
        {--confirm-live-refresh : Confirm live Docker container recreation when dry-run is disabled}';

    protected $description = 'Prepare runtime helper reload work and dry-run Docker refresh planning.';

    public function handle(RuntimeReloadService $reload): int
    {
        $log = $this->option('log-id')
            ? RuntimeReloadLog::query()->find($this->option('log-id'))
            : RuntimeReloadLog::query()->create([
                'trigger_type' => 'cli',
                'status' => 'pending',
                'mode' => $this->option('docker-refresh') ? 'docker' : 'prepare_only',
            ]);

        if (! $log) {
            $this->error('Runtime reload log not found.');

            return self::FAILURE;
        }

        $dryRun = filter_var($this->option('dry-run'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $dryRun = $dryRun ?? true;
        $confirmLiveRefresh = (bool) $this->option('confirm-live-refresh');

        if ($this->option('docker-refresh') && ! $dryRun && ! $confirmLiveRefresh) {
            $this->warn('Live Docker refresh requires --confirm-live-refresh. No containers will be recreated.');
        }

        try {
            $log->forceFill([
                'status' => 'running',
                'started_at' => $log->started_at ?? now(),
                'current_step' => 'Starting runtime reload task',
            ])->save();

            $result = $reload->run($log, [
                'publish_bundle' => (bool) $this->option('publish-bundle'),
                'docker_refresh' => (bool) $this->option('docker-refresh'),
                'dry_run' => $dryRun,
                'confirm_live_refresh' => $confirmLiveRefresh,
            ]);
        } catch (Throwable $exception) {
            $safeError = Str::limit((string) $exception->getMessage(), 2000, '');
            $log->forceFill([
                'status' => 'failed',
                'current_step' => 'Runtime reload task failed',
                'error' => $safeError,
                'completed_at' => now(),
                'duration_ms' => $log->started_at ? $log->started_at->diffInMilliseconds(now()) : null,
            ])->save();

            $this->error($safeError);

            return self::FAILURE;
        }

        $log->refresh();

        $this->line('Runtime reload log: '.$log->id);
        $this->line('Status: '.$log->status);
        $this->line('Current step: '.($log->current_step ?: 'n/a'));

        if ($log->helpers_compiled !== null) {
            $this->line('Helpers compiled: '.$log->helpers_compiled);
        }

        if ($log->containers_affected !== null) {
            $this->line('Containers affected: '.$log->containers_affected);
        }

        if ($log->output) {
            $this->line($log->output);
        }

        if (! ($result['ok'] ?? false)) {
            $this->error($result['error'] ?? 'Runtime reload failed.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
