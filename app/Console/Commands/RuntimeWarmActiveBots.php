<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\DockerRuntimeService;
use App\Services\RuntimeSettingsService;
use Illuminate\Console\Command;

class RuntimeWarmActiveBots extends Command
{
    protected $signature = 'runtime:warm-active-bots';
    protected $description = 'Start Docker runtime containers for active bots.';

    public function handle(RuntimeSettingsService $settings, DockerRuntimeService $runtime): int
    {
        if ($settings->string('runtime_mode', 'local') !== 'docker') {
            $this->info('Runtime mode is local; nothing to warm.');
            return self::SUCCESS;
        }

        $started = 0;
        $failed = 0;

        Bot::query()->where('status', 'running')->each(function (Bot $bot) use ($runtime, &$started, &$failed): void {
            $result = $runtime->startBotContainer($bot);

            if ($result['ok'] ?? false) {
                $started++;
            } else {
                $failed++;
            }
        });

        $this->info("Warm complete. Ready: {$started}. Failed: {$failed}.");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
