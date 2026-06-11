<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\DockerRuntimeService;
use App\Services\RuntimeSettingsService;
use Illuminate\Console\Command;

class RuntimeHealthCheck extends Command
{
    protected $signature = 'runtime:health-check';
    protected $description = 'Refresh Docker runtime health for active bots.';

    public function handle(RuntimeSettingsService $settings, DockerRuntimeService $runtime): int
    {
        if ($settings->string('runtime_mode', 'local') !== 'docker') {
            return self::SUCCESS;
        }

        $unhealthy = 0;

        Bot::query()->where('status', 'running')->each(function (Bot $bot) use ($runtime, &$unhealthy): void {
            $status = $runtime->getBotContainerStatus($bot);

            if (! ($status['healthy'] ?? false)) {
                $unhealthy++;
                $runtime->ensureBotContainerRunning($bot);
            }
        });

        $this->info("Runtime health check complete. Unhealthy: {$unhealthy}.");
        return self::SUCCESS;
    }
}
